<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Throwable;

class RolController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:roles,nombre|max:100',
            ], [
                'nombre.required' => 'El nombre del rol es obligatorio.',
                'nombre.unique'   => 'Este rol ya existe en el sistema.',
            ]);

            // Crear el rol insertando únicamente el campo 'nombre'
            $rol = Rol::create([
                'nombre' => trim($request->nombre),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente.',
                'rol'     => $rol
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    public function destroy($id)
{
    try {
        $rol = Rol::findOrFail($id);
        $rol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente.'
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar el rol: ' . $e->getMessage()
        ], 422);
    }
}
}