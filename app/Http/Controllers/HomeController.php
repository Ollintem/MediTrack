<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consultorio;
use App\Models\Paciente;
use App\Models\ProductoInventario;
use App\Models\Receta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard de MediTrack: el pulso clínico del día (agenda, médicos, consultorios e inventario)
 * y la tendencia de los últimos seis meses. No muestra dinero: eso vive en Caja y Facturación.
 */
class HomeController extends Controller
{
    /** Estados de cita que cuentan como "por atender". */
    private const ESTADOS_POR_ATENDER = ['Pendiente', 'Confirmada'];

    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    private const DIAS  = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    // La protección con login ya la hace el grupo middleware('auth') de routes/web.php

    /** Vista del dashboard con los datos ya calculados. */
    public function index()
    {
        return view('dash.index', ['datos' => $this->armarDatos()]);
    }

    /** Los mismos datos en JSON, para que el dashboard se actualice solo. */
    public function datos()
    {
        return response()->json($this->armarDatos());
    }

    // =====================================================================
    //  ARMADO DE DATOS
    // =====================================================================

    private function armarDatos(): array
    {
        $hoy  = Carbon::today();
        $user = auth()->user();

        $puede = [
            'pacientes'    => $this->puede($user, 'Pacientes'),
            'citas'        => $this->puede($user, 'Citas'),
            'consultas'    => $this->puede($user, 'Consultas'),
            'consultorios' => $this->puede($user, 'Consultorios'),
            'recetas'      => $this->puede($user, 'Recetas'),
            'inventario'   => $this->puede($user, 'Inventario'),
        ];

        $citasHoy = Cita::with(['paciente.alergias', 'personal', 'consultorio'])
            ->whereDate('fecha', $hoy)
            ->orderBy('hora')
            ->get();

        return [
            'generado'     => now()->format('H:i'),
            'hoy'          => $hoy->toDateString(),
            'miPersonalId' => optional(optional($user)->personal)->id,
            'puede'        => $puede,
            'urls'         => [
                'pacientes'    => $this->ruta('pacientes.index'),
                'citas'        => $this->ruta('citas.index'),
                'consultas'    => $this->ruta('consultas.index'),
                'consultorios' => $this->ruta('consultorios.index'),
                'recetas'      => $this->ruta('recetas.index'),
                'inventario'   => $this->ruta('inventario.index'),
            ],
            'kpis'         => [
                'pacientesHoy' => $this->kpiPacientesHoy($hoy, $citasHoy),
                'pendientes'   => $this->kpiPendientes($hoy, $citasHoy),
                'consultasMes' => $this->kpiConsultasMes($hoy),
                'ocupacion'    => $this->kpiOcupacion(),
            ],
            'agenda'       => $citasHoy->map(fn ($c) => $this->citaParaAgenda($c))->values()->all(),
            'resumenHoy'   => [
                'total'       => $citasHoy->count(),
                'finalizadas' => $citasHoy->where('estado', 'Finalizada')->count(),
                'enCurso'     => $citasHoy->where('estado', 'En curso')->count(),
                'canceladas'  => $citasHoy->where('estado', 'Cancelada')->count(),
                'porAtender'  => $citasHoy->whereIn('estado', self::ESTADOS_POR_ATENDER)->count(),
            ],
            'actividad'    => $this->actividadHoy($hoy),
            'proximas'     => $this->proximas($hoy),
            'consultorios' => $this->consultoriosAhora($citasHoy),
            'grafica'      => $this->grafica($hoy),
            'diario'       => $this->serieDiaria($hoy),
            'medicosHoy'   => $this->medicosHoy($citasHoy),
            'tiposMes'     => $this->tiposMes($hoy),
            'stock'        => $this->alertasStock(),
        ];
    }

    // ----- KPI: pacientes de hoy (distintos, sin cancelar) -----
    private function kpiPacientesHoy(Carbon $hoy, Collection $citasHoy): array
    {
        $valor = $citasHoy->where('estado', '!=', 'Cancelada')->pluck('paciente_id')->filter()->unique()->count();

        // Serie de los últimos 7 días para la mini gráfica
        $desde = $hoy->copy()->subDays(6);
        $porDia = Cita::whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hoy)
            ->where('estado', '!=', 'Cancelada')
            ->get(['fecha', 'paciente_id'])
            ->groupBy(fn ($c) => Carbon::parse($c->fecha)->toDateString())
            ->map(fn ($g) => $g->pluck('paciente_id')->filter()->unique()->count());

        $serie = [];
        for ($d = $desde->copy(); $d->lte($hoy); $d->addDay()) {
            $serie[] = [
                'dia'   => self::DIAS[$d->dayOfWeek],
                'valor' => (int) ($porDia[$d->toDateString()] ?? 0),
            ];
        }

        // Comparación contra el mismo día de la semana pasada
        $anterior = Cita::whereDate('fecha', $hoy->copy()->subWeek())
            ->where('estado', '!=', 'Cancelada')
            ->distinct()
            ->count('paciente_id');

        return [
            'valor'    => $valor,
            'anterior' => $anterior,
            'delta'    => $this->variacion($valor, $anterior),
            'serie'    => $serie,
        ];
    }

    // ----- KPI: citas por atender (hoy en adelante) -----
    private function kpiPendientes(Carbon $hoy, Collection $citasHoy): array
    {
        $base = Cita::whereDate('fecha', '>=', $hoy)->whereIn('estado', self::ESTADOS_POR_ATENDER);

        return [
            'valor'       => (clone $base)->count(),
            'hoy'         => $citasHoy->whereIn('estado', self::ESTADOS_POR_ATENDER)->count(),
            'confirmadas' => (clone $base)->where('estado', 'Confirmada')->count(),
        ];
    }

    // ----- KPI: consultas atendidas en el mes (el dinero vive en Caja y Facturación) -----
    private function kpiConsultasMes(Carbon $hoy): array
    {
        $inicioMes = $hoy->copy()->startOfMonth();
        $inicioAnt = $inicioMes->copy()->subMonth();
        $finAnt    = $inicioMes->copy()->subDay();

        $valor = Cita::where('estado', 'Finalizada')
            ->whereDate('fecha', '>=', $inicioMes)->whereDate('fecha', '<=', $hoy->copy()->endOfMonth())->count();
        $anterior = Cita::where('estado', 'Finalizada')
            ->whereDate('fecha', '>=', $inicioAnt)->whereDate('fecha', '<=', $finAnt)->count();

        return ['valor' => $valor, 'anterior' => $anterior, 'delta' => $this->variacion($valor, $anterior)];
    }

    // ----- KPI: ocupación de consultorios (en este momento) -----
    private function kpiOcupacion(): array
    {
        $estados = Consultorio::query()->pluck('estado')->map(fn ($e) => mb_strtolower(trim((string) $e)));

        $total         = $estados->count();
        $ocupados      = $estados->filter(fn ($e) => $e === 'ocupado')->count();
        $mantenimiento = $estados->filter(fn ($e) => $e === 'mantenimiento')->count();
        $operativos    = max($total - $mantenimiento, 0);

        return [
            'total'         => $total,
            'ocupados'      => $ocupados,
            'disponibles'   => $estados->filter(fn ($e) => $e === 'disponible')->count(),
            'mantenimiento' => $mantenimiento,
            'porcentaje'    => $operativos > 0 ? (int) round($ocupados * 100 / $operativos) : 0,
        ];
    }

    // ----- Lo que pasó hoy en otros módulos -----
    private function actividadHoy(Carbon $hoy): array
    {
        return [
            'recetas'         => Schema::hasTable('recetas') ? Receta::whereDate('fecha_emision', $hoy)->count() : 0,
            'pacientesNuevos' => Paciente::whereDate('created_at', $hoy)->count(),
        ];
    }

    // ----- Siguientes citas por atender después de hoy -----
    private function proximas(Carbon $hoy): array
    {
        return Cita::with(['paciente.alergias', 'personal', 'consultorio'])
            ->whereDate('fecha', '>', $hoy)
            ->whereIn('estado', self::ESTADOS_POR_ATENDER)
            ->orderBy('fecha')->orderBy('hora')
            ->limit(8)->get()
            ->map(fn ($c) => $this->citaParaAgenda($c) + ['fecha' => Carbon::parse($c->fecha)->toDateString()])
            ->values()->all();
    }

    // ----- Consultorios con quién está dentro ahora mismo -----
    private function consultoriosAhora(Collection $citasHoy): array
    {
        $enCurso = $citasHoy->where('estado', 'En curso')->keyBy('consultorio_id');

        $orden = ['ocupado' => 0, 'disponible' => 1];

        return Consultorio::query()->orderBy('piso')->orderBy('nombre')->get()
            ->map(function ($c) use ($enCurso) {
                $cita = $enCurso->get($c->id);

                return [
                    'id'       => $c->id,
                    'nombre'   => (string) $c->nombre,
                    'piso'     => (string) ($c->piso ?? ''),
                    'estado'   => mb_strtolower(trim((string) ($c->estado ?? 'disponible'))),
                    'medico'   => $cita ? ($this->nombre($cita->personal) ?: null) : null,
                    'paciente' => $cita ? ($this->nombre($cita->paciente) ?: null) : null,
                    'desde'    => $cita && $cita->hora ? Carbon::parse($cita->hora)->format('H:i') : null,
                ];
            })
            ->sortBy(fn ($c) => $orden[$c['estado']] ?? 2)
            ->values()->all();
    }

    // ----- Gráfica: últimos 6 meses -----
    private function grafica(Carbon $hoy): array
    {
        $inicio = $hoy->copy()->startOfMonth()->subMonths(5);
        $fin    = $hoy->copy()->endOfMonth();

        $citas = Cita::whereDate('fecha', '>=', $inicio)
            ->whereDate('fecha', '<=', $fin)
            ->get(['fecha', 'estado'])
            ->groupBy(fn ($c) => Carbon::parse($c->fecha)->format('Y-m'));

        $meses = [];

        for ($m = $inicio->copy(); $m->lte($fin); $m->addMonth()) {
            $grupo = $citas->get($m->format('Y-m'), collect());

            $finalizadas = $grupo->where('estado', 'Finalizada')->count();
            $canceladas  = $grupo->where('estado', 'Cancelada')->count();

            $meses[] = [
                'clave'       => $m->format('Y-m'),
                'etiqueta'    => self::MESES[$m->month - 1],
                'anio'        => $m->year,
                'finalizadas' => $finalizadas,
                'canceladas'  => $canceladas,
                'activas'     => $grupo->count() - $finalizadas - $canceladas,
                'total'       => $grupo->count(),
            ];
        }

        return ['meses' => $meses];
    }

    // ----- Consultas atendidas por día del mes, para la mini gráfica -----
    private function serieDiaria(Carbon $hoy): array
    {
        $inicio = $hoy->copy()->startOfMonth();
        $porDia = Cita::where('estado', 'Finalizada')
            ->whereDate('fecha', '>=', $inicio)->whereDate('fecha', '<=', $hoy)
            ->get(['fecha'])
            ->groupBy(fn ($c) => Carbon::parse($c->fecha)->toDateString())
            ->map(fn ($g) => $g->count());

        $serie = [];
        for ($d = $inicio->copy(); $d->lte($hoy); $d->addDay()) {
            $serie[] = ['dia' => $d->day, 'valor' => (int) ($porDia[$d->toDateString()] ?? 0)];
        }

        return $serie;
    }

    // ----- Citas de hoy por médico -----
    private function medicosHoy(Collection $citasHoy): array
    {
        return $citasHoy->where('estado', '!=', 'Cancelada')
            ->groupBy('personal_id')
            ->map(fn ($g) => [
                'nombre'      => $this->nombre($g->first()->personal) ?: 'Sin asignar',
                'atendidas'   => $g->where('estado', 'Finalizada')->count(),
                'enCurso'     => $g->where('estado', 'En curso')->count(),
                'porAtender'  => $g->whereIn('estado', self::ESTADOS_POR_ATENDER)->count(),
                'total'       => $g->count(),
            ])
            ->sortByDesc('total')->take(6)->values()->all();
    }

    // ----- Tipo de consulta de las citas del mes -----
    private function tiposMes(Carbon $hoy): array
    {
        $conteo = Cita::whereDate('fecha', '>=', $hoy->copy()->startOfMonth())
            ->whereDate('fecha', '<=', $hoy->copy()->endOfMonth())
            ->where('estado', '!=', 'Cancelada')
            ->get(['tipo_consulta'])
            ->countBy('tipo_consulta');

        return [
            'Seguimiento' => (int) ($conteo['Seguimiento'] ?? 0),
            'Primera Vez' => (int) ($conteo['Primera Vez'] ?? 0),
            'Urgencia'    => (int) ($conteo['Urgencia'] ?? 0),
        ];
    }

    // ----- Stock en o bajo el mínimo -----
    private function alertasStock(): array
    {
        if (!Schema::hasTable('productos_inventario')) {
            return ['disponible' => false, 'total' => 0, 'agotados' => 0, 'items' => []];
        }

        // El disponible descuenta lo apartado en tickets vigentes (atributo calculado del modelo).
        $bajos = ProductoInventario::query()->where('stock_minimo', '>', 0)->get()
            ->filter(fn ($p) => (int) $p->stock_disponible <= (int) $p->stock_minimo)
            ->sortBy([['stock_disponible', 'asc'], ['nombre', 'asc']])
            ->values();

        return [
            'disponible' => true,
            'total'      => $bajos->count(),
            'agotados'   => $bajos->filter(fn ($p) => (int) $p->stock_disponible <= 0)->count(),
            'items'      => $bajos->take(5)->map(fn ($p) => [
                'nombre'     => (string) ($p->nombre ?? 'Producto'),
                'disponible' => (int) $p->stock_disponible,
                'minimo'     => (int) $p->stock_minimo,
            ])->values()->all(),
        ];
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    private function citaParaAgenda($c): array
    {
        $hora = $c->hora ? Carbon::parse($c->hora) : null;
        $duracion = (int) ($c->duracion_min ?? 30);
        $paciente = $this->nombre($c->paciente) ?: 'Paciente';

        return [
            'id'          => $c->id,
            'hora'        => $hora ? $hora->format('H:i') : '--:--',
            'fin'         => $hora ? $hora->copy()->addMinutes($duracion)->format('H:i') : '--:--',
            'duracion'    => $duracion,
            'paciente'    => $paciente,
            'paciente_id' => $c->paciente_id,
            'alergias'    => $c->paciente ? $c->paciente->alergias->pluck('descripcion')->filter()->values()->all() : [],
            'personal_id' => $c->personal_id,
            'doctor'      => $this->nombre($c->personal) ?: 'Sin asignar',
            'consultorio' => (string) (data_get($c, 'consultorio.nombre') ?? ''),
            'tipo'        => (string) ($c->tipo_consulta ?? ''),
            'motivo'      => (string) ($c->motivo ?? ''),
            'estado'      => (string) ($c->estado ?? 'Pendiente'),
        ];
    }

    private function nombre($modelo): string
    {
        if (!$modelo) {
            return '';
        }

        return trim((string) (data_get($modelo, 'nombre_completo') ?: trim(
            data_get($modelo, 'nombre', '') . ' ' . (data_get($modelo, 'apellidos') ?? data_get($modelo, 'apellido_paterno') ?? '')
        )));
    }

    private function puede($user, string $modulo): bool
    {
        if (!$user) {
            return false;
        }

        try {
            return (bool) $user->tienePermiso($modulo);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ruta(string $nombre): ?string
    {
        return Route::has($nombre) ? route($nombre) : null;
    }

    /** Porcentaje de cambio; null cuando no hay contra qué comparar. */
    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior <= 0) {
            return null;
        }

        return round(($actual - $anterior) * 100 / $anterior, 1);
    }
}