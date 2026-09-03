<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Permiso;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::with('permisos.modulo')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $modulos = Modulo::all();
        return view('roles.create', compact('modulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|unique:roles,nombre|max:50',
            'descripcion' => 'nullable|max:255',
        ]);

        $rol = Rol::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        if ($request->has('permisos')) {
            foreach ($request->permisos as $modulo_id => $acciones) {
                Permiso::create([
                    'rol_id' => $rol->id,
                    'modulo_id' => $modulo_id,
                    'puede_ver' => isset($acciones['ver']),
                    'puede_crear' => isset($acciones['crear']),
                    'puede_editar' => isset($acciones['editar']),
                    'puede_eliminar' => isset($acciones['eliminar']),
                ]);
            }
        }

        return redirect()->route('roles.index')->with('success', 'Rol y permisos configurados correctamente.');
    }
}

