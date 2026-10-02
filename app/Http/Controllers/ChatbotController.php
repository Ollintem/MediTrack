<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    private const SESION = 'chatbot_historial';

    /** Recibe un mensaje del widget y devuelve la respuesta del asistente. */
    public function mensaje(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'mensaje' => ['required', 'string', 'max:1000'],
        ]);

        // El historial se guarda en la sesión del usuario (solo texto, sin resultados de BD)
        $historial = $request->session()->get(self::SESION, []);
        $historial[] = ['role' => 'user', 'content' => trim($datos['mensaje'])];
        $historial = array_slice($historial, -config('chatbot.max_historial', 20));

        // La API exige que la conversación empiece con un mensaje del usuario
        while ($historial && $historial[0]['role'] !== 'user') {
            array_shift($historial);
        }

        try {
            $respuesta = (new ChatbotService($request->user()))->responder($historial);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'respuesta' => 'Lo siento, el asistente no está disponible en este momento. Intenta de nuevo más tarde.',
                'error'     => true,
                // Con APP_DEBUG=true se muestra el motivo real en el chat (útil mientras desarrollas)
                'detalle'   => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }

        $historial[] = ['role' => 'assistant', 'content' => $respuesta];
        $request->session()->put(self::SESION, $historial);

        return response()->json(['respuesta' => $respuesta]);
    }

    /** Borra la conversación actual. */
    public function reiniciar(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESION);

        return response()->json(['ok' => true]);
    }
}