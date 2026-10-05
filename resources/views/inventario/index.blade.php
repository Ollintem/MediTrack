@extends('layouts.admin')

@section('content')
@php
    $productos  = $productos ?? collect();
    $categorias = $categorias ?? collect();
    if ($productos instanceof \Illuminate\Database\Eloquent\Collection) {
        $productos->loadMissing('categoria');
    }

    $puedeCrear    = auth()->user()->tienePermiso('Inventario', 'crear');
    $puedeEditar   = auth()->user()->tienePermiso('Inventario', 'editar');
    $puedeEliminar = auth()->user()->tienePermiso('Inventario', 'eliminar');

    $productosJs = $productos->map(function ($p) {
        $disponible = (int) ($p->stock_disponible ?? 0);
        $minimo     = (int) ($p->stock_minimo ?? 0);
        $compra     = (float) ($p->precio_compra ?? 0);
        $venta      = (float) ($p->precio_venta ?? 0);

        $caducidad = data_get($p, 'fecha_caducidad') ?: data_get($p, 'caducidad');
        try { $caducidad = $caducidad ? \Illuminate\Support\Carbon::parse($caducidad)->format('Y-m-d') : null; } catch (\Throwable $e) { $caducidad = null; }

        $estado = $disponible <= 0 ? 'agotado' : ($disponible <= $minimo ? 'bajo' : 'ok');

        return [
            'id'          => $p->id,
            'codigo'      => strtoupper((string) $p->codigo),
            'nombre'      => (string) $p->nombre,
            'descripcion' => (string) ($p->descripcion ?? ''),
            'categoria'   => data_get($p, 'categoria.nombre') ?: 'General',
            'compra'      => $compra,
            'venta'       => $venta,
            'margen'      => $compra > 0 ? round(($venta - $compra) / $compra * 100) : null,
            'disponible'  => $disponible,
            'minimo'      => $minimo,
            'estado'      => $estado,
            'caducidad'   => $caducidad,
            'buscar'      => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(implode(' ', [
                $p->codigo, $p->nombre, $p->descripcion, data_get($p, 'categoria.nombre'),
            ]))),
            'raw'         => $p->toArray(),
        ];
    })->values();

    $categoriasJs = $categorias->pluck('nombre')->filter()->unique()->sortBy(fn ($n) => mb_strtolower($n))->values();

    $tarjetas = [
        ['Medicamentos',     'total',    'bi-capsule',                  'bg-teal-50 text-teal-600',    'todos'],
        ['Stock bajo',       'bajo',     'bi-exclamation-triangle-fill','bg-amber-50 text-amber-600',  'bajo'],
        ['Agotados',          'agotado',  'bi-x-octagon-fill',           'bg-rose-50 text-rose-500',    'agotado'],
        ['Valor en almacén', 'valor',    'bi-cash-stack',               'bg-sky-50 text-sky-600',      null],
    ];
@endphp

<div class="space-y-6 w-full block" x-data="moduloInventario()">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-48 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        <svg class="absolute inset-x-0 bottom-2 w-full h-12 opacity-25 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        <div class="absolute right-10 top-8 hidden lg:flex gap-3 opacity-20 pointer-events-none">
            <span class="capsula w-7 h-16 rounded-full bg-gradient-to-b from-white from-50% to-emerald-300 to-50%"></span>
            <span class="capsula w-7 h-16 rounded-full bg-gradient-to-b from-emerald-200 from-50% to-white to-50%" style="animation-delay:-1.2s"></span>
            <span class="capsula w-10 h-10 mt-6 rounded-full bg-white" style="animation-delay:-2.4s"></span>
        </div>

        <div class="relative p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wide text-emerald-300 backdrop-blur-md">
                    <i class="bi bi-heart-pulse-fill latido-lento"></i> Almacén de farmacia
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-3">
                    Inventario de medicamentos <i class="bi bi-heart-pulse-fill text-emerald-300 text-2xl latido-lento"></i>
                </h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Catálogo de fármacos, existencias disponibles y alertas de reabastecimiento.
                </p>
            </div>

            @if ($puedeCrear)
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <button @click="openLoteModal = true" type="button"
                            class="group bg-white/10 hover:bg-white/20 text-white font-bold px-4 py-2.5 rounded-2xl text-xs border border-white/20 backdrop-blur-md transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-boxes text-emerald-300 text-sm transition-transform group-hover:scale-110"></i> Surtir lote
                    </button>
                    <button @click="openCreateModal = true" type="button"
                            class="group bg-white text-teal-900 hover:bg-emerald-50 font-black px-5 py-2.5 rounded-2xl text-xs shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-plus-lg text-sm text-teal-700 transition-transform duration-300 group-hover:rotate-90"></i> Registrar medicamento
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== RESUMEN ===================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $color, $filtroTarjeta])
            <button type="button" @if ($filtroTarjeta) @click="estadoFiltro = (estadoFiltro === '{{ $filtroTarjeta }}' ? 'todos' : '{{ $filtroTarjeta }}')" @endif
                    class="tarjeta-stat group text-left bg-white rounded-2xl border shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1 {{ $filtroTarjeta ? 'cursor-pointer' : 'cursor-default' }}"
                    :class="'{{ $filtroTarjeta }}' && estadoFiltro === '{{ $filtroTarjeta }}' && '{{ $filtroTarjeta }}' !== 'todos' ? 'border-teal-300 ring-2 ring-teal-100' : 'border-slate-100 hover:border-slate-200'"
                    style="animation-delay: {{ $i * 70 }}ms">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $titulo }}</p>
                        <p class="text-2xl font-black mt-1 tabular-nums truncate"
                           :class="('{{ $clave }}' === 'bajo' && resumen.bajo) ? 'text-amber-600' : (('{{ $clave }}' === 'agotado' && resumen.agotado) ? 'text-rose-600' : 'text-slate-800')"
                           x-text="'{{ $clave }}' === 'valor' ? dinero(resumenMostrado.valor, 0) : resumenMostrado.{{ $clave }}">0</p>
                    </div>
                    <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $color }}">
                        <i class="bi {{ $icono }}" @if ($clave === 'agotado') :class="resumen.agotado && 'latido-lento'" @endif></i>
                    </span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-2"
                   x-text="{ total: resumen.unidades + ' piezas disponibles', bajo: resumen.bajo ? 'Clic para ver cuáles' : 'Todo en orden', agotado: resumen.agotado ? 'Clic para ver cuáles' : 'Sin faltantes', valor: 'Costo de compra × existencias' }['{{ $clave }}']"></p>
            </button>
        @endforeach
    </div>

    {{-- ===================== LISTADO ===================== --}}
    <div class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d: 220ms">

        <div class="p-5 sm:p-6 border-b border-slate-100 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="flex items-center gap-3 mr-auto">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-box-seam"></i></span>
                    <div>
                        <h3 class="text-base font-black text-slate-800">Catálogo</h3>
                        <p class="text-[11px] font-semibold text-slate-400"
                           x-text="filtrados.length === productos.length ? productos.length + ' medicamentos' : filtrados.length + ' de ' + productos.length + ' medicamentos'"></p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative flex-1 min-w-[220px] lg:w-72">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="search" x-ref="buscar" x-model.debounce.150ms="searchQuery" placeholder="Código, nombre o descripción…"
                               class="w-full pl-9 pr-9 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-semibold focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all">
                        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-400 border border-slate-200 rounded px-1.5 py-0.5 hidden sm:block" x-show="!searchQuery">/</kbd>
                    </div>
                    <select x-model="orden" aria-label="Ordenar"
                            class="bg-slate-50 border border-slate-200 rounded-2xl px-3 py-2.5 text-[11px] font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                        <option value="nombre">Nombre (A-Z)</option>
                        <option value="stock">Menos existencias primero</option>
                        <option value="venta">Precio de venta (mayor)</option>
                        <option value="margen">Margen (mayor)</option>
                    </select>
                    @if ($puedeCrear)
                        <button type="button" @click="openCategoriaModal = true" title="Gestionar categorías"
                                class="h-10 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl text-[11px] font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-tags-fill text-teal-600"></i><span class="hidden sm:inline">Categorías</span>
                        </button>
                    @endif
                    <div class="relative flex bg-slate-100 p-1 rounded-2xl shrink-0">
                        <span class="absolute top-1 bottom-1 w-9 rounded-xl bg-white shadow-sm transition-all duration-300" :style="vista === 'lista' ? 'left:4px' : 'left:40px'"></span>
                        <button @click="cambiarVista('lista')" type="button" title="Vista de tabla" class="relative z-10 w-9 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" :class="vista === 'lista' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"><i class="bi bi-list-task"></i></button>
                        <button @click="cambiarVista('tarjetas')" type="button" title="Vista de tarjetas" class="relative z-10 w-9 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" :class="vista === 'tarjetas' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'"><i class="bi bi-grid-fill"></i></button>
                    </div>
                </div>
            </div>

            {{-- Estado + categorías --}}
            <div class="flex flex-wrap items-center gap-2">
                <template x-for="e in estados" :key="e.valor">
                    <button type="button" @click="estadoFiltro = e.valor"
                            :class="estadoFiltro === e.valor ? e.activo : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'"
                            class="px-3 py-1.5 rounded-full border text-[11px] font-bold transition-all cursor-pointer flex items-center gap-1.5">
                        <span x-show="e.punto" class="w-1.5 h-1.5 rounded-full" :class="e.punto"></span>
                        <span x-text="e.texto"></span>
                        <span class="text-[9px] font-black opacity-70" x-text="conteoEstado(e.valor)"></span>
                    </button>
                </template>
                <span class="w-px h-5 bg-slate-200 mx-1 hidden sm:block"></span>
                <select x-model="categoriaFiltro" aria-label="Filtrar por categoría"
                        class="bg-slate-50 border border-slate-200 rounded-full px-3 py-1.5 text-[11px] font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer capitalize">
                    <option value="">Todas las categorías</option>
                    <template x-for="c in categorias" :key="c">
                        <option :value="c" x-text="c.toLowerCase() + ' (' + productos.filter(p => p.categoria === c).length + ')'"></option>
                    </template>
                </select>
                <button type="button" x-show="hayFiltros" x-cloak @click="limpiarFiltros()" class="text-[11px] font-black text-teal-700 hover:text-teal-900 cursor-pointer">Limpiar</button>

                @if ($puedeCrear)
                    <button type="button" x-show="estadoFiltro === 'bajo' || estadoFiltro === 'agotado'" x-cloak @click="openLoteModal = true"
                            class="ml-auto h-8 px-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black flex items-center gap-1.5 shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                        <i class="bi bi-boxes"></i> Reabastecer en lote
                    </button>
                @endif
            </div>
        </div>

        {{-- ---------- TABLA ---------- --}}
        <div x-show="vista === 'lista' && filtrados.length" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 text-[10px] text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-5">Medicamento</th>
                        <th class="py-3.5 px-5 hidden md:table-cell">Categoría</th>
                        <th class="py-3.5 px-5 text-right hidden lg:table-cell">Compra</th>
                        <th class="py-3.5 px-5 text-right">Venta</th>
                        <th class="py-3.5 px-5 min-w-[190px]">Existencias</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    <template x-for="(p, i) in pagina" :key="p.id">
                        <tr class="fila-in group transition-colors"
                            :class="[p._saliendo && 'fila-sale', p.estado === 'agotado' ? 'bg-rose-50/30 hover:bg-rose-50/60' : 'hover:bg-teal-50/30']"
                            :style="'--d:' + Math.min(i * 30, 400) + 'ms'">
                            <td class="py-3.5 px-5 relative">
                                <span class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full"
                                      :class="{ 'bg-rose-500': p.estado === 'agotado', 'bg-amber-400': p.estado === 'bajo', 'bg-transparent': p.estado === 'ok' }"></span>
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center text-base shrink-0 transition-transform group-hover:scale-110 group-hover:-rotate-6"
                                          :class="colorCategoria(p.categoria)">
                                        <i class="bi bi-capsule"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 text-sm capitalize truncate max-w-[260px]" x-text="p.nombre.toLowerCase()"></p>
                                        <p class="text-[10px] font-bold text-slate-400 flex items-center gap-1.5">
                                            <button type="button" @click="copiar(p.codigo)" class="font-mono text-teal-700 hover:underline cursor-pointer" :title="'Copiar código ' + p.codigo" x-text="p.codigo"></button>
                                            <span x-show="p.descripcion" class="truncate max-w-[180px] capitalize" x-text="'· ' + p.descripcion.toLowerCase()"></span>
                                        </p>
                                        <p x-show="p.caducidad && diasParaCaducar(p) <= 60" class="text-[10px] font-black mt-0.5" :class="diasParaCaducar(p) < 0 ? 'text-rose-600' : 'text-amber-600'"
                                           x-text="diasParaCaducar(p) < 0 ? 'Caducado' : 'Caduca en ' + diasParaCaducar(p) + ' días'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-5 hidden md:table-cell">
                                <button type="button" @click="categoriaFiltro = p.categoria" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold capitalize border cursor-pointer hover:scale-105 transition-transform"
                                        :class="colorCategoria(p.categoria) + ' border-transparent'" x-text="p.categoria.toLowerCase()"></button>
                            </td>
                            <td class="py-3.5 px-5 text-right hidden lg:table-cell text-slate-500 tabular-nums" x-text="dinero(p.compra)"></td>
                            <td class="py-3.5 px-5 text-right">
                                <p class="font-black text-slate-900 tabular-nums" x-text="dinero(p.venta)"></p>
                                <p x-show="p.margen !== null" class="text-[10px] font-bold" :class="p.margen < 0 ? 'text-rose-600' : (p.margen < 15 ? 'text-amber-600' : 'text-emerald-600')"
                                   x-text="(p.margen >= 0 ? '+' : '') + p.margen + '% margen'"></p>
                            </td>
                            <td class="py-3.5 px-5">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="text-sm font-black tabular-nums" :class="{ 'text-rose-600': p.estado === 'agotado', 'text-amber-600': p.estado === 'bajo', 'text-slate-800': p.estado === 'ok' }"
                                          x-text="p.disponible + ' pzas.'"></span>
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase"
                                          :class="{ 'bg-rose-100 text-rose-700': p.estado === 'agotado', 'bg-amber-100 text-amber-700': p.estado === 'bajo', 'bg-emerald-50 text-emerald-700': p.estado === 'ok' }"
                                          x-text="{ agotado: 'Agotado', bajo: 'Stock bajo', ok: 'Suficiente' }[p.estado]"></span>
                                </div>
                                <div class="relative mt-1.5 h-2 rounded-full bg-slate-100 overflow-visible" :title="'Mínimo: ' + p.minimo + ' pzas.'">
                                    <div class="barra-stock h-full rounded-full" :style="'width:' + nivel(p) + '%'"
                                         :class="{ 'bg-rose-500': p.estado === 'agotado', 'bg-amber-400': p.estado === 'bajo', 'bg-emerald-500': p.estado === 'ok' }"></div>
                                    <span x-show="p.minimo > 0" class="absolute -top-0.5 -bottom-0.5 w-0.5 rounded bg-slate-500/60" :style="'left:' + posMinimo(p) + '%'"></span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 mt-1">
                                    <span x-text="'Mínimo ' + p.minimo"></span>
                                </p>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if ($puedeEditar)
                                        <button type="button" @click="abrirSurtir(p.raw)" title="Surtir existencias"
                                                class="h-8 px-3 rounded-xl text-[11px] font-black flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer"
                                                :class="p.estado === 'ok' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/20'">
                                            <i class="bi bi-box-arrow-in-down"></i><span class="hidden xl:inline">Surtir</span>
                                        </button>
                                        <button type="button" @click="abrirEditar(p.raw)" title="Editar"
                                                class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 hover:scale-110 flex items-center justify-center transition-all active:scale-95 cursor-pointer">
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
        <div x-show="vista === 'tarjetas' && filtrados.length" x-cloak class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="(p, i) in pagina" :key="p.id">
                <div class="fila-in group relative bg-white rounded-3xl border p-5 hover:shadow-lg hover:shadow-teal-900/5 hover:-translate-y-1 transition-all overflow-hidden"
                     :class="[p._saliendo && 'fila-sale', p.estado === 'agotado' ? 'border-rose-200' : (p.estado === 'bajo' ? 'border-amber-200' : 'border-slate-200 hover:border-teal-300')]"
                     :style="'--d:' + Math.min(i * 40, 400) + 'ms'">
                    <i class="bi bi-capsule absolute -right-4 -bottom-6 text-8xl text-slate-50 group-hover:text-teal-50 group-hover:rotate-12 transition-all pointer-events-none"></i>
                    <div class="relative flex items-start gap-3">
                        <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0" :class="colorCategoria(p.categoria)"><i class="bi bi-capsule"></i></span>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-black text-slate-900 text-sm capitalize truncate" x-text="p.nombre.toLowerCase()"></h4>
                            <p class="text-[10px] font-bold text-slate-400 truncate"><span class="font-mono text-teal-700" x-text="p.codigo"></span> · <span class="capitalize" x-text="p.categoria.toLowerCase()"></span></p>
                        </div>
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full uppercase shrink-0"
                              :class="{ 'bg-rose-100 text-rose-700': p.estado === 'agotado', 'bg-amber-100 text-amber-700': p.estado === 'bajo', 'bg-emerald-50 text-emerald-700': p.estado === 'ok' }"
                              x-text="{ agotado: 'Agotado', bajo: 'Bajo', ok: 'OK' }[p.estado]"></span>
                    </div>

                    <div class="relative mt-4">
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black tabular-nums" :class="{ 'text-rose-600': p.estado === 'agotado', 'text-amber-600': p.estado === 'bajo', 'text-slate-800': p.estado === 'ok' }" x-text="p.disponible"></span>
                            <span class="text-[10px] font-bold text-slate-400" x-text="'mín. ' + p.minimo"></span>
                        </div>
                        <div class="relative mt-1.5 h-2 rounded-full bg-slate-100">
                            <div class="barra-stock h-full rounded-full" :style="'width:' + nivel(p) + '%'"
                                 :class="{ 'bg-rose-500': p.estado === 'agotado', 'bg-amber-400': p.estado === 'bajo', 'bg-emerald-500': p.estado === 'ok' }"></div>
                            <span x-show="p.minimo > 0" class="absolute -top-0.5 -bottom-0.5 w-0.5 rounded bg-slate-500/60" :style="'left:' + posMinimo(p) + '%'"></span>
                        </div>
                    </div>

                    <div class="relative mt-4 grid grid-cols-2 gap-2 text-center">
                        <div class="rounded-xl bg-slate-50 py-2">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Compra</p>
                            <p class="text-xs font-bold text-slate-600 tabular-nums" x-text="dinero(p.compra)"></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 py-2">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Venta</p>
                            <p class="text-xs font-black text-slate-900 tabular-nums" x-text="dinero(p.venta)"></p>
                        </div>
                    </div>

                    <div class="relative mt-4 pt-3 border-t border-slate-100 flex items-center gap-1.5">
                        <p class="text-[10px] font-bold mr-auto" :class="p.margen === null ? 'text-slate-300' : (p.margen < 15 ? 'text-amber-600' : 'text-emerald-600')"
                           x-text="p.margen === null ? 'Sin costo registrado' : p.margen + '% de margen'"></p>
                        @if ($puedeEliminar)
                            <button type="button" @click="eliminar(p)" title="Eliminar" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition-all cursor-pointer"><i class="bi bi-trash-fill text-[11px]"></i></button>
                        @endif
                        @if ($puedeEditar)
                            <button type="button" @click="abrirEditar(p.raw)" title="Editar" class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 flex items-center justify-center transition-all cursor-pointer"><i class="bi bi-pencil-fill text-[11px]"></i></button>
                            <button type="button" @click="abrirSurtir(p.raw)" class="h-8 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black flex items-center gap-1.5 transition-all cursor-pointer">
                                <i class="bi bi-box-arrow-in-down"></i> Surtir
                            </button>
                        @endif
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filtrados.length > limite" class="px-6 pb-6 pt-2 text-center">
            <button type="button" @click="limite += 40"
                    class="px-5 py-2.5 rounded-2xl border border-slate-200 text-xs font-black text-slate-600 hover:border-teal-300 hover:text-teal-700 transition-all cursor-pointer"
                    x-text="'Mostrar más (' + (filtrados.length - limite) + ' restantes)'"></button>
        </div>

        <div x-show="!filtrados.length" x-cloak class="py-16 text-center px-6">
            <span class="flotar inline-flex w-16 h-16 rounded-3xl items-center justify-center text-3xl mb-3"
                  :class="productos.length ? 'bg-slate-100 text-slate-400' : 'bg-teal-50 text-teal-500'">
                <i class="bi" :class="productos.length ? (estadoFiltro !== 'todos' ? 'bi-emoji-smile' : 'bi-search') : 'bi-capsule'"></i>
            </span>
            <p class="text-sm font-black text-slate-600"
               x-text="!productos.length ? 'Aún no hay medicamentos registrados' : (estadoFiltro === 'agotado' && !searchQuery && !categoriaFiltro ? '¡No hay medicamentos agotados!' : (estadoFiltro === 'bajo' && !searchQuery && !categoriaFiltro ? '¡Nada con stock bajo!' : 'Ningún medicamento coincide con la búsqueda'))"></p>
            <button type="button" x-show="productos.length" @click="limpiarFiltros()" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">Ver todo el catálogo</button>
            @if ($puedeCrear)
                <button type="button" x-show="!productos.length" @click="openCreateModal = true" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">+ Registrar el primero</button>
            @endif
        </div>
    </div>

    {{-- Modales --}}
    @include('inventario.modalAgregarProducto')
    @include('inventario.modalEditarProducto')
    @include('inventario.modalSurtirStock')
    @include('inventario.modalGestionarCategorias')
    @include('inventario.modalSurtirLote')
</div>

<style>
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes filaIn   { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes filaSale { to { opacity: 0; transform: translateX(30px); } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes latidoLento { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @keyframes ecg      { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
    @keyframes capsula  { 0%, 100% { transform: translateY(0) rotate(-12deg); } 50% { transform: translateY(-10px) rotate(8deg); } }
    @keyframes llenar   { from { width: 0; } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .fila-in      { animation: filaIn .4s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .fila-sale    { animation: filaSale .35s ease-in forwards !important; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .latido-lento { display: inline-block; animation: latidoLento 1.6s ease-in-out infinite; }
    .ecg-linea    { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }
    .capsula      { display: block; animation: capsula 4s ease-in-out infinite; }
    .barra-stock  { animation: llenar .9s cubic-bezier(.16, 1, .3, 1) backwards; transition: width .6s cubic-bezier(.16, 1, .3, 1); }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .fila-in, .flotar, .latido-lento, .ecg-linea, .capsula, .barra-stock { animation: none !important; }
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
            productos: @json($productosJs),
            categorias: @json($categoriasJs),
            hoy: @js(now()->toDateString()),

            // ----- Filtros y vista -----
            searchQuery: '',
            categoriaFiltro: '',
            estadoFiltro: 'todos',
            orden: 'nombre',
            vista: 'lista',
            limite: 40,
            estados: [
                { valor: 'todos',   texto: 'Todos',      punto: '',              activo: 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-600/20' },
                { valor: 'ok',      texto: 'Suficiente', punto: 'bg-emerald-500', activo: 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/20' },
                { valor: 'bajo',    texto: 'Stock bajo', punto: 'bg-amber-400',  activo: 'bg-amber-500 text-white border-amber-500 shadow-md shadow-amber-500/20' },
                { valor: 'agotado', texto: 'Agotados',   punto: 'bg-rose-500',   activo: 'bg-rose-500 text-white border-rose-500 shadow-md shadow-rose-500/20' }
            ],
            resumenMostrado: { total: 0, bajo: 0, agotado: 0, valor: 0 },

            // ----- Modales -----
            openCreateModal: @js($errors->any() || session('error')),
            openSurtirModal: false,
            openEditModal: false,
            openCategoriaModal: false,
            openLoteModal: false,
            productoSeleccionado: {},
            productoEditar: {},

            init() {
                try { this.vista = localStorage.getItem('inventario_vista') || (window.innerWidth < 768 ? 'tarjetas' : 'lista'); } catch (e) {}
                ['searchQuery', 'categoriaFiltro', 'estadoFiltro', 'orden'].forEach(k => this.$watch(k, () => { this.limite = 40; }));
                this.$nextTick(() => this.animarResumen());

                window.addEventListener('keydown', e => {
                    const abierto = this.openCreateModal || this.openEditModal || this.openSurtirModal || this.openCategoriaModal || this.openLoteModal;
                    if (e.key === '/' && !abierto && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                        e.preventDefault();
                        this.$refs.buscar.focus();
                    }
                });

                @if (session('success'))
                    setTimeout(() => avisoInventario(@js(session('success')), 'success'), 300);
                @endif
                @if (session('error'))
                    setTimeout(() => avisoInventario(@js(session('error')), 'error'), 300);
                @endif
            },

            // ----- Resumen -----
            get resumen() {
                return {
                    total: this.productos.length,
                    bajo: this.productos.filter(p => p.estado === 'bajo').length,
                    agotado: this.productos.filter(p => p.estado === 'agotado').length,
                    valor: this.productos.reduce((s, p) => s + (p.compra * p.disponible), 0),
                    unidades: this.productos.reduce((s, p) => s + p.disponible, 0)
                };
            },
            animarResumen() {
                const meta = this.resumen;
                const sinMov = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                ['total', 'bajo', 'agotado', 'valor'].forEach(k => {
                    const destino = meta[k], origen = this.resumenMostrado[k];
                    if (sinMov || origen === destino) { this.resumenMostrado[k] = destino; return; }
                    const t0 = performance.now(), dur = 900;
                    const paso = t => {
                        const p = Math.min(1, (t - t0) / dur);
                        this.resumenMostrado[k] = Math.round(origen + (destino - origen) * (1 - Math.pow(1 - p, 3)));
                        if (p < 1) requestAnimationFrame(paso);
                    };
                    requestAnimationFrame(paso);
                });
            },
            conteoEstado(e) { return e === 'todos' ? this.productos.length : this.productos.filter(p => p.estado === e).length; },

            // ----- Filtros -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
            cumpleFiltro(codigo, nombre, descripcion, categoria) {
                const q = this.normalizar(this.searchQuery);
                return (!q || this.normalizar(codigo + ' ' + nombre + ' ' + descripcion).includes(q))
                    && (!this.categoriaFiltro || categoria === this.categoriaFiltro);
            },
            get filtrados() {
                const q = this.normalizar(this.searchQuery);
                const lista = this.productos.filter(p =>
                    (!q || p.buscar.includes(q)) &&
                    (!this.categoriaFiltro || p.categoria === this.categoriaFiltro) &&
                    (this.estadoFiltro === 'todos' || p.estado === this.estadoFiltro)
                );
                const orden = {
                    nombre: (a, b) => a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' }),
                    stock:  (a, b) => (a.disponible - b.disponible) || a.nombre.localeCompare(b.nombre),
                    venta:  (a, b) => b.venta - a.venta,
                    margen: (a, b) => (b.margen ?? -999) - (a.margen ?? -999)
                }[this.orden];
                return lista.sort(orden);
            },
            get pagina() { return this.filtrados.slice(0, this.limite); },
            get hayFiltros() { return this.searchQuery.trim() !== '' || this.categoriaFiltro !== '' || this.estadoFiltro !== 'todos'; },
            limpiarFiltros() { this.searchQuery = ''; this.categoriaFiltro = ''; this.estadoFiltro = 'todos'; },
            cambiarVista(v) { this.vista = v; try { localStorage.setItem('inventario_vista', v); } catch (e) {} },

            // ----- Formato -----
            dinero(n, dec = 2) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: dec, maximumFractionDigits: dec }); },
            colorCategoria(c) {
                const colores = ['bg-teal-50 text-teal-700', 'bg-sky-50 text-sky-700', 'bg-violet-50 text-violet-700', 'bg-amber-50 text-amber-700', 'bg-rose-50 text-rose-700', 'bg-emerald-50 text-emerald-700', 'bg-indigo-50 text-indigo-700'];
                let h = 0;
                for (const ch of String(c || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
                return colores[h % colores.length];
            },
            escala(p) { return Math.max(p.minimo * 3, p.disponible, 1); },
            nivel(p) { return Math.min(100, Math.round((p.disponible / this.escala(p)) * 100)); },
            posMinimo(p) { return Math.min(100, Math.round((p.minimo / this.escala(p)) * 100)); },
            diasParaCaducar(p) {
                if (!p.caducidad) return Infinity;
                return Math.round((new Date(p.caducidad + 'T00:00:00') - new Date(this.hoy + 'T00:00:00')) / 86400000);
            },

            // ----- Acciones -----
            abrirSurtir(producto) {
                this.productoSeleccionado = JSON.parse(JSON.stringify(producto));
                this.openSurtirModal = true;
            },
            abrirEditar(producto) {
                this.productoEditar = JSON.parse(JSON.stringify(producto));
                this.openEditModal = true;
            },
            async copiar(texto) {
                try { await navigator.clipboard.writeText(texto); avisoInventario('Código copiado: ' + texto, 'success'); }
                catch (e) { avisoInventario('No se pudo copiar', 'warning'); }
            },

            eliminar(p) {
                const aviso = p.disponible > 0 ? `<br><span style="font-size:12px;color:#b45309">Todavía tiene ${p.disponible} piezas en existencia.</span>` : '';
                Swal.fire({
                    title: '¿Eliminar medicamento?',
                    html: `Se eliminará <b>${this.escapar(p.nombre)}</b> (${this.escapar(p.codigo)}) del catálogo.${aviso}<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonColor: '#f43f5e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    showLoaderOnConfirm: true,
                    customClass: { popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try {
                            const res = await fetch(`${URL_INVENTARIO}/${p.id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': CSRF_INVENTARIO, 'Accept': 'application/json' }
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || data.success === false) {
                                throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (data.message || 'No se pudo eliminar el medicamento.'));
                            }
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