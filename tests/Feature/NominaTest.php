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

    public function test_no_se_registra_un_pago_con_fecha_futura(): void
    {
        $p = $this->periodo('QUINCENAL', '2026-01-01', '2026-01-15');

        $this->pagar($p, now()->addDay()->toDateString())->assertStatus(422)->assertJsonValidationErrors('fecha_pago');
        $this->assertSame('Pendiente', $p->recibos()->first()->estado);
    }
}
