<?php

namespace App\Http\Controllers;

use App\Services\Cambios;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /cambios?modulo=citas → { firma }.
 * La pantalla abierta la consulta cada pocos segundos; si la firma cambia, se actualiza.
 * Solo devuelve un código (no datos), y respeta el permiso "ver" del módulo.
 */
class CambiosController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $modulo = (string) $request->query('modulo');

        if (!isset(Cambios::MODULOS[$modulo])) {
            return response()->json(['ok' => false, 'message' => 'Módulo desconocido.'], 404);
        }

        if (!$request->user()->tienePermiso(Cambios::MODULOS[$modulo][0], 'ver')) {
            return response()->json(['ok' => false, 'message' => 'Sin permiso para este módulo.'], 403);
        }

        return response()->json(['firma' => Cambios::firma($modulo)])
            ->header('Cache-Control', 'no-store');
    }
}
