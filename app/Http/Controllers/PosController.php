<?php

namespace App\Http\Controllers;

use App\Models\ProductoInventario;
use App\Models\Paciente;
use App\Models\TicketVenta;
use App\Models\TicketDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PosController extends Controller
{
    /**
     * Muestra la interfaz del POS con catálogo, pacientes y tickets recientes.
     */
    public function index()
    {
        $productos = ProductoInventario::with('categoria')->get();

        $pacientes = Paciente::orderBy('primer_nombre', 'asc')
            ->orderBy('apellido_paterno', 'asc')
            ->get();

        $ticketsRecientes = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return view('pos.index', compact('productos', 'pacientes', 'ticketsRecientes'));
    }

    /**
     * Genera un nuevo ticket de venta con 25 minutos de vigencia.
     * Solo reserva stock disponible dinámico; NO modifica stock_actual en la BD.
     */
    public function generarTicket(Request $request)
    {
        $request->validate([
            'paciente_id'        => 'nullable|exists:pacientes,id',
            'carrito'            => 'required|array|min:1',
            'carrito.*.id'       => 'required|exists:productos_inventario,id',
            'carrito.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $montoTotal = 0;
            $detallesAgregados = [];

            foreach ($request->carrito as $item) {
                $producto = ProductoInventario::findOrFail($item['id']);
                
                if ($item['cantidad'] > $producto->stock_disponible) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "EL MEDICAMENTO '{$producto->nombre}' SOLO TIENE {$producto->stock_disponible} PZAS. DISPONIBLES."
                    ], 422);
                }

                $precioUnitario = $producto->precio_venta ?? 0;
                $subtotal = $precioUnitario * $item['cantidad'];
                $montoTotal += $subtotal;

                $detallesAgregados[] = [
                    'producto_id'     => $producto->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal,
                ];
            }

            $codigoTicket = 'TCK-' . strtoupper(Str::random(6));

            $ticket = TicketVenta::create([
                'codigo_ticket' => $codigoTicket,
                'paciente_id'   => $request->paciente_id,
                'user_id'       => Auth::id() ?? 1,
                'monto_total'   => $montoTotal,
                'status'        => 'pendiente',
                'expires_at'    => now('America/Mexico_City')->addMinutes(25),
            ]);

            foreach ($detallesAgregados as $detalle) {
                $detalle['ticket_id'] = $ticket->id;
                TicketDetalle::create($detalle);
            }

            DB::commit();

            $productosActualizados = ProductoInventario::with('categoria')->get();

            return response()->json([
                'status'    => 'success',
                'message'   => 'TICKET GENERADO CON ÉXITO',
                'ticket'    => $ticket->load(['paciente', 'detalles.producto']),
                'productos' => $productosActualizados
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL GENERAR EL TICKET: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * UNICO PUNTO DONDE SE DECREMENTA EL STOCK FÍSICO REAL EN INVENTARIO.
     */
    public function marcarEntregado($id)
    {
        try {
            DB::beginTransaction();

            $ticket = TicketVenta::with('detalles')->findOrFail($id);

            if ($ticket->status !== 'pagado') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'EL TICKET DEBE ESTAR EN ESTADO PAGADO PARA PODER ENTREGARSE.'
                ], 400);
            }

            // Cambiar estado a entregado
            $ticket->status = 'entregado';
            $ticket->save();

            // Descuento único en inventario físico
            foreach ($ticket->detalles as $detalle) {
                $producto = ProductoInventario::find($detalle->producto_id);

                if ($producto) {
                    $producto->decrement('stock_actual', $detalle->cantidad);

                    $stockVal = $producto->fresh()->stock_actual;
                    if ($stockVal <= 0) {
                        $producto->update(['estado' => 'Agotado']);
                    } elseif ($stockVal <= $producto->stock_minimo) {
                        $producto->update(['estado' => 'Bajo Stock']);
                    } else {
                        $producto->update(['estado' => 'Normal']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "TICKET {$ticket->codigo_ticket} ENTREGADO Y DESPACHADO CON ÉXITO."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL PROCESAR LA ENTREGA: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerTicket($id)
    {
        $ticket = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])->findOrFail($id);
        return response()->json($ticket);
    }

    public function buscarProducto(Request $request)
    {
        $q = strtoupper(trim($request->q ?? $request->search ?? ''));

        if (empty($q)) {
            return response()->json([]);
        }

        $productos = ProductoInventario::with('categoria')
            ->where(function ($query) use ($q) {
                $query->where(DB::raw('UPPER(codigo)'), 'LIKE', "%{$q}%")
                      ->orWhere(DB::raw('UPPER(nombre)'), 'LIKE', "%{$q}%");
            })
            ->get();

        return response()->json($productos);
    }

    public function ticket($id)
    {
        $ticket = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])->findOrFail($id);
        return view('pos.ticket', compact('ticket'));
    }
}