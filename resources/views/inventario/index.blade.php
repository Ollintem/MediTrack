@extends('layouts.admin')

{{--
    MÓDULO DE INVENTARIO · MediTrack
    Cifra con filtros por estado, catálogo en lista o anaquel y visor 3D del medicamento elegido
    (caja que se gira arrastrando, con nombre, dosis, código de barras real y existencias en sus caras).
    Los modales de registrar, editar, surtir, surtir lote y categorías siguen en sus archivos.
--}}

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
            'actual'      => (int) ($p->stock_actual ?? $disponible),
            'reservado'   => (int) ($p->stock_reservado ?? 0),
            'minimo'      => $minimo,
            'estado'      => $disponible <= 0 ? 'agotado' : ($disponible <= $minimo ? 'bajo' : 'ok'),
            'buscar'      => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(implode(' ', [
                $p->codigo, $p->nombre, $p->descripcion, data_get($p, 'categoria.nombre'),
            ]))),
            'raw'         => $p->toArray(),
        ];
    })->values();

    $datosInventario = [
        'url'        => url('inventario'),
        'csrf'       => csrf_token(),
        'productos'  => $productosJs,
        'categorias' => $categorias->pluck('nombre')->filter()->unique()->sortBy(fn ($n) => mb_strtolower($n))->values(),
        'abrirCrear' => (bool) ($errors->any() || session('error')),
        'exito'      => session('success'),
        'error'      => session('error'),
    ];
@endphp

<div class="iv" x-data="moduloInventario()" @keydown.window="tecla($event)" @categorias-inventario.window="categoriasCambiaron($event.detail)">

    <!-- BÚSQUEDA Y ACCIONES -->
    <header class="iv-top">
        <label class="iv-cmd">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="iv-sr">Buscar medicamento</span>
            <input type="search" x-ref="buscar" x-model.debounce.120ms="q" autocomplete="off"
                   placeholder="Busca por nombre, sustancia o escanea el código" @keydown.enter.prevent="buscarExacto()">
            <kbd aria-hidden="true">/</kbd>
        </label>
        @if ($puedeCrear)
            <button type="button" class="iv-claro" @click="openLoteModal = true"><i class="bi bi-boxes"></i><span>Surtir lote</span></button>
            <button type="button" class="iv-dark" @click="openCreateModal = true"><span x-text="hayInsumos ? 'Registrar producto' : 'Registrar medicamento'">Registrar medicamento</span> <kbd>N</kbd></button>
        @endif
    </header>

    <!-- CIFRA E INDICADORES (también filtran) -->
    <section class="iv-cifra" aria-label="Resumen">
        <div class="iv-n">
            <b class="t" x-text="productos.length">0</b>
            <span><span x-text="hayInsumos ? 'productos' : 'medicamentos'">medicamentos</span><br>en <span class="t" x-text="categorias.length"></span> <span x-text="categorias.length === 1 ? 'categoría' : 'categorías'"></span></span>
        </div>
        <div class="iv-ind">
            <template x-for="op in opciones" :key="op.v">
                <button type="button" :class="'e-' + op.v" @click="filtrar(op.v)" :aria-pressed="(estadoFiltro === op.v).toString()">
                    <span x-text="op.t"></span>
                    <b class="t" x-text="conteo(op.v)"></b>
                    <i :style="'--p:' + (productos.length ? conteo(op.v) / productos.length * 100 : 0) + '%'"></i>
                </button>
            </template>
        </div>
        <dl class="iv-tot">
            <div><dt>Piezas disponibles</dt><dd class="t" x-text="numero(resumen.unidades)"></dd></div>
            <div><dt>Valor en almacén</dt><dd class="t" x-text="dinero(resumen.valor, 0)"></dd></div>
        </dl>
    </section>

    <section class="iv-escenario">

        <!-- VISOR 3D DEL MEDICAMENTO -->
        <aside class="iv-visor" aria-label="Medicamento elegido">
            <div class="iv-escena" x-ref="escena" tabindex="0" role="img" :class="{ 'abierta': abierta, 'cine': cine, 'entra': entrando }"
                 :aria-label="sel ? 'Caja de ' + titulo(sel.nombre) + '. Arrastra o usa las flechas para girarla.' : 'Sin medicamento elegido'"
                 @pointerdown="agarrar($event)" @dblclick="verCara('tres')" @keydown="teclaEscena($event)">
                <span class="iv-escena-gl" x-ref="gl" aria-hidden="true"></span>
                <span class="iv-luz" aria-hidden="true"></span>
                <span class="iv-letras" aria-hidden="true" x-text="sel ? partes(sel).principal : ''"></span>
                <span class="iv-polvo" x-ref="polvo" aria-hidden="true"></span>
                <span class="iv-piso" aria-hidden="true"></span>
                <span class="iv-escena-et" aria-hidden="true"><i class="bi bi-box"></i><span x-text="cine ? 'Presentación' : 'Vista 3D'"></span></span>

                <!-- Reflejo en el piso (la misma caja, volteada) -->
                <div class="iv-reflejo" x-show="sel" aria-hidden="true">
                    <div class="iv-escala"><div class="iv-caja" x-ref="reflejo" :class="sel ? 't-' + tipoDe(sel).clase : ''" :style="sel ? '--k:' + colorDe(sel) : ''" x-html="sel ? cajaHTML(sel, true) : ''"></div></div>
                </div>
                <span class="iv-escena-sombra" x-ref="sombra" aria-hidden="true"></span>
                <div class="iv-escena-caja" x-show="sel">
                    <div class="iv-escala"><div class="iv-caja" x-ref="caja" :class="sel ? 't-' + tipoDe(sel).clase : ''" :style="sel ? '--k:' + colorDe(sel) : ''" x-html="sel ? cajaHTML(sel, true) : ''"></div></div>
                </div>

                <div class="iv-escena-vacia" x-show="!sel" x-cloak>
                    <i class="bi bi-box-seam"></i>
                    <p x-text="productos.length ? 'Elige un medicamento para verlo' : 'Aún no hay medicamentos'"></p>
                </div>

                <!-- Fichas de la presentación -->
                <template x-if="sel">
                    <div class="iv-cine-info" aria-hidden="true">
                        <div class="iv-ci iv-ci-1" :class="paso >= 1 && 've'">
                            <small x-text="sel.categoria"></small>
                            <b x-text="partes(sel).principal"></b>
                            <span x-text="[partes(sel).dosis, partes(sel).forma].filter(Boolean).join(' · ')"></span>
                        </div>
                        <div class="iv-ci iv-ci-2" :class="['e-' + sel.estado, paso >= 2 ? 've' : '']">
                            <small>Existencias</small>
                            <b class="t" x-text="sel.disponible"></b>
                            <span x-text="textoEstado(sel) + ' · mínimo ' + sel.minimo"></span>
                        </div>
                        <div class="iv-ci iv-ci-3" :class="paso >= 3 && 've'">
                            <small>Precio de venta</small>
                            <b class="t" x-text="dinero(sel.venta)"></b>
                            <span x-text="sel.margen === null ? 'Sin costo registrado' : sel.margen + '% de margen'"></span>
                        </div>
                        <div class="iv-ci iv-ci-4" :class="paso >= 4 && 've'">
                            <span class="iv-ci-bar" x-html="barrasDe(sel.codigo)"></span>
                            <span class="mono" x-text="sel.codigo"></span>
                        </div>
                    </div>
                </template>
                <i class="iv-cine-banda arriba" aria-hidden="true"></i><i class="iv-cine-banda abajo" aria-hidden="true"></i>
                <div class="iv-cine-acc" x-show="cine" x-cloak @pointerdown.stop>
                    <button type="button" @click="presentar()" title="Repetir"><i class="bi bi-arrow-counterclockwise"></i><span>Repetir</span></button>
                    <button type="button" @click="salirCine()" title="Cerrar (Esc)"><i class="bi bi-x-lg"></i><span>Cerrar</span></button>
                </div>

                <div class="iv-caras" x-show="sel" @pointerdown.stop @dblclick.stop>
                    <button type="button" @click="verCara('frente')" :aria-pressed="(cara === 'frente').toString()">Frente</button>
                    <button type="button" @click="verCara('reverso')" :aria-pressed="(cara === 'reverso').toString()">Reverso</button>
                    <button type="button" @click="verCara('lado')" :aria-pressed="(cara === 'lado').toString()">Lado</button>
                    <button type="button" @click="verCara('tres')" :aria-pressed="(cara === 'tres').toString()" aria-label="Vista de tres cuartos">3/4</button>
                    <i aria-hidden="true"></i>
                    <button type="button" class="iv-cb" @click="abrirCaja()" :aria-pressed="abierta.toString()" :title="abierta ? 'Cerrar la caja' : 'Abrir la caja y ver el contenido'" :aria-label="abierta ? 'Cerrar la caja' : 'Abrir la caja'"><i class="bi" :class="abierta ? 'bi-box' : 'bi-capsule-pill'"></i></button>
                    <button type="button" class="iv-cb" @click="alternarAuto()" :aria-pressed="auto.toString()" :title="auto ? 'Detener el giro' : 'Girar sola'" :aria-label="auto ? 'Detener el giro' : 'Girar sola'"><i class="bi" :class="auto ? 'bi-pause-fill' : 'bi-arrow-repeat'"></i></button>
                    <button type="button" class="iv-cb" x-show="!cine" @click="presentar()" title="Presentación en pantalla completa" aria-label="Presentación en pantalla completa"><i class="bi bi-play-fill"></i></button>
                </div>
                <p class="iv-pista" x-show="sel && !cine" aria-hidden="true">Arrastra para girar</p>
            </div>

            <template x-if="sel">
                <div class="iv-ficha" :class="'e-' + sel.estado">
                    <p class="iv-ficha-cat"><i :style="'background:' + colorDe(sel)"></i><span x-text="sel.categoria"></span><em x-text="tipoDe(sel).insumo ? 'Insumo' : 'Medicamento'"></em></p>
                    <h3 x-text="titulo(sel.nombre)"></h3>
                    <button type="button" class="iv-ficha-cod" @click="copiar(sel.codigo)" :title="'Copiar ' + sel.codigo">
                        <i class="bi bi-upc"></i><span x-text="sel.codigo"></span><i class="bi bi-copy"></i>
                    </button>

                    <div class="iv-ficha-stock">
                        <div class="iv-fs-n">
                            <b class="t" x-text="sel.disponible"></b>
                            <span>piezas<br>disponibles</span>
                            <span class="iv-estado" :class="'e-' + sel.estado"><i></i><span x-text="textoEstado(sel)"></span></span>
                        </div>
                        <i class="iv-barra grande" :title="'Mínimo ' + sel.minimo"><s :style="'width:' + nivel(sel) + '%'"></s><u x-show="sel.minimo > 0" :style="'left:' + posMinimo(sel) + '%'"></u></i>
                        <p class="iv-fs-pie">
                            <span x-text="'Mínimo ' + sel.minimo"></span>
                            <span x-text="mensajeStock(sel)" :class="sel.estado !== 'ok' && 'fuerte'"></span>
                        </p>
                        <p class="iv-fs-res" x-show="sel.reservado > 0" x-text="sel.reservado + ' apartadas en tickets del punto de venta'"></p>
                    </div>

                    <dl class="iv-ficha-p">
                        <div><dt>Compra</dt><dd class="t" x-text="dinero(sel.compra)"></dd></div>
                        <div><dt>Venta</dt><dd class="t" x-text="dinero(sel.venta)"></dd></div>
                        <div><dt>Margen</dt><dd class="t" :class="sel.margen !== null && sel.margen < 15 && 'bajo'" x-text="sel.margen === null ? '—' : sel.margen + '%'"></dd></div>
                    </dl>

                    <p class="iv-ficha-desc" x-show="!!descripcionUtil(sel)" x-text="descripcionUtil(sel)"></p>

                    <div class="iv-ficha-acc">
                        @if ($puedeEditar)
                            <button type="button" :class="sel.estado === 'ok' ? 'iv-btn' : 'iv-btn iv-prim'" @click="abrirSurtir(sel.raw)"><i class="bi bi-box-arrow-in-down"></i>Surtir <kbd>S</kbd></button>
                            <button type="button" class="iv-btn" @click="abrirEditar(sel.raw)"><i class="bi bi-pencil"></i>Editar <kbd>E</kbd></button>
                        @endif
                        @if ($puedeEliminar)
                            <button type="button" class="iv-btn iv-ico-r" @click="eliminar(sel)" aria-label="Eliminar medicamento" title="Eliminar"><i class="bi bi-trash3"></i></button>
                        @endif
                    </div>
                </div>
            </template>
            @if ($puedeCrear)
                <div class="iv-ficha iv-ficha-vacia" x-show="!productos.length" x-cloak>
                    <p>Registra tu primer medicamento para empezar el catálogo.</p>
                    <button type="button" class="iv-btn iv-prim" @click="openCreateModal = true"><i class="bi bi-plus-lg"></i>Registrar medicamento</button>
                </div>
            @endif
        </aside>

        <!-- CATÁLOGO -->
        <section class="iv-lista" aria-labelledby="ivListaT">
            <div class="iv-lh">
                <h2 id="ivListaT">Catálogo <small class="t" x-text="filtrados.length === productos.length ? productos.length : filtrados.length + ' de ' + productos.length"></small></h2>
                <select x-model="categoriaFiltro" aria-label="Filtrar por categoría" class="iv-sel">
                    <option value="">Todas las categorías</option>
                    <template x-for="c in categorias" :key="c">
                        <option :value="c" x-text="c"></option>
                    </template>
                </select>
                <select x-model="orden" aria-label="Ordenar" class="iv-sel">
                    <option value="nombre">Nombre (A-Z)</option>
                    <option value="stock">Menos existencias</option>
                    <option value="venta">Precio de venta</option>
                    <option value="margen">Margen</option>
                </select>
                @if ($puedeCrear)
                    <button type="button" class="iv-ico" @click="openCategoriaModal = true" aria-label="Categorías" title="Categorías"><i class="bi bi-tags"></i></button>
                @endif
                <div class="iv-seg" role="group" aria-label="Vista">
                    <button type="button" @click="cambiarVista('lista')" :aria-pressed="(vista === 'lista').toString()"><i class="bi bi-list-ul"></i>Lista</button>
                    <button type="button" @click="cambiarVista('anaquel')" :aria-pressed="(vista === 'anaquel').toString()"><i class="bi bi-box"></i>Anaquel</button>
                </div>
            </div>

            <div class="iv-filtros" x-show="hayFiltros" x-cloak>
                <span x-show="estadoFiltro !== 'todos'" class="iv-chip" :class="'e-' + estadoFiltro"><i></i><span x-text="(opciones.find(o => o.v === estadoFiltro) || {}).t"></span></span>
                <span x-show="!!categoriaFiltro" class="iv-chip"><i class="bi bi-tag"></i><span x-text="categoriaFiltro"></span></span>
                <span x-show="q.trim() !== ''" class="iv-chip"><i class="bi bi-search"></i><span x-text="'«' + q.trim() + '»'"></span></span>
                <button type="button" @click="limpiarFiltros()">Quitar filtros</button>
                @if ($puedeCrear)
                    <button type="button" class="iv-surtir-lote" x-show="estadoFiltro === 'bajo' || estadoFiltro === 'agotado'" @click="openLoteModal = true"><i class="bi bi-boxes"></i>Reabastecer en lote</button>
                @endif
            </div>

            <!-- Lista -->
            <div class="iv-filas" x-show="vista === 'lista'">
                <template x-for="p in pagina" :key="p.id">
                    <div class="iv-fila" :class="[sel && sel.id === p.id ? 'sel' : '', p._saliendo ? 'mt-sale' : '']" :data-id="p.id">
                        <button type="button" class="iv-fila-main" @click="seleccionar(p)">
                            <span class="iv-pk" :class="tipoDe(p).insumo && 'ins'" :style="'--k:' + colorDe(p)"><b x-show="!tipoDe(p).insumo" x-text="iniciales(p)"></b><span x-show="tipoDe(p).insumo" x-html="tipoDe(p).insumo ? itemSVG(tipoDe(p).item) : ''"></span></span>
                            <span class="iv-fila-n">
                                <b x-text="partes(p).principal"></b>
                                <small><span x-show="!!partes(p).dosis" x-text="partes(p).dosis"></span><span x-text="partes(p).forma || p.codigo"></span></small>
                            </span>
                            <span class="iv-fila-cat" x-text="p.categoria"></span>
                            <span class="iv-exis" :class="'e-' + p.estado">
                                <span><b class="t" x-text="p.disponible"></b><small x-text="'mín ' + p.minimo"></small></span>
                                <i class="iv-barra"><s :style="'width:' + nivel(p) + '%'"></s><u x-show="p.minimo > 0" :style="'left:' + posMinimo(p) + '%'"></u></i>
                            </span>
                            <span class="iv-precio t" x-text="dinero(p.venta)"></span>
                            <span class="iv-estado" :class="'e-' + p.estado"><i></i><span x-text="textoEstado(p)"></span></span>
                        </button>
                        <span class="iv-fila-acc">
                            @if ($puedeEditar)
                                <button type="button" class="iv-ico" :class="p.estado !== 'ok' && 'iv-ico-v'" @click="seleccionar(p); abrirSurtir(p.raw)" :aria-label="'Surtir ' + p.nombre" title="Surtir"><i class="bi bi-box-arrow-in-down"></i></button>
                                <button type="button" class="iv-ico" @click="seleccionar(p); abrirEditar(p.raw)" :aria-label="'Editar ' + p.nombre" title="Editar"><i class="bi bi-pencil"></i></button>
                            @endif
                            @if ($puedeEliminar)
                                <button type="button" class="iv-ico iv-ico-r" @click="eliminar(p)" :aria-label="'Eliminar ' + p.nombre" title="Eliminar"><i class="bi bi-trash3"></i></button>
                            @endif
                        </span>
                    </div>
                </template>
            </div>

            <!-- Anaquel: cada medicamento como caja en su repisa -->
            <div class="iv-anaquel" x-show="vista === 'anaquel'" x-cloak>
                <template x-for="p in pagina" :key="'a' + p.id">
                    <button type="button" class="iv-hueco" :class="[sel && sel.id === p.id ? 'sel' : '', 'e-' + p.estado, p._saliendo ? 'mt-sale' : '']" @click="seleccionar(p)" :aria-label="titulo(p.nombre)">
                        <span class="iv-hueco-caja"><span class="iv-caja mini" :class="'t-' + tipoDe(p).clase" :style="'--k:' + colorDe(p)" x-html="cajaHTML(p)"></span></span>
                        <span class="iv-hueco-n"><b x-text="partes(p).principal"></b><small x-text="[partes(p).dosis, p.disponible + ' pzas'].filter(Boolean).join(' · ')"></small></span>
                        <span class="iv-hueco-e" :class="'e-' + p.estado" x-show="p.estado !== 'ok'" x-text="p.estado === 'agotado' ? 'Agotado' : 'Por surtir'"></span>
                    </button>
                </template>
            </div>

            <div class="iv-mas" x-show="filtrados.length > limite" x-cloak>
                <button type="button" @click="limite += 60" x-text="'Mostrar ' + Math.min(60, filtrados.length - limite) + ' más'"></button>
            </div>

            <div class="iv-sin" x-show="!filtrados.length" x-cloak>
                <i class="bi" :class="productos.length ? (estadoFiltro !== 'todos' ? 'bi-check2-circle' : 'bi-search') : 'bi-capsule'"></i>
                <p x-text="!productos.length ? 'Aún no hay medicamentos registrados' : (estadoFiltro === 'agotado' && !q && !categoriaFiltro ? 'No hay medicamentos agotados' : (estadoFiltro === 'bajo' && !q && !categoriaFiltro ? 'Nada por surtir' : 'Ningún medicamento coincide'))"></p>
                <button type="button" x-show="hayFiltros" @click="limpiarFiltros()">Ver todo el catálogo</button>
            </div>
        </section>
    </section>

    {{-- Modales --}}
    @include('inventario.modalAgregarProducto')
    @include('inventario.modalEditarProducto')
    @include('inventario.modalSurtirStock')
    @include('inventario.modalGestionarCategorias')
    @include('inventario.modalSurtirLote')
</div>

<script type="application/json" id="iv-datos">{!! json_encode($datosInventario, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@verbatim
<style>
.iv, .iv-escena {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
}
.iv {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.iv *, .iv-escena * { box-sizing: border-box; }
.iv :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.iv button, .iv-escena button { cursor: pointer; }
.iv .t, .iv-escena .t { font-variant-numeric: tabular-nums; }
.iv .mono, .iv-escena .mono { font-family: var(--mono); letter-spacing: .02em; }
.iv-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.iv kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); background: transparent; }
[x-cloak] { display: none !important; }

/* Estados (color fijo por significado) */
:is(.iv, .iv-escena) .e-ok { --c: var(--verde); --cv: var(--verdev); }
:is(.iv, .iv-escena) .e-bajo { --c: var(--ambar); --cv: var(--ambarv); }
:is(.iv, .iv-escena) .e-agotado { --c: var(--rosa); --cv: var(--rosav); }

/* ---------- Búsqueda ---------- */
.iv-top { display: flex; align-items: center; gap: 10px; }
.iv-cmd { flex: 1; min-width: 0; height: 50px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 12px 30px -22px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 16px; }
.iv-cmd > .bi { color: var(--cred); }
.iv-cmd input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 450 15px var(--ui); color: var(--tinta); }
.iv-cmd input::placeholder { color: var(--media); }
.iv-dark, .iv-claro { height: 44px; border-radius: 12px; font: 600 14px var(--ui); display: flex; align-items: center; gap: 9px; white-space: nowrap; }
.iv-dark { background: var(--tinta); color: #fff; padding: 0 12px 0 16px; }
.iv-dark kbd { border-color: rgba(255, 255, 255, .25); color: #cfe; }
.iv-claro { background: var(--papel); border: 1px solid var(--linea); color: var(--tinta); padding: 0 14px; }
.iv-claro .bi { color: var(--cred); }
.iv-claro:hover { border-color: #b9cbc6; }
@container (max-width: 759px) { .iv-cmd > kbd, .iv-dark kbd { display: none; } .iv-dark { padding: 0 14px; } .iv-claro span { display: none; } }
@container (max-width: 520px) { .iv-top { flex-wrap: wrap; } .iv-cmd { flex-basis: 100%; } .iv-dark { flex: 1; justify-content: center; } }

/* ---------- Cifra ---------- */
.iv-cifra { display: flex; align-items: flex-end; gap: 28px; flex-wrap: wrap; }
.iv-n { display: flex; align-items: flex-end; gap: 12px; }
.iv-n > b { font: 800 88px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); }
.iv-n > span { font-size: 15px; font-weight: 500; line-height: 1.25; padding-bottom: 2px; }
.iv-ind { flex: 1; min-width: 280px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 20px; }
.iv-ind button { display: grid; grid-template-columns: 1fr auto; align-items: end; padding: 6px 0 0; text-align: left; font-weight: 500; color: var(--texto); background: none; border-top: 1px solid var(--linea); }
.iv-ind button span { font-size: 14px; padding-bottom: 4px; }
.iv-ind button b { font: 750 26px/1 var(--disp); color: var(--c); }
.iv-ind button i { grid-column: 1 / -1; height: 4px; border-radius: 2px; background: var(--linea); margin-top: 8px; position: relative; overflow: hidden; }
.iv-ind button i:after { content: ""; position: absolute; inset: 0; width: var(--p); background: var(--c); border-radius: 2px; transition: width .5s var(--e); }
.iv-ind button[aria-pressed=true] { border-top: 2px solid var(--c); }
.iv-ind button[aria-pressed=true] span { color: var(--tinta); font-weight: 650; }
.iv-tot { display: flex; gap: 22px; padding-bottom: 2px; }
.iv-tot div { display: flex; flex-direction: column; }
.iv-tot dt { font-size: 12px; font-weight: 500; color: var(--media); }
.iv-tot dd { font: 700 20px/1.1 var(--disp); color: var(--tinta); }
@container (max-width: 759px) { .iv-n > b { font-size: 68px; } .iv-ind { min-width: 100%; } }

/* ---------- Escenario ---------- */
.iv-escenario { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
@container (min-width: 1080px) {
    .iv-escenario { grid-template-columns: minmax(0, 1fr) 372px; }
    .iv-visor { grid-column: 2; grid-row: 1; position: sticky; top: 16px; }
    .iv-lista { grid-column: 1; grid-row: 1; }
}

/* ---------- Visor 3D: estudio de producto ---------- */
.iv-visor { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; box-shadow: 0 18px 40px -32px rgba(11, 30, 28, .5); }
.iv-escena { --z: 1; --suelo: calc((100% - 60px) / 2 + 84px * var(--z));
    position: relative; height: 336px; overflow: hidden; touch-action: none; cursor: grab; user-select: none; outline-offset: -3px; font-family: var(--ui);
    background: radial-gradient(85% 70% at 50% 28%, #134a45 0%, #0a2826 46%, #040d0c 100%); }
.iv-escena.agarrando { cursor: grabbing; }
.iv-escena-gl { position: absolute; inset: 0; opacity: .14; pointer-events: none; }
.iv-escena-gl svg { width: 100%; height: 100%; }
.iv-luz { position: absolute; left: 50%; top: -30px; width: 440px; height: calc(var(--suelo) + 60px); margin-left: -220px; pointer-events: none;
    background: linear-gradient(180deg, rgba(196, 255, 244, .34), rgba(166, 239, 226, .05) 75%, transparent);
    clip-path: polygon(41% 0, 59% 0, 100% 100%, 0 100%); filter: blur(16px); transition: opacity .6s var(--e); }
.iv-letras { position: absolute; left: 0; right: 0; top: calc(var(--suelo) - 190px * var(--z)); text-align: center; white-space: nowrap; pointer-events: none;
    font: 800 calc(76px * var(--z))/1 var(--disp); font-stretch: 75%; letter-spacing: -.02em; text-transform: uppercase;
    color: transparent; -webkit-text-stroke: 1px rgba(166, 239, 226, .2); transition: opacity .5s var(--e), translate .7s var(--e); }
.iv-piso { position: absolute; left: 0; right: 0; top: var(--suelo); bottom: 0; pointer-events: none;
    background: linear-gradient(180deg, rgba(166, 239, 226, .07), rgba(0, 0, 0, .55) 85%); border-top: 1px solid rgba(166, 239, 226, .12); }
.iv-escena-et { position: absolute; top: 14px; left: 16px; z-index: 5; display: inline-flex; align-items: center; gap: 6px; font: 600 11px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--foil); }
.iv-escena-caja, .iv-reflejo { position: absolute; inset: 0 0 60px; display: grid; place-items: center; perspective: 1000px; perspective-origin: 50% 38%; pointer-events: none; }
.iv-escena-caja { z-index: 2; }
.iv-escala { transform-style: preserve-3d; scale: var(--z); }
.iv-reflejo { z-index: 1; opacity: .3; transform-origin: 50% calc(50% + 84px * var(--z)); transform: scaleY(-1);
    -webkit-mask-image: linear-gradient(180deg, transparent 38%, #000 78%); mask-image: linear-gradient(180deg, transparent 38%, #000 78%); }
.iv-escena-sombra { position: absolute; z-index: 1; left: 50%; top: calc(var(--suelo) - 14px * var(--z)); width: calc(250px * var(--z)); height: calc(28px * var(--z)); margin-left: calc(-125px * var(--z)); border-radius: 50%; pointer-events: none;
    background: radial-gradient(closest-side, rgba(0, 0, 0, .6), transparent); filter: blur(3px); }
.iv-polvo { position: absolute; inset: 0; pointer-events: none; display: none; }
.iv-polvo i { position: absolute; width: 3px; height: 3px; border-radius: 50%; background: rgba(196, 255, 244, .55); filter: blur(.4px); animation: ivPolvo var(--t, 9s) linear infinite; animation-delay: var(--r, 0s); }
@keyframes ivPolvo { 0% { translate: 0 0; opacity: 0; } 15% { opacity: 1; } 85% { opacity: .8; } 100% { translate: var(--x, 20px) -160px; opacity: 0; } }
.iv-escena-vacia { position: absolute; inset: 0; z-index: 3; display: grid; place-content: center; justify-items: center; gap: 10px; color: rgba(234, 250, 246, .8); font-size: 14px; }
.iv-escena-vacia .bi { font-size: 46px; color: var(--foil); opacity: .7; }
.iv-caras { position: absolute; left: 12px; right: 12px; bottom: 12px; z-index: 6; display: flex; align-items: center; gap: 3px; padding: 3px; border-radius: 11px; background: rgba(4, 22, 20, .62); backdrop-filter: blur(8px); cursor: default; }
.iv-caras button { flex: 1; min-width: 0; height: 30px; border-radius: 8px; font: 600 12.5px var(--ui); color: rgba(234, 250, 246, .78); background: none; transition: background .15s, color .15s; }
.iv-caras button:hover { color: #fff; background: rgba(255, 255, 255, .08); }
.iv-caras button[aria-pressed=true] { background: #fff; color: var(--cred2); }
.iv-caras > i { flex: 0 0 1px; height: 18px; margin: 0 2px; background: rgba(255, 255, 255, .16); }
.iv-caras .iv-cb { flex: 0 0 32px; font-size: 15px; }
.iv-caras .iv-cb[aria-pressed=true] { background: rgba(166, 239, 226, .18); color: var(--foil); }
.iv-pista { position: absolute; top: 13px; right: 16px; z-index: 5; font-size: 11.5px; color: rgba(234, 250, 246, .55); pointer-events: none; }

/* Entrada al elegir: la luz se enciende y el nombre aparece detrás */
.iv-escena.entra .iv-luz { opacity: .25; transition: none; }
.iv-escena.entra .iv-letras { opacity: 0; translate: 0 14px; transition: none; }

/* Presentación en pantalla completa */
.iv-escena.cine { --z: 2.1; position: fixed; inset: 0; z-index: 10040; height: auto; border-radius: 0; cursor: grab;
    background: radial-gradient(70% 70% at 50% 30%, #134a45 0%, #0a2826 44%, #030a09 100%); }
.iv-escena.cine .iv-polvo { display: block; }
.iv-escena.cine .iv-caras { left: 50%; right: auto; width: min(520px, calc(100% - 32px)); translate: -50% 0; bottom: calc(7vh + 14px); }
.iv-escena.cine .iv-escena-et { top: calc(7vh + 16px); left: 24px; }
.iv-escena.cine .iv-letras { -webkit-text-stroke: 1.5px rgba(166, 239, 226, .16); }
.iv-escena.oscuro .iv-luz { opacity: 0; transition: none; }
.iv-escena.oscuro .iv-letras { opacity: 0; transition: none; }
.iv-cine-banda { position: absolute; left: 0; right: 0; height: 0; z-index: 7; background: #000; transition: height .45s var(--e); pointer-events: none; }
.iv-cine-banda.arriba { top: 0; } .iv-cine-banda.abajo { bottom: 0; }
.cine .iv-cine-banda { height: 7vh; }
.iv-cine-acc { position: absolute; z-index: 8; top: calc(7vh + 10px); right: 18px; display: flex; gap: 6px; }
.iv-cine-acc button { height: 36px; padding: 0 12px; border-radius: 10px; display: inline-flex; align-items: center; gap: 7px; background: rgba(255, 255, 255, .1); color: #eafaf6; font: 600 13px var(--ui); backdrop-filter: blur(6px); }
.iv-cine-acc button:hover { background: rgba(255, 255, 255, .18); }
.iv-cine-info { display: none; }
.cine .iv-cine-info { display: block; position: absolute; inset: 0; z-index: 4; pointer-events: none; }
.iv-ci { position: absolute; width: min(270px, 26vw); padding: 14px 16px; border-radius: 14px; color: #eafaf6;
    background: rgba(8, 36, 34, .55); border: 1px solid rgba(166, 239, 226, .16); backdrop-filter: blur(10px);
    opacity: 0; translate: -18px 0; transition: opacity .45s var(--e), translate .55s var(--e); }
.iv-ci.ve { opacity: 1; translate: 0 0; }
.iv-ci small { display: block; font: 600 10.5px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--foil); }
.iv-ci b { display: block; margin-top: 4px; font: 750 30px/1.05 var(--disp); color: #fff; }
.iv-ci span { display: block; margin-top: 4px; font-size: 13.5px; color: rgba(234, 250, 246, .78); }
.iv-ci-1 { left: 5vw; top: 20vh; }
.iv-ci-2 { left: 5vw; bottom: 22vh; }
.iv-ci-2 b { color: var(--c); filter: brightness(1.9) saturate(.8); }
.iv-ci-2.e-ok b { color: #7fe3b8; filter: none; }
.iv-ci-3 { right: 5vw; top: 20vh; translate: 18px 0; }
.iv-ci-4 { right: 5vw; bottom: 22vh; translate: 18px 0; display: grid; justify-items: center; gap: 4px; background: #fff; color: var(--tinta); }
.iv-ci-3.ve, .iv-ci-4.ve { translate: 0 0; }
.iv-ci-4 .iv-ci-bar svg { display: block; width: 210px; height: 56px; }
.iv-ci-4 .mono { margin: 0; color: var(--tinta); font-size: 12px; }
@media (max-width: 760px) {
    .iv-escena.cine { --z: 1.25; }
    .iv-ci { width: 44vw; padding: 10px 12px; } .iv-ci b { font-size: 20px; } .iv-ci span { font-size: 12px; }
    .iv-ci-1 { top: calc(7vh + 54px); } .iv-ci-3 { top: calc(7vh + 54px); } .iv-ci-2, .iv-ci-4 { bottom: calc(7vh + 64px); }
    .iv-ci-4 .iv-ci-bar svg { width: 130px; height: 40px; }
}
html.iv-sin-scroll { overflow: hidden; }

/* La caja 3D (.iv-caja y sus caras) vive en el layout: la comparten Inventario y el Dashboard */
/* Encabezado de los modales del módulo y caja de vista previa */
.iv-modal-cab { background: radial-gradient(120% 170% at 88% 0%, #13756d 0%, #0b5a54 46%, #073f3b 100%); }
.iv-prev { position: relative; flex: 0 0 112px; height: 74px; margin: -4px 0; perspective: 600px; }
.iv-prev .iv-caja.mini { transform: scale(.4) rotateX(-14deg) rotateY(-30deg); filter: drop-shadow(0 10px 12px rgba(0, 0, 0, .3)); }
@media (max-width: 520px) { .iv-prev { flex-basis: 84px; } .iv-prev .iv-caja.mini { transform: scale(.32) rotateX(-14deg) rotateY(-30deg); } }

/* ---------- Ficha ---------- */
.iv-ficha { padding: 16px 18px 18px; display: grid; gap: 12px; }
.iv-ficha-cat { display: flex; align-items: center; gap: 7px; font: 600 11px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--media); }
.iv-ficha-cat i { width: 9px; height: 9px; border-radius: 3px; }
.iv-ficha h3 { margin-top: -6px; font: 750 22px/1.1 var(--disp); color: var(--tinta); }
.iv-ficha-cod { justify-self: start; display: inline-flex; align-items: center; gap: 7px; margin-top: -4px; height: 26px; padding: 0 9px; border-radius: 8px; background: var(--niebla); font: 500 12.5px var(--mono); color: var(--texto); }
.iv-ficha-cod .bi-copy { font-size: 11px; color: var(--media); }
.iv-ficha-cod:hover { background: var(--velo); }
.iv-ficha-stock { padding: 12px 14px; border-radius: 14px; background: var(--cv); }
.iv-fs-n { display: flex; align-items: flex-end; gap: 9px; }
.iv-fs-n > b { font: 800 40px/.85 var(--disp); color: var(--c); }
.iv-fs-n > span:not(.iv-estado) { font-size: 12px; line-height: 1.15; color: var(--texto); }
.iv-fs-n .iv-estado { margin-left: auto; background: var(--papel); }
.iv-fs-pie { display: flex; justify-content: space-between; gap: 8px; margin-top: 6px; font-size: 12px; color: var(--media); }
.iv-fs-pie .fuerte { color: var(--c); font-weight: 650; }
.iv-fs-res { margin-top: 4px; font-size: 12px; color: var(--media); }
.iv-ficha-p { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border: 1px solid var(--linea2); border-radius: 12px; }
.iv-ficha-p div { padding: 8px 12px; }
.iv-ficha-p div + div { border-left: 1px solid var(--linea2); }
.iv-ficha-p dt { font-size: 11.5px; color: var(--media); }
.iv-ficha-p dd { font: 700 16px var(--disp); color: var(--tinta); }
.iv-ficha-p dd.bajo { color: var(--ambar); }
.iv-ficha-desc { font-size: 13px; color: var(--texto); }
.iv-ficha-acc { display: flex; gap: 8px; }
.iv-btn { height: 40px; padding: 0 14px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--linea); background: var(--papel); font: 600 13.5px var(--ui); color: var(--tinta); transition: background .15s, border-color .15s; }
.iv-btn:hover { border-color: #b9cbc6; background: #f8fbfa; }
.iv-btn kbd { padding: 0 5px; font-size: 10px; }
.iv-ficha-acc .iv-btn:not(.iv-ico-r) { flex: 1; }
.iv-prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.iv-prim:hover { background: var(--cred2); border-color: var(--cred2); }
.iv-prim kbd { border-color: rgba(255, 255, 255, .3); color: var(--foil); }
.iv-btn.iv-ico-r { width: 40px; padding: 0; color: var(--rosa); }
.iv-btn.iv-ico-r:hover { background: var(--rosav); border-color: #f3c3cc; }
.iv-ficha-vacia { justify-items: start; }
.iv-ficha-vacia p { font-size: 13.5px; }

/* ---------- Catálogo ---------- */
.iv-lista { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; }
.iv-lh { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 14px 16px 14px 18px; border-bottom: 1px solid var(--linea2); }
.iv-lh h2 { margin-right: auto; font: 750 20px var(--disp); color: var(--tinta); }
.iv-lh h2 small { font: 500 13px var(--ui); color: var(--media); margin-left: 6px; }
.iv-sel { height: 36px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 550 13px var(--ui); color: var(--texto); }
.iv-ico { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; border: 1px solid var(--linea); background: var(--papel); color: var(--texto); transition: background .15s, border-color .15s, color .15s; }
.iv-ico:hover { border-color: #b9cbc6; color: var(--tinta); }
.iv-ico-v { background: var(--cred); border-color: var(--cred); color: #fff; }
.iv-ico-v:hover { background: var(--cred2); color: #fff; }
.iv-ico.iv-ico-r:hover { color: var(--rosa); background: var(--rosav); border-color: #f3c3cc; }
.iv-seg { display: flex; gap: 2px; padding: 3px; border-radius: 11px; background: var(--niebla); }
.iv-seg button { height: 30px; padding: 0 11px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; font: 600 12.5px var(--ui); color: var(--media); background: none; }
.iv-seg button[aria-pressed=true] { background: var(--papel); color: var(--tinta); box-shadow: 0 1px 3px rgba(11, 30, 28, .14); }

.iv-filtros { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 10px 18px; border-bottom: 1px solid var(--linea2); background: #f8fbfa; }
.iv-chip { display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 10px; border-radius: 999px; background: var(--papel); border: 1px solid var(--linea); font-size: 12.5px; font-weight: 550; color: var(--tinta); }
.iv-chip > i:not(.bi) { width: 7px; height: 7px; border-radius: 50%; background: var(--c); }
.iv-chip .bi { font-size: 11px; color: var(--media); }
.iv-filtros > button { height: 26px; padding: 0 8px; font-size: 12.5px; font-weight: 600; color: var(--teal); background: none; }
.iv-filtros .iv-surtir-lote { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; padding: 0 12px; border-radius: 999px; background: var(--cred); color: #fff; }

.iv-fila { display: flex; align-items: center; border-bottom: 1px solid var(--linea2); }
.iv-fila:last-child { border-bottom: 0; }
.iv-fila.sel { background: linear-gradient(90deg, rgba(14, 124, 114, .08), transparent 60%); box-shadow: inset 3px 0 0 var(--teal); }
.iv-fila-main { flex: 1; min-width: 0; display: grid; grid-template-columns: 42px minmax(0, 1.7fr) minmax(0, .8fr) minmax(110px, 1fr) 76px 112px; align-items: center; gap: 14px; padding: 11px 6px 11px 18px; text-align: left; background: none; }
.iv-fila-main:hover { background: rgba(11, 90, 84, .03); }
.iv-pk { position: relative; width: 42px; height: 42px; border-radius: 11px; overflow: hidden; display: grid; place-items: start; padding: 9px 0 0 7px; background: #fbfdfc; box-shadow: inset 0 0 0 1px var(--linea); }
.iv-pk:before { content: ""; position: absolute; right: -9px; bottom: -11px; width: 28px; height: 22px; border-radius: 50%; background: var(--k); }
.iv-pk:after { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: var(--k); }
.iv-pk b { position: relative; font: 750 14px/1 var(--disp); color: var(--tinta); }
.iv-fila-n { min-width: 0; display: flex; flex-direction: column; }
.iv-fila-n b { font-weight: 650; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.iv-fila-n small { display: flex; gap: 8px; font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; }
.iv-fila-n small span:first-child { color: var(--texto); font-weight: 550; }
.iv-fila-n small .mono { font-size: 11.5px; }
.iv-fila-cat { font-size: 13px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.iv-exis { display: grid; gap: 4px; }
.iv-exis > span { display: flex; align-items: baseline; gap: 6px; }
.iv-exis b { font: 700 16px/1 var(--disp); color: var(--c); }
.iv-exis.e-ok b { color: var(--tinta); }
.iv-exis small { font-size: 11.5px; color: var(--media); }
.iv-barra { position: relative; display: block; height: 5px; border-radius: 3px; background: var(--linea2); }
.iv-barra s { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 3px; background: var(--c, var(--verde)); transition: width .45s var(--e); }
.iv-barra u { position: absolute; top: -3px; bottom: -3px; width: 2px; margin-left: -1px; border-radius: 1px; background: var(--tinta); opacity: .55; }
.iv-barra.grande { height: 8px; margin-top: 10px; background: rgba(255, 255, 255, .8); }
.iv-barra.grande s { border-radius: 4px; }
.iv-precio { font: 650 14px var(--ui); color: var(--tinta); text-align: right; }
.iv-estado { justify-self: start; display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: var(--cv); color: var(--c); font-size: 12.5px; font-weight: 600; white-space: nowrap; }
.iv-estado > i { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.iv-fila-acc { display: flex; gap: 6px; padding: 0 14px 0 4px; }
.iv-fila-acc .iv-ico { width: 34px; height: 34px; }
@container (max-width: 1240px) { .iv-fila-main { grid-template-columns: 42px minmax(0, 1.7fr) minmax(110px, 1fr) 76px 112px; } .iv-fila-cat { display: none; } }
@container (max-width: 860px) { .iv-fila-main { grid-template-columns: 42px minmax(0, 1fr) 100px; } .iv-precio, .iv-estado:not(.iv-fs-n .iv-estado) { display: none; } .iv-fila-acc .iv-ico:not(.iv-ico-v) { display: none; } }

/* Anaquel */
.iv-anaquel { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); padding: 6px 10px 14px; }
.iv-hueco { position: relative; display: grid; gap: 6px; padding: 14px 10px 12px; text-align: center; background: none; border-radius: 14px; transition: background .15s; }
.iv-hueco:before { content: ""; position: absolute; left: 6px; right: 6px; top: 108px; height: 8px; border-radius: 2px;
    background: linear-gradient(180deg, #dfe8e5, #c9d6d2); box-shadow: 0 6px 10px -6px rgba(11, 30, 28, .35); }
.iv-hueco:hover { background: #f6faf9; }
.iv-hueco.sel { background: var(--velo); }
.iv-hueco-caja { margin-top: -6px; z-index: 1; }
.iv-hueco-n { position: relative; display: flex; flex-direction: column; margin-top: 8px; }
.iv-hueco-n b { font-weight: 650; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.iv-hueco-n small { font-size: 12px; color: var(--media); }
.iv-hueco-e { position: absolute; top: 10px; right: 10px; z-index: 2; padding: 2px 8px; border-radius: 999px; background: var(--cv); color: var(--c); font-size: 11px; font-weight: 650; }
.iv-hueco.e-agotado .iv-caja.mini { filter: saturate(.35); opacity: .75; }

.iv-mas { padding: 12px; text-align: center; border-top: 1px solid var(--linea2); }
.iv-mas button { height: 36px; padding: 0 16px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--texto); }
.iv-sin { padding: 46px 20px; display: grid; justify-items: center; gap: 8px; text-align: center; color: var(--media); }
.iv-sin .bi { font-size: 30px; color: var(--teal); }
.iv-sin p { font-weight: 600; color: var(--tinta); }
.iv-sin button { font-weight: 600; color: var(--teal); background: none; }

@container (max-width: 1079px) { .iv-escena { height: 260px; } }
@media (prefers-reduced-motion: reduce) { .iv-caja.mini, .iv-barra s, .iv-ind button i:after { transition: none; } }
</style>

<script>
function moduloInventario() {
    const D = JSON.parse(document.getElementById('iv-datos').textContent);
    const quieto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Giro de la caja: fuera de lo reactivo para que arrastrar no cueste.
    const giro = { rx: -16, ry: -32, tz: 0, cuadro: 0, animando: false, mano: false, quieto: 0, visible: true, ultimo: 0, giraba: false };
    let hueco = null;
    // Referencias fijas: la escena se muda al body durante la presentación y ahí $refs ya no la encuentra
    const R = {};
    const CARAS = { frente: [-6, 0], reverso: [-6, 180], lado: [-6, -90], tres: [-16, -32] };
    // El dibujo de la caja (MTCaja) está en el layout
    const esc = MTCaja.esc, titulo = MTCaja.titulo, barras = MTCaja.barras;

    return {
        productos: D.productos,
        categorias: D.categorias,
        opciones: [
            { v: 'ok', t: 'Suficientes' },
            { v: 'bajo', t: 'Por surtir' },
            { v: 'agotado', t: 'Agotados' },
        ],
        q: '',
        categoriaFiltro: '',
        estadoFiltro: 'todos',
        orden: 'nombre',
        vista: 'lista',
        limite: 60,
        sel: null,
        cara: 'tres',
        abierta: false,
        cine: false,
        entrando: false,
        auto: true,
        paso: 0,
        cineTok: 0,

        // Modales (los usan los archivos modal*)
        openCreateModal: D.abrirCrear,
        openSurtirModal: false,
        openEditModal: false,
        openCategoriaModal: false,
        openLoteModal: false,
        productoSeleccionado: {},
        productoEditar: {},

        init() {
            try { const v = localStorage.getItem('inventario_vista'); if (v === 'lista' || v === 'anaquel') this.vista = v; } catch (e) {}
            ['q', 'categoriaFiltro', 'estadoFiltro', 'orden'].forEach(k => this.$watch(k, () => { this.limite = 60; }));
            if (this.$refs.gl && typeof mtGuilloche === 'function') this.$refs.gl.innerHTML = mtGuilloche('INVENTARIO');
            ['escena', 'caja', 'reflejo', 'sombra', 'polvo'].forEach(k => { R[k] = this.$refs[k]; });
            try { this.auto = localStorage.getItem('iv-auto') !== '0'; } catch (e) {}
            this.arrancarTornamesa();

            // Elegido: ?p=ID, luego el último que se vio, luego el primero de la lista
            let id = null, nuevo = null;
            try {
                id = new URLSearchParams(location.search).get('p') || sessionStorage.getItem('iv-sel');
                // Recién registrado: se elige el producto nuevo (lo deja el modal de registrar antes de enviar)
                nuevo = sessionStorage.getItem('iv-nuevo'); sessionStorage.removeItem('iv-nuevo');
            } catch (e) {}
            const recien = nuevo && D.exito && this.productos.find(p => p.codigo === String(nuevo).toUpperCase());
            const previo = recien || (id && this.productos.find(p => String(p.id) === String(id)));
            const primero = previo || this.filtrados[0];
            if (primero) this.seleccionar(primero, { sinGiro: !!previo, sinScroll: !previo });

            if (D.exito) setTimeout(() => {
                this.aviso(D.exito, 'success');
                if (this.sel && window.mtDestello) mtDestello('.iv-fila.sel, .iv-hueco.sel');
            }, 250);
            if (D.error) setTimeout(() => this.aviso(D.error, 'error'), 250);
        },

        // ----- Resumen y filtros -----
        get resumen() {
            return {
                valor: this.productos.reduce((s, p) => s + p.compra * p.disponible, 0),
                unidades: this.productos.reduce((s, p) => s + p.disponible, 0),
            };
        },
        get hayInsumos() { return this.productos.some(p => this.tipoDe(p).insumo); },
        conteo(e) { return this.productos.filter(p => p.estado === e).length; },
        filtrar(e) { this.estadoFiltro = this.estadoFiltro === e ? 'todos' : e; },
        normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
        get filtrados() {
            const q = this.normalizar(this.q);
            const lista = this.productos.filter(p =>
                (!q || p.buscar.includes(q)) &&
                (!this.categoriaFiltro || p.categoria === this.categoriaFiltro) &&
                (this.estadoFiltro === 'todos' || p.estado === this.estadoFiltro));
            const orden = {
                nombre: (a, b) => a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' }),
                stock: (a, b) => (a.disponible - b.disponible) || a.nombre.localeCompare(b.nombre, 'es'),
                venta: (a, b) => b.venta - a.venta,
                margen: (a, b) => (b.margen ?? -999) - (a.margen ?? -999),
            }[this.orden];
            return lista.sort(orden);
        },
        get pagina() { return this.filtrados.slice(0, this.limite); },
        get hayFiltros() { return this.q.trim() !== '' || this.categoriaFiltro !== '' || this.estadoFiltro !== 'todos'; },
        limpiarFiltros() { this.q = ''; this.categoriaFiltro = ''; this.estadoFiltro = 'todos'; },
        cambiarVista(v) { this.vista = v; try { localStorage.setItem('inventario_vista', v); } catch (e) {} },
        // Enter en el buscador: si el texto es un código exacto (lector), elige ese medicamento
        buscarExacto() {
            const t = this.$refs.buscar.value.trim().toUpperCase();
            this.q = this.$refs.buscar.value;
            const p = this.productos.find(x => x.codigo === t) || (this.filtrados.length === 1 ? this.filtrados[0] : null);
            if (!p) return;
            if (p.codigo === t) { this.q = ''; this.$refs.buscar.value = ''; }
            this.seleccionar(p);
            if (window.mtDestello) this.$nextTick(() => mtDestello('.iv-fila.sel, .iv-hueco.sel'));
        },
        categoriasCambiaron(d) {
            if (d && Array.isArray(d.lista)) this.categorias = d.lista.map(c => c.nombre).sort((a, b) => a.localeCompare(b, 'es'));
        },

        // ----- Formato -----
        dinero(n, dec = 2) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: dec, maximumFractionDigits: dec }); },
        numero(n) { return Number(n || 0).toLocaleString('es-MX'); },
        titulo,
        partes(p) { return MTCaja.partes(p); },
        iniciales(p) { return MTCaja.iniciales(p); },
        colorDe(p) { return MTCaja.colorDe(p); },
        textoEstado(p) { return { ok: 'Suficiente', bajo: 'Por surtir', agotado: 'Agotado' }[p.estado]; },
        escala(p) { return Math.max(p.minimo * 3, p.disponible, 1); },
        nivel(p) { return p.disponible <= 0 ? 0 : Math.max(2, Math.min(100, p.disponible / this.escala(p) * 100)); },
        posMinimo(p) { return Math.min(100, p.minimo / this.escala(p) * 100); },
        mensajeStock(p) {
            if (p.estado === 'agotado') return p.minimo > 0 ? 'Surte al menos ' + p.minimo : 'Sin existencias';
            if (p.estado === 'bajo') { const f = p.minimo - p.disponible + 1; return 'Faltan ' + f + ' para pasar el mínimo'; }
            return (p.disponible - p.minimo) + ' arriba del mínimo';
        },
        descripcionUtil(p) { return MTCaja.descripcionUtil(p); },

        // ----- Caja 3D -----
        barrasDe(cod) { return barras(cod); },
        // ----- Tipo de producto: medicamento o insumo (por nombre y categoría) -----
        tipoDe(p) { return MTCaja.tipoDe(p); },
        itemSVG(item) { return MTCaja.itemSVG(item); },
        contenidoHTML(p) { return MTCaja.contenidoHTML(p); },
        claseCaja(p, extra) { return 'iv-caja t-' + this.tipoDe(p).clase + (extra ? ' ' + extra : ''); },
        cajaHTML(p, completo) { return MTCaja.cajaHTML(p, completo); },
        pintarCaja() {
            const c = R.caja;
            if (!c) return;
            const tr = 'translateZ(' + giro.tz.toFixed(1) + 'px) rotateX(' + giro.rx.toFixed(2) + 'deg) rotateY(' + giro.ry.toFixed(2) + 'deg)';
            c.style.transform = tr;
            if (R.reflejo) R.reflejo.style.transform = tr;
            const v = MTCaja.luces(giro.rx, giro.ry), b = giro.ry * Math.PI / 180;
            c.style.cssText = c.style.cssText.replace(/--[sgp]-(fr|re|de|iz|ar|ab):[^;]*;?/g, '') + ';' + v;
            if (R.reflejo) R.reflejo.style.cssText = R.reflejo.style.cssText.replace(/--[sgp]-(fr|re|de|iz|ar|ab):[^;]*;?/g, '') + ';' + v;
            if (R.sombra) {
                const lejos = Math.max(0, -giro.tz) / 400;
                R.sombra.style.transform = 'scaleX(' + ((0.82 + 0.18 * Math.abs(Math.cos(b))) * (1 - lejos * .5)).toFixed(3) + ')';
                R.sombra.style.opacity = (1 - lejos).toFixed(2);
            }
        },
        tzBase() { return this.abierta ? (this.cine ? -230 : -200) : 0; },
        animarA(rx, ry, ms, desde, tz = this.tzBase()) {
            cancelAnimationFrame(giro.cuadro);
            giro.quieto = performance.now();
            if (quieto || !ms) { giro.rx = rx; giro.ry = ry; giro.tz = tz; giro.animando = false; return this.pintarCaja(); }
            const r0 = desde ? desde[0] : giro.rx, y0 = desde ? desde[1] : giro.ry, z0 = desde && desde[2] !== undefined ? desde[2] : giro.tz, t0 = performance.now();
            giro.animando = true;
            const paso = t => {
                const k = Math.min(1, (t - t0) / ms), e = k < 1 ? 1 - Math.pow(1 - k, 3) : 1;
                giro.rx = r0 + (rx - r0) * e; giro.ry = y0 + (ry - y0) * e; giro.tz = z0 + (tz - z0) * e;
                this.pintarCaja();
                if (k < 1) giro.cuadro = requestAnimationFrame(paso);
                else { giro.animando = false; giro.quieto = performance.now(); }
            };
            giro.cuadro = requestAnimationFrame(paso);
        },
        // Lleva ry a la vuelta más cercana del destino para no girar de más
        cerca(ry) { const n = ((giro.ry - ry) % 360 + 540) % 360 - 180; return giro.ry - n; },
        verCara(c) {
            this.cara = c;
            const [rx, ry] = CARAS[c];
            this.animarA(this.abierta && c === 'tres' ? -30 : rx, this.cerca(ry), 420);
        },
        abrirCaja() {
            this.abierta = !this.abierta;
            giro.quieto = performance.now();
            // Al abrir, la cámara sube un poco para ver el contenido
            this.animarA(this.abierta ? Math.min(giro.rx, -30) : (giro.rx < -24 ? -16 : giro.rx), giro.ry, 480);
        },
        alternarAuto() {
            this.auto = !this.auto;
            giro.quieto = this.auto ? 0 : performance.now();
            try { localStorage.setItem('iv-auto', this.auto ? '1' : '0'); } catch (e) {}
        },
        // Tornamesa: gira despacio cuando nadie la toca (se detiene al arrastrar, al usar los botones o si no está a la vista)
        arrancarTornamesa() {
            if (quieto) return;
            const el = R.escena;
            if (el && 'IntersectionObserver' in window) new IntersectionObserver(e => { giro.visible = e[0].isIntersecting; }).observe(el);
            const bucle = t => {
                const dt = Math.min(48, t - (giro.ultimo || t)); giro.ultimo = t;
                const libre = this.auto && this.sel && !giro.mano && !giro.animando && !document.hidden && (giro.visible || this.cine) && t - giro.quieto > 2400;
                if (libre) {
                    if (!giro.giraba) { giro.giraba = true; this.cara = ''; }
                    giro.ry += dt * .011;
                    this.pintarCaja();
                } else giro.giraba = false;
                requestAnimationFrame(bucle);
            };
            requestAnimationFrame(bucle);
        },
        agarrar(e) {
            if (!this.sel || e.button !== 0) return;
            cancelAnimationFrame(giro.cuadro);
            giro.animando = false;
            const el = R.escena;
            let lx = e.clientX, ly = e.clientY, lt = performance.now(), vx = 0, vy = 0;
            try { el.setPointerCapture(e.pointerId); } catch (_) {}
            el.classList.add('agarrando');
            giro.mano = true;
            this.cara = '';
            const mover = ev => {
                const t = performance.now(), dt = Math.max(8, t - lt);
                const dx = (ev.clientX - lx) * .5, dy = (ev.clientY - ly) * .5;
                giro.ry += dx; giro.rx = Math.max(-80, Math.min(80, giro.rx - dy));
                vx = dx / dt * 16; vy = -dy / dt * 16;
                lx = ev.clientX; ly = ev.clientY; lt = t;
                this.pintarCaja();
            };
            const soltar = () => {
                el.removeEventListener('pointermove', mover);
                el.removeEventListener('pointerup', soltar);
                el.removeEventListener('pointercancel', soltar);
                el.classList.remove('agarrando');
                giro.mano = false;
                giro.quieto = performance.now();
                if (quieto || performance.now() - lt > 80) return;
                // Inercia corta
                giro.animando = true;
                const paso = () => {
                    vx *= .92; vy *= .92;
                    giro.ry += vx; giro.rx = Math.max(-80, Math.min(80, giro.rx + vy));
                    this.pintarCaja();
                    if (Math.abs(vx) + Math.abs(vy) > .08) giro.cuadro = requestAnimationFrame(paso);
                    else { giro.animando = false; giro.quieto = performance.now(); }
                };
                giro.cuadro = requestAnimationFrame(paso);
            };
            el.addEventListener('pointermove', mover);
            el.addEventListener('pointerup', soltar);
            el.addEventListener('pointercancel', soltar);
        },
        teclaEscena(e) {
            if (e.key === 'Escape' && this.cine) { e.preventDefault(); e.stopPropagation(); return this.salirCine(); }
            const m = { ArrowLeft: [0, -20], ArrowRight: [0, 20], ArrowUp: [12, 0], ArrowDown: [-12, 0] }[e.key];
            if (!m || !this.sel) return;
            e.preventDefault(); e.stopPropagation();
            this.cara = '';
            this.animarA(Math.max(-80, Math.min(80, giro.rx + m[0])), giro.ry + m[1], 180);
        },

        // ----- Presentación en pantalla completa -----
        entrarCine() {
            if (this.cine) return;
            const el = R.escena;
            hueco = document.createComment('escena');
            el.parentNode.insertBefore(hueco, el);
            document.body.appendChild(el);
            document.documentElement.classList.add('iv-sin-scroll');
            if (!R.polvo.childElementCount) {
                let h = '';
                for (let i = 0; i < 26; i++) h += '<i style="left:' + (Math.random() * 100).toFixed(1) + '%;top:' + (20 + Math.random() * 70).toFixed(1) + '%;--t:' + (7 + Math.random() * 7).toFixed(1) + 's;--r:-' + (Math.random() * 10).toFixed(1) + 's;--x:' + (Math.random() * 60 - 30).toFixed(0) + 'px"></i>';
                R.polvo.innerHTML = h;
            }
            this.cine = true;
            this.$nextTick(() => el.focus({ preventScroll: true }));
        },
        salirCine() {
            if (!this.cine) return;
            this.cineTok++;
            this.cine = false;
            this.paso = 0;
            const el = R.escena;
            el.classList.remove('oscuro');
            if (hueco && hueco.parentNode) { hueco.parentNode.insertBefore(el, hueco); hueco.remove(); }
            hueco = null;
            document.documentElement.classList.remove('iv-sin-scroll');
            this.verCara(this.abierta ? 'tres' : 'tres');
        },
        // Secuencia corta tipo comercial: se enciende la luz, la cámara se acerca girando, muestra el reverso, abre la caja y presenta los datos
        async presentar() {
            if (!this.sel) return;
            this.entrarCine();
            const tok = ++this.cineTok, el = R.escena;
            const vivo = () => tok === this.cineTok && this.cine;
            const espera = ms => new Promise(r => setTimeout(r, quieto ? 0 : ms));
            this.paso = 0;
            this.abierta = false;
            this.cara = '';
            el.classList.add('oscuro');
            giro.rx = -6; giro.ry = -230; giro.tz = -520; this.pintarCaja();
            await espera(120); if (!vivo()) return;
            el.classList.remove('oscuro');
            this.animarA(-12, -28, 1500, null, 0);
            await espera(1300); if (!vivo()) return;
            this.paso = 1;
            this.animarA(-8, 160, 2000);
            await espera(700); if (!vivo()) return;
            this.paso = 2;
            await espera(700); if (!vivo()) return;
            this.paso = 3;
            await espera(800); if (!vivo()) return;
            this.abierta = true;
            this.animarA(-30, 330, 1100);
            await espera(700); if (!vivo()) return;
            await espera(800); if (!vivo()) return;
            this.paso = 4;
        },

        // ----- Selección -----
        seleccionar(p, op = {}) {
            const cambio = !this.sel || this.sel.id !== p.id;
            this.sel = p;
            try { sessionStorage.setItem('iv-sel', p.id); history.replaceState(null, '', location.pathname + '?p=' + p.id); } catch (e) {}
            if (!cambio) return;
            this.cara = 'tres';
            this.abierta = false;
            this.$nextTick(() => {
                const [rx, ry] = CARAS.tres;
                if (op.sinGiro || quieto) { giro.rx = rx; giro.ry = ry; giro.tz = 0; this.pintarCaja(); }
                else {
                    // Entrada: la luz se enciende mientras la caja llega girando desde el fondo
                    this.entrando = true;
                    requestAnimationFrame(() => requestAnimationFrame(() => { this.entrando = false; }));
                    this.animarA(rx, ry, 680, [-4, ry - 200, -260], 0);
                }
                if (!op.sinScroll && window.innerWidth < 1100 && R.escena && !this.cine) {
                    const r = R.escena.getBoundingClientRect();
                    if (r.bottom < 0 || r.top > window.innerHeight) R.escena.scrollIntoView({ block: 'nearest', behavior: quieto ? 'auto' : 'smooth' });
                }
            });
        },
        mover(paso) {
            const l = this.pagina;
            if (!l.length) return;
            let i = this.sel ? l.findIndex(p => p.id === this.sel.id) : -1;
            i = Math.max(0, Math.min(l.length - 1, i + paso));
            this.seleccionar(l[i], { sinScroll: true });
            this.$nextTick(() => {
                const el = this.$root.querySelector('.iv-fila.sel, .iv-hueco.sel');
                if (el) el.scrollIntoView({ block: 'nearest' });
            });
        },
        get modalAbierto() { return this.openCreateModal || this.openEditModal || this.openSurtirModal || this.openCategoriaModal || this.openLoteModal || !!document.querySelector('.swal2-container .swal2-popup:not(.swal2-toast)'); },
        tecla(e) {
            if (this.cine) { if (e.key === 'Escape') { e.preventDefault(); this.salirCine(); } return; }
            if (e.ctrlKey || e.metaKey || e.altKey || this.modalAbierto) return;
            const t = e.target, escribiendo = t && (['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName) || t.isContentEditable);
            if (e.key === 'Escape' && t === this.$refs.buscar && this.q) { this.q = ''; return; }
            if (escribiendo) return;
            const k = e.key.toLowerCase();
            if (e.key === '/') { e.preventDefault(); this.$refs.buscar.focus(); }
            else if (k === 'n' && document.querySelector('.iv-dark')) { e.preventDefault(); this.openCreateModal = true; }
            else if (k === 's' && this.sel && document.querySelector('.iv-ficha-acc .iv-btn')) { e.preventDefault(); this.abrirSurtir(this.sel.raw); }
            else if (k === 'e' && this.sel && document.querySelectorAll('.iv-ficha-acc .iv-btn').length > 1) { e.preventDefault(); this.abrirEditar(this.sel.raw); }
            else if ((e.key === 'ArrowDown' || e.key === 'j') && t !== R.escena) { e.preventDefault(); this.mover(1); }
            else if ((e.key === 'ArrowUp' || e.key === 'k') && t !== R.escena) { e.preventDefault(); this.mover(-1); }
        },

        // ----- Acciones -----
        abrirSurtir(producto) { this.productoSeleccionado = JSON.parse(JSON.stringify(producto)); this.openSurtirModal = true; },
        abrirEditar(producto) { this.productoEditar = JSON.parse(JSON.stringify(producto)); this.openEditModal = true; },
        aviso(m, i = 'success') {
            if (typeof window.notificar === 'function') return window.notificar(m, i);
            if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: i, title: m });
        },
        async copiar(t) {
            try { await navigator.clipboard.writeText(t); this.aviso('Código copiado: ' + t); }
            catch (e) { this.aviso('No se pudo copiar', 'warning'); }
        },
        eliminar(p) {
            const aviso = p.disponible > 0 ? '<br><span style="font-size:13px;color:#9a5800">Todavía tiene ' + p.disponible + ' piezas en existencia.</span>' : '';
            Swal.fire({
                title: '¿Eliminar medicamento?',
                html: 'Se quitará <b>' + esc(titulo(p.nombre)) + '</b> (' + esc(p.codigo) + ') del catálogo.' + aviso,
                icon: 'warning', showCancelButton: true, reverseButtons: true, focusCancel: true,
                confirmButtonColor: '#c8102e', cancelButtonColor: '#56625f',
                confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const res = await fetch(D.url + '/' + p.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': D.csrf, 'Accept': 'application/json' } });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok || data.success === false) throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (data.message || 'No se pudo eliminar el medicamento.'));
                        return data;
                    } catch (e) { Swal.showValidationMessage(e.message || 'Error de conexión'); }
                },
                allowOutsideClick: () => !Swal.isLoading(),
            }).then(r => {
                if (!r.isConfirmed) return;
                const l = this.pagina, i = l.findIndex(x => x.id === p.id);
                const sig = l[i + 1] || l[i - 1] || null;
                p._saliendo = true;
                setTimeout(() => {
                    this.productos = this.productos.filter(x => x.id !== p.id);
                    if (this.sel && this.sel.id === p.id) { this.sel = null; if (sig) this.seleccionar(sig, { sinScroll: true }); else { try { sessionStorage.removeItem('iv-sel'); history.replaceState(null, '', location.pathname); } catch (e) {} } }
                }, 190);
                this.aviso((r.value && r.value.message) || 'Medicamento eliminado.');
            });
        },
    };
}
</script>
@endverbatim
@endsection
