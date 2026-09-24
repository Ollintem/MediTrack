@extends('layouts.admin')

@section('content')
<div class="space-y-8 w-full block" x-data="{ 
    searchQuery: '',
    categoriaFiltro: '',
    openCreateModal: {{ $errors->any() ? 'true' : 'false' }},
    openSurtirModal: false,
    openEditModal: false,
    productoSeleccionado: {},
    productoEditar: {},

    // Función para abrir modal de Surtir/Agregar Stock
    abrirSurtir(producto) {
        this.productoSeleccionado = JSON.parse(JSON.stringify(producto));
        this.openSurtirModal = true;
    },

    // Función para abrir modal de Editar Medicamento
    abrirEditar(producto) {
        this.productoEditar = JSON.parse(JSON.stringify(producto));
        this.openEditModal = true;
    }
}">

    <!-- BANNER SUPERIOR CON DEGRADADO MEDITRACK -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-700/20">
        <!-- Adorno visual de fondo -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute right-40 -top-10 w-40 h-40 bg-emerald-400/20 rounded-full blur-xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/15 text-white backdrop-blur-md border border-white/20 uppercase tracking-wider mb-2">
                    <i class="bi bi-box-seam-fill"></i> MÓDULO DE FARMACIA
                </span>
                <h1 class="text-3xl font-extrabold uppercase tracking-tight">Inventario de Medicamentos</h1>
                <p class="text-teal-100/90 text-xs font-semibold mt-1">Catálogo maestro de fármacos, control de reservas y existencias físicas de farmacia.</p>
            </div>

            @if(auth()->user()->tienePermiso('Inventario', 'crear'))
            <button type="button" 
                    @click="openCreateModal = true" 
                    class="px-6 py-3.5 bg-white text-teal-800 hover:bg-teal-50 font-extrabold rounded-2xl text-xs shadow-lg shadow-black/10 transition-all flex items-center justify-center gap-2 transform active:scale-95 uppercase tracking-wider">
                <i class="bi bi-plus-lg text-base"></i>
                <span>Registrar Medicamento</span>
            </button>
            @endif
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- TOTAL PRODUCTOS -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold border border-teal-100">
                <i class="bi bi-capsule-capsule"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total en Catálogo</p>
                <h3 class="text-2xl font-black text-gray-800">{{ $totalProductos }}</h3>
            </div>
        </div>

        <!-- BAJO STOCK -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-100">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Stock Bajo / Reabastecer</p>
                <h3 class="text-2xl font-black text-amber-600">{{ $stockBajo }}</h3>
            </div>
        </div>

        <!-- AGOTADOS -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold border border-rose-100">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Sin Existencias</p>
                <h3 class="text-2xl font-black text-rose-600">{{ $agotados }}</h3>
            </div>
        </div>
    </div>

    <!-- BARRAS DE BÚSQUEDA Y FILTROS -->
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
            
            <!-- Buscador por Texto (Ingreso forzado en Mayúsculas) -->
            <div class="relative w-full md:w-96">
                <i class="bi bi-search absolute left-4 top-3.5 text-gray-400 text-xs"></i>
                <input type="text" 
                    x-model="searchQuery" 
                    placeholder="Buscar Código o Medicamento..." 
                    class="w-full bg-slate-50 border border-gray-200 focus:border-teal-400 focus:bg-white text-xs font-bold rounded-2xl pl-10 pr-4 py-3 focus:outline-none uppercase transition-all">
            </div>

            <!-- Dropdown Personalizado de Categorías -->
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
                    @forelse($productos as $prod)
                    @php 
                        $codigoUpper = strtoupper($prod->codigo);
                        $nombreLower = strtolower($prod->nombre);
                        $catLower    = strtolower($prod->categoria->nombre ?? 'General');
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors"
                        x-show="(searchQuery === '' || '{{ $codigoUpper }}'.includes(searchQuery.toUpperCase()) || '{{ strtoupper($prod->nombre) }}'.includes(searchQuery.toUpperCase())) && (categoriaFiltro === '' || '{{ $catLower }}' === categoriaFiltro)">
                        
                        <!-- CÓDIGO (Código en Mayúsculas Fijo) -->
                        <td class="py-4 px-5 font-extrabold text-teal-700 uppercase">
                            {{ $codigoUpper }}
                        </td>

                        <!-- NOMBRE (Visualización Primera Mayúscula / Resto Minúsculas) -->
                        <td class="py-4 px-5">
                            <div class="font-extrabold text-gray-800 text-sm capitalize">{{ $nombreLower }}</div>
                            <div class="text-[10px] text-gray-400 font-medium capitalize">{{ $prod->descripcion ? strtolower($prod->descripcion) : 'Sin descripción' }}</div>
                        </td>

                        <!-- CATEGORÍA (Visualización Formato Título) -->
                        <td class="py-4 px-5">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-slate-100 text-gray-600 border border-gray-200 capitalize">
                                {{ $catLower }}
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
                                <span class="text-sm font-black {{ $prod->stock_disponible <= $prod->stock_minimo ? 'text-amber-600' : 'text-gray-800' }}">
                                    {{ $prod->stock_disponible }} pzas.
                                </span>
                                <span class="text-[10px] font-bold text-gray-400">
                                    ({{ $prod->stock_actual }} Real)
                                </span>
                            </div>
                            @if($prod->stock_disponible == 0)
                                <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200 mt-0.5 inline-block uppercase">AGOTADO</span>
                            @elseif($prod->stock_disponible <= $prod->stock_minimo)
                                <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200 mt-0.5 inline-block uppercase">STOCK BAJO</span>
                            @endif
                        </td>

                        <!-- ACCIONES -->
                        <td class="py-4 px-5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Botón Surtir Stock -->
                                @if(auth()->user()->tienePermiso('Inventario', 'editar'))
                                <button type="button" 
                                        @click="abrirSurtir({{ json_encode($prod) }})" 
                                        class="bg-emerald-500 hover:bg-emerald-600 text-white p-2 rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center gap-1"
                                        title="Surtir de existencias">
                                    <i class="bi bi-box-arrow-in-down"></i>
                                </button>

                                <!-- Botón Editar Medicamento -->
                                <button type="button" 
                                        @click="abrirEditar({{ json_encode($prod) }})" 
                                        class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center gap-1"
                                        title="Editar medicamento">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                @endif

                                <!-- Botón Eliminar Medicamento -->
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
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-400 font-bold">
                            No hay medicamentos registrados en el inventario.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL REGISTRAR NUEVO MEDICAMENTO -->
    <template x-teleport="body">
        <div x-show="openCreateModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openCreateModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" @click="openCreateModal = false"></div>

                <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100">
                    
                    <!-- Encabezado Modal -->
                    <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-teal-700 to-emerald-600 text-white">
                        <div class="flex items-center gap-2 font-black text-sm uppercase">
                            <i class="bi bi-capsule text-lg"></i>
                            <span>Registrar Nuevo Medicamento</span>
                        </div>
                        <button type="button" @click="openCreateModal = false" class="text-white/80 hover:text-white text-lg font-bold"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <!-- Formulario con Entrada Forzada en Mayúsculas via CSS 'uppercase' -->
                    <form action="{{ route('inventario.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Código de Barras / SKU -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CÓDIGO DE BARRAS / SKU *</label>
                                <input type="text" name="codigo" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="EJ. 7501058618210">
                            </div>

                            <!-- Categoría -->
                            <div class="relative" x-data="{ openCatModal: false, catNombre: '', catId: '' }">
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CATEGORÍA *</label>
                                <input type="hidden" name="categoria_id" :value="catId" required>

                                <button type="button" 
                                        @click="openCatModal = !openCatModal" 
                                        @click.outside="openCatModal = false"
                                        class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold bg-white text-left flex justify-between items-center focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                    <span x-text="catNombre || 'SELECCIONE CATEGORÍA...'" :class="!catNombre ? 'text-gray-400 font-normal' : 'text-gray-800 font-bold uppercase'"></span>
                                    <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
                                </button>

                                <div x-show="openCatModal" class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-gray-100 shadow-xl py-1.5 text-xs font-bold uppercase overflow-hidden" x-cloak>
                                    @foreach($categorias as $cat)
                                    @php $cNom = strtoupper($cat->nombre); @endphp
                                    <button type="button" 
                                            @click="catId = '{{ $cat->id }}'; catNombre = '{{ $cNom }}'; openCatModal = false" 
                                            class="w-full text-left px-4 py-2 hover:bg-teal-50 hover:text-teal-700 transition-colors">
                                        {{ $cNom }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Nombre Comercial (Mayúsculas activas en escritura) -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">NOMBRE DE MEDICAMENTO Y PRESENTACIÓN *</label>
                            <input type="text" name="nombre" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="EJ. PARACETAMOL 500MG CAJA C/30 TABLETAS">
                        </div>

                        <!-- Precios y Stock Inicial -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. COMPRA ($) *</label>
                                <input type="number" step="0.01" name="precio_compra" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. VENTA ($) *</label>
                                <input type="number" step="0.01" name="precio_venta" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">STOCK INICIAL *</label>
                                <input type="number" name="stock_actual" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none" value="0">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">STOCK MÍNIMO *</label>
                                <input type="number" name="stock_minimo" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none" value="5">
                            </div>
                        </div>

                        <!-- Descripción/Sustancia activa (Mayúsculas activas en escritura) -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">DESCRIPCIÓN / SUSTANCIA ACTIVA (OPCIONAL)</label>
                            <textarea name="descripcion" rows="2" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="EJ. PARACETAMOL ANALGÉSICO Y ANTIPIRÉTICO"></textarea>
                        </div>

                        <!-- Footer Modal -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-bold uppercase">CANCELAR</button>
                            <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-teal-600/20 uppercase active:scale-95 transition-all">GUARDAR MEDICAMENTO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

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

    <!-- MODAL EDITAR MEDICAMENTO -->
    <template x-teleport="body">
        <div x-show="openEditModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openEditModal" class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" @click="openEditModal = false"></div>

                <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100">
                    
                    <div class="flex items-center justify-between px-6 py-4 bg-amber-500 text-white">
                        <div class="flex items-center gap-2 font-black text-sm uppercase">
                            <i class="bi bi-pencil-square text-lg"></i>
                            <span>Editar Medicamento</span>
                        </div>
                        <button type="button" @click="openEditModal = false" class="text-white/80 hover:text-white text-lg font-bold"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <form :action="`/inventario/${productoEditar.id}`" method="POST" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CÓDIGO DE BARRAS / SKU *</label>
                                <input type="text" name="codigo" x-model="productoEditar.codigo" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>

                            <div class="relative" x-data="{ openEditCat: false }">
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CATEGORÍA *</label>
                                <input type="hidden" name="categoria_id" :value="productoEditar.categoria_id">

                                <button type="button" 
                                        @click="openEditCat = !openEditCat" 
                                        @click.outside="openEditCat = false"
                                        class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold bg-white text-left flex justify-between items-center focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    <span x-text="productoEditar.categoria?.nombre ? productoEditar.categoria.nombre.toUpperCase() : 'SELECCIONE...'" class="text-gray-800 font-bold uppercase"></span>
                                    <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
                                </button>

                                <div x-show="openEditCat" class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-gray-100 shadow-xl py-1.5 text-xs font-bold uppercase overflow-hidden" x-cloak>
                                    @foreach($categorias as $cat)
                                    @php $cNomEdit = strtoupper($cat->nombre); @endphp
                                    <button type="button" 
                                            @click="productoEditar.categoria_id = '{{ $cat->id }}'; productoEditar.categoria = { nombre: '{{$cNomEdit }}' }; openEditCat = false" 
                                            class="w-full text-left px-4 py-2 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                                        {{ $cNomEdit }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">NOMBRE DE MEDICAMENTO Y PRESENTACIÓN *</label>
                            <input type="text" name="nombre" x-model="productoEditar.nombre" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. COMPRA ($) *</label>
                                <input type="number" step="0.01" name="precio_compra" x-model="productoEditar.precio_compra" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. VENTA ($) *</label>
                                <input type="number" step="0.01" name="precio_venta" x-model="productoEditar.precio_venta" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">STOCK MÍNIMO *</label>
                                <input type="number" name="stock_minimo" x-model="productoEditar.stock_minimo" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">DESCRIPCIÓN / SUSTANCIA ACTIVA</label>
                            <textarea name="descripcion" x-model="productoEditar.descripcion" rows="2" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="openEditModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-2xl text-xs font-bold uppercase">CANCELAR</button>
                            <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl text-xs font-bold shadow-lg shadow-amber-500/20 uppercase active:scale-95 transition-all">ACTUALIZAR MEDICAMENTO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

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