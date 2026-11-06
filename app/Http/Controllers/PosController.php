<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
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
        $productos = ProductoInventario::conStock()->with('categoria')->get();

        $pacientes = Paciente::orderBy('primer_nombre', 'asc')
            ->orderBy('apellido_paterno', 'asc')
            ->get();

        $ticketsRecientes = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto' => ProductoInventario::cargarConStock()])
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

            // Un renglón por producto (suma cantidades repetidas) y en orden de id:
            // así la validación es contra el total real y se evitan deadlocks.
            $carrito = collect($request->carrito)
                ->groupBy(fn ($i) => (int) $i['id'])
                ->map(fn ($g, $id) => ['id' => (int) $id, 'cantidad' => (int) $g->sum('cantidad')])
                ->sortKeys()
                ->values();

            foreach ($carrito as $item) {
                // Bloquea la fila: otra venta simultánea del mismo producto espera a que esta termine
                $producto = ProductoInventario::lockForUpdate()->findOrFail($item['id']);
                
                // Evaluar disponibilidad estricta considerando el accesor o resta directa
                $stockDisponible = $producto->stock_disponible ?? ($producto->stock_actual - ($producto->stock_reservado ?? 0));

                if ($item['cantidad'] > $stockDisponible) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => "EL MEDICAMENTO '{$producto->nombre}' SOLO TIENE {$stockDisponible} PZAS. DISPONIBLES."
                    ], 409); // conflicto con las existencias actuales
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

            $productosActualizados = ProductoInventario::conStock()->with('categoria')->get();

            return response()->json([
                'status'    => 'success',
                'message'   => 'TICKET GENERADO CON ÉXITO',
                'ticket'    => $ticket->load(['paciente', 'detalles.producto' => ProductoInventario::cargarConStock()]),
                'productos' => $productosActualizados
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL GENERAR EL TICKET.' . (config('app.debug') ? ' ' . $e->getMessage() : '')
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
            // lockForUpdate: una segunda entrega simultánea espera y luego ve 'entregado'
            $ticket = TicketVenta::with('detalles.producto') // sin conStock: la respuesta debe reflejar el stock DESPUÉS de la entrega
                ->where(function ($q) use ($codigoLimpio) {
                    $q->where('id', $codigoLimpio)
                      ->orWhere('codigo_ticket', $codigoLimpio);
                })
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => "EL TICKET '{$codigoLimpio}' NO FUE ENCONTRADO EN EL SISTEMA."
                ], 404);
            }

            if ($ticket->status === 'entregado') {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => "EL TICKET {$ticket->codigo_ticket} YA FUE ENTREGADO ANTERIORMENTE."
                ], 409);
            }

            if ($ticket->status !== 'pagado') {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'EL TICKET DEBE ESTAR EN ESTADO PAGADO PARA PODER ENTREGARSE.'
                ], 409);
            }

            // Piezas a descontar por producto. Se bloquean las filas en orden de id (evita deadlocks)
            // y se revisa que haya existencia física suficiente: el stock nunca queda negativo.
            $piezas = $ticket->detalles->groupBy('producto_id')->map(fn ($g) => (int) $g->sum('cantidad'))->sortKeys();
            $productos = ProductoInventario::whereIn('id', $piezas->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($piezas as $productoId => $cantidad) {
                $producto = $productos->get($productoId);
                $fisico = (int) ($producto->stock_actual ?? 0);

                if (!$producto || $fisico < $cantidad) {
                    DB::rollBack();
                    $nombre = $producto->nombre ?? "#{$productoId}";
                    return response()->json([
                        'status'  => 'error',
                        'message' => "NO HAY EXISTENCIA FÍSICA SUFICIENTE DE '{$nombre}' (HAY {$fisico}, SE REQUIEREN {$cantidad}). REVISA EL INVENTARIO ANTES DE ENTREGAR.",
                    ], 409);
                }
            }

            // 1. Cambiar estado a entregado (libera la reserva de este ticket)
            $ticket->status = 'entregado';
            $ticket->save();

            // 2. ÚNICO DESCUENTO REAL DE STOCK FÍSICO, con su movimiento de salida
            foreach ($piezas as $productoId => $cantidad) {
                $producto = $productos->get($productoId);
                $producto->decrement('stock_actual', $cantidad);

                // Mismo criterio de estado que Inventario: según el stock disponible
                $producto->refresh()->recalcularEstado()->save();

                MovimientoInventario::registrar($producto, 'Salida', $cantidad, [
                    'ticket_id' => $ticket->id,
                    'motivo'    => "ENTREGA DE TICKET {$ticket->codigo_ticket}",
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "TICKET {$ticket->codigo_ticket} ENTREGADO Y SURTIDO CON ÉXITO.",
                'ticket'  => $ticket
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL PROCESAR LA ENTREGA.' . (config('app.debug') ? ' ' . $e->getMessage() : '')
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
     * Buscador de productos vía AJAX.
     */
    public function buscarProducto(Request $request)
    {
        $q = strtoupper(trim($request->q ?? $request->search ?? ''));

        if (empty($q)) {
            return response()->json([]);
        }

        $productos = ProductoInventario::conStock()->with('categoria')
            ->where(function ($query) use ($q) {
                $query->where(DB::raw('UPPER(codigo)'), 'LIKE', "%{$q}%")
                      ->orWhere(DB::raw('UPPER(nombre)'), 'LIKE', "%{$q}%");
            })
            ->get();

        return response()->json($productos);
    }
}