<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Versión anterior del candado de módulos (solo revisaba "ver").
 * Se conserva por compatibilidad y delega en CheckPermission para que exista una sola regla.
 * En las rutas usa el alias "permiso:Modulo,accion".
 */
class CheckModuloPermiso
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        return app(CheckPermission::class)->handle($request, $next, $modulo, 'ver');
    }
}
