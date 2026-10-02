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

    // gemini-flash-latest siempre apunta al modelo Flash más reciente (incluido en la capa gratuita)
    'model' => env('CHATBOT_MODEL', 'gemini-flash-latest'),

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
    | Nombre del módulo (tabla `modulos`) que se revisa en `permisos` para cada
    | grupo de consultas. Si el rol del usuario no tiene `puede_ver` en ese
    | módulo, el chatbot no puede consultar esos datos. Ajusta los nombres para
    | que coincidan exactamente con los que registres en la tabla `modulos`.
    */
    'modulos' => [
        'citas'        => 'Citas',
        'pacientes'    => 'Pacientes',
        'personal'     => 'Personal',
        'consultorios' => 'Consultorios',
        'inventario'   => 'Inventario',
        'facturacion'  => 'Facturación',
    ],
];