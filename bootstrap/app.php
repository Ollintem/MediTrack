<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // REGISTRAMOS EL ALIAS 'permiso' PARA USARLO EN LAS RUTAS
        $middleware->alias([
            'permiso' => \App\Http\Middleware\CheckModuloPermiso::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();