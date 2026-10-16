{{-- MODAL: SURTIR LOTE (varios productos de una misma factura); la tarima del escaparate se llena al escanear --}}
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
            'venta'      => (float) ($p->precio_venta ?? 0),
            'categoria'  => data_get($p, 'categoria.nombre') ?: 'General',
            'estado'     => $disponible <= 0 ? 'agotado' : ($disponible <= $minimo ? 'bajo' : 'ok'),
        ];
    })->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
@endphp

<template x-teleport="body">
    <div x-show="openLoteModal" x-cloak
         x-data="surtirLote()"
         @keydown.escape.window="openLoteModal && !enviando && !swalAbierto() && cerrar()"
         @keydown.window="openLoteModal && (($event.ctrlKey || $event.metaKey) && $event.key === 'Enter') && ($event.preventDefault(), registrar())"
         class="np-velo"
         x-transition:enter="np-t-velo" x-transition:enter-start="np-t-velo-0" x-transition:enter-end="np-t-velo-1"
         x-transition:leave="np-t-velo-s" x-transition:leave-start="np-t-velo-1" x-transition:leave-end="np-t-velo-0">

        <div class="np np-ancho" role="dialog" aria-modal="true" aria-labelledby="slTitulo"
             @click.outside="!enviando && !swalAbierto() && cerrar()"
             x-show="openLoteModal"
             x-transition:enter="np-t-caja" x-transition:enter-start="np-t-caja-0" x-transition:enter-end="np-t-caja-1"
             x-transition:leave="np-t-caja-s" x-transition:leave-start="np-t-caja-1" x-transition:leave-end="np-t-caja-0">

            <!-- ESCAPARATE: la tarima se llena con lo que se va escaneando -->
            <aside class="np-vitrina" aria-label="Resumen de la entrada">
                <span class="np-gl" x-ref="gl" aria-hidden="true"></span>
                <span class="np-luz" aria-hidden="true"></span>
                <p class="np-et"><i class="bi bi-truck"></i>Entrada de almacén</p>

                <div class="np-tarima" aria-hidden="true">
                    <template x-for="(r, i) in tarima" :key="'t' + r.id">
                        <span class="np-tarima-h" :style="'--i:' + i" :class="{ 'ok': r.estadoEnvio === 'ok', 'mal': r.estadoEnvio === 'error' }">
                            <span class="iv-caja mini" :class="'t-' + tipoDe(r.p).clase" :style="'--k:' + colorDe(r.p)" x-html="cajaHTML(r.p)"></span>
                            <b class="t" x-text="'+' + (Number(r.cantidad) || 0)"></b>
                        </span>
                    </template>
                    <i class="np-tarima-base"></i>
                    <p class="np-tarima-vacia" x-show="!renglones.length">Escanea el primer producto y aparecerá aquí.</p>
                    <p class="np-tarima-mas" x-show="renglones.length > tarima.length" x-text="'y ' + (renglones.length - tarima.length) + ' más'"></p>
                </div>

                <dl class="np-totales">
                    <div><dt>Productos</dt><dd class="t" x-text="pendientes.length"></dd></div>
                    <div><dt>Piezas</dt><dd class="t" x-text="totalPiezas"></dd></div>
                    <div><dt>Costo aprox.</dt><dd class="t" x-text="costoTotal > 0 ? dinero(costoTotal) : '—'"></dd></div>
                </dl>
                <div class="np-envio" x-show="enviando || huboExito" x-cloak>
                    <i><s :style="'width:' + (enviando ? progreso : 100) + '%'"></s></i>
                    <span x-text="enviando ? 'Registrando ' + enviados + ' de ' + totalEnvio + '…' : 'Entrada registrada'"></span>
                </div>
            </aside>

            <!-- CAPTURA -->
            <div class="np-form">
                <header class="np-cab">
                    <div class="np-cab-t">
                        <p class="np-pre">Inventario · varios productos de una factura</p>
                        <h2 id="slTitulo">Surtir lote</h2>
                    </div>
                    <button type="button" class="np-x" @click="cerrar()" :disabled="enviando" aria-label="Cerrar" title="Cerrar (Esc)"><i class="bi bi-x-lg"></i></button>
                </header>

                <div class="np-cuerpo">
                    <section class="np-sec">
                        <h3><span>01</span>Proveedor y factura</h3>
                        <div class="np-in np-in-ico" :class="intento && !referencia.trim() && 'mal'">
                            <i class="bi bi-receipt"></i>
                            <input type="text" x-model="referencia" x-ref="referencia" maxlength="150" :disabled="enviando" class="np-mayus"
                                   @keydown.enter.prevent="$refs.buscador.focus()" placeholder="Ej. factura F-4892 · Surtifarma">
                        </div>
                        <p class="np-ayuda" :class="intento && !referencia.trim() && 'mal'">Se guarda con cada entrada para saber de dónde vino el producto.</p>
                    </section>

                    <section class="np-sec">
                        <h3><span>02</span>Productos recibidos</h3>
                        <div class="np-busca" @click.outside="abiertoBusqueda = false">
                            <div class="np-in np-in-ico">
                                <i class="bi bi-upc-scan"></i>
                                <input type="text" x-ref="buscador" x-model="busqueda" :disabled="enviando" autocomplete="off" style="text-transform:none"
                                       @focus="abiertoBusqueda = true" @input="abiertoBusqueda = true; resaltado = 0"
                                       @keydown.arrow-down.prevent="resaltado = Math.min(resaltado + 1, resultados.length - 1)"
                                       @keydown.arrow-up.prevent="resaltado = Math.max(resaltado - 1, 0)"
                                       @keydown.enter.prevent="agregarDesdeBusqueda()"
                                       @keydown.escape.stop="abiertoBusqueda = false"
                                       placeholder="Escanea el código o busca por nombre">
                                <kbd class="np-kbd">Enter agrega</kbd>
                            </div>
                            <div class="np-res" x-show="abiertoBusqueda && busqueda.trim() !== ''" x-cloak>
                                <template x-for="(p, i) in resultados" :key="p.id">
                                    <button type="button" @click="agregar(p)" @mouseenter="resaltado = i" :class="resaltado === i && 'act'">
                                        <span class="iv-pk" :class="tipoDe(p).insumo && 'ins'" :style="'--k:' + colorDe(p)"><b x-show="!tipoDe(p).insumo" x-text="iniciales(p)"></b><span x-show="tipoDe(p).insumo" x-html="tipoDe(p).insumo ? itemSVG(tipoDe(p).item) : ''"></span></span>
                                        <span class="np-res-n"><b x-text="titulo(p.nombre)"></b><small class="mono" x-text="p.codigo"></small></span>
                                        <span class="np-res-s" :class="'e-' + p.estado" x-text="p.disponible + ' · mín ' + p.minimo"></span>
                                        <span class="np-res-ya" x-show="enLista(p)">+1</span>
                                    </button>
                                </template>
                                <p x-show="!resultados.length">No está «<span x-text="busqueda"></span>» en el catálogo.</p>
                            </div>
                        </div>

                        <div class="np-reponer" x-show="porReponer.length" x-cloak>
                            <i class="bi bi-lightbulb"></i>
                            <span x-text="porReponer.length + (porReponer.length === 1 ? ' producto está' : ' productos están') + ' por surtir o agotados.'"></span>
                            <button type="button" @click="agregarPorReponer()" :disabled="enviando"><i class="bi bi-magic"></i>Agregarlos con la cantidad sugerida</button>
                        </div>

                        <div class="np-filas">
                            <template x-for="r in renglones" :key="r.id">
                                <div class="np-fila" :class="['en-' + (r.estadoEnvio || 'espera'), r._nuevo ? 'nuevo' : '']">
                                    <span class="iv-pk" :class="tipoDe(r.p).insumo && 'ins'" :style="'--k:' + colorDe(r.p)">
                                        <b x-show="!tipoDe(r.p).insumo && !r.estadoEnvio" x-text="iniciales(r.p)"></b>
                                        <span x-show="tipoDe(r.p).insumo && !r.estadoEnvio" x-html="tipoDe(r.p).insumo ? itemSVG(tipoDe(r.p).item) : ''"></span>
                                        <i class="bi np-fila-ic" x-show="r.estadoEnvio" :class="{ 'bi-arrow-repeat np-girando': r.estadoEnvio === 'enviando', 'bi-check-lg': r.estadoEnvio === 'ok', 'bi-exclamation-lg': r.estadoEnvio === 'error' }"></i>
                                    </span>
                                    <div class="np-fila-n">
                                        <b x-text="titulo(r.p.nombre)"></b>
                                        <small><span class="mono" x-text="r.p.codigo"></span> · <span x-text="r.p.fisico"></span> <i class="bi bi-arrow-right"></i> <b class="t" x-text="r.p.fisico + (Number(r.cantidad) || 0)"></b> piezas</small>
                                        <i class="np-doble claro"><s class="nuevo" :style="'width:' + nivelDespues(r) + '%'"></s><s :class="'e-' + r.p.estado" :style="'width:' + nivelAntes(r) + '%'"></s></i>
                                        <small class="mal" x-show="!!r.error" x-text="r.error"></small>
                                        <small class="sug" x-show="r.sugerida && r.estadoEnvio === ''" x-text="'Sugerido para llegar a ' + (r.p.minimo * 3) + ' piezas'"></small>
                                    </div>
                                    <div class="np-paso np-paso-s" :class="!(Number(r.cantidad) >= 1) && 'mal'">
                                        <button type="button" @click="r.cantidad = Math.max(1, (Number(r.cantidad) || 1) - 1)" :disabled="bloqueado(r)" aria-label="Una menos">−</button>
                                        <input type="number" x-model="r.cantidad" min="1" step="1" :disabled="bloqueado(r)" :id="'sl-cant-' + r.id" class="t" @keydown.enter.prevent="$refs.buscador.focus()" aria-label="Cantidad">
                                        <button type="button" @click="r.cantidad = (Number(r.cantidad) || 0) + 1" :disabled="bloqueado(r)" aria-label="Una más">+</button>
                                    </div>
                                    <div class="np-in np-lote"><input type="text" x-model="r.lote" :disabled="bloqueado(r)" maxlength="60" placeholder="Lote" class="np-mayus mono" aria-label="Lote"></div>
                                    <button type="button" class="np-quitar" @click="quitar(r)" :disabled="enviando" aria-label="Quitar" title="Quitar"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </template>
                            <div class="np-filas-vacia" x-show="!renglones.length">
                                <i class="bi bi-upc-scan"></i>
                                <p><b>Escanea o busca el primer producto</b>Si escaneas el mismo dos veces, se suma a la cantidad.</p>
                            </div>
                        </div>
                    </section>
                </div>

                <footer class="np-pie">
                    <span class="np-avance" x-text="pendientes.length ? pendientes.length + (pendientes.length === 1 ? ' producto · ' : ' productos · ') + totalPiezas + ' piezas' : (huboExito ? 'Todo registrado' : 'Sin productos todavía')"></span>
                    <button type="button" class="np-btn" @click="cerrar()" :disabled="enviando"><span x-text="huboExito ? 'Terminar' : 'Cancelar'"></span></button>
                    <button type="button" class="np-btn np-prim" @click="registrar()" :disabled="enviando || !pendientes.length">
                        <span x-show="enviando" class="np-gira"></span>
                        <span x-text="enviando ? 'Registrando ' + enviados + ' de ' + totalEnvio + '…' : (hayErrores ? 'Reintentar' : 'Registrar entrada')"></span>
                        <kbd x-show="!enviando">Ctrl ↵</kbd>
                    </button>
                </footer>
            </div>
        </div>
    </div>
</template>

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
                if (this.$refs.gl && typeof mtGuilloche === 'function') this.$refs.gl.innerHTML = mtGuilloche('LOTE');
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
                this.avisar('Se agregaron los productos por surtir. Ajusta las cantidades a lo que llegó.', 'info');
            },

            // Las últimas seis cajas escaneadas, para la tarima
            get tarima() { return this.renglones.slice(0, 6); },

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
                    this.avisar('Entrada registrada: ' + ok + (ok === 1 ? ' producto' : ' productos') + ', ' + cola.reduce((s, r) => s + Number(r.cantidad), 0) + ' piezas.', 'success');
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
                    text: 'Se perderán los ' + this.renglones.length + ' productos que agregaste.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Descartar', cancelButtonText: 'Seguir capturando',
                    confirmButtonColor: '#c8102e', cancelButtonColor: '#0b5a54',
                    customClass: { container: 'z-[10050]' }
                }).then(r => { if (r.isConfirmed) { this.openLoteModal = false; this.limpiar(); } });
            }
        };
    }
</script>