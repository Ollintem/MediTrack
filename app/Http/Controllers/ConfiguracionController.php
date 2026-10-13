<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Clinica;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        // Validación de entradas
        $request->validate([
            'nombre'           => 'nullable|string|max:255',
            'rut_empresa'      => 'nullable|string|max:255',
            'direccion'        => 'nullable|string|max:255',
            'telefono'         => 'nullable|digits:10',
            'email_clinica'    => 'nullable|email|max:255',
            'duracion_cita'    => 'nullable|integer',
            'aviso_privacidad' => 'nullable|string',
        ], [
            'telefono.digits'    => 'El teléfono principal debe contener exactamente 10 dígitos numéricos.',
            'email_clinica.email' => 'El formato del correo electrónico de la clínica no es válido.',
        ]);

        // ==========================================
        // 1. ACTUALIZAR O CREAR EN TABLA 'CLINICAS'
        // ==========================================
        $clinica = Clinica::first() ?? new Clinica();

        if ($request->hasAny(['nombre', 'rut_empresa', 'direccion', 'telefono', 'email_clinica'])) {
            $clinica->nombre      = $request->input('nombre', $clinica->nombre);
            $clinica->rut_empresa = $request->input('rut_empresa', $clinica->rut_empresa);
            $clinica->direccion   = $request->input('direccion', $clinica->direccion);
            $clinica->telefono    = $request->input('telefono', $clinica->telefono);
            
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
        $datos = $request->except([
            '_token', 
            '_method', 
            'confirmacion_auto_citas_submitted', 
            'recordatorio_pago_email_submitted',
            'email_clinica'
        ]);

        foreach ($datos as $clave => $valor) {
            Configuracion::updateOrCreate(
                ['clave' => $clave],
                ['valor' => is_string($valor) ? mb_strtoupper($valor) : $valor]
            );
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