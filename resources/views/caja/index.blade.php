@extends('layouts.admin')

@section('content')
<div class="space-y-6 w-full" x-data="cajaApp()">
    
    <!-- BANNER CAJA CENTRAL -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-teal-700 to-teal-600 p-6 text-white shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-white border border-white/20 uppercase tracking-wider mb-2">
                    <i class="bi bi-cash-coin"></i> FINANZAS
                </span>
                <h1 class="text-2xl font-extrabold uppercase tracking-tight">CAJA CENTRAL Y COBROS PENDIENTES</h1>
                <p class="text-teal-100 text-xs font-semibold mt-0.5">RECEPCIÓN DE PAGOS DE TICKETS DE FARMACIA Y LIGADO A DESPACHO.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LISTA DE COBROS PENDIENTES (8 COLS) -->
        <div class="lg:col-span-8 space-y-4">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-extrabold text-gray-800 uppercase flex items-center gap-2">
                        <i class="bi bi-clock-history text-amber-500"></i> TICKETS PENDIENTES POR COBRAR
                    </h3>
                    <span class="text-xs font-bold text-gray-400 bg-gray-50 px-3 py-1 rounded-full border">
                        {{ $ticketsPendientes->count() }} PENDIENTES
                    </span>
                </div>

                <!-- BUSCADOR RÁPIDO DE TICKET -->
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="BUSCAR TICKET POR CÓDIGO O PACIENTE..." 
                           class="w-full bg-slate-50 border border-gray-200 focus:border-teal-500 focus:bg-white text-xs font-bold rounded-2xl pl-10 pr-4 py-3 focus:outline-none uppercase placeholder:text-gray-400 transition-all">
                </div>

                <!-- CARDS DE TICKETS PENDIENTES -->
                <div class="space-y-3 max-h-[550px] overflow-y-auto pr-1">
                    @forelse($ticketsPendientes as $tck)
                    <div x-show="cumpleFiltro('{{ strtoupper($tck->codigo_ticket) }}', '{{ strtoupper($tck->paciente ? $tck->paciente->nombre_completo : 'PÚBLICO GENERAL') }}')"
                         class="p-4 rounded-2xl border border-gray-100 bg-slate-50/50 hover:border-teal-200 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black text-teal-800 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-200">
                                    {{ $tck->codigo_ticket }}
                                </span>
                                <!-- BADGE DE TIEMPO / VIGENCIA -->
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                    <i class="bi bi-hourglass-split"></i> EXPIRA: {{ $tck->expires_at->format('H:i') }}
                                </span>
                            </div>
                            <p class="text-xs font-extrabold text-gray-800 uppercase mt-1">
                                PACIENTE: {{ $tck->paciente ? strtoupper($tck->paciente->nombre_completo) : 'PÚBLICO GENERAL' }}
                            </p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">
                                MEDICAMENTOS: {{ $tck->detalles->count() }} PZAS.
                            </p>
                        </div>

                        <div class="flex items-center gap-4 justify-between md:justify-end">
                            <div class="text-right">
                                <span class="block text-[10px] font-bold text-gray-400 uppercase">TOTAL</span>
                                <span class="text-lg font-black text-teal-700">${{ number_format($tck->monto_total, 2) }}</span>
                            </div>

                            <button @click="abrirModalCobro({{ json_encode($tck) }})"
                                    class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-xl text-xs uppercase shadow-md shadow-teal-600/20 transition-all flex items-center gap-1.5">
                                <i class="bi bi-wallet2"></i> COBRAR
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-16 text-gray-400">
                        <i class="bi bi-check-circle text-3xl"></i>
                        <p class="text-xs font-bold uppercase mt-2">NO HAY COBROS PENDIENTES</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RESUMEN DE MOVIMIENTOS DEL DÍA (4 COLS) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-gray-800 uppercase border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="bi bi-receipt text-teal-600"></i> COBROS REALIZADOS HOY
                </h3>

                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                    @forelse($cobrosHoy as $mov)
                    <div class="p-3 bg-slate-50 rounded-2xl border border-gray-100 text-xs space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="font-extrabold text-teal-700">{{ $mov->ticket->codigo_ticket ?? 'S/N' }}</span>
                            <span class="font-black text-gray-800">${{ number_format($mov->monto, 2) }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">{{ $mov->concepto }}</p>
                        <div class="flex justify-between text-[9px] font-semibold text-gray-400 pt-1">
                            <span>MÉTODO: {{ strtoupper($mov->metodo_pago) }}</span>
                            <span>{{ $mov->created_at->format('H:i A') }}</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-center py-10 text-xs font-bold text-gray-400 uppercase">SIN MOVIMIENTOS REGISTRADOS HOY</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL PARA PROCESAR EL COBRO -->
    <div x-show="openModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div @click.outside="openModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="text-sm font-black text-gray-800 uppercase">PROCESAR COBRO: <span class="text-teal-600" x-text="ticketSeleccionado.codigo_ticket"></span></h3>
                <button @click="openModal = false" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="space-y-3">
                <div class="p-4 bg-slate-50 rounded-2xl space-y-2">
                    <p class="text-xs font-extrabold text-gray-700 uppercase" x-text="`PACIENTE: ${ticketSeleccionado.paciente ? ticketSeleccionado.paciente.nombre_completo : 'PÚBLICO GENERAL'}`"></p>
                    <div class="flex justify-between items-baseline pt-2 border-t border-gray-200">
                        <span class="text-xs font-bold text-gray-400 uppercase">TOTAL A COBRAR:</span>
                        <span class="text-2xl font-black text-teal-700" x-text="`$${parseFloat(ticketSeleccionado.monto_total || 0).toFixed(2)}`"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1">MÉTODO DE PAGO *</label>
                    <select x-model="metodoPago" class="w-full bg-slate-50 border border-gray-200 text-xs font-bold rounded-2xl px-3 py-3 uppercase focus:outline-none focus:border-teal-500">
                        <option value="Efectivo">EFECTIVO</option>
                        <option value="Tarjeta">TARJETA DE DÉBITO / CRÉDITO</option>
                        <option value="Transferencia">TRANSFERENCIA BANCARIA</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button @click="openModal = false" class="w-1/2 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-2xl text-xs uppercase">
                    CANCELAR
                </button>
                <button @click="confirmarPago()" :disabled="cargando" class="w-1/2 py-3 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-2xl text-xs uppercase shadow-lg shadow-teal-600/20">
                    <span x-text="cargando ? 'PROCESANDO...' : 'CONFIRMAR PAGO'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function cajaApp() {
    return {
        searchQuery: '',
        openModal: false,
        ticketSeleccionado: {},
        metodoPago: 'Efectivo',
        cargando: false,

        cumpleFiltro(codigo, paciente) {
            const q = this.searchQuery.trim().toUpperCase();
            return !q || codigo.includes(q) || paciente.includes(q);
        },

        abrirModalCobro(ticket) {
            this.ticketSeleccionado = ticket;
            this.openModal = true;
        },

        confirmarPago() {
            if (!this.ticketSeleccionado.id) return;
            this.cargando = true;

            fetch(`/caja/procesar-pago/${this.ticketSeleccionado.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    metodo_pago: this.metodoPago
                })
            })
            .then(res => res.json())
            .then(data => {
                this.cargando = false;
                if (data.status === 'success') {
                    window.notificar ? window.notificar(data.message, 'success') : alert(data.message);
                    this.openModal = false;
                    setTimeout(() => location.reload(), 1200);
                } else {
                    window.notificar ? window.notificar(data.message, 'error') : alert(data.message);
                }
            })
            .catch(() => {
                this.cargando = false;
                window.notificar ? window.notificar('ERROR DE CONEXIÓN AL PROCESAR EL PAGO', 'error') : alert('ERROR DE CONEXIÓN');
            });
        }
    }
}
</script>
@endsection