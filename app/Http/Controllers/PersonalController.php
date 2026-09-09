<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PersonalController extends Controller
{
    public function index()
    {
        $personal = Personal::with('usuario')->get();
        return view('personal.index', compact('personal'));
    }

    public function create()
    {
        $roles = Rol::all();
        $modulos = Modulo::all();
        return view('personal.create', compact('roles', 'modulos'));
    }

    public function store(Request $request)
    {
        // 1. Concatenar el usuario enviado con el dominio automático
        $emailCompleto = strtolower(trim($request->username)) . '@meditrack.com';
        $request->merge(['email' => $emailCompleto]);

        // 2. Validación de datos (se elimina la regla obligatoria para 'rol_profesional')
        $request->validate([
            'nombre'   => 'required|string|max:80',
            'apellido' => 'required|string|max:80',
            'username' => 'required|string|alpha_dash|max:50',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|size:8|confirmed',
            'rol_id'   => 'required|exists:roles,id',
            'rut'      => 'nullable|unique:personals,rut',
            'turno'    => 'required|in:Mañana,Tarde,Completo,Noche',
        ], [
            'password.size'     => 'La contraseña debe tener exactamente 8 caracteres.',
            'username.required' => 'Ingresa un nombre de usuario para el correo.',
            'email.unique'      => 'Este usuario de correo ya está registrado.',
            'rol_id.required'   => 'Debes seleccionar un rol para el sistema.',
        ]);

        // 3. Buscar el nombre del rol a partir del ID seleccionado
        $rol = Rol::findOrFail($request->rol_id);
        $nombreRol = $rol->nombre;
        $nombreCompleto = trim($request->nombre . ' ' . $request->apellido);

        DB::transaction(function () use ($request, $emailCompleto, $nombreCompleto, $nombreRol) {
            $clinicaId = DB::table('clinicas')->value('id') ?? 1;

            // 4. Crear credenciales del usuario asociando el rol_id
            $usuario = User::create([
                'nombre'     => $request->nombre,
                'apellido'   => $request->apellido,
                'email'      => $emailCompleto,
                'rol_id'     => $request->rol_id,
                'password'   => Hash::make($request->password),
                'clinica_id' => $clinicaId,
            ]);

            // 5. Crear registro de personal usando el nombre del rol como especialidad principal
            $personal = Personal::create([
                'user_id'                => $usuario->id,
                'clinica_id'             => $clinicaId,
                'nombre_completo'        => $nombreCompleto,
                'rut'                    => $request->rut,
                'telefono'               => $request->telefono,
                'numero_registro'        => $request->numero_registro,
                'especialidad_principal' => $nombreRol,
                'turno'                  => $request->turno,
                'estado'                 => 'Activo',
            ]);

            // 6. Registrar en la bitácora
            Bitacora::registrar(
                'Personal',
                'Crear',
                "Se dio de alta al nuevo miembro del personal: {$nombreCompleto}",
                [
                    'personal_id' => $personal->id,
                    'user_id'     => $usuario->id,
                    'correo'      => $emailCompleto,
                    'rol'         => $nombreRol,
                    'turno'       => $request->turno,
                ]
            );
        });

        return redirect()->route('personal.index')->with('success', 'Personal dado de alta correctamente.');
    }

    public function edit($id)
    {
        $personal = Personal::with('usuario')->findOrFail($id);
        $roles = Rol::all();
        $modulos = Modulo::all();

        return view('personal.edit', compact('personal', 'roles', 'modulos'));
    }

    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);
        $usuario = User::findOrFail($personal->user_id);

        $emailCompleto = strtolower(trim($request->username)) . '@meditrack.com';
        $request->merge(['email' => $emailCompleto]);

        $request->validate([
            'nombre_completo' => 'required|string|max:150',
            'username'        => 'required|string|alpha_dash|max:50',
            'email'           => 'required|email|unique:users,email,' . $usuario->id,
            'rol_id'          => 'required|exists:roles,id',
            'password'        => 'nullable|size:8|confirmed',
        ], [
            'password.size'   => 'La contraseña debe tener exactamente 8 caracteres.',
            'email.unique'    => 'Este usuario de correo ya está en uso.',
            'rol_id.required' => 'Debes seleccionar un rol para el sistema.',
        ]);

        $rol = Rol::findOrFail($request->rol_id);
        $nombreRol = $rol->nombre;

        DB::transaction(function () use ($request, $personal, $usuario, $emailCompleto, $nombreRol) {
            // Actualizar usuario
            $usuario->email  = $emailCompleto;
            $usuario->rol_id = $request->rol_id;

            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->password);
            }
            $usuario->save();

            // Actualizar ficha de personal
            $personal->update([
                'nombre_completo'        => $request->nombre_completo,
                'telefono'               => $request->telefono,
                'especialidad_principal' => $nombreRol,
            ]);

            // Registrar en la bitácora
            Bitacora::registrar(
                'Personal',
                'Editar',
                "Se actualizó la información del personal: {$request->nombre_completo}",
                [
                    'personal_id'       => $personal->id,
                    'correo'            => $emailCompleto,
                    'rol'               => $nombreRol,
                    'password_cambiado' => $request->filled('password')
                ]
            );
        });

        return redirect()->route('personal.index')->with('success', 'Personal actualizado correctamente.');
    }

    public function destroy($id)
    {
        $personal = Personal::where('id', $id)->orWhere('user_id', $id)->first();

        if ($personal) {
            $userId = $personal->user_id;
            $personalId = $personal->id;
            $nombreEliminado = $personal->nombre_completo;

            DB::transaction(function () use ($personalId, $userId) {
                DB::table('personals')->where('id', $personalId)->delete();

                if ($userId) {
                    DB::table('users')->where('id', $userId)->delete();
                }
            });

            Bitacora::registrar(
                'Personal',
                'Eliminar',
                "Se eliminó del sistema al miembro del personal: {$nombreEliminado}",
                [
                    'personal_id' => $personalId,
                    'user_id'     => $userId,
                    'nombre'      => $nombreEliminado
                ]
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'El miembro del personal ha sido eliminado correctamente.'
                ], 200);
            }

            return redirect()->route('personal.index')->with('success', 'Personal y usuario eliminados correctamente.');
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro a eliminar.'
            ], 404);
        }

        return redirect()->route('personal.index')->with('error', 'No se encontró el registro a eliminar.');
    }
}