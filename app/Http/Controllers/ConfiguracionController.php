<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    /**
     * Muestra la vista principal de configuración.
     */
    public function index()
    {
        // Simulamos o cargamos los datos actuales de la clínica
        $config = [
            'nombre_clinica' => 'MediTrack Central',
            'telefono'       => '55-1234-5678',
            'email'          => 'contacto@meditrack.com',
            'direccion'      => 'Av. Principal #123, Col. Centro',
            'moneda'         => 'MXN ($)',
            'duracion_cita'  => '30', // en minutos
            'notificaciones' => true,
        ];

        return view('configuracion.index', compact('config'));
    }

    /**
     * Actualiza la configuración de la clínica/sistema.
     */
    public function update(Request $request)
    {
        // Validamos los datos entrantes
        $request->validate([
            'nombre_clinica' => 'required|string|max:255',
            'telefono'       => 'nullable|string|max:20',
            'email'          => 'required|email|max:255',
            'direccion'      => 'nullable|string|max:255',
            'duracion_cita'  => 'required|integer|min:10|max:120',
        ]);

        // AQUÍ: Guardar en BD (o tabla de opciones/settings)
        // Por ahora retornamos una alerta de éxito
        return back()->with('success', 'La configuración ha sido actualizada correctamente.');
    }
}