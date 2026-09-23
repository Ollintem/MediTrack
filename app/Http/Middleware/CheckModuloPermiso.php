<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Permiso;

class CheckModuloPermiso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $user = Auth::user();

        // 1. Si no hay usuario, mandarlo al login
        if (!$user) {
            return redirect()->route('login');
        }

        // 2. Si es Super Admin o Administrador, pasa directo
        $isSuperAdmin = ($user->id === 1 || ($user->rol && in_array(strtolower(trim($user->rol->nombre)), ['super admin', 'administrador'])));
        
        if ($isSuperAdmin) {
            return $next($request);
        }

        // 3. Validar que tenga un rol asignado
        if (!$user->rol_id) {
            abort(403, 'No tienes un rol asignado en el sistema.');
        }

        // 4. Consultar si el rol tiene permiso (puede_ver = 1) para este módulo específico
        $tienePermiso = Permiso::where('rol_id', $user->rol_id)
            ->whereHas('modulo', function($query) use ($modulo) {
                $query->whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($modulo))]);
            })
            ->where('puede_ver', 1)
            ->exists();

        // 5. Si no tiene permiso, lo rebotamos al dashboard con un mensaje de error (SweetAlert2 lo atrapará)
        if (!$tienePermiso) {
            return redirect()->route('home')->with('error', 'Acceso denegado: No tienes permisos para el módulo de ' . $modulo);
        }

        return $next($request);
    }
}