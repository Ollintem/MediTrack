<?php

namespace App\Http\Controllers;

use App\Services\Reportes\ReporteFinanciero;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes financieros: ingresos (ventas) y egresos (compras, nómina, caja) de un periodo,
 * tendencia de 12 meses, utilidad de farmacia y detalle exportable a CSV.
 * El permiso "Reportes,ver" lo revisa la ruta (middleware permiso).
 */
class ReporteController extends Controller
{
    /** Días máximos en la gráfica por día (más que eso no se alcanza a leer). */
    private const MAX_DIAS_GRAFICA = 92;

    /** Si el periodo es más corto que esto, la gráfica por día agrega días previos como contexto. */
    private const MIN_DIAS_GRAFICA = 14;

    public function index(Request $request)
    {
        [$desde, $hasta] = $this->periodo($request);
        $reporte = new ReporteFinanciero($desde, $hasta);
        $dias = (int) $desde->diffInDays($hasta) + 1;

        // Gráfica: por día (por defecto en periodos de hasta ~3 meses) o por mes (12 meses)
        $vista = in_array($request->query('vista'), ['dia', 'mes'], true)
            ? $request->query('vista')
            : ($dias <= self::MAX_DIAS_GRAFICA ? 'dia' : 'mes');

        if ($vista === 'dia') {
            $inicioGrafica = $dias < self::MIN_DIAS_GRAFICA
                ? $hasta->copy()->subDays(self::MIN_DIAS_GRAFICA - 1)   // contexto: las 2 semanas previas
                : ($dias > self::MAX_DIAS_GRAFICA ? $hasta->copy()->subDays(self::MAX_DIAS_GRAFICA - 1) : $desde->copy());
            $serie = ReporteFinanciero::diario($inicioGrafica, $hasta);
        } else {
            $serie = ReporteFinanciero::mensual($hasta, 12);
        }

        return view('reportes.index', [
            'desde'       => $desde,
            'hasta'       => $hasta,
            'dias'        => $dias,
            'unDia'       => $dias === 1,
            'vista'       => $vista,
            'serie'       => $serie,
            'recortada'   => $vista === 'dia' && $dias > self::MAX_DIAS_GRAFICA,
            'resumen'     => $reporte->resumen(),
            'productos'   => $reporte->productosVendidos(10),
            'movimientos' => $reporte->movimientos(),
            'atajos'      => $this->atajos(),
        ]);
    }

    /** Descarga el detalle de ingresos y egresos del periodo (se abre en Excel). */
    public function exportar(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->periodo($request);
        $filas = (new ReporteFinanciero($desde, $hasta))->movimientos();
        $archivo = 'reporte-' . $desde->format('Ymd') . '-' . $hasta->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel respeta los acentos
            fputcsv($salida, ['Fecha', 'Categoría', 'Concepto', 'Método de pago', 'Ingreso', 'Egreso']);
            foreach ($filas as $f) {
                fputcsv($salida, [
                    $f['fecha'], $f['categoria'], $this->celdaSegura($f['concepto']), $f['metodo'],
                    number_format($f['ingreso'], 2, '.', ''), number_format($f['egreso'], 2, '.', ''),
                ]);
            }
            fclose($salida);
        }, $archivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Periodo pedido (?desde=&hasta=); por defecto, el mes en curso. Máximo 3 años. */
    private function periodo(Request $request): array
    {
        $datos = $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
        ]);

        $hasta = isset($datos['hasta']) ? Carbon::parse($datos['hasta']) : Carbon::today();
        $desde = isset($datos['desde']) ? Carbon::parse($datos['desde']) : $hasta->copy()->startOfMonth();

        // Si las fechas vienen al revés, se acomodan en lugar de marcar error
        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        if ($desde->diffInDays($hasta) > 366 * 3) {
            $desde = $hasta->copy()->subYears(3);
        }

        return [$desde->startOfDay(), $hasta->startOfDay()];
    }

    /** Periodos rápidos para los botones de la pantalla. */
    private function atajos(): array
    {
        $hoy = Carbon::today();

        return [
            ['texto' => 'Hoy',           'desde' => $hoy->copy(),                                      'hasta' => $hoy->copy()],
            ['texto' => 'Ayer',          'desde' => $hoy->copy()->subDay(),                            'hasta' => $hoy->copy()->subDay()],
            ['texto' => 'Esta semana',   'desde' => $hoy->copy()->startOfWeek(Carbon::MONDAY),         'hasta' => $hoy],
            ['texto' => 'Este mes',      'desde' => $hoy->copy()->startOfMonth(),                      'hasta' => $hoy],
            ['texto' => 'Mes pasado',    'desde' => $hoy->copy()->subMonthNoOverflow()->startOfMonth(), 'hasta' => $hoy->copy()->subMonthNoOverflow()->endOfMonth()],
            ['texto' => 'Últimos 3 meses', 'desde' => $hoy->copy()->subMonthsNoOverflow(2)->startOfMonth(), 'hasta' => $hoy],
            ['texto' => 'Este año',      'desde' => $hoy->copy()->startOfYear(),                       'hasta' => $hoy],
        ];
    }

    /** Evita que Excel interprete un texto que empieza con =, +, - o @ como fórmula. */
    private function celdaSegura(string $texto): string
    {
        return preg_match('/^[=+\-@]/', $texto) ? "'" . $texto : $texto;
    }
}
