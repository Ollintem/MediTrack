<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\Receta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class RecetasRendimientoTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    public function test_medicamentos_invalidos_dan_422_y_no_error_500(): void
    {
        $paciente = $this->paciente();

        $this->actingAs($this->admin())->postJson('/recetas', [
            'paciente_id'  => $paciente->id,
            'diagnostico'  => 'GRIPE',
            'medicamentos' => [['nombre' => ['no', 'es', 'texto']]],
        ])->assertStatus(422)->assertJsonValidationErrors('medicamentos.0.nombre');

        $this->assertSame(0, Receta::count());
    }

    public function test_receta_con_medicamentos_se_guarda_completa(): void
    {
        $paciente = $this->paciente();

        $this->actingAs($this->admin())->postJson('/recetas', [
            'paciente_id'  => $paciente->id,
            'diagnostico'  => 'gripe',
            'medicamentos' => [['nombre' => 'paracetamol', 'dosis' => '500 mg'], ['nombre' => '']],
        ])->assertCreated();

        $receta = Receta::with('detalles')->first();
        $this->assertSame('GRIPE', $receta->diagnostico);
        $this->assertSame(['PARACETAMOL'], $receta->detalles->pluck('medicamento')->all());
    }

    public function test_no_se_puede_enlazar_la_consulta_de_otro_medico(): void
    {
        $medicoUsuario = $this->usuario($this->rol('Doctor', ['Recetas' => ['ver', 'crear']]));
        $cita = $this->cita();   // pertenece a otro médico
        $consulta = Consulta::create([
            'cita_id' => $cita->id, 'paciente_id' => $cita->paciente_id, 'personal_id' => $cita->personal_id,
            'fecha' => now(), 'estado' => 'Finalizada',
        ]);

        $this->actingAs($medicoUsuario)->postJson('/recetas', [
            'paciente_id'  => $cita->paciente_id,
            'consulta_id'  => $consulta->id,
            'diagnostico'  => 'GRIPE',
            'medicamentos' => [['nombre' => 'PARACETAMOL']],
        ])->assertStatus(422)->assertJsonValidationErrors('consulta_id');
    }

    public function test_clinica_y_configuracion_se_consultan_una_vez_por_pagina(): void
    {
        $this->clinica();
        $admin = $this->admin();

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/inventario')->assertOk();
        $consultas = collect(DB::getQueryLog())->pluck('query');

        $this->assertSame(1, $consultas->filter(fn ($q) => str_contains($q, 'from `clinicas`'))->count());
        $this->assertSame(1, $consultas->filter(fn ($q) => str_contains($q, 'from `configuracions`'))->count());
    }
}
