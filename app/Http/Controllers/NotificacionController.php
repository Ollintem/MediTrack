<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Campana de avisos. Cada usuario solo ve y marca SUS avisos.
 */
class NotificacionController extends Controller
{
    private const RECIENTES = 15;

    /** Avisos recientes y cuántos hay sin leer (la campana lo consulta cada 20 s). */
    public function index(Request $request): JsonResponse
    {
        $base = Notificacion::where('user_id', $request->user()->id);

        return response()->json([
            'sin_leer' => (clone $base)->noLeidas()->count(),
            'avisos'   => (clone $base)->latest('id')->limit(self::RECIENTES)->get()->map->paraVista()->values(),
        ])->header('Cache-Control', 'no-store');
    }

    /** Marca un aviso como leído y devuelve a dónde lleva. */
    public function leer(Request $request, int $id): JsonResponse
    {
        $aviso = Notificacion::where('user_id', $request->user()->id)->findOrFail($id);
        $aviso->update(['leida' => true]);

        return response()->json(['ok' => true, 'url' => $aviso->url]);
    }

    public function leerTodas(Request $request): JsonResponse
    {
        $n = Notificacion::where('user_id', $request->user()->id)->noLeidas()->update(['leida' => true, 'updated_at' => now()]);

        return response()->json(['ok' => true, 'marcadas' => $n]);
    }
}
