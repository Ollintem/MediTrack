@extends('layouts.admin')

@section('content')
@php
    // Acepta colección o paginador
    $lista = $productos instanceof \Illuminate\Contracts\Pagination\Paginator
        ? collect($productos->items())
        : collect($productos ?? []);
    if ($lista instanceof \Illuminate\Database\Eloquent\Collection) {
        $lista->loadMissing('categoria');
    }

    $puedeCrear    = auth()->user()->tienePermiso('Inventario', 'crear');
    $puedeEditar   = auth()->user()->tienePermiso('Inventario', 'editar');
    $puedeEliminar = auth()->user()->tienePermiso('Inventario', 'eliminar');

    // Se conserva el objeto completo del producto (lo usan los modales de surtir/editar)
    // y se agregan campos auxiliares con prefijo "_" solo para la vista.
    $productosJs = $lista->map(function ($p) {
        $disp = (int) $p->stock_disponible;
        $min  = (int) $p->stock_minimo;
        $cat  = $p->categoria->nombre ?? 'General';

        return array_merge($p->toArray(), [
            '_categoria' => $cat,
            '_estado'    => $disp <= 0 ? 'agotado' : ($disp <= $min ? 'bajo' : 'ok'),
            '_buscar'    => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(implode(' ', [
                $p->codigo, $p->nombre, $p->descripcion, $cat,
            ]))),
        ]);
    })->values();
@endphp

<div class="space-y-6 w-full block" x-data="moduloInventario()">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-48 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        <svg class="absolute inset-x-0 bottom-2 w-full h-12 opacity-25 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        <div class="absolute right-10 top-8 hidden md:flex gap-3 opacity-20 pointer-events-none">
            <span class="capsula w-7 h-16 rounded-full bg-gradient-to-b from-white from-50% to-emerald-300 to-50%"></span>
            <span class="capsula w-7 h-16 rounded-full bg-gradient-to-b from-emerald-200 from-50% to-white to-50%" style="animation-delay:-1.2s"></span>
            <span class="capsula w-10 h-10 mt-6 rounded-full bg-white" style="animation-delay:-2.4s"></span>
        </div>

        <div class="relative p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wide text-emerald-300 backdrop-blur-md">
                    <i class="bi bi-box-seam-fill"></i> Almacén de farmacia
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-3">Inventario de medicamentos <i class="bi bi-capsule-pill text-emerald-300 text-2xl latido-lento"></i></h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Catálogo de fármacos, existencias físicas y reservas. Busca por código, nombre, descripción o categoría.
                </p>
            </div>
            @if ($puedeCrear)
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button @click="openLoteModal = true" type="button"
                            class="group bg-white/10 hover:bg-white/20 text-white font-black px-5 py-3 rounded-2xl text-xs border border-white/20 backdrop-blur-md transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-boxes text-sm text-emerald-300 transition-transform duration-300 group-hover:scale-110"></i> Surtir lote
                    </button>
                    <button @click="openCreateModal = true" type="button"
                            class="group bg-white text-teal-900 hover:bg-emerald-50 font-black px-5 py-3 rounded-2xl text-xs shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-plus-lg text-sm text-teal-700 transition-transform duration-300 group-hover:rotate-90"></i> Registrar medicamento
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== RESUMEN ===================== --}}
    @php
        // [título, clave, ícono, color, estado al que filtra]
        $tarjetas = [
            ['Total en catálogo', 'total',    'bi-capsule-pill',             'bg-teal-50 text-teal-600',       'todos'],
            ['Stock óptimo',      'ok',       'bi-check2-circle',            'bg-emerald-50 text-emerald-600', 'ok'],
            ['Stock bajo',        'bajo',     'bi-exclamation-triangle-fill','bg-amber-50 text-amber-600',     'bajo'],
            ['Agotados',          'agotados', 'bi-x-octagon-fill',           'bg-rose-50 text-rose-500',       'agotado'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $color, $filtro])
            <button type="button" @click="estado = '{{ $filtro }}'"
                    class="tarjeta-stat group text-left bg-white rounded-2xl border shadow-sm p-4 flex items-center justify-between gap-3 transition-all hover:shadow-md hover:-translate-y-1 cursor-pointer"
                    :class="estado === '{{ $filtro }}' && '{{ $filtro }}' !== 'todos' ? 'border-teal-300 ring-4 ring-teal-500/10' : 'border-slate-100 hover:border-slate-200'"
                    style="animation-delay: {{ $i * 70 }}ms">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $titulo }}</p>
                    <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="resumenMostrado.{{ $clave }}">0</p>
                </div>
                <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $color }}">
                    <i class="bi {{ $icono }}"></i>
                </span>
            </button>
        @endforeach
    </div>

    {{-- ===================== LISTADO ===================== --}}
    <div class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d: 220ms">

        <div class="p-5 sm:p-6 border-b border-slate-100 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-box-seam"></i></span>
                    <div>
                        <h3 class="text-base font-black text-slate-800">Medicamentos</h3>
                        <p class="text-[11px] font-semibold text-slate-400"
                           x-text="filtrados.length === productos.length ? productos.length + ' en catálogo' : filtrados.length + ' de ' + productos.length + ' medicamentos'"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1 md:w-80">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="search" x-ref="buscar" x-model.debounce.150ms="searchQuery" placeholder="Código, nombre, categoría…"
                               class="campo-busqueda w-full pl-9 pr-9 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-semibold focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all">
                        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-400 border border-slate-200 rounded px-1.5 py-0.5 hidden sm:block" x-show="!searchQuery">/</kbd>
                    </div>
                    <div class="relative flex bg-slate-100 p-1 rounded-2xl shrink-0">
                        <span class="absolute top-1 bottom-1 w-9 rounded-xl bg-white shadow-sm transition-all duration-300" :style="viewMode === 'list' ? 'left:4px' : 'left:40px'"></span>
                        <button @click="cambiarVista('list')" type="button" title="Vista de tabla" class="relative z-10 w-9 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" :class="viewMode === 'list' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'">
                            <i class="bi bi-list-task"></i>
                        </button>
                        <button @click="cambiarVista('grid')" type="button" title="Vista de tarjetas" class="relative z-10 w-9 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" :class="viewMode === 'grid' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'">
                            <i class="bi bi-grid-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Filtros rápidos --}}
            <div class="flex flex-wrap items-center gap-2">
                <template x-for="e in estados" :key="e.valor">
                    <button type="button" @click="estado = e.valor"
                            :class="estado === e.valor ? 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-600/20' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'"
                            class="px-3 py-1.5 rounded-full border text-[11px] font-bold transition-all cursor-pointer" x-text="e.texto"></button>
                </template>

                <select x-model="categoriaFiltro" aria-label="Filtrar por categoría"
                        class="bg-slate-50 border border-slate-200 rounded-full px-3 py-1.5 text-[11px] font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $cat)
                        <option value="{{ \Illuminate\Support\Str::lower($cat->nombre) }}">{{ \Illuminate\Support\Str::upper($cat->nombre) }}</option>
                    @endforeach
                </select>

                @if ($puedeCrear)
                    <button type="button" @click="openCategoriaModal = true" title="Gestionar categorías"
                            class="px-3 py-1.5 rounded-full border border-slate-200 bg-white text-[11px] font-bold text-slate-500 hover:border-teal-300 hover:text-teal-700 flex items-center gap-1.5 transition-all cursor-pointer">
                        <i class="bi bi-tags-fill text-teal-600"></i> Categorías
                    </button>
                @endif

                <button type="button" @click="orden = orden === 'nombre' ? 'stock' : 'nombre'"
                        class="ml-auto flex items-center gap-1.5 text-[11px] font-bold text-slate-500 hover:text-teal-700 cursor-pointer">
                    <i class="bi" :class="orden === 'nombre' ? 'bi-sort-alpha-down' : 'bi-sort-numeric-down'"></i>
                    <span x-text="orden === 'nombre' ? 'Nombre A-Z' : 'Menor stock primero'"></span>
                </button>
                <button type="button" x-show="hayFiltros" x-cloak @click="limpiarFiltros()" class="text-[11px] font-black text-teal-700 hover:text-teal-900 cursor-pointer">Limpiar</button>
            </div>
        </div>

        {{-- ---------- TABLA ---------- --}}
        <div x-show="viewMode === 'list' && filtrados.length" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 text-[10px] text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-5">Código</th>
                        <th class="py-3.5 px-5">Medicamento</th>
                        <th class="py-3.5 px-5 hidden md:table-cell">Categoría</th>
                        <th class="py-3.5 px-5 text-right hidden lg:table-cell">P. compra</th>
                        <th class="py-3.5 px-5 text-right">P. venta</th>
                        <th class="py-3.5 px-5">Existencias</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    <template x-for="(p, i) in pagina" :key="p.id">
                        <tr class="fila-in group hover:bg-teal-50/30 transition-colors" :style="'--d:' + Math.min(i * 35, 400) + 'ms'"
                            :class="p._saliendo && 'fila-sale'">
                            <td class="py-3.5 px-5">
                                <span class="font-black text-teal-800 bg-teal-50 border border-teal-100 px-2 py-1 rounded-lg uppercase whitespace-nowrap" x-text="p.codigo"></span>
                            </td>
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110"
                                          :class="colorCategoria(p._categoria)"><i class="bi bi-capsule"></i></span>
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 uppercase truncate max-w-[220px]" :title="p.nombre" x-text="p.nombre"></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase truncate max-w-[220px]" :title="p.descripcion" x-text="p.descripcion || 'Sin descripción'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-5 hidden md:table-cell">
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-50 border border-slate-200 rounded-full px-2.5 py-1 uppercase whitespace-nowrap" x-text="p._categoria"></span>
                            </td>
                            <td class="py-3.5 px-5 text-right hidden lg:table-cell text-slate-500 tabular-nums" x-text="dinero(p.precio_compra)"></td>
                            <td class="py-3.5 px-5 text-right font-black text-slate-900 tabular-nums" x-text="dinero(p.precio_venta)"></td>
                            <td class="py-3.5 px-5">
                                <div class="w-36 space-y-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <p class="font-black tabular-nums" :class="colorEstado(p._estado).texto" x-text="p.stock_disponible + ' pzas.'"></p>
                                        <p class="text-[10px] font-bold text-slate-400 tabular-nums" x-text="p.stock_actual + ' físicas'"></p>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500" :class="colorEstado(p._estado).barra" :style="'width:' + nivel(p) + '%'"></div>
                                    </div>
                                    <p class="text-[10px] font-bold" :class="colorEstado(p._estado).texto" x-text="textoEstado(p)"></p>
                                </div>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if ($puedeEditar)
                                        <button type="button" @click="abrirSurtir(p)" title="Surtir existencias"
                                                class="h-8 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-black flex items-center gap-1.5 shadow-sm hover:shadow-md transition-all active:scale-95 cursor-pointer">
                                            <i class="bi bi-box-arrow-in-down"></i><span class="hidden sm:inline">Surtir</span>
                                        </button>
                                        <button type="button" @click="abrirEditar(p)" title="Editar"
                                                class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 hover:scale-110 flex items-center justify-center transition-all active:scale-95 cursor-pointer">
                                            <i class="bi bi-pencil-fill text-[11px]"></i>
                                        </button>
                                    @endif
                                    @if ($puedeEliminar)
                                        <button type="button" @click="eliminar(p)" title="Eliminar"
                                                class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:scale-110 flex items-center justify-center transition-all active:scale-95 cursor-pointer">
                                            <i class="bi bi-trash-fill text-[11px]"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- ---------- TARJETAS ---------- --}}
        <div x-show="viewMode === 'grid' && filtrados.length" x-cloak class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="(p, i) in pagina" :key="p.id">
                <div class="fila-in group relative bg-white rounded-2xl border border-slate-200 p-5 hover:border-teal-400 hover:shadow-lg hover:shadow-teal-900/5 hover:-translate-y-1 transition-all flex flex-col gap-4 overflow-hidden"
                     :style="'--d:' + Math.min(i * 40, 400) + 'ms'" :class="p._saliendo && 'fila-sale'">
                    <i class="bi bi-capsule-pill absolute -right-3 -bottom-4 text-7xl text-slate-50 group-hover:text-teal-50 transition-colors pointer-events-none"></i>

                    <div class="relative flex items-center justify-between gap-2">
                        <span class="text-[11px] font-black text-teal-800 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-100 uppercase truncate" x-text="p.codigo"></span>
                        <span class="text-[10px] font-bold text-slate-600 bg-slate-50 border border-slate-200 rounded-full px-2 py-0.5 uppercase truncate" x-text="p._categoria"></span>
                    </div>

                    <div class="relative flex items-center gap-3">
                        <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-base shrink-0" :class="colorCategoria(p._categoria)"><i class="bi bi-capsule"></i></span>
                        <div class="min-w-0">
                            <h4 class="font-black text-slate-800 text-xs truncate uppercase" x-text="p.nombre"></h4>
                            <p class="text-[11px] text-slate-400 font-bold truncate uppercase" x-text="p.descripcion || 'Sin descripción'"></p>
                        </div>
                    </div>

                    <div class="relative space-y-1">
                        <div class="flex items-baseline justify-between">
                            <p class="text-sm font-black tabular-nums" :class="colorEstado(p._estado).texto" x-text="p.stock_disponible + ' pzas. disponibles'"></p>
                            <p class="text-[10px] font-bold text-slate-400 tabular-nums" x-text="p.stock_actual + ' físicas'"></p>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full" :class="colorEstado(p._estado).barra" :style="'width:' + nivel(p) + '%'"></div>
                        </div>
                        <p class="text-[10px] font-bold" :class="colorEstado(p._estado).texto" x-text="textoEstado(p)"></p>
                    </div>

                    <div class="relative flex items-center gap-2 pt-3 border-t border-slate-100">
                        <div class="mr-auto leading-tight">
                            <p class="text-sm font-black text-slate-900 tabular-nums" x-text="dinero(p.precio_venta)"></p>
                            <p class="text-[10px] font-bold text-slate-400 tabular-nums" x-text="'Compra ' + dinero(p.precio_compra)"></p>
                        </div>
                        @if ($puedeEliminar)
                            <button type="button" @click="eliminar(p)" title="Eliminar"
                                    class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition-all cursor-pointer">
                                <i class="bi bi-trash-fill text-[11px]"></i>
                            </button>
                        @endif
                        @if ($puedeEditar)
                            <button type="button" @click="abrirEditar(p)" title="Editar"
                                    class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition-all cursor-pointer">
                                <i class="bi bi-pencil-fill text-[11px]"></i>
                            </button>
                            <button type="button" @click="abrirSurtir(p)"
                                    class="h-8 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-black flex items-center gap-1.5 transition-all cursor-pointer">
                                <i class="bi bi-box-arrow-in-down"></i> Surtir
                            </button>
                        @endif
                    </div>
                </div>
            </template>
        </div>

        {{-- Mostrar más --}}
        <div x-show="filtrados.length > limite" class="px-6 pb-6 pt-2 text-center">
            <button type="button" @click="limite += 24"
                    class="px-5 py-2.5 rounded-2xl border border-slate-200 text-xs font-black text-slate-600 hover:border-teal-300 hover:text-teal-700 transition-all cursor-pointer"
                    x-text="'Mostrar más (' + (filtrados.length - limite) + ' restantes)'"></button>
        </div>

        {{-- Vacío --}}
        <div x-show="!filtrados.length" x-cloak class="py-16 text-center px-6">
            <span class="flotar inline-flex w-16 h-16 rounded-3xl items-center justify-center text-3xl mb-3"
                  :class="productos.length ? 'bg-slate-100 text-slate-400' : 'bg-teal-50 text-teal-500'">
                <i class="bi" :class="productos.length ? 'bi-search' : 'bi-box-seam'"></i>
            </span>
            <p class="text-sm font-black text-slate-600" x-text="productos.length ? 'Ningún medicamento coincide con la búsqueda' : 'Aún no hay medicamentos en el inventario'"></p>
            <button type="button" x-show="productos.length" @click="limpiarFiltros()" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">Quitar filtros</button>
            @if ($puedeCrear)
                <button type="button" x-show="!productos.length" @click="openCreateModal = true" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">+ Registrar el primero</button>
            @endif
        </div>
    </div>

    {{-- ===================== MODALES ===================== --}}
    @include('inventario.modalAgregarProducto')
    @include('inventario.modalEditarProducto')
    @include('inventario.modalSurtirStock')         {{-- Surtir individual --}}
    @include('inventario.modalGestionarCategorias')
    @include('inventario.modalSurtirLote')          {{-- Surtir lote desde el encabezado --}}
</div>

<style>
    /* Mayúsculas en lo que escribe el usuario (excepto el buscador) */
    input[type="text"]:not(.campo-busqueda), textarea { text-transform: uppercase !important; }

    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes filaIn   { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes filaSale { to { opacity: 0; transform: translateX(30px); } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes capsula  { 0%, 100% { transform: translateY(0) rotate(-12deg); } 50% { transform: translateY(-10px) rotate(8deg); } }
    @keyframes latidoLento { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @keyframes ecg { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .fila-in      { animation: filaIn .4s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .fila-sale    { animation: filaSale .35s ease-in forwards !important; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .ecg-linea    { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }
    .latido-lento { display: inline-block; animation: latidoLento 1.6s ease-in-out infinite; }
    .capsula      { display: block; animation: capsula 4s ease-in-out infinite; }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .fila-in, .flotar, .capsula, .latido-lento, .ecg-linea { animation: none !important; }
    }
</style>

<script>
    const URL_INVENTARIO = @js(url('inventario'));
    const CSRF_INVENTARIO = @js(csrf_token());

    function avisoInventario(mensaje, icono = 'success') {
        if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
    }

    function moduloInventario() {
        return {
            // ----- Datos -----
            productos: @json($productosJs),

            // ----- Vista y filtros -----
            searchQuery: '',
            categoriaFiltro: '',
            estado: 'todos',
            orden: 'nombre',
            viewMode: 'list',
            limite: 24,
            estados: [
                { valor: 'todos',   texto: 'Todos' },
                { valor: 'ok',      texto: 'Con existencia' },
                { valor: 'bajo',    texto: 'Stock bajo' },
                { valor: 'agotado', texto: 'Agotados' }
            ],

            // ----- Modales (los usan los @include de inventario.*) -----
            openCreateModal: @js($errors->any() || session('error')),
            openSurtirModal: false,
            openEditModal: false,
            openCategoriaModal: false,
            openLoteModal: false,
            productoSeleccionado: {},
            productoEditar: {},

            resumenMostrado: { total: 0, ok: 0, bajo: 0, agotados: 0 },

            init() {
                try { this.viewMode = localStorage.getItem('inventario_vista') || (window.innerWidth < 768 ? 'grid' : 'list'); } catch (e) {}

                ['searchQuery', 'categoriaFiltro', 'estado', 'orden'].forEach(k => this.$watch(k, () => { this.limite = 24; }));

                this.$nextTick(() => this.animarResumen());

                window.addEventListener('keydown', e => {
                    const hayModal = this.openCreateModal || this.openSurtirModal || this.openEditModal || this.openCategoriaModal || this.openLoteModal;
                    if (e.key === '/' && !hayModal && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                        e.preventDefault();
                        this.$refs.buscar.focus();
                    }
                });
            },

            // ----- Resumen -----
            get resumen() {
                const l = this.productos;
                return {
                    total: l.length,
                    ok: l.filter(p => p._estado === 'ok').length,
                    bajo: l.filter(p => p._estado === 'bajo').length,
                    agotados: l.filter(p => p._estado === 'agotado').length
                };
            },
            animarResumen() {
                const meta = this.resumen;
                const sinMov = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                Object.keys(meta).forEach(k => {
                    const destino = meta[k], origen = this.resumenMostrado[k];
                    if (sinMov || origen === destino) { this.resumenMostrado[k] = destino; return; }
                    const t0 = performance.now(), dur = 800;
                    const paso = t => {
                        const p = Math.min(1, (t - t0) / dur);
                        this.resumenMostrado[k] = Math.round(origen + (destino - origen) * (1 - Math.pow(1 - p, 3)));
                        if (p < 1) requestAnimationFrame(paso);
                    };
                    requestAnimationFrame(paso);
                });
            },

            // ----- Filtros -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
            get filtrados() {
                const q = this.normalizar(this.searchQuery);
                const cat = this.normalizar(this.categoriaFiltro);
                const lista = this.productos.filter(p =>
                    (!q || p._buscar.includes(q)) &&
                    (!cat || this.normalizar(p._categoria) === cat) &&
                    (this.estado === 'todos' || p._estado === this.estado)
                );
                return lista.sort((a, b) => this.orden === 'stock'
                    ? (Number(a.stock_disponible) - Number(b.stock_disponible)) || String(a.nombre).localeCompare(String(b.nombre), 'es')
                    : String(a.nombre).localeCompare(String(b.nombre), 'es'));
            },
            get pagina() { return this.filtrados.slice(0, this.limite); },
            get hayFiltros() { return this.searchQuery.trim() !== '' || this.categoriaFiltro !== '' || this.estado !== 'todos'; },
            limpiarFiltros() { this.searchQuery = ''; this.categoriaFiltro = ''; this.estado = 'todos'; },
            cambiarVista(v) {
                this.viewMode = v;
                try { localStorage.setItem('inventario_vista', v); } catch (e) {}
            },

            // ----- Formato -----
            dinero(v) { return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            colorCategoria(n) {
                const colores = ['bg-teal-100 text-teal-800', 'bg-sky-100 text-sky-800', 'bg-violet-100 text-violet-800', 'bg-amber-100 text-amber-800', 'bg-rose-100 text-rose-800', 'bg-emerald-100 text-emerald-800'];
                let h = 0;
                for (const ch of String(n || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
                return colores[h % colores.length];
            },
            colorEstado(e) {
                return {
                    ok:      { texto: 'text-slate-800', barra: 'bg-emerald-500' },
                    bajo:    { texto: 'text-amber-600', barra: 'bg-amber-500' },
                    agotado: { texto: 'text-rose-600',  barra: 'bg-rose-500' }
                }[e] || { texto: 'text-slate-800', barra: 'bg-slate-400' };
            },
            // Llenado de la barra: el mínimo equivale a la mitad
            nivel(p) {
                const disp = Number(p.stock_disponible) || 0;
                const tope = Math.max((Number(p.stock_minimo) || 0) * 2, 1);
                return disp <= 0 ? 0 : Math.max(6, Math.min(100, Math.round(disp / tope * 100)));
            },
            textoEstado(p) {
                if (p._estado === 'agotado') return 'Agotado';
                if (p._estado === 'bajo') return 'Stock bajo · mínimo ' + p.stock_minimo;
                return 'Mínimo ' + p.stock_minimo;
            },

            // ----- Acciones -----
            abrirSurtir(p) {
                this.productoSeleccionado = JSON.parse(JSON.stringify(p));
                this.openSurtirModal = true;
            },
            abrirEditar(p) {
                this.productoEditar = JSON.parse(JSON.stringify(p));
                this.openEditModal = true;
            },
            eliminar(p) {
                Swal.fire({
                    title: '¿Eliminar medicamento?',
                    html: `Se eliminará <b style="color:#0f766e">${this.escapar(p.codigo)}</b> · <b>${this.escapar(p.nombre)}</b>.<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f43f5e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    showLoaderOnConfirm: true,
                    customClass: { popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try {
                            const res = await fetch(`${URL_INVENTARIO}/${p.id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': CSRF_INVENTARIO, 'Accept': 'application/json' }
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || data.success === false) throw new Error(data.message || 'No se pudo eliminar el medicamento.');
                            return data;
                        } catch (e) {
                            Swal.showValidationMessage(e.message || 'Error de conexión');
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(result => {
                    if (!result.isConfirmed) return;
                    p._saliendo = true;
                    setTimeout(() => {
                        this.productos = this.productos.filter(x => x.id !== p.id);
                        this.animarResumen();
                    }, 330);
                    avisoInventario((result.value && result.value.message) || 'Medicamento eliminado.', 'success');
                });
            },
            escapar(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
        };
    }
</script>
@endsection