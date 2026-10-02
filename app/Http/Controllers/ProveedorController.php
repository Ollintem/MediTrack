<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Muestra el listado general de proveedores.
     */
    public function index()
    {
        $proveedores = Proveedor::orderBy('razon_social', 'asc')->get();
        return view('proveedores.index', compact('proveedores'));
    }

    /**
     * Almacena un nuevo proveedor en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'rut_rfc' => 'nullable|string|max:50',
            'categoria' => 'required|string|max:100',
            'contacto_nombre' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'dias_credito' => 'required|integer|min:0',
            'banco' => 'nullable|string|max:100',
            'cuenta_bancaria' => 'nullable|string|max:100',
            'estado' => 'required|in:activo,inactivo',
            'notas' => 'nullable|string',
        ]);

        Proveedor::create($validated);

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor registrado exitosamente.');
    }

    /**
     * Actualiza la información de un proveedor existente.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $validated = $request->validate([
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'rut_rfc' => 'nullable|string|max:50',
            'categoria' => 'required|string|max:100',
            'contacto_nombre' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'dias_credito' => 'required|integer|min:0',
            'banco' => 'nullable|string|max:100',
            'cuenta_bancaria' => 'nullable|string|max:100',
            'estado' => 'required|in:activo,inactivo',
            'notas' => 'nullable|string',
        ]);

        $proveedor->update($validated);

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    /**
     * Elimina a un proveedor del sistema mediante petición AJAX.
     */
    public function destroy(Proveedor $proveedor)
    {
        try {
            $proveedor->delete();
            return response()->json([
                'success' => true,
                'message' => 'El proveedor ha sido eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el proveedor debido a registros vinculados.'
            ], 422);
        }
    }
}