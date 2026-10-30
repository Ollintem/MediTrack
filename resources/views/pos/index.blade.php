@extends('layouts.admin')

@section('title', 'Punto de venta - MediTrack')

{{--
    PUNTO DE VENTA (mostrador de farmacia) · prefijo ps-
    Catálogo con la caja 3D de cada producto (MTCaja del layout), el pedido como ticket térmico que se va imprimiendo
    y la franja de pedidos pagados por entregar. Flujo: el POS genera el ticket (aparta existencias 25 min),
    caja central lo cobra y aquí se entrega (único punto donde baja el stock físico). Rutas y PosController sin cambios.
--}}

@section('content')
@php
    $productosPos = collect($productos ?? [])->map(function ($p) {
        $disponible = (int) ($p->stock_disponible ?? $p->stock_actual ?? 0);
        $minimo     = (int) ($p->stock_minimo ?? 0);
        return [
            'id'          => $p->id,
            'nombre'      => (string) ($p->nombre ?? 'Producto'),
            'codigo'      => strtoupper((string) ($p->codigo ?? $p->codigo_barras ?? '')),
            'categoria'   => data_get($p, 'categoria.nombre') ?: 'General',
            'descripcion' => (string) ($p->descripcion ?? ''),
            'venta'       => (float) ($p->precio_venta ?? 0),
            'disponible'  => $disponible,
            'fisico'      => (int) ($p->stock_actual ?? 0),
            'minimo'      => $minimo,
            'estado'      => $disponible <= 0 ? 'agotado' : ($disponible <= $minimo ? 'bajo' : 'ok'),
        ];
    })->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();

    $pacientesPos = collect($pacientes ?? [])->map(fn ($p) => [
        'id'     => $p->id,
        'nombre' => trim((string) ($p->nombre ?? implode(' ', array_filter([$p->primer_nombre, $p->apellido_paterno, $p->apellido_materno])))),
        'codigo' => (string) ($p->codigo ?? ''),
    ])->values();

    $datosPos = [
        'productos' => $productosPos,
        'pacientes' => $pacientesPos,
        'tickets'   => $ticketsRecientes ?? $tickets ?? [],
        'url'       => [
            'generar'  => route('pos.generar-ticket'),
            'entregar' => url('/pos/entregar-ticket'),
        ],
        'csrf'      => csrf_token(),
        'clinica'   => data_get($clinica ?? null, 'nombre') ?: 'MediTrack',
    ];
@endphp

<script type="application/json" id="ps-datos">{!! json_encode($datosPos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>

<div class="ps" x-data="posApp()" x-cloak @keydown.window="tecla($event)">

    <!-- BUSCADOR / LECTOR Y TICKETS -->
    <header class="ps-top">
        <label class="ps-cmd" :class="destelloBuscar && 'leido'">
            <i class="bi bi-upc-scan" aria-hidden="true"></i>
            <span class="ps-sr">Escanear código o buscar producto</span>
            <input type="search" x-ref="buscar" x-model="q" @keydown.enter.prevent="escanear()" @keydown.escape="q = ''"
                   autocomplete="off" placeholder="Escanea el código de barras o busca un medicamento">
            <kbd aria-hidden="true">/</kbd>
        </label>
        <button type="button" class="ps-btn" @click="abrirHistorial()">
            <i class="bi bi-receipt"></i> Tickets
            <b class="ps-cuenta" x-show="pendientesVivos.length" x-text="pendientesVivos.length" title="Pendientes de pago en caja"></b>
        </button>
    </header>

    <!-- PEDIDOS PAGADOS POR ENTREGAR -->
    <section class="ps-entregas" x-show="porEntregar.length">
        <div class="ps-ent-cab">
            <i class="bi bi-bag-check"></i>
            <div><b x-text="porEntregar.length === 1 ? '1 pedido pagado por entregar' : porEntregar.length + ' pedidos pagados por entregar'"></b><span>Al entregar se descuentan del inventario.</span></div>
            <form class="ps-folio" @submit.prevent="entregarPorCodigo()">
                <span>TCK-</span>
                <input type="text" x-model="codigoRapido" maxlength="12" placeholder="Folio" aria-label="Folio del ticket a entregar" autocomplete="off">
                <button type="submit" :disabled="!codigoRapido.trim() || entregando"><i class="bi bi-box-arrow-right"></i>Entregar</button>
            </form>
        </div>
        <div class="ps-ent-lista">
            <template x-for="t in porEntregar" :key="'e' + t.id">
                <article class="ps-ent" :class="t._saliendo && 'sale'">
                    <div>
                        <b class="t" x-text="folio(t)"></b>
                        <span x-text="[nombrePaciente(t), piezas(t)].filter(Boolean).join(' · ')"></span>
                    </div>
                    <span class="ps-din t" x-text="dinero(t.monto_total)"></span>
                    <button type="button" class="ps-mini" @click="entregar(t)" :disabled="entregando === t.id">
                        <i class="bi" :class="entregando === t.id ? 'bi-hourglass-split' : 'bi-check2'"></i><span x-text="entregando === t.id ? 'Surtiendo' : 'Entregar'"></span>
                    </button>
                </article>
            </template>
        </div>
    </section>

    <section class="ps-esc">
        <!-- CATÁLOGO -->
        <div class="ps-cat">
            <div class="ps-filtros">
                <div class="ps-seg" role="group" aria-label="Categoría">
                    <button type="button" :aria-pressed="(!cat).toString()" @click="cat = ''">Todo <small x-text="P.length"></small></button>
                    <template x-for="c in categorias" :key="'c' + c.n">
                        <button type="button" :aria-pressed="(cat === c.n).toString()" @click="cat = cat === c.n ? '' : c.n">
                            <i :style="'background:' + c.color"></i><span x-text="c.n"></span><small x-text="c.total"></small>
                        </button>
                    </template>
                </div>
                <label class="ps-chk"><input type="checkbox" x-model="soloDisp"> Solo con existencias</label>
            </div>

            <div class="ps-grid">
                <template x-for="(p, i) in filtrados" :key="'p' + p.id">
                    <button type="button" class="ps-prod" :class="['s-' + p.estado, enCarrito(p) && 'en', golpe === p.id && 'golpe']"
                            :disabled="p.disponible <= 0" @click="agregar(p)" :style="'animation-delay:' + Math.min(i * 14, 140) + 'ms'"
                            :aria-label="'Agregar ' + titulo(p.nombre) + ', ' + dinero(p.venta)">
                        <span class="ps-art"><span class="ps-mesa"><span class="iv-caja" :class="'t-' + tipo(p)" :style="estiloCaja(p)" x-html="caja(p)"></span></span></span>
                        <span class="ps-info">
                            <span class="ps-cat-n"><i :style="'background:' + color(p)"></i><span x-text="p.categoria"></span></span>
                            <b x-text="partes(p).principal"></b>
                            <span class="ps-dosis" x-text="[partes(p).dosis, partes(p).forma].filter(Boolean).join(' · ') || p.codigo"></span>
                            <span class="ps-pie">
                                <span class="ps-precio t" x-text="dinero(p.venta)"></span>
                                <span class="ps-disp t" x-text="p.disponible <= 0 ? 'Agotado' : (disponibleAhora(p) + ' disp.')"></span>
                            </span>
                        </span>
                        <span class="ps-cant t" x-show="enCarrito(p)" x-text="'×' + enCarrito(p)"></span>
                    </button>
                </template>
            </div>
            <div class="ps-vacio" x-show="!filtrados.length">
                <b x-text="P.length ? 'Ningún producto coincide' : 'No hay productos en el inventario'"></b>
                <button type="button" x-show="P.length" @click="q = ''; cat = ''; soloDisp = false">Quitar filtros</button>
            </div>
        </div>

        <!-- PEDIDO COMO TICKET TÉRMICO -->
        <aside class="ps-pedido" aria-label="Pedido">
            <div class="ps-papel">
                <header class="ps-p-cab">
                    <span>Pedido de mostrador</span>
                    <button type="button" x-show="carrito.length" @click="vaciar()">Vaciar</button>
                </header>

                <div class="ps-pac" @click.outside="pacAbierto = false">
                    <span class="ps-p-lab">Paciente</span>
                    <button type="button" class="ps-pac-sel" @click="pacAbierto = !pacAbierto; $nextTick(() => pacAbierto && $refs.pacQ.focus())" :aria-expanded="pacAbierto.toString()">
                        <i class="bi" :class="paciente ? 'bi-person-check' : 'bi-people'"></i>
                        <span x-text="paciente ? titulo(paciente.nombre) : 'Público general'"></span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="ps-pac-menu" x-show="pacAbierto" x-transition.opacity.duration.120ms>
                        <input type="search" x-ref="pacQ" x-model="pacQ" placeholder="Buscar paciente" autocomplete="off" @keydown.enter.prevent="pacientesFiltrados[0] && elegirPaciente(pacientesFiltrados[0])" @keydown.escape="pacAbierto = false">
                        <button type="button" @click="elegirPaciente(null)" :class="!paciente && 'si'"><i class="bi bi-people"></i>Público general</button>
                        <template x-for="pa in pacientesFiltrados" :key="'pa' + pa.id">
                            <button type="button" @click="elegirPaciente(pa)" :class="paciente && paciente.id === pa.id && 'si'"><i class="bi bi-person"></i><span x-text="titulo(pa.nombre)"></span><small x-text="pa.codigo"></small></button>
                        </template>
                        <p x-show="pacQ && !pacientesFiltrados.length">Sin coincidencias</p>
                    </div>
                </div>

                <ol class="ps-lineas">
                    <template x-for="(it, i) in carrito" :key="'l' + it.id">
                        <li class="ps-linea">
                            <div class="ps-l-n">
                                <b x-text="titulo(it.nombre)"></b>
                                <span class="t" x-text="dinero(it.precio) + ' c/u'"></span>
                            </div>
                            <div class="ps-paso">
                                <button type="button" @click="cambiar(i, -1)" :aria-label="it.cantidad === 1 ? 'Quitar' : 'Uno menos'"><i class="bi" :class="it.cantidad === 1 ? 'bi-trash3' : 'bi-dash'"></i></button>
                                <b class="t" x-text="it.cantidad"></b>
                                <button type="button" @click="cambiar(i, 1)" :disabled="it.cantidad >= it.max" aria-label="Uno más"><i class="bi bi-plus"></i></button>
                            </div>
                            <span class="ps-l-sub t" x-text="dinero(it.precio * it.cantidad)"></span>
                        </li>
                    </template>
                </ol>
                <div class="ps-p-vacio" x-show="!carrito.length">
                    <i class="bi bi-upc-scan"></i>
                    <b>Escanea o toca un producto</b>
                    <span>Se agrega aquí; con cada toque suma una pieza.</span>
                </div>

                <footer class="ps-p-pie">
                    <p class="ps-p-res t" x-show="carrito.length"><span x-text="piezasCarrito + (piezasCarrito === 1 ? ' pieza' : ' piezas')"></span><span x-text="carrito.length + (carrito.length === 1 ? ' producto' : ' productos')"></span></p>
                    <p class="ps-total"><span>Total a pagar en caja</span><b class="t" x-html="cifra(total)"></b></p>
                    <button type="button" class="ps-generar" @click="generar()" :disabled="!carrito.length || cargando">
                        <i class="bi" :class="cargando ? 'bi-hourglass-split' : 'bi-ticket-perforated'"></i>
                        <span x-text="cargando ? 'Generando…' : 'Generar ticket'"></span>
                        <kbd x-show="!cargando">Ctrl ↵</kbd>
                    </button>
                    <p class="ps-nota"><i class="bi bi-clock-history"></i> Aparta las piezas 25 minutos mientras se paga en caja.</p>
                </footer>
            </div>
        </aside>
    </section>

    <!-- BARRA DEL PEDIDO EN PANTALLAS ANGOSTAS -->
    <template x-teleport="body">
    <button type="button" class="ps-barra-m" x-show="carrito.length && !openTicketModal && !historial" x-transition.opacity.duration.150ms @click="document.querySelector('.ps-pedido').scrollIntoView({ behavior: 'smooth', block: 'start' })">
        <i class="bi bi-ticket-perforated"></i>
        <span x-text="piezasCarrito + (piezasCarrito === 1 ? ' pieza' : ' piezas')"></span>
        <b class="t" x-text="dinero(total)"></b>
        <i class="bi bi-chevron-down"></i>
    </button>
    </template>

    <!-- MODAL: HISTORIAL DE TICKETS -->
    <template x-teleport="body">
        <div class="psm-velo" x-show="historial" x-cloak @keydown.escape.window="historial = false"
             x-transition:enter="psm-t" x-transition:enter-start="psm-0" x-transition:enter-end="psm-1"
             x-transition:leave="psm-t-s" x-transition:leave-start="psm-1" x-transition:leave-end="psm-0">
            <div class="psm ancho" role="dialog" aria-modal="true" aria-label="Tickets generados" @click.outside="historial = false">
                <div class="psm-cab">
                    <span class="ic"><i class="bi bi-receipt"></i></span>
                    <div><h3>Tickets generados</h3><p x-text="'Últimos ' + T.length"></p></div>
                    <button type="button" class="cerrar" @click="historial = false" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="psm-filtros">
                    <input type="search" x-ref="buscarTck" x-model="tq" class="psm-in" placeholder="Buscar por folio o paciente" autocomplete="off">
                    <div class="ps-seg" role="group" aria-label="Estado">
                        <template x-for="e in ESTADOS" :key="'f' + e.k">
                            <button type="button" :aria-pressed="(filtroEstado === e.k).toString()" @click="filtroEstado = e.k">
                                <span x-text="e.t"></span><small x-text="contarEstado(e.k)"></small>
                            </button>
                        </template>
                    </div>
                </div>
                <div class="psm-cuerpo">
                    <template x-for="t in ticketsFiltrados" :key="'h' + t.id">
                        <article class="ps-tk">
                            <span class="ps-edo" :class="estadoDe(t)" x-text="textoEstado(estadoDe(t))"></span>
                            <div class="ps-tk-n">
                                <b class="t" x-text="folio(t)"></b>
                                <span x-text="nombrePaciente(t) + ' · ' + hora(t.created_at) + (estadoDe(t) === 'pendiente' ? ' · vence en ' + restante(t) : '')"></span>
                            </div>
                            <span class="ps-din t" x-text="dinero(t.monto_total)"></span>
                            <button type="button" class="ps-mini" x-show="estadoDe(t) === 'pagado'" @click="entregar(t)" :disabled="entregando === t.id"><i class="bi bi-check2"></i>Entregar</button>
                            <button type="button" class="ps-ico" @click="ver(t)" title="Ver comprobante" aria-label="Ver comprobante"><i class="bi bi-eye"></i></button>
                        </article>
                    </template>
                    <div class="ps-vacio" x-show="!ticketsFiltrados.length"><b x-text="T.length ? 'Ningún ticket coincide' : 'Aún no se generan tickets'"></b></div>
                </div>
            </div>
        </div>
    </template>

    @include('pos.modalTicket')
</div>

@verbatim
<style>
.ps {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --termico: #fffef9; --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px;
}
.ps *, .psm * { box-sizing: border-box; }
.ps p, .ps h3, .psm p, .psm h3 { margin: 0; }
.ps button, .psm button { cursor: pointer; font-family: inherit; }
.ps button:disabled, .psm button:disabled { cursor: default; }
.ps :focus-visible, .psm :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.ps kbd, .psm kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); background: transparent; }
.ps .t, .psm .t { font-variant-numeric: tabular-nums; }
.ps-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

/* Arriba */
.ps-top { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 12px; }
.ps-cmd { flex: 1 1 320px; min-width: 0; height: 52px; border-radius: 15px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 12px 30px -24px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 16px; transition: border-color .15s, box-shadow .15s; }
.ps-cmd:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .13); }
.ps-cmd.leido { border-color: var(--verde); box-shadow: 0 0 0 4px rgba(8, 115, 79, .18); }
.ps-cmd > .bi { font-size: 18px; color: var(--cred); }
.ps-cmd input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 450 16px var(--ui); color: var(--tinta); }
.ps-cmd input::placeholder { color: var(--media); }
.ps-btn { position: relative; height: 46px; display: inline-flex; align-items: center; gap: 8px; padding: 0 16px; border-radius: 13px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); }
.ps-btn:hover { background: #f7faf9; }
.ps-btn .bi { color: var(--cred); }
.ps-cuenta { min-width: 22px; height: 22px; padding: 0 6px; border-radius: 99px; display: grid; place-items: center; background: var(--ambarv); color: var(--ambar); font: 700 12px var(--mono); }

/* Por entregar */
.ps-entregas { border-radius: 18px; overflow: hidden; color: #fff; background: var(--cred2); background-image: radial-gradient(120% 160% at 100% 0, rgba(166, 239, 226, .18), transparent 60%); }
.ps-ent-cab { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; padding: 14px 16px 10px; }
.ps-ent-cab > .bi { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; background: rgba(255, 255, 255, .1); color: var(--foil); font-size: 18px; }
.ps-ent-cab > div { flex: 1 1 200px; min-width: 0; display: flex; flex-direction: column; }
.ps-ent-cab b { font: 700 17px/1.2 var(--disp); }
.ps-ent-cab > div span { font-size: 12.5px; color: #b9d8d2; }
.ps-folio { display: flex; align-items: center; height: 40px; border-radius: 11px; overflow: hidden; background: rgba(255, 255, 255, .1); border: 1px solid rgba(166, 239, 226, .25); }
.ps-folio > span { padding: 0 4px 0 12px; font: 600 13px var(--mono); color: var(--foil); }
.ps-folio input { width: 120px; height: 100%; border: 0; outline: 0; background: none; color: #fff; font: 600 13px var(--mono); text-transform: uppercase; }
.ps-folio input::placeholder { color: rgba(255, 255, 255, .45); text-transform: none; }
.ps-folio button { height: 100%; display: inline-flex; align-items: center; gap: 6px; padding: 0 13px; border: 0; background: var(--foil); color: var(--cred2); font: 650 13px var(--ui); }
.ps-folio button:disabled { opacity: .5; }
.ps-ent-lista { display: flex; gap: 8px; overflow-x: auto; padding: 0 16px 14px; scrollbar-width: thin; }
.ps-ent { flex: 0 0 auto; min-width: 270px; display: flex; align-items: center; gap: 10px; padding: 10px 10px 10px 12px; border-radius: 12px; background: rgba(255, 255, 255, .07); border: 1px solid rgba(166, 239, 226, .16); transition: opacity .2s, transform .2s; }
.ps-ent.sale { opacity: 0; transform: translateY(-6px); }
.ps-ent > div { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.ps-ent > div b { font: 600 13px var(--mono); color: #fff; }
.ps-ent > div span { font-size: 12px; color: #b9d8d2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px; }
.ps-ent .ps-din { color: #fff; }
.ps-ent .ps-mini { background: var(--foil); color: var(--cred2); }

/* Escenario */
.ps-esc { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; align-items: start; }
@container (min-width: 860px) { .ps-esc { grid-template-columns: minmax(0, 1fr) 330px; } .ps-pedido { position: sticky; top: 16px; } }
@container (min-width: 1100px) { .ps-esc { grid-template-columns: minmax(0, 1fr) 360px; } }
@container (min-width: 1240px) { .ps-esc { grid-template-columns: minmax(0, 1fr) 390px; } }

/* Catálogo */
.ps-cat { min-width: 0; display: grid; gap: 12px; }
.ps-filtros { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; }
.ps-seg { display: inline-flex; flex-wrap: wrap; gap: 2px; padding: 3px; border-radius: 11px; background: var(--papel); border: 1px solid var(--linea); min-width: 0; }
.ps-seg button { height: 30px; display: inline-flex; align-items: center; gap: 6px; padding: 0 10px; border: 0; border-radius: 8px; background: none; font: 600 12.5px var(--ui); color: var(--media); white-space: nowrap; }
.ps-seg button i { width: 7px; height: 7px; border-radius: 50%; }
.ps-seg button small { font: 600 11px var(--mono); opacity: .7; }
.ps-seg button[aria-pressed=true] { background: var(--tinta); color: #fff; }
.ps-chk { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 600; color: var(--texto); cursor: pointer; }
.ps-chk input { width: 16px; height: 16px; accent-color: var(--cred); }
.ps-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(176px, 1fr)); gap: 10px; }
.ps-prod { position: relative; display: flex; flex-direction: column; padding: 0; border-radius: 16px; border: 1px solid var(--linea); background: var(--papel); text-align: left; overflow: hidden; color: inherit;
    box-shadow: 0 14px 30px -28px rgba(11, 30, 28, .55); transition: border-color .15s, box-shadow .15s, transform .15s var(--e); animation: ps-in .18s var(--e) backwards; }
.ps-prod:hover:not(:disabled) { border-color: #b9d4cf; transform: translateY(-2px); box-shadow: 0 18px 34px -24px rgba(11, 30, 28, .5); }
.ps-prod:active:not(:disabled) { transform: translateY(0) scale(.985); }
.ps-prod.en { border-color: var(--cred); box-shadow: inset 0 0 0 1px var(--cred), 0 18px 34px -26px rgba(11, 90, 84, .6); }
.ps-prod.golpe .ps-mesa { animation: ps-golpe .26s var(--e); }
.ps-prod:disabled { opacity: .55; }
.ps-prod:disabled .ps-art { filter: grayscale(1); }
@keyframes ps-in { from { opacity: 0; transform: translateY(6px); } }
@keyframes ps-golpe { 40% { transform: scale(.42) translateY(-16px); } }
.ps-art { position: relative; height: 104px; display: grid; place-items: center; overflow: hidden; background: radial-gradient(90% 90% at 50% 20%, #f7fbfa, #e7efed); border-bottom: 1px solid var(--linea2); }
.ps-art::after { content: ''; position: absolute; left: 18%; right: 18%; bottom: 12px; height: 10px; border-radius: 50%; background: rgba(11, 30, 28, .14); filter: blur(5px); }
.ps-mesa { position: relative; z-index: 1; width: 0; height: 0; perspective: 700px; transform: scale(.4); transition: transform .2s var(--e); }
.ps-prod:hover:not(:disabled) .ps-mesa { transform: scale(.43) translateY(-6px); }
.ps-mesa .iv-caja { position: absolute; left: 0; top: 0; margin: calc(var(--h) / -2) 0 0 calc(var(--w) / -2); }
.ps-info { display: flex; flex-direction: column; gap: 2px; padding: 10px 12px 11px; min-width: 0; }
.ps-cat-n { display: flex; align-items: center; gap: 6px; font: 600 10.5px var(--mono); letter-spacing: .05em; text-transform: uppercase; color: var(--media); min-width: 0; }
.ps-cat-n i { flex: none; width: 7px; height: 7px; border-radius: 50%; }
.ps-cat-n span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ps-info > b { font: 700 15px/1.2 var(--disp); color: var(--tinta); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ps-dosis { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ps-pie { display: flex; align-items: baseline; justify-content: space-between; gap: 6px; margin-top: 6px; }
.ps-precio { font: 700 16px var(--mono); color: var(--tinta); }
.ps-disp { font-size: 12px; font-weight: 600; color: var(--verde); white-space: nowrap; }
.s-bajo .ps-disp { color: var(--ambar); }
.s-agotado .ps-disp { color: var(--rosa); }
.ps-cant { position: absolute; top: 8px; right: 8px; z-index: 2; min-width: 30px; height: 26px; padding: 0 8px; border-radius: 99px; display: grid; place-items: center; background: var(--cred); color: #fff; font: 700 12.5px var(--mono); box-shadow: 0 6px 14px -6px rgba(11, 90, 84, .7); }
.ps-vacio { padding: 34px 16px; text-align: center; display: grid; justify-items: center; gap: 6px; color: var(--media); }
.ps-vacio b { font: 700 16px var(--disp); color: var(--tinta); }
.ps-vacio button { border: 0; background: none; font: 600 13.5px var(--ui); color: var(--cred); }

/* Pedido: papel térmico con borde dentado */
.ps-pedido { min-width: 0; filter: drop-shadow(0 22px 30px rgba(11, 30, 28, .14)); }
.ps-papel { --diente: 9px; position: relative; display: flex; flex-direction: column; background: var(--termico); padding: 18px 18px 22px;
    -webkit-mask: radial-gradient(circle at 50% 0, transparent 5px, #000 5.5px) 0 0 / var(--diente) 51% repeat-x, radial-gradient(circle at 50% 100%, transparent 5px, #000 5.5px) 0 100% / var(--diente) 51% repeat-x;
            mask: radial-gradient(circle at 50% 0, transparent 5px, #000 5.5px) 0 0 / var(--diente) 51% repeat-x, radial-gradient(circle at 50% 100%, transparent 5px, #000 5.5px) 0 100% / var(--diente) 51% repeat-x; }
.ps-p-cab { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 4px 0 12px; border-bottom: 1px dashed #d8d4c6; }
.ps-p-cab span { font: 600 11px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--media); }
.ps-p-cab button { border: 0; background: none; padding: 0; font: 600 12.5px var(--ui); color: var(--rosa); }
.ps-pac { position: relative; padding: 12px 0; border-bottom: 1px dashed #d8d4c6; }
.ps-p-lab { display: block; margin-bottom: 5px; font: 600 10.5px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--media); }
.ps-pac-sel { width: 100%; height: 40px; display: flex; align-items: center; gap: 9px; padding: 0 12px; border-radius: 11px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); text-align: left; }
.ps-pac-sel span { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ps-pac-sel .bi:first-child { color: var(--cred); }
.ps-pac-sel .bi:last-child { color: var(--media); font-size: 12px; }
.ps-pac-menu { position: absolute; left: 0; right: 0; top: calc(100% - 6px); z-index: 30; display: grid; gap: 2px; padding: 6px; max-height: 280px; overflow-y: auto; border-radius: 13px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 24px 40px -18px rgba(11, 30, 28, .35); }
.ps-pac-menu input { height: 38px; margin-bottom: 4px; padding: 0 10px; border-radius: 9px; border: 1px solid var(--linea); outline: 0; font: 500 14px var(--ui); }
.ps-pac-menu input:focus { border-color: var(--teal); }
.ps-pac-menu button { display: flex; align-items: center; gap: 8px; min-height: 36px; padding: 6px 9px; border: 0; border-radius: 8px; background: none; text-align: left; font: 500 13.5px var(--ui); color: var(--tinta); }
.ps-pac-menu button span { flex: 1; min-width: 0; }
.ps-pac-menu button small { font: 500 11px var(--mono); color: var(--media); }
.ps-pac-menu button .bi { color: var(--media); }
.ps-pac-menu button:hover { background: var(--niebla); }
.ps-pac-menu button.si { background: var(--velo); color: var(--cred); }
.ps-pac-menu p { padding: 8px; font-size: 13px; color: var(--media); }
.ps-lineas { list-style: none; margin: 0; padding: 4px 0 0; display: grid; max-height: min(46vh, 420px); overflow-y: auto; }
.ps-linea { display: grid; grid-template-columns: minmax(0, 1fr) auto; grid-template-areas: "n sub" "paso sub"; gap: 6px 10px; padding: 10px 0; border-bottom: 1px dotted #d8d4c6; animation: ps-imprime .2s var(--e); }
@keyframes ps-imprime { from { opacity: 0; transform: translateY(-8px); clip-path: inset(0 0 100% 0); } to { clip-path: inset(0 0 0 0); } }
.ps-l-n { grid-area: n; min-width: 0; display: flex; flex-direction: column; }
.ps-l-n b { font-size: 14px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ps-l-n span { font: 500 12px var(--mono); color: var(--media); }
.ps-paso { grid-area: paso; display: inline-flex; align-items: center; gap: 2px; justify-self: start; padding: 2px; border-radius: 10px; background: var(--niebla); }
.ps-paso button { width: 28px; height: 28px; display: grid; place-items: center; border: 0; border-radius: 8px; background: none; color: var(--tinta); font-size: 15px; }
.ps-paso button:hover:not(:disabled) { background: var(--papel); }
.ps-paso button:disabled { opacity: .35; }
.ps-paso button .bi-trash3 { font-size: 13px; color: var(--rosa); }
.ps-paso b { min-width: 26px; text-align: center; font: 700 14px var(--mono); color: var(--tinta); }
.ps-l-sub { grid-area: sub; align-self: center; font: 700 14.5px var(--mono); color: var(--tinta); }
.ps-p-vacio { display: grid; justify-items: center; gap: 4px; padding: 30px 10px; text-align: center; color: var(--media); }
.ps-p-vacio .bi { font-size: 30px; color: #b9cbc7; }
.ps-p-vacio b { font: 700 16px var(--disp); color: var(--tinta); }
.ps-p-vacio span { font-size: 13px; }
.ps-p-pie { display: grid; gap: 10px; padding-top: 12px; margin-top: 4px; border-top: 1px dashed #d8d4c6; }
.ps-p-res { display: flex; justify-content: space-between; font: 500 12px var(--mono); color: var(--media); }
.ps-total { display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; }
.ps-total span { font: 600 10.5px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--media); padding-bottom: 4px; }
.ps-total b { font: 800 40px/.9 var(--disp); font-stretch: 82%; letter-spacing: -.03em; color: var(--tinta); white-space: nowrap; }
.ps-total b small { font-size: .45em; color: var(--media); letter-spacing: 0; }
.ps-generar { height: 52px; display: flex; align-items: center; justify-content: center; gap: 10px; border: 0; border-radius: 14px; background: var(--cred); color: #fff; font: 650 15.5px var(--ui); box-shadow: 0 14px 26px -16px rgba(11, 90, 84, .9); transition: background .15s, transform .1s; }
.ps-generar:hover:not(:disabled) { background: var(--cred2); }
.ps-generar:active:not(:disabled) { transform: scale(.985); }
.ps-generar:disabled { background: #b9c6c3; box-shadow: none; }
.ps-generar .bi { font-size: 18px; color: var(--foil); }
.ps-generar kbd { border-color: rgba(255, 255, 255, .3); color: #cfe; }
.ps-nota { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: var(--media); }

/* Piezas comunes */
.ps-din { font: 600 14px var(--mono); color: var(--tinta); white-space: nowrap; }
.ps-mini { flex: none; height: 32px; display: inline-flex; align-items: center; gap: 6px; padding: 0 12px; border: 0; border-radius: 9px; background: var(--verde); color: #fff; font: 650 12.5px var(--ui); white-space: nowrap; }
.ps-mini:disabled { opacity: .6; }
.ps-ico { flex: none; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); color: var(--media); }
.ps-ico:hover { color: var(--cred); border-color: #b9d4cf; background: var(--velo); }
.ps-edo { flex: none; display: inline-flex; align-items: center; gap: 6px; height: 22px; padding: 0 9px; border-radius: 99px; font: 600 12px var(--ui); color: var(--c); background: color-mix(in srgb, var(--c) 11%, #fff); }
.ps-edo::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.ps-edo.pendiente { --c: var(--ambar); } .ps-edo.pagado { --c: var(--verde); } .ps-edo.entregado { --c: var(--cred); } .ps-edo.expirado, .ps-edo.cancelado { --c: var(--media); }

/* Modales */
.psm-velo { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; padding: 16px; background: rgba(4, 18, 17, .6); backdrop-filter: blur(4px); }
.psm-t { transition: opacity .16s cubic-bezier(.2, .8, .2, 1); } .psm-t-s { transition: opacity .12s ease-in; } .psm-0 { opacity: 0; } .psm-1 { opacity: 1; }
.psm {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    width: min(560px, 100%); max-height: calc(100vh - 32px); display: flex; flex-direction: column; overflow: hidden; border-radius: 20px; background: var(--papel);
    box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .55); font: 450 14px/1.45 var(--ui); color: var(--texto); animation: psm-in .2s cubic-bezier(.2, .8, .2, 1);
}
.psm.ancho { width: min(720px, 100%); }
@keyframes psm-in { from { transform: translateY(10px) scale(.985); } }
.psm-cab { flex: none; display: flex; align-items: center; gap: 12px; padding: 16px 18px; color: #fff; background: var(--cred2); background-image: radial-gradient(120% 140% at 100% 0, rgba(166, 239, 226, .2), transparent 60%); }
.psm-cab .ic { flex: none; width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: rgba(255, 255, 255, .1); color: var(--foil); font-size: 18px; }
.psm-cab > div { flex: 1; min-width: 0; }
.psm-cab h3 { font: 700 18px/1.2 var(--disp); color: #fff; }
.psm-cab p { font: 500 12px var(--mono); letter-spacing: .04em; color: var(--foil); }
.psm-cab .cerrar { flex: none; width: 34px; height: 34px; border-radius: 10px; border: 0; background: rgba(255, 255, 255, .08); color: #fff; }
.psm-cab .cerrar:hover { background: rgba(255, 255, 255, .18); }
.psm-filtros { flex: none; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 18px; border-bottom: 1px solid var(--linea2); }
.psm-in { flex: 1 1 220px; min-width: 0; height: 40px; padding: 0 12px; border-radius: 11px; border: 1px solid var(--linea); outline: 0; font: 500 14px var(--ui); color: var(--tinta); }
.psm-in:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .13); }
.psm .ps-seg { background: var(--niebla); border: 0; }
.psm .ps-seg button[aria-pressed=true] { background: var(--papel); color: var(--tinta); box-shadow: 0 1px 3px rgba(11, 30, 28, .12); }
.psm-cuerpo { flex: 1; min-height: 0; overflow-y: auto; }
.ps-tk { display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-bottom: 1px solid var(--linea2); }
.ps-tk:last-child { border-bottom: 0; }
.ps-tk-n { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.ps-tk-n b { font: 600 14px var(--mono); color: var(--tinta); }
.ps-tk-n span { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ps-tk .ps-edo { width: 96px; justify-content: center; }
@media (max-width: 560px) { .ps-tk { flex-wrap: wrap; } .ps-tk .ps-edo { width: auto; } .ps-tk-n { flex: 1 1 60%; } }

.ps-barra-m { display: none; }
/* Fuera de .ps (teletransportada): .ps contiene el diseño y un position: fixed quedaría atrapado dentro */
@media (max-width: 1180px) {
    .ps-barra-m { position: fixed; left: 16px; right: 84px; bottom: 18px; z-index: 40; height: 52px; display: flex; align-items: center; gap: 10px; padding: 0 16px; border: 0; border-radius: 15px; cursor: pointer;
        background: #0b1e1c; color: #fff; font: 600 14px 'Geist', ui-sans-serif, system-ui, sans-serif; box-shadow: 0 18px 34px -14px rgba(4, 18, 17, .6); }
    .ps-barra-m span { flex: 1; text-align: left; }
    .ps-barra-m b { font: 700 16px 'Geist Mono', ui-monospace, monospace; }
    .ps-barra-m .bi:first-child { color: #a6efe2; font-size: 18px; }
}
@container (max-width: 640px) {
    .ps-cmd > kbd, .ps-generar kbd { display: none; }
    .ps-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .ps-art { height: 88px; }
    .ps-mesa { transform: scale(.34); }
    .ps-folio { flex: 1 1 100%; } .ps-folio input { flex: 1; width: auto; }
}
@media (prefers-reduced-motion: reduce) { .ps-prod, .ps-linea, .psm, .ps-prod.golpe .ps-mesa { animation: none !important; } }
</style>

<script>
function posApp() {
    const D = JSON.parse(document.getElementById('ps-datos').textContent);
    const C = window.MTCaja;
    const norm = s => C ? C.normalizar(s) : String(s || '').toLowerCase();
    const LUZ = C ? C.luces(-14, -30) : '';
    const VIGENCIA = 25 * 60 * 1000;

    return {
        P: D.productos, PAC: D.pacientes, T: D.tickets, CLINICA: D.clinica,
        ESTADOS: [{ k: '', t: 'Todos' }, { k: 'pendiente', t: 'Por cobrar' }, { k: 'pagado', t: 'Pagados' }, { k: 'entregado', t: 'Entregados' }, { k: 'expirado', t: 'Vencidos' }],
        q: '', cat: '', soloDisp: false,
        carrito: [], paciente: null, pacAbierto: false, pacQ: '',
        cargando: false, entregando: null, codigoRapido: '',
        historial: false, tq: '', filtroEstado: '',
        openTicketModal: false, ticketGenerado: null,
        ahora: Date.now(), golpe: null, destelloBuscar: false,

        init() {
            setInterval(() => { this.ahora = Date.now(); }, 1000);
        },
        aviso(m, i = 'success') {
            if (typeof window.notificar === 'function') return window.notificar(m, i);
            if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: i, title: m });
        },
        tecla(e) {
            const enCampo = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;
            if (this.openTicketModal || this.historial || document.querySelector('.swal2-popup:not(.swal2-toast)')) return;
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.generar(); return; }
            if (enCampo || e.ctrlKey || e.metaKey || e.altKey) return;
            if (e.key === '/') { e.preventDefault(); this.$refs.buscar.focus(); }
        },

        // ----- Catálogo -----
        get categorias() {
            const m = {};
            this.P.forEach(p => { m[p.categoria] = (m[p.categoria] || 0) + 1; });
            return Object.keys(m).sort((a, b) => m[b] - m[a] || a.localeCompare(b)).map(n => ({ n, total: m[n], color: this.color({ categoria: n }) }));
        },
        get filtrados() {
            const q = norm(this.q);
            return this.P.filter(p => (!this.cat || p.categoria === this.cat) && (!this.soloDisp || p.disponible > 0)
                && (!q || norm(p.nombre + ' ' + p.codigo + ' ' + p.categoria).includes(q)));
        },
        // Enter en el buscador: un lector de código manda el código y Enter; si coincide exacto se agrega y se limpia
        escanear() {
            const q = this.q.trim().toUpperCase();
            if (!q) return;
            const exacto = this.P.find(p => p.codigo === q);
            const unico = this.filtrados.length === 1 ? this.filtrados[0] : null;
            const p = exacto || unico;
            if (!p) { this.aviso('No hay un producto con el código ' + q + '.', 'warning'); return; }
            if (this.agregar(p)) {
                this.q = '';
                this.destelloBuscar = true;
                setTimeout(() => { this.destelloBuscar = false; }, 260);
            }
        },

        // ----- Pedido -----
        enCarrito(p) { const it = this.carrito.find(x => x.id === p.id); return it ? it.cantidad : 0; },
        disponibleAhora(p) { return Math.max(0, p.disponible - this.enCarrito(p)); },
        agregar(p) {
            if (p.disponible <= 0) { this.aviso(this.titulo(p.nombre) + ' está agotado.', 'warning'); return false; }
            const it = this.carrito.find(x => x.id === p.id);
            if (it) {
                if (it.cantidad >= it.max) { this.aviso('Solo hay ' + it.max + ' piezas disponibles de ' + this.titulo(p.nombre) + '.', 'warning'); return false; }
                it.cantidad++;
            } else {
                this.carrito.push({ id: p.id, nombre: p.nombre, precio: p.venta, cantidad: 1, max: p.disponible });
            }
            this.golpe = null;
            this.$nextTick(() => { this.golpe = p.id; setTimeout(() => { if (this.golpe === p.id) this.golpe = null; }, 280); });
            return true;
        },
        cambiar(i, d) {
            const it = this.carrito[i];
            if (!it) return;
            if (it.cantidad + d <= 0) { this.carrito.splice(i, 1); return; }
            if (it.cantidad + d > it.max) return;
            it.cantidad += d;
        },
        vaciar() { this.carrito = []; this.paciente = null; },
        get total() { return this.carrito.reduce((s, i) => s + i.precio * i.cantidad, 0); },
        get piezasCarrito() { return this.carrito.reduce((s, i) => s + i.cantidad, 0); },
        get pacientesFiltrados() {
            const q = norm(this.pacQ);
            return (q ? this.PAC.filter(p => norm(p.nombre + ' ' + p.codigo).includes(q)) : this.PAC).slice(0, 8);
        },
        elegirPaciente(p) { this.paciente = p; this.pacAbierto = false; this.pacQ = ''; },

        async generar() {
            if (!this.carrito.length || this.cargando) return;
            this.cargando = true;
            try {
                const r = await fetch(D.url.generar, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.csrf },
                    body: JSON.stringify({ paciente_id: this.paciente ? this.paciente.id : null, carrito: this.carrito.map(i => ({ id: i.id, cantidad: i.cantidad })) }),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || data.status !== 'success') {
                    const err = data.errors ? Object.values(data.errors)[0][0] : null;
                    throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : this.frase(err || data.message || 'No se pudo generar el ticket.'));
                }
                this.T.unshift(data.ticket);
                if (Array.isArray(data.productos)) this.refrescar(data.productos);
                this.ticketGenerado = data.ticket;
                this.carrito = []; this.paciente = null;
                this.openTicketModal = true;
            } catch (e) {
                this.aviso(e.message, 'error');
            } finally {
                this.cargando = false;
            }
        },
        // El servidor devuelve los productos con su nuevo disponible (ya descontando lo apartado)
        refrescar(lista) {
            const m = {};
            lista.forEach(p => { m[p.id] = p; });
            this.P.forEach(p => {
                const n = m[p.id];
                if (!n) return;
                p.disponible = Number(n.stock_disponible ?? n.stock_actual ?? p.disponible) || 0;
                p.fisico = Number(n.stock_actual ?? p.fisico) || 0;
                p.estado = p.disponible <= 0 ? 'agotado' : (p.disponible <= p.minimo ? 'bajo' : 'ok');
            });
        },

        // ----- Entregas -----
        get porEntregar() { return this.T.filter(t => this.estadoDe(t) === 'pagado'); },
        get pendientesVivos() { return this.T.filter(t => this.estadoDe(t) === 'pendiente'); },
        async entregarPorCodigo() {
            const s = this.codigoRapido.trim().toUpperCase();
            if (!s) return;
            const ok = await this.entregarCodigo(s.startsWith('TCK-') ? s : 'TCK-' + s);
            if (ok) this.codigoRapido = '';
        },
        async entregar(t) { this.entregando = t.id; await this.entregarCodigo(this.folio(t), t); this.entregando = null; },
        async entregarCodigo(codigo, t = null) {
            if (!t) this.entregando = true;
            try {
                const r = await fetch(D.url.entregar + '/' + encodeURIComponent(codigo), {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.csrf },
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || data.status !== 'success') throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : this.frase(data.message || 'No se pudo entregar el ticket.'));
                const tk = t || this.T.find(x => this.folio(x) === codigo);
                if (tk) { tk._saliendo = true; setTimeout(() => { tk.status = 'entregado'; tk._saliendo = false; }, 200); }
                this.aviso('Ticket ' + codigo + ' entregado; se descontó del inventario.');
                return true;
            } catch (e) {
                this.aviso(e.message, 'error');
                return false;
            } finally {
                if (!t) this.entregando = null;
            }
        },

        // ----- Historial -----
        abrirHistorial() { this.historial = true; this.$nextTick(() => this.$refs.buscarTck && this.$refs.buscarTck.focus()); },
        estadoDe(t) {
            const s = String(t.status || '').toLowerCase();
            if (s === 'pendiente' && t.expires_at && new Date(t.expires_at).getTime() <= this.ahora) return 'expirado';
            return s;
        },
        textoEstado(s) { return { pendiente: 'Por cobrar', pagado: 'Pagado', entregado: 'Entregado', expirado: 'Vencido', cancelado: 'Cancelado' }[s] || s; },
        contarEstado(k) { return k ? this.T.filter(t => this.estadoDe(t) === k).length : this.T.length; },
        get ticketsFiltrados() {
            const q = norm(this.tq);
            return this.T.filter(t => (!this.filtroEstado || this.estadoDe(t) === this.filtroEstado)
                && (!q || norm(this.folio(t) + ' ' + this.nombrePaciente(t)).includes(q)));
        },
        ver(t) { this.ticketGenerado = t; this.historial = false; this.$nextTick(() => { this.openTicketModal = true; }); },

        // ----- Comprobante (modalTicket) -----
        restante(t) {
            if (!t || !t.expires_at) return '—';
            const ms = new Date(t.expires_at).getTime() - this.ahora;
            if (ms <= 0) return '0:00';
            const m = Math.floor(ms / 60000), s = Math.floor(ms % 60000 / 1000);
            return m + ':' + String(s).padStart(2, '0');
        },
        avanceVigencia(t) {
            if (!t || !t.expires_at) return 0;
            return Math.max(0, Math.min(100, (new Date(t.expires_at).getTime() - this.ahora) / VIGENCIA * 100));
        },
        formatearHoraExpiracion(f) { return this.hora(f); },
        lineas(t) {
            return (t && t.detalles ? t.detalles : []).map(d => ({
                id: d.id, cantidad: Number(d.cantidad) || 0,
                nombre: this.titulo(d.producto ? d.producto.nombre : 'Producto'),
                subtotal: Number(d.subtotal) || (Number(d.precio_unitario) || 0) * (Number(d.cantidad) || 0),
            }));
        },
        barrasDe(t) { return C && t ? C.barras(this.folio(t)) : ''; },
        imprimirTicketModal() { window.print(); },

        // ----- Formatos -----
        folio(t) { return t ? (t.codigo_ticket || t.codigo || '') : ''; },
        nombrePaciente(t) {
            const p = t && t.paciente;
            if (!p) return 'Público general';
            return this.titulo(p.nombre || p.nombre_completo || [p.primer_nombre, p.apellido_paterno, p.apellido_materno].filter(Boolean).join(' ')) || 'Paciente';
        },
        piezas(t) {
            const n = (t.detalles || []).reduce((s, d) => s + (Number(d.cantidad) || 0), 0);
            return n ? n + (n === 1 ? ' pieza' : ' piezas') : '';
        },
        hora(f) { return f ? new Date(f).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) : ''; },
        fecha(f) { return f ? new Date(f).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
        frase(m) { const s = String(m || '').trim(); return s === s.toUpperCase() ? s.charAt(0) + s.slice(1).toLowerCase() : s; },
        dinero(v) { return C ? C.dinero(v) : '$' + Number(v || 0).toFixed(2); },
        cifra(v) { const [e, c] = this.dinero(v).split('.'); return e + '<small>.' + (c || '00') + '</small>'; },
        titulo(t) { return C ? C.titulo(t) : t; },
        partes(p) { return C ? C.partes(p) : { principal: p.nombre, dosis: '', forma: '' }; },
        tipo(p) { return C ? C.tipoDe(p).clase : 'medicamento'; },
        color(p) { return C ? C.colorDe(p) : '#0b5a54'; },
        caja(p) { return C ? C.cajaHTML(p) : ''; },
        estiloCaja(p) { return '--k:' + this.color(p) + ';' + LUZ + ';transform:rotateX(-14deg) rotateY(-30deg)'; },
    };
}
</script>
@endverbatim
@endsection