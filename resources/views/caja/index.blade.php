@extends('layouts.admin')

@section('content')
<div class="space-y-6 w-full" x-data="cajaApp()">
    
    <!-- BANNER CAJA CENTRAL -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-teal-700 to-teal-600 p-6 text-white shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-white border border-white/20 uppercase tracking-wider mb-2">
                    <i class="bi bi-cash-coin"></i> FINANZAS Y COBROS
                </span>
                <h1 class="text-2xl font-extrabold uppercase tracking-tight">CAJA CENTRAL Y COBROS PENDIENTES</h1>
                <p class="text-teal-100 text-xs font-semibold mt-0.5">RECEPCIÓN DE PAGOS, EMISIÓN DE COMPROBANTES Y ENVÍO A DESPACHO.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LISTA DE COBROS PENDIENTES (8 COLS) -->
        <div class="lg:col-span-8 space-y-4">

            <!-- LECTOR DE CÓDIGO DE BARRAS -->
            <!-- El lector actúa como teclado: escribe el folio y manda ENTER solo -->
            <div class="bg-white p-5 rounded-3xl border-2 border-teal-200 shadow-sm space-y-2">
                <label class="block text-[11px] font-extrabold text-gray-700 uppercase flex items-center gap-1.5">
                    <i class="bi bi-upc-scan text-teal-600 text-base"></i> ESCANEAR COMPROBANTE
                </label>
                <div class="relative">
                    <i class="bi bi-upc absolute left-4 top-3.5 text-gray-400 text-sm"></i>
                    <input type="text"
                           x-ref="scan"
                           x-model="codigoEscaneado"
                           @keydown.enter.prevent="escanearTicket()"
                           :disabled="buscandoTicket"
                           autocomplete="off"
                           placeholder="PASE EL LECTOR SOBRE EL CÓDIGO DE BARRAS (TCK-...)"
                           class="w-full pl-11 pr-4 py-3 rounded-2xl border border-gray-200 text-sm font-black uppercase tracking-wider focus:ring-2 focus:ring-teal-500 focus:outline-none placeholder:text-gray-400 placeholder:font-bold placeholder:text-xs">
                </div>
                <!-- CÁMARA DEL TELÉFONO + COBRO AUTOMÁTICO -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 pt-1">
                    <button type="button" @click="camaraActiva ? detenerCamara() : iniciarCamara()"
                            class="px-4 py-2.5 rounded-2xl text-xs font-black uppercase flex items-center justify-center gap-2 cursor-pointer transition-all"
                            :class="camaraActiva ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-teal-600 hover:bg-teal-700 text-white'">
                        <i class="bi" :class="camaraActiva ? 'bi-camera-video-off' : 'bi-camera'"></i>
                        <span x-text="camaraActiva ? 'DETENER CÁMARA' : 'ESCANEAR CON CÁMARA'"></span>
                    </button>

                    <label class="flex items-center gap-2 text-[11px] font-extrabold text-gray-700 uppercase cursor-pointer select-none">
                        <input type="checkbox" x-model="cobroAutomatico" class="w-4 h-4 accent-teal-600">
                        COBRAR AL ESCANEAR
                    </label>
                </div>

                <!-- MÉTODO QUE SE USA EN EL COBRO AUTOMÁTICO -->
                <div x-show="cobroAutomatico" class="grid grid-cols-3 gap-2">
                    <template x-for="m in ['Efectivo','Tarjeta','Transferencia']" :key="m">
                        <button type="button" @click="metodoPago = m"
                                class="py-2 rounded-xl border text-[10px] font-black uppercase cursor-pointer transition-all"
                                :class="metodoPago === m ? 'bg-teal-600 border-teal-600 text-white' : 'bg-white border-gray-200 text-gray-600'"
                                x-text="m"></button>
                    </template>
                </div>

                <!-- VISTA DE LA CÁMARA -->
                <div x-show="camaraActiva" x-cloak class="rounded-2xl overflow-hidden border border-gray-200 bg-black">
                    <div id="lector-camara" class="w-full"></div>
                </div>

                <p x-show="buscandoTicket || cargando" class="text-[11px] font-bold text-teal-700 uppercase">PROCESANDO TICKET...</p>
                <p x-show="errorEscaneo" x-cloak class="text-[11px] font-extrabold text-rose-600 uppercase flex items-center gap-1.5">
                    <i class="bi bi-exclamation-triangle-fill"></i><span x-text="errorEscaneo"></span>
                </p>
                <div x-show="ultimoCobro" x-cloak class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-extrabold uppercase flex items-center justify-between gap-2">
                    <span class="flex items-center gap-1.5"><i class="bi bi-check-circle-fill"></i> <span x-text="ultimoCobro"></span></span>
                    <button type="button" x-show="ticketCobrado" @click="openTicketPagadoModal = true"
                            class="text-[10px] underline cursor-pointer">VER COMPROBANTE</button>
                </div>
            </div>

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
                    <div x-show="!pagados.includes({{ $tck->id }}) && cumpleFiltro('{{ strtoupper($tck->codigo_ticket) }}', '{{ strtoupper($tck->paciente ? $tck->paciente->nombre_completo : 'PÚBLICO GENERAL') }}')"
                         class="p-4 rounded-2xl border border-gray-100 bg-slate-50/50 hover:border-teal-200 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black text-teal-800 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-200">
                                    {{ $tck->codigo_ticket }}
                                </span>
                                <!-- BADGE DE TIEMPO / VIGENCIA -->
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                    <i class="bi bi-hourglass-split"></i> EXPIRA: {{ $tck->expires_at ? $tck->expires_at->format('H:i') : 'N/A' }}
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

    <!-- INCLUSIÓN MODULAR DE MODALES DE COBRO Y COMPROBANTE OFICIAL -->
    @include('caja.modalCobroTicket')

</div>

<!-- LECTOR DE CÓDIGOS CON LA CÁMARA (CODE128) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
function cajaApp() {
    return {
        searchQuery: '',
        openModal: false,
        openTicketPagadoModal: false,
        ticketSeleccionado: {},
        ticketCobrado: null,
        metodoPago: 'Efectivo',
        cargando: false,

        // --- LECTOR DE CÓDIGO DE BARRAS (PISTOLA O CÁMARA DEL TELÉFONO) ---
        codigoEscaneado: '',
        buscandoTicket: false,
        errorEscaneo: '',
        ultimoCobro: '',
        cobroAutomatico: true,
        camaraActiva: false,
        lectorCamara: null,
        ultimoFolioLeido: '',
        ultimaLecturaMs: 0,
        pagados: [],
        urlBuscarTicket: "{{ route('caja.buscar-ticket', '__FOLIO__') }}",
        urlPagar: "{{ route('caja.procesar-pago', '__ID__') }}",

        init() {
            this.enfocarLector();

            // Al cerrar cualquiera de los modales, el cursor regresa al lector
            this.$watch('openModal', abierto => { if (!abierto) this.enfocarLector(); });
            this.$watch('openTicketPagadoModal', abierto => { if (!abierto) this.enfocarLector(); });
        },

        enfocarLector() {
            // En el teléfono no se enfoca el campo para que no salga el teclado
            if (this.camaraActiva) return;
            this.$nextTick(() => this.$refs.scan && this.$refs.scan.focus());
        },

        normalizarFolio(valor) {
            // Lector en inglés + teclado en español => el guion llega como apóstrofo
            return (valor || '').trim().toUpperCase().replace(/'/g, '-');
        },

        // Lectura con pistola (teclado + ENTER)
        escanearTicket() {
            const folio = this.normalizarFolio(this.codigoEscaneado);
            this.codigoEscaneado = '';
            this.procesarFolio(folio);
        },

        // Flujo común: buscar ticket y cobrarlo automáticamente o abrir el modal
        async procesarFolio(folio) {
            if (!folio || this.buscandoTicket || this.cargando) return;

            this.errorEscaneo = '';
            this.ultimoCobro = '';
            this.buscandoTicket = true;

            try {
                const res = await fetch(this.urlBuscarTicket.replace('__FOLIO__', encodeURIComponent(folio)), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.status !== 'success') {
                    this.mostrarError(data.message || 'NO SE PUDO LEER EL TICKET.');
                    return;
                }

                if (this.cobroAutomatico) {
                    this.ticketSeleccionado = data.ticket;
                    this.buscandoTicket = false;
                    // En automático no se abre el comprobante, para seguir escaneando sin tocar la pantalla
                    await this.confirmarPago({ mostrarComprobante: !this.camaraActiva });
                } else {
                    this.abrirModalCobro(data.ticket);
                }
            } catch (e) {
                console.error(e);
                this.mostrarError('ERROR DE CONEXIÓN AL BUSCAR EL TICKET.');
            } finally {
                this.buscandoTicket = false;
            }
        },

        mostrarError(mensaje) {
            this.errorEscaneo = mensaje;
            if (window.notificar) window.notificar(mensaje, 'error');
            if (navigator.vibrate) navigator.vibrate([100, 60, 100]);
            this.enfocarLector();
        },

        // --- CÁMARA DEL TELÉFONO ---
        async iniciarCamara() {
            this.errorEscaneo = '';

            if (!window.isSecureContext) {
                this.mostrarError('LA CÁMARA SOLO FUNCIONA CON HTTPS. ABRA EL SISTEMA CON UNA DIRECCIÓN https://');
                return;
            }
            if (typeof Html5Qrcode === 'undefined') {
                this.mostrarError('NO SE CARGÓ LA LIBRERÍA DEL LECTOR. REVISE LA CONEXIÓN A INTERNET.');
                return;
            }

            this.camaraActiva = true;
            await this.$nextTick();

            try {
                this.lectorCamara = new Html5Qrcode('lector-camara', {
                    formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128],
                    verbose: false
                });

                await this.lectorCamara.start(
                    { facingMode: 'environment' }, // cámara trasera
                    {
                        fps: 10,
                        // Recuadro ancho y bajo: es la forma de un código de barras
                        qrbox: (w, h) => ({ width: Math.floor(w * 0.9), height: Math.floor(Math.min(h, w) * 0.35) })
                    },
                    texto => this.alLeerConCamara(texto),
                    () => {} // cuadros sin código: se ignoran
                );
            } catch (e) {
                console.error(e);
                this.camaraActiva = false;
                this.lectorCamara = null;
                this.mostrarError('NO SE PUDO ABRIR LA CÁMARA. REVISE QUE EL NAVEGADOR TENGA PERMISO.');
            }
        },

        async detenerCamara() {
            if (this.lectorCamara) {
                try { await this.lectorCamara.stop(); this.lectorCamara.clear(); } catch (e) {}
            }
            this.lectorCamara = null;
            this.camaraActiva = false;
            this.enfocarLector();
        },

        alLeerConCamara(texto) {
            const folio = this.normalizarFolio(texto);
            const ahora = Date.now();

            // La cámara lee el mismo código varias veces por segundo: se ignora
            // el mismo folio durante 4 s para no mandarlo repetido
            if (folio === this.ultimoFolioLeido && ahora - this.ultimaLecturaMs < 4000) return;

            this.ultimoFolioLeido = folio;
            this.ultimaLecturaMs = ahora;
            if (navigator.vibrate) navigator.vibrate(80);

            this.procesarFolio(folio);
        },

        cumpleFiltro(codigo, paciente) {
            const q = this.searchQuery.trim().toUpperCase();
            return !q || codigo.includes(q) || paciente.includes(q);
        },

        abrirModalCobro(ticket) {
            this.ticketSeleccionado = ticket;
            this.errorEscaneo = '';
            this.openModal = true;
        },

        async confirmarPago({ mostrarComprobante = true } = {}) {
            if (!this.ticketSeleccionado.id || this.cargando) return;
            this.cargando = true;

            try {
                const res = await fetch(this.urlPagar.replace('__ID__', this.ticketSeleccionado.id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ metodo_pago: this.metodoPago })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    if (window.notificar) window.notificar(data.message, 'success');
                    this.openModal = false;

                    this.ticketCobrado = data.ticket;
                    this.pagados.push(data.ticket.id);
                    this.ultimoCobro = `${data.ticket.codigo_ticket} COBRADO · $${parseFloat(data.ticket.monto_total).toFixed(2)} · ${this.metodoPago.toUpperCase()}`;
                    if (navigator.vibrate) navigator.vibrate(200);

                    // Mostrar el modal con el ticket oficial pagado
                    if (mostrarComprobante) this.openTicketPagadoModal = true;
                    else this.enfocarLector();
                } else {
                    this.mostrarError(data.message || 'NO SE PUDO REGISTRAR EL COBRO.');
                }
            } catch (e) {
                console.error(e);
                this.mostrarError('ERROR DE CONEXIÓN AL PROCESAR EL PAGO');
            } finally {
                this.cargando = false;
            }
        },

        imprimirComprobante() {
            window.print();
        }
    }
}
</script>
@endsection