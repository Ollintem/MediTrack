<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\PeriodoNomina;
use App\Models\Personal;
use App\Models\TicketDetalle;
use App\Models\TicketVenta;
use App\Models\User;
use App\Services\Notificador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    /** Un médico con cuenta (su ficha de personal es el personal_id de las citas). */
    private function medicoConCuenta(): User
    {
        return $this->usuario($this->rol('Doctor', ['Consultas' => ['ver'], 'Citas' => ['ver']]));
    }

    private function avisosDe(User $u)
    {
        return Notificacion::where('user_id', $u->id)->orderBy('id')->get();
    }

    // ----- Citas -----

    public function test_al_agendar_una_cita_le_llega_al_medico(): void
    {
        $medico = $this->medicoConCuenta();
        $this->actingAs($this->admin());

        $this->cita(['personal_id' => $medico->personal->id, 'fecha' => '2031-01-10', 'hora' => '10:00:00']);

        $avisos = $this->avisosDe($medico);
        $this->assertCount(1, $avisos);
        $this->assertSame('Nueva cita', $avisos[0]->titulo);
        $this->assertStringContainsString('MARIA CORTES MORENO · vie 10 ene 10:00', $avisos[0]->mensaje);
        $this->assertSame('Citas', $avisos[0]->tipo);
    }

    public function test_no_se_avisa_a_quien_hizo_el_cambio(): void
    {
        $medico = $this->medicoConCuenta();
        $this->actingAs($medico);   // el propio médico agenda su cita

        $this->cita(['personal_id' => $medico->personal->id]);

        $this->assertCount(0, $this->avisosDe($medico));
    }

    public function test_reprogramar_cancelar_y_reasignar_avisan(): void
    {
        $medico = $this->medicoConCuenta();
        $otro = $this->medicoConCuenta();
        $this->actingAs($this->admin());
        $cita = $this->cita(['personal_id' => $medico->personal->id, 'fecha' => '2031-01-10', 'hora' => '10:00:00']);

        $cita->update(['hora' => '12:00:00']);
        $cita->update(['motivo' => 'OTRO MOTIVO']);              // no avisa: no cambia el horario
        $cita->update(['personal_id' => $otro->personal->id]);   // reasignada
        $cita->update(['estado' => 'Cancelada']);                // le avisa al médico actual

        $this->assertSame(['Nueva cita', 'Cita reprogramada', 'Cita reasignada'], $this->avisosDe($medico)->pluck('titulo')->all());
        $this->assertStringContainsString('10:00 → vie 10 ene 12:00', $this->avisosDe($medico)[1]->mensaje);
        $this->assertSame(['Nueva cita', 'Cita cancelada'], $this->avisosDe($otro)->pluck('titulo')->all());
    }

    // ----- Inventario -----

    public function test_stock_bajo_y_agotado_avisan_solo_al_cruzar(): void
    {
        $almacen = $this->usuario($this->rol('Almacén', ['Inventario' => ['ver', 'editar']]));
        $sinPermiso = $this->usuario($this->rol('Recepción', ['Pacientes' => ['ver']]));
        $this->actingAs($this->admin());
        $p = $this->producto(20, 5);

        $p->update(['stock_actual' => 4]); $p->recalcularEstado()->save();   // cruza a bajo stock
        $p->update(['stock_actual' => 3]); $p->recalcularEstado()->save();   // sigue bajo: no repite
        $p->update(['stock_actual' => 0]); $p->recalcularEstado()->save();   // agotado

        $this->assertSame(['Stock bajo', 'Producto agotado'], $this->avisosDe($almacen)->pluck('titulo')->all());
        $this->assertStringContainsString('quedan 4 (mínimo 5)', $this->avisosDe($almacen)[0]->mensaje);
        $this->assertCount(0, $this->avisosDe($sinPermiso));
    }

    // ----- Caja → POS -----

    public function test_ticket_pagado_avisa_a_quien_entrega_en_el_pos(): void
    {
        $farmacia = $this->usuario($this->rol('Farmacia', ['POS' => ['ver', 'editar']]));
        $cajero = $this->usuario($this->rol('Cajero', ['Caja Central' => ['ver', 'crear']]));
        $this->actingAs($cajero);
        $p = $this->producto(10);
        $ticket = TicketVenta::create(['codigo_ticket' => 'TCK-' . Str::upper(Str::random(6)), 'monto_total' => 40, 'status' => 'pendiente', 'expires_at' => now()->addMinutes(25)]);
        TicketDetalle::create(['ticket_id' => $ticket->id, 'producto_id' => $p->id, 'cantidad' => 2, 'precio_unitario' => 20, 'subtotal' => 40]);
        \App\Models\MovimientoCaja::create(['tipo' => 'Ingreso', 'concepto' => \App\Models\MovimientoCaja::CONCEPTO_APERTURA, 'monto' => 0, 'metodo_pago' => 'Efectivo']);

        $this->postJson("/caja/procesar-pago/{$ticket->id}", ['metodo_pago' => 'Efectivo'])->assertOk();

        $avisos = $this->avisosDe($farmacia);
        $this->assertCount(1, $avisos);
        $this->assertSame('Ticket pagado', $avisos[0]->titulo);
        $this->assertStringContainsString($ticket->codigo_ticket . ' ($40.00) está listo para entregar', $avisos[0]->mensaje);
        $this->assertCount(0, $this->avisosDe($cajero));   // quien cobró no se avisa a sí mismo
    }

    // ----- Nómina (diaria) -----

    public function test_nomina_por_pagar_avisa_una_vez_por_dia(): void
    {
        $rh = $this->usuario($this->rol('RH', ['Personal' => ['ver', 'editar']]));
        $this->actingAs($this->admin());
        $emp = $this->medico();
        $emp->forceFill(['salario_diario' => 100])->save();
        $this->post('/personal/nomina', ['tipo' => 'QUINCENAL', 'fecha_inicio' => now()->startOfMonth()->toDateString(),
            'fecha_fin' => now()->startOfMonth()->addDays(14)->toDateString(), 'fecha_pago' => now()->toDateString(), 'personal_ids' => [$emp->id]]);

        $this->artisan('notificaciones:diarias')->assertSuccessful();
        $this->artisan('notificaciones:diarias')->assertSuccessful();   // misma fecha: no repite

        $avisos = $this->avisosDe($rh);
        $this->assertCount(1, $avisos);
        $this->assertSame('Nómina por pagar', $avisos[0]->titulo);
        $this->assertStringContainsString('1 recibo pendiente se pagan hoy', $avisos[0]->mensaje);
        $this->assertStringContainsString('/personal/nomina/' . PeriodoNomina::first()->id, $avisos[0]->url);
    }

    public function test_limpieza_borra_solo_leidos_viejos(): void
    {
        $u = $this->admin();
        $viejaLeida = Notificacion::create(['clinica_id' => $this->clinica()->id, 'user_id' => $u->id, 'titulo' => 'A', 'mensaje' => 'a', 'tipo' => 'Sistema', 'leida' => true, 'fecha' => now()]);
        $viejaSinLeer = Notificacion::create(['clinica_id' => $this->clinica()->id, 'user_id' => $u->id, 'titulo' => 'B', 'mensaje' => 'b', 'tipo' => 'Sistema', 'leida' => false, 'fecha' => now()]);
        Notificacion::whereKey([$viejaLeida->id, $viejaSinLeer->id])->update(['created_at' => now()->subDays(61)]);

        $this->assertSame(1, Notificador::limpiar(60));
        $this->assertTrue(Notificacion::whereKey($viejaSinLeer->id)->exists());
    }

    // ----- Campana -----

    public function test_cada_quien_ve_y_marca_solo_sus_avisos(): void
    {
        $medico = $this->medicoConCuenta();
        $otro = $this->medicoConCuenta();
        $this->actingAs($this->admin());
        $this->cita(['personal_id' => $medico->personal->id]);
        $aviso = $this->avisosDe($medico)->first();

        $this->actingAs($otro)->getJson('/notificaciones')->assertOk()->assertJsonPath('sin_leer', 0)->assertJsonCount(0, 'avisos');
        $this->actingAs($otro)->postJson("/notificaciones/{$aviso->id}/leer")->assertNotFound();

        $this->actingAs($medico)->getJson('/notificaciones')->assertOk()->assertJsonPath('sin_leer', 1)->assertJsonPath('avisos.0.titulo', 'Nueva cita');
        $this->actingAs($medico)->postJson("/notificaciones/{$aviso->id}/leer")->assertOk()->assertJsonPath('url', $aviso->url);
        $this->actingAs($medico)->getJson('/notificaciones')->assertJsonPath('sin_leer', 0);
    }

    public function test_marcar_todas_como_leidas(): void
    {
        $medico = $this->medicoConCuenta();
        $this->actingAs($this->admin());
        $this->cita(['personal_id' => $medico->personal->id]);
        $this->cita(['personal_id' => $medico->personal->id, 'hora' => '12:00:00']);

        $this->actingAs($medico)->postJson('/notificaciones/leer-todas')->assertOk()->assertJsonPath('marcadas', 2);
        $this->assertSame(0, Notificacion::where('user_id', $medico->id)->noLeidas()->count());
    }

    public function test_la_campana_muestra_el_numero_de_avisos_sin_leer(): void
    {
        $medico = $this->medicoConCuenta();
        $this->actingAs($this->admin());
        $this->cita(['personal_id' => $medico->personal->id]);

        $this->actingAs($medico)->get('/consultas')->assertOk()->assertSee('campanaAvisos(1)', false);
    }

    public function test_una_falla_al_avisar_no_impide_guardar(): void
    {
        $medico = $this->medicoConCuenta();
        $this->actingAs($this->admin());
        // Se fuerza un error al crear cualquier aviso
        Notificacion::creating(function () { throw new \RuntimeException('falla simulada al avisar'); });

        $cita = $this->cita(['personal_id' => $medico->personal->id]);   // crear avisa → falla → se ignora
        $cita->update(['hora' => '15:00:00']);                           // reprogramar avisa → falla → se ignora

        $this->assertSame('15:00:00', substr((string) $cita->fresh()->hora, 0, 8));
        $this->assertSame(0, Notificacion::count());
    }
}
