{{-- MODAL: EDITAR MEDICAMENTO O INSUMO (mismo diseño que registrar: escaparate con la caja y formulario por pasos) --}}
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
         @keydown.escape.window="openEditModal && !enviando && !swalAbierto() && cerrar()"
         @keydown.window="openEditModal && (($event.ctrlKey || $event.metaKey) && $event.key === 'Enter') && ($event.preventDefault(), cambios.length && $refs.form.requestSubmit())"
         class="np-velo"
         x-transition:enter="np-t-velo" x-transition:enter-start="np-t-velo-0" x-transition:enter-end="np-t-velo-1"
         x-transition:leave="np-t-velo-s" x-transition:leave-start="np-t-velo-1" x-transition:leave-end="np-t-velo-0">

        <div class="np" role="dialog" aria-modal="true" aria-labelledby="epTitulo"
             @click.outside="!enviando && !swalAbierto() && cerrar()"
             x-show="openEditModal"
             x-transition:enter="np-t-caja" x-transition:enter-start="np-t-caja-0" x-transition:enter-end="np-t-caja-1"
             x-transition:leave="np-t-caja-s" x-transition:leave-start="np-t-caja-1" x-transition:leave-end="np-t-caja-0">

            <!-- ESCAPARATE: la caja con los cambios aplicados, existencias y resumen de cambios -->
            <aside class="np-vitrina" aria-label="Vista previa">
                <span class="np-gl" x-ref="gl" aria-hidden="true"></span>
                <span class="np-luz" aria-hidden="true"></span>
                <p class="np-et"><i class="bi bi-box"></i><span x-text="cambios.length ? 'Así quedará' : 'Así se ve hoy'"></span></p>

                <div class="np-escenario np-esc-corto" aria-hidden="true">
                    <div class="np-giro" :class="{ 'quieta': enviando }">
                        <div class="iv-caja np-caja" :class="'t-' + tipoDe(previa).clase" :style="'--k:' + colorDe(previa) + ';--s-fr:.04;--s-de:.3;--s-ar:0;--s-iz:.3;--g-fr:.16;--p-fr:38%'" x-html="cajaHTML(previa)"></div>
                    </div>
                    <span class="np-sombra"></span>
                </div>

                <div class="np-exis" :class="'e-' + estado">
                    <div class="np-exis-n">
                        <b class="t" x-text="disponible"></b>
                        <span>piezas<br>disponibles</span>
                        <span class="iv-estado" :class="'e-' + estado"><i></i><span x-text="{ agotado: 'Agotado', bajo: 'Por surtir', ok: 'Suficiente' }[estado]"></span></span>
                    </div>
                    <i class="iv-barra"><s :style="'width:' + (disponible > 0 ? Math.max(2, nivel) : 0) + '%'"></s><u x-show="Number(productoEditar.stock_minimo) > 0" :style="'left:' + posMinimo + '%'"></u></i>
                    <button type="button" class="np-exis-btn" x-show="typeof abrirSurtir === 'function'" @click="irASurtir()"><i class="bi bi-box-arrow-in-down"></i>Surtir existencias</button>
                </div>

                <div class="np-cambios" x-show="cambios.length" x-cloak>
                    <p><span x-text="cambios.length + (cambios.length === 1 ? ' cambio' : ' cambios')"></span><button type="button" @click="restaurar()">Deshacer todo</button></p>
                    <ul>
                        <template x-for="c in cambios" :key="c.campo">
                            <li><b x-text="c.etiqueta"></b><s x-text="c.antes || '(vacío)'"></s><i class="bi bi-arrow-right"></i><span x-text="c.despues || '(vacío)'"></span></li>
                        </template>
                    </ul>
                </div>
            </aside>

            <!-- FORMULARIO -->
            <form :action="urlActualizar" method="POST" x-ref="form" @submit="enviar($event)" class="np-form" novalidate>
                @csrf
                @method('PUT')
                {{-- Datos para volver a abrir este modal si el servidor regresa errores --}}
                <input type="hidden" name="_form" value="editar">
                <input type="hidden" name="_producto_id" :value="productoEditar.id">
                <input type="hidden" name="_stock_actual" :value="productoEditar.stock_actual">
                <input type="hidden" name="_stock_disponible" :value="productoEditar.stock_disponible">

                <header class="np-cab">
                    <div class="np-cab-t">
                        <p class="np-pre" x-text="'Editando ' + (tipoDe(previa).insumo ? 'insumo' : 'medicamento') + ' · ' + (original.codigo || '')"></p>
                        <h2 id="epTitulo" x-text="titulo(original.nombre || 'Medicamento')"></h2>
                    </div>
                    <button type="button" class="np-x" @click="cerrar()" :disabled="enviando" aria-label="Cerrar" title="Cerrar (Esc)"><i class="bi bi-x-lg"></i></button>
                </header>

                <div class="np-cuerpo">
                    @if ($errors->any() && old('_form') === 'editar')
                        <div class="np-error mt-sacude" role="alert">
                            <i class="bi bi-exclamation-octagon"></i>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- 1. Identificación -->
                    <section class="np-sec">
                        <h3><span>01</span>Identificación</h3>
                        <div class="np-campo">
                            <label for="ep-codigo">Código de barras o SKU <span class="np-mod" x-show="cambio('codigo')">modificado</span></label>
                            <div class="np-in np-in-ico" :class="(codigoRepetido || (intento && !productoEditar.codigo)) && 'mal'">
                                <i class="bi bi-upc-scan"></i>
                                <input id="ep-codigo" type="text" name="codigo" x-model="productoEditar.codigo" required maxlength="50" autocomplete="off" class="mono np-mayus"
                                       @keydown.enter.prevent="$refs.nombre.focus()">
                            </div>
                            <p class="np-ayuda mal" x-show="codigoRepetido" x-text="codigoRepetido ? 'Ya lo usa: ' + codigoRepetido.nombre : ''"></p>
                            <p class="np-ayuda" :class="avisoEan && 'mal'" x-show="!codigoRepetido" x-text="avisoEan || 'Escanéalo o escríbelo.'"></p>
                            @error('codigo')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                        </div>

                        <div class="np-campo">
                            <label for="ep-nombre">Nombre y presentación <span class="np-mod" x-show="cambio('nombre')">modificado</span></label>
                            <div class="np-in" :class="intento && !productoEditar.nombre && 'mal'">
                                <input id="ep-nombre" type="text" name="nombre" x-model="productoEditar.nombre" x-ref="nombre" required maxlength="150" class="np-mayus" autocomplete="off">
                            </div>
                            @error('nombre')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                        </div>

                        <div class="np-campo">
                            <label>Categoría <span class="np-mod" x-show="cambio('categoria_id')">modificado</span></label>
                            <input type="hidden" name="categoria_id" :value="productoEditar.categoria_id">
                            <template x-if="categorias.length > 10">
                                <div class="np-in np-in-ico np-cat-buscar">
                                    <i class="bi bi-search"></i>
                                    <input type="text" x-model="buscarCat" placeholder="Buscar categoría" autocomplete="off" style="text-transform:none"
                                           @keydown.enter.prevent="categoriasFiltradas[0] ? elegirCategoria(categoriasFiltradas[0]) : abrirCategorias()">
                                </div>
                            </template>
                            <div class="np-chips" role="radiogroup" aria-label="Categoría" :class="intento && !productoEditar.categoria_id && 'mal'">
                                <template x-for="c in categoriasFiltradas" :key="c.id">
                                    <button type="button" role="radio" :aria-checked="(String(productoEditar.categoria_id) === c.id).toString()" :aria-pressed="(String(productoEditar.categoria_id) === c.id).toString()" @click="elegirCategoria(c)">
                                        <i :style="'background:' + colorDe({ categoria: c.nombre })"></i><span x-text="c.nombre"></span>
                                    </button>
                                </template>
                                <button type="button" class="np-chip-nueva" @click="abrirCategorias()"><i class="bi bi-plus-lg"></i><span x-text="buscarCat.trim() && !categoriasFiltradas.length ? 'Crear «' + buscarCat.trim() + '»' : 'Nueva categoría'"></span></button>
                            </div>
                            @error('categoria_id')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                        </div>

                        <div class="np-campo">
                            <label for="ep-desc">Sustancia activa o nota <em>opcional</em> <span class="np-mod" x-show="cambio('descripcion')">modificado</span> <span class="np-cuenta mono" x-text="(productoEditar.descripcion || '').length + '/255'"></span></label>
                            <div class="np-in">
                                <textarea id="ep-desc" name="descripcion" x-model="productoEditar.descripcion" rows="2" maxlength="255" class="np-mayus"></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- 2. Precios -->
                    <section class="np-sec">
                        <h3><span>02</span>Precios por pieza</h3>
                        <div class="np-par">
                            <div class="np-campo">
                                <label for="ep-compra">Compra <span class="np-mod" x-show="cambio('precio_compra')">modificado</span></label>
                                <div class="np-in np-in-pre" :class="intento && productoEditar.precio_compra === '' && 'mal'">
                                    <span>$</span>
                                    <input id="ep-compra" type="number" name="precio_compra" x-model="productoEditar.precio_compra" step="0.01" min="0" required class="t">
                                </div>
                                <p class="np-ayuda" x-show="cambio('precio_compra')" x-text="'Antes ' + dinero(original.precio_compra) + ' (' + (variacion('precio_compra') > 0 ? '+' : '') + variacion('precio_compra') + '%)'"></p>
                                @error('precio_compra')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                            </div>
                            <div class="np-campo">
                                <label for="ep-venta">Venta <span class="np-mod" x-show="cambio('precio_venta')">modificado</span></label>
                                <div class="np-in np-in-pre" :class="((intento && productoEditar.precio_venta === '') || (margen !== null && margen < 0)) && 'mal'">
                                    <span>$</span>
                                    <input id="ep-venta" type="number" name="precio_venta" x-model="productoEditar.precio_venta" step="0.01" min="0" required class="t">
                                </div>
                                <p class="np-ayuda" x-show="cambio('precio_venta')" x-text="'Antes ' + dinero(original.precio_venta) + ' (' + (variacion('precio_venta') > 0 ? '+' : '') + variacion('precio_venta') + '%)'"></p>
                                @error('precio_venta')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="np-margen">
                            <span>Venta con margen de</span>
                            <template x-for="m in [30, 50, 80, 100]" :key="m">
                                <button type="button" @click="aplicarMargen(m)" :disabled="!(Number(productoEditar.precio_compra) > 0)" :aria-pressed="(margen === m).toString()" x-text="m + '%'"></button>
                            </template>
                        </div>
                        <div class="np-gana" x-show="margen !== null" :class="margen < 0 ? 'e-agotado' : (margen < 15 ? 'e-bajo' : 'e-ok')">
                            <i class="np-gana-bar"><s :style="'width:' + Math.max(3, Math.min(100, Math.abs(margen || 0))) + '%'"></s></i>
                            <b class="t" x-text="(margen >= 0 ? '+' : '−') + dinero(Math.abs(ganancia))"></b>
                            <span x-text="margen < 0 ? 'de pérdida por pieza' : 'de ganancia por pieza · ' + margen + '%'"></span>
                        </div>
                    </section>

                    <!-- 3. Aviso de existencias -->
                    <section class="np-sec">
                        <h3><span>03</span>Aviso de existencias</h3>
                        <div class="np-par">
                            <div class="np-campo">
                                <label for="ep-minimo">Mínimo para avisar <span class="np-mod" x-show="cambio('stock_minimo')">modificado</span></label>
                                <div class="np-paso">
                                    <button type="button" @click="productoEditar.stock_minimo = String(Math.max(0, (Number(productoEditar.stock_minimo) || 0) - 1))" aria-label="Bajar el mínimo">−</button>
                                    <input id="ep-minimo" type="number" name="stock_minimo" x-model="productoEditar.stock_minimo" min="0" step="1" required class="t">
                                    <button type="button" @click="productoEditar.stock_minimo = String((Number(productoEditar.stock_minimo) || 0) + 1)" aria-label="Subir el mínimo">+</button>
                                </div>
                                @error('stock_minimo')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                            </div>
                            <p class="np-nota">Las existencias no se editan aquí: se suman con <b>Surtir</b> o <b>Surtir lote</b> y se descuentan con las ventas.</p>
                        </div>
                    </section>
                </div>

                <footer class="np-pie">
                    <div class="np-avance">
                        <span x-show="!cambios.length">Sin cambios por guardar</span>
                        <span x-show="cambios.length" x-cloak class="np-hay"><i></i><span x-text="cambios.length + (cambios.length === 1 ? ' cambio por guardar' : ' cambios por guardar')"></span></span>
                    </div>
                    <button type="button" class="np-btn" @click="cerrar()" :disabled="enviando">Cancelar</button>
                    <button type="submit" class="np-btn np-prim" :disabled="enviando || !cambios.length">
                        <span x-show="enviando" class="np-gira"></span>
                        <span x-text="enviando ? 'Guardando…' : 'Guardar cambios'"></span>
                        <kbd x-show="!enviando">Ctrl ↵</kbd>
                    </button>
                </footer>
            </form>
        </div>
    </div>
</template>

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
                if (this.$refs.gl && typeof mtGuilloche === 'function') this.$refs.gl.innerHTML = mtGuilloche('EDICION');
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
            get previa() {
                const e = this.productoEditar || {}, disp = Number(e.stock_disponible ?? e.stock_actual) || 0, minimo = Number(e.stock_minimo) || 0;
                return {
                    nombre: String(e.nombre || 'Medicamento').toUpperCase(),
                    codigo: String(e.codigo || ''),
                    categoria: this.nombreDe(e.categoria_id) || 'General',
                    descripcion: e.descripcion || '',
                    venta: Number(e.precio_venta) || 0,
                    disponible: disp, minimo,
                    estado: disp <= 0 ? 'agotado' : (disp <= minimo ? 'bajo' : 'ok'),
                };
            },
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
                // Solo avisa si se cambió el código; el que ya está guardado no se vuelve a revisar
                const c = texto(this.productoEditar.codigo);
                if (!this.cambio('codigo') || !/^\d{13}$/.test(c)) return '';
                let suma = 0;
                for (let i = 0; i < 12; i++) suma += Number(c[i]) * (i % 2 === 0 ? 1 : 3);
                return (10 - (suma % 10)) % 10 === Number(c[12]) ? '' : 'No es un EAN-13 estándar (el último dígito no corresponde). Si lo escaneaste de la caja, revisa que se haya leído completo.';
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
            get estado() { const m = Number(this.productoEditar.stock_minimo) || 0; return this.disponible <= 0 ? 'agotado' : (this.disponible <= m ? 'bajo' : 'ok'); },
            get escala() { return Math.max((Number(this.productoEditar.stock_minimo) || 0) * 3, this.disponible, 1); },
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
                    confirmButtonColor: '#0b5a54', customClass: { container: 'z-[10050]' }
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
            swalAbierto() { return !!(window.Swal && Swal.isVisible && Swal.isVisible()); },
            cerrar() {
                if (this.enviando) return;
                if (!this.cambios.length || !window.Swal) { this.openEditModal = false; return; }
                Swal.fire({
                    title: '¿Descartar los cambios?', text: 'Los cambios en este medicamento no se guardarán.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Descartar', cancelButtonText: 'Seguir editando',
                    confirmButtonColor: '#c8102e', cancelButtonColor: '#0b5a54',
                    customClass: { container: 'z-[10050]' }
                }).then(r => { if (r.isConfirmed) this.openEditModal = false; });
            }
        };
    }
</script>