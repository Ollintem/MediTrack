<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class CitasConsultoriosTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    public function test_pasar_a_en_curso_ocupa_y_cancelar_libera_el_consultorio(): void
    {
        $admin = $this->admin();
        $cita = $this->cita();

        $this->actingAs($admin)->putJson("/citas/{$cita->id}", $this->formularioCita($cita, ['estado' => 'En curso']))->assertOk();
        $this->assertSame('Ocupado', $cita->consultorio->fresh()->estado);

        $this->actingAs($admin)->putJson("/citas/{$cita->id}", $this->formularioCita($cita->fresh(), ['estado' => 'Cancelada']))->assertOk();
        $this->assertSame('Disponible', $cita->consultorio->fresh()->estado);
    }

    public function test_no_libera_si_queda_otra_consulta_en_curso(): void
    {
        $sala = $this->consultorio('Ocupado');
        $c1 = $this->cita(['consultorio_id' => $sala->id, 'estado' => 'En curso']);
        $this->cita(['consultorio_id' => $sala->id, 'estado' => 'En curso', 'hora' => '11:00:00']);

        $this->actingAs($this->admin())->putJson("/citas/{$c1->id}", $this->formularioCita($c1, ['estado' => 'Cancelada']))->assertOk();

        $this->assertSame('Ocupado', $sala->fresh()->estado);
    }

    public function test_borrar_una_cita_en_curso_libera_el_consultorio(): void
    {
        $sala = $this->consultorio('Ocupado');
        $cita = $this->cita(['consultorio_id' => $sala->id, 'estado' => 'En curso']);

        $this->actingAs($this->admin())->deleteJson("/citas/{$cita->id}")->assertOk();

        $this->assertSame('Disponible', $sala->fresh()->estado);
    }

    public function test_no_se_agenda_encima_de_otra_cita_del_mismo_medico(): void
    {
        $cita = $this->cita();
        $otra = $this->formularioCita($cita, ['consultorio_id' => $this->consultorio()->id, 'hora' => '09:15']);

        $this->actingAs($this->admin())->postJson('/citas', $otra)
            ->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);
    }

    public function test_el_nombre_del_paciente_no_repite_el_apellido(): void
    {
        $this->cita(['fecha' => now()->toDateString()]);

        $agenda = $this->actingAs($this->admin())->getJson('/home/datos')->assertOk()->json('agenda');

        $this->assertSame('MARIA CORTES MORENO', $agenda[0]['paciente']);
    }
}
