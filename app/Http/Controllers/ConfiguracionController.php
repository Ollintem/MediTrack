<?php

namespace App\Http\Controllers;

use App\Models\Configuracion; // 1. IMPORTANTE: Importamos el modelo de la BD
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    /**
     * Muestra la vista principal de configuración.
     */
    public function index()
    {
        // 2. Cargamos las configuraciones reales de la base de datos como un array ['clave' => 'valor']
        $config = Configuracion::pluck('valor', 'clave')->toArray();

        return view('configuracion.index', compact('config'));
    }

    /**
     * Actualiza la configuración de la clínica/sistema.
     */
    public function update(Request $request)
    {
        // Todos los campos se definen como nullable para permitir guardados parciales
        $request->validate([
            'nombre_clinica'  => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'telefono'        => 'nullable|digits:10',
            'direccion'       => 'nullable|string|max:255',
            'duracion_cita'   => 'nullable|integer',
            'aviso_privacidad'=> 'nullable|string',
        ], [
            'telefono.digits' => 'El teléfono principal debe contener exactamente 10 dígitos numéricos.',
            'email.email'     => 'El formato del correo electrónico no es válido.',
        ]);

        // Extraemos solo los campos del formulario
        $datos = $request->except('_token', '_method');

        foreach ($datos as $clave => $valor) {
            Configuracion::updateOrCreate(
                ['clave' => $clave],
                ['valor' => is_string($valor) ? mb_strtoupper($valor) : $valor]
            );
        }

        return back()->with('success', 'La configuración ha sido actualizada correctamente.');
    }
}