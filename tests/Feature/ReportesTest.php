<?php

namespace Tests\Feature;

use App\Models\Modulo;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use App\Models\PeriodoNomina;
use App\Models\ReciboNomina;
use App\Models\TicketDetalle;
use App\Models\TicketVenta;
use App\Services\Reportes\ReporteFinanciero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    /**
     * Escenario del mes:
     *  - Venta cobrada: 3 piezas × $20 = $60 en tarjeta (costo $10 c/u → utilidad $30)
     *  - Fondo de apertura de $500 (NO es ingreso) y corte de cierre (NO es egreso)
     *  - Inventario inicial de 50 piezas (NO es compra)
     *  - Surtido de 10 piezas a $10 = $100 de compra
     *  - Recibo de nómina pagado: neto $1,500
     */
    private function escenario(): void
    {
        $this->actingAs($this->admin());
        $p = $this->producto(0);

        MovimientoInventario::registrar($p, 'Entrada', 50, ['motivo' => MovimientoInventario::MOTIVO_INICIAL]);
        MovimientoInventario::registrar($p, 'Entrada', 10, ['motivo' => 'SURTIDO DE LOTE', 'referencia' => 'F-1']);

        $ticket = TicketVenta::create([
            'codigo_ticket' => 'TCK-' . Str::upper(Str::random(6)), 'monto_total' => 60,
            'status' => 'pagado', 'expires_at' => now()->addMinutes(25),
        ]);
        TicketDetalle::create(['ticket_id' => $ticket->id, 'producto_id' => $p->id, 'cantidad' => 3, 'precio_unitario' => 20, 'subtotal' => 60]);

        MovimientoCaja::create(['tipo' => 'Ingreso', 'concepto' => MovimientoCaja::CONCEPTO_APERTURA, 'monto' => 500, 'metodo_pago' => 'Efectivo']);
        MovimientoCaja::create(['ticket_id' => $ticket->id, 'tipo' => 'Ingreso', 'concepto' => 'PAGO DE TICKET', 'monto' => 60, 'metodo_pago' => 'Tarjeta']);
        MovimientoCaja::create(['tipo' => 'Egreso', 'concepto' => MovimientoCaja::PREFIJO_CIERRE . ' - CORTE', 'monto' => 500, 'metodo_pago' => 'Efectivo']);

        $periodo = PeriodoNomina::create(['tipo' => 'QUINCENAL', 'fecha_inicio' => now()->startOfMonth(), 'fecha_fin' => now()->startOfMonth()->addDays(14), 'estado' => 'Pagado']);
        ReciboNomina::create([
            'periodo_id' => $periodo->id, 'personal_id' => $this->medico()->id,
            'neto' => 1500, 'estado' => 'Pagado', 'fecha_pago' => now()->toDateString(), 'metodo_pago' => 'TRANSFERENCIA',
        ]);
    }

    public function test_calcula_ingresos_egresos_y_utilidad_del_periodo(): void
    {
        $this->escenario();

        $r = (new ReporteFinanciero(now()->startOfMonth(), now()))->resumen();
        $cat = collect($r['categorias'])->pluck('total', 'clave');

        $this->assertEquals(60, $r['ingresos']);              // el fondo de apertura no cuenta
        $this->assertEquals(100, $cat['compras']);            // el inventario inicial no cuenta
        $this->assertEquals(1500, $cat['nomina']);
        $this->assertEquals(0, $cat['otros_egresos']);        // el corte de cierre no cuenta
        $this->assertEquals(1600, $r['egresos']);
        $this->assertEquals(-1540, $r['balance']);
        $this->assertEquals(60, collect($r['metodos'])->firstWhere('metodo', 'Tarjeta')['total']);
        $this->assertEquals(['ventas' => 60, 'costo' => 30, 'utilidad' => 30, 'margen' => 50.0], $r['farmacia']);
    }

    public function test_la_tendencia_mensual_coincide_con_el_resumen(): void
    {
        $this->escenario();

        $mes = collect(ReporteFinanciero::mensual(now(), 12))->firstWhere('clave', now()->format('Y-m'));

        $this->assertEquals(60, $mes['ingresos']);
        $this->assertEquals(1600, $mes['egresos']);
    }

    public function test_la_pantalla_y_el_csv_responden(): void
    {
        $this->escenario();

        $this->get('/reportes')->assertOk()->assertSee('Reportes')->assertSee('$1,600.00');

        $csv = $this->get('/reportes/exportar')->assertOk()->streamedContent();
        $this->assertStringContainsString('Categoría', $csv);
        $this->assertStringContainsString('Compras de inventario', $csv);
        $this->assertStringContainsString('Nómina', $csv);
        $this->assertStringNotContainsString('APERTURA', $csv);
    }

    public function test_la_serie_por_dia_separa_cada_dia(): void
    {
        $this->escenario();   // todo ocurre hoy

        $dias = collect(ReporteFinanciero::diario(now()->subDays(2), now()))->keyBy('clave');

        $this->assertCount(3, $dias);
        $this->assertEquals(60, $dias[now()->toDateString()]['ingresos']);
        $this->assertEquals(1600, $dias[now()->toDateString()]['egresos']);
        $this->assertEquals(0, $dias[now()->subDay()->toDateString()]['ingresos']);
    }

    public function test_reporte_de_un_solo_dia_con_navegacion(): void
    {
        $this->escenario();
        $ayer = now()->subDay()->toDateString();
        $hoy  = now()->toDateString();

        $this->get("/reportes?desde={$hoy}&hasta={$hoy}")
            ->assertOk()
            ->assertSee('Ingresos y egresos por día')
            ->assertSee('Día anterior')
            ->assertDontSee('Día siguiente')            // no se navega al futuro
            ->assertSee('$1,600.00');

        // Ayer no tuvo movimientos
        $this->get("/reportes?desde={$ayer}&hasta={$ayer}")
            ->assertOk()
            ->assertSee('Día siguiente')
            ->assertSee('No hay ingresos ni egresos en este periodo.');
    }

    public function test_la_grafica_elige_dia_o_mes_segun_el_periodo(): void
    {
        $this->actingAs($this->admin());
        // Se revisa el HTML final (el middleware del chatbot reescribe la respuesta, así que no es una "vista")
        $barras = fn ($html) => substr_count($html, 'class="rp-hit"');

        $mes = $this->get('/reportes?desde=2026-01-01&hasta=2026-01-31')->assertSee('Ingresos y egresos por día')->getContent();
        $this->assertSame(31, $barras($mes));

        $anio = $this->get('/reportes?desde=2026-01-01&hasta=2026-12-31')->assertSee('Ingresos y egresos por mes')->getContent();
        $this->assertSame(12, $barras($anio));

        $anioPorDia = $this->get('/reportes?desde=2026-01-01&hasta=2026-12-31&vista=dia')
            ->assertSee('Últimos 92 días del periodo')->getContent();
        $this->assertSame(92, $barras($anioPorDia));
    }

    public function test_reportes_requiere_permiso(): void
    {
        $sinPermiso = $this->usuario($this->rol('Caja', ['Caja Central' => ['ver']]));
        $conPermiso = $this->usuario($this->rol('Contador', ['Reportes' => ['ver']]));

        $this->actingAs($sinPermiso)->get('/reportes')->assertRedirect(route('home'));
        $this->actingAs($conPermiso)->get('/reportes')->assertOk();
    }

    public function test_facturacion_ya_no_existe(): void
    {
        Modulo::sincronizar();

        $this->assertFalse(Modulo::where('nombre', 'Facturación')->exists());
        $this->assertFalse(Schema::hasTable('facturas'));
        $this->assertStringNotContainsString('Facturación', $this->actingAs($this->admin())->get('/home')->getContent());
    }
}
