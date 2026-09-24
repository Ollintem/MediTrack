<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Clinica;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    /**
     * Muestra la vista principal de configuración.
     */
    public function index()
    {
        // 1. Cargamos el primer registro existente de la tabla 'clinicas'
        $clinica = Clinica::first();

        // 2. Cargamos las configuraciones clave-valor de la tabla 'configuraciones'
        $config = Configuracion::pluck('valor', 'clave')->toArray();

        // Envia ambos objetos/arrays a la vista de configuración
        return view('configuracion.index', compact('clinica', 'config'));
    }

    /**
     * Actualiza la configuración de la clínica y del sistema.
     */
    public function update(Request $request)
    {
        // Validación de entradas
        $request->validate([
            'nombre'                  => 'nullable|string|max:255',
            'rut_empresa'             => 'nullable|string|max:255',
            'direccion'              => 'nullable|string|max:255',
            'telefono'               => 'nullable|digits:10',
            'email'                   => 'nullable|email|max:255',
            'duracion_cita'           => 'nullable|integer',
            'aviso_privacidad'        => 'nullable|string',
        ], [
            'telefono.digits' => 'El teléfono principal debe contener exactamente 10 dígitos numéricos.',
            'email.email'     => 'El formato del correo electrónico no es válido.',
        ]);

        // ==========================================
        // 1. ACTUALIZAR O CREAR EN TABLA 'CLINICAS'
        // ==========================================
        $clinica = Clinica::first() ?? new Clinica();

        // Si se envió algún campo perteneciente a la tabla 'clinicas'
        if ($request->hasAny(['nombre', 'rut_empresa', 'direccion', 'telefono'])) {
            $clinica->nombre                  = $request->input('nombre', $clinica->nombre);
            $clinica->rut_empresa             = $request->input('rut_empresa', $clinica->rut_empresa);
            $clinica->direccion              = $request->input('direccion', $clinica->direccion);
            $clinica->telefono               = $request->input('telefono', $clinica->telefono);
            
            // Manejo de checkboxes (1 si está presente, 0 si no)
            if ($request->has('confirmacion_auto_citas_submitted')) {
                $clinica->confirmacion_auto_citas = $request->has('confirmacion_auto_citas') ? 1 : 0;
            }
            if ($request->has('recordatorio_pago_email_submitted')) {
                $clinica->recordatorio_pago_email = $request->has('recordatorio_pago_email') ? 1 : 0;
            }

            $clinica->save();
        }

        // ==========================================
        // 2. ACTUALIZAR TABLA 'CONFIGURACIONES' (CLAVE-VALOR)
        // ==========================================
        $datos = $request->except([
            '_token', 
            '_method', 
            'confirmacion_auto_citas_submitted', 
            'recordatorio_pago_email_submitted'
        ]);

        foreach ($datos as $clave => $valor) {
            Configuracion::updateOrCreate(
                ['clave' => $clave],
                ['valor' => is_string($valor) ? mb_strtoupper($valor) : $valor]
            );
        }

        return back()->with('success', 'La configuración de la clínica ha sido actualizada correctamente.');
    }
}