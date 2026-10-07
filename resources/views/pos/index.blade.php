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
            
            <div class="flex flex-wrap items-center gap-3">
                <!-- BOTÓN HISTORIAL DE TICKETS GENERADOS -->
                <button type="button" 
                        @click="openHistorialModal = true" 
                        class="px-5 py-3 bg-white/15 hover:bg-white/25 text-white font-black rounded-2xl text-xs backdrop-blur-md border border-white/20 shadow-md transition-all flex items-center justify-center gap-2 active:scale-95 cursor-pointer">
                    <i class="bi bi-receipt-cutoff text-amber-300 text-base"></i>
                    <span>CONSULTAR TICKETS GENERADOS</span>
                </button>

                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20">
                    <div class="w-10 h-10 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center font-black text-lg">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold text-teal-200 uppercase">RESERVA DE STOCK</span>
                        <span class="text-xs font-black uppercase text-amber-300">25 MINUTOS DE VIGENCIA</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PANEL PRINCIPAL DEL POS (2 COLUMNAS) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- COLUMNA IZQUIERDA: CATÁLOGO Y BÚSQUEDA (7 COLS) -->
        <div class="lg:col-span-7 space-y-4">
            
            <!-- BUSCADOR PRINCIPAL -->
            <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-sm space-y-3">
                <label class="block text-[11px] font-extrabold text-gray-700 uppercase">BUSCAR MEDICAMENTO (NOMBRE O CÓDIGO DE BARRAS)</label>
                <div class="relative">
                    <i class="bi bi-search absolute left-4 top-3 text-gray-400 text-sm"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           @input="filtrarProductos()"
                           @keydown.enter.prevent="buscarPorCodigoExacto()"
                           placeholder="ESCRIBE NOMBRE O ESCANEA CÓDIGO DE BARRAS..." 
                           class="w-full pl-11 pr-4 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none transition-all">
                </div>
            </div>

            <!-- GRILLA DE PRODUCTOS -->
            <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-sm min-h-[420px] flex flex-col justify-between">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[500px] overflow-y-auto pr-1">
                    <template x-for="prod in productosFiltrados" :key="prod.id">
                        <div @click="agregarAlCarrito(prod)" 
                             class="p-3.5 rounded-2xl border border-gray-100 hover:border-teal-300 hover:bg-teal-50/40 transition-all cursor-pointer group flex flex-col justify-between space-y-2 relative overflow-hidden bg-white shadow-xs">
                            
                            <!-- ENCABEZADO Y NOMBRE -->
                            <div class="space-y-1">
                                <div class="flex justify-between items-start gap-1">
                                    <span class="text-[9px] font-black uppercase text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md truncate max-w-[120px]" 
                                          x-text="prod.categoria ? prod.categoria.nombre : 'MEDICAMENTO'"></span>
                                    <span class="text-[10px] font-extrabold uppercase text-gray-400" x-text="prod.codigo || prod.codigo_barras || 'S/C'"></span>
                                </div>
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-snug group-hover:text-teal-700 transition-colors" x-text="prod.nombre"></h4>
                            </div>

                            <!-- INFORMACIÓN DE STOCK -->
                            <div class="pt-2 border-t border-gray-100 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="block text-[9px] font-bold text-gray-400 uppercase">DISPONIBLE:</span>
                                        <span class="text-xs font-black" 
                                              :class="obtenerStockDisponible(prod) > 5 ? 'text-emerald-600' : 'text-rose-600'" 
                                              x-text="`${obtenerStockDisponible(prod)} PZAS.`"></span>
                                        
                                        <span class="text-[9px] font-extrabold text-gray-400/80 block" 
                                              x-text="`(${prod.stock_actual || 0} TOTAL)`"></span>
                                    </div>

                                    <span class="text-sm font-black text-teal-800" x-text="`$${parseFloat(prod.precio_venta).toFixed(2)}`"></span>
                                </div>

                                <template x-if="(prod.stock_reservado || 0) > 0">
                                    <div class="flex items-center gap-1 text-[9px] font-black text-amber-800 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200/60">
                                        <i class="bi bi-clock-history text-amber-600"></i>
                                        <span>APARTADO EN TICKET: <strong x-text="`${prod.stock_reservado} PZAS.`"></strong></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="productosFiltrados.length === 0">
                        <div class="col-span-2 py-12 text-center text-gray-400 space-y-2">
                            <i class="bi bi-box-seam text-3xl block text-gray-300"></i>
                            <p class="text-xs font-extrabold uppercase">NO SE ENCONTRARON MEDICAMENTOS DISPONIBLES</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: SELECCIÓN DE PACIENTE Y CARRITO (5 COLS) -->
        <div class="lg:col-span-5 space-y-4">
            
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                
                <!-- SELECCIÓN DE PACIENTE -->
                <div class="space-y-1">
                    <label class="block text-[11px] font-extrabold text-gray-700 uppercase">PACIENTE / CLIENTE *</label>
                    <select x-model="pacienteId" class="w-full p-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <option value="">PÚBLICO GENERAL</option>
                        @foreach($pacientes as $pac)
                            <option value="{{ $pac->id }}">{{ strtoupper($pac->nombre_completo ?? ($pac->primer_nombre . ' ' .$pac->apellido_paterno)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- TABLA DEL CARRITO DE COMPRAS -->
                <div class="border-t border-b border-gray-100 py-3 space-y-2">
                    <h3 class="text-xs font-black text-gray-800 uppercase flex items-center justify-between">
                        <span>DETALLE DEL TICKET</span>
                        <span class="text-teal-700 font-extrabold" x-text="`${carrito.length} ITEMS`"></span>
                    </h3>

                    <div class="max-h-[260px] overflow-y-auto space-y-2 pr-1">
                        <template x-for="(item, index) in carrito" :key="item.id">
                            <div class="p-2.5 bg-gray-50/80 rounded-2xl flex items-center justify-between gap-2 text-xs">
                                <div class="min-w-0 flex-1">
                                    <p class="font-extrabold text-gray-800 uppercase truncate" x-text="item.nombre"></p>
                                    <p class="text-[10px] text-gray-400 font-bold" x-text="`$${parseFloat(item.precio_venta).toFixed(2)} C/U`"></p>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="disminuirCantidad(index)" class="w-6 h-6 rounded-lg bg-white border border-gray-200 font-black text-gray-600 hover:bg-gray-100 flex items-center justify-center cursor-pointer">-</button>
                                    <span class="font-black text-xs w-6 text-center" x-text="item.cantidad"></span>
                                    <button type="button" @click="aumentarCantidad(index)" class="w-6 h-6 rounded-lg bg-white border border-gray-200 font-black text-gray-600 hover:bg-gray-100 flex items-center justify-center cursor-pointer">+</button>
                                </div>

                                <div class="text-right pl-2">
                                    <span class="font-black text-teal-800 block" x-text="`$${(item.precio_venta * item.cantidad).toFixed(2)}`"></span>
                                    <button type="button" @click="eliminarDelCarrito(index)" class="text-[10px] text-rose-500 font-bold hover:underline cursor-pointer">QUITAR</button>
                                </div>
                            </div>
                        </template>

                        <template x-if="carrito.length === 0">
                            <div class="py-8 text-center text-gray-400">
                                <i class="bi bi-cart-x text-2xl block mb-1"></i>
                                <span class="text-[11px] font-extrabold uppercase">CARRITO VACÍO</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- RESUMEN DE MONTO Y GENERAR TICKET -->
                <div class="space-y-3 pt-1">
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs font-black text-gray-700 uppercase">TOTAL A PAGAR:</span>
                        <span class="text-2xl font-black text-teal-800" x-text="`$${calcularTotal().toFixed(2)}`"></span>
                    </div>

                    <button type="button" 
                            @click="generarTicket()" 
                            :disabled="carrito.length === 0 || procesando"
                            class="w-full py-3.5 bg-teal-600 hover:bg-teal-700 disabled:bg-gray-300 text-white font-black text-xs uppercase rounded-2xl shadow-lg shadow-teal-600/20 active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i class="bi bi-receipt" x-show="!procesando"></i>
                        <span x-text="procesando ? 'GENERANDO COMPROBANTE...' : 'GENERAR TICKET DE FILTRO'"></span>
                    </button>
                </div>

            </div>

        </div>

    </div>

    <!-- MODAL DE TICKETS GENERADOS / HISTORIAL -->
    <template x-teleport="body">
        <div x-show="openHistorialModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div @click.outside="openHistorialModal = false" class="bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl space-y-4">
                
                <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                    <h3 class="text-sm font-black text-gray-800 uppercase flex items-center gap-2">
                        <i class="bi bi-receipt-cutoff text-teal-600 text-base"></i> HISTORIAL DE TICKETS GENERADOS
                    </h3>
                    <button type="button" @click="openHistorialModal = false" class="text-gray-400 hover:text-gray-600 font-bold focus:outline-none cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- BUSCADOR Y FILTROS DEL HISTORIAL -->
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

                <!-- LISTADO DE TICKETS RECIENTES -->
                <div class="max-h-[380px] overflow-y-auto pr-1 space-y-2">
                    <template x-for="tck in ticketsFiltrados()" :key="tck.id">
                        <div class="p-3 bg-slate-50 hover:bg-teal-50/30 transition-all rounded-2xl border border-gray-100 flex items-center justify-between gap-3 text-xs">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-teal-800" x-text="tck.codigo_ticket"></span>
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-md uppercase"
                                          :class="{
                                              'bg-amber-100 text-amber-800 border border-amber-200': tck.status === 'pendiente',
                                              'bg-emerald-100 text-emerald-800 border border-emerald-200': tck.status === 'pagado',
                                              'bg-blue-100 text-blue-800 border border-blue-200': tck.status === 'entregado',
                                              'bg-rose-100 text-rose-800 border border-rose-200': tck.status === 'expirado'
                                          }" x-text="tck.status"></span>
                                </div>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase" x-text="`PACIENTE: ${tck.paciente ? (tck.paciente.nombre_completo || tck.paciente.primer_nombre) : 'PÚBLICO GENERAL'}`"></p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="font-black text-gray-800" x-text="`$${parseFloat(tck.monto_total).toFixed(2)}`"></span>
                                <button type="button" 
                                        @click="verDetalleTicket(tck)" 
                                        class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-xl text-[10px] uppercase transition-all shadow-xs active:scale-95 cursor-pointer">
                                    VER / REIMPRIMIR
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="ticketsFiltrados().length === 0">
                        <div class="text-center py-10 text-gray-400">
                            <i class="bi bi-inbox text-3xl block mb-1"></i>
                            <span class="text-xs font-bold uppercase">NO SE ENCONTRARON TICKETS EN EL HISTORIAL</span>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </template>

    <!-- INCLUSIÓN MODULAR DEL MODAL DE VISTA PREVIA E IMPRESIÓN DE TICKET -->
    @include('pos.modalTicket')

</div>

<script>
function posApp() {
    return {
        searchQuery: '',
        searchTicketQuery: '',
        filtroEstatus: '',
        pacienteId: '',
        carrito: [],
        productos: @json($productos),
        productosFiltrados: [],
        listaTickets: @json($ticketsRecientes ?? []),
        openTicketModal: false,
        openHistorialModal: false,
        ticketGenerado: null,
        procesando: false,

        init() {
            this.productosFiltrados = this.productos;
        },

        obtenerStockDisponible(prod) {
            if (prod.stock_disponible !== undefined && prod.stock_disponible !== null) {
                return prod.stock_disponible;
            }
            return Math.max(0, (prod.stock_actual || 0) - (prod.stock_reservado || 0));
        },

        filtrarProductos() {
            const q = this.searchQuery.trim().toUpperCase();
            if (q === '') {
                this.productosFiltrados = this.productos;
                return;
            }
            this.productosFiltrados = this.productos.filter(p => 
                (p.nombre && p.nombre.toUpperCase().includes(q)) || 
                (p.codigo && p.codigo.toUpperCase().includes(q)) ||
                (p.codigo_barras && p.codigo_barras.toUpperCase().includes(q))
            );
        },

        buscarPorCodigoExacto() {
            const q = this.searchQuery.trim().toUpperCase();
            if (!q) return;

            const prod = this.productos.find(p => 
                String(p.codigo || p.codigo_barras || '').toUpperCase() === q
            );

            if (prod) {
                this.agregarAlCarrito(prod);
                this.searchQuery = '';
                this.filtrarProductos();
            } else {
                alert('PRODUCTO NO ENCONTRADO CON ESE CÓDIGO');
            }
        },

        ticketsFiltrados() {
            const q = this.searchTicketQuery.trim().toUpperCase();
            const estatus = this.filtroEstatus.trim().toLowerCase();

            return this.listaTickets.filter(t => {
                const cod = (t.codigo_ticket || '').toUpperCase();
                const pac = t.paciente ? (t.paciente.nombre_completo || t.paciente.primer_nombre || '').toUpperCase() : 'PÚBLICO GENERAL';
                const st = (t.status || '').toLowerCase();

                const coincideTexto = !q || cod.includes(q) || pac.includes(q);
                const coincideEstatus = !estatus || st === estatus;

                return coincideTexto && coincideEstatus;
            });
        },

        agregarAlCarrito(producto) {
            const stockDisponible = this.obtenerStockDisponible(producto);
            
            if (stockDisponible <= 0) {
                alert('MEDICAMENTO SIN STOCK DISPONIBLE');
                return;
            }

            const itemExistente = this.carrito.find(item => item.id === producto.id);
            if (itemExistente) {
                if (itemExistente.cantidad + 1 > stockDisponible) {
                    alert(`SOLO TIENE ${stockDisponible} PZAS. DISPONIBLES.`);
                    return;
                }
                itemExistente.cantidad++;
            } else {
                this.carrito.push({
                    id: producto.id,
                    nombre: producto.nombre,
                    precio_venta: parseFloat(producto.precio_venta),
                    cantidad: 1,
                    productoRef: producto
                });
            }
        },

        aumentarCantidad(index) {
            const item = this.carrito[index];
            const stockDisponible = this.obtenerStockDisponible(item.productoRef);
            
            if (item.cantidad + 1 > stockDisponible) {
                alert(`SOLO TIENE ${stockDisponible} PZAS. DISPONIBLES PARA AGREGAR.`);
                return;
            }
            item.cantidad++;
        },

        disminuirCantidad(index) {
            if (this.carrito[index].cantidad > 1) {
                this.carrito[index].cantidad--;
            } else {
                this.eliminarDelCarrito(index);
            }
        },

        eliminarDelCarrito(index) {
            this.carrito.splice(index, 1);
        },

        calcularTotal() {
            return this.carrito.reduce((acc, item) => acc + (item.precio_venta * item.cantidad), 0);
        },

        formatearHoraExpiracion(fechaStr) {
            if (!fechaStr) return 'N/A';
            const f = new Date(fechaStr);
            return f.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true });
        },

        async generarTicket() {
            if (this.carrito.length === 0) return;

            this.procesando = true;

            const payload = {
                paciente_id: this.pacienteId || null,
                carrito: this.carrito.map(item => ({
                    id: item.id,
                    cantidad: item.cantidad
                }))
            };

            try {
                const response = await fetch("{{ route('pos.generar-ticket') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // 1. Descontar localmente el stock reservado en el array de productos en vivo
                    this.carrito.forEach(cartItem => {
                        const prod = this.productos.find(p => p.id === cartItem.id);
                        if (prod) {
                            prod.stock_reservado = (prod.stock_reservado || 0) + cartItem.cantidad;
                            prod.stock_disponible = Math.max(0, (prod.stock_disponible ?? prod.stock_actual) - cartItem.cantidad);
                        }
                    });

                    this.ticketGenerado = data.ticket;
                    this.listaTickets.unshift(data.ticket);
                    this.openTicketModal = true;
                    this.carrito = [];
                    this.pacienteId = '';
                    this.filtrarProductos();
                } else {
                    alert(data.message || 'ERROR AL GENERAR TICKET');
                }
            } catch (error) {
                console.error(error);
                alert('OCURRIÓ UN ERROR EN EL SERVIDOR');
            } finally {
                this.procesando = false;
            }
        },

        verDetalleTicket(tck) {
            this.ticketGenerado = tck;
            this.openHistorialModal = false;
            this.openTicketModal = true;
        },

        imprimirTicketModal() {
            window.print();
        }
    }
}
</script>
@endsection