<?php

namespace App\Http\Controllers;

use App\Models\ProductoInventario;
use App\Models\CategoriaInventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventarioController extends Controller
{
    public function index()
    {
        $productos = ProductoInventario::with('categoria')->latest()->get();
        $categorias = CategoriaInventario::all();

        // Métricas superiores para las tarjetas
        $totalProductos = $productos->count();
        $stockBajo      = $productos->filter(fn($p) => $p->stock_disponible <= $p->stock_minimo && $p->stock_disponible > 0)->count();
        $agotados       = $productos->filter(fn($p) => $p->stock_disponible == 0)->count();

        return view('inventario.index', compact(
            'productos', 
            'categorias', 
            'totalProductos', 
            'stockBajo', 
            'agotados'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo'        => 'required|string|max:50|unique:productos_inventario,codigo',
            'nombre'        => 'required|string|max:200',
            'categoria_id'  => 'required|exists:categorias_inventario,id',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_actual'  => 'required|integer|min:0',
            'stock_minimo'  => 'required|integer|min:0',
        ]);

        $data = $request->all();
        $data['clinica_id'] = Auth::user()->clinica_id ?? 1; // Asigna clínica actual o id por defecto

        $producto = ProductoInventario::create($data);

        // Si ingresa con stock inicial, registra el movimiento de entrada
        if ($request->stock_actual > 0) {
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'personal_id' => Auth::id() ?? 1,
                'cantidad'    => $request->stock_actual,
                'tipo'        => 'Entrada',
                'motivo'      => 'Registro de inventario inicial'
            ]);
        }

        return redirect()->route('inventario.index')->with('success', 'Medicamento registrado exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $producto = ProductoInventario::findOrFail($id);

        $request->validate([
            'codigo'        => 'required|string|max:50|unique:productos_inventario,codigo,'.$id,
            'nombre'        => 'required|string|max:200',
            'categoria_id'  => 'required|exists:categorias_inventario,id',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'required|integer|min:0',
        ]);

        $producto->update($request->all());

        return redirect()->route('inventario.index')->with('success', 'Medicamento actualizado correctamente.');
    }

    public function agregarStock(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
            'motivo'   => 'nullable|string|max:255'
        ]);

        $producto = ProductoInventario::findOrFail($id);
        $producto->increment('stock_actual', $request->cantidad);

        MovimientoInventario::create([
            'producto_id' => $producto->id,
            'personal_id' => Auth::id() ?? 1,
            'cantidad'    => $request->cantidad,
            'tipo'        => 'Entrada',
            'motivo'      => $request->motivo ?? 'Reabastecimiento de mercancía'
        ]);

        return redirect()->route('inventario.index')->with('success', 'Stock actualizado correctamente.');
    }

    public function destroy($id)
    {
        $producto = ProductoInventario::findOrFail($id);
        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'MEDICAMENTO ELIMINADO CORRECTAMENTE DEL INVENTARIO.'
        ]);
    }
}