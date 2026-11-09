<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** La raíz manda al login (antes la prueba esperaba 200 y fallaba). */
    public function test_la_raiz_redirige_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_la_pagina_de_login_carga(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_sin_sesion_no_se_entra_al_sistema(): void
    {
        $this->get('/home')->assertRedirect(route('login'));
    }
}
