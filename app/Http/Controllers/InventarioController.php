<?php

namespace App\Http\Controllers;

use App\Models\ProductoInventario;
use App\Models\CategoriaInventario;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * Muestra el catálogo principal de inventario.
     */
    public function index()
    {
        $productos = ProductoInventario::conStock()->with('categoria')->orderBy('created_at', 'desc')->get();
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
        $existeProducto = ProductoInventario::where('codigo', $codigo)->exists();
        if ($existeProducto) {
            return redirect()->back()
                ->withInput()
                ->with('error', '¡El código de barras (' . $codigo . ') ya está registrado con otro medicamento!');
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
        DB::transaction(function () use ($request, $codigo) {
            $clinicaId = DB::table('clinicas')->value('id') ?? 1;

            $stockActual = (int) $request->stock_actual;
            $stockMinimo = (int) $request->stock_minimo;

            $estado = 'Normal';
            if ($stockActual <= 0) {
                $estado = 'Agotado';
            } elseif ($stockActual <= $stockMinimo) {
                $estado = 'Bajo Stock';
            }

            $producto = ProductoInventario::create([
                'clinica_id'       => $clinicaId,
                'categoria_id'     => $request->categoria_id,
                'codigo'           => $codigo,
                'nombre'           => mb_strtoupper(trim($request->nombre), 'UTF-8'),
                'descripcion'      => $request->descripcion ? mb_strtoupper(trim($request->descripcion), 'UTF-8') : '', // la columna no acepta NULL
                'precio_compra'    => $request->precio_compra,
                'precio_venta'     => $request->precio_venta,
                'stock_actual'     => $stockActual,
                'stock_minimo'     => $stockMinimo,
                'stock_maximo'     => $stockMinimo * 5,
                'estado'           => $estado,
            ]);

            // Existencia inicial con su movimiento de entrada (auditoría)
            if ($stockActual > 0) {
                MovimientoInventario::registrar($producto, 'Entrada', $stockActual, ['motivo' => 'INVENTARIO INICIAL']);
            }
        });

        // 4. Redirección con mensaje de éxito
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

        $stockMinimo = (int) $request->stock_minimo;

        // Recalcular estado por si cambió el stock mínimo (misma regla que el resto del inventario)
        $producto->stock_minimo = $stockMinimo;
        $estado = $producto->recalcularEstado()->estado;

        $producto->update([
            'codigo'        => trim($request->codigo),
            'categoria_id'  => $request->categoria_id,
            'nombre'        => mb_strtoupper(trim($request->nombre), 'UTF-8'),
            'precio_compra' => $request->precio_compra,
            'precio_venta'  => $request->precio_venta,
            'stock_minimo'  => $stockMinimo,
            'stock_maximo'  => $stockMinimo * 5,
            'descripcion'   => $request->descripcion ? mb_strtoupper(trim($request->descripcion), 'UTF-8') : '', // la columna no acepta NULL
            'estado'        => $estado,
        ]);

        return redirect()->route('inventario.index')->with('success', 'Medicamento actualizado correctamente.');
    }

    /**
     * Modal 3: Surtir / Reabastecer existencias de stock individual desde la tabla.
     */
    public function agregarStock(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
            'motivo'   => 'nullable|string|max:255',
        ], [
            'cantidad.required' => 'Debe ingresar la cantidad a sumar.',
            'cantidad.min'      => 'La cantidad debe ser de al menos 1 unidad.',
        ]);

        DB::transaction(function () use ($request, $id) {
            $this->sumarStock(
                (int) $id,
                (int) $request->cantidad,
                ['motivo' => $request->filled('motivo') ? mb_strtoupper(trim($request->motivo), 'UTF-8') : 'REABASTECIMIENTO']
            );
        });

        return redirect()->route('inventario.index')
            ->with('success', 'Stock actualizado correctamente.');
    }

    /**
     * Modal 4: Surtir un lote de medicamentos (por proveedor/factura).
     */
    public function surtirLote(Request $request)
    {
        $request->validate([
            'referencia'  => 'required|string|max:255',
            'producto_id' => 'required|exists:productos_inventario,id',
            'cantidad'    => 'required|integer|min:1',
            'lote'        => 'nullable|string|max:100',
        ], [
            'referencia.required'  => 'Debe especificar el proveedor o número de factura.',
            'producto_id.required' => 'Debe seleccionar un medicamento del catálogo.',
            'producto_id.exists'   => 'El medicamento seleccionado no existe.',
            'cantidad.required'    => 'Debe ingresar la cantidad a ingresar.',
            'cantidad.min'         => 'Debe ingresar al menos 1 unidad.',
        ]);

        DB::transaction(function () use ($request) {
            // Antes la referencia (proveedor/factura) y el lote se validaban pero no se guardaban
            $this->sumarStock((int) $request->producto_id, (int) $request->cantidad, [
                'motivo'     => 'SURTIDO DE LOTE',
                'referencia' => mb_strtoupper(trim($request->referencia), 'UTF-8'),
                'lote'       => $request->filled('lote') ? mb_strtoupper(trim($request->lote), 'UTF-8') : null,
            ]);
        });

        // El modal de lotes envía cada renglón por fetch y espera JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lote de medicamento surtido correctamente en el almacén.',
            ]);
        }

        return redirect()->route('inventario.index')
            ->with('success', 'Lote de medicamento surtido correctamente en el almacén.');
    }

    /**
     * Suma existencias de forma segura y deja constancia en movimientos_inventario.
     * Bloquea la fila y usa un incremento en SQL: dos surtidos simultáneos ya no se pisan.
     * Debe llamarse dentro de una transacción.
     */
    private function sumarStock(int $productoId, int $cantidad, array $datosMovimiento): ProductoInventario
    {
        $producto = ProductoInventario::lockForUpdate()->findOrFail($productoId);

        $producto->increment('stock_actual', $cantidad);
        $producto->refresh()->recalcularEstado()->save();

        MovimientoInventario::registrar($producto, 'Entrada', $cantidad, $datosMovimiento);

        return $producto;
    }

    /**
     * Eliminar medicamento (AJAX SweetAlert).
     */
    public function destroy($id)
    {
        $producto = ProductoInventario::findOrFail($id);

        // Un producto con ventas no se borra: se perdería el detalle de tickets cobrados.
        if (\App\Models\TicketDetalle::where('producto_id', $producto->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: este medicamento ya tiene ventas registradas. '
                           . 'Puedes dejar su stock en 0 para que deje de venderse.',
            ], 409);
        }

        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medicamento eliminado correctamente.'
        ]);
    }
}