<?php

namespace App\Http\Controllers;

use App\Models\PeriodoNomina;
use App\Models\Personal;
use App\Models\ReciboNomina;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NominaController extends Controller
{
    // =====================================================================
    //  PANEL DE NÓMINA
    // =====================================================================

    public function index()
    {
        $periodos = PeriodoNomina::withCount([
                'recibos',
                'recibos as pagados_count' => fn ($q) => $q->where('estado', 'Pagado'),
            ])
            ->withSum('recibos as total_neto', 'neto')
            ->withSum(['recibos as neto_pendiente' => fn ($q) => $q->where('estado', '!=', 'Pagado')], 'neto')
            ->orderByDesc('fecha_inicio')
            ->get();

        $empleados = $this->empleadosActivos();
        $ultimos   = $this->ultimoPeriodoPorEmpleado($empleados->pluck('id'));

        $empleadosJs = $empleados->map(function ($p) use ($ultimos) {
            $tipo = $this->tipoPago($p);
            [$ini, $fin] = $this->periodoSiguiente($tipo, $ultimos[$p->id] ?? null);

            return [
                'id'        => $p->id,
                'nombre'    => $this->nombre($p),
                'puesto'    => (string) ($p->especialidad_principal ?? ''),
                'salario'   => (float) ($p->salario_diario ?? 0),
                'tipo_pago' => $tipo,
                'ultimo'    => optional($ultimos[$p->id] ?? null)->format('Y-m-d'),
                'prox_ini'  => $ini->format('Y-m-d'),
                'prox_fin'  => $fin->format('Y-m-d'),
                'ficha'     => route('personal.nomina.empleado', $p->id),
            ];
        })->sortBy('prox_fin')->values();

        $resumen = [
            'pendiente'  => (float) ReciboNomina::where('estado', '!=', 'Pagado')->sum('neto'),
            'pagadoMes'  => (float) ReciboNomina::where('estado', 'Pagado')
                                ->whereYear('fecha_pago', now()->year)->whereMonth('fecha_pago', now()->month)->sum('neto'),
            'pagadoAnio' => (float) ReciboNomina::where('estado', 'Pagado')->whereYear('fecha_pago', now()->year)->sum('neto'),
            'empleados'  => $empleados->count(),
            'sinSalario' => $empleados->filter(fn ($p) => (float) $p->salario_diario <= 0)->count(),
        ];

        return view('personal.nomina.index', [
            'periodos'  => $periodos,
            'resumen'   => $resumen,
            'empleados' => $empleadosJs,
        ]);
    }

    /** Crea un periodo solo con los empleados elegidos. */
    public function store(Request $request)
    {
        $this->autorizar('crear');

        $data = $request->validate([
            'tipo'            => 'required|in:' . implode(',', PeriodoNomina::TIPOS),
            'fecha_inicio'    => 'required|date',
            'fecha_fin'       => 'required|date|after_or_equal:fecha_inicio',
            'fecha_pago'      => 'nullable|date|after_or_equal:fecha_inicio',
            'notas'           => 'nullable|string|max:1000',
            'personal_ids'    => 'required|array|min:1',
            'personal_ids.*'  => 'integer|exists:personals,id',
        ], [
            'personal_ids.required'     => 'Elige al menos un empleado para este pago.',
            'personal_ids.min'          => 'Elige al menos un empleado para este pago.',
            'fecha_fin.after_or_equal'  => 'La fecha final no puede ser anterior a la inicial.',
            'fecha_pago.after_or_equal' => 'La fecha de pago no puede ser anterior al inicio del periodo.',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['personal_ids'])));

        // Un empleado no puede cobrar dos veces el mismo tramo de fechas (los pagos ESPECIALES sí se permiten)
        if ($data['tipo'] !== 'ESPECIAL') {
            $repetidos = $this->empleadosConTraslape($ids, $data['fecha_inicio'], $data['fecha_fin']);
            if ($repetidos->isNotEmpty()) {
                return back()->withInput()->withErrors([
                    'personal_ids' => 'Ya tienen un recibo en esas fechas: ' . $repetidos->implode(', ') . '. Quítalos o usa un pago especial.',
                ]);
            }
        }

        $periodo = DB::transaction(function () use ($data, $ids) {
            $periodo = PeriodoNomina::create(collect($data)->except('personal_ids')->all() + [
                'estado'     => 'Abierto',
                'creado_por' => auth()->id(),
            ]);

            $this->generarRecibos($periodo, $ids);

            return $periodo;
        });

        $n = $periodo->recibos()->count();

        return redirect()->route('personal.nomina.show', $periodo)
            ->with('success', $n === 1 ? 'Pago creado para 1 empleado.' : "Periodo creado con {$n} recibos.");
    }

    public function show(PeriodoNomina $periodo)
    {
        $periodo->load('recibos.personal');

        $incluidos   = $periodo->recibos->pluck('personal_id')->all();
        $disponibles = $this->empleadosActivos()
            ->reject(fn ($p) => in_array($p->id, $incluidos))
            ->map(fn ($p) => [
                'id'        => $p->id,
                'nombre'    => $this->nombre($p),
                'tipo_pago' => $this->tipoPago($p),
                'salario'   => (float) ($p->salario_diario ?? 0),
            ])
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('personal.nomina.show', [
            'periodo'     => $periodo,
            'recibos'     => $periodo->recibos
                ->map(fn ($r) => $this->reciboParaVista($r))
                ->sortBy('empleado', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'disponibles' => $disponibles,
        ]);
    }

    /** Cerrar (bloquear edición) o reabrir un periodo. El estado "Pagado" se pone solo cuando se pagan todos los recibos. */
    public function cambiarEstado(Request $request, PeriodoNomina $periodo)
    {
        $this->autorizar('editar');

        $data = $request->validate(['estado' => 'required|in:Abierto,Cerrado']);

        if ($periodo->estado === 'Pagado') {
            return back()->with('error', 'Este periodo ya está pagado por completo; revierte un pago si necesitas cambiarlo.');
        }

        $periodo->update(['estado' => $data['estado']]);

        return back()->with('success', $data['estado'] === 'Cerrado'
            ? 'Periodo cerrado. Los recibos pendientes ya no se pueden editar, pero sí pagar.'
            : 'El periodo se reabrió para edición.');
    }

    public function destroy(PeriodoNomina $periodo)
    {
        $this->autorizar('eliminar');

        $n = $periodo->recibos()->count();
        $periodo->delete();   // los recibos se borran en cascada

        return response()->json([
            'success'  => true,
            'message'  => $n ? "Periodo y sus {$n} recibo(s) eliminados." : 'Periodo eliminado.',
            'redirect' => route('personal.nomina.index'),
        ]);
    }

    // =====================================================================
    //  EMPLEADOS DENTRO DEL PERIODO
    // =====================================================================

    public function agregarEmpleado(Request $request, PeriodoNomina $periodo)
    {
        $this->autorizar('editar');

        if (!$periodo->estaAbierto()) {
            return back()->with('error', 'Reabre el periodo para agregar empleados.');
        }

        $data = $request->validate(['personal_id' => 'required|integer|exists:personals,id']);

        if ($periodo->recibos()->where('personal_id', $data['personal_id'])->exists()) {
            return back()->with('error', 'Ese empleado ya está en el periodo.');
        }

        if ($periodo->tipo !== 'ESPECIAL') {
            $repetidos = $this->empleadosConTraslape([$data['personal_id']], $periodo->fecha_inicio, $periodo->fecha_fin, $periodo->id);
            if ($repetidos->isNotEmpty()) {
                return back()->with('error', $repetidos->first() . ' ya tiene un recibo en esas fechas.');
            }
        }

        $this->generarRecibos($periodo, [$data['personal_id']]);
        $periodo->sincronizarEstado();

        return back()->with('success', 'Empleado agregado al periodo.');
    }

    /** Elimina un recibo (pendiente o pagado). Si el periodo se queda vacío, también se elimina. */
    public function quitarRecibo(ReciboNomina $recibo)
    {
        // Quitar un pendiente de un periodo abierto es "editar"; borrar uno pagado o de un periodo cerrado es "eliminar"
        $this->autorizar($recibo->estaPagado() || !$recibo->periodo->estaAbierto() ? 'eliminar' : 'editar');

        $periodo = $recibo->periodo;
        $recibo->delete();

        if ($periodo->recibos()->count() === 0) {
            $periodo->delete();

            return response()->json([
                'success'           => true,
                'message'           => 'Recibo eliminado. El periodo quedó vacío y también se eliminó.',
                'periodo_eliminado' => true,
                'redirect'          => route('personal.nomina.index'),
            ]);
        }

        $periodo->sincronizarEstado();

        return response()->json(['success' => true, 'message' => 'Recibo eliminado.', 'estado' => $periodo->estado]);
    }

    // =====================================================================
    //  RECIBOS
    // =====================================================================

    public function updateRecibo(Request $request, ReciboNomina $recibo)
    {
        $this->autorizar('editar');

        if ($recibo->estaPagado()) {
            return response()->json(['success' => false, 'message' => 'Este recibo ya se pagó; revierte el pago para editarlo.'], 422);
        }
        if (!$recibo->periodo->estaAbierto()) {
            return response()->json(['success' => false, 'message' => 'El periodo está cerrado; reábrelo para editar.'], 422);
        }

        $data = $request->validate([
            'salario_diario'    => 'required|numeric|min:0|max:999999',
            'dias_trabajados'   => 'required|numeric|min:0|max:31',
            'horas_extra'       => 'nullable|numeric|min:0|max:9999999',
            'bonos'             => 'nullable|numeric|min:0|max:9999999',
            'isr'               => 'nullable|numeric|min:0|max:9999999',
            'imss'              => 'nullable|numeric|min:0|max:9999999',
            'otras_deducciones' => 'nullable|numeric|min:0|max:9999999',
            'notas'             => 'nullable|string|max:1000',
            'guardar_salario'   => 'nullable|boolean',
        ]);

        $recibo->fill(collect($data)->except(['guardar_salario', 'notas'])->map(fn ($v) => $v ?? 0)->all());
        $recibo->notas = $data['notas'] ?? null;
        $recibo->recalcular()->save();

        if ($request->boolean('guardar_salario') && $recibo->personal) {
            $recibo->personal->forceFill(['salario_diario' => $data['salario_diario']])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Recibo actualizado.',
            'recibo'  => $this->reciboParaVista($recibo->fresh('personal')),
        ]);
    }

    /** Paga uno o varios recibos del periodo (cada empleado puede pagarse en distinto momento). */
    public function pagar(Request $request, PeriodoNomina $periodo)
    {
        $this->autorizar('editar');

        $data = $request->validate([
            'recibo_ids'   => 'required|array|min:1',
            'recibo_ids.*' => 'integer',
            'fecha_pago'   => 'required|date',
            'metodo_pago'  => 'required|in:' . implode(',', ReciboNomina::METODOS),
            'referencia'   => 'nullable|string|max:100',
        ], [
            'recibo_ids.required' => 'Selecciona al menos un empleado para pagar.',
        ]);

        $recibos = $periodo->recibos()->whereIn('id', $data['recibo_ids'])->where('estado', '!=', 'Pagado')->get();

        if ($recibos->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Los recibos seleccionados ya estaban pagados.'], 422);
        }

        $negativos = $recibos->filter(fn ($r) => $r->neto < 0);
        if ($negativos->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Hay recibos con neto negativo; corrígelos antes de pagar.'], 422);
        }

        DB::transaction(function () use ($recibos, $data, $periodo) {
            foreach ($recibos as $r) {
                $r->update([
                    'estado'      => 'Pagado',
                    'fecha_pago'  => $data['fecha_pago'],
                    'metodo_pago' => $data['metodo_pago'],
                    'referencia'  => $data['referencia'] ?? null,
                    'pagado_por'  => auth()->id(),
                ]);
            }
            $periodo->sincronizarEstado();
        });

        $periodo->load('recibos.personal');
        $n = $recibos->count();

        return response()->json([
            'success' => true,
            'message' => $n === 1 ? 'Pago registrado.' : "Se registraron {$n} pagos.",
            'estado'  => $periodo->estado,
            'recibos' => $periodo->recibos->map(fn ($r) => $this->reciboParaVista($r))->values(),
        ]);
    }

    public function revertirPago(ReciboNomina $recibo)
    {
        $this->autorizar('editar');

        if (!$recibo->estaPagado()) {
            return response()->json(['success' => false, 'message' => 'Este recibo no está pagado.'], 422);
        }

        $recibo->update(['estado' => 'Pendiente', 'fecha_pago' => null, 'metodo_pago' => null, 'referencia' => null, 'pagado_por' => null]);
        $recibo->periodo->sincronizarEstado();

        return response()->json([
            'success' => true,
            'message' => 'Pago revertido; el recibo vuelve a estar pendiente.',
            'estado'  => $recibo->periodo->estado,
            'recibo'  => $this->reciboParaVista($recibo->fresh('personal')),
        ]);
    }

    public function pdf(ReciboNomina $recibo)
    {
        $recibo->load('periodo', 'personal');

        $datos = [
            'recibo'   => $recibo,
            'empleado' => $this->nombre($recibo->personal),
            // Ojo: no se llama $clinica porque el sistema ya comparte una variable global con ese nombre
            'datosClinica' => $this->datosClinica(),
        ];

        $archivo = 'recibo-nomina-' . $recibo->id . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('personal.nomina.recibo-pdf', $datos)->stream($archivo);
        }

        return view('personal.nomina.recibo-pdf', $datos + ['imprimible' => true]);
    }

    // =====================================================================
    //  FICHA DE NÓMINA DE UN EMPLEADO
    // =====================================================================

    public function empleado(Personal $personal)
    {
        $recibos = ReciboNomina::with('periodo')
            ->where('personal_id', $personal->id)
            ->get()
            ->sortByDesc(fn ($r) => optional($r->periodo)->fecha_inicio)
            ->values();

        $tipo = $this->tipoPago($personal);
        $ultimo = $this->ultimoPeriodoPorEmpleado(collect([$personal->id]))[$personal->id] ?? null;
        [$proxIni, $proxFin] = $this->periodoSiguiente($tipo, $ultimo);

        $pagados = $recibos->where('estado', 'Pagado');

        return view('personal.nomina.empleado', [
            'personal' => $personal,
            'empleado' => [
                'id'        => $personal->id,
                'nombre'    => $this->nombre($personal),
                'puesto'    => (string) ($personal->especialidad_principal ?? ''),
                'turno'     => (string) ($personal->turno ?? ''),
                'estado'    => (string) ($personal->estado ?? 'Activo'),
                'salario'   => (float) ($personal->salario_diario ?? 0),
                'tipo_pago' => $tipo,
                'prox_ini'  => $proxIni->format('Y-m-d'),
                'prox_fin'  => $proxFin->format('Y-m-d'),
            ],
            'resumen' => [
                'pagadoAnio'   => (float) $pagados->filter(fn ($r) => optional($r->fecha_pago)->year === now()->year)->sum('neto'),
                'pendiente'    => (float) $recibos->where('estado', '!=', 'Pagado')->sum('neto'),
                'recibos'      => $recibos->count(),
                'ultimoPago'   => optional($pagados->sortByDesc('fecha_pago')->first())->fecha_pago?->format('Y-m-d'),
            ],
            'recibos' => $recibos->map(fn ($r) => $this->reciboParaVista($r) + [
                'periodo_id'   => $r->periodo_id,
                'periodo_tipo' => optional($r->periodo)->tipo,
                'inicio'       => optional(optional($r->periodo)->fecha_inicio)->format('Y-m-d'),
                'fin'          => optional(optional($r->periodo)->fecha_fin)->format('Y-m-d'),
                'url_periodo'  => route('personal.nomina.show', $r->periodo_id),
            ])->values(),
        ]);
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    /** Crea recibos para los empleados indicados que aún no estén en el periodo. */
    private function generarRecibos(PeriodoNomina $periodo, array $ids): int
    {
        $existentes = $periodo->recibos()->pluck('personal_id')->all();
        $dias = $periodo->dias();
        $creados = 0;

        foreach (Personal::whereIn('id', $ids)->get() as $empleado) {
            if (in_array($empleado->id, $existentes)) {
                continue;
            }

            $recibo = new ReciboNomina([
                'periodo_id'      => $periodo->id,
                'personal_id'     => $empleado->id,
                'salario_diario'  => (float) ($empleado->salario_diario ?? 0),
                'dias_trabajados' => $periodo->tipo === 'ESPECIAL' ? 0 : $dias,
                'estado'          => 'Pendiente',
            ]);
            $recibo->recalcular()->save();
            $creados++;
        }

        return $creados;
    }

    /** Nombres de los empleados que ya tienen un recibo (no especial) que se cruza con esas fechas. */
    private function empleadosConTraslape(array $ids, $inicio, $fin, ?int $excluirPeriodo = null): Collection
    {
        return ReciboNomina::with('personal')
            ->whereIn('personal_id', $ids)
            ->whereHas('periodo', function ($q) use ($inicio, $fin, $excluirPeriodo) {
                $q->where('tipo', '!=', 'ESPECIAL')
                  ->where('fecha_inicio', '<=', $fin)
                  ->where('fecha_fin', '>=', $inicio);
                if ($excluirPeriodo) {
                    $q->where('id', '!=', $excluirPeriodo);
                }
            })
            ->get()
            ->map(fn ($r) => $this->nombre($r->personal))
            ->unique()->values();
    }

    /** Última fecha_fin de un periodo normal (no especial) por empleado. */
    private function ultimoPeriodoPorEmpleado(Collection $ids): array
    {
        return DB::table('recibos_nomina as r')
            ->join('periodos_nomina as p', 'p.id', '=', 'r.periodo_id')
            ->whereIn('r.personal_id', $ids->all())
            ->where('p.tipo', '!=', 'ESPECIAL')
            ->groupBy('r.personal_id')
            ->selectRaw('r.personal_id, MAX(p.fecha_fin) as fin')
            ->pluck('fin', 'personal_id')
            ->map(fn ($f) => Carbon::parse($f))
            ->all();
    }

    /**
     * Siguiente periodo de pago de un empleado según su tipo.
     * Sin historial: el periodo que corre hoy.
     */
    private function periodoSiguiente(string $tipo, ?Carbon $ultimoFin): array
    {
        $base = $ultimoFin ? $ultimoFin->copy()->addDay() : null;

        switch ($tipo) {
            case 'SEMANAL':
                $ini = $base ?: now()->startOfWeek(Carbon::MONDAY);
                return [$ini->copy(), $ini->copy()->addDays(6)];

            case 'MENSUAL':
                $ini = $base ?: now()->startOfMonth();
                return [$ini->copy(), $ini->day === 1 ? $ini->copy()->endOfMonth() : $ini->copy()->addMonth()->subDay()];

            default: // QUINCENAL
                $ini = $base ?: (now()->day <= 15 ? now()->startOfMonth() : now()->startOfMonth()->addDays(15));
                if ($ini->day === 1)  return [$ini->copy(), $ini->copy()->day(15)];
                if ($ini->day === 16) return [$ini->copy(), $ini->copy()->endOfMonth()];
                return [$ini->copy(), $ini->copy()->addDays(14)];
        }
    }

    private function tipoPago($persona): string
    {
        $t = strtoupper((string) (data_get($persona, 'tipo_pago') ?: 'QUINCENAL'));
        return in_array($t, ['SEMANAL', 'QUINCENAL', 'MENSUAL'], true) ? $t : 'QUINCENAL';
    }

    private function empleadosActivos()
    {
        $q = Personal::query();

        if (Schema::hasColumn('personals', 'estado')) {
            $q->where(fn ($w) => $w->whereNull('estado')->orWhereRaw('LOWER(estado) = ?', ['activo']));
        }

        return $q->get();
    }

    private function reciboParaVista(ReciboNomina $r): array
    {
        return [
            'id'                 => $r->id,
            'personal_id'        => $r->personal_id,
            'empleado'           => $this->nombre($r->personal) ?: 'Empleado #' . $r->personal_id,
            'puesto'             => (string) (data_get($r->personal, 'especialidad_principal') ?? ''),
            'tipo_pago'          => $this->tipoPago($r->personal),
            'ficha'              => route('personal.nomina.empleado', $r->personal_id),
            'salario_diario'     => $r->salario_diario,
            'dias_trabajados'    => $r->dias_trabajados,
            'sueldo_base'        => $r->sueldo_base,
            'horas_extra'        => $r->horas_extra,
            'bonos'              => $r->bonos,
            'isr'                => $r->isr,
            'imss'               => $r->imss,
            'otras_deducciones'  => $r->otras_deducciones,
            'total_percepciones' => $r->total_percepciones,
            'total_deducciones'  => $r->total_deducciones,
            'neto'               => $r->neto,
            'notas'              => (string) ($r->notas ?? ''),
            'estado'             => $r->estado ?: 'Pendiente',
            'fecha_pago'         => optional($r->fecha_pago)->format('Y-m-d'),
            'metodo_pago'        => $r->metodo_pago,
            'referencia'         => (string) ($r->referencia ?? ''),
        ];
    }

    private function nombre($persona): string
    {
        if (!$persona) {
            return '';
        }

        return trim((string) (data_get($persona, 'nombre_completo') ?: trim(implode(' ', array_filter([
            data_get($persona, 'nombre'),
            data_get($persona, 'apellido') ?? data_get($persona, 'apellidos') ?? data_get($persona, 'apellido_paterno'),
        ])))));
    }

    /** Nombre y datos fiscales/contacto de la clínica para el encabezado del recibo. */
    private function datosClinica(): array
    {
        $datos = ['nombre' => config('app.name', 'MediTrack'), 'rfc' => '', 'direccion' => '', 'telefono' => ''];

        try {
            if (Schema::hasTable('clinicas') && ($c = DB::table('clinicas')->first())) {
                $datos['nombre']    = (string) (data_get($c, 'nombre') ?: $datos['nombre']);
                $datos['rfc']       = (string) (data_get($c, 'rfc') ?: data_get($c, 'rut_empresa') ?: '');
                $datos['direccion'] = (string) (data_get($c, 'direccion') ?: '');
                $datos['telefono']  = (string) (data_get($c, 'telefono') ?: '');
                return $datos;
            }
            if (Schema::hasTable('configuraciones')) {
                $valor = DB::table('configuraciones')->where('clave', 'nombre_clinica')->value('valor');
                if ($valor) {
                    $datos['nombre'] = (string) $valor;
                }
            }
        } catch (\Throwable $e) {
            // Estructura distinta: se usa el nombre por defecto
        }

        return $datos;
    }

    private function autorizar(string $accion): void
    {
        $user = auth()->user();
        if ($user && method_exists($user, 'tienePermiso')) {
            abort_unless($user->tienePermiso('Personal', $accion), 403, 'No tienes permiso para esta acción.');
        }
    }
}