<?php

/*
|--------------------------------------------------------------------------
| Configuración del Asistente MediTrack (chatbot)
|--------------------------------------------------------------------------
*/

return [

    // API key de Google Gemini (gratis en https://aistudio.google.com). Se define en el .env
    'api_key' => env('GEMINI_API_KEY'),

    'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models',

    // Flash-Lite es el más rápido y con menos demanda; Flash queda como respaldo
    'model' => env('CHATBOT_MODEL', 'gemini-flash-lite-latest'),

    // Si el modelo principal está saturado (error 503) se intenta con estos, en orden.
    // Puedes poner varios separados por coma en el .env: CHATBOT_MODELOS_RESPALDO=modelo1,modelo2
    'modelos_respaldo' => array_filter(array_map('trim', explode(',', env('CHATBOT_MODELOS_RESPALDO', 'gemini-flash-latest')))),

    'max_tokens' => (int) env('CHATBOT_MAX_TOKENS', 1024),

    // Cuántos mensajes previos de la conversación se reenvían al modelo
    'max_historial' => 20,

    // Límite de consultas encadenadas a la BD por cada pregunta (evita bucles)
    'max_iteraciones_herramientas' => 5,

    // Máximo de registros que devuelve cada consulta
    'max_resultados' => 50,

    // Si es true, el chatbot solo ve el CÓDIGO del paciente y nunca su nombre
    'ocultar_nombres_pacientes' => (bool) env('CHATBOT_OCULTAR_NOMBRES', false),

    // Archivo con la guía de uso del sistema (lo que el bot "sabe" de MediTrack)
    'guia' => resource_path('chatbot/guia_meditrack.md'),

    /*
    | Módulo (tabla `modulos`) que se revisa con User::tienePermiso() para cada
    | grupo de consultas. Son los mismos nombres que usan tus rutas con
    | el middleware 'permiso:...'.
    */
    'modulos' => [
        'citas'        => 'Citas',
        'pacientes'    => 'Pacientes',
        'personal'     => 'Personal',
        'consultorios' => 'Consultorios',
        'inventario'   => 'Inventario',
        'caja'         => 'Caja Central',
    ],
];