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
        $middleware->alias([
            'permiso' => \App\Http\Middleware\CheckPermission::class,
        ]);

        // Asistente MediTrack en todas las páginas
        $middleware->web(append: [
            \App\Http\Middleware\InyectarChatbot::class,
        ]);

        // Confía en el túnel (cloudflared) para que Laravel sepa que la
        // página llegó por HTTPS y genere todas las URLs con https://
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();