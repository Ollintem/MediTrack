<?php

namespace App\Http\Controllers;

use App\Models\TicketVenta;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CajaController extends Controller
{
    public function index()
    {
        // Obtener tickets pendientes que aún no hayan expirado (dentro de los 25 minutos)
        $ticketsPendientes = TicketVenta::with(['paciente', 'vendedor', 'detalles.producto'])
            ->where('status', 'pendiente')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener historial de cobros recientes del día
        $cobrosHoy = MovimientoCaja::with(['ticket.paciente', 'usuario'])
            ->whereDate('created_at', now()->today())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('caja.index', compact('ticketsPendientes', 'cobrosHoy'));
    }

    public function procesarPago(Request $request, $id)
    {
        $request->validate([
            'metodo_pago' => 'required|in:Efectivo,Tarjeta,Transferencia',
        ]);

        try {
            DB::beginTransaction();

            $ticket = TicketVenta::findOrFail($id);

            // 1. Validar que no haya expirado por el temporizador de 25 minutos
            if ($ticket->status === 'pendiente' && $ticket->expires_at <= now()) {
                $ticket->update(['status' => 'expirado']);
                DB::commit();

                return response()->json([
                    'status'  => 'error',
                    'message' => 'EL TICKET HA EXPIRADO (EXCEDIÓ LOS 25 MINUTOS). REVALIDAR CON MOSTRADOR.'
                ], 422);
            }

            if ($ticket->status !== 'pendiente') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'ESTE TICKET YA FUE PROCESADO O CANCELADO.'
                ], 422);
            }

            // 2. Cambiar estatus a 'pagado'
            $ticket->update(['status' => 'pagado']);

            // 3. Registrar el ingreso monetario en la caja
            $movimiento = MovimientoCaja::create([
                'ticket_id'   => $ticket->id,
                'user_id'     => Auth::id() ?? 1,
                'tipo'        => 'Ingreso',
                'concepto'    => "PAGO DE TICKET DE FARMACIA: {$ticket->codigo_ticket}",
                'monto'       => $ticket->monto_total,
                'metodo_pago' => $request->metodo_pago,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "PAGO REGISTRADO CORRECTAMENTE. TICKET {$ticket->codigo_ticket} ENVIADO A FARMACIA PARA SURTIDO.",
                'ticket'  => $ticket
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL PROCESAR EL COBRO: ' . $e->getMessage()
            ], 500);
        }
    }
}