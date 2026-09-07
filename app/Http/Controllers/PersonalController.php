<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Rol;

class PersonalController extends Controller
{
    public function index()
    {
        // Carga la relación 'usuario' para obtener el correo electrónico
        $personal = Personal::with('usuario')->get();
        return view('personal.index', compact('personal'));
    }

    public function create()
{
    // Cargar los roles existentes
    $roles = Rol::all();
    return view('personal.create', compact('roles'));
}

    public function store(Request $request)
    {
        // 1. Concatenar el usuario enviado con el dominio automático de MediTrack
        $emailCompleto = strtolower(trim($request->username)) . '@meditrack.com';
        $request->merge(['email' => $emailCompleto]);

        // 2. Validación de datos recibidos del formulario
        $request->validate([
            'nombre'          => 'required|string|max:80',
            'apellido'        => 'required|string|max:80',
            'username'        => 'required|string|alpha_dash|max:50',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|size:8|confirmed', // Exige exactamente 8 caracteres
            'rol_profesional' => 'required|string|max:100',  // Seleccionado desde el select/dropdown
            'rut'             => 'nullable|unique:personals,rut',
            'turno'           => 'required|in:Mañana,Tarde,Completo,Noche',
        ], [
            'password.size'     => 'La contraseña debe tener exactamente 8 caracteres.',
            'username.required' => 'Ingresa un nombre de usuario para el correo.',
            'email.unique'      => 'Este usuario de correo ya está registrado.',
        ]);

        DB::transaction(function () use ($request, $emailCompleto) {
            // Busca dinámicamente el ID real de la clínica registrada en la BD (default 1)
            $clinicaId = DB::table('clinicas')->value('id') ?? 1;

            // 3. Crear credenciales del usuario con el correo @meditrack.com
            $usuario = User::create([
                'nombre'     => $request->nombre,
                'apellido'   => $request->apellido,
                'email'      => $emailCompleto,
                'password'   => Hash::make($request->password),
                'clinica_id' => $clinicaId,
            ]);

            // 4. Crear registro de personal vinculado
            Personal::create([
                'user_id'                => $usuario->id,
                'clinica_id'             => $clinicaId,
                'nombre_completo'        => trim($request->nombre . ' ' . $request->apellido),
                'rut'                    => $request->rut,
                'telefono'               => $request->telefono,
                'numero_registro'        => $request->numero_registro,
                'especialidad_principal' => $request->rol_profesional,
                'turno'                  => $request->turno,
                'estado'                 => 'Activo',
            ]);
        });

        return redirect()->route('personal.index')->with('success', 'Personal dado de alta correctamente.');
    }

    public function edit($id)
{
    $personal = Personal::with('usuario')->findOrFail($id);
    $roles = Rol::all();
    return view('personal.edit', compact('personal', 'roles'));
}

  public function update(Request $request, $id)
{
    $personal = Personal::findOrFail($id);
    $usuario = User::findOrFail($personal->user_id);

    // Concatenar el usuario enviado con el dominio automático de MediTrack
    $emailCompleto = strtolower(trim($request->username)) . '@meditrack.com';
    $request->merge(['email' => $emailCompleto]);

    $request->validate([
        'nombre_completo' => 'required|string|max:150',
        'username'        => 'required|string|alpha_dash|max:50',
        'email'           => 'required|email|unique:users,email,' . $usuario->id,
        'password'        => 'nullable|size:8|confirmed',
        'rol_profesional' => 'required|string|max:100',
    ], [
        'password.size'   => 'La contraseña debe tener exactamente 8 caracteres.',
        'email.unique'    => 'Este usuario de correo ya está en uso.',
    ]);

    DB::transaction(function () use ($request, $personal, $usuario, $emailCompleto) {
        // Actualizar datos del usuario de acceso
        $usuario->email = $emailCompleto;
        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }
        $usuario->save();

        // Actualizar ficha de personal
        $personal->update([
            'nombre_completo'        => $request->nombre_completo,
            'telefono'               => $request->telefono,
            'especialidad_principal' => $request->rol_profesional,
        ]);
    });

    return redirect()->route('personal.index')->with('success', 'Personal actualizado correctamente.');
}

    public function destroy($id)
    {
        // 1. Busca el registro en 'personals' por su ID o por user_id
        $personal = Personal::where('id', $id)->orWhere('user_id', $id)->first();

        if ($personal) {
            $userId = $personal->user_id;
            $personalId = $personal->id;

            // 2. Eliminar físicamente de la tabla 'personals'
            DB::table('personals')->where('id', $personalId)->delete();

            // 3. Eliminar físicamente el usuario correspondiente de la tabla 'users'
            if ($userId) {
                DB::table('users')->where('id', $userId)->delete();
            }

            return redirect()->route('personal.index')->with('success', 'Personal y usuario eliminados correctamente.');
        }

        return redirect()->route('personal.index')->with('error', 'No se encontró el registro a eliminar.');
    }
}