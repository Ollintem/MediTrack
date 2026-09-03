<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Permiso;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $modulo, string $accion = 'puede_ver'): Response
    {
        $user = $request->user();

        // 1. Si no hay usuario autenticado, redirigir al login
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Si es el Super Admin (ID 1), otorgar acceso inmediato
        if ($user->id === 1) {
            return $next($request);
        }

        // 3. Para otros usuarios, consultar sus permisos según su rol y módulo
        $tienePermiso = Permiso::where('rol_id', $user->rol_id)
            ->whereHas('modulo', function ($query) use ($modulo) {
                $query->where('nombre', $modulo);
            })
            ->where($accion, true)
            ->exists();

        if (!$tienePermiso) {
            abort(403, 'No tienes permisos para acceder a este recurso.');
        }

        return $next($request);
    }
}