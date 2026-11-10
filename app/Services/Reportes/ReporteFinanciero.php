<?php

namespace App\Services\Reportes;

use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ingresos y egresos de la clínica en un periodo.
 *
 * INGRESOS
 *   - Ventas: cobros de tickets en Caja Central (por método de pago).
 *   - Otros ingresos de caja: ingresos sin ticket (no cuenta el fondo de apertura).
 * EGRESOS
 *   - Compras: entradas de inventario × costo unitario (no cuenta el inventario inicial
 *     que se captura al dar de alta un producto: ese stock ya se tenía).
 *   - Nómina: recibos pagados (neto entregado al empleado), por fecha de pago.
 *   - Otros egresos de caja: egresos sin ser el corte de cierre.
 * FARMACIA
 *   - Costo de lo vendido y utilidad bruta de los tickets cobrados en el periodo.
 */
class ReporteFinanciero
{
    public const CATEGORIAS = [
        'ventas'        => ['tipo' => 'ingreso', 'nombre' => 'Ventas de farmacia'],
        'otros_ingresos'=> ['tipo' => 'ingreso', 'nombre' => 'Otros ingresos de caja'],
        'compras'       => ['tipo' => 'egreso',  'nombre' => 'Compras de inventario'],
        'nomina'        => ['tipo' => 'egreso',  'nombre' => 'Nómina'],
        'otros_egresos' => ['tipo' => 'egreso',  'nombre' => 'Otros egresos de caja'],
    ];

    private string $desde;
    private string $hasta;

    public function __construct(CarbonInterface $desde, CarbonInterface $hasta)
    {
        $this->desde = $desde->copy()->startOfDay()->toDateTimeString();
        $this->hasta = $hasta->copy()->endOfDay()->toDateTimeString();
    }

    // =====================================================================
    //  RESUMEN DEL PERIODO
    // =====================================================================

    public function resumen(): array
    {
        $porMetodo = $this->ventas()
            ->groupBy('metodo_pago')
            ->selectRaw('metodo_pago, COUNT(*) as cobros, SUM(monto) as total')
            ->get()
            ->keyBy('metodo_pago');

        $categorias = [
            'ventas'         => (float) $porMetodo->sum('total'),
            'otros_ingresos' => (float) $this->cajaSinTicket('Ingreso')->sum('monto'),
            'compras'        => (float) $this->compras()->sum(DB::raw('cantidad * COALESCE(costo_unitario, 0)')),
            'nomina'         => (float) $this->nomina()->sum('neto'),
            'otros_egresos'  => (float) $this->cajaSinTicket('Egreso')->sum('monto'),
        ];

        $ingresos = $this->sumarTipo($categorias, 'ingreso');
        $egresos  = $this->sumarTipo($categorias, 'egreso');

        $farmacia = $this->farmacia();

        return [
            'ingresos'   => $ingresos,
            'egresos'    => $egresos,
            'balance'    => round($ingresos - $egresos, 2),
            'categorias' => collect(self::CATEGORIAS)->map(fn ($c, $clave) => $c + [
                'clave' => $clave,
                'total' => round($categorias[$clave], 2),
            ])->values()->all(),
            'metodos'    => collect(['Efectivo', 'Tarjeta', 'Transferencia'])->map(fn ($m) => [
                'metodo' => $m,
                'cobros' => (int) ($porMetodo[$m]->cobros ?? 0),
                'total'  => round((float) ($porMetodo[$m]->total ?? 0), 2),
            ])->all(),
            'farmacia'   => $farmacia,
            'conteos'    => [
                'cobros'   => (int) $porMetodo->sum('cobros'),
                'compras'  => $this->compras()->count(),
                'recibos'  => $this->nomina()->count(),
            ],
        ];
    }

    /** Ventas, costo de lo vendido y utilidad bruta (tickets cobrados en el periodo). */
    public function farmacia(): array
    {
        $productos = $this->productosVendidos();
        $ventas = (float) $productos->sum('ventas');
        $costo  = (float) $productos->sum('costo');

        return [
            'ventas'   => round($ventas, 2),
            'costo'    => round($costo, 2),
            'utilidad' => round($ventas - $costo, 2),
            'margen'   => $ventas > 0 ? round(($ventas - $costo) * 100 / $ventas, 1) : null,
        ];
    }

    /** Productos vendidos con piezas, ventas, costo y utilidad, de mayor a menor venta. */
    public function productosVendidos(?int $limite = null): Collection
    {
        // Costo real de la entrega si ya se entregó; si no, el precio de compra actual del producto
        $costoEntrega = DB::table('movimientos_inventario')
            ->where('tipo', 'Salida')->whereNotNull('ticket_id')
            ->groupBy('ticket_id', 'producto_id')
            ->selectRaw('ticket_id, producto_id, MAX(costo_unitario) as costo_unitario');

        return DB::table('ticket_detalles as td')
            ->joinSub($this->ventas()->select('ticket_id'), 'cobros', 'cobros.ticket_id', '=', 'td.ticket_id')
            ->join('productos_inventario as p', 'p.id', '=', 'td.producto_id')
            ->leftJoinSub($costoEntrega, 'mi', fn ($j) => $j->on('mi.ticket_id', '=', 'td.ticket_id')->on('mi.producto_id', '=', 'td.producto_id'))
            ->groupBy('p.id', 'p.nombre', 'p.codigo')
            ->selectRaw('p.id, p.nombre, p.codigo, SUM(td.cantidad) as piezas, SUM(td.subtotal) as ventas,
                         SUM(td.cantidad * COALESCE(mi.costo_unitario, p.precio_compra, 0)) as costo')
            ->orderByDesc('ventas')
            ->when($limite, fn ($q) => $q->limit($limite))
            ->get()
            ->map(fn ($r) => [
                'nombre'   => $r->nombre,
                'codigo'   => $r->codigo,
                'piezas'   => (int) $r->piezas,
                'ventas'   => round((float) $r->ventas, 2),
                'costo'    => round((float) $r->costo, 2),
                'utilidad' => round((float) $r->ventas - (float) $r->costo, 2),
            ]);
    }

    // =====================================================================
    //  TENDENCIA POR MES Y POR DÍA
    // =====================================================================

    private const MESES_CORTOS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    private const DIAS_CORTOS  = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    /** Ingresos y egresos por mes, de los $meses meses que terminan en el mes de $hasta. */
    public static function mensual(CarbonInterface $hasta, int $meses = 12): array
    {
        $inicio = $hasta->copy()->startOfMonth()->subMonthsNoOverflow($meses - 1);

        return self::serie($inicio, $hasta->copy()->endOfMonth(), 'mes');
    }

    /** Ingresos y egresos de cada día entre $desde y $hasta (incluidos). */
    public static function diario(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        return self::serie($desde->copy()->startOfDay(), $hasta->copy()->startOfDay(), 'dia');
    }

    /**
     * Serie de ingresos/egresos agrupada por 'mes' o por 'dia'. Cada fila trae su rango
     * (desde/hasta) para poder abrir el reporte de ese mes o de ese día.
     */
    private static function serie(CarbonInterface $inicio, CarbonInterface $fin, string $agrupar): array
    {
        $rep = new self($inicio, $fin);
        $formato = $agrupar === 'dia' ? '%Y-%m-%d' : '%Y-%m';

        $sumar = fn (Builder $q, string $columna, string $valor) => $q
            ->selectRaw("DATE_FORMAT({$columna}, '{$formato}') as periodo, SUM({$valor}) as total")
            ->groupBy('periodo')->pluck('total', 'periodo');

        $ingresos = [
            $sumar($rep->ventas(), 'movimientos_caja.created_at', 'monto'),
            $sumar($rep->cajaSinTicket('Ingreso'), 'created_at', 'monto'),
        ];
        $egresos = [
            $sumar($rep->compras(), 'movimientos_inventario.created_at', 'cantidad * COALESCE(costo_unitario, 0)'),
            $sumar($rep->nomina(), 'recibos_nomina.fecha_pago', 'neto'),
            $sumar($rep->cajaSinTicket('Egreso'), 'created_at', 'monto'),
        ];
        $total = fn (array $series, string $clave) => round(array_sum(array_map(fn ($s) => (float) ($s[$clave] ?? 0), $series)), 2);

        $filas = [];
        for ($p = $inicio->copy(); $p->lte($fin); $agrupar === 'dia' ? $p->addDay() : $p->addMonthNoOverflow()) {
            $clave = $p->format($agrupar === 'dia' ? 'Y-m-d' : 'Y-m');
            $in = $total($ingresos, $clave);
            $eg = $total($egresos, $clave);

            $filas[] = [
                'clave'    => $clave,
                'etiqueta' => $agrupar === 'dia'
                    ? (string) $p->day
                    : self::MESES_CORTOS[$p->month - 1] . ' ' . $p->format('y'),
                'titulo'   => $agrupar === 'dia'
                    ? self::DIAS_CORTOS[$p->dayOfWeek] . ' ' . $p->day . ' ' . self::MESES_CORTOS[$p->month - 1] . ' ' . $p->year
                    : $p->locale('es')->translatedFormat('F Y'),
                'desde'    => $agrupar === 'dia' ? $clave : $p->copy()->startOfMonth()->toDateString(),
                'hasta'    => $agrupar === 'dia' ? $clave : $p->copy()->endOfMonth()->toDateString(),
                'ingresos' => $in,
                'egresos'  => $eg,
                'balance'  => round($in - $eg, 2),
            ];
        }

        return $filas;
    }

    // =====================================================================
    //  DETALLE (tabla y exportación CSV)
    // =====================================================================

    /** Cada ingreso y egreso del periodo, del más reciente al más antiguo. */
    public function movimientos(): Collection
    {
        $ventas = $this->ventas()
            ->leftJoin('tickets_venta as t', 't.id', '=', 'movimientos_caja.ticket_id')
            ->get(['movimientos_caja.created_at as fecha', 't.codigo_ticket', 'movimientos_caja.metodo_pago', 'movimientos_caja.monto'])
            ->map(fn ($r) => $this->fila($r->fecha, 'ventas', 'Cobro de ticket ' . $r->codigo_ticket, $r->metodo_pago, $r->monto));

        $caja = $this->cajaSinTicket()
            ->get(['created_at as fecha', 'tipo', 'concepto', 'metodo_pago', 'monto'])
            ->map(fn ($r) => $this->fila($r->fecha, $r->tipo === 'Ingreso' ? 'otros_ingresos' : 'otros_egresos', $r->concepto, $r->metodo_pago, $r->monto));

        $compras = $this->compras()
            ->join('productos_inventario as p', 'p.id', '=', 'movimientos_inventario.producto_id')
            ->get(['movimientos_inventario.created_at as fecha', 'p.nombre', 'movimientos_inventario.cantidad',
                   'movimientos_inventario.costo_unitario', 'movimientos_inventario.referencia', 'movimientos_inventario.motivo'])
            ->map(fn ($r) => $this->fila(
                $r->fecha, 'compras',
                "{$r->cantidad} × {$r->nombre}" . ($r->referencia ? " · {$r->referencia}" : ($r->motivo ? " · {$r->motivo}" : '')),
                null, $r->cantidad * (float) $r->costo_unitario
            ));

        $nomina = $this->nomina()
            ->join('personals as pe', 'pe.id', '=', 'recibos_nomina.personal_id')
            ->get(['recibos_nomina.fecha_pago as fecha', 'pe.nombre_completo', 'recibos_nomina.metodo_pago', 'recibos_nomina.neto'])
            ->map(fn ($r) => $this->fila($r->fecha, 'nomina', 'Pago de nómina a ' . $r->nombre_completo, $r->metodo_pago, $r->neto));

        return $ventas->concat($caja)->concat($compras)->concat($nomina)
            ->sortByDesc('fecha')->values();
    }

    // =====================================================================
    //  CONSULTAS BASE (una por fuente de dinero)
    // =====================================================================

    /** Cobros de tickets en Caja Central. */
    public function ventas(): Builder
    {
        return DB::table('movimientos_caja')
            ->where('movimientos_caja.tipo', 'Ingreso')
            ->whereNotNull('movimientos_caja.ticket_id')
            ->whereBetween('movimientos_caja.created_at', [$this->desde, $this->hasta]);
    }

    /** Movimientos de caja que no son cobros de tickets, sin el fondo de apertura ni el corte. */
    public function cajaSinTicket(?string $tipo = null): Builder
    {
        return DB::table('movimientos_caja')
            ->whereNull('ticket_id')
            ->where('concepto', '!=', MovimientoCaja::CONCEPTO_APERTURA)
            ->where('concepto', 'not like', MovimientoCaja::PREFIJO_CIERRE . '%')
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->whereBetween('created_at', [$this->desde, $this->hasta]);
    }

    /** Entradas de inventario compradas en el periodo (sin el inventario inicial). */
    public function compras(): Builder
    {
        return DB::table('movimientos_inventario')
            ->where('movimientos_inventario.tipo', 'Entrada')
            ->where(fn ($q) => $q->whereNull('movimientos_inventario.motivo')->orWhereRaw('UPPER(movimientos_inventario.motivo) != ?', [MovimientoInventario::MOTIVO_INICIAL]))
            ->whereBetween('movimientos_inventario.created_at', [$this->desde, $this->hasta]);
    }

    /** Recibos de nómina pagados en el periodo. */
    public function nomina(): Builder
    {
        return DB::table('recibos_nomina')
            ->where('recibos_nomina.estado', 'Pagado')
            ->whereBetween('recibos_nomina.fecha_pago', [substr($this->desde, 0, 10), substr($this->hasta, 0, 10)]);
    }

    // =====================================================================
    //  AYUDANTES
    // =====================================================================

    private function sumarTipo(array $categorias, string $tipo): float
    {
        return round(collect(self::CATEGORIAS)
            ->filter(fn ($c) => $c['tipo'] === $tipo)
            ->keys()
            ->sum(fn ($clave) => $categorias[$clave]), 2);
    }

    private function fila($fecha, string $categoria, string $concepto, ?string $metodo, $monto): array
    {
        $tipo = self::CATEGORIAS[$categoria]['tipo'];
        $monto = round((float) $monto, 2);

        return [
            'fecha'     => Carbon::parse($fecha)->format('Y-m-d H:i'),
            'categoria' => self::CATEGORIAS[$categoria]['nombre'],
            'tipo'      => $tipo,
            'concepto'  => $concepto,
            'metodo'    => $metodo ? ucfirst(mb_strtolower($metodo)) : '',
            'ingreso'   => $tipo === 'ingreso' ? $monto : 0.0,
            'egreso'    => $tipo === 'egreso' ? $monto : 0.0,
        ];
    }
}
