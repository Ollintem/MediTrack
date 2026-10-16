{{-- MODAL: SURTIR EXISTENCIAS DE UN PRODUCTO (mismo diseño que registrar: escaparate a la izquierda) --}}
{{-- Usa del index: openSurtirModal, productoSeleccionado y openLoteModal. Envía a /inventario/{id}/agregar-stock --}}
<template x-teleport="body">
    <div x-show="openSurtirModal" x-cloak
         x-data="surtirStock()"
         @keydown.escape.window="openSurtirModal && !enviando && !swalAbierto() && cerrar()"
         @keydown.window="openSurtirModal && (($event.ctrlKey || $event.metaKey) && $event.key === 'Enter') && ($event.preventDefault(), $refs.form.requestSubmit())"
         class="np-velo"
         x-transition:enter="np-t-velo" x-transition:enter-start="np-t-velo-0" x-transition:enter-end="np-t-velo-1"
         x-transition:leave="np-t-velo-s" x-transition:leave-start="np-t-velo-1" x-transition:leave-end="np-t-velo-0">

        <div class="np np-medio" role="dialog" aria-modal="true" aria-labelledby="ssTitulo"
             @click.outside="!enviando && !swalAbierto() && cerrar()"
             x-show="openSurtirModal"
             x-transition:enter="np-t-caja" x-transition:enter-start="np-t-caja-0" x-transition:enter-end="np-t-caja-1"
             x-transition:leave="np-t-caja-s" x-transition:leave-start="np-t-caja-1" x-transition:leave-end="np-t-caja-0">

            <!-- ESCAPARATE: la caja con el estado en que quedará y el antes → después -->
            <aside class="np-vitrina" aria-label="Existencias">
                <span class="np-gl" x-ref="gl" aria-hidden="true"></span>
                <span class="np-luz" aria-hidden="true"></span>
                <p class="np-et"><i class="bi bi-box-arrow-in-down"></i>Entrada al almacén</p>

                <div class="np-escenario np-esc-corto" aria-hidden="true">
                    <div class="np-giro" :class="{ 'quieta': enviando }">
                        <div class="iv-caja np-caja" :class="'t-' + tipoDe(prod).clase" :style="'--k:' + colorDe(prod) + ';--s-fr:.04;--s-de:.3;--s-ar:0;--s-iz:.3;--g-fr:.16;--p-fr:38%'" x-html="cajaHTML(prod)"></div>
                    </div>
                    <span class="np-sombra"></span>
                </div>

                <div class="np-antes">
                    <div :class="'e-' + estadoAntes">
                        <small>Hoy</small>
                        <b class="t" x-text="disponible"></b>
                        <span class="iv-estado" :class="'e-' + estadoAntes"><i></i><span x-text="etiqueta(estadoAntes)"></span></span>
                    </div>
                    <span class="np-antes-mas" :class="cantidadValida && 'on'"><i class="bi bi-arrow-right"></i><b class="t" x-text="cantidadValida ? '+' + cantidadNum : ''"></b></span>
                    <div :class="'e-' + estadoDespues">
                        <small>Quedará en</small>
                        <b class="t" x-text="despues"></b>
                        <span class="iv-estado" :class="'e-' + estadoDespues"><i></i><span x-text="etiqueta(estadoDespues)"></span></span>
                    </div>
                </div>
                <i class="np-doble"><s class="nuevo" :style="'width:' + nivel(despues) + '%'"></s><s :class="'e-' + estadoAntes" :style="'width:' + nivel(disponible) + '%'"></s><u x-show="minimo > 0" :style="'left:' + nivel(minimo) + '%'"></u></i>
                <p class="np-doble-pie">
                    <span x-text="'Mínimo ' + minimo"></span>
                    <span x-show="cantidadValida && estadoAntes !== 'ok' && estadoDespues === 'ok'" class="bien">Sale de la alerta</span>
                    <span x-show="cantidadValida && estadoDespues !== 'ok'" class="mal" x-text="'Faltarían ' + faltanParaMinimo + ' para pasar el mínimo'"></span>
                </p>
            </aside>

            <!-- FORMULARIO -->
            <form :action="urlBase + '/' + productoSeleccionado.id + '/agregar-stock'" method="POST" x-ref="form" @submit="enviar($event)" class="np-form" novalidate>
                @csrf

                <header class="np-cab">
                    <div class="np-cab-t">
                        <p class="np-pre" x-text="'Surtir existencias · ' + (productoSeleccionado.codigo || '')"></p>
                        <h2 id="ssTitulo" x-text="titulo(productoSeleccionado.nombre || 'Medicamento')"></h2>
                    </div>
                    <button type="button" class="np-x" @click="cerrar()" :disabled="enviando" aria-label="Cerrar" title="Cerrar (Esc)"><i class="bi bi-x-lg"></i></button>
                </header>

                <div class="np-cuerpo">
                    <section class="np-sec">
                        <h3><span>01</span>¿Cuántas piezas entran?</h3>
                        <div class="np-paso np-paso-xl" :class="intento && !cantidadValida && 'mal'">
                            <button type="button" @click="sumar(-1)" aria-label="Una menos">−</button>
                            <input id="ss-cantidad" type="number" name="cantidad" x-model="cantidad" x-ref="cantidad" min="1" step="1" required class="t" placeholder="0"
                                   @keydown.enter.prevent="$refs.motivo.focus()">
                            <button type="button" @click="sumar(1)" aria-label="Una más">+</button>
                        </div>
                        <div class="np-margen">
                            <template x-for="n in [5, 10, 20, 50, 100]" :key="n">
                                <button type="button" @click="sumar(n)" x-text="'+' + n"></button>
                            </template>
                            <button type="button" class="np-sug" x-show="sugerida > 0" @click="cantidad = String(sugerida)" :title="'Para llegar a ' + (minimo * 3) + ' piezas (3 veces el mínimo)'"><i class="bi bi-magic"></i><span x-text="'Sugerido ' + sugerida"></span></button>
                            <button type="button" x-show="cantidad !== ''" @click="cantidad = ''">Limpiar</button>
                        </div>
                        <p class="np-ayuda" :class="intento && !cantidadValida ? 'mal' : (muchas ? 'mal' : '')"
                           x-text="intento && !cantidadValida ? 'Escribe cuántas piezas entran (mínimo 1).' : (muchas ? 'Es una cantidad grande: revisa que sea correcta.' : (costo > 0 ? 'Costo aproximado de la entrada: ' + dinero(costo) : 'Piezas que recibiste físicamente.'))"></p>
                        @error('cantidad')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                    </section>

                    <section class="np-sec">
                        <h3><span>02</span>Motivo <em class="np-opc">opcional</em></h3>
                        <div class="np-chips">
                            <template x-for="m in motivos" :key="m.texto">
                                <button type="button" @click="elegirMotivo(m.texto)" :aria-pressed="motivo.toUpperCase().startsWith(m.texto.toUpperCase()).toString()"><i class="bi" :class="m.icono" style="width:auto;height:auto;background:none"></i><span x-text="m.texto"></span></button>
                            </template>
                        </div>
                        <div class="np-in" style="margin-top:10px">
                            <textarea id="ss-motivo" name="motivo" x-model="motivo" x-ref="motivo" rows="2" maxlength="255" class="np-mayus" placeholder="Ej. factura F-4892, proveedor Surtifarma"></textarea>
                        </div>
                    </section>

                    <button type="button" class="np-atajo" x-show="typeof openLoteModal !== 'undefined'" @click="irALote()">
                        <i class="bi bi-boxes"></i>
                        <span><b>¿Llegaron varios productos en la misma factura?</b><small>Usa Surtir lote para registrarlos juntos.</small></span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                <footer class="np-pie">
                    <span class="np-avance"></span>
                    <button type="button" class="np-btn" @click="cerrar()" :disabled="enviando">Cancelar</button>
                    <button type="submit" class="np-btn np-prim" :disabled="enviando">
                        <span x-show="enviando" class="np-gira"></span>
                        <span x-text="enviando ? 'Guardando…' : (cantidadValida ? 'Sumar ' + cantidadNum + (cantidadNum === 1 ? ' pieza' : ' piezas') : 'Sumar al almacén')"></span>
                        <kbd x-show="!enviando">Ctrl ↵</kbd>
                    </button>
                </footer>
            </form>
        </div>
    </div>
</template>

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
                if (this.$refs.gl && typeof mtGuilloche === 'function') this.$refs.gl.innerHTML = mtGuilloche('SURTIR');
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

            // Producto para dibujar la caja con el estado en que quedará
            get prod() {
                const r = this.productoSeleccionado || {};
                return {
                    nombre: String(r.nombre || 'Medicamento'), codigo: String(r.codigo || ''),
                    categoria: (r.categoria && r.categoria.nombre) || 'General', descripcion: r.descripcion || '',
                    venta: Number(r.precio_venta) || 0, disponible: this.despues, minimo: this.minimo, estado: this.estadoDespues,
                };
            },

            // ----- Visual -----
            escala() { return Math.max(this.minimo * 3, this.despues, 1); },
            nivel(n) { return Math.min(100, Math.round(n / this.escala() * 100)); },
            etiqueta(e) { return { agotado: 'Agotado', bajo: 'Por surtir', ok: 'Suficiente' }[e]; },
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