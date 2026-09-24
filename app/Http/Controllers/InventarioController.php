<?php

namespace App\Http\Controllers;

use App\Models\ProductoInventario;
use App\Models\CategoriaInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * Muestra el catálogo principal de inventario.
     */
    public function index()
    {
        $productos = ProductoInventario::with('categoria')->orderBy('created_at', 'desc')->get();
        $categorias = CategoriaInventario::orderBy('nombre', 'asc')->get();

        // Métricas para tarjetas
        $totalProductos = $productos->count();
        $stockBajo = $productos->filter(fn($p) => $p->stock_disponible <= $p->stock_minimo && $p->stock_disponible > 0)->count();
        $agotados = $productos->filter(fn($p) => $p->stock_disponible <= 0)->count();

        return view('inventario.index', compact('productos', 'categorias', 'totalProductos', 'stockBajo', 'agotados'));
    }

    /**
     * Modal 1: Guarda un nuevo medicamento / producto.
     */
    public function store(Request $request)
    {
        $codigo = trim($request->codigo);

        // 1. Verificación manual de código duplicado
        $existeProducto = \App\Models\ProductoInventario::where('codigo', $codigo)->exists();
        if ($existeProducto) {
            return redirect()->back()
                ->withInput()
                ->with('error', '¡El código de barras ('.$codigo.') ya está registrado con otro medicamento!');
        }

        // 2. Validaciones generales
        $request->validate([
            'codigo'        => 'required|digits:13',
            'categoria_id'  => 'required|exists:categorias_inventario,id',
            'nombre'        => 'required|string|max:200',
            'precio_compra' => 'required|numeric|min:0.01',
            'precio_venta'  => 'required|numeric|min:0.01',
            'stock_actual'  => 'required|integer|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'descripcion'   => 'nullable|string',
        ], [
            'codigo.digits'          => 'El código de barras debe tener exactamente 13 dígitos numéricos.',
            'categoria_id.required'  => 'Favor de seleccionar una categoría.',
            'nombre.required'        => 'Favor de ingresar el nombre del medicamento.',
            'precio_compra.required' => 'Favor de ingresar el precio de compra.',
            'precio_venta.required'  => 'Favor de ingresar el precio de venta.',
        ]);

        // 3. Guardar el producto
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $codigo) {
            $clinicaId = \Illuminate\Support\Facades\DB::table('clinicas')->value('id') ?? 1;

            $stockActual = (int) $request->stock_actual;
            $stockMinimo = (int) $request->stock_minimo;

            $estado = 'Normal';
            if ($stockActual <= 0) {
                $estado = 'Agotado';
            } elseif ($stockActual <= $stockMinimo) {
                $estado = 'Bajo Stock';
            }

            \App\Models\ProductoInventario::create([
                'clinica_id'       => $clinicaId,
                'categoria_id'     => $request->categoria_id,
                'codigo'           => $codigo,
                'nombre'           => mb_strtoupper(trim($request->nombre), 'UTF-8'),
                'descripcion'      => $request->descripcion ? mb_strtoupper(trim($request->descripcion), 'UTF-8') : null,
                'precio_compra'    => $request->precio_compra,
                'precio_venta'     => $request->precio_venta,
                'stock_actual'     => $stockActual,
                'stock_disponible' => $stockActual,
                'stock_minimo'     => $stockMinimo,
                'stock_maximo'     => $stockMinimo * 5,
                'estado'           => $estado,
            ]);
        });

        // 4. Redirección con mensaje de éxito para el Layout Admin
        return redirect()->route('inventario.index')
            ->with('success', 'Medicamento registrado correctamente en el sistema.');
    }

    /**
     * Modal 2: Actualiza los datos de un producto.
     */
    public function update(Request $request, $id)
    {
        $producto = ProductoInventario::findOrFail($id);

        $request->validate([
            'codigo'        => 'required|digits:13|unique:productos_inventario,codigo,' . $id,
            'categoria_id'  => 'required|exists:categorias_inventario,id',
            'nombre'        => 'required|string|max:255',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'descripcion'   => 'nullable|string',
        ], [
            'codigo.digits' => 'El código de barras debe contener exactamente 13 dígitos numéricos.',
            'codigo.unique' => 'Este código de barras pertenece a otro medicamento.',
        ]);

        $producto->update([
            'codigo'        => trim($request->codigo),
            'categoria_id'  => $request->categoria_id,
            'nombre'        => mb_strtoupper(trim($request->nombre), 'UTF-8'),
            'precio_compra' => $request->precio_compra,
            'precio_venta'  => $request->precio_venta,
            'stock_minimo'  => $request->stock_minimo,
            'descripcion'   => $request->descripcion ? mb_strtoupper(trim($request->descripcion), 'UTF-8') : null,
        ]);

        return redirect()->route('inventario.index')->with('success', 'Medicamento actualizado correctamente.');
    }

    /**
     * Modal 3: Surtir / Reabastecer existencias de stock.
     */
    public function agregarStock(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
            'motivo'   => 'nullable|string|max:255',
        ]);

        $producto = ProductoInventario::findOrFail($id);

        // Incrementar stock real y disponible
        $producto->increment('stock_actual', $request->cantidad);
        $producto->increment('stock_disponible', $request->cantidad);

        return redirect()->route('inventario.index')->with('success', "Se sumaron {$request->cantidad} unidades al stock de {$producto->nombre}.");
    }

    /**
     * Eliminar medicamento (AJAX SweetAlert).
     */
    public function destroy($id)
    {
        $producto = ProductoInventario::findOrFail($id);
        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medicamento eliminado correctamente.'
        ]);
    }
}