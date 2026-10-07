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
     * Muestra la pantalla principal del POS con el catálogo y la lista de tickets recientes.
     */
    public function index()
    {
        $productos = ProductoInventario::with('categoria')->get();
        
        $pacientes = Paciente::orderBy('primer_nombre', 'asc')
            ->orderBy('apellido_paterno', 'asc')
            ->get();

        // Cargar los últimos 30 tickets generados para la búsqueda y consulta en el POS
        $ticketsRecientes = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return view('pos.index', compact('productos', 'pacientes', 'ticketsRecientes'));
    }

    /**
     * Genera un nuevo ticket de venta con 25 minutos de vigencia.
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

            // Validar disponibilidad de stock reservable
            foreach ($request->carrito as $item) {
                $producto = ProductoInventario::findOrFail($item['id']);
                
                $stockDisp = $producto->stock_disponible ?? $producto->stock_actual ?? 0;

                if ($item['cantidad'] > $stockDisp) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "EL MEDICAMENTO '{$producto->nombre}' SOLO TIENE {$stockDisp} PZAS. DISPONIBLES."
                    ], 422);
                }

                $subtotal = $producto->precio_venta * $item['cantidad'];
                $montoTotal += $subtotal;

                $detallesAgregados[] = [
                    'producto_id'     => $producto->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $producto->precio_venta,
                    'subtotal'        => $subtotal,
                ];
            }

            // Generar código único de ticket
            $codigoTicket = 'TCK-' . strtoupper(Str::random(6));

            // Crear cabecera del ticket con 25 min de vigencia
            $ticket = TicketVenta::create([
                'codigo_ticket' => $codigoTicket,
                'paciente_id'   => $request->paciente_id,
                'user_id'       => Auth::id() ?? 1,
                'monto_total'   => $montoTotal, // Usamos el total calculado acumulado
                'status'        => 'pendiente',
                'created_at'    => now('America/Mexico_City'),
                'expires_at'    => now('America/Mexico_City')->addMinutes(25),
            ]);

            // Guardar detalles del pedido
            foreach ($detallesAgregados as $detalle) {
                $detalle['ticket_id'] = $ticket->id;
                TicketDetalle::create($detalle);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'TICKET GENERADO CON ÉXITO',
                'ticket'  => $ticket->load(['paciente', 'detalles.producto'])
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
     * Obtiene los datos de un ticket por su ID para AJAX / Modal de impresión.
     */
    public function obtenerTicket($id)
    {
        $ticket = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])->findOrFail($id);
        return response()->json($ticket);
    }

    /**
     * Búsqueda dinámica de productos por AJAX (código de barras o nombre).
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
     * Imprimir ticket / comprobante de venta en formato imprimible.
     */
    public function ticket($id)
    {
        $ticket = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])->findOrFail($id);
        return view('pos.ticket', compact('ticket'));
    }
}