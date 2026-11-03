<?php

namespace App\Http\Controllers;

use App\Models\TicketVenta;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CajaController extends Controller
{
    /**
     * Muestra la pantalla principal de la Caja Central.
     */
    public function index()
    {
        $fechaHoy = today('America/Mexico_City');

        $estadoCaja = $this->obtenerEstadoCaja();
        $cajaAbierta = $estadoCaja['abierta'] ? $estadoCaja['apertura'] : null;

        // Sugerir el monto dejado en el último cierre
        $montoAnterior = $estadoCaja['cierre'] ? (float) $estadoCaja['cierre']->monto : 0.00;

        // Tickets pendientes de cobro
        $ticketsPendientes = TicketVenta::with(['paciente', 'detalles.producto'])
            ->where('status', 'pendiente')
            ->where('expires_at', '>', now('America/Mexico_City'))
            ->orderBy('created_at', 'desc')
            ->get();

        // Carga los cobros realizados durante la sesión actual (posteriores a la apertura)
        $cobrosHoy = collect();
        if ($cajaAbierta) {
            $cobrosHoy = MovimientoCaja::with(['ticket.paciente', 'ticket.detalles.producto'])
                ->where('tipo', 'Ingreso')
                ->whereNotNull('ticket_id')
                ->where('id', '>', $cajaAbierta->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Desglose de totales del turno activo
        $totales = [
            'fondo_inicial' => $cajaAbierta ? (float) $cajaAbierta->monto : 0.00,
            'efectivo'      => (float) $cobrosHoy->where('metodo_pago', 'Efectivo')->sum('monto'),
            'tarjeta'       => (float) $cobrosHoy->where('metodo_pago', 'Tarjeta')->sum('monto'),
            'transferencia' => (float) $cobrosHoy->where('metodo_pago', 'Transferencia')->sum('monto'),
        ];

        $totales['total_ventas'] = $totales['efectivo'] + $totales['tarjeta'] + $totales['transferencia'];
        $totales['total_efectivo_en_caja'] = $totales['fondo_inicial'] + $totales['efectivo'];

        return view('caja.index', compact(
            'ticketsPendientes', 
            'cobrosHoy', 
            'cajaAbierta', 
            'totales', 
            'montoAnterior'
        ));
    }

    /**
     * Apertura la Caja Central del día/turno registrando el fondo inicial.
     */
    public function aperturar(Request $request)
    {
        $request->validate([
            'monto_inicial' => 'required|numeric|min:0',
        ], [
            'monto_inicial.required' => 'EL FONDO INICIAL ES OBLIGATORIO.',
            'monto_inicial.numeric'  => 'EL FONDO INICIAL DEBE SER UN NÚMERO.',
            'monto_inicial.min'      => 'EL FONDO INICIAL NO PUEDE SER NEGATIVO.',
        ]);

        if ($this->obtenerEstadoCaja()['abierta']) {
            return back()->with('error', 'LA CAJA YA SE ENCUENTRA APERTURADA Y ACTIVA.');
        }

        try {
            MovimientoCaja::create([
                'ticket_id'   => null,
                'user_id'     => Auth::id() ?? 1,
                'tipo'        => 'Ingreso',
                'concepto'    => 'APERTURA DE CAJA CENTRAL - FONDO INICIAL',
                'monto'       => $request->monto_inicial,
                'metodo_pago' => 'Efectivo',
            ]);
        } catch (\Exception $e) {
            report($e);
            return back()->withInput()->with('error', 'ERROR AL APERTURAR LA CAJA: ' . $e->getMessage());
        }

        return redirect()->route('caja.index')->with('success', 'CAJA APERTURADA CORRECTAMENTE.');
    }

    /**
     * Realiza el Cierre / Corte de Caja Central del turno con validación de contraseña de supervisor.
     */
    public function cerrar(Request $request)
    {
        $request->validate([
            'admin_password' => 'required',
        ], [
            'admin_password.required' => 'LA CONTRASEÑA DE AUTORIZACIÓN ES OBLIGATORIA.',
        ]);

        // Validar contraseña del usuario autenticado
        if (!Hash::check($request->admin_password, Auth::user()->password)) {
            return back()->with('error', 'CONTRASEÑA DE AUTORIZACIÓN INCORRECTA.');
        }

        $estadoCaja = $this->obtenerEstadoCaja();
        if (!$estadoCaja['abierta']) {
            return back()->with('error', 'LA CAJA NO SE ENCUENTRA APERTURADA.');
        }

        $ultimaApertura = $estadoCaja['apertura'];

        try {
            // Calcular total en efectivo del turno activo
            $ventasEfectivo = MovimientoCaja::where('tipo', 'Ingreso')
                ->whereNotNull('ticket_id')
                ->where('metodo_pago', 'Efectivo')
                ->where('id', '>', $ultimaApertura->id)
                ->sum('monto');

            $montoTotalCierre = (float) $ultimaApertura->monto + (float) $ventasEfectivo;

            MovimientoCaja::create([
                'ticket_id'   => null,
                'user_id'     => Auth::id() ?? 1,
                'tipo'        => 'Egreso',
                'concepto'    => 'CIERRE DE CAJA CENTRAL - CORTE DEL DÍA',
                'monto'       => $montoTotalCierre,
                'metodo_pago' => 'Efectivo',
            ]);

            return redirect()->route('caja.index')->with('success', 'CORTE Y CIERRE DE CAJA REALIZADO CORRECTAMENTE.');

        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'ERROR AL CERRAR LA CAJA: ' . $e->getMessage());
        }
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
            ], 409);
        }

        return response()->json(['status' => 'success', 'ticket' => $ticket]);
    }

    /**
     * Registra el pago del ticket en la Caja Central.
     */
    public function procesarPago(Request $request, $id)
    {
        if (!$this->obtenerEstadoCaja()['abierta']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'LA CAJA DEBE ESTAR APERTURADA PARA PROCESAR PAGOS.',
            ], 409);
        }

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
                return response()->json(['status' => 'error', 'message' => $resultado['error']], 409);
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
                'message' => 'ERROR AL PROCESAR EL COBRO.' . (config('app.debug') ? ' ' . $e->getMessage() : ''),
            ], 500);
        }
    }

    private function obtenerEstadoCaja(): array
    {
        $ultimaApertura = MovimientoCaja::where('tipo', 'Ingreso')
            ->where('concepto', 'APERTURA DE CAJA CENTRAL - FONDO INICIAL')
            ->latest()
            ->first();

        $ultimoCierre = MovimientoCaja::where('tipo', 'Egreso')
            ->where('concepto', 'like', 'CIERRE DE CAJA CENTRAL%')
            ->latest()
            ->first();

        return [
            'apertura' => $ultimaApertura,
            'cierre' => $ultimoCierre,
            'abierta' => $ultimaApertura && (!$ultimoCierre || $ultimaApertura->id > $ultimoCierre->id),
        ];
    }
}