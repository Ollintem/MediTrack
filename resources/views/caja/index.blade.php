@extends('layouts.admin')

@section('title', 'Caja Central y Cobros - MediTrack')

@section('content')
<div class="space-y-6 w-full block" x-data="cajaApp()">

    <!-- BANNER HERO PRINCIPAL -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-700 to-emerald-600 p-6 text-white shadow-xl shadow-teal-900/10">
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold bg-white/20 text-white backdrop-blur-md uppercase tracking-wider mb-2">
                    <i class="bi bi-wallet2"></i> FINANZAS Y COBROS
                </span>
                <h1 class="text-2xl font-black uppercase tracking-tight">CAJA CENTRAL Y COBROS PENDIENTES</h1>
                <p class="text-xs text-teal-100 font-medium mt-1">
                    RECEPCIÓN DE PAGOS, EMISIÓN DE COMPROBANTES Y ENVÍO A DESPACHO.
                </p>
            </div>
        </div>
    </div>

    <!-- ESTRUCTURA PRINCIPAL DE CAJA (2 COLUMNAS) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- COLUMNA IZQUIERDA: COBROS Y BÚSQUEDA (8 COLS) -->
        <div class="lg:col-span-8 space-y-4">

            <!-- ESCÁNER Y BÚSQUEDA RÁPIDA DE COMPROBANTES -->
            <div class="bg-white p-5 rounded-3xl border-2 border-teal-200 shadow-sm space-y-3">
                <label class="block text-[11px] font-extrabold text-gray-700 uppercase flex items-center gap-1.5">
                    <i class="bi bi-upc-scan text-teal-600 text-base"></i> ESCANEAR COMPROBANTE
                </label>
                
                <div class="relative">
                    <i class="bi bi-upc absolute left-4 top-3.5 text-gray-400 text-sm"></i>
                    <input type="text" 
                           x-ref="scanInput" 
                           x-model="codigoEscaneado" 
                           @keydown.enter.prevent="buscarPorCodigo()" 
                           :disabled="buscando"
                           placeholder="PASE EL LECTOR SOBRE EL CÓDIGO DE BARRAS (TCK-...)" 
                           class="w-full pl-11 pr-4 py-3 rounded-2xl border border-gray-200 text-sm font-black uppercase tracking-wider focus:ring-2 focus:ring-teal-500 focus:outline-none placeholder:text-gray-400 placeholder:font-bold placeholder:text-xs">
                </div>

                <!-- OPCIONES DE COBRO CON CÁMARA Y MÉTODO -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                    <div class="flex items-center gap-3">
                        <!-- BOTÓN DE ACTIVAR CÁMARA -->
                        <button type="button" 
                                @click="camaraActiva ? detenerCamara() : iniciarCamara()" 
                                class="px-4 py-2.5 rounded-2xl text-xs font-black uppercase flex items-center justify-center gap-2 cursor-pointer transition-all shadow-xs active:scale-95"
                                :class="camaraActiva ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-teal-600 hover:bg-teal-700 text-white'">
                            <i class="bi" :class="camaraActiva ? 'bi-camera-video-off' : 'bi-camera-fill'"></i>
                            <span x-text="camaraActiva ? 'DETENER CÁMARA' : 'ESCANEAR CON CÁMARA'"></span>
                        </button>

                        <label class="flex items-center gap-2 text-[11px] font-extrabold text-gray-700 uppercase cursor-pointer select-none">
                            <input type="checkbox" x-model="cobroAutomatico" class="w-4 h-4 accent-teal-600 rounded-md">
                            <span>COBRAR AL ESCANEAR</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <template x-for="m in ['Efectivo', 'Tarjeta', 'Transferencia']" :key="m">
                            <button type="button" 
                                    @click="metodoPago = m" 
                                    class="px-3 py-1.5 rounded-xl border text-[10px] font-black uppercase transition-all cursor-pointer"
                                    :class="metodoPago === m ? 'bg-teal-600 border-teal-600 text-white shadow-xs' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'" 
                                    x-text="m"></button>
                        </template>
                    </div>
                </div>

                <!-- CONTENEDOR DE LA CÁMARA WEB / MOVIL -->
                <div x-show="camaraActiva" x-cloak class="mt-3 rounded-2xl overflow-hidden border border-gray-200 bg-slate-900 p-2">
                    <div id="lector-camara-caja" class="w-full max-h-64 rounded-xl overflow-hidden"></div>
                </div>
            </div>

            <!-- LISTADO DE TICKETS PENDIENTES POR COBRAR -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-extrabold text-gray-800 uppercase flex items-center gap-2">
                        <i class="bi bi-clock-history text-amber-500"></i> TICKETS PENDIENTES POR COBRAR
                    </h3>
                    <span class="text-xs font-bold text-gray-500 bg-gray-50 px-3 py-1 rounded-full border border-gray-200"
                          x-text="`${ticketsPendientes.length} PENDIENTES`"></span>
                </div>

                <!-- BUSCADOR INTERNO EN LISTA -->
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="BUSCAR TICKET POR CÓDIGO O PACIENTE..." 
                           class="w-full bg-slate-50 border border-gray-200 focus:border-teal-500 focus:bg-white text-xs font-bold rounded-2xl pl-10 pr-4 py-3 focus:outline-none uppercase placeholder:text-gray-400 transition-all">
                </div>

                <!-- GRILLA DE TICKETS PENDIENTES -->
                <div class="space-y-2.5 max-h-[420px] overflow-y-auto pr-1">
                    <template x-for="tck in ticketsFiltrados()" :key="tck.id">
                        <div class="p-4 rounded-2xl border border-gray-100 bg-slate-50/50 hover:bg-teal-50/30 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-teal-800 text-xs" x-text="tck.codigo_ticket"></span>
                                    <span class="text-[9px] font-black bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-md uppercase flex items-center gap-1">
                                        <i class="bi bi-hourglass-split"></i> EXPIRA: <span x-text="formatearHoraExpiracion(tck.expires_at)"></span>
                                    </span>
                                </div>
                                <p class="text-xs font-black text-gray-800 uppercase" x-text="`PACIENTE: ${tck.paciente ? (tck.paciente.nombre_completo || tck.paciente.primer_nombre) : 'PÚBLICO GENERAL'}`"></p>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase" x-text="`MEDICAMENTOS: ${tck.detalles ? tck.detalles.length : 0} PZAS.`"></p>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0">
                                <div class="text-right">
                                    <span class="block text-[9px] font-extrabold text-gray-400 uppercase">TOTAL</span>
                                    <span class="text-lg font-black text-teal-800" x-text="`$${parseFloat(tck.monto_total).toFixed(2)}`"></span>
                                </div>

                                <button type="button" 
                                        @click="abrirModalCobro(tck)" 
                                        class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-xl text-xs uppercase shadow-md shadow-teal-600/20 active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer">
                                    <i class="bi bi-wallet2"></i> COBRAR
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="ticketsFiltrados().length === 0">
                        <div class="py-10 text-center text-gray-400 space-y-1">
                            <i class="bi bi-inbox text-3xl block text-gray-300"></i>
                            <p class="text-xs font-extrabold uppercase">NO HAY TICKETS PENDIENTES DE PAGO</p>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA: HISTORIAL DE COBROS DE HOY (4 COLS) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-xs font-black text-gray-800 uppercase flex items-center gap-2">
                        <i class="bi bi-receipt text-emerald-600 text-base"></i> COBROS REALIZADOS HOY
                    </h3>
                </div>

                <!-- LISTADO DE COBROS RECIENTES -->
                <div class="space-y-2 max-h-[500px] overflow-y-auto pr-1">
                    <template x-for="cobro in cobrosHoy" :key="cobro.id">
                        <div class="p-3 bg-slate-50 rounded-2xl border border-gray-100 flex items-center justify-between gap-2 text-xs hover:border-teal-200 transition-all group">
                            <div class="space-y-0.5 min-w-0">
                                <span class="font-black text-teal-800 block truncate" x-text="cobro.ticket ? cobro.ticket.codigo_ticket : 'TICKET'"></span>
                                <p class="text-[10px] font-bold text-gray-500 uppercase truncate" x-text="cobro.concepto"></p>
                                <p class="text-[9px] font-extrabold text-gray-400 uppercase" x-text="`MÉSTATIC: ${cobro.metodo_pago}`"></p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="font-black text-gray-900 block" x-text="`$${parseFloat(cobro.monto).toFixed(2)}`"></span>
                                
                                <!-- BOTÓN REIMPRIMIR FUNCIONAL -->
                                <button type="button" 
                                        @click="reimprimirComprobante(cobro)" 
                                        class="text-[9px] font-extrabold text-teal-600 hover:text-teal-800 uppercase underline flex items-center justify-end gap-1 mt-1 cursor-pointer">
                                    <i class="bi bi-printer"></i> REIMPRIMIR
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="cobrosHoy.length === 0">
                        <div class="py-8 text-center text-gray-400 space-y-1">
                            <i class="bi bi-wallet-2 text-2xl block text-gray-300"></i>
                            <p class="text-[11px] font-extrabold uppercase">NO SE HAN REGISTRADO COBROS HOY</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

    <!-- INCLUSIÓN MODULAR DEL MODAL DE COBRO E IMPRESIÓN -->
    @include('caja.modalCobroTicket')

</div>

<!-- LIBRERÍA HTML5-QRCODE PARA LECTURA POR CÁMARA -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
function cajaApp() {
    return {
        searchQuery: '',
        codigoEscaneado: '',
        cobroAutomatico: false,
        metodoPago: 'Efectivo',
        buscando: false,
        procesandoPago: false,
        camaraActiva: false,
        html5QrcodeScanner: null,
        
        ticketsPendientes: @json($ticketsPendientes ?? []),
        cobrosHoy: @json($cobrosHoy ?? []),

        openCobroModal: false,
        ticketSeleccionado: null,

        init() {
            this.$nextTick(() => {
                if (this.$refs.scanInput) this.$refs.scanInput.focus();
            });
        },

        iniciarCamara() {
            this.camaraActiva = true;
            this.$nextTick(() => {
                this.html5QrcodeScanner = new Html5Qrcode("lector-camara-caja");
                this.html5QrcodeScanner.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 150 } },
                    (decodedText) => {
                        this.codigoEscaneado = decodedText;
                        this.detenerCamara();
                        this.buscarPorCodigo();
                    },
                    (errorMessage) => { /* Silenciar lectura fallida */ }
                ).catch(err => {
                    console.error("Error al iniciar cámara: ", err);
                    this.camaraActiva = false;
                    if (typeof window.notificar === 'function') {
                        window.notificar('NO SE PUDO ACCEDER A LA CÁMARA', 'error');
                    }
                });
            });
        },

        detenerCamara() {
            if (this.html5QrcodeScanner) {
                this.html5QrcodeScanner.stop().then(() => {
                    this.html5QrcodeScanner.clear();
                    this.camaraActiva = false;
                }).catch(err => {
                    this.camaraActiva = false;
                });
            } else {
                this.camaraActiva = false;
            }
        },

        ticketsFiltrados() {
            const q = this.searchQuery.trim().toUpperCase();
            if (!q) return this.ticketsPendientes;
            return this.ticketsPendientes.filter(t => {
                const cod = (t.codigo_ticket || '').toUpperCase();
                const pac = t.paciente ? (t.paciente.nombre_completo || t.paciente.primer_nombre || '').toUpperCase() : 'PÚBLICO GENERAL';
                return cod.includes(q) || pac.includes(q);
            });
        },

        formatearHoraExpiracion(fechaStr) {
            if (!fechaStr) return 'N/A';
            const f = new Date(fechaStr);
            return f.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true });
        },

        abrirModalCobro(ticket) {
            this.ticketSeleccionado = ticket;
            this.openCobroModal = true;
        },

        async buscarPorCodigo() {
            const codigo = this.codigoEscaneado.trim();
            if (!codigo) return;

            this.buscando = true;

            try {
                const response = await fetch(`{{ url('/caja/buscar-codigo') }}/${codigo}`);
                const data = await response.json();

                if (data.status === 'success') {
                    this.ticketSeleccionado = data.ticket;
                    this.codigoEscaneado = '';

                    if (this.cobroAutomatico) {
                        await this.procesarCobroDirecto(data.ticket.id, this.metodoPago);
                    } else {
                        this.openCobroModal = true;
                    }
                } else {
                    if (typeof window.notificar === 'function') {
                        window.notificar(data.message || 'TICKET NO ENCONTRADO O EXPIRADO', 'error');
                    }
                }
            } catch (e) {
                console.error(e);
                if (typeof window.notificar === 'function') {
                    window.notificar('ERROR AL BUSCAR CÓDIGO', 'error');
                }
            } finally {
                this.buscando = false;
            }
        },

        async procesarCobroDirecto(ticketId, metodo) {
            this.procesandoPago = true;

            try {
                const response = await fetch(`{{ url('/caja/procesar-pago') }}/${ticketId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ metodo_pago: metodo })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    this.ticketSeleccionado = data.ticket;
                    this.openCobroModal = true;
                    
                    this.ticketsPendientes = this.ticketsPendientes.filter(t => t.id !== ticketId);
                    this.cobrosHoy.unshift({
                        id: Date.now(),
                        monto: data.ticket.monto_total,
                        metodo_pago: metodo,
                        concepto: `PAGO DE TICKET DE FARMACIA: ${data.ticket.codigo_ticket}`,
                        ticket: data.ticket
                    });

                    if (typeof window.notificar === 'function') {
                        window.notificar('PAGO REGISTRADO CON ÉXITO', 'success');
                    }
                } else {
                    if (typeof window.notificar === 'function') {
                        window.notificar(data.message || 'OCURRIÓ UN ERROR AL COBRAR', 'error');
                    }
                }
            } catch (e) {
                console.error(e);
                if (typeof window.notificar === 'function') {
                    window.notificar('ERROR DE CONEXIÓN CON EL SERVIDOR', 'error');
                }
            } finally {
                this.procesandoPago = false;
            }
        },

        reimprimirComprobante(cobro) {
            if (cobro && cobro.ticket) {
                this.ticketSeleccionado = cobro.ticket;
                this.openCobroModal = true;
            } else {
                if (typeof window.notificar === 'function') {
                    window.notificar('NO SE ENCONTRÓ EL DETALLE DEL TICKET PARA REIMPRIMIR', 'warning');
                }
            }
        },

        imprimirComprobante() {
            window.print();
        }
    }
}
</script>
@endsection