{{-- MODAL: GESTIONAR CATEGORÍAS DE INVENTARIO --}}
{{-- Usa del index: openCategoriaModal, openCreateModal, openEditModal, productos (para contar medicamentos por categoría) y categoriaFiltro --}}
{{-- Al crear, renombrar o eliminar avisa con el evento "categorias-inventario" para que el index y los modales se actualicen sin recargar --}}
{{-- Si se abre desde Registrar o Editar medicamento (window.__categoriaDesde), al agregar una categoría la selecciona allá y regresa --}}
@php
    $categoriasGestion = collect($categorias ?? [])->map(fn ($c) => ['id' => (string) $c->id, 'nombre' => $c->nombre])->values();
    $puedeRenombrar = \Illuminate\Support\Facades\Route::has('categorias-inventario.update');
@endphp

<template x-teleport="body">
    <div x-show="openCategoriaModal" x-cloak
         x-data="gestorCategorias()"
         @keydown.escape.window="openCategoriaModal && !ocupado && !editandoId && cerrar()"
         class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-lg max-h-[92vh] flex flex-col overflow-hidden"
             @click.outside="!ocupado && !swalAbierto() && cerrar()"
             x-show="openCategoriaModal"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">

            {{-- ===== Encabezado ===== --}}
            <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-700 text-white shrink-0 overflow-hidden">
                <div class="absolute -right-8 -top-10 w-36 h-36 rounded-full bg-white/5"></div>
                <i class="bi bi-tags absolute right-14 -bottom-6 text-7xl text-white/5 -rotate-12 pointer-events-none gc-flotar"></i>
                <div class="relative flex items-center gap-4">
                    <span class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-xl text-emerald-300 shrink-0 gc-pop">
                        <i class="bi bi-tags-fill"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300 flex items-center gap-1.5">
                            <i class="bi bi-heart-pulse-fill gc-latido"></i>
                            <span x-text="origen === 'crear' ? 'Registrando medicamento' : (origen === 'editar' ? 'Editando medicamento' : 'Almacén de farmacia')"></span>
                        </p>
                        <h3 class="text-base font-black tracking-tight">Categorías</h3>
                        <p class="text-[11px] font-bold text-teal-100/80"
                           x-text="lista.length + (lista.length === 1 ? ' categoría' : ' categorías') + ' · ' + sinUso + ' sin medicamentos'"></p>
                    </div>
                    <button type="button" @click="cerrar()" :title="origen ? 'Volver al formulario (Esc)' : 'Cerrar (Esc)'"
                            class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">

                {{-- ===== Nueva categoría ===== --}}
                <form @submit.prevent="crear()" class="gc-seccion rounded-2xl border border-teal-100 bg-gradient-to-br from-teal-50/70 to-white p-4" style="--d:60ms" novalidate>
                    <label for="gc-nueva" class="block text-[10px] font-black text-teal-800 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="bi bi-plus-circle-fill"></i> Nueva categoría
                    </label>
                    <div class="flex gap-2">
                        <input id="gc-nueva" type="text" x-ref="nueva" x-model="nuevo" maxlength="80" autocomplete="off"
                               @input="error = ''"
                               class="flex-1 min-w-0 px-3.5 py-2.5 rounded-2xl border-2 bg-white text-xs font-bold uppercase outline-none transition-all"
                               :class="error || repetida ? 'border-rose-300 bg-rose-50 focus:border-rose-400' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'"
                               placeholder="EJ. ANALGÉSICOS">
                        <button type="submit" :disabled="ocupado || !nuevo.trim() || !!repetida"
                                class="px-4 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-black shadow-md shadow-teal-600/20 flex items-center gap-1.5 shrink-0 transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="creando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                            <i x-show="!creando" class="bi bi-plus-lg"></i>
                            <span>Agregar</span>
                        </button>
                    </div>
                    <p class="text-[10px] font-bold mt-1.5 min-h-[14px]"
                       :class="error || repetida ? 'text-rose-600' : 'text-slate-400'"
                       x-text="error || (repetida
                            ? 'Ya existe la categoría «' + repetida.nombre + '»' + (origen ? '. Elígela abajo.' : '')
                            : (origen ? 'Al agregarla quedará seleccionada en el medicamento.' : 'Escribe el nombre y presiona Enter.'))"></p>

                    {{-- Sugerencias rápidas (solo las que aún no existen) --}}
                    <div x-show="sugerenciasDisponibles.length" class="flex flex-wrap gap-1.5 mt-1">
                        <template x-for="s in sugerenciasDisponibles" :key="s">
                            <button type="button" @click="nuevo = s; $refs.nueva.focus()"
                                    class="px-2.5 py-1 rounded-full bg-white border border-slate-200 text-[10px] font-bold text-slate-500 hover:border-teal-300 hover:text-teal-700 transition-all cursor-pointer"
                                    x-text="'+ ' + s"></button>
                        </template>
                    </div>
                </form>

                {{-- ===== Lista ===== --}}
                <div class="gc-seccion" style="--d:140ms">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Registradas</p>
                        <div class="w-40" x-show="lista.length > 5">
                            <input type="text" x-model="buscar" placeholder="Buscar…" style="text-transform:none"
                                   class="w-full px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] font-semibold outline-none focus:border-teal-400">
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-100 overflow-hidden divide-y divide-slate-100">
                        <template x-for="(c, i) in filtradas" :key="c.id">
                            <div class="gc-fila group flex items-center gap-3 px-3.5 py-2.5 transition-colors"
                                 :class="[c._nueva ? 'gc-nueva bg-emerald-50' : 'hover:bg-slate-50', c._saliendo && 'gc-sale']"
                                 :style="'--d:' + Math.min(i * 35, 350) + 'ms'">
                                <span class="w-8 h-8 rounded-xl flex items-center justify-center text-xs shrink-0" :class="color(c.nombre)">
                                    <i class="bi bi-tag-fill"></i>
                                </span>

                                {{-- Nombre (o campo para renombrar) --}}
                                <div class="flex-1 min-w-0">
                                    <template x-if="editandoId !== c.id">
                                        <div>
                                            <button type="button" @click="clicNombre(c)" :disabled="!origen && !usos(c)"
                                                    class="text-xs font-black text-slate-800 uppercase truncate max-w-full text-left enabled:hover:text-teal-700 enabled:cursor-pointer"
                                                    :title="origen ? 'Usar esta categoría' : (usos(c) ? 'Ver sus medicamentos en el inventario' : '')" x-text="c.nombre"></button>
                                            <p class="text-[10px] font-bold" :class="usos(c) ? 'text-slate-400' : 'text-slate-300 italic'"
                                               x-text="usos(c) ? usos(c) + (usos(c) === 1 ? ' medicamento' : ' medicamentos') : 'Sin medicamentos'"></p>
                                        </div>
                                    </template>
                                    <template x-if="editandoId === c.id">
                                        <input type="text" x-model="nombreEditado" x-init="$nextTick(() => { $el.focus(); $el.select(); })" maxlength="80"
                                               @keydown.enter.prevent="guardarNombre(c)" @keydown.escape.stop.prevent="editandoId = null"
                                               class="w-full px-3 py-1.5 rounded-xl border-2 border-teal-400 bg-white text-xs font-black uppercase outline-none focus:ring-4 focus:ring-teal-500/10">
                                    </template>
                                </div>

                                {{-- Acciones --}}
                                <div class="flex items-center gap-1 shrink-0">
                                    <template x-if="editandoId === c.id">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="guardarNombre(c)" :disabled="ocupado" title="Guardar (Enter)"
                                                    class="w-7 h-7 rounded-lg bg-teal-600 text-white hover:bg-teal-700 flex items-center justify-center text-xs transition-all cursor-pointer">
                                                <span x-show="ocupado" class="w-3 h-3 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                                <i x-show="!ocupado" class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" @click="editandoId = null" title="Cancelar (Esc)"
                                                    class="w-7 h-7 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center text-xs transition-all cursor-pointer">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editandoId !== c.id">
                                        <div class="flex items-center gap-1">
                                            {{-- Desde un formulario: elegir la categoría y volver --}}
                                            <button type="button" x-show="origen" @click="usarCategoria(c)" title="Usar esta categoría"
                                                    class="h-7 px-2.5 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white text-[10px] font-black flex items-center gap-1 transition-all cursor-pointer">
                                                <i class="bi bi-check2"></i> Usar
                                            </button>
                                            <div class="flex items-center gap-1 sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100 transition-opacity">
                                                @if ($puedeRenombrar)
                                                    <button type="button" @click="editar(c)" title="Renombrar"
                                                            class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-500 hover:text-white flex items-center justify-center text-xs transition-all cursor-pointer">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                @endif
                                                <button type="button" @click="eliminar(c)" title="Eliminar"
                                                        class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white flex items-center justify-center text-xs transition-all cursor-pointer">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div x-show="!filtradas.length" class="p-6 text-center">
                            <span class="gc-flotar inline-flex w-12 h-12 rounded-2xl bg-teal-50 text-teal-500 items-center justify-center text-xl mb-2">
                                <i class="bi" :class="lista.length ? 'bi-search' : 'bi-tags'"></i>
                            </span>
                            <p class="text-xs font-black text-slate-500" x-text="lista.length ? 'Ninguna coincide' : 'Aún no hay categorías'"></p>
                            <p x-show="!lista.length" class="text-[10px] font-semibold text-slate-400 mt-0.5">Crea la primera arriba o elige una sugerencia.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                <p class="text-[10px] font-semibold text-slate-400 mr-auto hidden sm:block"
                   x-text="origen ? 'Clic en un nombre para usarla en el medicamento.' : 'Clic en un nombre para ver sus medicamentos.'"></p>
                <button type="button" x-show="origen" @click="regresar()"
                        class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-black shadow-md shadow-teal-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="bi bi-arrow-left"></i>
                    <span x-text="origen === 'editar' ? 'Volver a la edición' : 'Volver al registro'"></span>
                </button>
                <button type="button" x-show="!origen" @click="cerrar()"
                        class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-2xl text-xs font-extrabold transition-all cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>

<style>
    .gc-seccion { animation: gcSube .45s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .gc-fila    { animation: gcFila .35s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .gc-nueva   { animation: gcNueva 1.6s ease-out; }
    .gc-sale    { animation: gcSale .3s ease-in forwards !important; }
    .gc-pop     { animation: gcPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .gc-latido  { display: inline-block; animation: gcLatido 1.6s ease-in-out infinite; }
    .gc-flotar  { animation: gcFlotar 5s ease-in-out infinite; }

    @keyframes gcSube   { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    @keyframes gcFila   { from { opacity: 0; transform: translateX(-8px); } to { opacity: 1; transform: none; } }
    @keyframes gcNueva  { 0% { background: #a7f3d0; transform: scale(.97); } 30% { transform: scale(1); } 100% { background: #ecfdf5; } }
    @keyframes gcSale   { to { opacity: 0; transform: translateX(30px); height: 0; padding-top: 0; padding-bottom: 0; } }
    @keyframes gcPop    { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes gcLatido { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.25); } 30% { transform: scale(1); } 45% { transform: scale(1.15); } }
    @keyframes gcFlotar { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }

    @media (prefers-reduced-motion: reduce) {
        .gc-seccion, .gc-fila, .gc-nueva, .gc-pop, .gc-latido, .gc-flotar { animation: none !important; }
    }
</style>

<script>
    function gestorCategorias() {
        const URL_CATEGORIAS = @js(url('categorias-inventario'));
        const URL_CREAR      = @js(route('categorias-inventario.store'));
        const CSRF           = @js(csrf_token());
        const normalizar = s => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

        return {
            lista: @json($categoriasGestion),
            nuevo: '',
            buscar: '',
            error: '',
            creando: false,
            ocupado: false,
            editandoId: null,
            nombreEditado: '',
            origen: null,   // 'crear' | 'editar' | null (abierto desde el index)
            sugerencias: ['Analgésicos', 'Antibióticos', 'Antiinflamatorios', 'Antihistamínicos', 'Antihipertensivos', 'Material de curación', 'Vitaminas'],

            init() {
                this.$watch('openCategoriaModal', abierto => {
                    if (!abierto) return;
                    // ¿Se abrió desde Registrar o Editar medicamento?
                    const desde = window.__categoriaDesde || null;
                    window.__categoriaDesde = null;
                    this.origen = desde ? desde.origen : null;
                    if (desde && desde.nombre) this.nuevo = desde.nombre.toUpperCase();
                    this.error = '';
                    this.buscar = '';
                    this.editandoId = null;
                    setTimeout(() => this.$refs.nueva && this.$refs.nueva.focus(), 320);
                });
            },

            // ----- Datos -----
            get filtradas() {
                const q = normalizar(this.buscar);
                return this.lista.filter(c => !q || normalizar(c.nombre).includes(q))
                    .sort((a, b) => (b._nueva ? 1 : 0) - (a._nueva ? 1 : 0) || a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' }));
            },
            get repetida() {
                const n = normalizar(this.nuevo);
                return n ? this.lista.find(c => normalizar(c.nombre) === n) : null;
            },
            get sugerenciasDisponibles() {
                const existentes = new Set(this.lista.map(c => normalizar(c.nombre)));
                return this.sugerencias.filter(s => !existentes.has(normalizar(s))).slice(0, this.lista.length ? 3 : 7);
            },
            usos(c) {
                const productos = Array.isArray(this.productos) ? this.productos : [];
                return productos.filter(p => normalizar(p.categoria) === normalizar(c.nombre)).length;
            },
            get sinUso() { return this.lista.filter(c => !this.usos(c)).length; },
            color(n) {
                const colores = ['bg-teal-50 text-teal-700', 'bg-sky-50 text-sky-700', 'bg-violet-50 text-violet-700', 'bg-amber-50 text-amber-700', 'bg-rose-50 text-rose-700', 'bg-emerald-50 text-emerald-700', 'bg-indigo-50 text-indigo-700'];
                let h = 0;
                for (const ch of String(n || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
                return colores[h % colores.length];
            },

            // Avisa al index y a los modales de registrar / editar que la lista cambió
            avisarCambio(extra = {}) {
                window.dispatchEvent(new CustomEvent('categorias-inventario', {
                    detail: { lista: this.lista.map(c => ({ id: String(c.id), nombre: c.nombre })), ...extra }
                }));
            },
            async peticion(url, method, body) {
                const r = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: body ? JSON.stringify(body) : undefined
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || data.success === false) {
                    let mensaje = data.message || 'Ocurrió un error.';
                    if (data.errors) mensaje = data.errors[Object.keys(data.errors)[0]][0];
                    if (r.status === 419) mensaje = 'Tu sesión expiró. Recarga la página.';
                    throw new Error(mensaje);
                }
                return data;
            },
            avisar(mensaje, icono = 'success') {
                if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
                if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2500, showConfirmButton: false, icon: icono, title: mensaje });
            },
            swalAbierto() { return !!(window.Swal && Swal.isVisible && Swal.isVisible()); },

            // ----- Crear -----
            async crear() {
                const nombre = this.nuevo.trim().replace(/\s+/g, ' ');
                if (!nombre || this.ocupado) return;
                if (this.repetida) { this.error = 'Ya existe la categoría «' + this.repetida.nombre + '»'; return; }

                this.ocupado = this.creando = true;
                let volver = false;
                try {
                    const data = await this.peticion(URL_CREAR, 'POST', { nombre: nombre.toUpperCase() });
                    const cat = data.categoria || data;
                    const nueva = { id: String(cat.id), nombre: cat.nombre || nombre.toUpperCase(), _nueva: true };
                    this.lista.push(nueva);
                    this.nuevo = '';
                    // "para" le indica al formulario de origen que la seleccione
                    this.avisarCambio({ creada: { id: nueva.id, nombre: nueva.nombre }, para: this.origen });
                    this.avisar('Categoría «' + nueva.nombre + '» agregada');
                    setTimeout(() => { nueva._nueva = false; }, 1800);
                    volver = !!this.origen;
                } catch (e) {
                    this.error = e.message || 'No se pudo guardar la categoría.';
                } finally {
                    this.ocupado = this.creando = false;
                }
                if (volver) this.regresar();
                else this.$refs.nueva && this.$refs.nueva.focus();
            },

            // ----- Usar una existente (solo desde un formulario) -----
            usarCategoria(c) {
                if (!this.origen) return;
                window.dispatchEvent(new CustomEvent('categorias-inventario', {
                    detail: { lista: this.lista.map(x => ({ id: String(x.id), nombre: x.nombre })), creada: { id: String(c.id), nombre: c.nombre }, para: this.origen }
                }));
                this.regresar();
            },
            clicNombre(c) {
                if (this.origen) return this.usarCategoria(c);
                this.verProductos(c);
            },

            // ----- Renombrar -----
            editar(c) {
                this.editandoId = c.id;
                this.nombreEditado = c.nombre;
            },
            async guardarNombre(c) {
                const nombre = this.nombreEditado.trim().replace(/\s+/g, ' ').toUpperCase();
                if (!nombre || this.ocupado) return;
                if (normalizar(nombre) === normalizar(c.nombre) && nombre === c.nombre) { this.editandoId = null; return; }
                const otra = this.lista.find(x => x.id !== c.id && normalizar(x.nombre) === normalizar(nombre));
                if (otra) { this.avisar('Ya existe la categoría «' + otra.nombre + '»', 'warning'); return; }

                this.ocupado = true;
                try {
                    await this.peticion(`${URL_CATEGORIAS}/${c.id}`, 'PUT', { nombre });
                    const antes = c.nombre;
                    c.nombre = nombre;
                    this.editandoId = null;
                    this.avisarCambio({ renombrada: { id: c.id, antes, despues: nombre } });
                    this.avisar('Categoría renombrada');
                } catch (e) {
                    this.avisar(e.message || 'No se pudo renombrar.', 'error');
                } finally {
                    this.ocupado = false;
                }
            },

            // ----- Eliminar -----
            eliminar(c) {
                const n = this.usos(c);
                const aviso = n
                    ? `<br><span style="font-size:12px;color:#b45309"><b>${n} ${n === 1 ? 'medicamento usa' : 'medicamentos usan'}</b> esta categoría y ${n === 1 ? 'quedará' : 'quedarán'} sin categoría.</span>`
                    : '<br><span style="font-size:12px;color:#64748b">Ningún medicamento la usa.</span>';
                const escapar = t => { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; };

                Swal.fire({
                    title: '¿Eliminar categoría?',
                    html: `Se eliminará <b>${escapar(c.nombre)}</b>.${aviso}`,
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonColor: '#f43f5e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    showLoaderOnConfirm: true,
                    customClass: { container: 'z-[10050]', popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try { return await this.peticion(`${URL_CATEGORIAS}/${c.id}`, 'DELETE'); }
                        catch (e) { Swal.showValidationMessage(e.message || 'No se pudo eliminar la categoría.'); }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(r => {
                    if (!r.isConfirmed) return;
                    c._saliendo = true;
                    setTimeout(() => {
                        this.lista = this.lista.filter(x => x.id !== c.id);
                        this.avisarCambio({ eliminada: { id: c.id, nombre: c.nombre } });
                    }, 300);
                    this.avisar('Categoría eliminada');
                });
            },

            // ----- Navegación -----
            verProductos(c) {
                if (!this.usos(c) || this.origen) return;
                this.categoriaFiltro = c.nombre;   // filtro del index
                this.openCategoriaModal = false;
            },
            // Regresa al formulario desde donde se abrió (registrar o editar)
            regresar() {
                const origen = this.origen;
                this.origen = null;
                this.editandoId = null;
                this.openCategoriaModal = false;
                setTimeout(() => {
                    if (origen === 'crear') this.openCreateModal = true;
                    if (origen === 'editar') this.openEditModal = true;
                }, 200);
            },
            // Si se abrió desde un formulario, cerrar siempre regresa a él para no perder lo capturado
            cerrar() {
                if (this.ocupado) return;
                if (this.origen) return this.regresar();
                this.editandoId = null;
                this.openCategoriaModal = false;
            }
        };
    }
</script>