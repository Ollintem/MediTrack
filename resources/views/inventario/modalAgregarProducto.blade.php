{{-- MODAL: REGISTRAR NUEVO MEDICAMENTO --}}
{{-- Usa del index: openCreateModal, openCategoriaModal, productos (códigos repetidos) y la caja 3D (cajaHTML, partes, colorDe, barrasDe) --}}
{{-- A la izquierda, el escaparate: la caja del medicamento se arma mientras se captura. A la derecha, el formulario en tres pasos. --}}
@php
    $categoriasModal = collect($categorias ?? [])->map(fn ($c) => ['id' => (string) $c->id, 'nombre' => $c->nombre])->values();
    $categoriaInicial = (string) old('categoria_id', $categoriasModal->first()['id'] ?? '');
@endphp

<template x-teleport="body">
    <div x-show="openCreateModal" x-cloak
         x-data="formNuevoProducto()"
         @categorias-inventario.window="actualizarCategorias($event.detail)"
         @keydown.escape.window="openCreateModal && !enviando && !swalAbierto() && cerrar()"
         @keydown.window="openCreateModal && (($event.ctrlKey || $event.metaKey) && $event.key === 'Enter') && ($event.preventDefault(), $refs.form.requestSubmit())"
         class="np-velo"
         x-transition:enter="np-t-velo" x-transition:enter-start="np-t-velo-0" x-transition:enter-end="np-t-velo-1"
         x-transition:leave="np-t-velo-s" x-transition:leave-start="np-t-velo-1" x-transition:leave-end="np-t-velo-0">

        <div class="np" role="dialog" aria-modal="true" aria-labelledby="npTitulo"
             @click.outside="!enviando && !swalAbierto() && cerrar()"
             x-show="openCreateModal"
             x-transition:enter="np-t-caja" x-transition:enter-start="np-t-caja-0" x-transition:enter-end="np-t-caja-1"
             x-transition:leave="np-t-caja-s" x-transition:leave-start="np-t-caja-1" x-transition:leave-end="np-t-caja-0">

            <!-- ESCAPARATE: la caja se arma con lo capturado -->
            <aside class="np-vitrina" aria-label="Vista previa">
                <span class="np-gl" x-ref="gl" aria-hidden="true"></span>
                <span class="np-luz" aria-hidden="true"></span>
                <p class="np-et"><i class="bi bi-box"></i>Así se verá en el inventario</p>

                <div class="np-escenario" aria-hidden="true">
                    <div class="np-giro" :class="{ 'quieta': enviando }">
                        <div class="iv-caja np-caja" :class="'t-' + tipoDe(previa).clase" :style="'--k:' + colorDe(previa) + ';--s-fr:.04;--s-de:.3;--s-ar:0;--s-iz:.3;--g-fr:.16;--p-fr:38%'" x-html="cajaHTML(previa)"></div>
                    </div>
                    <span class="np-sombra"></span>
                </div>

                <dl class="np-partes">
                    <div :class="partesNombre.principal && 'ok'"><dt>Sustancia</dt><dd x-text="partesNombre.principal || '—'"></dd></div>
                    <div :class="partesNombre.dosis && 'ok'"><dt>Dosis</dt><dd x-text="partesNombre.dosis || '—'"></dd></div>
                    <div :class="partesNombre.forma && 'ok'"><dt>Forma</dt><dd x-text="partesNombre.forma || '—'"></dd></div>
                </dl>

                <div class="np-codigo" :class="{ 'ok': codigoValido, 'mal': errorCodigo || (f.codigo.length === 13 && !codigoValido) }">
                    <span class="np-codigo-bar" x-html="f.codigo.length === 13 ? barrasDe(f.codigo) : ''"></span>
                    <span class="np-codigo-vacio" x-show="f.codigo.length !== 13" aria-hidden="true">
                        <template x-for="n in 13" :key="n"><i :class="n <= f.codigo.length && 'on'"></i></template>
                    </span>
                    <span class="np-codigo-t mono" x-text="f.codigo || '0000000000000'"></span>
                </div>
            </aside>

            <!-- FORMULARIO -->
            <form action="{{ route('inventario.store') }}" method="POST" x-ref="form" @submit="enviar($event)" class="np-form" novalidate>
                @csrf

                <header class="np-cab">
                    <div>
                        <p class="np-pre" x-text="'Inventario · ' + (tipoDe(previa).insumo ? 'nuevo insumo' : 'nuevo medicamento')">Inventario · nuevo medicamento</p>
                        <h2 id="npTitulo" x-text="tipoDe(previa).insumo ? 'Registrar insumo' : 'Registrar medicamento'">Registrar medicamento</h2>
                    </div>
                    <button type="button" class="np-x" @click="cerrar()" :disabled="enviando" aria-label="Cerrar" title="Cerrar (Esc)"><i class="bi bi-x-lg"></i></button>
                </header>

                <div class="np-cuerpo">
                    @if ($errors->any())
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
                            <label for="np-codigo">Código de barras <em>EAN-13</em></label>
                            <div class="np-in np-in-ico" :class="{ 'mal': errorCodigo, 'bien': codigoValido }">
                                <i class="bi bi-upc-scan"></i>
                                <input id="np-codigo" type="text" name="codigo" x-model="f.codigo" x-ref="codigo"
                                       inputmode="numeric" autocomplete="off" maxlength="13" required class="mono"
                                       @input="f.codigo = f.codigo.replace(/\D/g, '').slice(0, 13)"
                                       @keydown.enter.prevent="$refs.nombre.focus()"
                                       placeholder="Escanéalo o escríbelo">
                                <span class="np-cuenta mono" x-text="f.codigo.length + '/13'"></span>
                            </div>
                            <p class="np-ayuda" :class="{ 'mal': errorCodigo || (f.codigo.length === 13 && !codigoValido), 'bien': codigoValido }" x-text="mensajeCodigo"></p>
                            @error('codigo')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                        </div>

                        <div class="np-campo">
                            <label id="npNombreL" for="np-nombre">Nombre y presentación</label>
                            <div class="np-in" :class="intento && !f.nombre.trim() && 'mal'">
                                <input id="np-nombre" type="text" name="nombre" x-model="f.nombre" x-ref="nombre" required maxlength="150"
                                       class="np-mayus" placeholder="Ej. PARACETAMOL 500 MG TABLETAS" autocomplete="off">
                            </div>
                            <p class="np-ayuda">Escribe sustancia, dosis y forma: la caja las separa sola.</p>
                            @error('nombre')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                        </div>

                        <div class="np-campo">
                            <label>Categoría</label>
                            <input type="hidden" name="categoria_id" :value="f.categoria_id">
                            <template x-if="categorias.length > 10">
                                <div class="np-in np-in-ico np-cat-buscar">
                                    <i class="bi bi-search"></i>
                                    <input type="text" x-model="buscarCat" placeholder="Buscar categoría" autocomplete="off" style="text-transform:none"
                                           @keydown.enter.prevent="categoriasFiltradas[0] ? elegirCategoria(categoriasFiltradas[0]) : abrirCategorias()">
                                </div>
                            </template>
                            <div class="np-chips" role="radiogroup" aria-label="Categoría" :class="intento && !f.categoria_id && 'mal'">
                                <template x-for="c in categoriasFiltradas" :key="c.id">
                                    <button type="button" role="radio" :aria-checked="(f.categoria_id === c.id).toString()" @click="elegirCategoria(c)" :aria-pressed="(f.categoria_id === c.id).toString()">
                                        <i :style="'background:' + colorDe({ categoria: c.nombre })"></i><span x-text="c.nombre"></span>
                                    </button>
                                </template>
                                <button type="button" class="np-chip-nueva" @click="abrirCategorias()"><i class="bi bi-plus-lg"></i><span x-text="buscarCat.trim() && !categoriasFiltradas.length ? 'Crear «' + buscarCat.trim() + '»' : 'Nueva categoría'"></span></button>
                            </div>
                            <p class="np-ayuda mal" x-show="!categorias.length" x-cloak>Aún no hay categorías: crea la primera para poder registrar.</p>
                            @error('categoria_id')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                        </div>

                        <div class="np-campo">
                            <label for="np-desc">Sustancia activa o nota <em>opcional</em> <span class="np-cuenta mono" x-text="f.descripcion.length + '/255'"></span></label>
                            <div class="np-in">
                                <textarea id="np-desc" name="descripcion" x-model="f.descripcion" rows="2" maxlength="255" class="np-mayus" placeholder="Ej. CAJA CON 20 TABLETAS"></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- 2. Precios -->
                    <section class="np-sec">
                        <h3><span>02</span>Precios por pieza</h3>
                        <div class="np-par">
                            <div class="np-campo">
                                <label for="np-compra">Compra</label>
                                <div class="np-in np-in-pre" :class="intento && f.precio_compra === '' && 'mal'">
                                    <span>$</span>
                                    <input id="np-compra" type="number" name="precio_compra" x-model="f.precio_compra" step="0.01" min="0" required class="t" placeholder="0.00">
                                </div>
                                @error('precio_compra')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                            </div>
                            <div class="np-campo">
                                <label for="np-venta">Venta</label>
                                <div class="np-in np-in-pre" :class="((intento && f.precio_venta === '') || (margen !== null && margen < 0)) && 'mal'">
                                    <span>$</span>
                                    <input id="np-venta" type="number" name="precio_venta" x-model="f.precio_venta" step="0.01" min="0" required class="t" placeholder="0.00">
                                </div>
                                @error('precio_venta')<p class="np-ayuda mal">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="np-margen">
                            <span>Venta con margen de</span>
                            <template x-for="m in [30, 50, 80, 100]" :key="m">
                                <button type="button" @click="aplicarMargen(m)" :disabled="!(Number(f.precio_compra) > 0)" :aria-pressed="(margen === m).toString()" x-text="m + '%'"></button>
                            </template>
                        </div>
                        <div class="np-gana" x-show="margen !== null" x-cloak :class="margen < 0 ? 'e-agotado' : (margen < 15 ? 'e-bajo' : 'e-ok')">
                            <i class="np-gana-bar"><s :style="'width:' + Math.max(3, Math.min(100, Math.abs(margen))) + '%'"></s></i>
                            <b class="t" x-text="(margen >= 0 ? '+' : '−') + dinero(Math.abs(ganancia))"></b>
                            <span x-text="margen < 0 ? 'de pérdida por pieza' : 'de ganancia por pieza · ' + margen + '%'"></span>
                        </div>
                    </section>

                    <!-- 3. Existencias -->
                    <section class="np-sec">
                        <h3><span>03</span>Existencias</h3>
                        <div class="np-par">
                            <div class="np-campo">
                                <label for="np-stock">Piezas que entran hoy</label>
                                <div class="np-paso">
                                    <button type="button" @click="f.stock_actual = String(Math.max(0, (Number(f.stock_actual) || 0) - 1))" aria-label="Quitar una pieza">−</button>
                                    <input id="np-stock" type="number" name="stock_actual" x-model="f.stock_actual" min="0" step="1" required class="t">
                                    <button type="button" @click="f.stock_actual = String((Number(f.stock_actual) || 0) + 1)" aria-label="Agregar una pieza">+</button>
                                </div>
                            </div>
                            <div class="np-campo">
                                <label for="np-minimo">Mínimo para avisar</label>
                                <div class="np-paso">
                                    <button type="button" @click="f.stock_minimo = String(Math.max(0, (Number(f.stock_minimo) || 0) - 1))" aria-label="Bajar el mínimo">−</button>
                                    <input id="np-minimo" type="number" name="stock_minimo" x-model="f.stock_minimo" min="0" step="1" required class="t">
                                    <button type="button" @click="f.stock_minimo = String((Number(f.stock_minimo) || 0) + 1)" aria-label="Subir el mínimo">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="np-nivel" :class="'e-' + previa.estado">
                            <i class="iv-barra"><s :style="'width:' + nivelPrevio + '%'"></s><u x-show="Number(f.stock_minimo) > 0" :style="'left:' + minimoPrevio + '%'"></u></i>
                            <p><span class="iv-estado" :class="'e-' + previa.estado"><i></i><span x-text="{ ok: 'Suficiente', bajo: 'Por surtir', agotado: 'Agotado' }[previa.estado]"></span></span>
                               <span x-text="previa.estado === 'agotado' ? 'Se registrará sin existencias.' : (previa.estado === 'bajo' ? 'Entra en o bajo el mínimo: aparecerá por surtir.' : 'Queda ' + (Number(f.stock_actual) - Number(f.stock_minimo)) + ' arriba del mínimo.')"></span></p>
                        </div>
                    </section>
                </div>

                <footer class="np-pie">
                    <div class="np-avance" :title="camposListos + ' de 7 datos'">
                        <span class="np-puntos"><template x-for="n in 7" :key="n"><i :class="n <= camposListos && 'on'"></i></template></span>
                        <span x-text="faltantes.length ? (intento ? 'Falta: ' + faltantes.join(', ') : camposListos + ' de 7 datos') : 'Listo para registrar'" :class="intento && faltantes.length && 'mal'"></span>
                    </div>
                    <button type="button" class="np-btn" @click="cerrar()" :disabled="enviando">Cancelar</button>
                    <button type="submit" class="np-btn np-prim" :disabled="enviando || !categorias.length">
                        <span x-show="enviando" class="np-gira"></span>
                        <span x-text="enviando ? 'Registrando…' : 'Registrar'"></span>
                        <kbd x-show="!enviando">Ctrl ↵</kbd>
                    </button>
                </footer>
            </form>
        </div>
    </div>
</template>

@verbatim
<style>
/* Tokens propios: el modal vive en el body (teleport) */
.np-velo, .np {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
}
.np-velo { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; padding: 16px; background: rgba(4, 18, 17, .62); backdrop-filter: blur(4px); }
.np { position: relative; width: min(1080px, 100%); height: min(720px, calc(100vh - 32px)); display: grid; grid-template-columns: 380px minmax(0, 1fr);
    background: var(--papel); border-radius: 22px; overflow: hidden; box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .55); font: 450 14px/1.45 var(--ui); color: var(--texto); }
.np *, .np *:before, .np *:after { box-sizing: border-box; }
.np :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.np button { cursor: pointer; }
.np .t { font-variant-numeric: tabular-nums; }
.np .mono { font-family: var(--mono); letter-spacing: .03em; }
.np-t-velo { transition: opacity .18s var(--e); } .np-t-velo-s { transition: opacity .14s ease-in; }
.np-t-velo-0 { opacity: 0; } .np-t-velo-1 { opacity: 1; }
.np-t-caja { transition: opacity .22s var(--e), translate .26s var(--e), scale .26s var(--e); } .np-t-caja-s { transition: opacity .13s ease-in, scale .13s ease-in; }
.np-t-caja-0 { opacity: 0; translate: 0 10px; scale: .98; } .np-t-caja-1 { opacity: 1; translate: 0 0; scale: 1; }

/* ---------- Escaparate ---------- */
.np-vitrina { position: relative; overflow: hidden; display: flex; flex-direction: column; padding: 22px 22px 20px; color: #eafaf6;
    background: radial-gradient(90% 60% at 50% 30%, #134a45 0%, #0a2826 50%, #040d0c 100%); }
.np-gl { position: absolute; inset: 0; opacity: .14; pointer-events: none; }
.np-gl svg { width: 100%; height: 100%; }
.np-luz { position: absolute; left: 50%; top: -20px; width: 360px; height: 330px; margin-left: -180px; pointer-events: none;
    background: linear-gradient(180deg, rgba(196, 255, 244, .3), rgba(166, 239, 226, .04) 78%, transparent); clip-path: polygon(40% 0, 60% 0, 100% 100%, 0 100%); filter: blur(14px); }
.np-et { position: relative; display: flex; align-items: center; gap: 7px; font: 600 11px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--foil); }
.np-escenario { position: relative; flex: 1; min-height: 210px; display: grid; place-items: center; perspective: 900px; }
.np-giro { transform-style: preserve-3d; animation: npVaiven 7s ease-in-out infinite; }
.np-giro.quieta { animation-play-state: paused; }
.np-caja { transform: none; }
@keyframes npVaiven { 0%, 100% { transform: rotateX(-14deg) rotateY(-38deg); } 50% { transform: rotateX(-9deg) rotateY(-16deg); } }
.np-sombra { position: absolute; left: 50%; top: calc(50% + 84px); width: 240px; height: 26px; margin-left: -120px; border-radius: 50%; background: radial-gradient(closest-side, rgba(0, 0, 0, .6), transparent); filter: blur(3px); }
.np-partes { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; margin-top: 4px; }
.np-partes div { padding: 8px 10px; border-radius: 10px; background: rgba(255, 255, 255, .06); border: 1px solid rgba(166, 239, 226, .12); min-width: 0; transition: background .2s, border-color .2s; }
.np-partes div.ok { background: rgba(166, 239, 226, .12); border-color: rgba(166, 239, 226, .3); }
.np-partes dt { font: 600 10px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: rgba(166, 239, 226, .75); }
.np-partes dd { margin-top: 2px; font: 650 13.5px var(--ui); color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-partes div:not(.ok) dd { color: rgba(234, 250, 246, .4); }
.np-codigo { position: relative; margin-top: 10px; display: grid; justify-items: center; gap: 4px; padding: 10px 12px 8px; border-radius: 12px; background: #fff; color: var(--tinta); box-shadow: 0 0 0 2px transparent; transition: box-shadow .2s; }
.np-codigo.ok { box-shadow: 0 0 0 2px #7fe3b8; }
.np-codigo.mal { box-shadow: 0 0 0 2px #f39aa8; }
.np-codigo-bar svg { display: block; width: 230px; height: 46px; }
.np-codigo-vacio { display: flex; gap: 3px; height: 46px; align-items: stretch; }
.np-codigo-vacio i { width: 13px; border-radius: 2px; background: repeating-linear-gradient(90deg, #dfe6e4 0 2px, transparent 2px 4px); }
.np-codigo-vacio i.on { background: repeating-linear-gradient(90deg, #0b1e1c 0 2px, transparent 2px 4px); }
.np-codigo-t { font-size: 12px; color: var(--media); }

/* ---------- Formulario ---------- */
.np-form { min-width: 0; min-height: 0; display: flex; flex-direction: column; }
.np-cab { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 20px 24px 14px; border-bottom: 1px solid var(--linea2); }
.np-pre { font: 600 11px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--teal); }
.np-cab h2 { margin-top: 2px; font: 750 26px/1.1 var(--disp); color: var(--tinta); letter-spacing: -.01em; }
.np-x { width: 36px; height: 36px; flex: none; border-radius: 10px; display: grid; place-items: center; background: var(--niebla); color: var(--texto); }
.np-x:hover { background: var(--velo); color: var(--tinta); }
.np-cuerpo { flex: 1; min-height: 0; overflow-y: auto; padding: 6px 24px 18px; overscroll-behavior: contain; }
.np-error { display: flex; gap: 10px; margin-top: 14px; padding: 12px 14px; border-radius: 12px; background: var(--rosav); color: var(--rosa); font-size: 13px; font-weight: 550; }
.np-sec { padding: 16px 0 14px; border-bottom: 1px solid var(--linea2); }
.np-sec:last-child { border-bottom: 0; }
.np-sec h3 { display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px; font: 700 17px var(--disp); color: var(--tinta); }
.np-sec h3 span { font: 600 11px var(--mono); color: var(--teal); letter-spacing: .08em; }
.np-campo { margin-bottom: 12px; min-width: 0; }
.np-campo:last-child { margin-bottom: 0; }
.np-campo > label { display: flex; align-items: baseline; gap: 6px; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--tinta); }
.np-campo > label em { font-style: normal; font-weight: 500; color: var(--media); font-size: 12px; }
.np-campo > label .np-cuenta { margin-left: auto; }
.np-in { display: flex; align-items: center; gap: 8px; min-height: 42px; padding: 0 12px; border-radius: 11px; border: 1px solid var(--linea); background: var(--papel); transition: border-color .15s, box-shadow .15s; }
.np-in:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .14); }
.np-in.mal { border-color: #e9919f; box-shadow: 0 0 0 3px rgba(200, 16, 46, .08); }
.np-in.bien { border-color: #7fcfae; }
.np-in input, .np-in textarea { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; padding: 10px 0; font: 500 14.5px var(--ui); color: var(--tinta); }
.np-in input.mono { font-family: var(--mono); letter-spacing: .08em; }
.np-in textarea { resize: none; line-height: 1.4; }
.np-in input::placeholder, .np-in textarea::placeholder { color: #93a19d; font-weight: 450; text-transform: none; letter-spacing: 0; font-family: var(--ui); }
.np-in > .bi { color: var(--cred); }
.np-in-pre > span { font: 600 15px var(--ui); color: var(--media); }
.np-in input[type=number]::-webkit-inner-spin-button, .np-in input[type=number]::-webkit-outer-spin-button,
.np-paso input::-webkit-inner-spin-button, .np-paso input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.np-in input[type=number], .np-paso input { -moz-appearance: textfield; }
.np-mayus { text-transform: uppercase; }
.np-cuenta { font-size: 11.5px; color: var(--media); }
.np-ayuda { margin-top: 5px; font-size: 12.5px; color: var(--media); }
.np-ayuda.mal { color: var(--rosa); font-weight: 550; }
.np-ayuda.bien { color: var(--verde); font-weight: 550; }
.np-par { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }

.np-cat-buscar { margin-bottom: 8px; }
.np-chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 2px; border-radius: 12px; }
.np-chips.mal { box-shadow: 0 0 0 2px rgba(200, 16, 46, .25); }
.np-chips button { height: 34px; padding: 0 12px; border-radius: 999px; display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--linea); background: var(--papel); font: 550 13px var(--ui); color: var(--texto); transition: background .15s, border-color .15s, color .15s; }
.np-chips button:hover { border-color: #b9cbc6; }
.np-chips button > i { width: 9px; height: 9px; border-radius: 3px; }
.np-chips button[aria-pressed=true] { background: var(--tinta); border-color: var(--tinta); color: #fff; }
.np-chips .np-chip-nueva { border-style: dashed; color: var(--teal); }
.np-chips .np-chip-nueva .bi { font-size: 12px; }

.np-margen { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 10px; font-size: 12.5px; color: var(--media); }
.np-margen span { margin-right: 2px; }
.np-margen button { height: 28px; padding: 0 10px; border-radius: 8px; border: 1px solid var(--linea); background: var(--papel); font: 600 12.5px var(--ui); color: var(--texto); }
.np-margen button:hover:not(:disabled) { border-color: var(--teal); color: var(--teal); }
.np-margen button[aria-pressed=true] { background: var(--velo); border-color: var(--teal); color: var(--cred); }
.np-margen button:disabled { opacity: .45; cursor: not-allowed; }
.np-gana { display: grid; grid-template-columns: 1fr; gap: 2px 8px; margin-top: 10px; padding: 10px 12px; border-radius: 12px; background: var(--cv); }
.np-gana-bar { display: block; height: 5px; border-radius: 3px; background: rgba(255, 255, 255, .8); margin-bottom: 6px; }
.np-gana-bar s { display: block; height: 100%; border-radius: 3px; background: var(--c); transition: width .35s var(--e); }
.np-gana b { font: 750 18px var(--disp); color: var(--c); }
.np-gana span { font-size: 12.5px; color: var(--texto); }
.np-gana { grid-template-columns: auto 1fr; align-items: baseline; }
.np-gana-bar { grid-column: 1 / -1; }

.np-paso { display: grid; grid-template-columns: 42px minmax(0, 1fr) 42px; height: 46px; border-radius: 11px; border: 1px solid var(--linea); overflow: hidden; transition: border-color .15s, box-shadow .15s; }
.np-paso:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .14); }
.np-paso button { background: var(--niebla); font: 600 18px var(--ui); color: var(--texto); transition: background .12s; }
.np-paso button:hover { background: var(--velo); color: var(--cred); }
.np-paso input { width: 100%; border: 0; outline: 0 !important; box-shadow: none !important; text-align: center; font: 750 20px var(--disp); color: var(--tinta); background: var(--papel); }
.np-nivel { margin-top: 12px; padding: 10px 12px 11px; border-radius: 12px; background: var(--cv); }
.np-nivel .iv-barra { height: 6px; background: rgba(255, 255, 255, .85); }
.np-nivel p { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 9px; font-size: 12.5px; color: var(--texto); }
.np-nivel .iv-estado { background: var(--papel); }
.np .e-ok { --c: var(--verde); --cv: var(--verdev); }
.np .e-bajo { --c: var(--ambar); --cv: var(--ambarv); }
.np .e-agotado { --c: var(--rosa); --cv: var(--rosav); }

.np-pie { display: flex; align-items: center; gap: 8px; padding: 14px 24px; border-top: 1px solid var(--linea2); background: #f8fbfa; }
.np-avance { margin-right: auto; min-width: 0; display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: var(--media); }
.np-avance > span:last-child { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-avance .mal { color: var(--rosa); font-weight: 550; }
.np-puntos { display: flex; gap: 3px; flex: none; }
.np-puntos i { width: 14px; height: 4px; border-radius: 2px; background: var(--linea); transition: background .2s; }
.np-puntos i.on { background: var(--teal); }
.np-btn { height: 42px; padding: 0 16px; border-radius: 11px; display: inline-flex; align-items: center; gap: 9px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); white-space: nowrap; }
.np-btn:hover:not(:disabled) { border-color: #b9cbc6; }
.np-btn:disabled { opacity: .6; cursor: not-allowed; }
.np-prim { background: var(--tinta); border-color: var(--tinta); color: #fff; padding: 0 12px 0 18px; }
.np-prim:hover:not(:disabled) { background: #163532; }
.np-prim kbd { font: 600 11px var(--mono); border: 1px solid rgba(255, 255, 255, .25); border-radius: 6px; padding: 1px 6px; color: var(--foil); }
.np-gira { width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .4); border-top-color: #fff; animation: npGira .7s linear infinite; }
@keyframes npGira { to { rotate: 360deg; } }

/* ---------- Piezas compartidas con Editar, Surtir y Surtir lote ---------- */
.np-esc-corto { min-height: 180px; flex: 0 1 230px; }
.np-cab-t { min-width: 0; }
.np-cab-t h2 { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-exis { position: relative; margin-top: 4px; padding: 12px 14px; border-radius: 14px; background: rgba(255, 255, 255, .07); border: 1px solid rgba(166, 239, 226, .14); }
.np-exis-n { display: flex; align-items: flex-end; gap: 9px; }
.np-exis-n > b { font: 800 38px/.85 var(--disp); color: #fff; }
.np-exis.e-agotado .np-exis-n > b { color: #ff9aab; }
.np-exis.e-bajo .np-exis-n > b { color: #ffcf7a; }
.np-exis-n > span:not(.iv-estado) { font-size: 12px; line-height: 1.15; color: rgba(234, 250, 246, .75); }
.np-exis-n .iv-estado { margin-left: auto; background: #fff; }
.np-exis .iv-barra { margin-top: 10px; height: 6px; background: rgba(255, 255, 255, .14); }
.np-exis .iv-barra u { background: #fff; }
.np-exis.e-ok .iv-barra s { background: #7fe3b8; }
.np-exis-btn { margin-top: 10px; width: 100%; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: rgba(166, 239, 226, .16); color: #eafaf6; font: 600 13px var(--ui); }
.np-exis-btn:hover { background: rgba(166, 239, 226, .26); }
.np-cambios { position: relative; margin-top: 10px; padding: 10px 12px; border-radius: 14px; background: #fff; color: var(--tinta); min-height: 0; overflow-y: auto; }
.np-cambios p { display: flex; align-items: center; justify-content: space-between; font: 600 11px var(--mono); letter-spacing: .08em; text-transform: uppercase; color: var(--ambar); }
.np-cambios p button { font: 600 12px var(--ui); letter-spacing: 0; text-transform: none; color: var(--teal); background: none; }
.np-cambios ul { margin-top: 6px; display: grid; gap: 4px; }
.np-cambios li { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 6px; font-size: 12.5px; }
.np-cambios li b { font-weight: 650; }
.np-cambios li s { color: var(--media); max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-cambios li .bi { font-size: 11px; color: var(--ambar); }
.np-cambios li > span { font-weight: 600; color: var(--cred); max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-mod { margin-left: 4px; padding: 0 7px; border-radius: 999px; background: var(--ambarv); color: var(--ambar); font: 600 11px var(--ui); }
.np-nota { align-self: end; padding: 10px 12px; border-radius: 12px; background: var(--niebla); font-size: 12.5px; color: var(--texto); }
.np-hay { display: inline-flex; align-items: center; gap: 7px; color: var(--ambar); font-weight: 600; }
.np-hay > i { width: 7px; height: 7px; border-radius: 50%; background: var(--ambar); }

/* Surtir: modal mediano con antes → después */
.np.np-medio { width: min(900px, 100%); height: min(640px, calc(100vh - 32px)); grid-template-columns: 330px minmax(0, 1fr); }
.np-antes { position: relative; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 8px; text-align: center; }
.np-antes > div { padding: 10px 6px; border-radius: 12px; background: rgba(255, 255, 255, .07); border: 1px solid rgba(166, 239, 226, .14); display: grid; justify-items: center; gap: 4px; }
.np-antes small { font: 600 10px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: rgba(166, 239, 226, .8); }
.np-antes b { font: 800 34px/.95 var(--disp); color: #fff; }
.np-antes .e-agotado b { color: #ff9aab; } .np-antes .e-bajo b { color: #ffcf7a; }
.np-antes .iv-estado { background: #fff; font-size: 11.5px; }
.np-antes-mas { display: grid; justify-items: center; color: rgba(166, 239, 226, .5); transition: color .2s; }
.np-antes-mas.on { color: var(--foil); }
.np-antes-mas b { font: 700 14px var(--ui); color: var(--foil); min-height: 18px; }
.np-doble { position: relative; display: block; margin-top: 12px; height: 8px; border-radius: 4px; background: rgba(255, 255, 255, .14); }
.np-doble s { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 4px; transition: width .35s var(--e); }
.np-doble s.nuevo { background: rgba(166, 239, 226, .55); }
.np-doble s.e-ok { background: #7fe3b8; } .np-doble s.e-bajo { background: #ffcf7a; } .np-doble s.e-agotado { background: #ff9aab; }
.np-doble u { position: absolute; top: -3px; bottom: -3px; width: 2px; margin-left: -1px; background: #fff; }
.np-doble-pie { position: relative; display: flex; justify-content: space-between; gap: 8px; margin-top: 6px; font-size: 12px; color: rgba(234, 250, 246, .7); }
.np-doble-pie .bien { color: #7fe3b8; font-weight: 600; } .np-doble-pie .mal { color: #ffcf7a; font-weight: 600; }
.np-paso-xl { height: 64px; grid-template-columns: 64px minmax(0, 1fr) 64px; }
.np-paso-xl input { font-size: 32px; }
.np-paso-xl button { font-size: 24px; }
.np-paso.mal { border-color: #e9919f; box-shadow: 0 0 0 3px rgba(200, 16, 46, .08); }
.np-margen .np-sug { border-color: #f0cf96; color: var(--ambar); display: inline-flex; align-items: center; gap: 5px; }
.np-opc { font: 500 12px var(--ui); color: var(--media); font-style: normal; }
.np-chips button > .bi { font-size: 13px; color: var(--media); }
.np-chips button[aria-pressed=true] > .bi { color: var(--foil); }
.np-atajo { width: 100%; margin: 6px 0 14px; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; border: 1px dashed #c3d2ce; background: var(--papel); text-align: left; color: var(--texto); }
.np-atajo:hover { border-color: var(--teal); background: #f6fbfa; }
.np-atajo > .bi:first-child { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: var(--niebla); color: var(--cred); }
.np-atajo span { flex: 1; display: grid; }
.np-atajo b { font-weight: 600; color: var(--tinta); font-size: 13.5px; }
.np-atajo small { font-size: 12.5px; color: var(--media); }

/* Surtir lote: modal ancho con tarima y renglones */
.np.np-ancho { width: min(1180px, 100%); height: min(760px, calc(100vh - 32px)); grid-template-columns: 340px minmax(0, 1fr); }
.np-tarima { position: relative; flex: 1; min-height: 220px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-auto-rows: 92px; align-content: end; gap: 0 4px; padding: 10px 0 22px; }
.np-tarima-h { position: relative; display: block; perspective: 600px; animation: npCae .35s var(--e) backwards; }
.np-tarima-h .iv-caja.mini { transform: scale(.36) rotateX(-14deg) rotateY(-30deg); }
.np-tarima-h b { position: absolute; right: 2px; top: 4px; z-index: 2; padding: 1px 7px; border-radius: 999px; background: var(--foil); color: var(--cred2); font: 700 12px var(--ui); }
.np-tarima-h.ok b { background: #7fe3b8; } .np-tarima-h.mal b { background: #ff9aab; }
@keyframes npCae { from { opacity: 0; translate: 0 -24px; } }
.np-tarima-base { position: absolute; left: 4px; right: 4px; bottom: 6px; height: 14px; border-radius: 3px; background: repeating-linear-gradient(90deg, #8a6a43 0 28%, transparent 28% 36%), linear-gradient(#a3825a, #7c5d3a); opacity: .85; box-shadow: 0 10px 18px -6px rgba(0, 0, 0, .6); }
.np-tarima-vacia { position: absolute; inset: 0 10px 30px; display: grid; place-items: center; text-align: center; font-size: 13.5px; color: rgba(234, 250, 246, .6); }
.np-tarima-mas { position: absolute; left: 0; right: 0; bottom: -2px; text-align: center; font-size: 12px; color: rgba(234, 250, 246, .7); }
.np-totales { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; margin-top: 8px; }
.np-totales div { padding: 8px 10px; border-radius: 10px; background: rgba(255, 255, 255, .07); border: 1px solid rgba(166, 239, 226, .14); min-width: 0; }
.np-totales dt { font: 600 10px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: rgba(166, 239, 226, .8); }
.np-totales dd { margin-top: 2px; font: 750 20px var(--disp); color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-envio { position: relative; margin-top: 10px; display: grid; gap: 6px; font-size: 12.5px; color: var(--foil); }
.np-envio i { display: block; height: 6px; border-radius: 3px; background: rgba(255, 255, 255, .14); overflow: hidden; }
.np-envio s { display: block; height: 100%; background: #7fe3b8; transition: width .3s var(--e); }
.np-busca { position: relative; }
.np-kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); white-space: nowrap; }
.np-res { position: absolute; z-index: 5; left: 0; right: 0; top: calc(100% + 6px); max-height: 300px; overflow-y: auto; padding: 4px; border-radius: 12px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 20px 40px -18px rgba(11, 30, 28, .45); animation: mtPop .18s var(--e); }
.np-res button { width: 100%; display: flex; align-items: center; gap: 10px; padding: 7px 8px; border-radius: 9px; background: none; text-align: left; }
.np-res button.act { background: var(--velo); }
.np-res .iv-pk { width: 34px; height: 34px; border-radius: 9px; }
.np-res .iv-pk b { font-size: 12px; }
.np-res-n { flex: 1; min-width: 0; display: grid; }
.np-res-n b { font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-res-n small { font-size: 11.5px; color: var(--media); }
.np-res-s { font-size: 12px; color: var(--c); font-weight: 600; white-space: nowrap; }
.np-res-ya { padding: 1px 7px; border-radius: 999px; background: var(--velo); color: var(--cred); font: 700 11px var(--ui); }
.np-res p { padding: 12px; text-align: center; font-size: 13px; color: var(--media); }
.np-reponer { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 10px; margin-top: 10px; padding: 10px 12px; border-radius: 12px; background: var(--ambarv); color: var(--ambar); font-size: 13px; font-weight: 550; }
.np-reponer button { margin-left: auto; height: 32px; padding: 0 12px; border-radius: 9px; display: inline-flex; align-items: center; gap: 6px; background: var(--ambar); color: #fff; font: 600 12.5px var(--ui); }
.np-filas { margin-top: 12px; display: grid; gap: 8px; }
.np-fila { display: grid; grid-template-columns: 42px minmax(0, 1fr) 150px 110px 34px; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 12px; border: 1px solid var(--linea); background: var(--papel); transition: background .2s, border-color .2s; animation: mtFila .25s var(--e) backwards; }
.np-fila.nuevo { animation: npNuevo .9s ease-out; }
@keyframes npNuevo { 0% { box-shadow: 0 0 0 0 rgba(14, 124, 114, .45); } 100% { box-shadow: 0 0 0 10px rgba(14, 124, 114, 0); } }
.np-fila.en-enviando { background: #f6fbfa; border-color: #b9dcd5; }
.np-fila.en-ok { background: var(--verdev); border-color: #a9dcc3; }
.np-fila.en-error { background: var(--rosav); border-color: #f0b3bd; }
.np-fila-ic { position: relative; font-size: 18px; color: var(--cred); }
.en-ok .np-fila-ic { color: var(--verde); } .en-error .np-fila-ic { color: var(--rosa); }
.np-girando { display: inline-block; animation: npGira .8s linear infinite; }
.np-fila-n { min-width: 0; display: grid; gap: 2px; }
.np-fila-n > b { font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.np-fila-n small { font-size: 12px; color: var(--media); }
.np-fila-n small b { color: var(--cred); }
.np-fila-n small.mal { color: var(--rosa); font-weight: 550; }
.np-fila-n small.sug { color: var(--ambar); }
.np-doble.claro { margin-top: 4px; height: 5px; max-width: 240px; background: var(--linea2); }
.np-doble.claro s.nuevo { background: rgba(14, 124, 114, .3); }
.np-doble.claro s.e-ok { background: var(--verde); } .np-doble.claro s.e-bajo { background: #d99a2b; } .np-doble.claro s.e-agotado { background: var(--rosa); }
.np-paso-s { height: 38px; grid-template-columns: 34px minmax(0, 1fr) 34px; }
.np-paso-s input { font-size: 16px; }
.np-paso button:disabled { opacity: .45; cursor: not-allowed; }
.np-lote { min-height: 38px; padding: 0 10px; }
.np-lote input { padding: 6px 0; font-size: 13px; }
.np-quitar { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; background: none; color: var(--media); }
.np-quitar:hover:not(:disabled) { background: var(--rosav); color: var(--rosa); }
.np-filas-vacia { display: flex; align-items: center; gap: 14px; padding: 22px 18px; border-radius: 12px; border: 1px dashed #c3d2ce; color: var(--media); }
.np-filas-vacia .bi { font-size: 26px; color: var(--teal); }
.np-filas-vacia p { font-size: 13px; } .np-filas-vacia b { display: block; color: var(--tinta); font-weight: 600; }
@media (max-width: 1100px) { .np-fila { grid-template-columns: 42px minmax(0, 1fr) 130px 34px; } .np-lote { grid-column: 2 / 4; } }

/* ---------- Pantallas chicas: el escaparate pasa arriba y se compacta ---------- */
@media (max-width: 900px) {
    .np-velo { padding: 0; align-items: end; }
    .np { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto minmax(0, 1fr); height: 94vh; border-radius: 20px 20px 0 0; }
    .np-vitrina { display: grid; grid-template-columns: 150px minmax(0, 1fr); grid-template-rows: auto auto; gap: 8px 12px; padding: 12px 16px; }
    .np-et { grid-column: 1 / -1; }
    .np-escenario { min-height: 96px; }
    .np-giro { scale: .52; }
    .np-sombra { width: 130px; margin-left: -65px; top: calc(50% + 40px); }
    .np-partes { grid-template-columns: minmax(0, 1fr); gap: 4px; margin: 0; align-self: center; }
    .np-partes div { padding: 4px 8px; display: flex; gap: 8px; align-items: baseline; }
    .np-partes dd { margin: 0; font-size: 12.5px; }
    .np-codigo, .np-luz, .np-cambios { display: none; }
    .np.np-medio, .np.np-ancho { grid-template-columns: minmax(0, 1fr); height: 94vh; }
    .np-tarima, .np-envio { display: none; } .np-totales { grid-column: 1 / -1; margin: 0; } .np-totales dd { font-size: 16px; }
    .np-fila { grid-template-columns: 38px minmax(0, 1fr) 34px; } .np-fila .np-paso-s { grid-column: 2; } .np-lote { grid-column: 2; } .np-fila .np-quitar { grid-column: 3; grid-row: 1; }
    .np-doble, .np-doble-pie { display: none; } .np-antes { grid-column: 1 / -1; } .np-antes b { font-size: 24px; }
    .np-exis { grid-column: 1 / -1; padding: 8px 10px; } .np-exis-btn, .np-exis .iv-barra { display: none; } .np-exis-n > b { font-size: 26px; }
    .np-esc-corto { min-height: 96px; }
    .np-cab { padding: 14px 16px 10px; } .np-cab h2 { font-size: 22px; }
    .np-cuerpo { padding: 4px 16px 14px; }
    .np-pie { padding: 12px 16px; } .np-avance > span:last-child, .np-prim kbd { display: none; }
}
@media (max-width: 480px) { .np-par { grid-template-columns: minmax(0, 1fr); } }
@media (prefers-reduced-motion: reduce) { .np-giro { animation: none; transform: rotateX(-14deg) rotateY(-30deg); } }
</style>
@endverbatim

<script>
    function formNuevoProducto() {
        const vacio = () => ({ codigo: '', categoria_id: '', nombre: '', precio_compra: '', precio_venta: '', stock_actual: '0', stock_minimo: '5', descripcion: '' });

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
            buscarCat: '',
            intento: false,
            enviando: false,

            // ----- Vista previa de la caja -----
            get previa() {
                const stock = Number(this.f.stock_actual) || 0, minimo = Number(this.f.stock_minimo) || 0;
                return {
                    nombre: (this.f.nombre || 'Nuevo medicamento').toUpperCase(),
                    codigo: this.f.codigo || '0000000000000',
                    categoria: this.nombreCategoria || 'Categoría',
                    descripcion: this.f.descripcion || '',
                    venta: Number(this.f.precio_venta) || 0,
                    disponible: stock, minimo,
                    estado: stock <= 0 ? 'agotado' : (stock <= minimo ? 'bajo' : 'ok'),
                };
            },
            get partesNombre() {
                if (!this.f.nombre.trim()) return { principal: '', dosis: '', forma: '' };
                return typeof this.partes === 'function' ? this.partes({ nombre: this.f.nombre.toUpperCase() }) : { principal: this.f.nombre, dosis: '', forma: '' };
            },
            get escalaPrevia() { return Math.max((Number(this.f.stock_minimo) || 0) * 3, Number(this.f.stock_actual) || 0, 1); },
            get nivelPrevio() { const s = Number(this.f.stock_actual) || 0; return s <= 0 ? 0 : Math.max(2, Math.min(100, s / this.escalaPrevia * 100)); },
            get minimoPrevio() { return Math.min(100, (Number(this.f.stock_minimo) || 0) / this.escalaPrevia * 100); },

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
                this.buscarCat = '';
            },
            // Abre el gestor de categorías; lo escrito en el buscador llega como nombre sugerido.
            // El registro solo se oculta: al volver conserva todo lo capturado.
            abrirCategorias() {
                window.__categoriaDesde = { origen: 'crear', nombre: this.buscarCat.trim() };
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
                if (this.codigoRepetido) return 'Ya está registrado: ' + this.codigoRepetido.nombre;
                if (n === 0) return 'Con el lector, el código entra solo y pasa al nombre.';
                if (n < 13) return 'Faltan ' + (13 - n) + ' dígitos';
                if (this.digitoControl(this.f.codigo) !== Number(this.f.codigo[12])) return 'El último dígito no cuadra: revisa si hay un error de captura.';
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
                if (!f.nombre.trim()) falta.push('nombre');
                if (!f.categoria_id) falta.push('categoría');
                if (f.precio_compra === '') falta.push('compra');
                if (f.precio_venta === '') falta.push('venta');
                if (f.stock_actual === '') falta.push('piezas');
                if (f.stock_minimo === '') falta.push('mínimo');
                return falta;
            },
            get camposListos() { return 7 - this.faltantes.length; },

            enviar(e) {
                this.intento = true;
                if (this.enviando) { e.preventDefault(); return; }
                if (this.faltantes.length || this.codigoRepetido) {
                    e.preventDefault();
                    const mensaje = this.codigoRepetido ? 'Ese código ya está registrado.' : 'Completa: ' + this.faltantes.join(', ') + '.';
                    if (typeof window.notificar === 'function') window.notificar(mensaje, 'warning');
                    if (window.mtSacudir) mtSacudir('.np-form');
                    return;
                }
                try { sessionStorage.setItem('iv-nuevo', this.f.codigo); } catch (_) {}
                this.enviando = true;   // el formulario se envía normalmente al servidor
            },

            // ----- Cerrar -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
            swalAbierto() { return !!(window.Swal && Swal.isVisible && Swal.isVisible()); },
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
                        this.f = { ...vacio(), categoria_id: primera };
                        this.intento = false;
                    }, 250);
                };
                if (!this.hayDatos || !window.Swal) return cerrarYLimpiar();
                Swal.fire({
                    title: '¿Descartar el registro?',
                    text: 'Se perderán los datos que capturaste.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonText: 'Descartar', cancelButtonText: 'Seguir capturando',
                    confirmButtonColor: '#c8102e', cancelButtonColor: '#0b5a54',
                    customClass: { container: 'z-[10050]' }
                }).then(r => { if (r.isConfirmed) cerrarYLimpiar(); });
            },

            init() {
                if (this.$refs.gl && typeof mtGuilloche === 'function') this.$refs.gl.innerHTML = mtGuilloche('REGISTRO');
                // Al abrir, pone el cursor en el código (listo para el lector de código de barras)
                this.$watch('openCreateModal', abierto => {
                    if (abierto) setTimeout(() => this.$refs.codigo && this.$refs.codigo.focus(), 240);
                });
                // Un lector teclea los 13 dígitos de golpe: al completarse, pasa al nombre
                this.$watch('f.codigo', v => { if (v.length === 13 && document.activeElement === this.$refs.codigo) setTimeout(() => this.$refs.nombre.focus(), 60); });
                if (this.openCreateModal) setTimeout(() => this.$refs.codigo && this.$refs.codigo.focus(), 300);
            }
        };
    }
</script>
