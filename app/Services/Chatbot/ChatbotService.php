<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Asistente MediTrack con Google Gemini.
 * Explica el uso del sistema (guía) y consulta la base de datos mediante
 * HerramientasChatbot usando "function calling" de Gemini.
 */
class ChatbotService
{
    private HerramientasChatbot $herramientas;

    public function __construct(private User $user)
    {
        $this->herramientas = new HerramientasChatbot($user);
    }

    /**
     * @param  array  $historial  [['role' => 'user'|'assistant', 'content' => 'texto'], ...]
     * @return string  Respuesta final en texto
     */
    public function responder(array $historial): string
    {
        if (!config('chatbot.api_key')) {
            throw new RuntimeException('Falta configurar GEMINI_API_KEY en el archivo .env');
        }

        // Gemini usa 'model' en lugar de 'assistant' y el texto va dentro de 'parts'
        $contenidos = array_map(fn ($m) => [
            'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $historial);

        $funciones = $this->declaracionesGemini();
        $maxIteraciones = (int) config('chatbot.max_iteraciones_herramientas', 5);

        for ($i = 0; $i <= $maxIteraciones; $i++) {
            $respuesta = $this->llamarApi($contenidos, $i < $maxIteraciones ? $funciones : []);

            $candidato = $respuesta['candidates'][0] ?? null;
            $contenido = $candidato['content'] ?? null;
            $partes = $contenido['parts'] ?? [];

            if (!$candidato || ($candidato['finishReason'] ?? '') === 'SAFETY') {
                return 'No puedo responder esa pregunta. Intenta formularla de otra manera.';
            }

            $llamadas = array_values(array_filter($partes, fn ($p) => isset($p['functionCall'])));

            if (!$llamadas) {
                return $this->extraerTexto($partes);
            }

            // Se regresa el turno del modelo tal cual (incluye datos internos que Gemini necesita).
            // Los argumentos vacíos {} llegan como [] en PHP; se convierten de nuevo en objeto.
            $contenido['parts'] = array_map(function ($p) {
                if (isset($p['functionCall'])) {
                    $p['functionCall']['args'] = (object) ($p['functionCall']['args'] ?? []);
                }
                return $p;
            }, $partes);
            $contenidos[] = $contenido;

            // Ejecutar cada consulta solicitada y devolver los resultados
            $resultados = [];
            foreach ($llamadas as $parte) {
                $llamada = $parte['functionCall'];
                $args = (array) ($llamada['args'] ?? []);

                $salida = $this->herramientas->ejecutar($llamada['name'], $args);

                Log::info('Chatbot herramienta', [
                    'user_id'     => $this->user->id,
                    'herramienta' => $llamada['name'],
                    'parametros'  => $args,
                ]);

                $respuestaFuncion = [
                    'name'     => $llamada['name'],
                    'response' => (object) $salida,
                ];
                if (isset($llamada['id'])) {
                    $respuestaFuncion['id'] = $llamada['id'];
                }

                $resultados[] = ['functionResponse' => $respuestaFuncion];
            }

            $contenidos[] = ['role' => 'user', 'parts' => $resultados];
        }

        return 'No pude completar la consulta. Intenta hacer la pregunta de forma más específica.';
    }

    private function llamarApi(array $contenidos, array $funciones): array
    {
        $cuerpo = [
            'systemInstruction' => ['parts' => [['text' => $this->promptSistema()]]],
            'contents'          => $contenidos,
            'generationConfig'  => [
                'maxOutputTokens' => (int) config('chatbot.max_tokens', 1024),
                'temperature'     => 0.3,
            ],
        ];

        if ($funciones) {
            $cuerpo['tools'] = [['functionDeclarations' => $funciones]];
        }

        $url = config('chatbot.api_url') . '/' . config('chatbot.model') . ':generateContent';

        $http = Http::withHeaders(['x-goog-api-key' => config('chatbot.api_key')])
            ->timeout(60)
            ->post($url, $cuerpo);

        if ($http->status() === 429) {
            throw new RuntimeException('Se alcanzó el límite gratuito de Gemini. Espera un minuto e intenta de nuevo.');
        }

        if ($http->failed()) {
            Log::error('Chatbot: error de Gemini', ['status' => $http->status(), 'body' => $http->body()]);
            $mensaje = $http->json('error.message') ?? 'sin detalle';
            throw new RuntimeException('Gemini respondió con error ' . $http->status() . ': ' . $mensaje);
        }

        return $http->json();
    }

    /** Convierte las herramientas (formato JSON Schema) al formato que pide Gemini. */
    private function declaracionesGemini(): array
    {
        return array_map(fn ($h) => [
            'name'        => $h['name'],
            'description' => $h['description'],
            'parameters'  => $this->esquemaGemini($h['input_schema']),
        ], $this->herramientas->definiciones());
    }

    /** Gemini usa tipos en MAYÚSCULAS (STRING, OBJECT...) y no acepta 'required' vacío. */
    private function esquemaGemini(array $esquema): array
    {
        $resultado = ['type' => strtoupper($esquema['type'] ?? 'string')];

        foreach (['description', 'enum'] as $campo) {
            if (isset($esquema[$campo])) {
                $resultado[$campo] = $esquema[$campo];
            }
        }

        if (!empty($esquema['properties'])) {
            $resultado['properties'] = array_map(fn ($p) => $this->esquemaGemini($p), $esquema['properties']);
        }

        if (!empty($esquema['required'])) {
            $resultado['required'] = array_values($esquema['required']);
        }

        return $resultado;
    }

    private function extraerTexto(array $partes): string
    {
        $texto = collect($partes)
            ->filter(fn ($p) => isset($p['text']) && empty($p['thought']))
            ->pluck('text')
            ->implode("\n");

        return trim($texto) ?: 'No tengo una respuesta para eso.';
    }

    private function promptSistema(): string
    {
        $ahora = now()->setTimezone('America/Mexico_City');
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

        $guiaArchivo = config('chatbot.guia');
        $guia = is_file($guiaArchivo) ? file_get_contents($guiaArchivo) : '';

        $rol = $this->user->isSuperAdmin() ? 'Super Administrador' : (DB::table('roles')->where('id', $this->user->rol_id)->value('nombre') ?? 'Sin rol');
        $nombre = trim(($this->user->nombre ?? '') . ' ' . ($this->user->apellido ?? ''));

        return <<<PROMPT
Eres el Asistente MediTrack, el chatbot interno del ERP MediTrack para clínicas privadas.
Ayudas al personal de la clínica de dos formas:
1. Explicas cómo usar el sistema, usando ÚNICAMENTE la guía de abajo. Si algo no está en la guía, di que no tienes esa información y sugiere contactar al administrador. No inventes pantallas, botones ni funciones.
2. Respondes preguntas sobre los datos de la clínica usando las funciones disponibles. Nunca inventes datos: si una función no devuelve resultados, dilo.

Reglas:
- Responde en español, breve y claro. Usa listas cuando haya varios registros.
- Solo tienes acceso de LECTURA. Si te piden crear, modificar, cancelar o eliminar algo, explica cómo hacerlo en el sistema según la guía.
- No tienes acceso a información clínica (diagnósticos, alergias, condiciones, medicamentos, recetas, signos vitales ni expedientes). Si la piden, indica que debe consultarse directamente en el expediente del paciente dentro del sistema.
- Si una función responde que el usuario no tiene permiso, díselo con amabilidad.
- Si no tienes una función para lo que te preguntan, es porque el rol del usuario no tiene acceso a ese módulo o el dato no está disponible.
- Solo respondes temas de MediTrack y de la clínica. Si preguntan otra cosa, indica amablemente que solo puedes ayudar con el sistema.
- Interpreta fechas relativas ("hoy", "mañana", "esta semana") con base en la fecha actual y envíalas a las funciones en formato YYYY-MM-DD.

Contexto:
- Fecha y hora actual: {$dias[$ahora->dayOfWeek]} {$ahora->format('Y-m-d H:i')} (hora del centro de México)
- Usuario: {$nombre}
- Rol: {$rol}

=== GUÍA DE USO DE MEDITRACK ===
{$guia}
PROMPT;
    }
}