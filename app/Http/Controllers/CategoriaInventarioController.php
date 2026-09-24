<?php

namespace App\Http\Controllers;

use App\Models\CategoriaInventario;
use Illuminate\Http\Request;

class CategoriaInventarioController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_inventario,nombre',
        ]);

        $categoria = CategoriaInventario::create([
            'nombre' => trim($request->nombre),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoría agregada correctamente.',
            'categoria' => $categoria
        ]);
    }

    public function destroy($id)
    {
        $categoria = CategoriaInventario::findOrFail($id);
        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente.'
        ]);
    }
}