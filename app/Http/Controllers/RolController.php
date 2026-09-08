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
        $modulos = Modulo::all();

        return view('personal.roles', compact('roles', 'modulos'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:roles,nombre|max:100',
            ], [
                'nombre.required' => 'El nombre del rol es obligatorio.',
                'nombre.unique'   => 'Este rol ya se encuentra registrado.',
            ]);

            $rol = Rol::create([
                'nombre' => trim($request->nombre),
            ]);

            // Guardar casillas de verificación marcadas por cada módulo
            if ($request->has('permisos')) {
                foreach ($request->permisos as $modulo_id => $acciones) {
                    Permiso::create([
                        'rol_id'         => $rol->id,
                        'modulo_id'      => $modulo_id,
                        'puede_ver'      => isset($acciones['ver']),
                        'puede_crear'    => isset($acciones['crear']),
                        'puede_editar'   => isset($acciones['editar']),
                        'puede_eliminar' => isset($acciones['eliminar']),
                    ]);
                }
            }

            // Si la petición viene vía AJAX (Fetch/JSON), retornar respuesta JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rol y permisos creados exitosamente.',
                    'rol'     => $rol
                ], 200);
            }

            // Si es un submit convencional, hacer la redirección estándar
            return redirect()->route('roles.index')->with('success', 'Rol y permisos creados exitosamente.');

        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

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