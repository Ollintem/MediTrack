@extends('layouts.admin')

@section('content')
<div class="space-y-8 w-full block" x-data="{ 
    searchQuery: '',
    categoriaFiltro: '',
    openCreateModal: {{ $errors->any() ? 'true' : 'false' }},
    openSurtirModal: false,
    openEditModal: false,
    openCategoriaModal: false,
    openLoteModal: false,
    productoSeleccionado: {},
    productoEditar: {},

    // FUNCIÓN DE FILTRADO PARA BUSCADOR Y CATEGORÍAS
    cumpleFiltro(codigo, nombre, descripcion, categoria) {
        // Normalizar texto de búsqueda
        const q = this.searchQuery.trim().toLowerCase();
        const coincideBusqueda = !q || 
            codigo.toLowerCase().includes(q) || 
            nombre.toLowerCase().includes(q) || 
            descripcion.toLowerCase().includes(q);

        // Normalizar filtro de categoría
        const catFiltro = this.categoriaFiltro.trim().toLowerCase();
        const catProd = categoria.trim().toLowerCase();
        const coincideCategoria = !catFiltro || catProd === catFiltro;

        return coincideBusqueda && coincideCategoria;
    },

    abrirSurtir(producto) {
        this.productoSeleccionado = JSON.parse(JSON.stringify(producto));
        this.openSurtirModal = true;
    },

    abrirEditar(producto) {
        this.productoEditar = JSON.parse(JSON.stringify(producto));
        this.openEditModal = true;
    }
}">

    <!-- BANNER HERO PREMIUM -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-box-seam-fill text-emerald-300"></i> Almacén de Farmacia
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Inventario de Medicamentos</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Catálogo maestro de fármacos, control de reservas y existencias físicas de farmacia.
                </p>
            </div>

            @if(auth()->user()->tienePermiso('Inventario', 'crear'))
            <!-- Botón 1: Surtir Lote Masivo (NUEVO) -->
            <button @click="openLoteModal = true" 
                    type="button" 
                    class="group bg-white/10 hover:bg-white/20 text-white font-bold px-5 py-3 rounded-2xl text-xs sm:text-sm border border-white/20 backdrop-blur-md shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5 active:scale-95">
                <i class="bi bi-boxes text-emerald-300 text-base group-hover:scale-110 transition-transform"></i>
                <span>Surtir Lote de Medicamentos</span>
            </button>
            <button type="button" 
                    @click="openCreateModal = true" 
                    class="group bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                <i class="bi bi-plus-lg text-teal-600 group-hover:scale-110 transition-transform duration-300 text-base"></i>
                <span>Registrar Medicamento</span>
            </button>
            @endif
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold border border-teal-100">
                    <i class="bi bi-capsule-capsule"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total en Catálogo</p>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <h3 class="text-2xl font-black text-gray-800">{{ $totalProductos }}</h3>
                        <span class="text-xs font-semibold text-gray-400">ítems</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-100">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Bajo / Reabastecer</p>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <h3 class="text-2xl font-black text-amber-600">{{ $stockBajo }}</h3>
                        <span class="text-xs font-semibold text-gray-500">por reponer</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold border border-rose-100">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Sin Existencias</p>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <h3 class="text-2xl font-black text-rose-600">{{ $agotados }}</h3>
                        <span class="text-xs font-semibold text-gray-500">agotados</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BARRAS DE BÚSQUEDA Y FILTROS -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
            
            <!-- Buscador por Texto -->
            <div class="relative w-full md:w-96">
                <i class="bi bi-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Buscar Código o Medicamento..." 
                       class="w-full bg-slate-50 border border-gray-200 focus:border-teal-400 focus:bg-white text-xs font-bold rounded-2xl pl-10 pr-4 py-3 focus:outline-none uppercase transition-all">
            </div>

            <!-- Dropdown Filtro Categorías -->
            <div class="relative w-full md:w-64" x-data="{ openCatFilter: false }">
                <button type="button" 
                        @click="openCatFilter = !openCatFilter" 
                        @click.outside="openCatFilter = false"
                        class="w-full px-4 py-3 rounded-2xl border border-gray-200 text-xs font-bold bg-slate-50 text-left flex justify-between items-center focus:ring-2 focus:ring-teal-500 transition-all">
                    <span x-text="categoriaFiltro ? 'Categoría: ' + categoriaFiltro : 'Todas las Categorías'" class="text-gray-700 capitalize"></span>
                    <i class="bi bi-chevron-down text-gray-400 text-xs transition-transform duration-200" :class="{ 'rotate-180': openCatFilter }"></i>
                </button>

                <div x-show="openCatFilter" 
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-gray-100 shadow-xl py-1.5 text-xs font-bold overflow-hidden" x-cloak>
                    
                    <button type="button" 
                            @click="categoriaFiltro = ''; openCatFilter = false" 
                            class="w-full text-left px-4 py-2 hover:bg-teal-50 hover:text-teal-700 flex items-center justify-between transition-colors">
                        <span>Todas las Categorías</span>
                        <i x-show="categoriaFiltro === ''" class="bi bi-check-lg text-teal-600 font-black"></i>
                    </button>

                    @foreach($categorias as $cat)
                    @php $nombreCat = strtolower($cat->nombre); @endphp
                    <button type="button" 
                            @click="categoriaFiltro = '{{ $nombreCat }}'; openCatFilter = false" 
                            class="w-full text-left px-4 py-2 hover:bg-teal-50 hover:text-teal-700 flex items-center justify-between transition-colors capitalize">
                        <span>{{ strtolower($cat->nombre) }}</span>
                        <i x-show="categoriaFiltro === '{{ $nombreCat }}'" class="bi bi-check-lg text-teal-600 font-black"></i>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- TABLA PRINCIPAL DE PRODUCTOS -->
        <div class="overflow-x-auto rounded-2xl border border-gray-100">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-100 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">
                        <th class="py-4 px-5">CÓDIGO</th>
                        <th class="py-4 px-5">MEDICAMENTO / DESCRIPCIÓN</th>
                        <th class="py-4 px-5">CATEGORÍA</th>
                        <th class="py-4 px-5 text-center">PRECIO COMPRA</th>
                        <th class="py-4 px-5 text-center">PRECIO VENTA</th>
                        <th class="py-4 px-5 text-center">STOCK (DISPONIBLE / FÍSICO)</th>
                        <th class="py-4 px-5 text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs font-semibold">
                    @if(count($productos) > 0)
                        @foreach($productos as $prod)
                        <tr class="hover:bg-slate-50/80 transition-colors"
                            x-show="cumpleFiltro(
                                @js($prod->codigo), 
                                @js($prod->nombre), 
                                @js($prod->descripcion ?? ''), 
                                @js($prod->categoria->nombre ?? '')
                            )">
                            
                            <!-- CÓDIGO -->
                            <td class="py-4 px-5 font-extrabold text-teal-700 uppercase">
                                {{ strtoupper($prod->codigo) }}
                            </td>

                            <!-- NOMBRE -->
                            <td class="py-4 px-5">
                                <div class="font-extrabold text-gray-800 text-sm capitalize">{{ strtolower($prod->nombre) }}</div>
                                <div class="text-[10px] text-gray-400 font-medium capitalize">{{ $prod->descripcion ? strtolower($prod->descripcion) : 'Sin descripción' }}</div>
                            </td>

                            <!-- CATEGORÍA -->
                            <td class="py-4 px-5">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-slate-100 text-gray-600 border border-gray-200 capitalize">
                                    {{ strtolower($prod->categoria->nombre ?? 'General') }}
                                </span>
                            </td>

                            <!-- PRECIO COMPRA -->
                            <td class="py-4 px-5 text-center font-bold text-gray-600">
                                ${{ number_format($prod->precio_compra, 2) }}
                            </td>

                            <!-- PRECIO VENTA -->
                            <td class="py-4 px-5 text-center font-black text-gray-800">
                                ${{ number_format($prod->precio_venta, 2) }}
                            </td>

                            <!-- STOCK DISPONIBLE Y FÍSICO -->
                            <td class="py-4 px-5 text-center">
                                <div class="flex items-baseline justify-center gap-1.5">
                                    <span class="text-sm font-black {{ $prod->stock_disponible <=$prod->stock_minimo ? 'text-amber-600' : 'text-gray-800' }}">
                                        {{ $prod->stock_disponible }} pzas.
                                    </span>
                                    <span class="text-[10px] font-bold text-gray-400">
                                        ({{ $prod->stock_actual }} Real)
                                    </span>
                                </div>
                                @if($prod->stock_disponible == 0)
                                    <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200 mt-0.5 inline-block uppercase">AGOTADO</span>
                                @elseif($prod->stock_disponible <=$prod->stock_minimo)
                                    <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200 mt-0.5 inline-block uppercase">STOCK BAJO</span>
                                @endif
                            </td>

                            <!-- ACCIONES -->
                            <td class="py-4 px-5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if(auth()->user()->tienePermiso('Inventario', 'editar'))
                                    <button type="button" 
                                            @click="abrirSurtir({{ json_encode($prod) }})" 
                                            class="bg-emerald-500 hover:bg-emerald-600 text-white p-2 rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center gap-1"
                                            title="Surtir de existencias">
                                        <i class="bi bi-box-arrow-in-down"></i>
                                    </button>

                                    <button type="button" 
                                            @click="abrirEditar({{ json_encode($prod) }})" 
                                            class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center gap-1"
                                            title="Editar medicamento">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @endif

                                    @if(auth()->user()->tienePermiso('Inventario', 'eliminar'))
                                    <button type="button" 
                                            onclick="eliminarProducto({{ $prod->id }}, '{{ addslashes($prod->nombre) }}')" 
                                            class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center gap-1"
                                            title="Eliminar medicamento">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400 font-bold">
                                No hay medicamentos registrados en el inventario.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL SURTIR / AGREGAR STOCK -->
    <template x-teleport="body">
        <div x-show="openSurtirModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openSurtirModal" class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" @click="openSurtirModal = false"></div>

                <div class="inline-block w-full max-w-md my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100">
                    
                    <div class="flex items-center justify-between px-6 py-4 bg-emerald-600 text-white">
                        <div class="flex items-center gap-2 font-black text-sm uppercase">
                            <i class="bi bi-box-arrow-in-down text-lg"></i>
                            <span>Surtir Existencias</span>
                        </div>
                        <button type="button" @click="openSurtirModal = false" class="text-white/80 hover:text-white text-lg font-bold"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <form :action="`/inventario/${productoSeleccionado.id}/agregar-stock`" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">MEDICAMENTO:</p>
                            <h4 class="text-sm font-black text-gray-800 capitalize" x-text="productoSeleccionado.nombre"></h4>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CANTIDAD A SUMAR *</label>
                            <input type="number" name="cantidad" min="1" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="EJ. 20">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">MOTIVO O FACTURA (OPCIONAL)</label>
                            <input type="text" name="motivo" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="EJ. REABASTECIMIENTO PROVEEDOR SURTIFARMA">
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="openSurtirModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-2xl text-xs font-bold uppercase">CANCELAR</button>
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white rounded-2xl text-xs font-bold shadow-lg shadow-emerald-600/20 uppercase active:scale-95 transition-all">INGRESAR STOCK</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- INCLUSIÓN DE MODALES EXTRAÍDOS -->
    @include('inventario.modalAgregarProducto')
    @include('inventario.modalEditarProducto')
    @include('inventario.modalSurtirStock')
    @include('inventario.modalGestionarCategorias')
    @include('inventario.modalSurtirLote')

</div>

<script>
function eliminarProducto(id, nombre) {
    Swal.fire({
        title: '¿ELIMINAR MEDICAMENTO?',
        text: `ESTÁS A PUNTO DE ELIMINAR "${nombre.toUpperCase()}". ESTA ACCIÓN NO SE PUEDE DESHACER.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'SÍ, ELIMINAR',
        cancelButtonText: 'CANCELAR',
        customClass: {
            popup: 'rounded-3xl border border-gray-100 font-sans',
            title: 'text-sm font-black text-gray-800 uppercase',
            htmlContainer: 'text-xs font-bold text-gray-500 uppercase'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/inventario/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.notificar === 'function') {
                        window.notificar(data.message, 'success');
                    }
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    if (typeof window.notificar === 'function') {
                        window.notificar(data.message || 'OCURRIÓ UN ERROR AL ELIMINAR.', 'error');
                    }
                }
            })
            .catch(() => {
                if (typeof window.notificar === 'function') {
                    window.notificar('ERROR DE CONEXIÓN CON EL SERVIDOR.', 'error');
                }
            });
        }
    });
}
</script>
@endsection