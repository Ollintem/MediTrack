<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Candado de permisos por ruta.
 *
 * Uso en routes/web.php:
 *   permiso:Pacientes              -> necesita "ver"
 *   permiso:Pacientes,crear        -> necesita "crear"
 *   permiso:Consultas,crear,editar -> basta con UNA de las acciones listadas
 *
 * Respuestas:
 *   - Sin sesión:  401 en JSON / redirección al login en navegación normal.
 *   - Sin permiso: 403 en JSON / redirección con mensaje de error en navegación normal.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $modulo, string ...$acciones): Response
    {
        $user = $request->user();
        $acciones = $acciones ?: ['ver'];

        // 1. Sin sesión
        if (!$user) {
            return $this->esperaJson($request)
                ? $this->json('Tu sesión expiró. Vuelve a iniciar sesión.', 401)
                : redirect()->guest(route('login'));
        }

        // 2. Basta con que tenga una de las acciones pedidas
        foreach ($acciones as $accion) {
            if ($user->tienePermiso($modulo, $accion)) {
                return $next($request);
            }
        }

        // 3. Sin permiso
        $mensaje = $this->mensaje($modulo, $acciones[0]);

        if ($this->esperaJson($request)) {
            return $this->json($mensaje, 403);
        }

        // Navegación normal: se regresa a una página válida y el layout muestra el aviso (SweetAlert)
        if ($request->isMethod('GET')) {
            return redirect()->route('home')->with('error', $mensaje);
        }

        return redirect()->back(302, [], route('home'))->with('error', $mensaje);
    }

    /** Peticiones AJAX/fetch: esperan JSON en lugar de una página. */
    private function esperaJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax() || $request->isJson();
    }

    private function json(string $mensaje, int $codigo): Response
    {
        // Se mandan las llaves que ya leen las vistas (message, ok, success, status)
        return response()->json([
            'ok'      => false,
            'success' => false,
            'status'  => 'error',
            'message' => $mensaje,
        ], $codigo);
    }

    private function mensaje(string $modulo, string $accion): string
    {
        $verbo = [
            'ver'      => 'acceder al módulo de',
            'crear'    => 'registrar en el módulo de',
            'editar'   => 'modificar en el módulo de',
            'eliminar' => 'eliminar en el módulo de',
        ][mb_strtolower($accion)] ?? 'usar el módulo de';

        return "No tienes permiso para {$verbo} {$modulo}.";
    }
}
