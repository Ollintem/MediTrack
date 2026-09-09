<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Throwable;

class RolController extends Controller
{
    public function index()
    {
        // Verificar si tiene permiso para ver Roles
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'ver')) {
            abort(403, 'No tienes permisos para acceder al módulo de Roles.');
        }

        $roles = Rol::with('permisos.modulo')->get();
        $modulos = Modulo::all(); 

        return view('personal.roles', compact('roles', 'modulos'));
    }

    public function store(Request $request)
    {
        // Validación de Permisos
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'crear')) {
            abort(403, 'No tienes permisos para crear roles.');
        }

        try {
            $request->validate([
                'nombre' => 'required|string|unique:roles,nombre|max:100',
            ]);

            $rol = Rol::create([
                'nombre' => trim($request->nombre),
            ]);

            if ($request->has('permisos')) {
                foreach ($request->permisos as $modulo_id => $acciones) {
                    Permiso::create([
                        'rol_id'       => $rol->id,
                        'modulo_id'    => $modulo_id,
                        'puede_ver'    => isset($acciones['ver']),
                        'puede_crear'  => isset($acciones['crear']),
                        'puede_editar' => isset($acciones['editar']),
                        'puede_eliminar' => isset($acciones['eliminar']),
                    ]);
                }
            }

            return redirect()->route('roles.index')->with('success', 'Rol y permisos creados correctamente.');

        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        // Validación de Permisos
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'editar')) {
            abort(403, 'No tienes permisos para editar roles.');
        }

        try {
            $request->validate([
                'nombre' => 'required|string|max:100|unique:roles,nombre,' . $id,
            ]);

            $rol = Rol::findOrFail($id);
            $rol->update([
                'nombre' => trim($request->nombre),
            ]);

            // Reemplazar permisos asignados
            Permiso::where('rol_id', $rol->id)->delete();

            if ($request->has('permisos')) {
                foreach ($request->permisos as $modulo_id => $acciones) {
                    Permiso::create([
                        'rol_id'       => $rol->id,
                        'modulo_id'    => $modulo_id,
                        'puede_ver'    => isset($acciones['ver']),
                        'puede_crear'  => isset($acciones['crear']),
                        'puede_editar' => isset($acciones['editar']),
                        'puede_eliminar' => isset($acciones['eliminar']),
                    ]);
                }
            }

            return redirect()->route('roles.index')->with('success', 'Rol y permisos actualizados correctamente.');

        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        // Validación de Permisos
        if (!auth()->check() || !auth()->user()->tienePermiso('Roles', 'eliminar')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar roles.'
            ], 403);
        }

        try {
            $rol = Rol::findOrFail($id);
            $rol->delete();

            return response()->json([
                'success' => true,
                'message' => 'El rol ha sido eliminado del sistema correctamente.'
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 422);
        }
    }
}