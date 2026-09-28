{{-- MODAL: REGISTRAR NUEVO MEDICAMENTO --}}
{{-- Usa del index: openCreateModal (abrir/cerrar), openCategoriaModal (si no hay categorías) y productos (para detectar códigos repetidos) --}}
@php
    $categoriasModal = collect($categorias ?? [])->map(fn ($c) => ['id' => (string) $c->id, 'nombre' => $c->nombre])->values();
    $categoriaInicial = (string) old('categoria_id', $categoriasModal->first()['id'] ?? '');
@endphp

<template x-teleport="body">
    <div x-show="openCreateModal" x-cloak
         x-data="formNuevoProducto()"
         @categorias-inventario.window="actualizarCategorias($event.detail)"
         @keydown.escape.window="openCreateModal && !enviando && cerrar()"
         class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-2xl max-h-[94vh] flex flex-col overflow-hidden"
             @click.outside="!enviando && cerrar()"
             x-show="openCreateModal"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">

            {{-- ===== Encabezado ===== --}}
            <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-700 text-white shrink-0 overflow-hidden">
                <div class="absolute -right-8 -top-10 w-36 h-36 rounded-full bg-white/5"></div>
                <i class="bi bi-capsule absolute right-16 -bottom-6 text-7xl text-white/5 rotate-12 pointer-events-none"></i>
                <div class="relative flex items-center gap-4">
                    <span class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-xl text-emerald-300 shrink-0 mp-pop">
                        <i class="bi bi-capsule"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300 flex items-center gap-1.5">
                            <i class="bi bi-heart-pulse-fill mp-latido"></i> Almacén de farmacia
                        </p>
                        <h3 class="text-base font-black tracking-tight">Registrar nuevo medicamento</h3>
                    </div>
                    <button type="button" @click="cerrar()" :disabled="enviando" title="Cerrar (Esc)"
                            class="ml-auto w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
                {{-- Avance del formulario --}}
                <div class="relative mt-4 flex items-center gap-3">
                    <div class="flex-1 h-1.5 rounded-full bg-white/15 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-300 to-teal-200 transition-all duration-500" :style="'width:' + avance + '%'"></div>
                    </div>
                    <span class="text-[10px] font-black text-teal-100 tabular-nums" x-text="camposListos + ' de 7 campos'"></span>
                </div>
            </div>

            <form action="{{ route('inventario.store') }}" method="POST" x-ref="form" @submit="enviar($event)" class="flex flex-col flex-1 min-h-0" novalidate>
                @csrf

                <div class="flex-1 overflow-y-auto p-5 sm:p-6">
                    {{-- Errores del servidor --}}
                    @if ($errors->any())
                        <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex gap-2.5 mp-shake">
                            <i class="bi bi-exclamation-octagon-fill text-rose-500 text-base shrink-0"></i>
                            <div class="space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="space-y-5">

                        {{-- ===== 1. Identificación ===== --}}
                        <section class="mp-seccion" style="--d:60ms">
                            <h4 class="mp-titulo"><span>1</span> Identificación</h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Código de barras --}}
                                <div>
                                    <label for="np-codigo" class="mp-label">Código de barras (EAN-13) *</label>
                                    <input id="np-codigo" type="text" name="codigo" x-model="f.codigo" x-ref="codigo"
                                           inputmode="numeric" autocomplete="off" maxlength="13" required
                                           @input="f.codigo = f.codigo.replace(/\D/g, '').slice(0, 13)"
                                           @keydown.enter.prevent="$refs.nombre.focus()"
                                           class="mp-campo font-mono tracking-wider"
                                           :class="errorCodigo ? 'mp-campo-error' : (codigoValido ? 'mp-campo-ok' : '')"
                                           placeholder="7501234567890">
                                    {{-- Indicador de dígitos --}}
                                    <div class="flex gap-0.5 mt-1.5" aria-hidden="true">
                                        <template x-for="n in 13" :key="n">
                                            <span class="h-1 flex-1 rounded-full transition-colors duration-200"
                                                  :class="n <= f.codigo.length ? (errorCodigo ? 'bg-rose-400' : 'bg-teal-500') : 'bg-slate-200'"></span>
                                        </template>
                                    </div>
                                    <p class="mp-ayuda" :class="errorCodigo ? 'text-rose-600' : (codigoValido ? 'text-emerald-600' : 'text-slate-400')" x-text="mensajeCodigo"></p>
                                    @error('codigo')<p class="mp-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>

                                {{-- Categoría --}}
                                <div class="relative" @click.outside="abiertoCat = false">
                                    <label class="mp-label">Categoría *</label>
                                    <input type="hidden" name="categoria_id" :value="f.categoria_id">

                                    <template x-if="categorias.length">
                                        <div>
                                            <button type="button" @click="abiertoCat = !abiertoCat; $nextTick(() => abiertoCat && $refs.buscarCat.focus())"
                                                    class="mp-campo text-left flex items-center justify-between gap-2 cursor-pointer"
                                                    :class="intento && !f.categoria_id ? 'mp-campo-error' : ''">
                                                <span class="capitalize truncate" :class="f.categoria_id ? 'text-slate-800' : 'text-slate-400'" x-text="nombreCategoria || 'Selecciona una categoría'"></span>
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
                                                    <input type="text" x-ref="buscarCat" x-model="buscarCat" placeholder="Buscar categoría…"
                                                           @keydown.enter.prevent="categoriasFiltradas[0] ? elegirCategoria(categoriasFiltradas[0]) : abrirCategorias()"
                                                           class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold outline-none focus:border-teal-400 normal-case" style="text-transform:none">
                                                </div>
                                                <div class="max-h-48 overflow-y-auto py-1">
                                                    <template x-for="c in categoriasFiltradas" :key="c.id">
                                                        <button type="button" @click="elegirCategoria(c)"
                                                                class="w-full text-left px-4 py-2 text-xs font-bold hover:bg-teal-50 hover:text-teal-700 flex items-center justify-between capitalize transition-colors cursor-pointer"
                                                                :class="f.categoria_id === c.id ? 'text-teal-700 bg-teal-50/60' : 'text-slate-600'">
                                                            <span x-text="c.nombre.toLowerCase()"></span>
                                                            <i x-show="f.categoria_id === c.id" class="bi bi-check-lg text-teal-600"></i>
                                                        </button>
                                                    </template>
                                                    <div x-show="!categoriasFiltradas.length" class="px-4 py-3 text-[11px]">
                                                        <p class="text-slate-400 italic">No se encontró la categoría.</p>
                                                        <button type="button" @click="abrirCategorias()" class="mt-1 font-black text-teal-700 hover:text-teal-900 cursor-pointer"
                                                                x-text="'+ Agregar «' + buscarCat.trim().toUpperCase() + '»'"></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Sin categorías registradas --}}
                                    <template x-if="!categorias.length">
                                        <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-[11px] font-bold text-amber-800">
                                            <p><i class="bi bi-exclamation-triangle-fill"></i> No hay categorías registradas.</p>
                                            <button type="button" @click="abrirCategorias()" class="mt-1 underline underline-offset-2 cursor-pointer">Crear una categoría</button>
                                        </div>
                                    </template>
                                    @error('categoria_id')<p class="mp-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            {{-- Nombre --}}
                            <div class="mt-4">
                                <label for="np-nombre" class="mp-label">Nombre del medicamento *</label>
                                <input id="np-nombre" type="text" name="nombre" x-model="f.nombre" x-ref="nombre" required maxlength="150"
                                       class="mp-campo uppercase" :class="intento && !f.nombre.trim() ? 'mp-campo-error' : ''"
                                       placeholder="PARACETAMOL 500 MG">
                                @error('nombre')<p class="mp-ayuda text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            {{-- Descripción --}}
                            <div class="mt-4">
                                <label for="np-desc" class="mp-label flex justify-between">
                                    <span>Presentación / sustancia activa <span class="normal-case font-semibold text-slate-400">(opcional)</span></span>
                                    <span class="normal-case font-semibold text-slate-400 tabular-nums" x-text="f.descripcion.length + '/255'"></span>
                                </label>
                                <textarea id="np-desc" name="descripcion" x-model="f.descripcion" rows="2" maxlength="255"
                                          class="mp-campo resize-none uppercase" placeholder="TABLETAS 500 MG, CAJA CON 20 PIEZAS"></textarea>
                            </div>
                        </section>

                        {{-- ===== 2. Precios ===== --}}
                        <section class="mp-seccion" style="--d:140ms">
                            <h4 class="mp-titulo"><span>2</span> Precios</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="np-compra" class="mp-label">Precio de compra ($) *</label>
                                    <input id="np-compra" type="number" name="precio_compra" x-model="f.precio_compra" step="0.01" min="0" required
                                           class="mp-campo tabular-nums" :class="intento && f.precio_compra === '' ? 'mp-campo-error' : ''" placeholder="45.50">
                                    @error('precio_compra')<p class="mp-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="np-venta" class="mp-label">Precio de venta ($) *</label>
                                    <input id="np-venta" type="number" name="precio_venta" x-model="f.precio_venta" step="0.01" min="0" required
                                           class="mp-campo tabular-nums"
                                           :class="(intento && f.precio_venta === '') || margen !== null && margen < 0 ? 'mp-campo-error' : ''" placeholder="80.00">
                                    @error('precio_venta')<p class="mp-ayuda text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            {{-- Margen rápido --}}
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase">Calcular venta:</span>
                                <template x-for="m in [30, 50, 80, 100]" :key="m">
                                    <button type="button" @click="aplicarMargen(m)" :disabled="!(Number(f.precio_compra) > 0)"
                                            class="px-2.5 py-1 rounded-full border border-slate-200 text-[10px] font-black text-slate-500 hover:border-teal-300 hover:text-teal-700 hover:bg-teal-50 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                            x-text="'+' + m + '%'"></button>
                                </template>
                                <span x-show="margen !== null" x-transition.opacity class="ml-auto text-[11px] font-black px-2.5 py-1 rounded-full"
                                      :class="margen < 0 ? 'bg-rose-50 text-rose-600' : (margen < 15 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-700')"
                                      x-text="margen < 0 ? 'Pérdida de ' + dinero(Math.abs(ganancia)) + ' por pieza' : 'Ganancia ' + dinero(ganancia) + ' (' + margen + '%)'"></span>
                            </div>
                        </section>

                        {{-- ===== 3. Existencias ===== --}}
                        <section class="mp-seccion" style="--d:220ms">
                            <h4 class="mp-titulo"><span>3</span> Existencias</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="np-stock" class="mp-label">Stock inicial *</label>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" @click="f.stock_actual = Math.max(0, (Number(f.stock_actual) || 0) - 1)" class="mp-paso">−</button>
                                        <input id="np-stock" type="number" name="stock_actual" x-model="f.stock_actual" min="0" step="1" required class="mp-campo text-center tabular-nums">
                                        <button type="button" @click="f.stock_actual = (Number(f.stock_actual) || 0) + 1" class="mp-paso">+</button>
                                    </div>
                                    <p class="mp-ayuda text-slate-400">Piezas que entran hoy al almacén.</p>
                                </div>
                                <div>
                                    <label for="np-minimo" class="mp-label">Stock mínimo (alerta) *</label>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" @click="f.stock_minimo = Math.max(0, (Number(f.stock_minimo) || 0) - 1)" class="mp-paso">−</button>
                                        <input id="np-minimo" type="number" name="stock_minimo" x-model="f.stock_minimo" min="0" step="1" required class="mp-campo text-center tabular-nums">
                                        <button type="button" @click="f.stock_minimo = (Number(f.stock_minimo) || 0) + 1" class="mp-paso">+</button>
                                    </div>
                                    <p class="mp-ayuda text-slate-400">Al llegar a esta cantidad se marcará como "stock bajo".</p>
                                </div>
                            </div>
                            <p x-show="Number(f.stock_actual) <= Number(f.stock_minimo) && f.stock_actual !== ''" x-transition.opacity
                               class="mt-3 text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-2xl px-3 py-2 flex items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span x-text="Number(f.stock_actual) === 0 ? 'Se registrará como agotado.' : 'Se registrará con stock bajo desde el inicio.'"></span>
                            </p>
                        </section>
                    </div>
                </div>

                {{-- ===== Pie ===== --}}
                <div class="flex items-center gap-2.5 px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                    <p x-show="intento && faltantes.length" x-cloak class="hidden sm:flex items-center gap-1.5 text-[11px] font-bold text-rose-600 mr-auto">
                        <i class="bi bi-exclamation-circle-fill"></i> <span x-text="'Falta: ' + faltantes.join(', ')"></span>
                    </p>
                    <span class="ml-auto"></span>
                    <button type="button" @click="cerrar()" :disabled="enviando"
                            class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-2xl text-xs font-extrabold transition-all cursor-pointer disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="enviando || !categorias.length"
                            class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-black shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                        <span x-show="enviando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <i x-show="!enviando" class="bi bi-check-lg"></i>
                        <span x-text="enviando ? 'Registrando…' : 'Registrar en catálogo'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style>
    .mp-seccion { animation: mpSube .45s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .mp-titulo { display: flex; align-items: center; gap: .5rem; font-size: 11px; font-weight: 900; color: #0f766e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .75rem; }
    .mp-titulo span { width: 1.25rem; height: 1.25rem; border-radius: .5rem; background: #0f766e; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; }
    .mp-label { display: block; font-size: 10px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .375rem; }
    .mp-campo { width: 100%; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 1rem; padding: .625rem .875rem; font-size: .75rem; font-weight: 700; color: #1e293b; outline: none; transition: all .15s; }
    .mp-campo::placeholder { color: #cbd5e1; font-weight: 600; }
    .mp-campo:focus { background: #fff; border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20, 184, 166, .12); }
    .mp-campo-error { border-color: #fb7185 !important; background: #fff1f2; }
    .mp-campo-ok { border-color: #34d399; }
    .mp-ayuda { font-size: 10px; font-weight: 700; margin-top: .3rem; }
    .mp-paso { width: 2.5rem; height: 2.5rem; flex: none; border-radius: .9rem; background: #f1f5f9; color: #475569; font-weight: 900; font-size: 1rem; transition: all .15s; cursor: pointer; }
    .mp-paso:hover { background: #ccfbf1; color: #0f766e; }
    .mp-paso:active { transform: scale(.9); }
    .mp-pop { animation: mpPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .mp-latido { display: inline-block; animation: mpLatido 1.6s ease-in-out infinite; }
    .mp-shake { animation: mpShake .4s ease-in-out; }
    /* Quita las flechas de los campos numéricos (se usan los botones − / +) */
    .mp-campo[type=number]::-webkit-inner-spin-button, .mp-campo[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .mp-campo[type=number] { -moz-appearance: textfield; }

    @keyframes mpSube   { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    @keyframes mpPop    { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes mpLatido { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.25); } 30% { transform: scale(1); } 45% { transform: scale(1.15); } }
    @keyframes mpShake  { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }

    @media (prefers-reduced-motion: reduce) {
        .mp-seccion, .mp-pop, .mp-latido, .mp-shake { animation: none !important; }
    }
</style>

<script>
    function formNuevoProducto() {
        const vacio = () => ({ codigo: '', categoria_id: '', nombre: '', precio_compra: '', precio_venta: '', stock_actual: 0, stock_minimo: 5, descripcion: '' });

        return {
            categorias: @json($categoriasModal),
            f: {
                codigo:        @js((string) old('codigo', '')),
                categoria_id:  @js($categoriaInicial),
                nombre:        @js((string) old('nombre', '')),
                precio_compra: @js((string) old('precio_compra', '')),
                precio_venta:  @js((string) old('precio_venta', '')),
                stock_actual:  @js((string) old('stock_actual', '0')),
                stock_minimo:  @js((string) old('stock_minimo', '5')),
                descripcion:   @js((string) old('descripcion', ''))
            },
            abiertoCat: false,
            buscarCat: '',
            intento: false,
            enviando: false,

            // ----- Categoría -----
            get nombreCategoria() {
                const c = this.categorias.find(c => c.id === String(this.f.categoria_id));
                return c ? c.nombre : '';
            },
            get categoriasFiltradas() {
                const q = this.normalizar(this.buscarCat);
                return this.categorias.filter(c => !q || this.normalizar(c.nombre).includes(q));
            },
            elegirCategoria(c) {
                this.f.categoria_id = c.id;
                this.abiertoCat = false;
                this.buscarCat = '';
            },
            // Abre el gestor de categorías; lo escrito en el buscador llega como nombre sugerido.
            // El registro solo se oculta: al volver conserva todo lo capturado.
            abrirCategorias() {
                window.__categoriaDesde = { origen: 'crear', nombre: this.buscarCat.trim() };
                this.abiertoCat = false;
                this.buscarCat = '';
                this.openCreateModal = false;
                setTimeout(() => { this.openCategoriaModal = true; }, 200);
            },
            // Lo dispara el gestor al crear, renombrar o eliminar
            actualizarCategorias(d) {
                if (!d || !Array.isArray(d.lista)) return;
                this.categorias = d.lista;
                if (d.creada && d.para === 'crear') this.f.categoria_id = String(d.creada.id);
                if (d.eliminada && String(this.f.categoria_id) === String(d.eliminada.id)) this.f.categoria_id = '';
            },

            // ----- Código de barras EAN-13 -----
            digitoControl(doce) {
                let suma = 0;
                for (let i = 0; i < 12; i++) suma += Number(doce[i]) * (i % 2 === 0 ? 1 : 3);
                return (10 - (suma % 10)) % 10;
            },
            get codigoRepetido() {
                const lista = Array.isArray(this.productos) ? this.productos : [];
                return this.f.codigo.length === 13 ? lista.find(p => String(p.codigo) === this.f.codigo) : null;
            },
            get codigoValido() {
                return this.f.codigo.length === 13 && !this.codigoRepetido && this.digitoControl(this.f.codigo) === Number(this.f.codigo[12]);
            },
            get errorCodigo() {
                return !!this.codigoRepetido || (this.intento && this.f.codigo.length !== 13);
            },
            get mensajeCodigo() {
                const n = this.f.codigo.length;
                if (this.codigoRepetido) return 'Ya existe: ' + this.codigoRepetido.nombre;
                if (n === 0) return 'Escanéalo con el lector o escríbelo.';
                if (n < 13) return 'Faltan ' + (13 - n) + ' dígitos';
                if (this.digitoControl(this.f.codigo) !== Number(this.f.codigo[12])) return 'Revisa el código: el último dígito no coincide (puede tener un error de captura).';
                return 'Código válido';
            },

            // ----- Precios -----
            get ganancia() {
                const c = Number(this.f.precio_compra), v = Number(this.f.precio_venta);
                return (this.f.precio_compra !== '' && this.f.precio_venta !== '') ? v - c : 0;
            },
            get margen() {
                const c = Number(this.f.precio_compra), v = Number(this.f.precio_venta);
                if (this.f.precio_compra === '' || this.f.precio_venta === '' || !(c > 0)) return null;
                return Math.round((v - c) / c * 100);
            },
            aplicarMargen(pct) {
                const c = Number(this.f.precio_compra);
                if (c > 0) this.f.precio_venta = (Math.round(c * (1 + pct / 100) * 100) / 100).toFixed(2);
            },
            dinero(n) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

            // ----- Validación -----
            get faltantes() {
                const f = this.f, falta = [];
                if (f.codigo.length !== 13) falta.push('código');
                if (!f.categoria_id) falta.push('categoría');
                if (!f.nombre.trim()) falta.push('nombre');
                if (f.precio_compra === '') falta.push('precio de compra');
                if (f.precio_venta === '') falta.push('precio de venta');
                if (f.stock_actual === '') falta.push('stock inicial');
                if (f.stock_minimo === '') falta.push('stock mínimo');
                return falta;
            },
            get camposListos() { return 7 - this.faltantes.length; },
            get avance() { return Math.round(this.camposListos / 7 * 100); },

            enviar(e) {
                this.intento = true;
                if (this.enviando) { e.preventDefault(); return; }
                if (this.faltantes.length || this.codigoRepetido) {
                    e.preventDefault();
                    const mensaje = this.codigoRepetido ? 'Ese código ya está registrado.' : 'Completa: ' + this.faltantes.join(', ') + '.';
                    if (typeof window.notificar === 'function') window.notificar(mensaje, 'warning');
                    return;
                }
                this.enviando = true;   // el formulario se envía normalmente al servidor
            },

            // ----- Cerrar -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
            get hayDatos() {
                const f = this.f;
                return !!(f.codigo || f.nombre.trim() || f.precio_compra || f.precio_venta || f.descripcion.trim());
            },
            cerrar() {
                if (this.enviando) return;
                const cerrarYLimpiar = () => {
                    this.openCreateModal = false;
                    setTimeout(() => {
                        const primera = this.categorias[0] ? this.categorias[0].id : '';
                        this.f = { ...vacio(), categoria_id: primera, stock_actual: '0', stock_minimo: '5' };
                        this.intento = false;
                    }, 250);
                };
                if (!this.hayDatos || !window.Swal) return cerrarYLimpiar();
                Swal.fire({
                    title: '¿Descartar el registro?',
                    text: 'Se perderán los datos que capturaste.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Descartar', cancelButtonText: 'Seguir capturando',
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#0d9488',
                    customClass: { container: 'z-[10050]', popup: 'rounded-3xl' }
                }).then(r => { if (r.isConfirmed) cerrarYLimpiar(); });
            },

            init() {
                // Al abrir, pone el cursor en el código (listo para el lector de código de barras)
                this.$watch('openCreateModal', abierto => {
                    if (abierto) setTimeout(() => this.$refs.codigo && this.$refs.codigo.focus(), 320);
                });
                if (this.openCreateModal) setTimeout(() => this.$refs.codigo && this.$refs.codigo.focus(), 400);
            }
        };
    }
</script>