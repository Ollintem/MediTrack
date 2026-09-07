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
        $roles = Rol::with('permisos.modulo')->get();
        $modulos = Modulo::all(); // Se envían los módulos para los checkboxes

        return view('personal.roles', compact('roles', 'modulos'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:roles,nombre|max:100',
            ]);

            $rol = Rol::create([
                'nombre' => trim($request->nombre),
            ]);

            // Guardar casillas de verificación marcadas por cada módulo
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

            return redirect()->route('roles.index')->with('success', 'Rol y permisos creados exitosamente.');

        } catch (Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
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