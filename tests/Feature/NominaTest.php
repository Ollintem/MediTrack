<?php

namespace Tests\Feature;

use App\Models\PeriodoNomina;
use App\Models\Personal;
use App\Models\ReciboNomina;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class NominaTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    private Personal $empleado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin());
        $this->empleado = $this->medico();
        $this->empleado->forceFill(['salario_diario' => 400, 'tipo_pago' => 'QUINCENAL'])->save();
    }

    private function periodo(string $tipo, string $inicio, string $fin): PeriodoNomina
    {
        $this->post('/personal/nomina', [
            'tipo' => $tipo, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'personal_ids' => [$this->empleado->id],
        ])->assertRedirect();

        return PeriodoNomina::latest('id')->first();
    }

    private function pagar(PeriodoNomina $p, string $fecha = null)
    {
        return $this->postJson("/personal/nomina/{$p->id}/pagar", [
            'recibo_ids' => $p->recibos()->pluck('id')->all(),
            'fecha_pago' => $fecha ?? now()->toDateString(),
            'metodo_pago' => 'TRANSFERENCIA',
        ]);
    }

    /** @dataProvider casosDeDias */
    public function test_regla_de_dias(string $tipo, string $inicio, string $fin, int $esperados): void
    {
        $this->assertSame($esperados, PeriodoNomina::diasAPagar($tipo, Carbon::parse($inicio), Carbon::parse($fin)));
    }

    public static function casosDeDias(): array
    {
        return [
            'quincena de 16 días paga 15'       => ['QUINCENAL', '2026-01-16', '2026-01-31', 15],
            'quincena de febrero paga 15'       => ['QUINCENAL', '2026-02-16', '2026-02-28', 15],
            'primera quincena paga 15'          => ['QUINCENAL', '2026-03-01', '2026-03-15', 15],
            'quincena incompleta: días reales'  => ['QUINCENAL', '2026-03-01', '2026-03-10', 10],
            'rango largo no pasa de 15'         => ['QUINCENAL', '2026-03-05', '2026-03-25', 15],
            'mes de 31 días paga 30'            => ['MENSUAL',   '2026-03-01', '2026-03-31', 30],
            'febrero paga 30'                   => ['MENSUAL',   '2026-02-01', '2026-02-28', 30],
            'mes incompleto: días reales'       => ['MENSUAL',   '2026-03-10', '2026-03-31', 22],
            'semana completa'                   => ['SEMANAL',   '2026-04-06', '2026-04-12', 7],
            'especial se captura a mano'        => ['ESPECIAL',  '2026-04-06', '2026-04-06', 0],
        ];
    }

    public function test_al_generar_llega_al_periodo_listo_para_pagar(): void
    {
        $this->post('/personal/nomina', [
            'tipo' => 'QUINCENAL', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-15', 'personal_ids' => [$this->empleado->id],
        ])->assertRedirect(route('personal.nomina.show', [PeriodoNomina::latest('id')->first(), 'pagar' => 1]));
    }

    public function test_la_pagina_del_periodo_ya_no_usa_ventana_de_pago(): void
    {
        $p = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15');

        $this->get("/personal/nomina/{$p->id}")->assertOk()
            ->assertSee('x-ref="barraPago"', false)          // barra de pago dentro de la página
            ->assertSee('Registrar pago')
            ->assertSee('Guardar y pagar')
            ->assertDontSee('modalPago', false);
    }

    public function test_el_recibo_de_una_quincena_de_16_dias_paga_15(): void
    {
        $recibo = $this->periodo('QUINCENAL', '2026-01-16', '2026-01-31')->recibos()->first();

        $this->assertEquals(15, $recibo->dias_trabajados);
        $this->assertEquals(6000, $recibo->sueldo_base);   // 400 × 15
    }

    public function test_no_se_borra_un_periodo_con_pagos(): void
    {
        $p = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15');
        $this->pagar($p, '2026-01-15')->assertOk();

        $this->deleteJson("/personal/nomina/{$p->id}")->assertStatus(409);
        $this->assertTrue(PeriodoNomina::whereKey($p->id)->exists());

        // Al revertir el pago, ya se puede eliminar
        $this->postJson('/personal/nomina/recibo/' . $p->recibos()->first()->id . '/revertir')->assertOk();
        $this->deleteJson("/personal/nomina/{$p->id}")->assertOk();
        $this->assertFalse(PeriodoNomina::whereKey($p->id)->exists());
    }

    public function test_no_se_borra_un_recibo_pagado(): void
    {
        $p = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15');
        $this->pagar($p, '2026-01-15')->assertOk();
        $recibo = $p->recibos()->first();

        $this->deleteJson("/personal/nomina/recibo/{$recibo->id}")->assertStatus(409);
        $this->assertTrue(ReciboNomina::whereKey($recibo->id)->exists());
    }

    private function guardarRecibo(ReciboNomina $r, array $extras)
    {
        return $this->putJson("/personal/nomina/recibo/{$r->id}", [
            'salario_diario' => 400, 'dias_trabajados' => 15, 'bonos' => 0,
            'isr' => 0, 'imss' => 0, 'otras_deducciones' => 0, 'extras' => $extras,
        ]);
    }

    public function test_pagos_extraordinarios_se_calculan_con_la_cuota_declarada(): void
    {
        $this->empleado->forceFill(['cuota_hora_extra' => 100])->save();
        $recibo = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15')->recibos()->first();

        $this->guardarRecibo($recibo, [
            ['concepto' => 'guardia del sábado', 'horas' => 4],
            ['concepto' => 'cierre de inventario', 'horas' => 1.5],
        ])->assertOk()->assertJsonPath('recibo.horas_extra', 550);   // (4 + 1.5) × 100

        $recibo->refresh();
        $this->assertEquals(6550, $recibo->total_percepciones);   // 6,000 de sueldo + 550 de extras
        $this->assertSame(['GUARDIA DEL SÁBADO', 'CIERRE DE INVENTARIO'], $recibo->extras->pluck('concepto')->all());
        $this->assertEquals([100, 100], $recibo->extras->pluck('cuota')->all());
    }

    public function test_el_recibo_en_pdf_lista_cada_pago_extraordinario(): void
    {
        $this->empleado->forceFill(['cuota_hora_extra' => 100])->save();
        $recibo = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15')->recibos()->first();
        $this->guardarRecibo($recibo, [['concepto' => 'guardia del sábado', 'horas' => 4]])->assertOk();

        // Se revisa el HTML de la plantilla del PDF (el PDF ya generado va comprimido)
        $recibo = $recibo->fresh(['periodo', 'personal', 'extras']);
        $html = view('personal.nomina.recibo-pdf', [
            'recibo' => $recibo, 'empleado' => $recibo->personal->nombre_completo,
            'datosClinica' => ['nombre' => 'CLÍNICA', 'rfc' => '', 'direccion' => '', 'telefono' => ''],
        ])->render();

        $this->assertStringContainsString('GUARDIA DEL SÁBADO (4 h × $100.00)', $html);
        $this->assertStringContainsString('$400.00', $html);
        $this->get("/personal/nomina/recibo/{$recibo->id}/pdf")->assertOk();
    }

    public function test_el_recibo_no_cambia_si_despues_cambia_la_cuota(): void
    {
        $this->empleado->forceFill(['cuota_hora_extra' => 100])->save();
        $recibo = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15')->recibos()->first();
        $this->guardarRecibo($recibo, [['concepto' => 'guardia', 'horas' => 2]])->assertOk();

        $this->empleado->forceFill(['cuota_hora_extra' => 999])->save();

        $this->assertEquals(200, $recibo->fresh()->horas_extra);
    }

    public function test_sin_cuota_declarada_no_se_agregan_pagos_extraordinarios(): void
    {
        $recibo = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15')->recibos()->first();

        $this->guardarRecibo($recibo, [['concepto' => 'guardia', 'horas' => 2]])
            ->assertStatus(422)->assertJsonFragment(['success' => false]);
        $this->assertEquals(0, $recibo->fresh()->horas_extra);
    }

    public function test_el_monto_de_horas_extra_no_se_puede_escribir_a_mano(): void
    {
        $this->empleado->forceFill(['cuota_hora_extra' => 100])->save();
        $recibo = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15')->recibos()->first();

        $this->putJson("/personal/nomina/recibo/{$recibo->id}", [
            'salario_diario' => 400, 'dias_trabajados' => 15, 'horas_extra' => 50000,
        ])->assertOk();

        // Solo cambia si se agregan pagos extraordinarios con concepto
        $this->assertEquals(0, $recibo->fresh()->horas_extra);
    }

    public function test_la_cuota_se_declara_en_la_ficha_del_personal(): void
    {
        $user = $this->usuario($this->rol('Doctor'));
        $p = $user->personal;

        $this->putJson("/personal/{$p->id}", [
            'nombre' => 'ANA', 'apellido' => 'LÓPEZ', 'rol_id' => $user->rol_id, 'turno' => 'Completo',
            'requiere_acceso' => 1, 'username' => strstr($user->email, '@', true),
            'salario_diario' => 400, 'cuota_hora_extra' => 125.5, 'tipo_pago' => 'QUINCENAL',
        ])->assertOk();

        $this->assertEquals(125.5, (float) $p->fresh()->cuota_hora_extra);
    }

    public function test_no_se_registra_un_pago_con_fecha_futura(): void
    {
        $p = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15');

        $this->pagar($p, now()->addDay()->toDateString())->assertStatus(422)->assertJsonValidationErrors('fecha_pago');
        $this->assertSame('Pendiente', $p->recibos()->first()->estado);
    }
}
