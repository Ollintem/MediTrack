<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $modulo, string $accion = 'ver'): Response
    {
        $user = $request->user();

        // 1. Si no hay usuario autenticado, redirigir al login
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Usar el método tienePermiso de tu modelo User (cubre ID 1, Administradores y permisos de BD)
        if ($user->tienePermiso($modulo, $accion)) {
            return $next($request);
        }

        // 3. Si no tiene permiso, abortar con error 403
        abort(403, 'No tienes permisos para acceder a este recurso.');
    }
}