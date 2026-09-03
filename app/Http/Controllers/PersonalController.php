<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PersonalController extends Controller
{
    public function index()
    {
        // Trae únicamente los registros de la tabla 'personals'
        $personal = Personal::all();

        return view('personal.index', compact('personal'));
    }

    public function create()
    {
        return view('personal.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'          => 'required|string|max:80',
            'apellido'        => 'required|string|max:80',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|min:8',
            'rut'             => 'nullable|unique:personals,rut',
            'turno'           => 'required|in:Mañana,Tarde,Completo',
            'rol_profesional' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Crear credenciales de acceso (User)
            $usuario = User::create([
                'nombre'     => $request->nombre,
                'apellido'   => $request->apellido,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'clinica_id' => auth()->user()->clinica_id ?? null,
            ]);

            // 2. Crear ficha laboral en Personal
            Personal::create([
                'usuario_id'      => $usuario->id,
                'clinica_id'      => $usuario->clinica_id,
                'nombre_completo' => $request->nombre . ' ' . $request->apellido,
                'rut'             => $request->rut,
                'telefono'        => $request->telefono,
                'correo'          => $request->email,
                'rol_profesional' => $request->rol_profesional,
                'numero_registro' => $request->numero_registro,
                'turno'           => $request->turno,
                'estado'          => 'Activo',
            ]);
        });

        return redirect()->route('personal.index')->with('success', 'Personal dado de alta correctamente.');
    }
}