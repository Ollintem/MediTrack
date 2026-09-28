{{-- MODAL: EDITAR MEDICAMENTO --}}
{{-- Usa del index: openEditModal, productoEditar, openCreateModal, productos (códigos repetidos) y abrirSurtir() --}}
@php
    $categoriasEditar = collect($categorias ?? [])->map(fn ($c) => ['id' => (string) $c->id, 'nombre' => $c->nombre])->values();

    // Si el servidor regresó errores de ESTE formulario, se vuelve a abrir la edición (y no el de registrar)
    $reabrirEdicion = $errors->any() && old('_form') === 'editar' ? [
        'id'            => old('_producto_id'),
        'codigo'        => old('codigo'),
        'categoria_id'  => old('categoria_id'),
        'nombre'        => old('nombre'),
        'precio_compra' => old('precio_compra'),
        'precio_venta'  => old('precio_venta'),
        'stock_minimo'  => old('stock_minimo'),
        'descripcion'   => old('descripcion'),
        'stock_actual'     => old('_stock_actual'),
        'stock_disponible' => old('_stock_disponible'),
    ] : null;
@endphp

<template x-teleport="body">
    <div x-show="openEditModal" x-cloak
         x-data="formEditarProducto()"
         @categorias-inventario.window="actualizarCategorias($event.detail)"
         @keydown.escape.window="openEditModal && !enviando && cerrar()"
         class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-3xl max-h-[94vh] flex flex-col overflow-hidden"
             @click.outside="!enviando && cerrar()"
             x-show="openEditModal"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">

            {{-- ===== Encabezado ===== --}}
            <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-700 text-white shrink-0 overflow-hidden">
                <div class="absolute -right-8 -top-10 w-36 h-36 rounded-full bg-white/5"></div>
                <i class="bi bi-pencil absolute right-16 -bottom-6 text-7xl text-white/5 -rotate-12 pointer-events-none"></i>
                <div class="relative flex items-center gap-4">
                    <span class="w-12 h-12 rounded-2xl bg-amber-400/20 border border-amber-300/30 flex items-center justify-center text-xl text-amber-300 shrink-0 me-pop">
                        <i class="bi bi-pencil-square"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-amber-300 flex items-center gap-1.5">
                            <i class="bi bi-heart-pulse-fill me-latido"></i> Editando medicamento
                        </p>
                        <h3 class="text-base font-black tracking-tight truncate uppercase" x-text="original.nombre || 'Medicamento'"></h3>
                        <p class="text-[10px] font-mono font-bold text-teal-100/70" x-text="original.codigo"></p>
                    </div>
                    <span x-show="cambios.length" x-transition.opacity class="hidden sm:inline-flex items-center gap-1.5 text-[10px] font-black bg-amber-400 text-amber-950 rounded-full px-2.5 py-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-900 me-parpadeo"></span>
                        <span x-text="cambios.length + (cambios.length === 1 ? ' cambio' : ' cambios')"></span>
                    </span>
                    <button type="button" @click="cerrar()" :disabled="enviando" title="Cerrar (Esc)"
                            class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <form :action="urlActualizar" method="POST" @submit="enviar($event)" class="flex flex-col flex-1 min-h-0" novalidate>
                @csrf
                @method('PUT')
                {{-- Datos para volver a abrir este modal si el servidor regresa errores --}}
                <input type="hidden" name="_form" value="editar">
                <input type="hidden" name="_producto_id" :value="productoEditar.id">
                <input type="hidden" name="_stock_actual" :value="productoEditar.stock_actual">
                <input type="hidden" name="_stock_disponible" :value="productoEditar.stock_disponible">

                <div class="flex-1 overflow-y-auto p-5 sm:p-6">
                    @if ($errors->any() && old('_form') === 'editar')
                        <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex gap-2.5 me-shake">
                            <i class="bi bi-exclamation-octagon-fill text-rose-500 text-base shrink-0"></i>
                            <div class="space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Existencias actuales (se modifican con "Surtir", no aquí) --}}
                    <div class="me-seccion mb-5 rounded-2xl border p-4 flex flex-col sm:flex-row sm:items-center gap-4"
                         :class="estado === 'agotado' ? 'bg-rose-50/50 border-rose-200' : (estado === 'bajo' ? 'bg-amber-50/50 border-amber-200' : 'bg-slate-50 border-slate-200')" style="--d:40ms">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <span class="w-11 h-11 rounded-2xl bg-white flex items-center justify-center text-lg shadow-sm shrink-0"
                                  :class="{ 'text-rose-500': estado === 'agotado', 'text-amber-500': estado === 'bajo', 'text-emerald-600': estado === 'ok' }">
                                <i class="bi bi-box-seam-fill"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xl font-black tabular-nums text-slate-800" x-text="disponible"></span>
                                    <span class="text-[11px] font-bold text-slate-500">piezas disponibles</span>
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full uppercase"
                                          :class="{ 'bg-rose-100 text-rose-700': estado === 'agotado', 'bg-amber-100 text-amber-700': estado === 'bajo', 'bg-emerald-100 text-emerald-700': estado === 'ok' }"
                                          x-text="{ agotado: 'Agotado', bajo: 'Stock bajo', ok: 'Suficiente' }[estado]"></span>
                                </div>
                                <div class="relative mt-1.5 h-2 rounded-full bg-white">
                                    <div class="h-full rounded-full transition-all duration-500" :style="'width:' + nivel + '%'"
                                         :class="{ 'bg-rose-500': estado === 'agotado', 'bg-amber-400': estado === 'bajo', 'bg-emerald-500': estado === 'ok' }"></div>
                                    <span x-show="Number(productoEditar.stock_minimo) > 0" class="absolute -top-0.5 -bottom-0.5 w-0.5 rounded bg-slate-500/60 transition-all duration-500" :style="'left:' + posMinimo + '%'"></span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 mt-1"
                                   x-text="fisico + ' físicas' + (fisico > disponible ? ' · ' + (fisico - disponible) + ' reservadas' : '') + ' · la marca indica el mínimo'"></p>
                            </div>
                        </div>
                        <button type="button" x-show="typeof abrirSurtir === 'function'" @click="irASurtir()"
                                class="shrink-0 h-9 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                            <i class="bi bi-box-arrow-in-down"></i> Surtir existencias
                        </button>
                    </div>

                    <div class="space-y-5">
                        {{-- ===== 1. Identificación ===== --}}
                        <section class="me-seccion" style="--d:100ms">
                            <h4 class="me-titulo"><span>1</span> Identificación</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Código --}}
                                <div>
                                    <label for="ep-codigo" class="me-label">Código de barras / SKU * <span x-show="cambio('codigo')" class="me-cambio">modificado</span></label>
                                    <input id="ep-codigo" type="text" name="codigo" x-model="productoEditar.codigo" required maxlength="50" autocomplete="off"
                                           @input="productoEditar.codigo = String(productoEditar.codigo).toUpperCase().replace(/\s/g, '')"
                                           @keydown.enter.prevent="$refs.nombre.focus()"
                                           class="me-campo font-mono tracking-wider uppercase"
                                           :class="codigoRepetido || (intento && !String(productoEditar.codigo || '').trim()) ? 'me-campo-error' : ''">
                                    <p class="me-ayuda" :class="codigoRepetido || avisoEan ? 'text-rose-600' : 'text-slate-400'"
                                       x-text="codigoRepetido ? 'Ya lo usa: ' + codigoRepetido.nombre : (avisoEan || (cambio('codigo') ? 'Antes: ' + original.codigo : 'Escanéalo o escríbelo'))"></p>
                                    @error('codigo')<p class="me-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>

                                {{-- Categoría --}}
                                <div class="relative" @click.outside="abiertoCat = false">
                                    <label class="me-label">Categoría * <span x-show="cambio('categoria_id')" class="me-cambio">modificado</span></label>
                                    <input type="hidden" name="categoria_id" :value="productoEditar.categoria_id">
                                    <button type="button" @click="abiertoCat = !abiertoCat; $nextTick(() => abiertoCat && $refs.buscarCat.focus())"
                                            class="me-campo text-left flex items-center justify-between gap-2 cursor-pointer"
                                            :class="intento && !productoEditar.categoria_id ? 'me-campo-error' : ''">
                                        <span class="capitalize truncate" :class="nombreCategoria ? 'text-slate-800' : 'text-slate-400'" x-text="(nombreCategoria || 'Selecciona una categoría').toLowerCase()"></span>
                                        <i class="bi bi-chevron-down text-slate-400 text-xs transition-transform" :class="abiertoCat && 'rotate-180'"></i>
                                    </button>
                                    <div x-show="abiertoCat" x-cloak
                                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                         class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
                                        <div class="p-2 border-b border-slate-100 space-y-2">
                                            <button type="button" @click="abrirCategorias()"
                                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 text-[11px] font-black transition-all cursor-pointer">
                                                <i class="bi bi-plus-circle-fill"></i> Agregar nueva categoría
                                            </button>
                                            <input type="text" x-ref="buscarCat" x-model="buscarCat" placeholder="Buscar categoría…" style="text-transform:none"
                                                   @keydown.enter.prevent="categoriasFiltradas[0] ? elegirCategoria(categoriasFiltradas[0]) : abrirCategorias()"
                                                   class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold outline-none focus:border-teal-400">
                                        </div>
                                        <div class="max-h-48 overflow-y-auto py-1">
                                            <template x-for="c in categoriasFiltradas" :key="c.id">
                                                <button type="button" @click="elegirCategoria(c)"
                                                        class="w-full text-left px-4 py-2 text-xs font-bold hover:bg-teal-50 hover:text-teal-700 flex items-center justify-between capitalize transition-colors cursor-pointer"
                                                        :class="String(productoEditar.categoria_id) === c.id ? 'text-teal-700 bg-teal-50/60' : 'text-slate-600'">
                                                    <span x-text="c.nombre.toLowerCase()"></span>
                                                    <i x-show="String(productoEditar.categoria_id) === c.id" class="bi bi-check-lg text-teal-600"></i>
                                                </button>
                                            </template>
                                            <div x-show="!categoriasFiltradas.length" class="px-4 py-3 text-[11px]">
                                                <p class="text-slate-400 italic">No se encontró la categoría.</p>
                                                <button type="button" @click="abrirCategorias()" class="mt-1 font-black text-teal-700 hover:text-teal-900 cursor-pointer"
                                                        x-text="'+ Agregar «' + buscarCat.trim().toUpperCase() + '»'"></button>
                                            </div>
                                        </div>
                                    </div>
                                    @error('categoria_id')<p class="me-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="ep-nombre" class="me-label">Nombre y presentación * <span x-show="cambio('nombre')" class="me-cambio">modificado</span></label>
                                <input id="ep-nombre" type="text" name="nombre" x-model="productoEditar.nombre" x-ref="nombre" required maxlength="150"
                                       class="me-campo uppercase" :class="intento && !String(productoEditar.nombre || '').trim() ? 'me-campo-error' : ''">
                                @error('nombre')<p class="me-ayuda text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="mt-4">
                                <label for="ep-desc" class="me-label flex justify-between">
                                    <span>Descripción / sustancia activa <span x-show="cambio('descripcion')" class="me-cambio">modificado</span></span>
                                    <span class="normal-case font-semibold text-slate-400 tabular-nums" x-text="String(productoEditar.descripcion || '').length + '/255'"></span>
                                </label>
                                <textarea id="ep-desc" name="descripcion" x-model="productoEditar.descripcion" rows="2" maxlength="255" class="me-campo resize-none uppercase"></textarea>
                            </div>
                        </section>

                        {{-- ===== 2. Precios y alerta ===== --}}
                        <section class="me-seccion" style="--d:180ms">
                            <h4 class="me-titulo"><span>2</span> Precios y alerta de stock</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="ep-compra" class="me-label">Precio de compra ($) * <span x-show="cambio('precio_compra')" class="me-cambio">modificado</span></label>
                                    <input id="ep-compra" type="number" name="precio_compra" x-model="productoEditar.precio_compra" step="0.01" min="0" required class="me-campo tabular-nums"
                                           :class="intento && productoEditar.precio_compra === '' ? 'me-campo-error' : ''">
                                    <p x-show="cambio('precio_compra')" class="me-ayuda" :class="variacion('precio_compra') > 0 ? 'text-rose-600' : 'text-emerald-600'"
                                       x-text="'Antes ' + dinero(original.precio_compra) + ' (' + (variacion('precio_compra') > 0 ? '+' : '') + variacion('precio_compra') + '%)'"></p>
                                    @error('precio_compra')<p class="me-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="ep-venta" class="me-label">Precio de venta ($) * <span x-show="cambio('precio_venta')" class="me-cambio">modificado</span></label>
                                    <input id="ep-venta" type="number" name="precio_venta" x-model="productoEditar.precio_venta" step="0.01" min="0" required class="me-campo tabular-nums"
                                           :class="(intento && productoEditar.precio_venta === '') || (margen !== null && margen < 0) ? 'me-campo-error' : ''">
                                    <p x-show="cambio('precio_venta')" class="me-ayuda" :class="variacion('precio_venta') > 0 ? 'text-emerald-600' : 'text-rose-600'"
                                       x-text="'Antes ' + dinero(original.precio_venta) + ' (' + (variacion('precio_venta') > 0 ? '+' : '') + variacion('precio_venta') + '%)'"></p>
                                    @error('precio_venta')<p class="me-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="ep-minimo" class="me-label">Stock mínimo * <span x-show="cambio('stock_minimo')" class="me-cambio">modificado</span></label>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" @click="productoEditar.stock_minimo = Math.max(0, (Number(productoEditar.stock_minimo) || 0) - 1)" class="me-paso">−</button>
                                        <input id="ep-minimo" type="number" name="stock_minimo" x-model="productoEditar.stock_minimo" min="0" step="1" required class="me-campo text-center tabular-nums">
                                        <button type="button" @click="productoEditar.stock_minimo = (Number(productoEditar.stock_minimo) || 0) + 1" class="me-paso">+</button>
                                    </div>
                                    @error('stock_minimo')<p class="me-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase">Calcular venta:</span>
                                <template x-for="m in [30, 50, 80, 100]" :key="m">
                                    <button type="button" @click="aplicarMargen(m)" :disabled="!(Number(productoEditar.precio_compra) > 0)"
                                            class="px-2.5 py-1 rounded-full border border-slate-200 text-[10px] font-black text-slate-500 hover:border-teal-300 hover:text-teal-700 hover:bg-teal-50 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                            x-text="'+' + m + '%'"></button>
                                </template>
                                <span x-show="margen !== null" class="ml-auto text-[11px] font-black px-2.5 py-1 rounded-full"
                                      :class="margen < 0 ? 'bg-rose-50 text-rose-600' : (margen < 15 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-700')"
                                      x-text="margen < 0 ? 'Pérdida de ' + dinero(Math.abs(ganancia)) + ' por pieza' : 'Ganancia ' + dinero(ganancia) + ' (' + margen + '%)'"></span>
                            </div>
                        </section>

                        {{-- ===== Resumen de cambios ===== --}}
                        <section x-show="cambios.length" x-transition.opacity class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
                            <h4 class="text-[11px] font-black text-amber-800 uppercase tracking-wide flex items-center gap-1.5 mb-2">
                                <i class="bi bi-list-check"></i> Se guardarán estos cambios
                                <button type="button" @click="restaurar()" class="ml-auto normal-case text-[11px] font-black text-amber-700 hover:text-amber-900 underline underline-offset-2 cursor-pointer">Deshacer todo</button>
                            </h4>
                            <ul class="space-y-1">
                                <template x-for="c in cambios" :key="c.campo">
                                    <li class="text-[11px] font-semibold text-slate-700 flex flex-wrap items-center gap-1.5">
                                        <span class="font-black text-slate-800" x-text="c.etiqueta + ':'"></span>
                                        <span class="line-through text-slate-400 truncate max-w-[180px]" x-text="c.antes || '(vacío)'"></span>
                                        <i class="bi bi-arrow-right text-amber-600"></i>
                                        <span class="font-black text-teal-700 truncate max-w-[200px]" x-text="c.despues || '(vacío)'"></span>
                                    </li>
                                </template>
                            </ul>
                        </section>
                    </div>
                </div>

                {{-- ===== Pie ===== --}}
                <div class="flex items-center gap-2.5 px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                    <p x-show="!cambios.length" class="hidden sm:block text-[11px] font-bold text-slate-400 mr-auto">Sin cambios por guardar</p>
                    <span class="ml-auto"></span>
                    <button type="button" @click="cerrar()" :disabled="enviando"
                            class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-2xl text-xs font-extrabold transition-all cursor-pointer disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="enviando || !cambios.length"
                            class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-black shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                        <span x-show="enviando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <i x-show="!enviando" class="bi bi-check-lg"></i>
                        <span x-text="enviando ? 'Guardando…' : 'Guardar cambios'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style>
    .me-seccion { animation: meSube .45s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .me-titulo { display: flex; align-items: center; gap: .5rem; font-size: 11px; font-weight: 900; color: #0f766e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .75rem; }
    .me-titulo span { width: 1.25rem; height: 1.25rem; border-radius: .5rem; background: #0f766e; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; }
    .me-label { display: block; font-size: 10px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .375rem; }
    .me-cambio { display: inline-block; margin-left: .35rem; font-size: 9px; font-weight: 900; color: #b45309; background: #fef3c7; border-radius: 9999px; padding: 0 .4rem; text-transform: none; letter-spacing: 0; }
    .me-campo { width: 100%; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 1rem; padding: .625rem .875rem; font-size: .75rem; font-weight: 700; color: #1e293b; outline: none; transition: all .15s; }
    .me-campo:focus { background: #fff; border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20, 184, 166, .12); }
    .me-campo-error { border-color: #fb7185 !important; background: #fff1f2; }
    .me-ayuda { font-size: 10px; font-weight: 700; margin-top: .3rem; }
    .me-paso { width: 2.5rem; height: 2.5rem; flex: none; border-radius: .9rem; background: #f1f5f9; color: #475569; font-weight: 900; font-size: 1rem; transition: all .15s; cursor: pointer; }
    .me-paso:hover { background: #ccfbf1; color: #0f766e; }
    .me-paso:active { transform: scale(.9); }
    .me-campo[type=number]::-webkit-inner-spin-button, .me-campo[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .me-campo[type=number] { -moz-appearance: textfield; }
    .me-pop { animation: mePop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .me-latido { display: inline-block; animation: meLatido 1.6s ease-in-out infinite; }
    .me-parpadeo { animation: meParpadeo 1.2s ease-in-out infinite; }
    .me-shake { animation: meShake .4s ease-in-out; }

    @keyframes meSube     { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    @keyframes mePop      { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes meLatido   { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.25); } 30% { transform: scale(1); } 45% { transform: scale(1.15); } }
    @keyframes meParpadeo { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
    @keyframes meShake    { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }

    @media (prefers-reduced-motion: reduce) {
        .me-seccion, .me-pop, .me-latido, .me-parpadeo, .me-shake { animation: none !important; }
    }
</style>

<script>
    function formEditarProducto() {
        const CAMPOS = [
            ['codigo', 'Código'], ['categoria_id', 'Categoría'], ['nombre', 'Nombre'], ['descripcion', 'Descripción'],
            ['precio_compra', 'Precio de compra'], ['precio_venta', 'Precio de venta'], ['stock_minimo', 'Stock mínimo']
        ];
        const texto = v => (v === null || v === undefined) ? '' : String(v).trim();
        const esPrecio = k => k === 'precio_compra' || k === 'precio_venta';

        return {
            categorias: @json($categoriasEditar),
            reabrir: @json($reabrirEdicion),
            urlBase: @js(url('inventario')),
            original: {},
            abiertoCat: false,
            buscarCat: '',
            intento: false,
            enviando: false,
            pausado: false,   // true mientras se está en el gestor de categorías

            init() {
                // Cada vez que se abre, se guarda una copia para comparar los cambios
                this.$watch('openEditModal', abierto => {
                    if (!abierto) return;
                    // Regresa del gestor de categorías: se conserva la edición tal como estaba
                    if (this.pausado) { this.pausado = false; return; }
                    this.tomarOriginal();
                    this.intento = false;
                    this.enviando = false;
                    this.abiertoCat = false;
                });

                // Regresó del servidor con errores de este formulario: reabrir la edición
                if (this.reabrir && this.reabrir.id) {
                    const lista = Array.isArray(this.productos) ? this.productos : [];
                    const base = (lista.find(p => String(p.id) === String(this.reabrir.id)) || {}).raw || {};
                    this.openCreateModal = false;
                    this.productoEditar = { ...base, ...this.reabrir };
                    this.$nextTick(() => {
                        this.openEditModal = true;
                        if (base.id) this.original = this.normalizarProducto(base);
                        this.intento = true;
                    });
                }
            },

            normalizarProducto(p) {
                const o = {};
                CAMPOS.forEach(([k]) => { o[k] = esPrecio(k) ? Number(p[k] || 0).toFixed(2) : texto(p[k]); });
                o.categoria_id = texto(p.categoria_id);
                return o;
            },
            tomarOriginal() {
                const p = this.productoEditar || {};
                // Precios con 2 decimales para que "80" y "80.00" no cuenten como cambio
                if (p.precio_compra !== undefined && p.precio_compra !== '') p.precio_compra = Number(p.precio_compra).toFixed(2);
                if (p.precio_venta !== undefined && p.precio_venta !== '') p.precio_venta = Number(p.precio_venta).toFixed(2);
                if (p.categoria_id !== undefined && p.categoria_id !== null) p.categoria_id = String(p.categoria_id);
                this.original = this.normalizarProducto(p);
            },
            restaurar() {
                Object.keys(this.original).forEach(k => { this.productoEditar[k] = this.original[k]; });
            },

            get urlActualizar() { return this.productoEditar && this.productoEditar.id ? `${this.urlBase}/${this.productoEditar.id}` : '#'; },

            // ----- Cambios -----
            valorActual(k) { const v = this.productoEditar ? this.productoEditar[k] : ''; return esPrecio(k) ? (v === '' ? '' : Number(v).toFixed(2)) : texto(v); },
            cambio(k) { return this.original[k] !== undefined && this.valorActual(k).toUpperCase() !== String(this.original[k]).toUpperCase(); },
            get cambios() {
                if (!this.openEditModal) return [];
                return CAMPOS.filter(([k]) => this.cambio(k)).map(([k, etiqueta]) => {
                    let antes = this.original[k], despues = this.valorActual(k);
                    if (k === 'categoria_id') { antes = this.nombreDe(antes); despues = this.nombreDe(despues); }
                    if (esPrecio(k)) { antes = this.dinero(antes); despues = despues === '' ? '' : this.dinero(despues); }
                    return { campo: k, etiqueta, antes, despues };
                });
            },
            variacion(k) {
                const a = Number(this.original[k]), b = Number(this.productoEditar[k]);
                return a > 0 ? Math.round((b - a) / a * 100) : 0;
            },

            // ----- Categoría -----
            nombreDe(id) { const c = this.categorias.find(c => c.id === String(id)); return c ? c.nombre : ''; },
            get nombreCategoria() {
                return this.nombreDe(this.productoEditar.categoria_id) || (this.productoEditar.categoria && this.productoEditar.categoria.nombre) || '';
            },
            get categoriasFiltradas() {
                const q = this.normalizar(this.buscarCat);
                return this.categorias.filter(c => !q || this.normalizar(c.nombre).includes(q));
            },
            elegirCategoria(c) {
                this.productoEditar.categoria_id = c.id;
                this.productoEditar.categoria = { ...(this.productoEditar.categoria || {}), id: c.id, nombre: c.nombre };
                this.abiertoCat = false;
                this.buscarCat = '';
            },
            // Abre el gestor de categorías sin perder lo que se lleva editado
            abrirCategorias() {
                window.__categoriaDesde = { origen: 'editar', nombre: this.buscarCat.trim() };
                this.pausado = true;
                this.abiertoCat = false;
                this.buscarCat = '';
                this.openEditModal = false;
                setTimeout(() => { this.openCategoriaModal = true; }, 200);
            },
            // Lo dispara el gestor al crear, renombrar o eliminar
            actualizarCategorias(d) {
                if (!d || !Array.isArray(d.lista)) return;
                this.categorias = d.lista;
                if (d.creada && d.para === 'editar') this.elegirCategoria({ id: String(d.creada.id), nombre: d.creada.nombre });
                if (d.eliminada && String(this.productoEditar.categoria_id) === String(d.eliminada.id)) {
                    this.productoEditar.categoria_id = '';
                    this.productoEditar.categoria = null;
                }
            },

            // ----- Código -----
            get codigoRepetido() {
                const lista = Array.isArray(this.productos) ? this.productos : [];
                const codigo = texto(this.productoEditar.codigo).toUpperCase();
                return codigo ? lista.find(p => String(p.codigo).toUpperCase() === codigo && String(p.id) !== String(this.productoEditar.id)) : null;
            },
            get avisoEan() {
                const c = texto(this.productoEditar.codigo);
                if (!/^\d{13}$/.test(c)) return '';
                let suma = 0;
                for (let i = 0; i < 12; i++) suma += Number(c[i]) * (i % 2 === 0 ? 1 : 3);
                return (10 - (suma % 10)) % 10 === Number(c[12]) ? '' : 'El último dígito no coincide: revisa que el código esté bien capturado.';
            },

            // ----- Precios -----
            get ganancia() { return Number(this.productoEditar.precio_venta || 0) - Number(this.productoEditar.precio_compra || 0); },
            get margen() {
                const c = Number(this.productoEditar.precio_compra);
                if (this.productoEditar.precio_venta === '' || !(c > 0)) return null;
                return Math.round(this.ganancia / c * 100);
            },
            aplicarMargen(pct) {
                const c = Number(this.productoEditar.precio_compra);
                if (c > 0) this.productoEditar.precio_venta = (Math.round(c * (1 + pct / 100) * 100) / 100).toFixed(2);
            },
            dinero(n) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

            // ----- Existencias (solo lectura) -----
            get disponible() { return Number(this.productoEditar.stock_disponible ?? this.productoEditar.stock_actual ?? 0); },
            get fisico() { return Number(this.productoEditar.stock_actual ?? this.disponible); },
            get estado() { const m = Number(this.productoEditar.stock_minimo) || 0; return this.disponible <= 0 ? 'agotado' : (this.disponible <= m ? 'bajo' : 'ok'); },
            get escala() { return Math.max((Number(this.productoEditar.stock_minimo) || 0) * 3, this.fisico, this.disponible, 1); },
            get nivel() { return Math.min(100, Math.round(this.disponible / this.escala * 100)); },
            get posMinimo() { return Math.min(100, Math.round((Number(this.productoEditar.stock_minimo) || 0) / this.escala * 100)); },
            irASurtir() {
                const producto = JSON.parse(JSON.stringify(this.productoEditar));
                const seguir = () => { this.openEditModal = false; setTimeout(() => this.abrirSurtir(producto), 200); };
                if (!this.cambios.length || !window.Swal) return seguir();
                Swal.fire({
                    title: '¿Salir sin guardar?', text: 'Tienes cambios sin guardar en este medicamento.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Salir y surtir', cancelButtonText: 'Seguir editando',
                    confirmButtonColor: '#0d9488', customClass: { container: 'z-[10050]', popup: 'rounded-3xl' }
                }).then(r => { if (r.isConfirmed) seguir(); });
            },

            // ----- Enviar y cerrar -----
            get faltantes() {
                const p = this.productoEditar, falta = [];
                if (!texto(p.codigo)) falta.push('código');
                if (!texto(p.categoria_id)) falta.push('categoría');
                if (!texto(p.nombre)) falta.push('nombre');
                if (p.precio_compra === '' || p.precio_compra === null) falta.push('precio de compra');
                if (p.precio_venta === '' || p.precio_venta === null) falta.push('precio de venta');
                if (p.stock_minimo === '' || p.stock_minimo === null) falta.push('stock mínimo');
                return falta;
            },
            enviar(e) {
                this.intento = true;
                if (this.enviando) { e.preventDefault(); return; }
                if (this.faltantes.length || this.codigoRepetido) {
                    e.preventDefault();
                    const mensaje = this.codigoRepetido ? 'Ese código ya lo usa otro medicamento.' : 'Completa: ' + this.faltantes.join(', ') + '.';
                    if (typeof window.notificar === 'function') window.notificar(mensaje, 'warning');
                    return;
                }
                this.enviando = true;
            },
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
            cerrar() {
                if (this.enviando) return;
                if (!this.cambios.length || !window.Swal) { this.openEditModal = false; return; }
                Swal.fire({
                    title: '¿Descartar los cambios?', text: 'Los cambios en este medicamento no se guardarán.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Descartar', cancelButtonText: 'Seguir editando',
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#0d9488',
                    customClass: { container: 'z-[10050]', popup: 'rounded-3xl' }
                }).then(r => { if (r.isConfirmed) this.openEditModal = false; });
            }
        };
    }
</script>