<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PersonalController extends Controller
{
    /**
     * Muestra la lista/directorio del personal.
     */
    public function index(Request $request)
    {
        $query = Personal::with('user');

        // Búsqueda por nombre, teléfono o RUT si se envía término
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_completo', 'LIKE', "%{$buscar}%")
                  ->orWhere('telefono', 'LIKE', "%{$buscar}%")
                  ->orWhere('rut', 'LIKE', "%{$buscar}%")
                  ->orWhere('especialidad_principal', 'LIKE', "%{$buscar}%");
            });
        }

        // Filtro por estatus
        if ($request->filled('estatus')) {
            $query->where('estado', $request->estatus);
        }

        $personal = $query->orderBy('nombre_completo')->paginate(12)->withQueryString();

        return view('personal.index', compact('personal'));
    }

    /**
     * Muestra el formulario para dar de alta nuevo personal.
     */
    public function create()
    {
        $roles = Rol::orderBy('nombre')->get();

        return view('personal.create', compact('roles'));
    }

    /**
     * Guarda el registro del nuevo personal y su usuario asociado (si aplica).
     */
    public function store(Request $request)
    {
        // 1. Validaciones
        $reglas = [
            'nombre'           => 'required|string|max:80',
            'apellido'         => 'required|string|max:80',
            'genero'           => 'nullable|in:Masculino,Femenino,Otro',
            'fecha_nacimiento' => 'nullable|date',
            'fecha_ingreso'    => 'nullable|date',
            'rut'              => 'nullable|string|max:30',
            'telefono'         => 'nullable|string|size:10',
            'especialidad'     => 'nullable|string|max:100',
            'direccion'        => 'nullable|string|max:150',
            'colonia'          => 'nullable|string|max:100',
            'municipio'        => 'nullable|string|max:100',
            'estado'           => 'nullable|string|max:100',
            'codigo_postal'    => 'nullable|string|max:5',
            'rol_id'           => 'required|exists:roles,id',
            'turno'            => 'required|in:Completo,Mañana,Tarde,Noche',
            'estatus'          => 'required|in:Activo,Inactivo',
            'requiere_acceso'  => 'required|boolean',
        ];

        if ($request->boolean('requiere_acceso')) {
            $dominio = 'meditrack.com';
            $request->merge(['email' => strtolower($request->username) . '@' . $dominio]);

            $reglas['username'] = 'required|string|min:3|max:40|regex:/^[a-z0-9._-]+$/';
            $reglas['email']    = 'required|email|unique:users,email';
            $reglas['password'] = 'required|string|min:8|max:8|confirmed';
        }

        $request->validate($reglas);

        try {
            DB::transaction(function () use ($request) {
                $userId = null;
                $nombreCompleto = trim(mb_strtoupper($request->nombre) . ' ' . mb_strtoupper($request->apellido));

                // 2. Crear usuario si se requiere acceso al sistema
                if ($request->boolean('requiere_acceso')) {
                    $usuario = User::create([
                        'name'     => $nombreCompleto,
                        'email'    => $request->email,
                        'password' => Hash::make($request->password),
                        'rol_id'   => $request->rol_id,
                    ]);
                    $userId = $usuario->id;
                }

                // 3. Crear el expediente del personal
                Personal::create([
                    'user_id'                => $userId,
                    'clinica_id'             => Auth::user()->clinica_id ?? 1,
                    'nombre_completo'        => $nombreCompleto,
                    'genero'                 => $request->genero,
                    'fecha_nacimiento'       => $request->fecha_nacimiento,
                    'fecha_ingreso'          => $request->fecha_ingreso,
                    'rut'                    => $request->rut ? mb_strtoupper(str_replace(' ', '', $request->rut)) : null,
                    'telefono'               => $request->telefono,
                    'numero_registro'        => $request->rut ? mb_strtoupper($request->rut) : null,
                    'direccion'              => $request->direccion ? mb_strtoupper($request->direccion) : null,
                    'especialidad_principal' => $request->especialidad ? mb_strtoupper($request->especialidad) : null,
                    'turno'                  => $request->turno,
                    'estado'                 => $request->estatus,
                ]);
            });

            return redirect()->route('personal.index')
                ->with('success', 'El miembro del personal se registró correctamente.');

        } catch (\Exception $e) {
            report($e);
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Ocurrió un error al intentar registrar el personal: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra la ficha detallada de un miembro del personal.
     */
    public function show($id)
    {
        $personal = Personal::with('user')->findOrFail($id);

        return view('personal.show', compact('personal'));
    }

    /**
     * Muestra el formulario de edición de personal.
     */
    public function edit($id)
    {
        $personal = Personal::with('user')->findOrFail($id);
        $roles = Rol::orderBy('nombre')->get();

        return view('personal.edit', compact('personal', 'roles'));
    }

    /**
     * Actualiza el registro de personal.
     */
    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);

        $request->validate([
            'nombre_completo' => 'required|string|max:160',
            'telefono'        => 'nullable|string|size:10',
            'turno'           => 'required|in:Completo,Mañana,Tarde,Noche',
            'estado'          => 'required|in:Activo,Inactivo',
        ]);

        $personal->update([
            'nombre_completo' => mb_strtoupper($request->nombre_completo),
            'telefono'        => $request->telefono,
            'turno'           => $request->turno,
            'estado'          => $request->estado,
        ]);

        return redirect()->route('personal.index')
            ->with('success', 'Información del personal actualizada correctamente.');
    }

    /**
     * Elimina/Desactiva un registro de personal.
     */
    public function destroy($id)
    {
        $personal = Personal::findOrFail($id);

        // Desactivación lógica recomendada o eliminación física según modelo de negocio
        $personal->update(['estado' => 'Inactivo']);

        return redirect()->route('personal.index')
            ->with('success', 'El registro de personal ha sido desactivado.');
    }
}