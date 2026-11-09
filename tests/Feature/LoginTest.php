<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosDePrueba;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase, DatosDePrueba;

    public function test_entra_con_usuario_y_contrasena_correctos(): void
    {
        $user = $this->usuario();

        $this->post('/login', ['email' => $user->email, 'password' => 'Clave1234'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_bloquea_despues_de_cinco_intentos_fallidos(): void
    {
        $user = $this->usuario();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'Incorrecta1']);
        }

        // Aun con la contraseña correcta, queda bloqueado
        $this->post('/login', ['email' => $user->email, 'password' => 'Clave1234'])
            ->assertSessionHasErrors('email');

        // Los segundos restantes varían (59 o 60) según el tiempo que tardó la prueba
        $this->assertStringContainsString('Demasiados intentos fallidos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_personal_inactivo_no_puede_entrar(): void
    {
        $user = $this->usuario(estado: 'Inactivo');

        $this->post('/login', ['email' => $user->email, 'password' => 'Clave1234'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_se_cierra_la_sesion_de_quien_es_desactivado(): void
    {
        $user = $this->admin();
        $user->personal->update(['estado' => 'Inactivo']);

        $this->actingAs($user)->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
