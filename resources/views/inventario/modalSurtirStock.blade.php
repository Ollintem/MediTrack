{{-- MODAL: SURTIR EXISTENCIAS DE UN MEDICAMENTO --}}
{{-- Usa del index: openSurtirModal, productoSeleccionado y openLoteModal. Envía a /inventario/{id}/agregar-stock --}}
<template x-teleport="body">
    <div x-show="openSurtirModal" x-cloak
         x-data="surtirStock()"
         @keydown.escape.window="openSurtirModal && !enviando && !swalAbierto() && cerrar()"
         class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-lg max-h-[94vh] flex flex-col overflow-hidden"
             @click.outside="!enviando && !swalAbierto() && cerrar()"
             x-show="openSurtirModal"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">

            {{-- ===== Encabezado ===== --}}
            <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-700 text-white shrink-0 overflow-hidden">
                <div class="absolute -right-8 -top-10 w-36 h-36 rounded-full bg-white/5"></div>
                <i class="bi bi-box-seam absolute right-14 -bottom-6 text-7xl text-white/5 pointer-events-none ss-flotar"></i>
                <div class="relative flex items-center gap-4">
                    <span class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-xl text-emerald-300 shrink-0 ss-pop">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300 flex items-center gap-1.5">
                            <i class="bi bi-heart-pulse-fill ss-latido"></i> Surtir existencias
                        </p>
                        <h3 class="text-base font-black tracking-tight uppercase truncate" x-text="productoSeleccionado.nombre || 'Medicamento'"></h3>
                        <p class="text-[10px] font-mono font-bold text-teal-100/70" x-text="productoSeleccionado.codigo || ''"></p>
                    </div>
                    <button type="button" @click="cerrar()" :disabled="enviando" title="Cerrar (Esc)"
                            class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <form :action="urlBase + '/' + productoSeleccionado.id + '/agregar-stock'" method="POST" @submit="enviar($event)"
                  class="flex flex-col flex-1 min-h-0" novalidate>
                @csrf

                <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">

                    {{-- ===== Antes → después ===== --}}
                    <div class="ss-seccion rounded-2xl border border-slate-200 bg-slate-50/60 p-4" style="--d:40ms">
                        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 text-center">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase">Hoy</p>
                                <p class="text-2xl font-black tabular-nums" :class="colorTexto(estadoAntes)" x-text="disponible"></p>
                                <span class="ss-chip" :class="colorChip(estadoAntes)" x-text="etiqueta(estadoAntes)"></span>
                            </div>
                            <div class="flex flex-col items-center">
                                <span class="text-[11px] font-black text-teal-700 tabular-nums" x-text="cantidadValida ? '+' + cantidadNum : ''"></span>
                                <i class="bi bi-arrow-right text-xl text-teal-500 ss-flecha"></i>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase">Quedará en</p>
                                <p class="text-2xl font-black tabular-nums transition-colors" :class="colorTexto(estadoDespues)" x-text="despues"></p>
                                <span class="ss-chip" :class="colorChip(estadoDespues)" x-text="etiqueta(estadoDespues)"></span>
                            </div>
                        </div>

                        {{-- Barra con el antes, lo que se suma y la marca del mínimo --}}
                        <div class="relative mt-4 h-3 rounded-full bg-white border border-slate-200 overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-teal-300/70 rounded-full transition-all duration-500" :style="'width:' + nivel(despues) + '%'"></div>
                            <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500" :class="colorBarra(estadoAntes)" :style="'width:' + nivel(disponible) + '%'"></div>
                            <span x-show="minimo > 0" class="absolute inset-y-0 w-0.5 bg-slate-600/70" :style="'left:' + nivel(minimo) + '%'"></span>
                        </div>
                        <div class="mt-1.5 text-[10px] font-bold text-slate-400">
                            <span x-text="'Mínimo: ' + minimo"></span>
                        </div>

                        <p x-show="cantidadValida && estadoAntes !== 'ok' && estadoDespues === 'ok'" x-transition.opacity
                           class="mt-3 text-[11px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 flex items-center gap-1.5">
                            <i class="bi bi-check-circle-fill"></i> Con esta entrada deja de estar en alerta.
                        </p>
                        <p x-show="cantidadValida && estadoDespues !== 'ok'" x-transition.opacity
                           class="mt-3 text-[11px] font-black text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 flex items-center gap-1.5">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span x-text="'Seguirá en stock bajo. Faltarían ' + faltanParaMinimo + ' piezas para superar el mínimo.'"></span>
                        </p>
                    </div>

                    {{-- ===== Cantidad ===== --}}
                    <div class="ss-seccion" style="--d:110ms">
                        <label for="ss-cantidad" class="ss-label">Cantidad a sumar *</label>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="sumar(-1)" class="ss-paso">−</button>
                            <input id="ss-cantidad" type="number" name="cantidad" x-model="cantidad" x-ref="cantidad" min="1" step="1" required
                                   @keydown.enter.prevent="$refs.motivo.focus()"
                                   class="ss-campo text-center text-lg font-black tabular-nums" :class="intento && !cantidadValida ? 'ss-error' : ''"
                                   placeholder="0">
                            <button type="button" @click="sumar(1)" class="ss-paso">+</button>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <template x-for="n in [5, 10, 20, 50, 100]" :key="n">
                                <button type="button" @click="sumar(n)" class="ss-atajo" x-text="'+' + n"></button>
                            </template>
                            <button type="button" x-show="sugerida > 0" @click="cantidad = sugerida" class="ss-atajo !border-amber-300 !text-amber-700 hover:!bg-amber-50"
                                    :title="'Para llegar a ' + (minimo * 3) + ' piezas (3 veces el mínimo)'">
                                <i class="bi bi-magic"></i> Sugerido: <span x-text="sugerida"></span>
                            </button>
                            <button type="button" x-show="cantidad !== ''" @click="cantidad = ''" class="ss-atajo !text-slate-400">Limpiar</button>
                        </div>
                        <p class="text-[10px] font-bold mt-1.5"
                           :class="intento && !cantidadValida ? 'text-rose-600' : (muchas ? 'text-amber-600' : 'text-slate-400')"
                           x-text="intento && !cantidadValida ? 'Escribe cuántas piezas entran (mínimo 1).' : (muchas ? 'Es una cantidad grande: revisa que sea correcta.' : (costo > 0 ? 'Costo aproximado de la entrada: ' + dinero(costo) : 'Piezas que recibiste físicamente.'))"></p>
                        @error('cantidad')<p class="text-[10px] font-bold text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- ===== Motivo ===== --}}
                    <div class="ss-seccion" style="--d:180ms">
                        <label for="ss-motivo" class="ss-label">Motivo / nota de recepción <span class="normal-case font-semibold text-slate-400">(opcional)</span></label>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            <template x-for="m in motivos" :key="m.texto">
                                <button type="button" @click="elegirMotivo(m.texto)"
                                        class="px-2.5 py-1 rounded-full border text-[10px] font-bold transition-all cursor-pointer flex items-center gap-1"
                                        :class="motivo.toUpperCase().startsWith(m.texto.toUpperCase()) ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'">
                                    <i class="bi" :class="m.icono"></i><span x-text="m.texto"></span>
                                </button>
                            </template>
                        </div>
                        <textarea id="ss-motivo" name="motivo" x-model="motivo" x-ref="motivo" rows="2" maxlength="255"
                                  class="ss-campo resize-none uppercase" placeholder="EJ. FACTURA F-4892, PROVEEDOR SURTIFARMA"></textarea>
                    </div>

                    {{-- Atajo al lote --}}
                    <button type="button" x-show="typeof openLoteModal !== 'undefined'" @click="irALote()"
                            class="w-full text-left rounded-2xl border border-dashed border-slate-300 hover:border-teal-400 hover:bg-teal-50/40 p-3 flex items-center gap-3 transition-all cursor-pointer group">
                        <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 group-hover:bg-teal-100 group-hover:text-teal-700 flex items-center justify-center transition-colors"><i class="bi bi-boxes"></i></span>
                        <span class="min-w-0">
                            <span class="block text-xs font-black text-slate-700">¿Llegaron varios medicamentos en la misma factura?</span>
                            <span class="block text-[10px] font-semibold text-slate-400">Usa "Surtir lote" para registrarlos todos juntos.</span>
                        </span>
                        <i class="bi bi-arrow-right ml-auto text-slate-400 group-hover:text-teal-600 group-hover:translate-x-1 transition-all"></i>
                    </button>
                </div>

                <div class="flex items-center gap-2.5 px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                    <span class="ml-auto"></span>
                    <button type="button" @click="cerrar()" :disabled="enviando"
                            class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-2xl text-xs font-extrabold transition-all cursor-pointer disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="enviando"
                            class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-black shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                        <span x-show="enviando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <i x-show="!enviando" class="bi bi-plus-lg"></i>
                        <span x-text="enviando ? 'Guardando…' : (cantidadValida ? 'Sumar ' + cantidadNum + (cantidadNum === 1 ? ' pieza' : ' piezas') : 'Sumar al stock')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style>
    .ss-seccion { animation: ssSube .45s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .ss-label   { display: block; font-size: 10px; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .375rem; }
    .ss-campo   { width: 100%; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 1rem; padding: .625rem .875rem; font-size: .75rem; font-weight: 700; color: #1e293b; outline: none; transition: all .15s; }
    .ss-campo:focus { background: #fff; border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20, 184, 166, .12); }
    .ss-campo[type=number]::-webkit-inner-spin-button, .ss-campo[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .ss-campo[type=number] { -moz-appearance: textfield; }
    .ss-error   { border-color: #fb7185 !important; background: #fff1f2; }
    .ss-paso    { width: 3rem; height: 3rem; flex: none; border-radius: 1rem; background: #f1f5f9; color: #475569; font-weight: 900; font-size: 1.1rem; transition: all .15s; cursor: pointer; }
    .ss-paso:hover { background: #ccfbf1; color: #0f766e; }
    .ss-paso:active { transform: scale(.9); }
    .ss-atajo   { padding: .3rem .7rem; border-radius: 9999px; border: 1px solid #e2e8f0; background: #fff; font-size: 10px; font-weight: 900; color: #475569; transition: all .15s; cursor: pointer; display: inline-flex; align-items: center; gap: .25rem; }
    .ss-atajo:hover { border-color: #5eead4; color: #0f766e; background: #f0fdfa; }
    .ss-atajo:active { transform: scale(.94); }
    .ss-chip    { display: inline-block; margin-top: .15rem; font-size: 9px; font-weight: 900; text-transform: uppercase; padding: .1rem .5rem; border-radius: 9999px; transition: all .3s; }
    .ss-pop     { animation: ssPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .ss-latido  { display: inline-block; animation: ssLatido 1.6s ease-in-out infinite; }
    .ss-flotar  { animation: ssFlotar 5s ease-in-out infinite; }
    .ss-flecha  { display: inline-block; animation: ssFlecha 1.4s ease-in-out infinite; }

    @keyframes ssSube   { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    @keyframes ssPop    { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes ssLatido { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.25); } 30% { transform: scale(1); } 45% { transform: scale(1.15); } }
    @keyframes ssFlotar { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes ssFlecha { 0%, 100% { transform: translateX(-3px); } 50% { transform: translateX(3px); } }

    @media (prefers-reduced-motion: reduce) {
        .ss-seccion, .ss-pop, .ss-latido, .ss-flotar, .ss-flecha { animation: none !important; }
    }
</style>

<script>
    function surtirStock() {
        return {
            urlBase: @js(url('inventario')),
            cantidad: '',
            motivo: '',
            intento: false,
            enviando: false,
            motivos: [
                { texto: 'Compra a proveedor',   icono: 'bi-truck' },
                { texto: 'Devolución',           icono: 'bi-arrow-counterclockwise' },
                { texto: 'Ajuste de inventario', icono: 'bi-sliders' },
                { texto: 'Donación',             icono: 'bi-gift' }
            ],

            init() {
                // Cada vez que se abre para un medicamento, el formulario empieza limpio
                this.$watch('openSurtirModal', abierto => {
                    if (!abierto) return;
                    this.cantidad = '';
                    this.motivo = '';
                    this.intento = false;
                    this.enviando = false;
                    setTimeout(() => this.$refs.cantidad && this.$refs.cantidad.focus(), 320);
                });
            },

            // ----- Datos del medicamento -----
            // Solo se trabaja con el stock disponible (el mismo que se muestra en el inventario)
            get disponible() { return Number(this.productoSeleccionado.stock_disponible) || 0; },
            get minimo() { return Number(this.productoSeleccionado.stock_minimo || 0); },
            get cantidadNum() { return parseInt(this.cantidad, 10) || 0; },
            get cantidadValida() { return this.cantidadNum >= 1 && Number(this.cantidad) === this.cantidadNum; },
            get despues() { return this.disponible + (this.cantidadValida ? this.cantidadNum : 0); },
            
            // El estado se calcula con lo disponible
            estadoDe(disponible) { return disponible <= 0 ? 'agotado' : (disponible <= this.minimo ? 'bajo' : 'ok'); },
            get estadoAntes() { return this.estadoDe(this.disponible); },
            get estadoDespues() { return this.estadoDe(this.despues); },
            get faltanParaMinimo() { return Math.max(0, this.minimo + 1 - (this.disponible + (this.cantidadValida ? this.cantidadNum : 0))); },
            get sugerida() { return this.minimo > 0 ? Math.max(0, this.minimo * 3 - this.disponible) : 0; },
            get muchas() { return this.cantidadNum >= 500 || (this.disponible > 0 && this.cantidadNum > this.disponible * 20 && this.cantidadNum > 50); },
            get costo() { return this.cantidadValida ? this.cantidadNum * Number(this.productoSeleccionado.precio_compra || 0) : 0; },

            // ----- Visual -----
            escala() { return Math.max(this.minimo * 3, this.despues, 1); },
            nivel(n) { return Math.min(100, Math.round(n / this.escala() * 100)); },
            etiqueta(e) { return { agotado: 'Agotado', bajo: 'Stock bajo', ok: 'Suficiente' }[e]; },
            colorTexto(e) { return { agotado: 'text-rose-600', bajo: 'text-amber-600', ok: 'text-emerald-600' }[e]; },
            colorChip(e) { return { agotado: 'bg-rose-100 text-rose-700', bajo: 'bg-amber-100 text-amber-700', ok: 'bg-emerald-100 text-emerald-700' }[e]; },
            colorBarra(e) { return { agotado: 'bg-rose-500', bajo: 'bg-amber-400', ok: 'bg-emerald-500' }[e]; },
            dinero(n) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

            // ----- Acciones -----
            sumar(n) {
                const nuevo = Math.max(1, (parseInt(this.cantidad, 10) || 0) + n);
                this.cantidad = String(nuevo);
            },
            elegirMotivo(texto) {
                const actual = this.motivo.trim();
                const yaTiene = this.motivos.find(m => actual.toUpperCase().startsWith(m.texto.toUpperCase()));
                const resto = yaTiene ? actual.slice(yaTiene.texto.length).replace(/^[\s,.-]+/, '') : actual;
                this.motivo = (texto + (resto ? ', ' + resto : '')).toUpperCase();
                this.$nextTick(() => { this.$refs.motivo.focus(); this.$refs.motivo.setSelectionRange(this.motivo.length, this.motivo.length); });
            },
            irALote() {
                this.openSurtirModal = false;
                setTimeout(() => { this.openLoteModal = true; }, 200);
            },
            enviar(e) {
                this.intento = true;
                if (this.enviando) { e.preventDefault(); return; }
                if (!this.cantidadValida) {
                    e.preventDefault();
                    this.$refs.cantidad.focus();
                    return;
                }
                this.enviando = true;
            },
            swalAbierto() { return !!(window.Swal && Swal.isVisible && Swal.isVisible()); },
            cerrar() {
                if (this.enviando) return;
                this.openSurtirModal = false;
            }
        };
    }
</script>