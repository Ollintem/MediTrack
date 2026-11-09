<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\ProductoInventario;
use App\Models\TicketDetalle;
use App\Models\TicketVenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class InventarioPosTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    private function ticket(ProductoInventario $producto, int $cantidad, string $status, int $minutos = 25): TicketVenta
    {
        $ticket = TicketVenta::create([
            'codigo_ticket' => 'TCK-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'monto_total'   => 20 * $cantidad,
            'status'        => $status,
            'expires_at'    => now()->addMinutes($minutos),
        ]);
        TicketDetalle::create([
            'ticket_id' => $ticket->id, 'producto_id' => $producto->id,
            'cantidad'  => $cantidad, 'precio_unitario' => 20, 'subtotal' => 20 * $cantidad,
        ]);

        return $ticket;
    }

    public function test_el_stock_disponible_descuenta_lo_apartado_en_una_sola_consulta(): void
    {
        $p = $this->producto(10);
        $this->ticket($p, 3, 'pendiente');          // apartado vigente
        $this->ticket($p, 2, 'pagado');             // pagado, falta entregar
        $this->ticket($p, 4, 'pendiente', -5);      // ya vencido: no cuenta

        $cargado = ProductoInventario::conStock()->find($p->id);

        $this->assertSame(5, $cargado->stock_reservado);
        $this->assertSame(5, $cargado->stock_disponible);
        $this->assertSame($p->fresh()->stock_disponible, $cargado->stock_disponible); // misma regla sin conStock
    }

    public function test_entregar_descuenta_stock_y_registra_la_salida(): void
    {
        $p = $this->producto(10);
        $ticket = $this->ticket($p, 3, 'pagado');

        $this->actingAs($this->admin())->postJson("/pos/marcar-entregado/{$ticket->id}")->assertOk();

        $this->assertSame(7, (int) $p->fresh()->stock_actual);
        $this->assertSame('entregado', $ticket->fresh()->getRawOriginal('status'));
        $this->assertTrue(MovimientoInventario::where(['producto_id' => $p->id, 'tipo' => 'Salida', 'cantidad' => 3, 'ticket_id' => $ticket->id])->exists());
    }

    public function test_no_entrega_si_no_hay_existencia_fisica(): void
    {
        $p = $this->producto(2);
        $ticket = $this->ticket($p, 5, 'pagado');

        $this->actingAs($this->admin())->postJson("/pos/marcar-entregado/{$ticket->id}")->assertStatus(409);

        $this->assertSame(2, (int) $p->fresh()->stock_actual);
        $this->assertSame('pagado', $ticket->fresh()->getRawOriginal('status'));
    }

    public function test_surtir_lote_guarda_referencia_lote_y_movimiento(): void
    {
        $p = $this->producto(1);

        $this->actingAs($this->admin())->postJson('/inventario/surtir-lote', [
            'producto_id' => $p->id, 'cantidad' => 10, 'referencia' => 'Factura 123', 'lote' => 'L-77',
        ])->assertOk();

        $this->assertSame(11, (int) $p->fresh()->stock_actual);
        $this->assertTrue(MovimientoInventario::where([
            'producto_id' => $p->id, 'tipo' => 'Entrada', 'cantidad' => 10, 'referencia' => 'FACTURA 123', 'lote' => 'L-77',
        ])->exists());
    }

    public function test_la_tarea_programada_expira_tickets_vencidos(): void
    {
        $p = $this->producto(10);
        $vencido = $this->ticket($p, 1, 'pendiente', -1);
        $vigente = $this->ticket($p, 1, 'pendiente', 10);

        $this->artisan('tickets:expirar')->assertSuccessful();

        $this->assertSame('expirado', $vencido->fresh()->getRawOriginal('status'));
        $this->assertSame('pendiente', $vigente->fresh()->getRawOriginal('status'));
    }
}
