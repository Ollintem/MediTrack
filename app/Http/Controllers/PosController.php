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
     * Genera un nuevo ticket de venta con 25 minutos de reserva temporal.
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
                
                // Evaluar disponibilidad estricta considerando el accesor o resta directa
                $stockDisponible = $producto->stock_disponible ?? ($producto->stock_actual - ($producto->stock_reservado ?? 0));

                if ($item['cantidad'] > $stockDisponible) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "EL MEDICAMENTO '{$producto->nombre}' SOLO TIENE {$stockDisponible} PZAS. DISPONIBLES."
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
     * ÚNICO PUNTO DONDE SE DECREMENTA EL STOCK FÍSICO REAL (stock_actual).
     * Al cambiar a 'entregado', las piezas salen del inventario físico y se limpia la reserva.
     */
    public function marcarEntregado($id)
    {
        try {
            DB::beginTransaction();

            $codigoLimpio = trim(strtoupper($id));

            // Permite buscar tanto por ID numérico como por 'codigo_ticket' (columna real)
            $ticket = TicketVenta::with('detalles.producto')
                ->where('id', $codigoLimpio)
                ->orWhere('codigo_ticket', $codigoLimpio)
                ->first();

            if (!$ticket) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "EL TICKET '{$codigoLimpio}' NO FUE ENCONTRADO EN EL SISTEMA."
                ], 404);
            }

            if ($ticket->status === 'entregado') {
                return response()->json([
                    'status'  => 'error',
                    'message' => "EL TICKET {$ticket->codigo_ticket} YA FUE ENTREGADO ANTERIORMENTE."
                ], 422);
            }

            if ($ticket->status !== 'pagado') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'EL TICKET DEBE ESTAR EN ESTADO PAGADO PARA PODER ENTREGARSE.'
                ], 422);
            }

            // 1. Cambiar estado a entregado
            $ticket->status = 'entregado';
            $ticket->save();

            // 2. ÚNICO DESCUENTO REAL DE STOCK FÍSICO
            if ($ticket->detalles) {
                foreach ($ticket->detalles as $detalle) {
                    $producto = ProductoInventario::find($detalle->producto_id);

                    if ($producto) {
                        $producto->decrement('stock_actual', $detalle->cantidad);

                        $stockFisico = $producto->fresh()->stock_actual;

                        // Ajustar estado visual según niveles de stock
                        if ($stockFisico <= 0) {
                            $producto->update(['estado' => 'Agotado']);
                        } elseif ($stockFisico <= $producto->stock_minimo) {
                            $producto->update(['estado' => 'Bajo Stock']);
                        } else {
                            $producto->update(['estado' => 'Normal']);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "TICKET {$ticket->codigo_ticket} ENTREGADO Y SURTIDO CON ÉXITO.",
                'ticket'  => $ticket
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL PROCESAR LA ENTREGA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alias / Ruta rápida para entregar ticket por parámetro de código en URL.
     */
    public function entregarTicket($codigo)
    {
        return $this->marcarEntregado($codigo);
    }

    /**
     * Obtiene los datos detallados de un ticket en formato JSON.
     */
    public function obtenerTicket($id)
    {
        $ticket = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])->findOrFail($id);
        return response()->json($ticket);
    }

    /**
     * Buscador de productos vía AJAX.
     */
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

    /**
     * Carga la vista del ticket para impresión / reimpresión.
     */
    public function ticket($id)
    {
        $ticket = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])->findOrFail($id);
        return view('pos.ticket', compact('ticket'));
    }
}