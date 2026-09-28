{{-- MODAL: SURTIR LOTE DE MEDICAMENTOS (varios medicamentos de una misma factura) --}}
{{-- Usa del index: openLoteModal. Envía una entrada por medicamento a la ruta inventario.surtir-lote (misma que antes) --}}
@php
    $productosLote = collect($productos ?? [])->map(function ($p) {
        $disponible = (int) ($p->stock_disponible ?? 0);
        $minimo     = (int) ($p->stock_minimo ?? 0);
        return [
            'id'         => (string) $p->id,
            'codigo'     => strtoupper((string) $p->codigo),
            'nombre'     => (string) $p->nombre,
            'fisico'     => (int) ($p->stock_actual ?? $disponible),
            'disponible' => $disponible,
            'minimo'     => $minimo,
            'compra'     => (float) ($p->precio_compra ?? 0),
            'estado'     => $disponible <= 0 ? 'agotado' : ($disponible <= $minimo ? 'bajo' : 'ok'),
        ];
    })->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
@endphp

<template x-teleport="body">
    <div x-show="openLoteModal" x-cloak
         x-data="surtirLote()"
         @keydown.escape.window="openLoteModal && !enviando && !swalAbierto() && cerrar()"
         class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-4xl max-h-[94vh] flex flex-col overflow-hidden"
             @click.outside="!enviando && !swalAbierto() && cerrar()"
             x-show="openLoteModal"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">

            {{-- ===== Encabezado ===== --}}
            <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-700 text-white shrink-0 overflow-hidden">
                <div class="absolute -right-8 -top-10 w-36 h-36 rounded-full bg-white/5"></div>
                <i class="bi bi-truck absolute right-16 -bottom-5 text-7xl text-white/5 pointer-events-none sl-camion"></i>
                <div class="relative flex items-center gap-4">
                    <span class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-xl text-emerald-300 shrink-0 sl-pop">
                        <i class="bi bi-boxes"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300 flex items-center gap-1.5">
                            <i class="bi bi-heart-pulse-fill sl-latido"></i> Entrada de almacén
                        </p>
                        <h3 class="text-base font-black tracking-tight">Surtir lote de medicamentos</h3>
                        <p class="text-[11px] font-bold text-teal-100/80">Agrega todos los medicamentos de una misma factura y regístralos juntos.</p>
                    </div>
                    <button type="button" @click="cerrar()" :disabled="enviando" title="Cerrar (Esc)"
                            class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">

                {{-- ===== 1. Factura ===== --}}
                <section class="sl-seccion" style="--d:60ms">
                    <h4 class="sl-titulo"><span>1</span> Proveedor y factura</h4>
                    <div class="relative">
                        <i class="bi bi-receipt absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="referencia" x-ref="referencia" maxlength="150" :disabled="enviando"
                               @keydown.enter.prevent="$refs.buscador.focus()"
                               class="sl-campo pl-10 uppercase" :class="intento && !referencia.trim() ? 'sl-error' : ''"
                               placeholder="EJ. FACTURA F-4892 · SURTIFARMA">
                    </div>
                    <p class="text-[10px] font-bold mt-1.5" :class="intento && !referencia.trim() ? 'text-rose-600' : 'text-slate-400'">
                        Se guarda con cada entrada para saber de dónde vino el medicamento.
                    </p>
                </section>

                {{-- ===== 2. Agregar medicamentos ===== --}}
                <section class="sl-seccion" style="--d:130ms">
                    <h4 class="sl-titulo"><span>2</span> Medicamentos recibidos</h4>

                    <div class="relative" @click.outside="abiertoBusqueda = false">
                        <i class="bi bi-upc-scan absolute left-3.5 top-1/2 -translate-y-1/2 text-teal-500"></i>
                        <input type="text" x-ref="buscador" x-model="busqueda" :disabled="enviando" autocomplete="off" style="text-transform:none"
                               @focus="abiertoBusqueda = true" @input="abiertoBusqueda = true; resaltado = 0"
                               @keydown.arrow-down.prevent="resaltado = Math.min(resaltado + 1, resultados.length - 1)"
                               @keydown.arrow-up.prevent="resaltado = Math.max(resaltado - 1, 0)"
                               @keydown.enter.prevent="agregarDesdeBusqueda()"
                               @keydown.escape.stop="abiertoBusqueda = false"
                               class="sl-campo pl-10 pr-28" placeholder="Escanea el código o busca por nombre…">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-400 border border-slate-200 rounded px-1.5 py-0.5 hidden sm:block">Enter = agregar</span>

                        <div x-show="abiertoBusqueda && busqueda.trim() && resultados.length" x-cloak
                             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
                            <div class="max-h-64 overflow-y-auto py-1">
                                <template x-for="(p, i) in resultados" :key="p.id">
                                    <button type="button" @click="agregar(p)" @mouseenter="resaltado = i"
                                            class="w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors cursor-pointer"
                                            :class="resaltado === i ? 'bg-teal-50' : ''">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="punto(p.estado)"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-black text-slate-800 uppercase truncate" x-text="p.nombre"></p>
                                            <p class="text-[10px] font-mono font-bold text-teal-700" x-text="p.codigo"></p>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-400 shrink-0" x-text="p.disponible + ' disp. · mín. ' + p.minimo"></span>
                                        <span x-show="enLista(p)" class="text-[9px] font-black text-teal-700 bg-teal-100 rounded-full px-2 py-0.5 shrink-0">+1</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-show="abiertoBusqueda && busqueda.trim() && !resultados.length" x-cloak
                             class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-slate-200 shadow-xl p-4 text-center text-xs font-bold text-slate-400">
                            No se encontró "<span x-text="busqueda"></span>" en el catálogo.
                        </div>
                    </div>

                    {{-- Atajo: reabastecer lo que hace falta --}}
                    <div x-show="porReponer.length" class="mt-3 flex flex-wrap items-center gap-2 p-3 rounded-2xl bg-amber-50 border border-amber-200">
                        <i class="bi bi-lightbulb-fill text-amber-500"></i>
                        <p class="text-[11px] font-bold text-amber-800 flex-1 min-w-[180px]"
                           x-text="porReponer.length + (porReponer.length === 1 ? ' medicamento está' : ' medicamentos están') + ' en stock bajo o agotado.'"></p>
                        <button type="button" @click="agregarPorReponer()" :disabled="enviando"
                                class="h-8 px-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-black flex items-center gap-1.5 transition-all cursor-pointer">
                            <i class="bi bi-magic"></i> Agregarlos con cantidad sugerida
                        </button>
                    </div>

                    {{-- Lista --}}
                    <div class="mt-4 space-y-2">
                        <template x-for="(r, i) in renglones" :key="r.id">
                            <div class="sl-renglon rounded-2xl border p-3 sm:p-3.5 transition-all"
                                 :class="{
                                     'border-slate-200 bg-white': r.estadoEnvio === '',
                                     'border-teal-200 bg-teal-50/50': r.estadoEnvio === 'enviando',
                                     'border-emerald-300 bg-emerald-50': r.estadoEnvio === 'ok',
                                     'border-rose-300 bg-rose-50': r.estadoEnvio === 'error',
                                     'sl-nuevo': r._nuevo
                                 }">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    {{-- Medicamento --}}
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm shrink-0"
                                              :class="{ 'bg-rose-50 text-rose-500': r.p.estado === 'agotado', 'bg-amber-50 text-amber-600': r.p.estado === 'bajo', 'bg-teal-50 text-teal-600': r.p.estado === 'ok' }">
                                            <i class="bi" :class="{ 'bi-capsule': r.estadoEnvio === '', 'bi-arrow-repeat sl-girar': r.estadoEnvio === 'enviando', 'bi-check-lg': r.estadoEnvio === 'ok', 'bi-exclamation-lg': r.estadoEnvio === 'error' }"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-black text-slate-800 uppercase truncate" x-text="r.p.nombre"></p>
                                            <p class="text-[10px] font-bold text-slate-400">
                                                <span class="font-mono text-teal-700" x-text="r.p.codigo"></span>
                                                · <span x-text="r.p.fisico"></span>
                                                <i class="bi bi-arrow-right text-teal-500"></i>
                                                <span class="font-black text-teal-700" x-text="r.p.fisico + (Number(r.cantidad) || 0)"></span> piezas
                                            </p>
                                            {{-- Barra: antes y después --}}
                                            <div class="relative mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden max-w-[240px]">
                                                <div class="absolute inset-y-0 left-0 bg-teal-200 rounded-full transition-all duration-500" :style="'width:' + nivelDespues(r) + '%'"></div>
                                                <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500" :style="'width:' + nivelAntes(r) + '%'"
                                                     :class="{ 'bg-rose-500': r.p.estado === 'agotado', 'bg-amber-400': r.p.estado === 'bajo', 'bg-emerald-500': r.p.estado === 'ok' }"></div>
                                            </div>
                                            <p x-show="r.error" class="text-[10px] font-bold text-rose-600 mt-1" x-text="r.error"></p>
                                        </div>
                                    </div>

                                    {{-- Cantidad, lote y quitar --}}
                                    <div class="flex items-center gap-2 md:shrink-0">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="r.cantidad = Math.max(1, (Number(r.cantidad) || 1) - 1)" :disabled="bloqueado(r)" class="sl-paso">−</button>
                                            <input type="number" x-model="r.cantidad" min="1" step="1" :disabled="bloqueado(r)" :id="'sl-cant-' + r.id"
                                                   @keydown.enter.prevent="$refs.buscador.focus()"
                                                   class="w-16 h-9 rounded-xl border-2 text-center text-xs font-black tabular-nums outline-none transition-all"
                                                   :class="Number(r.cantidad) >= 1 ? 'border-slate-200 focus:border-teal-500' : 'border-rose-300 bg-rose-50'">
                                            <button type="button" @click="r.cantidad = (Number(r.cantidad) || 0) + 1" :disabled="bloqueado(r)" class="sl-paso">+</button>
                                        </div>
                                        <input type="text" x-model="r.lote" :disabled="bloqueado(r)" maxlength="60" placeholder="LOTE"
                                               class="w-28 h-9 px-3 rounded-xl border-2 border-slate-200 text-[11px] font-bold uppercase outline-none focus:border-teal-500 transition-all">
                                        <button type="button" @click="quitar(r)" :disabled="enviando" title="Quitar"
                                                class="w-9 h-9 rounded-xl text-slate-400 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center transition-all cursor-pointer disabled:opacity-30">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <p x-show="r.sugerida && r.estadoEnvio === ''" class="text-[10px] font-bold text-amber-600 mt-1.5 flex items-center gap-1">
                                    <i class="bi bi-magic"></i> <span x-text="'Sugerido para llegar a ' + (r.p.minimo * 3) + ' piezas (3 veces el mínimo)'"></span>
                                </p>
                            </div>
                        </template>

                        <div x-show="!renglones.length" class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center">
                            <span class="sl-flotar inline-flex w-14 h-14 rounded-2xl bg-teal-50 text-teal-500 items-center justify-center text-2xl mb-2"><i class="bi bi-upc-scan"></i></span>
                            <p class="text-xs font-black text-slate-500">Escanea o busca el primer medicamento</p>
                            <p class="text-[10px] font-semibold text-slate-400 mt-0.5">Si escaneas el mismo dos veces, se suma a la cantidad.</p>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ===== Pie: totales y registrar ===== --}}
            <div class="border-t border-slate-100 bg-slate-50/60 shrink-0">
                <div x-show="enviando" class="h-1 bg-teal-100">
                    <div class="h-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-300" :style="'width:' + progreso + '%'"></div>
                </div>
                <div class="flex flex-wrap items-center gap-3 px-5 sm:px-6 py-4">
                    <div class="flex items-center gap-4 mr-auto">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Medicamentos</p>
                            <p class="text-sm font-black text-slate-800 tabular-nums" x-text="pendientes.length"></p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase">Piezas</p>
                            <p class="text-sm font-black text-slate-800 tabular-nums" x-text="totalPiezas"></p>
                        </div>
                        <div x-show="costoTotal > 0">
                            <p class="text-[9px] font-black text-slate-400 uppercase">Costo aprox.</p>
                            <p class="text-sm font-black text-teal-700 tabular-nums" x-text="dinero(costoTotal)"></p>
                        </div>
                    </div>
                    <button type="button" @click="cerrar()" :disabled="enviando"
                            class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-2xl text-xs font-extrabold transition-all cursor-pointer disabled:opacity-50">
                        <span x-text="huboExito ? 'Terminar' : 'Cancelar'"></span>
                    </button>
                    <button type="button" @click="registrar()" :disabled="enviando || !pendientes.length"
                            class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-black shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                        <span x-show="enviando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <i x-show="!enviando" class="bi bi-box-arrow-in-down"></i>
                        <span x-text="enviando ? 'Registrando ' + enviados + ' de ' + totalEnvio + '…' : (hayErrores ? 'Reintentar' : 'Registrar entrada')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    .sl-seccion  { animation: slSube .45s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .sl-titulo   { display: flex; align-items: center; gap: .5rem; font-size: 11px; font-weight: 900; color: #0f766e; text-transform: uppercase; letter-spacing: .06em; margin-bottom: .75rem; }
    .sl-titulo span { width: 1.25rem; height: 1.25rem; border-radius: .5rem; background: #0f766e; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; }
    .sl-campo    { width: 100%; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 1rem; padding: .625rem .875rem; font-size: .75rem; font-weight: 700; color: #1e293b; outline: none; transition: all .15s; }
    .sl-campo:focus { background: #fff; border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20, 184, 166, .12); }
    .sl-error    { border-color: #fb7185 !important; background: #fff1f2; }
    .sl-paso     { width: 2.25rem; height: 2.25rem; border-radius: .75rem; background: #f1f5f9; color: #475569; font-weight: 900; transition: all .15s; cursor: pointer; }
    .sl-paso:hover:not(:disabled) { background: #ccfbf1; color: #0f766e; }
    .sl-paso:active { transform: scale(.9); }
    .sl-paso:disabled { opacity: .4; cursor: not-allowed; }
    input[type=number].w-16::-webkit-inner-spin-button, input[type=number].w-16::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .sl-renglon  { animation: slRenglon .35s cubic-bezier(.16, 1, .3, 1) backwards; }
    .sl-nuevo    { animation: slNuevo .9s ease-out; }
    .sl-pop      { animation: slPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .sl-latido   { display: inline-block; animation: slLatido 1.6s ease-in-out infinite; }
    .sl-flotar   { animation: slFlotar 5s ease-in-out infinite; }
    .sl-camion   { animation: slCamion 6s ease-in-out infinite; }
    .sl-girar    { display: inline-block; animation: slGirar .8s linear infinite; }

    @keyframes slSube    { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    @keyframes slRenglon { from { opacity: 0; transform: translateY(-6px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes slNuevo   { 0% { box-shadow: 0 0 0 0 rgba(20, 184, 166, .55); } 100% { box-shadow: 0 0 0 10px rgba(20, 184, 166, 0); } }
    @keyframes slPop     { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes slLatido  { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.25); } 30% { transform: scale(1); } 45% { transform: scale(1.15); } }
    @keyframes slFlotar  { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes slCamion  { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(-16px); } }
    @keyframes slGirar   { to { transform: rotate(360deg); } }

    @media (prefers-reduced-motion: reduce) {
        .sl-seccion, .sl-renglon, .sl-nuevo, .sl-pop, .sl-latido, .sl-flotar, .sl-camion, .sl-girar { animation: none !important; }
    }
</style>

<script>
    function surtirLote() {
        const URL_LOTE = @js(route('inventario.surtir-lote'));
        const CSRF = @js(csrf_token());
        const normalizar = s => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
        let siguienteId = 1;

        return {
            catalogo: @json($productosLote),
            referencia: '',
            busqueda: '',
            abiertoBusqueda: false,
            resaltado: 0,
            renglones: [],
            intento: false,
            enviando: false,
            enviados: 0,
            totalEnvio: 0,
            huboExito: false,

            init() {
                this.$watch('openLoteModal', abierto => {
                    if (abierto) setTimeout(() => (this.referencia ? this.$refs.buscador : this.$refs.referencia).focus(), 320);
                });
            },

            // ----- Búsqueda -----
            get resultados() {
                const q = normalizar(this.busqueda);
                if (!q) return [];
                const exacto = this.catalogo.filter(p => p.codigo.toLowerCase() === q);
                if (exacto.length) return exacto;
                return this.catalogo.filter(p => normalizar(p.codigo + ' ' + p.nombre).includes(q)).slice(0, 12);
            },
            enLista(p) { return this.renglones.some(r => r.p.id === p.id && r.estadoEnvio !== 'ok'); },
            agregarDesdeBusqueda() {
                const lista = this.resultados;
                if (!lista.length) {
                    if (this.busqueda.trim()) this.avisar('No se encontró "' + this.busqueda.trim() + '" en el catálogo', 'warning');
                    return;
                }
                this.agregar(lista[Math.min(this.resaltado, lista.length - 1)]);
            },
            // Agrega el medicamento; si ya está en la lista, suma 1 (útil al escanear varias cajas)
            agregar(p, cantidad = 1, sugerida = false) {
                const existente = this.renglones.find(r => r.p.id === p.id && r.estadoEnvio !== 'ok');
                if (existente) {
                    existente.cantidad = (Number(existente.cantidad) || 0) + cantidad;
                    existente.estadoEnvio = '';
                    existente.error = '';
                    existente._nuevo = true;
                    setTimeout(() => { existente._nuevo = false; }, 900);
                } else {
                    const r = { id: siguienteId++, p, cantidad, lote: '', sugerida, estadoEnvio: '', error: '', _nuevo: true };
                    this.renglones.unshift(r);
                    setTimeout(() => { r._nuevo = false; }, 900);
                }
                this.busqueda = '';
                this.resaltado = 0;
                this.abiertoBusqueda = false;
                this.$nextTick(() => this.$refs.buscador.focus());
            },
            quitar(r) { this.renglones = this.renglones.filter(x => x.id !== r.id); },

            // ----- Reabastecer lo que hace falta -----
            get porReponer() {
                return this.catalogo.filter(p => p.estado !== 'ok' && p.minimo > 0 && !this.enLista(p));
            },
            sugerida(p) { return Math.max(1, p.minimo * 3 - p.disponible); },
            agregarPorReponer() {
                this.porReponer.forEach(p => this.agregar(p, this.sugerida(p), true));
                this.avisar('Se agregaron los medicamentos por reponer. Ajusta las cantidades a lo que llegó.', 'info');
            },

            // ----- Visual -----
            punto(estado) { return { agotado: 'bg-rose-500', bajo: 'bg-amber-400', ok: 'bg-emerald-500' }[estado]; },
            escala(r) { return Math.max(r.p.minimo * 3, r.p.fisico + (Number(r.cantidad) || 0), 1); },
            nivelAntes(r) { return Math.min(100, Math.round(r.p.fisico / this.escala(r) * 100)); },
            nivelDespues(r) { return Math.min(100, Math.round((r.p.fisico + (Number(r.cantidad) || 0)) / this.escala(r) * 100)); },
            bloqueado(r) { return this.enviando || r.estadoEnvio === 'ok'; },
            dinero(n) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

            // ----- Totales -----
            get pendientes() { return this.renglones.filter(r => r.estadoEnvio !== 'ok'); },
            get totalPiezas() { return this.pendientes.reduce((s, r) => s + (Number(r.cantidad) || 0), 0); },
            get costoTotal() { return this.pendientes.reduce((s, r) => s + (Number(r.cantidad) || 0) * r.p.compra, 0); },
            get hayErrores() { return this.renglones.some(r => r.estadoEnvio === 'error'); },
            get progreso() { return this.totalEnvio ? Math.round(this.enviados / this.totalEnvio * 100) : 0; },

            // ----- Registrar (una entrada por medicamento, a la misma ruta de siempre) -----
            async registrar() {
                this.intento = true;
                if (!this.referencia.trim()) { this.avisar('Escribe el proveedor o número de factura.', 'warning'); this.$refs.referencia.focus(); return; }
                const invalido = this.pendientes.find(r => !(Number(r.cantidad) >= 1));
                if (invalido) { this.avisar('La cantidad de ' + invalido.p.nombre + ' debe ser al menos 1.', 'warning'); return; }
                if (!this.pendientes.length || this.enviando) return;

                const cola = [...this.pendientes];
                this.enviando = true;
                this.enviados = 0;
                this.totalEnvio = cola.length;

                for (const r of cola) {
                    r.estadoEnvio = 'enviando';
                    r.error = '';
                    try {
                        const datos = new FormData();
                        datos.append('_token', CSRF);
                        datos.append('referencia', this.referencia.trim().toUpperCase());
                        datos.append('producto_id', r.p.id);
                        datos.append('cantidad', parseInt(r.cantidad, 10));
                        datos.append('lote', String(r.lote || '').trim().toUpperCase());

                        const res = await fetch(URL_LOTE, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
                            body: datos
                        });
                        let data = {};
                        if ((res.headers.get('content-type') || '').includes('application/json')) data = await res.json().catch(() => ({}));

                        if (!res.ok || data.success === false) {
                            let mensaje = data.message || ('Error ' + res.status);
                            if (data.errors) mensaje = data.errors[Object.keys(data.errors)[0]][0];
                            if (res.status === 419) mensaje = 'Tu sesión expiró. Recarga la página.';
                            throw new Error(mensaje);
                        }
                        r.estadoEnvio = 'ok';
                        r.p.fisico += parseInt(r.cantidad, 10);
                        this.huboExito = true;
                    } catch (e) {
                        r.estadoEnvio = 'error';
                        r.error = e.message || 'No se pudo registrar.';
                    }
                    this.enviados++;
                }

                this.enviando = false;
                const ok = cola.filter(r => r.estadoEnvio === 'ok').length;
                const fallas = cola.length - ok;

                if (!fallas) {
                    this.avisar('Entrada registrada: ' + ok + (ok === 1 ? ' medicamento' : ' medicamentos') + ', ' + cola.reduce((s, r) => s + Number(r.cantidad), 0) + ' piezas.', 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.avisar(ok + ' registrados y ' + fallas + ' con error. Revisa los marcados en rojo y vuelve a intentar.', 'warning');
                }
            },

            // ----- Cerrar -----
            avisar(mensaje, icono = 'success') {
                if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
                if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
            },
            swalAbierto() { return !!(window.Swal && Swal.isVisible && Swal.isVisible()); },
            limpiar() {
                this.renglones = [];
                this.referencia = '';
                this.busqueda = '';
                this.intento = false;
            },
            cerrar() {
                if (this.enviando) return;
                // Si ya se registró algo, se recarga para ver las existencias nuevas
                if (this.huboExito) { this.openLoteModal = false; window.location.reload(); return; }
                if (!this.renglones.length || !window.Swal) { this.openLoteModal = false; this.limpiar(); return; }
                Swal.fire({
                    title: '¿Descartar la entrada?',
                    text: 'Se perderán los ' + this.renglones.length + ' medicamentos que agregaste.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Descartar', cancelButtonText: 'Seguir capturando',
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#0d9488',
                    customClass: { container: 'z-[10050]', popup: 'rounded-3xl' }
                }).then(r => { if (r.isConfirmed) { this.openLoteModal = false; this.limpiar(); } });
            }
        };
    }
</script>