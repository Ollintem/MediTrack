<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Agrega el widget del Asistente MediTrack a TODAS las páginas HTML
 * del sistema cuando hay un usuario con sesión iniciada.
 */
class InyectarChatbot
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        // Solo usuarios logueados, páginas normales (no JSON, AJAX, descargas ni redirecciones)
        if (!$request->user()
            || !$response instanceof Response
            || $request->expectsJson()
            || $request->is('chatbot/*')) {
            return $response;
        }

        // Las vistas normalmente aún no traen Content-Type aquí; si lo traen, debe ser HTML
        $tipo = $response->headers->get('Content-Type');
        if ($tipo && !str_contains($tipo, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        $posicion = strripos($html, '</body>');

        // Si la página no tiene </body> o ya trae el widget, no se toca
        if ($posicion === false || str_contains($html, 'id="mt-chat-boton"')) {
            return $response;
        }

        $widget = view('partials.chatbot')->render();
        $response->setContent(substr_replace($html, $widget, $posicion, 0));

        return $response;
    }
}   