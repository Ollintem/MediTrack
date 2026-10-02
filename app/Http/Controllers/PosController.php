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
    public function index()
    {
        // Obtener productos con su cálculo de stock disponible dinámico
        $productos = ProductoInventario::with('categoria')->get();
        $pacientes = Paciente::orderBy('primer_nombre', 'asc')
                        ->orderBy('apellido_paterno', 'asc')
                        ->get();

        return view('pos.index', compact('productos', 'pacientes'));
    }

    public function generarTicket(Request $request)
    {
        $request->validate([
            'paciente_id' => 'nullable|exists:pacientes,id',
            'carrito'     => 'required|array|min:1',
            'carrito.*.id' => 'required|exists:productos_inventario,id',
            'carrito.*.cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $montoTotal = 0;
            $detallesAgregados = [];

            // Validar disponibilidad de stock reservable
            foreach ($request->carrito as $item) {
                $producto = ProductoInventario::findOrFail($item['id']);
                
                if ($item['cantidad'] > $producto->stock_disponible) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "EL MEDICAMENTO '{$producto->nombre}' SOLO TIENE {$producto->stock_disponible} PZAS. DISPONIBLES."
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
                'monto_total'   => $montoTotal,
                'status'        => 'pendiente',
                'expires_at'    => now()->addMinutes(25),
            ]);

            // Guardar detalles
            foreach ($detallesAgregados as $detalle) {
                $detalle['ticket_id'] = $ticket->id;
                TicketDetalle::create($detalle);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'TICKET GENERADO CON ÉXITO',
                'ticket'  => $ticket->load('paciente', 'detalles.producto')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL GENERAR EL TICKET: ' . $e->getMessage()
            ], 500);
        }
    }
}