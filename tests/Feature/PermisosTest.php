<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class PermisosTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    public function test_sin_permiso_recibe_403_en_peticiones_json(): void
    {
        $user = $this->usuario($this->rol('Recepción', ['Pacientes' => ['ver']]));

        $this->actingAs($user)->getJson('/home/datos')->assertOk();
        $this->actingAs($user)->getJson('/citas/eventos')->assertForbidden();
    }

    public function test_con_permiso_puede_ver_el_modulo(): void
    {
        $user = $this->usuario($this->rol('Agenda', ['Citas' => ['ver']]));

        $this->actingAs($user)->get('/citas')->assertOk();
    }

    public function test_configuracion_solo_guarda_claves_permitidas(): void
    {
        $this->clinica();

        $this->actingAs($this->admin())->put('/configuracion', [
            'aviso_privacidad' => 'Texto Con Mayúsculas y minúsculas',
            'clave_inventada'  => 'no debe guardarse',
        ])->assertRedirect();

        $this->assertSame('Texto Con Mayúsculas y minúsculas', Configuracion::where('clave', 'aviso_privacidad')->value('valor'));
        $this->assertFalse(Configuracion::where('clave', 'clave_inventada')->exists());
    }

    public function test_bitacora_requiere_permiso(): void
    {
        $sinPermiso = $this->usuario($this->rol('Enfermería', ['Pacientes' => ['ver']]));
        $conPermiso = $this->usuario($this->rol('Auditor', ['Bitácora' => ['ver']]));

        $this->actingAs($sinPermiso)->get('/bitacora')->assertRedirect(route('home'));
        $this->actingAs($conPermiso)->get('/bitacora')->assertOk();
    }
}
