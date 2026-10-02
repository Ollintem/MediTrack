<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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
        $hoy = Carbon::today();

        $citasHoy = Cita::with(['paciente', 'personal', 'consultorio'])
            ->whereDate('fecha', $hoy)
            ->orderBy('hora')
            ->get();

        return [
            'generado'   => now()->format('H:i'),
            'kpis'       => [
                'pacientesHoy' => $this->kpiPacientesHoy($hoy, $citasHoy),
                'pendientes'   => $this->kpiPendientes($hoy, $citasHoy),
                'ingresos'     => $this->kpiIngresos($hoy),
                'ocupacion'    => $this->kpiOcupacion(),
            ],
            'grafica'    => $this->grafica($hoy),
            'agenda'     => $citasHoy->map(fn ($c) => $this->citaParaAgenda($c))->values()->all(),
            'resumenHoy' => [
                'total'       => $citasHoy->count(),
                'finalizadas' => $citasHoy->where('estado', 'Finalizada')->count(),
                'enCurso'     => $citasHoy->where('estado', 'En curso')->count(),
                'canceladas'  => $citasHoy->where('estado', 'Cancelada')->count(),
            ],
            'stock'      => $this->alertasStock(),
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

    // ----- KPI: citas pendientes (hoy en adelante) -----
    private function kpiPendientes(Carbon $hoy, Collection $citasHoy): array
    {
        $base = Cita::whereDate('fecha', '>=', $hoy)->whereIn('estado', self::ESTADOS_POR_ATENDER);

        return [
            'valor'       => (clone $base)->count(),
            'hoy'         => $citasHoy->whereIn('estado', self::ESTADOS_POR_ATENDER)->count(),
            'confirmadas' => (clone $base)->where('estado', 'Confirmada')->count(),
        ];
    }

    // ----- KPI: ingresos del mes (si existe Facturación) o consultas atendidas -----
    private function kpiIngresos(Carbon $hoy): array
    {
        $inicioMes   = $hoy->copy()->startOfMonth();
        $inicioAnt   = $inicioMes->copy()->subMonth();
        $finAnt      = $inicioMes->copy()->subDay();
        $fuente      = $this->fuenteIngresos();

        if ($fuente) {
            $valor    = $this->sumaIngresos($fuente, $inicioMes, $hoy->copy()->endOfMonth());
            $anterior = $this->sumaIngresos($fuente, $inicioAnt, $finAnt);

            return ['modo' => 'ingresos', 'valor' => $valor, 'anterior' => $anterior, 'delta' => $this->variacion($valor, $anterior)];
        }

        // Sin módulo de facturación: se muestran las consultas atendidas del mes
        $valor = Cita::where('estado', 'Finalizada')
            ->whereDate('fecha', '>=', $inicioMes)->whereDate('fecha', '<=', $hoy->copy()->endOfMonth())->count();
        $anterior = Cita::where('estado', 'Finalizada')
            ->whereDate('fecha', '>=', $inicioAnt)->whereDate('fecha', '<=', $finAnt)->count();

        return ['modo' => 'consultas', 'valor' => $valor, 'anterior' => $anterior, 'delta' => $this->variacion($valor, $anterior)];
    }

    // ----- KPI: ocupación de consultorios (en este momento) -----
    private function kpiOcupacion(): array
    {
        $vacio = ['total' => 0, 'ocupados' => 0, 'disponibles' => 0, 'mantenimiento' => 0, 'porcentaje' => 0];

        $clase = 'App\\Models\\Consultorio';
        if (!class_exists($clase)) {
            return $vacio;
        }

        $estados = $clase::query()->pluck('estado')->map(fn ($e) => mb_strtolower(trim((string) $e)));

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

    // ----- Gráfica: últimos 6 meses -----
    private function grafica(Carbon $hoy): array
    {
        $inicio = $hoy->copy()->startOfMonth()->subMonths(5);
        $fin    = $hoy->copy()->endOfMonth();

        $citas = Cita::whereDate('fecha', '>=', $inicio)
            ->whereDate('fecha', '<=', $fin)
            ->get(['fecha', 'estado'])
            ->groupBy(fn ($c) => Carbon::parse($c->fecha)->format('Y-m'));

        $fuente = $this->fuenteIngresos();
        $meses  = [];

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
                'ingresos'    => $fuente ? $this->sumaIngresos($fuente, $m->copy()->startOfMonth(), $m->copy()->endOfMonth()) : 0,
            ];
        }

        return ['meses' => $meses, 'hayIngresos' => (bool) $fuente];
    }

    // ----- Stock bajo el mínimo -----
    private function alertasStock(): array
    {
        foreach (['Producto', 'Medicamento', 'Inventario'] as $nombre) {
            $clase = "App\\Models\\{$nombre}";
            if (!class_exists($clase)) {
                continue;
            }

            $tabla = (new $clase)->getTable();
            if (!Schema::hasTable($tabla) || !Schema::hasColumn($tabla, 'stock_minimo')) {
                continue;
            }

            $colDisponible = collect(['stock_disponible', 'stock_actual', 'stock'])
                ->first(fn ($c) => Schema::hasColumn($tabla, $c));
            if (!$colDisponible) {
                continue;
            }

            $base = $clase::query()
                ->where('stock_minimo', '>', 0)
                ->whereColumn($colDisponible, '<=', 'stock_minimo');

            $items = (clone $base)->orderBy($colDisponible)->limit(5)->get()
                ->map(fn ($p) => [
                    'nombre'     => (string) ($p->nombre ?? 'Producto'),
                    'disponible' => (int) $p->{$colDisponible},
                    'minimo'     => (int) $p->stock_minimo,
                ])->values()->all();

            return ['disponible' => true, 'total' => (clone $base)->count(), 'items' => $items];
        }

        return ['disponible' => false, 'total' => 0, 'items' => []];
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    private function citaParaAgenda($c): array
    {
        $hora = $c->hora ? Carbon::parse($c->hora) : null;
        $duracion = (int) ($c->duracion_min ?? 30);

        return [
            'id'          => $c->id,
            'hora'        => $hora ? $hora->format('H:i') : '--:--',
            'fin'         => $hora ? $hora->copy()->addMinutes($duracion)->format('H:i') : '--:--',
            'paciente'    => $this->nombre($c->paciente) ?: 'Paciente',
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

    /** Porcentaje de cambio; null cuando no hay contra qué comparar. */
    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior <= 0) {
            return null;
        }

        return round(($actual - $anterior) * 100 / $anterior, 1);
    }

    /**
     * Detecta si ya existe el módulo de facturación (modelo Factura con tabla,
     * columna de monto y de fecha). Mientras no exista, el dashboard muestra consultas.
     */
    private function fuenteIngresos(): ?array
    {
        static $cache = false;
        if ($cache !== false) {
            return $cache;
        }

        $clase = 'App\\Models\\Factura';
        if (!class_exists($clase)) {
            return $cache = null;
        }

        $tabla = (new $clase)->getTable();
        if (!Schema::hasTable($tabla)) {
            return $cache = null;
        }

        $monto = collect(['total', 'monto', 'importe'])->first(fn ($c) => Schema::hasColumn($tabla, $c));
        $fecha = collect(['fecha', 'fecha_emision', 'created_at'])->first(fn ($c) => Schema::hasColumn($tabla, $c));

        if (!$monto || !$fecha) {
            return $cache = null;
        }

        return $cache = [
            'clase'  => $clase,
            'monto'  => $monto,
            'fecha'  => $fecha,
            'estado' => Schema::hasColumn($tabla, 'estado') ? 'estado' : null,
        ];
    }

    private function sumaIngresos(array $fuente, Carbon $desde, Carbon $hasta): float
    {
        $q = $fuente['clase']::query()
            ->where($fuente['fecha'], '>=', $desde->copy()->startOfDay())
            ->where($fuente['fecha'], '<=', $hasta->copy()->endOfDay());

        if ($fuente['estado']) {
            $q->whereNotIn($fuente['estado'], ['Cancelada', 'Anulada', 'cancelada', 'anulada']);
        }

        return (float) $q->sum($fuente['monto']);
    }
}