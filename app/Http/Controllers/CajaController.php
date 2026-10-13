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
        $ticketsPendientes = TicketVenta::with(['paciente', 'detalles.producto'])
            ->where('status', 'pendiente')
            ->where('expires_at', '>', now('America/Mexico_City'))
            ->orderBy('created_at', 'desc')
            ->get();

        $cobrosHoy = MovimientoCaja::with(['ticket.paciente'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('caja.index', compact('ticketsPendientes', 'cobrosHoy'));
    }

    public function buscarPorCodigo(string $codigo)
    {
        $codigo = strtoupper(str_replace("'", '-', trim($codigo)));

        $ticket = TicketVenta::with(['paciente', 'detalles.producto'])
            ->where('codigo_ticket', $codigo)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status'  => 'error',
                'message' => "NO EXISTE UN TICKET CON EL FOLIO {$codigo}.",
            ], 404);
        }

        if ($ticket->status === 'pendiente' && $ticket->expires_at <= now('America/Mexico_City')) {
            $ticket->update(['status' => 'expirado']);
        }

        $mensajes = [
            'pagado'    => 'ESTE TICKET YA FUE PAGADO.',
            'entregado' => 'ESTE TICKET YA FUE PAGADO Y ENTREGADO.',
            'expirado'  => 'EL TICKET HA EXPIRADO. REVALIDAR EN TERMINAL POS.',
        ];

        if ($ticket->status !== 'pendiente') {
            return response()->json([
                'status'  => 'error',
                'message' => $mensajes[$ticket->status] ?? 'ESTE TICKET YA FUE PROCESADO O CANCELADO.',
                'ticket'  => $ticket,
            ], 422);
        }

        return response()->json(['status' => 'success', 'ticket' => $ticket]);
    }

    public function procesarPago(Request $request, $id)
    {
        $request->validate([
            'metodo_pago' => 'required|in:Efectivo,Tarjeta,Transferencia',
        ]);

        try {
            $resultado = DB::transaction(function () use ($request, $id) {
                $ticket = TicketVenta::with('detalles.producto')->lockForUpdate()->findOrFail($id);

                if ($ticket->status === 'pendiente' && $ticket->expires_at <= now('America/Mexico_City')) {
                    $ticket->update(['status' => 'expirado']);
                    return ['error' => 'EL TICKET HA EXPIRADO. REVALIDAR EN TERMINAL POS.'];
                }

                if ($ticket->status !== 'pendiente') {
                    return ['error' => 'ESTE TICKET YA FUE PROCESADO O CANCELADO.'];
                }

                // Marca como pagado
                $ticket->update(['status' => 'pagado']);

                // Registra el ingreso financiero
                MovimientoCaja::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => Auth::id() ?? 1,
                    'tipo'        => 'Ingreso',
                    'concepto'    => "PAGO DE TICKET DE FARMACIA: {$ticket->codigo_ticket}",
                    'monto'       => $ticket->monto_total,
                    'metodo_pago' => $request->metodo_pago,
                ]);

                return ['ticket' => $ticket->fresh(['paciente', 'detalles.producto'])];
            });

            if (isset($resultado['error'])) {
                return response()->json(['status' => 'error', 'message' => $resultado['error']], 422);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'PAGO REGISTRADO CORRECTAMENTE. TICKET LISTO PARA SURTIR EN POS.',
                'ticket'  => $resultado['ticket'],
            ]);

        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'ERROR AL PROCESAR EL COBRO: ' . $e->getMessage(),
            ], 500);
        }
    }
}