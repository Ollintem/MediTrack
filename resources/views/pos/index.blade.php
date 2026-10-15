@extends('layouts.admin')

@section('title', 'Punto de Venta (POS) - MediTrack')

@section('content')
<div class="space-y-6 w-full block" x-data="posApp()">

    <!-- BANNER HERO PRINCIPAL -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-700 to-emerald-600 p-6 text-white shadow-xl shadow-teal-900/10">
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold bg-white/20 text-white backdrop-blur-md uppercase tracking-wider mb-2">
                    <i class="bi bi-shop"></i> MOSTRADOR / FILTRO DE FARMACIA
                </span>
                <h1 class="text-2xl font-black uppercase tracking-tight">PUNTO DE VENTA (POS)</h1>
                <p class="text-xs text-teal-100 font-medium mt-1">
                    SELECCIONA MEDICAMENTOS Y GENERA EL COMPROBANTE DE FILTRO PARA PAGO EN CAJA.
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- BOTÓN HISTORIAL DE TICKETS GENERADOS -->
                <button type="button" 
                        @click="openHistorialModal = true"
                        class="px-4 py-2.5 bg-white/15 hover:bg-white/25 text-white rounded-2xl text-xs font-black uppercase flex items-center gap-2 backdrop-blur-md border border-white/20 transition-all shadow-sm cursor-pointer active:scale-95">
                    <i class="bi bi-receipt-cutoff text-amber-300"></i>
                    <span>CONSULTAR TICKETS GENERADOS</span>
                </button>

                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md p-2.5 px-4 rounded-2xl border border-white/20">
                    <div class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center font-black text-sm">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <span class="block text-[9px] font-bold text-teal-200 uppercase">RESERVA DE STOCK</span>
                        <span class="text-[11px] font-black uppercase text-amber-300">25 MINUTOS DE VIGENCIA</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE PEDIDOS POR ENTREGAR CON BUSCADOR RÁPIDO TCK- Y BOTÓN ENTREGAR -->
    <div x-show="obtenerTicketsPorEntregar().length > 0" x-cloak class="bg-gradient-to-r from-emerald-50 via-teal-50 to-white border border-emerald-200 p-5 rounded-3xl shadow-xs space-y-3">
        <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-emerald-200/60 pb-3 gap-3">
            <h3 class="text-xs font-black text-emerald-900 uppercase flex items-center gap-2">
                <i class="bi bi-box-seam-fill text-emerald-600 text-sm"></i> 
                <span>PEDIDOS POR ENTREGAR (PAGADOS)</span>
                <span class="bg-emerald-200 text-emerald-900 text-[10px] font-extrabold px-2 py-0.5 rounded-full" x-text="`${obtenerTicketsPorEntregar().length} LISTOS`"></span>
            </h3>

            <!-- BUSCADOR CON PREFIJO TCK- Y ACCIÓN DE ENTREGA -->
            <div class="flex items-center gap-2">
                <div class="relative flex items-center bg-white border border-emerald-300 rounded-2xl overflow-hidden focus-within:ring-2 focus-within:ring-emerald-500 transition-all shadow-xs">
                    <span class="bg-emerald-100 text-emerald-900 font-black text-xs px-3 py-2 select-none border-r border-emerald-200">
                        TCK-
                    </span>
                    <input type="text" 
                           x-model="codigoRapidoIngresado" 
                           @keydown.enter.prevent.stop="entregarPorCodigoRapido()"
                           maxlength="10"
                           placeholder="XOFNZS..." 
                           class="w-32 sm:w-36 bg-transparent px-3 py-1.5 text-xs font-black text-gray-900 uppercase focus:outline-none placeholder:text-gray-400">
                </div>

                <button type="button" 
                        @click.prevent.stop="entregarPorCodigoRapido()"
                        :disabled="!codigoRapidoIngresado.trim() || procesandoEntrega"
                        class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 disabled:bg-gray-300 text-white font-extrabold text-[10px] uppercase rounded-2xl shadow-md active:scale-95 transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="bi bi-box-arrow-right text-xs"></i>
                    <span x-text="procesandoEntrega ? 'SURTIENDO...' : 'ENTREGAR'"></span>
                </button>
            </div>

            <span class="text-[10px] font-bold text-emerald-700 uppercase hidden lg:inline">SURTIR Y ENTREGAR MEDICAMENTO AL CLIENTE</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[220px] overflow-y-auto pr-1">
            <template x-for="tck in obtenerTicketsPorEntregar()" :key="tck.id">
                <div class="p-3 bg-white rounded-2xl border border-emerald-100 shadow-xs flex items-center justify-between gap-2">
                    <div class="space-y-0.5 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="font-black text-emerald-800 text-xs truncate" x-text="tck.codigo_ticket || tck.codigo"></span>
                            <span class="text-[9px] font-black bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded-md uppercase">PAGADO</span>
                        </div>
                        <p class="text-[10px] font-semibold text-gray-500 uppercase truncate" x-text="`PACIENTE: ${tck.paciente ? (tck.paciente.nombre_completo || tck.paciente.primer_nombre) : 'PÚBLICO GENERAL'}`"></p>
                        <p class="text-[10px] font-black text-teal-800" x-text="`$${parseFloat(tck.monto_total || 0).toFixed(2)}`"></p>
                    </div>

                    <button type="button" 
                            @click.prevent.stop="marcarComoEntregado(tck)" 
                            :disabled="procesandoEntregado === tck.id"
                            class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 text-white font-extrabold rounded-xl text-[10px] uppercase transition-all shadow-xs active:scale-95 flex items-center gap-1 cursor-pointer shrink-0">
                        <i class="bi bi-check2-circle text-xs"></i>
                        <span x-text="procesandoEntregado === tck.id ? 'SURTIENDO...' : 'ENTREGAR'"></span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- PANEL PRINCIPAL POS (CATÁLOGO + CARRITO) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- CATÁLOGO Y BUSCADOR (COLUMNA IZQUIERDA - 7 COLS) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                
                <!-- BUSCADOR CON LECTOR EAN-13 / NOMBRE -->
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           @keydown.enter.prevent="buscarPorCodigoExacto()"
                           placeholder="ESCANEAR CÓDIGO DE BARRAS O BUSCAR MEDICAMENTO..." 
                           class="w-full bg-slate-50 border border-gray-200 focus:border-teal-500 focus:bg-white text-xs font-bold rounded-2xl pl-10 pr-4 py-3 focus:outline-none uppercase placeholder:text-gray-400 transition-all">
                </div>

                <!-- LISTA DE PRODUCTOS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[500px] overflow-y-auto pr-1">
                    @foreach($productos as $prod)
                    <div x-show="cumpleFiltro('{{ strtoupper($prod->codigo ?? $prod->codigo_barras ?? '') }}', '{{ strtoupper($prod->nombre) }}')"
                         @click="agregarAlCarrito({{ json_encode($prod) }})"
                         class="p-4 rounded-2xl border transition-all flex flex-col justify-between space-y-2 select-none {{ ($prod->stock_disponible ?? $prod->stock_actual) <= 0 ? 'bg-gray-100/70 border-gray-200 opacity-60 cursor-not-allowed' : 'bg-slate-50/50 hover:bg-teal-50/50 hover:border-teal-200 border-gray-100 cursor-pointer' }}">
                        <div>
                            <div class="flex justify-between items-start gap-1">
                                <span class="text-[10px] font-extrabold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">
                                    {{ strtoupper($prod->codigo ?? $prod->codigo_barras ?? 'S/C') }}
                                </span>
                                <span class="text-xs font-black text-gray-800">
                                    ${{ number_format($prod->precio_venta, 2) }}
                                </span>
                            </div>
                            <h4 class="text-xs font-extrabold text-gray-800 uppercase mt-2 line-clamp-2">
                                {{ strtoupper($prod->nombre) }}
                            </h4>
                        </div>

                        <!-- REPRESENTACIÓN DE STOCK -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200/60 text-xs">
                            <span class="text-[10px] font-bold text-gray-400">DISPONIBLE:</span>
                            <div class="flex items-baseline gap-1">
                                @if(($prod->stock_disponible ?? $prod->stock_actual) <= 0)
                                    <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200 uppercase">AGOTADO</span>
                                @else
                                    <span class="font-black text-gray-800">
                                        {{ $prod->stock_disponible ?? $prod->stock_actual }} PZAS.
                                    </span>
                                    <span class="text-[10px] font-semibold text-gray-400/80">
                                        ({{ $prod->stock_actual }} REAL)
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- CARRITO DE COMPRA (COLUMNA DERECHA - 5 COLS) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col justify-between min-h-[500px]">
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="text-sm font-extrabold text-gray-800 uppercase flex items-center gap-2">
                            <i class="bi bi-cart-fill text-teal-600"></i> RESUMEN DE PEDIDO
                        </h3>
                        <button @click="vaciarCarrito()" class="text-[10px] font-extrabold text-rose-500 hover:text-rose-700 uppercase cursor-pointer">
                            VACIAR
                        </button>
                    </div>

                    <!-- PACIENTE OPCIONAL -->
                    <div>
                        <label class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1">PACIENTE (OPCIONAL):</label>
                        <select x-model="pacienteId" class="w-full bg-slate-50 border border-gray-200 text-xs font-bold rounded-2xl px-3 py-2.5 uppercase focus:outline-none focus:border-teal-500">
                            <option value="">PÚBLICO GENERAL</option>
                            @foreach($pacientes as $pac)
                            <option value="{{ $pac->id }}">{{ strtoupper($pac->nombre_completo ?? ($pac->primer_nombre . ' ' .$pac->apellido_paterno)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- ÍTEMS DEL CARRITO -->
                    <div class="space-y-2 max-h-[260px] overflow-y-auto pr-1">
                        <template x-for="(item, index) in carrito" :key="item.id">
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-gray-100 text-xs">
                                <div class="space-y-0.5 max-w-[160px]">
                                    <p class="font-extrabold text-gray-800 uppercase line-clamp-1" x-text="item.nombre"></p>
                                    <p class="text-[10px] font-bold text-teal-700" x-text="`$${item.precio.toFixed(2)}`"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="cambiarCantidad(index, -1)" class="w-6 h-6 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-gray-100 cursor-pointer">-</button>
                                    <span class="font-black text-gray-800 text-xs" x-text="item.cantidad"></span>
                                    <button @click="cambiarCantidad(index, 1)" class="w-6 h-6 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-gray-100 cursor-pointer">+</button>
                                    <button @click="removerItem(index)" class="text-rose-500 hover:text-rose-700 ml-1 cursor-pointer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="carrito.length === 0" class="text-center py-12 text-gray-400">
                            <i class="bi bi-cart-x text-3xl"></i>
                            <p class="text-xs font-bold uppercase mt-2">CARRITO VACÍO</p>
                        </div>
                    </div>
                </div>

                <!-- TOTAL Y ACCIÓN -->
                <div class="border-t border-gray-100 pt-4 space-y-4">
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-extrabold text-gray-400 uppercase">TOTAL A PAGAR:</span>
                        <span class="text-2xl font-black text-teal-700" x-text="`$${calcularTotal().toFixed(2)}`"></span>
                    </div>

                    <button @click="generarTicket()" 
                            :disabled="carrito.length === 0 || cargando"
                            class="w-full py-3.5 bg-teal-600 hover:bg-teal-700 disabled:bg-gray-300 text-white font-black rounded-2xl text-xs uppercase shadow-lg shadow-teal-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i class="bi bi-ticket-perforated-fill text-base"></i>
                        <span x-text="cargando ? 'GENERANDO...' : 'GENERAR TICKET (25 MIN)'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL HISTORIAL DE TICKETS GENERADOS -->
    <template x-teleport="body">
        <div x-show="openHistorialModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openHistorialModal" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="openHistorialModal = false"></div>

                <div x-show="openHistorialModal" class="inline-block w-full max-w-3xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100 p-6 space-y-4">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h3 class="text-sm font-black text-gray-800 uppercase flex items-center gap-2">
                            <i class="bi bi-receipt text-teal-600"></i> HISTORIAL DE TICKETS GENERADOS
                        </h3>
                        <button type="button" @click="openHistorialModal = false" class="text-gray-400 hover:text-gray-600 font-bold focus:outline-none cursor-pointer">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <div class="sm:col-span-8 relative">
                            <i class="bi bi-search absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            <input type="text" x-model="searchTicketQuery" placeholder="BUSCAR POR FOLIO (TCK-...) O PACIENTE..." 
                                   class="w-full bg-slate-50 border border-gray-200 text-xs font-bold rounded-2xl pl-9 pr-3 py-2.5 uppercase focus:outline-none focus:border-teal-500">
                        </div>
                        <div class="sm:col-span-4">
                            <select x-model="filtroEstatus" class="w-full bg-slate-50 border border-gray-200 text-xs font-bold rounded-2xl px-3 py-2.5 uppercase focus:outline-none focus:border-teal-500">
                                <option value="">TODOS LOS ESTADOS</option>
                                <option value="pendiente">PENDIENTES</option>
                                <option value="pagado">PAGADOS</option>
                                <option value="entregado">ENTREGADOS</option>
                                <option value="expirado">EXPIRADOS</option>
                            </select>
                        </div>
                    </div>

                    <div class="max-h-[400px] overflow-y-auto pr-1 space-y-2">
                        <template x-for="tck in ticketsFiltrados()" :key="tck.id">
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100 flex items-center justify-between gap-3 text-xs">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-teal-800 text-xs" x-text="tck.codigo_ticket || tck.codigo"></span>
                                        <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-md uppercase"
                                              :class="{
                                                  'bg-amber-100 text-amber-800': tck.status === 'pendiente',
                                                  'bg-emerald-100 text-emerald-800': tck.status === 'pagado',
                                                  'bg-teal-100 text-teal-800': tck.status === 'entregado',
                                                  'bg-rose-100 text-rose-800': tck.status === 'expirado'
                                              }"
                                              x-text="tck.status"></span>
                                    </div>
                                    <p class="text-[10px] font-bold text-gray-600 uppercase truncate" x-text="`PACIENTE: ${tck.paciente ? (tck.paciente.nombre_completo || tck.paciente.primer_nombre) : 'PÚBLICO GENERAL'}`"></p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="font-black text-gray-900 text-xs mr-1" x-text="`$${parseFloat(tck.monto_total || 0).toFixed(2)}`"></span>
                                    
                                    <!-- BOTÓN ENTREGAR EN EL MODAL (SOLO VISIBLE SI ESTÁ PAGADO) -->
                                    <template x-if="tck.status === 'pagado'">
                                        <button type="button" 
                                                @click.prevent.stop="marcarComoEntregado(tck)" 
                                                :disabled="procesandoEntregado === tck.id"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-[10px] uppercase cursor-pointer flex items-center gap-1 shadow-xs transition-all">
                                            <i class="bi bi-box-arrow-right"></i>
                                            <span x-text="procesandoEntregado === tck.id ? 'SURTIENDO...' : 'ENTREGAR'"></span>
                                        </button>
                                    </template>

                                    <!-- BOTÓN VER / REIMPRIMIR -->
                                    <button type="button" 
                                            @click.prevent.stop="verDetalleTicket(tck)" 
                                            class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-xl text-[10px] uppercase cursor-pointer transition-all shadow-xs">
                                        VER
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="ticketsFiltrados().length === 0">
                            <div class="py-10 text-center text-gray-400 font-bold text-xs uppercase">
                                NO SE ENCONTRARON TICKETS REGISTRADOS
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @include('pos.modalTicket')

</div>

<script>
function posApp() {
    return {
        searchQuery: '',
        searchTicketQuery: '',
        filtroEstatus: '',
        codigoRapidoIngresado: '',
        pacienteId: '',
        carrito: [],
        cargando: false,
        procesandoEntrega: false,
        procesandoEntregado: null,
        openHistorialModal: false,
        openTicketModal: false,
        ticketGenerado: null,

        tickets: @json($ticketsRecientes ?? $tickets ?? []),

        obtenerTicketsPorEntregar() {
            return this.tickets.filter(t => t.status === 'pagado');
        },

        ticketsFiltrados() {
            let lista = this.tickets;
            const q = this.searchTicketQuery.trim().toUpperCase();
            const e = this.filtroEstatus.trim().toLowerCase();

            if (e !== '') {
                lista = lista.filter(t => (t.status || '').toLowerCase() === e);
            }

            if (q !== '') {
                lista = lista.filter(t => {
                    const cod = (t.codigo_ticket || t.codigo || '').toUpperCase();
                    const pac = t.paciente ? (t.paciente.nombre_completo || t.paciente.primer_nombre || '').toUpperCase() : 'PÚBLICO GENERAL';
                    return cod.includes(q) || pac.includes(q);
                });
            }

            return lista;
        },

        cumpleFiltro(codigo, nombre) {
            const q = this.searchQuery.trim().toUpperCase();
            if (!q) return true;
            return codigo.includes(q) || nombre.includes(q);
        },

        async entregarPorCodigoRapido() {
            let sufijo = this.codigoRapidoIngresado.trim().toUpperCase();
            if (!sufijo) return;

            const codigoCompleto = sufijo.startsWith('TCK-') ? sufijo : `TCK-${sufijo}`;
            this.procesandoEntrega = true;

            try {
                const response = await fetch(`{{ url('/pos/entregar-ticket') }}/${codigoCompleto}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (response.ok && data.status === 'success') {
                    this.codigoRapidoIngresado = '';
                    
                    // Actualizar estado local inmediatamente
                    const tck = this.tickets.find(t => (t.codigo_ticket || t.codigo) === codigoCompleto);
                    if (tck) tck.status = 'entregado';

                    if (typeof window.notificar === 'function') {
                        window.notificar(data.message || `TICKET ${codigoCompleto} ENTREGADO Y SURTIDO CON ÉXITO`, 'success');
                    } else {
                        alert(data.message || `TICKET ${codigoCompleto} ENTREGADO`);
                    }
                } else {
                    if (typeof window.notificar === 'function') {
                        window.notificar(data.message || 'TICKET NO ENCONTRADO O NO ESTÁ PAGADO', 'error');
                    } else {
                        alert(data.message || 'ERROR AL ENTREGAR TICKET');
                    }
                }
            } catch (e) {
                console.error(e);
                if (typeof window.notificar === 'function') {
                    window.notificar('ERROR AL PROCESAR LA ENTREGA EN EL SERVIDOR', 'error');
                } else {
                    alert('ERROR DE CONEXIÓN CON EL SERVIDOR');
                }
            } finally {
                this.procesandoEntrega = false;
            }
        },

        async marcarComoEntregado(tck) {
            if (!tck) return;
            this.procesandoEntregado = tck.id;
            const codigo = tck.codigo_ticket || tck.codigo || '';
            
            this.codigoRapidoIngresado = codigo.replace('TCK-', '');
            await this.entregarPorCodigoRapido();
            this.procesandoEntregado = null;
        },

        agregarAlCarrito(prod) {
            const stockDisponible = prod.stock_disponible ?? prod.stock_actual ?? 0;
            if (stockDisponible <= 0) return;

            const item = this.carrito.find(i => i.id === prod.id);
            if (item) {
                if (item.cantidad + 1 <= stockDisponible) item.cantidad++;
            } else {
                this.carrito.push({
                    id: prod.id,
                    nombre: prod.nombre,
                    precio: parseFloat(prod.precio_venta),
                    cantidad: 1,
                    stock_disponible: stockDisponible
                });
            }
        },

        cambiarCantidad(index, diff) {
            if (this.carrito[index].cantidad + diff > 0) {
                if (diff > 0 && this.carrito[index].cantidad + diff > this.carrito[index].stock_disponible) {
                    return;
                }
                this.carrito[index].cantidad += diff;
            } else {
                this.removerItem(index);
            }
        },

        removerItem(index) {
            this.carrito.splice(index, 1);
        },

        vaciarCarrito() {
            this.carrito = [];
        },

        calcularTotal() {
            return this.carrito.reduce((sum, i) => sum + (i.precio * i.cantidad), 0);
        },

        async generarTicket() {
            if (this.carrito.length === 0) return;
            this.cargando = true;

            try {
                const response = await fetch("{{ route('pos.generar-ticket') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        paciente_id: this.pacienteId || null,
                        carrito: this.carrito
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    this.ticketGenerado = data.ticket;
                    this.tickets.unshift(data.ticket);
                    this.vaciarCarrito();
                    this.openTicketModal = true;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.cargando = false;
            }
        },

        verDetalleTicket(ticket) {
            this.ticketGenerado = ticket;
            this.openHistorialModal = false;
            setTimeout(() => { this.openTicketModal = true; }, 100);
        }
    }
}
</script>
@endsection