<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si a alguien se le marca como "Inactivo" mientras tiene la sesión abierta,
 * en su siguiente petición se cierra la sesión (el login ya no lo deja entrar).
 */
class CerrarSesionInactivos
{
    private const MENSAJE = 'Tu cuenta fue desactivada. Pide a un administrador que la reactive.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->estaInactivo()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok'      => false,
                    'success' => false,
                    'status'  => 'error',
                    'message' => self::MENSAJE,
                ], 401);
            }

            return redirect()->route('login')->withErrors(['email' => self::MENSAJE]);
        }

        return $next($request);
    }
}
