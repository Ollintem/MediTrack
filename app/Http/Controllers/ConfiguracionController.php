<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Clinica;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        // Envía ambos objetos/arrays a la vista de configuración
        return view('configuracion.index', compact('clinica', 'config'));
    }

    /**
     * Actualiza la configuración general de la clínica y del sistema (Solo Administradores).
     */
    public function update(Request $request)
    {
        // Se obtiene antes de validar: la regla unique necesita su id y,
        // si aún no existe ninguna clínica, los datos básicos son obligatorios.
        $clinica = Clinica::first() ?? new Clinica();
        $nueva   = ! $clinica->exists;

        // Validación de entradas (tamaños alineados con las columnas de 'clinicas')
        $request->validate([
            'nombre'           => [Rule::requiredIf($nueva), 'nullable', 'string', 'max:150'],
            'rut_empresa'      => [Rule::requiredIf($nueva), 'nullable', 'string', 'max:20',
                                   Rule::unique('clinicas', 'rut_empresa')->ignore($clinica->id)],
            'direccion'        => [Rule::requiredIf($nueva), 'nullable', 'string', 'max:255'],
            'telefono'         => [Rule::requiredIf($nueva), 'nullable', 'digits:10'],
            'email_clinica'    => 'nullable|email|max:150',
            'duracion_cita'    => 'nullable|integer',
            'aviso_privacidad' => 'nullable|string',
        ], [
            'telefono.digits'      => 'El teléfono principal debe contener exactamente 10 dígitos numéricos.',
            'email_clinica.email'  => 'El formato del correo electrónico de la clínica no es válido.',
            'email_clinica.max'    => 'El correo de la clínica no puede exceder 150 caracteres.',
            'nombre.max'           => 'El nombre de la clínica no puede exceder 150 caracteres.',
            'rut_empresa.max'      => 'El RFC no puede exceder 20 caracteres.',
            'rut_empresa.unique'   => 'Ese RFC ya está registrado en otra clínica.',
            'direccion.max'        => 'La dirección no puede exceder 255 caracteres.',
            'nombre.required'      => 'El nombre de la clínica es obligatorio.',
            'rut_empresa.required' => 'El RFC de la clínica es obligatorio.',
            'direccion.required'   => 'La dirección de la clínica es obligatoria.',
            'telefono.required'    => 'El teléfono de la clínica es obligatorio.',
        ]);

        // ==========================================
        // 1. ACTUALIZAR O CREAR EN TABLA 'CLINICAS'
        // ==========================================
        if ($request->hasAny(['nombre', 'rut_empresa', 'direccion', 'telefono', 'email_clinica'])) {
            // Un campo vacío conserva el valor actual (las columnas son NOT NULL)
            foreach (['nombre', 'rut_empresa', 'direccion', 'telefono'] as $campo) {
                if ($request->filled($campo)) {
                    $clinica->{$campo} = $request->input($campo);
                }
            }
            
            if ($request->has('email_clinica')) {
                $clinica->email   = $request->input('email_clinica');
            }

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
        // Solo se guardan claves conocidas: antes se guardaba cualquier campo que llegara en la petición.
        $datos = [];

        if ($request->has('aviso_privacidad')) {
            // Texto legal: se respeta tal como se escribió (sin pasarlo a mayúsculas)
            $datos['aviso_privacidad'] = $request->input('aviso_privacidad');
        }

        if ($request->has('duracion_cita')) {
            $datos['duracion_cita'] = $request->filled('duracion_cita') ? (string) (int) $request->input('duracion_cita') : null;
        }

        // Casilla: si el formulario principal se envió sin ella, significa que se desmarcó
        if ($request->has('alertas_bitacora')) {
            $datos['alertas_bitacora'] = '1';
        } elseif ($request->has('nombre')) {
            $datos['alertas_bitacora'] = '0';
        }

        foreach ($datos as $clave => $valor) {
            Configuracion::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        }

        return back()->with('success', 'La configuración de la clínica ha sido actualizada correctamente.');
    }

    /**
     * Actualiza el correo electrónico y/o contraseña de cualquier usuario en sesión.
     */
    public function actualizarCuenta(Request $request)
    {
        // Reobtenemos la instancia directamente de la BD usando la ID autenticada
        /** @var \App\Models\User $user */
        $user = User::findOrFail(Auth::id());

        // 1. Validaciones
        $request->validate([
            'email'            => 'required|string|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required|string',
            'password'         => 'nullable|string|min:8|confirmed',
        ], [
            'email.required'            => 'El correo electrónico o usuario de acceso es obligatorio.',
            'email.unique'              => 'El correo electrónico ingresado ya pertenece a otro usuario.',
            'current_password.required' => 'Debes ingresar tu contraseña actual para autorizar los cambios.',
            'password.min'              => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'        => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        // 2. Verificar la contraseña actual
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual ingresada es incorrecta.'],
            ]);
        }

        $cambios = false;

        // 3. Cambiar correo/usuario si es diferente al guardado
        if (trim($request->email) !== $user->email) {
            $user->email = trim($request->email);
            $cambios = true;
        }

        // 4. Cambiar contraseña solo si se escribió una nueva
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $cambios = true;
        }

        if (!$cambios) {
            return back()->with('info', 'No se realizaron modificaciones en la cuenta.');
        }

        // 5. Guardar explícitamente en la base de datos
        $user->save();

        return back()->with('success', 'Tus credenciales de acceso se actualizaron correctamente.');
    }
}