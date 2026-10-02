@extends('layouts.admin')

@section('content')
<div class="space-y-6 w-full" x-data="posApp()">
    
    <!-- BANNER DE TERMINAL DE VENTA -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-6 text-white shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-white border border-white/20 uppercase tracking-wider mb-2">
                    <i class="bi bi-shop"></i> MOSTRADOR
                </span>
                <h1 class="text-2xl font-extrabold uppercase tracking-tight">TERMINAL DE VENTA (POS)</h1>
                <p class="text-teal-100 text-xs font-semibold mt-0.5">SELECCIÓN Y GENERACIÓN DE TICKETS CON TIEMPO DE ESPERA (25 MIN).</p>
            </div>
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
                    <div x-show="cumpleFiltro('{{ strtoupper($prod->codigo) }}', '{{ strtoupper($prod->nombre) }}')"
                         @click="agregarAlCarrito({{ json_encode($prod) }})"
                         class="p-4 rounded-2xl border transition-all flex flex-col justify-between space-y-2 cursor-pointer select-none {{ $prod->stock_disponible <= 0 ? 'bg-gray-100/70 border-gray-200 opacity-60 cursor-not-allowed' : 'bg-slate-50/50 hover:bg-teal-50/50 hover:border-teal-200 border-gray-100' }}">
                        <div>
                            <div class="flex justify-between items-start gap-1">
                                <span class="text-[10px] font-extrabold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">
                                    {{ strtoupper($prod->codigo) }}
                                </span>
                                <span class="text-xs font-black text-gray-800">
                                    ${{ number_format($prod->precio_venta, 2) }}
                                </span>
                            </div>
                            <h4 class="text-xs font-extrabold text-gray-800 uppercase mt-2 line-clamp-2">
                                {{ strtoupper($prod->nombre) }}
                            </h4>
                        </div>

                        <!-- REPRESENTACIÓN DE STOCK: DISPONIBLE (NEGRITAS) VS FÍSICO (TENUE) -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200/60 text-xs">
                            <span class="text-[10px] font-bold text-gray-400">DISPONIBLE:</span>
                            <div class="flex items-baseline gap-1">
                                @if($prod->stock_disponible <= 0)
                                    <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200 uppercase">AGOTADO</span>
                                @else
                                    <span class="font-black text-gray-800">
                                        {{ $prod->stock_disponible }} PZAS.
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
                        <button @click="vaciarCarrito()" class="text-[10px] font-extrabold text-rose-500 hover:text-rose-700 uppercase">
                            VACIAR
                        </button>
                    </div>

                    <!-- PACIENTE OPCIONAL -->
                    <div>
                        <label class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1">PACIENTE (OPCIONAL):</label>
                        <select x-model="pacienteId" class="w-full bg-slate-50 border border-gray-200 text-xs font-bold rounded-2xl px-3 py-2.5 uppercase focus:outline-none focus:border-teal-500">
                            <option value="">PÚBLICO GENERAL</option>
                            @foreach($pacientes as $pac)
                            <option value="{{ $pac->id }}">{{ strtoupper($pac->nombre_completo) }}</option>
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
                                    <button @click="cambiarCantidad(index, -1)" class="w-6 h-6 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-gray-100">-</button>
                                    <span class="font-black text-gray-800 text-xs" x-text="item.cantidad"></span>
                                    <button @click="cambiarCantidad(index, 1)" class="w-6 h-6 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-gray-100">+</button>
                                    <button @click="removerItem(index)" class="text-rose-500 hover:text-rose-700 ml-1">
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
                            class="w-full py-3.5 bg-teal-600 hover:bg-teal-700 disabled:bg-gray-300 text-white font-black rounded-2xl text-xs uppercase shadow-lg shadow-teal-600/20 transition-all flex items-center justify-center gap-2">
                        <i class="bi bi-ticket-perforated-fill text-base"></i>
                        <span x-text="cargando ? 'GENERANDO...' : 'GENERAR TICKET (25 MIN)'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function posApp() {
    return {
        searchQuery: '',
        pacienteId: '',
        carrito: [],
        cargando: false,
        catalogoProductos: @json($productos),

        cumpleFiltro(codigo, nombre) {
            const q = this.searchQuery.trim().toUpperCase();
            return !q || codigo.includes(q) || nombre.includes(q);
        },

        buscarPorCodigoExacto() {
            const q = this.searchQuery.trim().toUpperCase();
            if (!q) return;

            const prod = this.catalogoProductos.find(p => String(p.codigo).toUpperCase() === q);

            if (prod) {
                this.agregarAlCarrito(prod);
                this.searchQuery = ''; // Limpiar tras escaneo exitoso
            } else {
                if (typeof window.notificar === 'function') {
                    window.notificar('PRODUCTO NO ENCONTRADO CON ESE CÓDIGO', 'error');
                } else {
                    alert('PRODUCTO NO ENCONTRADO CON ESE CÓDIGO');
                }
            }
        },

        agregarAlCarrito(prod) {
            if (prod.stock_disponible < 1) {
                if (typeof window.notificar === 'function') {
                    window.notificar('MEDICAMENTO SIN STOCK DISPONIBLE', 'error');
                } else {
                    alert('MEDICAMENTO SIN STOCK DISPONIBLE');
                }
                return;
            }

            const index = this.carrito.findIndex(i => i.id === prod.id);
            if (index > -1) {
                if (this.carrito[index].cantidad + 1 > prod.stock_disponible) {
                    if (typeof window.notificar === 'function') {
                        window.notificar('EXCEDE EL STOCK DISPONIBLE', 'error');
                    } else {
                        alert('EXCEDE EL STOCK DISPONIBLE');
                    }
                    return;
                }
                this.carrito[index].cantidad++;
            } else {
                this.carrito.push({
                    id: prod.id,
                    nombre: prod.nombre,
                    precio: parseFloat(prod.precio_venta),
                    cantidad: 1,
                    stock_disponible: prod.stock_disponible
                });
            }
        },

        cambiarCantidad(index, delta) {
            const item = this.carrito[index];
            const nuevaCant = item.cantidad + delta;
            if (nuevaCant <= 0) {
                this.removerItem(index);
            } else if (nuevaCant > item.stock_disponible) {
                if (typeof window.notificar === 'function') {
                    window.notificar('EXCEDE EL STOCK DISPONIBLE', 'error');
                } else {
                    alert('EXCEDE EL STOCK DISPONIBLE');
                }
            } else {
                item.cantidad = nuevaCant;
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

        generarTicket() {
            if (this.carrito.length === 0) return;
            this.cargando = true;

            fetch("{{ route('pos.procesar-venta') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    paciente_id: this.pacienteId || null,
                    carrito: this.carrito
                })
            })
            .then(res => res.json())
            .then(data => {
                this.cargando = false;
                if (data.status === 'success' || data.success) {
                    const cod = data.ticket ? data.ticket.codigo_ticket : (data.codigo || 'OK');
                    if (typeof window.notificar === 'function') {
                        window.notificar(`TICKET ${cod} GENERADO CORRECTAMENTE`, 'success');
                    } else {
                        alert(`TICKET GENERADO: ${cod}`);
                    }
                    this.vaciarCarrito();
                    setTimeout(() => location.reload(), 1200);
                } else {
                    if (typeof window.notificar === 'function') {
                        window.notificar(data.message || 'ERROR AL GENERAR TICKET', 'error');
                    } else {
                        alert(data.message || 'ERROR AL GENERAR TICKET');
                    }
                }
            })
            .catch(() => {
                this.cargando = false;
                if (typeof window.notificar === 'function') {
                    window.notificar('ERROR DE CONEXIÓN AL GENERAR TICKET', 'error');
                } else {
                    alert('ERROR DE CONEXIÓN');
                }
            });
        }
    }
}
</script>
@endsection