<?php

namespace App\Http\Controllers;

use App\Models\TicketVenta;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CajaController extends Controller
{
    /**
     * Muestra la pantalla principal de la Caja Central.
     */
    public function index()
    {
        $ticketsPendientes = TicketVenta::with(['paciente', 'detalles.producto'])
            ->where('status', 'pendiente')
            ->where('expires_at', '>', now('America/Mexico_City'))
            ->orderBy('created_at', 'desc')
            ->get();

        // Carga los cobros realizados hoy junto con su ticket completo para poder reimprimir
        $cobrosHoy = MovimientoCaja::with(['ticket.paciente', 'ticket.detalles.producto'])
            ->whereDate('created_at', today('America/Mexico_City'))
            ->orderBy('created_at', 'desc')
            ->get();

        return view('caja.index', compact('ticketsPendientes', 'cobrosHoy'));
    }

    /**
     * Busca un ticket por su folio desde el lector de código de barras.
     */
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
            'pagado'    => 'ESTE TICKET YA FUE PAGADO (PENDIENTE DE ENTREGA EN FARMACIA).',
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

    /**
     * Registra el pago del ticket en la Caja Central.
     * Cambia a 'pagado' para que pase al área de entrega de Farmacia POS[cite: 1, 7].
     */
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

                // 1. Marcar como pagado
                $ticket->update(['status' => 'pagado']);

                // 2. Registrar el movimiento en la bitácora de Caja
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
                'message' => 'PAGO REGISTRADO CORRECTAMENTE. TICKET LISTO PARA SURTIR EN FARMACIA/POS.',
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