<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        // El permiso se revisa ANTES de buscar el registro de la URL ({paciente}, {cita}...):
        // así quien no tiene permiso recibe 403 y no puede averiguar qué IDs existen (404).
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\CheckPermission::class,
        );

        // Asistente MediTrack en todas las páginas
        $middleware->web(append: [
            \App\Http\Middleware\InyectarChatbot::class,
        ]);

        // Confía en el túnel (cloudflared) para que Laravel sepa que la
        // página llegó por HTTPS y genere todas las URLs con https://
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * Respuestas de error uniformes para las peticiones AJAX/fetch de las vistas.
         * Todas llevan { ok:false, success:false, status:'error', message } con el código HTTP correcto:
         * 401 sesión, 403 permiso, 404 no existe, 405 método, 409 conflicto en BD, 419 CSRF, 429 límite, 500 servidor.
         * Las validaciones (422) se dejan con el formato propio de Laravel (message + errors).
         */
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($e instanceof ValidationException) {
                return null;
            }

            $esJson = $request->expectsJson() || $request->ajax() || $request->isJson();

            // Conflictos de base de datos (registro con datos relacionados o duplicado)
            if ($e instanceof QueryException) {
                $codigoMysql = (int) ($e->errorInfo[1] ?? 0);
                $mensaje = match ($codigoMysql) {
                    1451    => 'No se puede eliminar porque tiene registros relacionados en el sistema.',
                    1452    => 'El registro hace referencia a un dato que ya no existe.',
                    1062    => 'Ya existe un registro con esos datos.',
                    default => null,
                };

                if ($mensaje) {
                    return $esJson
                        ? response()->json(['ok' => false, 'success' => false, 'status' => 'error', 'message' => $mensaje], 409)
                        : back()->withInput()->with('error', $mensaje);
                }
            }

            if (!$esJson) {
                return null; // Navegación normal: páginas de error de Laravel
            }

            [$codigo, $mensaje] = match (true) {
                $e instanceof AuthenticationException  => [401, 'Tu sesión expiró. Vuelve a iniciar sesión.'],
                $e instanceof AuthorizationException   => [403, $e->getMessage() ?: 'No tienes permiso para realizar esta acción.'],
                $e instanceof ModelNotFoundException   => [404, 'El registro no existe o ya fue eliminado.'],
                $e instanceof TokenMismatchException   => [419, 'La página expiró. Recarga e intenta de nuevo.'],
                $e instanceof HttpExceptionInterface   => [$e->getStatusCode(), match ($e->getStatusCode()) {
                    403     => $e->getMessage() ?: 'No tienes permiso para realizar esta acción.',
                    404     => 'El registro no existe o ya fue eliminado.',
                    405     => 'Esta acción no está disponible.',
                    419     => 'La página expiró. Recarga e intenta de nuevo.',
                    429     => 'Demasiadas solicitudes. Espera un momento.',
                    default => $e->getMessage() ?: 'No se pudo completar la solicitud.',
                }],
                default => [500, config('app.debug')
                    ? 'Error en el servidor: ' . $e->getMessage()
                    : 'Ocurrió un error en el servidor. Intenta de nuevo.'],
            };

            return response()->json([
                'ok'      => false,
                'success' => false,
                'status'  => 'error',
                'message' => $mensaje,
            ], $codigo);
        });
    })->create();
