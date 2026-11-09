<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seguro contra accidentes: las pruebas borran y recrean la base que usan (RefreshDatabase).
     * Se revisa al crear la aplicación, ANTES de que RefreshDatabase toque nada.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $base = $app['config']->get('database.connections.' . $app['config']->get('database.default') . '.database');
        if ($base !== 'meditrack_test') {
            throw new RuntimeException("Las pruebas solo pueden usar la base 'meditrack_test' (se intentó usar '{$base}'). Revisa phpunit.xml.");
        }

        return $app;
    }
}
