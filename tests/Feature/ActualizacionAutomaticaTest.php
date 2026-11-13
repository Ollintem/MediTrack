<?php

namespace Tests\Feature;

use App\Services\Cambios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class ActualizacionAutomaticaTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    private function firma(string $modulo): string
    {
        return $this->getJson("/cambios?modulo={$modulo}")->assertOk()->json('firma');
    }

    public function test_la_firma_cambia_al_agregar_editar_o_borrar(): void
    {
        $this->actingAs($this->admin());
        $inicial = $this->firma('citas');
        $this->assertSame($inicial, $this->firma('citas'));          // sin cambios, misma firma

        $cita = $this->cita();
        $trasAgregar = $this->firma('citas');
        $this->assertNotSame($inicial, $trasAgregar);

        $this->travel(2)->seconds();                                  // updated_at distinto
        $cita->update(['motivo' => 'OTRO MOTIVO']);
        $trasEditar = $this->firma('citas');
        $this->assertNotSame($trasAgregar, $trasEditar);

        $cita->delete();
        $this->assertNotSame($trasEditar, $this->firma('citas'));
    }

    public function test_respeta_los_permisos_del_modulo(): void
    {
        $user = $this->usuario($this->rol('Recepción', ['Citas' => ['ver']]));

        $this->actingAs($user)->getJson('/cambios?modulo=citas')->assertOk()->assertJsonStructure(['firma']);
        $this->actingAs($user)->getJson('/cambios?modulo=nomina')->assertForbidden();
        $this->actingAs($user)->getJson('/cambios?modulo=inventado')->assertNotFound();
    }

    public function test_sin_sesion_no_responde(): void
    {
        $this->getJson('/cambios?modulo=citas')->assertUnauthorized();
    }

    public function test_cada_pantalla_vigilada_trae_el_script_de_su_modulo(): void
    {
        $this->actingAs($this->admin());

        $this->get('/citas')->assertOk()->assertSee('/cambios', false)->assertSee("const MODULO = 'citas'", false);
        $this->get('/personal/nomina')->assertOk()->assertSee("const MODULO = 'nomina'", false);
        // El POS espera a que el carrito esté vacío para recargar
        $this->get('/pos')->assertOk()->assertSee("const MODULO = 'pos'", false)->assertSee('window.mtOcupado = () => this.carrito.length > 0', false);
        // El Dashboard ya se actualiza con su propio mecanismo
        $this->get('/home')->assertOk()->assertDontSee('const MODULO =', false);
        // Ya no hay aviso flotante encima de la pantalla
        $this->get('/citas')->assertDontSee('Hay cambios nuevos');
    }

    public function test_cada_modulo_y_ruta_esta_bien_configurado(): void
    {
        foreach (Cambios::MODULOS as $modulo => [$permiso, $tablas]) {
            $this->assertNotEmpty($tablas, $modulo);
            $this->actingAs($this->admin())->getJson("/cambios?modulo={$modulo}")->assertOk();
        }
        $this->assertSame('nomina', Cambios::moduloDeRuta('personal.nomina.show'));
        $this->assertSame('personal', Cambios::moduloDeRuta('personal.index'));
        $this->assertNull(Cambios::moduloDeRuta('home'));
    }
}
