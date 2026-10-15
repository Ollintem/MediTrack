@extends('layouts.admin')

{{--
    MÓDULO DE CONSULTORIOS · MediTrack
    Cifra con filtros por estado, plano 3D por piso (arrastrar para acomodar, clic para ver),
    placa del consultorio que se voltea para editar (consultorios/modal.blade.php) y lista.
    Guarda por AJAX: ConsultorioController responde JSON.
--}}

@section('content')
@php
    $datosConsultorios = [
        'url'          => url('consultorios'),
        'csrf'         => csrf_token(),
        'consultorios' => $consultoriosJs ?? collect(),
    ];
@endphp

<div class="co" x-data="modConsultorios()" :class="{ 'editando': editando }" @keydown.window="tecla($event)">

    <!-- BÚSQUEDA Y ALTA -->
    <header class="co-top">
        <label class="co-cmd">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="co-sr">Buscar consultorio</span>
            <input type="search" x-ref="buscar" x-model="q" autocomplete="off" placeholder="Busca un consultorio por nombre o piso">
            <kbd aria-hidden="true">/</kbd>
        </label>
        <button type="button" class="co-dark" @click="nuevo()">Nuevo consultorio <kbd>N</kbd></button>
    </header>

    <!-- CIFRA E INDICADORES (también filtran) -->
    <section class="co-cifra" aria-label="Resumen">
        <div class="co-n">
            <b class="t" x-text="P.length">0</b>
            <span>consultorios<br>en <span class="t" x-text="pisos.length"></span> <span x-text="pisos.length === 1 ? 'piso' : 'pisos'"></span></span>
        </div>
        <div class="co-ind">
            <template x-for="op in opciones" :key="'i' + op.slug">
                <button type="button" :class="'e-' + op.slug" @click="filtrar(op.slug)" :aria-pressed="(filtro === op.slug).toString()">
                    <span x-text="op.plural"></span>
                    <b class="t" x-text="contar(op.slug)"></b>
                    <i :style="'--p:' + (P.length ? contar(op.slug) / P.length * 100 : 0) + '%'"></i>
                </button>
            </template>
        </div>
        <p class="co-vivo" title="El estado se actualiza solo cada 15 segundos">
            <i></i>En vivo · <span class="t" x-text="actualizado || '—'"></span>
        </p>
    </section>

    <!-- PLANO + PLACA -->
    <section class="co-escenario">
        <div class="co-plano">
            <!-- Elevador: el piso más alto queda arriba -->
            <nav class="co-elev" aria-label="Pisos">
                <p>Pisos</p>
                <div class="co-elev-bt">
                    <template x-for="p in pisos" :key="'e' + p">
                        <div class="co-elev-p">
                            <button type="button" class="co-elev-piso" @click="cambiarPiso(p)" :aria-pressed="(piso === p).toString()"
                                    :title="etiquetaPiso(p) + ' · ' + contarPiso(p) + ' consultorios'">
                                <b :class="{ 'largo': String(p).length > 3 }" x-text="p === '' ? '—' : p"></b>
                                <small class="t" x-text="contarPiso(p)"></small>
                                <i x-show="enConsultaPiso(p) > 0" :title="enConsultaPiso(p) + ' en consulta'"></i>
                            </button>
                            <button type="button" class="co-elev-x" @click.stop="eliminarPiso(p)" :aria-label="'Quitar ' + etiquetaPiso(p)" :title="'Quitar ' + etiquetaPiso(p)"><i class="bi bi-x"></i></button>
                        </div>
                    </template>
                </div>
                <button type="button" class="co-elev-mas" @click="abrirPisoNuevo()" x-show="!pisoNuevo.abierto" aria-label="Agregar piso">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <form class="co-elev-nuevo" x-show="pisoNuevo.abierto" x-cloak @submit.prevent="agregarPiso()" @keydown.escape.stop="pisoNuevo.abierto = false">
                    <input type="text" x-ref="pisoNuevo" x-model="pisoNuevo.valor" maxlength="20" placeholder="Piso" aria-label="Nombre del piso nuevo">
                    <button type="submit" aria-label="Agregar piso"><i class="bi bi-check-lg"></i></button>
                    <small x-show="pisoNuevo.error" x-text="pisoNuevo.error"></small>
                </form>
            </nav>

            <div class="co-lienzo" x-ref="lienzo" :style="'height:' + Math.round(640 * escala) + 'px'">
                <div class="co-escena" :class="{ 'cambio': animarPiso }" :style="'transform:scale(' + escala + ')'">
                    <div class="co-rotulo">
                        <p x-text="etiquetaPiso(piso)"></p>
                        <span class="t" x-text="'Planta · ' + planoVisible.length + (planoVisible.length === 1 ? ' consultorio' : ' consultorios')"></span>
                    </div>

                    <div class="co-vacio-piso" x-show="!planoVisible.length" x-cloak>
                        <p><b>Este piso aún no tiene consultorios</b>Registra el primero y aparecerá aquí.</p>
                        <button type="button" class="co-btn co-prim" @click="nuevo()">Agregar consultorio aquí</button>
                    </div>

                    <div class="co-losa">
                        <div class="co-muro-t"></div><div class="co-muro-i"></div>
                        <div class="co-remate-t"></div><div class="co-remate-i"></div>
                        <div class="co-destino" :style="estiloDestino()"></div>

                        <template x-for="(c, i) in planoVisible" :key="c.id">
                            <div class="co-sala" :data-id="c.id"
                                 :class="'e-' + c.estado + (sel && sel.id === c.id ? ' sel' : '') + (pasaFiltro(c) ? '' : ' tenue') + (arrastre && arrastre.id === c.id && arrastre.movio ? ' alzada' : '')"
                                 :style="estiloSala(c, i)"
                                 :title="c.nombre + ' · ' + info(c.estado).texto + (c.ocupadoPor ? ' con ' + c.ocupadoPor : '')"
                                 @pointerdown="iniciarArrastre($event, c)" @pointermove="mover($event, c)"
                                 @pointerup="soltar(c)" @pointercancel="cancelarArrastre(c)">
                                <div class="co-s-piso"></div>
                                <div class="co-s-muro-t"></div><div class="co-s-muro-i"></div>
                                <div class="co-s-rem-t"></div><div class="co-s-rem-i"></div>
                                <div class="co-s-letrero"><div>
                                    <b x-text="c.nombre"></b>
                                    <span x-text="info(c.estado).corto"></span>
                                </div></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <footer class="co-plano-pie">
                <span class="co-ley e-disponible"><i></i>Disponible</span>
                <span class="co-ley e-ocupado"><i></i>En consulta</span>
                <span class="co-ley e-mantenimiento"><i></i>Mantenimiento</span>
                <span class="co-pista">Arrastra para acomodar · toca para ver su placa</span>
            </footer>
        </div>

        {{-- Placa del consultorio (frente) y su formulario (reverso) --}}
        @include('consultorios.modal')
    </section>

    <!-- LISTA -->
    <section class="co-lista" aria-labelledby="coListaT">
        <div class="co-lh">
            <h2 id="coListaT">Consultorios <small class="t" x-text="filtrados.length"></small></h2>
            <button type="button" @click="filtrar('todos')" :aria-pressed="(filtro === 'todos').toString()">Todos</button>
            <template x-for="op in opciones" :key="'l' + op.slug">
                <button type="button" @click="filtrar(op.slug)" :aria-pressed="(filtro === op.slug).toString()" x-text="op.plural"></button>
            </template>
        </div>
        <div class="co-filas">
            <template x-for="c in filtrados" :key="'r' + c.id">
                <div class="co-fila" :class="{ 'sel': sel && sel.id === c.id }">
                    <button type="button" class="co-fila-main" @click="seleccionar(c, true)">
                        <span class="co-puerta" :class="'e-' + c.estado"><b x-text="iniciales(c.nombre)"></b></span>
                        <span class="co-fila-n"><b x-text="c.nombre"></b><small x-text="etiquetaPiso(c.piso)"></small></span>
                        <span class="co-estado" :class="'e-' + c.estado"><i></i><span x-text="info(c.estado).texto"></span></span>
                        <span class="co-medico" x-text="c.estado === 'ocupado' ? (c.ocupadoPor ? 'Con ' + c.ocupadoPor : 'En uso') : '—'"></span>
                    </button>
                    <span class="co-fila-acc">
                        <button type="button" class="co-ico" @click="editar(c)" :aria-label="'Editar ' + c.nombre"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="co-ico co-ico-r" @click="eliminar(c)" :aria-label="'Eliminar ' + c.nombre"><i class="bi bi-trash3"></i></button>
                    </span>
                </div>
            </template>
            <div class="co-sin" x-show="!filtrados.length" x-cloak>
                <p x-show="P.length"><b>Ningún consultorio coincide</b>Prueba con otro nombre, piso o estado.</p>
                <p x-show="!P.length"><b>Aún no hay consultorios</b>Usa «Nuevo consultorio» para registrar el primero.</p>
            </div>
        </div>
    </section>
</div>

<script type="application/json" id="co-datos">{!! json_encode($datosConsultorios, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@verbatim
<style>
.co {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1); --flip: cubic-bezier(.3, .7, .1, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.co * { box-sizing: border-box; }
.co :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.co button { cursor: pointer; }
.co .t { font-variant-numeric: tabular-nums; }
.co-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.co kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); background: transparent; }
[x-cloak] { display: none !important; }

/* Estados (color fijo por significado) */
.co .e-disponible { --c: var(--verde); --cv: var(--verdev); }
.co .e-ocupado { --c: var(--cred); --cv: var(--velo); }
.co .e-mantenimiento { --c: var(--ambar); --cv: var(--ambarv); }
.co .e-desconocido, .co .e-vacio { --c: var(--media); --cv: var(--niebla); }

/* ---------- Búsqueda ---------- */
.co-top { display: flex; align-items: center; gap: 12px; }
.co-cmd { flex: 1; min-width: 0; max-width: 760px; height: 50px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 12px 30px -22px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 16px; }
.co-cmd > .bi { color: var(--cred); }
.co-cmd input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 450 15px var(--ui); color: var(--tinta); }
.co-cmd input::placeholder { color: var(--media); }
.co-dark { height: 44px; border-radius: 12px; background: var(--tinta); color: #fff; font: 600 14px var(--ui); display: flex; align-items: center; gap: 10px; padding: 0 12px 0 16px; white-space: nowrap; }
.co-dark kbd { border-color: rgba(255, 255, 255, .25); color: #cfe; }
@container (max-width: 639px) { .co-cmd > kbd, .co-dark kbd { display: none; } .co-dark { padding: 0 14px; } }

/* ---------- Cifra ---------- */
.co-cifra { display: flex; align-items: flex-end; gap: 28px; flex-wrap: wrap; }
.co-n { display: flex; align-items: flex-end; gap: 12px; }
.co-n > b { font: 800 88px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); }
.co-n > span { font-size: 15px; font-weight: 500; color: var(--texto); line-height: 1.25; padding-bottom: 2px; }
.co-ind { flex: 1; min-width: 280px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 20px; }
.co-ind button { display: grid; grid-template-columns: 1fr auto; align-items: end; padding: 6px 0 0; text-align: left; font-weight: 500; color: var(--texto); background: none; border-top: 1px solid var(--linea); }
.co-ind button span { font-size: 14px; padding-bottom: 4px; }
.co-ind button b { font: 750 26px/1 var(--disp); color: var(--c); }
.co-ind button i { grid-column: 1 / -1; height: 4px; border-radius: 2px; background: var(--linea); margin-top: 8px; position: relative; overflow: hidden; }
.co-ind button i:after { content: ""; position: absolute; inset: 0; width: var(--p); background: var(--c); border-radius: 2px; transition: width .6s var(--e); }
.co-ind button[aria-pressed=true] { border-top: 2px solid var(--c); }
.co-ind button[aria-pressed=true] span { color: var(--tinta); font-weight: 650; }
.co-vivo { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 500; color: var(--media); padding-bottom: 4px; }
.co-vivo i { width: 7px; height: 7px; border-radius: 50%; background: var(--verde); box-shadow: 0 0 0 3px var(--verdev); }
@container (max-width: 759px) { .co-n > b { font-size: 68px; } .co-ind { min-width: 100%; } }

/* ---------- Escenario ---------- */
.co-escenario { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
@container (min-width: 1040px) { .co-escenario { grid-template-columns: minmax(0, 1fr) 320px; } }

.co-plano { position: relative; display: grid; grid-template-columns: 76px minmax(0, 1fr); background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; box-shadow: 0 18px 40px -32px rgba(11, 30, 28, .5); transition: opacity .35s, filter .35s; }
.co-plano-pie { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: 8px 18px; padding: 12px 18px; border-top: 1px solid var(--linea2); font-size: 12px; font-weight: 500; color: var(--media); }
.co-ley { display: inline-flex; align-items: center; gap: 7px; }
.co-ley i { width: 12px; height: 12px; border-radius: 3px; border: 2px solid var(--c); background: var(--papel); }
.co-ley.e-ocupado i { background: var(--cred); }
.co-ley.e-mantenimiento i { background: repeating-linear-gradient(45deg, var(--ambarv) 0 3px, #f2d9a8 3px 6px); }
.co-pista { margin-left: auto; }
@container (max-width: 639px) { .co-pista { display: none; } }

/* Elevador de pisos */
.co-elev { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 14px 0; background: #f8fbfa; border-right: 1px solid var(--linea2); }
.co-elev > p { font-size: 12px; font-weight: 600; color: var(--media); }
.co-elev-bt { display: flex; flex-direction: column-reverse; gap: 8px; }
.co-elev-bt .co-elev-piso, .co-elev-mas { position: relative; width: 52px; height: 52px; border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1px; background: var(--papel); border: 1px solid var(--linea); color: var(--tinta); transition: background .15s, border-color .15s, color .15s, transform .15s; }
.co-elev-bt .co-elev-piso:hover { border-color: #b9cbc6; transform: translateY(-1px); }
.co-elev-bt b { font: 750 20px/1 var(--disp); font-stretch: 85%; max-width: 46px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.co-elev-bt b.largo { font-size: 12px; }
.co-elev-bt small { font: 500 11px var(--mono); color: var(--media); }
.co-elev-piso > i { position: absolute; top: 6px; right: 6px; width: 7px; height: 7px; border-radius: 50%; background: var(--cred); box-shadow: 0 0 0 2px var(--papel); }
.co-elev-bt .co-elev-piso[aria-pressed=true] { background: var(--cred); border-color: var(--cred); color: #fff; box-shadow: 0 10px 18px -10px rgba(7, 63, 59, .8); }
.co-elev-bt .co-elev-piso[aria-pressed=true] small { color: var(--foil); }
.co-elev-bt .co-elev-piso[aria-pressed=true] > i { background: var(--foil); box-shadow: 0 0 0 2px var(--cred); }
.co-elev-p { position: relative; }
.co-elev-x { position: absolute; top: -7px; left: -7px; z-index: 2; width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; padding: 0; border: 1px solid var(--linea); background: var(--papel); color: var(--media); font-size: 15px; box-shadow: 0 3px 8px rgba(11, 30, 28, .18); opacity: 0; scale: .6; pointer-events: none; transition: opacity .12s, scale .15s var(--e), color .12s, background .12s; }
.co-elev-p:hover .co-elev-x, .co-elev-x:focus-visible { opacity: 1; scale: 1; pointer-events: auto; }
.co-elev-x:hover { background: var(--rosav); color: var(--rosa); border-color: #f3c6cf; }
@media (hover: none) { .co-elev-piso[aria-pressed=true] + .co-elev-x { opacity: 1; scale: 1; pointer-events: auto; } }
.co-sw-p { font-size: 14.5px; color: #2c3d3a; margin: 0 0 12px; }
.co-sw-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; }
.co-sw-chip { height: 36px; padding: 0 14px; border-radius: 10px; border: 1px solid #dbe4e1; background: #fff; font: 600 13.5px 'Geist', sans-serif; color: #2c3d3a; cursor: pointer; }
.co-sw-chip.on { border-color: #0b5a54; background: #e2efec; color: #0b5a54; }
.co-sw-nuevo { width: 130px; height: 36px; padding: 0 10px; border-radius: 10px; border: 1px dashed #b9cbc6; font: 600 13.5px 'Geist', sans-serif; text-transform: uppercase; text-align: center; outline: 0; }
.co-sw-nuevo:focus { border-style: solid; border-color: #0e7c72; box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.co-sw-nuevo::placeholder { text-transform: none; }
.co-elev-mas { border-style: dashed; color: var(--media); background: transparent; }
.co-elev-mas:hover { color: var(--cred); border-color: var(--cred); }
.co-elev-nuevo { width: 64px; display: flex; flex-direction: column; gap: 4px; }
.co-elev-nuevo input { width: 100%; height: 36px; border: 1px solid var(--teal); border-radius: 10px; padding: 0 6px; font: 600 13px var(--ui); text-align: center; color: var(--tinta); outline: 0; }
.co-elev-nuevo button { height: 30px; border-radius: 9px; background: var(--cred); color: #fff; }
.co-elev-nuevo small { font-size: 11px; line-height: 1.2; color: var(--rosa); text-align: center; }

/* Lienzo escalado: el plano mide 900 x 640 y se ajusta al ancho disponible */
.co-lienzo { position: relative; overflow: hidden; background-color: #fbfdfc; background-image: radial-gradient(circle, #dfe8e5 1px, transparent 1px); background-size: 22px 22px; }
.co-escena { position: absolute; left: 0; top: 0; width: 900px; height: 640px; transform-origin: 0 0; user-select: none; }
.co-escena.cambio { animation: coPiso .45s var(--e); }
@keyframes coPiso { from { opacity: 0; translate: 0 16px; } }
.co-rotulo { position: absolute; left: 26px; top: 20px; pointer-events: none; }
.co-rotulo p { font: 800 56px/.9 var(--disp); font-stretch: 78%; letter-spacing: -.03em; color: #d5e0dc; }
.co-rotulo span { display: block; margin-top: 8px; font: 500 14px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: #7d8986; }
.co-vacio-piso { position: absolute; inset: 0; z-index: 30; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; text-align: center; font-size: 17px; color: var(--media); }
.co-vacio-piso b { display: block; font: 750 26px var(--disp); color: var(--tinta); margin-bottom: 4px; }
.co-vacio-piso .co-btn { height: 48px; font-size: 16px; padding: 0 20px; }

/* Losa isométrica (720 x 540) */
.co-losa { position: absolute; left: 90px; top: 64px; width: 720px; height: 540px; transform: rotateX(52deg) rotateZ(45deg); transform-style: preserve-3d; background-color: var(--niebla); background-image: radial-gradient(circle, #c9d6d2 1.2px, transparent 1.2px); background-size: 20px 20px; border: 1px solid #cfdcd8; border-radius: 6px; box-shadow: 0 12px 0 rgba(11, 30, 28, .06); }
.co-muro-t, .co-muro-i, .co-remate-t, .co-remate-i { position: absolute; pointer-events: none; }
.co-muro-t { left: 0; top: 0; width: 100%; height: 64px; background: #fff; transform-origin: 0 0; transform: rotateX(90deg); }
.co-muro-i { left: 0; top: 0; width: 64px; height: 100%; background: #e3ebe8; transform-origin: 0 0; transform: rotateY(-90deg); }
.co-remate-t { left: -7px; top: -7px; width: calc(100% + 7px); height: 7px; background: var(--tinta); transform: translateZ(64px); }
.co-remate-i { left: -7px; top: -7px; width: 7px; height: calc(100% + 7px); background: var(--tinta); transform: translateZ(64px); }
.co-destino { position: absolute; border-radius: 4px; pointer-events: none; }

/* Consultorio (maqueta: piso + dos muros) */
.co-sala { position: absolute; transform-style: preserve-3d; touch-action: none; cursor: grab; animation: coCae .6s cubic-bezier(.34, 1.3, .64, 1) backwards; animation-delay: var(--d, 0ms); transition: opacity .3s, filter .3s; }
@keyframes coCae { from { transform: translateZ(110px); } }
.co-sala > * { position: absolute; transform-style: preserve-3d; }
.co-sala:not(.alzada):hover { transform: translateZ(10px) !important; }
.co-sala.alzada { cursor: grabbing; }
.co-sala.tenue { opacity: .28; filter: saturate(.2); }
.co-s-piso { inset: 0; background: var(--papel); box-shadow: inset 0 0 0 2px var(--c); border-radius: 2px; }
.co-sala.e-disponible .co-s-piso { background: linear-gradient(135deg, #eef8f3, #cfeadd); }
.co-sala.e-ocupado .co-s-piso { background: linear-gradient(135deg, #0d6a63, var(--cred2)); box-shadow: inset 0 0 0 2px var(--cred2); }
.co-sala.e-mantenimiento .co-s-piso { background: repeating-linear-gradient(45deg, var(--ambarv) 0 8px, #f2d9a8 8px 16px); }
.co-sala.e-desconocido .co-s-piso { background: var(--niebla); }
.co-sala.sel .co-s-piso { box-shadow: inset 0 0 0 4px var(--teal); }
.co-sala.sel .co-s-letrero > div { box-shadow: 0 0 0 3px var(--teal), 0 8px 18px rgba(11, 30, 28, .3); }
.co-s-muro-t { left: 0; top: 0; width: 100%; height: 50px; background: linear-gradient(#fff, #f3f7f6); box-shadow: inset 0 0 0 1px #cfdcd8; transform-origin: 0 0; transform: rotateX(90deg); }
.co-s-muro-i { left: 0; top: 0; width: 50px; height: 100%; background: linear-gradient(90deg, #d6e1de, #e6eeeb); box-shadow: inset 0 0 0 1px #c3d1cd; transform-origin: 0 0; transform: rotateY(-90deg); }
.co-s-rem-t { left: -5px; top: -5px; width: calc(100% + 5px); height: 5px; background: var(--c); transform: translateZ(50px); }
.co-s-rem-i { left: -5px; top: -5px; width: 5px; height: calc(100% + 5px); background: var(--c); transform: translateZ(50px); }
.co-s-letrero { inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; transform: translateZ(1px); }
.co-s-letrero > div { transform: translateZ(30px) rotateZ(-45deg) rotateX(-52deg); display: flex; flex-direction: column; align-items: center; gap: 2px; max-width: 118px; padding: 5px 9px 6px; text-align: center; line-height: 1.1; background: var(--papel); border-radius: 8px; box-shadow: 0 2px 8px rgba(11, 30, 28, .22); }
.co-s-letrero b { font: 700 16px/1.05 var(--disp); font-stretch: 85%; color: var(--tinta); word-break: break-word; }
.co-s-letrero span { font: 600 12px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: var(--c); }
.co-sala.e-ocupado .co-s-letrero > div { background: var(--tinta); }
.co-sala.e-ocupado .co-s-letrero b { color: #fff; }
.co-sala.e-ocupado .co-s-letrero span { color: var(--foil); }

/* ---------- Placa (frente y reverso) ---------- */
.co-lado { display: grid; gap: 14px; align-content: start; }
@container (min-width: 640px) and (max-width: 1039px) { .co-lado { grid-template-columns: 320px minmax(0, 1fr); align-items: start; } }
.co-placa { perspective: 1400px; }
.co-placa-in { position: relative; height: 360px; transform-style: preserve-3d; transition: transform .45s var(--flip); }
.co-placa.volteada .co-placa-in { transform: rotateY(180deg); }
.co-cara { position: absolute; inset: 0; border-radius: 22px; overflow: hidden; backface-visibility: hidden; -webkit-backface-visibility: hidden; }

.co-frente { color: #eafaf6; background: linear-gradient(155deg, #0d6a63, var(--cred) 45%, var(--cred2)); box-shadow: 0 24px 44px -26px rgba(7, 63, 59, .9), inset 0 0 0 1px rgba(166, 239, 226, .18); }
.co-gl, .co-gl svg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.co-fr { position: relative; height: 100%; display: flex; flex-direction: column; padding: 20px 22px 0; }
.co-fr header { display: flex; align-items: center; justify-content: space-between; }
.co-k { font: 500 12px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: var(--foil); }
.co-k b { font-weight: 600; color: #fff; }
.co-cruz { width: 30px; height: 30px; fill: rgba(166, 239, 226, .85); }
.co-fr h2 { margin-top: 18px; font: 780 34px/1 var(--disp); font-stretch: 78%; letter-spacing: -.01em; color: #fff; text-transform: uppercase; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.co-fr dl { margin-top: auto; display: grid; grid-template-columns: repeat(3, auto); justify-content: start; gap: 4px 22px; padding-bottom: 16px; }
.co-fr dt { font-size: 12px; font-weight: 500; color: #a9d8cf; }
.co-fr dd { font: 650 15px var(--ui); color: #fff; white-space: nowrap; }
.co-franja { margin: 0 -22px; display: flex; align-items: center; gap: 10px; min-height: 48px; padding: 10px 22px; font-size: 13.5px; font-weight: 600; color: #fff; background: rgba(4, 36, 33, .55); border-top: 1px solid rgba(166, 239, 226, .2); }
.co-frente.e-disponible .co-franja { background: rgba(8, 115, 79, .55); }
.co-frente.e-mantenimiento .co-franja { background: repeating-linear-gradient(45deg, rgba(154, 88, 0, .75) 0 10px, rgba(120, 66, 0, .75) 10px 20px); }
.co-fr-vacio h2 { text-transform: none; font-size: 30px; }
.co-fr-vacio p { margin-top: 10px; font-size: 14px; color: #cdeee7; }

.co-reverso { transform: rotateY(180deg); background: #fbfdfc; border: 1px solid var(--linea); box-shadow: 0 24px 44px -30px rgba(11, 30, 28, .55); padding: 18px 18px 0; display: flex; flex-direction: column; gap: 12px; }
.co-rv-k { font: 500 12px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: var(--cred); }
.co-campo { display: flex; flex-direction: column; gap: 5px; }
.co-campo > span { font-size: 13px; font-weight: 600; color: var(--tinta); }
.co-campo input { height: 40px; border: 1px solid var(--linea); border-radius: 10px; padding: 0 12px; font: 500 14px var(--ui); color: var(--tinta); background: var(--papel); outline: 0; transition: border-color .15s, box-shadow .15s; }
.co-campo input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.co-campo.mal input { border-color: var(--rosa); }
.co-campo small { font-size: 12px; font-weight: 500; color: var(--rosa); }
.co-campo small.co-nota { color: var(--media); }
.co-pisos-op { display: flex; flex-wrap: wrap; gap: 6px; }
.co-pisos-op input { width: 86px; }
.co-chip { height: 40px; min-width: 40px; padding: 0 10px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--texto); }
.co-chip.on { border-color: var(--cred); background: var(--velo); color: var(--cred); }
.co-rv-pie { margin: auto -18px 0; display: flex; align-items: center; gap: 8px; padding: 12px 18px; border-top: 1px solid var(--linea2); background: var(--papel); }
.co-msg { flex: 1; min-width: 0; font-size: 12px; color: var(--media); }

/* Selector de estado */
.co-seg { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
.co-seg button { height: 38px; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 0 4px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 12.5px var(--ui); color: var(--texto); white-space: nowrap; transition: border-color .15s, background .15s; }
.co-seg button i { flex: none; width: 7px; height: 7px; border-radius: 50%; background: var(--c); }
.co-seg button[aria-checked=true] { border-color: var(--c); background: var(--cv); color: var(--c); }
.co-seg button:disabled { opacity: .4; cursor: not-allowed; }

/* Botones */
.co-btn { height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 16px; border-radius: 11px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); transition: background .15s, border-color .15s; }
.co-btn:hover { background: #f7faf9; }
.co-prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.co-prim:hover { background: var(--cred2); }
.co-prim:disabled { opacity: .6; cursor: progress; }
.co-prim kbd { border-color: rgba(255, 255, 255, .3); color: #cfe; }
.co-borrar { color: var(--rosa); border-color: #f3c6cf; }
.co-borrar:hover { background: var(--rosav); }
.co-ancho { width: 100%; }
.co-acc-in { display: grid; gap: 10px; }
.co-acc-t { font-size: 13px; font-weight: 600; color: var(--media); }

/* Edición: todo lo demás se atenúa */
.co.editando .co-plano, .co.editando .co-lista, .co.editando .co-cifra { opacity: .35; filter: saturate(.6); pointer-events: none; }

/* ---------- Lista ---------- */
.co-lista { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; transition: opacity .35s, filter .35s; }
.co-lh { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 16px 18px; border-bottom: 1px solid var(--linea2); }
.co-lh h2 { margin-right: auto; font: 750 20px var(--disp); color: var(--tinta); }
.co-lh h2 small { font: 500 13px var(--ui); color: var(--media); margin-left: 6px; }
.co-lh button { height: 34px; padding: 0 14px; border-radius: 999px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--texto); }
.co-lh button[aria-pressed=true] { background: var(--tinta); border-color: var(--tinta); color: #fff; }
.co-fila { display: flex; align-items: center; border-bottom: 1px solid var(--linea2); }
.co-fila:last-child { border-bottom: 0; }
.co-fila.sel { background: linear-gradient(90deg, rgba(14, 124, 114, .08), transparent 60%); box-shadow: inset 3px 0 0 var(--teal); }
.co-fila-main { flex: 1; min-width: 0; display: grid; grid-template-columns: 44px minmax(0, 1.4fr) minmax(0, 1fr) minmax(0, 1.2fr); align-items: center; gap: 14px; padding: 12px 18px; text-align: left; background: none; }
.co-fila-main:hover { background: rgba(11, 90, 84, .03); }
.co-puerta { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: var(--cv); box-shadow: inset 0 0 0 1.5px var(--c); }
.co-puerta b { font: 750 15px var(--disp); font-stretch: 85%; color: var(--c); }
.co-puerta.e-ocupado { background: var(--cred); }
.co-puerta.e-ocupado b { color: #fff; }
.co-fila-n { min-width: 0; display: flex; flex-direction: column; }
.co-fila-n b { font-weight: 650; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.co-fila-n small { font: 500 12px var(--mono); color: var(--media); letter-spacing: .03em; }
.co-estado { justify-self: start; display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: var(--cv); color: var(--c); font-size: 12.5px; font-weight: 600; white-space: nowrap; }
.co-estado i { width: 7px; height: 7px; border-radius: 50%; background: var(--c); }
.co-medico { font-size: 13px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.co-fila-acc { display: flex; gap: 6px; padding-right: 16px; }
.co-ico { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; border: 1px solid var(--linea); background: var(--papel); color: var(--texto); }
.co-ico:hover { background: var(--niebla); color: var(--tinta); }
.co-ico-r:hover { background: var(--rosav); color: var(--rosa); border-color: #f3c6cf; }
.co-sin { padding: 28px 18px; text-align: center; font-size: 13px; color: var(--media); }
.co-sin b { display: block; font: 750 18px var(--disp); color: var(--tinta); margin-bottom: 2px; }
@container (max-width: 759px) {
    .co-fila-main { grid-template-columns: 44px minmax(0, 1fr) auto; }
    .co-medico { display: none; }
}
@container (max-width: 479px) { .co-estado span { display: none; } .co-estado { padding: 6px; } }

@media (prefers-reduced-motion: reduce) {
    .co-sala, .co-escena.cambio { animation: none !important; }
    .co-placa-in { transition: none; }
    .co-placa.volteada .co-placa-in { transform: none; }
    .co-placa.volteada .co-frente { visibility: hidden; }
    .co-reverso { transform: none; visibility: hidden; }
    .co-placa.volteada .co-reverso { visibility: visible; }
}
</style>

<script>
function modConsultorios() {
    const DATOS = JSON.parse(document.getElementById('co-datos').textContent);
    const URL = DATOS.url, CSRF = DATOS.csrf;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const avisar = (m, t = 'success') => { if (window.notificar) window.notificar(m, t); };
    const lee = (k, d) => { try { return JSON.parse(localStorage.getItem(k) || d); } catch (e) { return JSON.parse(d); } };
    const guarda = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} };
    const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    const INFO = {
        disponible:    { texto: 'Disponible',    corto: 'Libre',       plural: 'Disponibles',   icono: 'bi-door-open' },
        ocupado:       { texto: 'En consulta',   corto: 'En consulta', plural: 'En consulta',   icono: 'bi-activity' },
        mantenimiento: { texto: 'Mantenimiento', corto: 'Mant.',       plural: 'Mantenimiento', icono: 'bi-tools' },
        desconocido:   { texto: 'Sin definir',   corto: '—',           plural: 'Sin definir',   icono: 'bi-question-circle' }
    };

    return {
        P: (DATOS.consultorios || []).map(c => Object.assign({ ocupadoPor: null }, c)),
        sel: null,
        piso: '',
        pisosExtra: [],
        q: '',
        filtro: 'todos',
        escala: 1,
        animarPiso: false,
        arrastre: null,
        actualizado: '',
        // Plano (mismas medidas que el CSS)
        tam: 100, paso: 20, ancho: 720, alto: 540, angZ: 45, angX: 52,
        // Edición
        editando: false,
        guardando: false,
        form: { id: null, nombre: '', piso: '', estado: 'Disponible' },
        errores: {},
        mensajeForm: '',
        pisoNuevo: { abierto: false, valor: '', error: '' },
        opciones: [
            { valor: 'Disponible',    slug: 'disponible',    corto: 'Libre',       plural: 'Disponibles' },
            { valor: 'Ocupado',       slug: 'ocupado',       corto: 'En consulta', plural: 'En consulta' },
            { valor: 'Mantenimiento', slug: 'mantenimiento', corto: 'Mant.',       plural: 'Mantenimiento' }
        ],

        init() {
            // Pisos vacíos creados en este navegador (no hay tabla de pisos en la BD)
            const reales = new Set(this.P.map(c => c.piso));
            this.pisosExtra = lee('pisos_extra_consultorios', '[]').filter(p => !reales.has(p));
            guarda('pisos_extra_consultorios', this.pisosExtra);

            // Posiciones: las guardadas en la BD, las de respaldo local o una cuadrícula automática
            const locales = lee('plano_consultorios', '{}');
            this.P.forEach(c => {
                if (c.x === null || c.y === null) {
                    if (locales[c.id]) { c.x = locales[c.id].x; c.y = locales[c.id].y; }
                    else Object.assign(c, this.casillaLibre(c.piso, c.id));
                }
            });

            let ultimo = null; try { ultimo = sessionStorage.getItem('piso_activo_consultorios'); } catch (e) {}
            this.piso = (ultimo !== null && this.pisos.includes(ultimo)) ? ultimo : (this.pisos[0] ?? '');
            this.sel = this.planoVisible[0] || this.P[0] || null;
            this.$watch('sel', () => window.mtRefresca && mtRefresca('.co-frente .co-fr'));

            // Escala del plano según el ancho disponible
            const ajustar = () => { const w = this.$refs.lienzo.clientWidth; this.escala = Math.min(1, Math.max(.35, w / 900)); };
            ajustar();
            if (window.ResizeObserver) new ResizeObserver(ajustar).observe(this.$refs.lienzo);
            else window.addEventListener('resize', ajustar);

            // Estado en vivo (cada 15 s con la pestaña visible)
            this.sincronizar();
            setInterval(() => { if (!document.hidden && !this.arrastre && !this.editando) this.sincronizar(); }, 15000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) this.sincronizar(); });
        },

        /* ---------- Datos derivados ---------- */
        info(slug) { return INFO[slug] || INFO.desconocido; },
        get pisos() {
            return [...new Set([...this.P.map(c => c.piso), ...this.pisosExtra])]
                .sort((a, b) => String(a).localeCompare(String(b), 'es', { numeric: true }));
        },
        get planoVisible() { return this.P.filter(c => c.piso === this.piso); },
        get filtrados() {
            return this.P.filter(c => this.pasaFiltro(c))
                .sort((a, b) => String(a.piso).localeCompare(String(b.piso), 'es', { numeric: true }) || a.nombre.localeCompare(b.nombre, 'es', { numeric: true }));
        },
        pasaFiltro(c) {
            const q = norm(this.q.trim());
            const texto = !q || norm(c.nombre + ' piso ' + c.piso).includes(q);
            return texto && (this.filtro === 'todos' || c.estado === this.filtro);
        },
        contar(slug) { return this.P.filter(c => c.estado === slug).length; },
        contarPiso(p) { return this.P.filter(c => c.piso === p).length; },
        enConsultaPiso(p) { return this.P.filter(c => c.piso === p && c.estado === 'ocupado').length; },
        etiquetaPiso(p) { return p === '' || p === null || p === undefined ? 'Sin piso' : (/^\d+$/.test(p) ? 'Piso ' + p : p); },
        iniciales(n) {
            const num = String(n).match(/\d+/);
            if (num) return num[0].slice(-3);
            return String(n).trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
        },
        franja(c) {
            if (c.estado === 'ocupado') return c.ocupadoPor ? 'En consulta con ' + c.ocupadoPor : 'En uso en este momento';
            if (c.estado === 'mantenimiento') return 'En mantenimiento: no se le asignan citas';
            if (c.estado === 'disponible') return 'Libre para asignar citas';
            return 'Estado sin definir';
        },
        filtrar(slug) { this.filtro = this.filtro === slug ? 'todos' : slug; },

        /* ---------- Patrón de seguridad de la placa ---------- */
        guilloche(seed) {
            let h = 2166136261; for (const ch of String(seed)) { h ^= ch.charCodeAt(0); h = Math.imul(h, 16777619); } h >>>= 0;
            const w = 320, al = 360, n = 20 + h % 10, cx = w * .78, cy = al * .3; let d = '';
            for (let k = 0; k < 12; k++) {
                const A = 50 + k * 8, B = 6 + (k % 4) * 2, ph = k * .22 + (h % 100) / 50;
                for (let i = 0; i <= 240; i++) { const t = i / 240 * 2 * Math.PI, r = A + B * Math.cos(n * t + ph); d += (i ? 'L' : 'M') + (cx + r * Math.cos(t)).toFixed(1) + ',' + (cy + r * Math.sin(t)).toFixed(1); }
                d += 'Z';
            }
            for (let k = 0; k < 6; k++) { const y0 = 150 + k * 34; for (let x = 0; x <= w; x += 6) d += (x ? 'L' : 'M') + x + ',' + (y0 + 6 * Math.sin(x / 27 + k * .9 + h % 5) + 2.5 * Math.sin(x / 8 + k)).toFixed(1); }
            return `<svg viewBox="0 0 ${w} ${al}" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="${d}" fill="none" stroke="#a6efe2" stroke-width=".6" opacity=".22"/></svg>`;
        },

        /* ---------- Pisos ---------- */
        cambiarPiso(p) {
            if (this.arrastre || this.editando) return;
            this.piso = p;
            try { sessionStorage.setItem('piso_activo_consultorios', p); } catch (e) {}
            if (!this.sel || this.sel.piso !== p) this.sel = this.planoVisible[0] || this.sel;
            if (!reduce) { this.animarPiso = false; this.$nextTick(() => { this.animarPiso = true; }); }
        },
        abrirPisoNuevo() {
            this.pisoNuevo = { abierto: true, valor: '', error: '' };
            this.$nextTick(() => this.$refs.pisoNuevo.focus());
        },
        agregarPiso() {
            const v = this.pisoNuevo.valor.trim().toUpperCase();
            if (!v) { this.pisoNuevo.error = 'Escribe el piso'; return; }
            if (this.pisos.includes(v)) { this.pisoNuevo.error = 'Ya existe'; return; }
            this.pisosExtra.push(v); guarda('pisos_extra_consultorios', this.pisosExtra);
            this.pisoNuevo.abierto = false;
            this.cambiarPiso(v);
            avisar(this.etiquetaPiso(v) + ' agregado. Registra su primer consultorio.');
        },

        /* Quitar un piso: si tiene consultorios, se mueven a otro piso o se eliminan junto con él */
        async eliminarPiso(p) {
            if (this.editando || this.arrastre) return;
            const esc = t => String(t).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
            const nombre = this.etiquetaPiso(p);
            const enPiso = this.P.filter(c => c.piso === p);
            const otros = this.pisos.filter(x => x !== p);
            const terminar = (destino) => {
                this.pisosExtra = this.pisosExtra.filter(x => x !== p);
                if (destino !== undefined && destino !== null && !this.P.some(c => c.piso === destino) && !this.pisosExtra.includes(destino)) this.pisosExtra.push(destino);
                guarda('pisos_extra_consultorios', this.pisosExtra);
                this.cambiarPiso(destino !== undefined && destino !== null ? destino : (this.pisos[0] ?? ''));
            };

            if (!enPiso.length) {
                const r = await Swal.fire({ title: '¿Quitar ' + nombre + '?', text: 'No tiene consultorios registrados.', icon: 'question', showCancelButton: true, reverseButtons: true, confirmButtonText: 'Quitar piso', cancelButtonText: 'Cancelar', confirmButtonColor: '#c8102e', cancelButtonColor: '#56625f' });
                if (!r.isConfirmed) return;
                terminar(); avisar(nombre + ' quitado.'); return;
            }

            const chips = otros.map(o => '<button type="button" class="co-sw-chip" data-v="' + esc(o) + '">' + esc(this.etiquetaPiso(o)) + '</button>').join('');
            const r = await Swal.fire({
                title: '¿Quitar ' + nombre + '?',
                html: '<p class="co-sw-p">Tiene <b>' + enPiso.length + (enPiso.length === 1 ? ' consultorio' : ' consultorios') + '</b>. Elige a qué piso pasarlos:</p>'
                    + '<div class="co-sw-chips">' + chips + '<input type="text" class="co-sw-nuevo" maxlength="20" placeholder="Otro piso…" aria-label="Nombre de otro piso"></div>',
                icon: 'question', showCancelButton: true, showDenyButton: true, reverseButtons: true, focusCancel: true,
                confirmButtonText: 'Mover y quitar piso', denyButtonText: 'Eliminar también sus consultorios', cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0b5a54', denyButtonColor: '#c8102e', cancelButtonColor: '#56625f',
                didOpen: (el) => {
                    const lista = el.querySelectorAll('.co-sw-chip'), nuevo = el.querySelector('.co-sw-nuevo');
                    const elegir = b => { lista.forEach(x => x.classList.toggle('on', x === b)); if (b) nuevo.value = ''; };
                    lista.forEach(b => b.addEventListener('click', () => elegir(b)));
                    nuevo.addEventListener('input', () => elegir(null));
                    if (lista[0]) elegir(lista[0]); else nuevo.focus();
                },
                preConfirm: () => {
                    const el = Swal.getPopup(), on = el.querySelector('.co-sw-chip.on'), nuevo = el.querySelector('.co-sw-nuevo').value.trim().toUpperCase();
                    const v = on ? on.dataset.v : nuevo;
                    if (!v && !on) { Swal.showValidationMessage('Elige un piso o escribe uno nuevo.'); return false; }
                    if (v === p) { Swal.showValidationMessage('Elige un piso distinto.'); return false; }
                    return v;
                },
            });
            if (r.isDismissed) return;

            const estado = c => ({ disponible: 'Disponible', ocupado: 'Ocupado', mantenimiento: 'Mantenimiento' })[c.estado] || 'Disponible';
            const fallos = [];
            Swal.fire({ title: r.isConfirmed ? 'Moviendo consultorios…' : 'Eliminando consultorios…', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
            for (const c of enPiso) {
                try {
                    const res = r.isConfirmed
                        ? await fetch(`${URL}/${c.id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify({ nombre: c.nombre, piso: r.value, estado: estado(c) }) })
                        : await fetch(`${URL}/${c.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                    const d = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(d.message || 'Error');
                    if (r.isConfirmed) { const i = this.P.findIndex(x => x.id === c.id); if (i >= 0 && d.consultorio) this.P.splice(i, 1, Object.assign({}, this.P[i], d.consultorio)); }
                    else this.P.splice(this.P.findIndex(x => x.id === c.id), 1);
                } catch (e) { fallos.push(c.nombre + ': ' + e.message); }
            }
            Swal.close();
            if (fallos.length) {
                if (this.P.some(c => c.piso === p) && !this.pisosExtra.includes(p)) { /* el piso sigue existiendo por los que no se pudieron mover */ }
                Swal.fire({ title: 'No se pudo con todos', icon: 'warning', html: '<p style="font-size:14px;text-align:left">' + fallos.map(esc).join('<br>') + '</p>', confirmButtonColor: '#0b5a54' });
            }
            if (!this.P.some(c => c.piso === p)) terminar(r.isConfirmed ? r.value : null);
            if (this.sel && !this.P.includes(this.sel)) this.sel = this.planoVisible[0] || this.P[0] || null;
            if (!fallos.length) avisar(r.isConfirmed ? nombre + ' quitado; sus consultorios pasaron a ' + this.etiquetaPiso(r.value) + '.' : nombre + ' y sus consultorios se eliminaron.');
        },

        /* ---------- Selección ---------- */
        seleccionar(c, desdeLista) {
            if (this.editando || !c) return;
            this.sel = c;
            if (c.piso !== this.piso) this.cambiarPiso(c.piso);
            if (desdeLista && this.$refs.lienzo) this.$refs.lienzo.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
        },

        /* ---------- Plano: posiciones y arrastre ---------- */
        casillaLibre(piso, sinId) {
            // Casillas ordenadas desde el centro de la losa: los consultorios se agrupan al centro,
            // con pasillos de 40 px; si ya no caben, se usa una cuadrícula más apretada.
            const ocupa = (x, y) => this.P.some(o => o.id !== sinId && o.piso === piso && o.x !== null && o.x !== undefined && Math.abs(o.x - x) < this.tam && Math.abs(o.y - y) < this.tam);
            for (const paso of [140, 120, 100]) {
                const casillas = [], cols = Math.floor((this.ancho - this.tam) / paso) + 1, filas = Math.floor((this.alto - this.tam) / paso) + 1;
                const x0 = Math.round((this.ancho - this.tam - (cols - 1) * paso) / 2 / this.paso) * this.paso;
                const y0 = Math.round((this.alto - this.tam - (filas - 1) * paso) / 2 / this.paso) * this.paso;
                for (let f = 0; f < filas; f++) for (let k = 0; k < cols; k++) casillas.push({ x: x0 + k * paso, y: y0 + f * paso });
                const cx = (this.ancho - this.tam) / 2, cy = (this.alto - this.tam) / 2;
                casillas.sort((a, b) => Math.hypot(a.x - cx, a.y - cy) - Math.hypot(b.x - cx, b.y - cy) || a.y - b.y || a.x - b.x);
                const libre = casillas.find(k => !ocupa(k.x, k.y));
                if (libre) return libre;
            }
            return { x: 0, y: 0 };
        },
        estiloSala(c, i) {
            const alzada = this.arrastre && this.arrastre.id === c.id && this.arrastre.movio;
            return `left:${c.x}px;top:${c.y}px;width:${this.tam}px;height:${this.tam}px;--d:${i * 60}ms;`
                + `transform:translateZ(${alzada ? 22 : (this.sel && this.sel.id === c.id ? 8 : 0)}px);z-index:${alzada ? 20 : 10};`
                + `transition:${alzada ? 'none' : 'left .15s, top .15s, transform .2s, opacity .3s'};`;
        },
        estiloDestino() {
            const a = this.arrastre;
            if (!a || !a.movio) return 'display:none';
            const c = a.choque ? '200,16,46' : '14,124,114';
            return `left:${a.sx}px;top:${a.sy}px;width:${this.tam}px;height:${this.tam}px;background:rgba(${c},.14);border:2px dashed rgba(${c},.8);`;
        },
        iniciarArrastre(e, c) {
            if (this.editando || e.button > 0) return;
            e.currentTarget.setPointerCapture(e.pointerId);
            this.arrastre = { id: c.id, cx: e.clientX, cy: e.clientY, x0: c.x, y0: c.y, sx: c.x, sy: c.y, movio: false, choque: false };
        },
        mover(e, c) {
            const a = this.arrastre;
            if (!a || a.id !== c.id) return;
            if (!a.movio) { if (Math.hypot(e.clientX - a.cx, e.clientY - a.cy) < 5) return; a.movio = true; }
            const t = this.angZ * Math.PI / 180, f = this.angX * Math.PI / 180;
            const u = (e.clientX - a.cx) / this.escala, v = (e.clientY - a.cy) / this.escala / Math.cos(f);
            c.x = Math.max(0, Math.min(a.x0 + u * Math.cos(t) + v * Math.sin(t), this.ancho - this.tam));
            c.y = Math.max(0, Math.min(a.y0 - u * Math.sin(t) + v * Math.cos(t), this.alto - this.tam));
            const snap = (val, max) => Math.max(0, Math.min(Math.floor(max / this.paso) * this.paso, Math.round(val / this.paso) * this.paso));
            a.sx = snap(c.x, this.ancho - this.tam); a.sy = snap(c.y, this.alto - this.tam);
            a.choque = this.P.some(o => o.id !== c.id && o.piso === c.piso && Math.abs(o.x - a.sx) < this.tam && Math.abs(o.y - a.sy) < this.tam);
        },
        soltar(c) {
            const a = this.arrastre;
            if (!a || a.id !== c.id) return;
            this.arrastre = null;
            if (!a.movio) { this.seleccionar(c); return; }
            if (a.choque) { c.x = a.x0; c.y = a.y0; avisar('Ese lugar ya lo ocupa otro consultorio.', 'warning'); return; }
            c.x = a.sx; c.y = a.sy;
            if (c.x !== a.x0 || c.y !== a.y0) this.guardarPosicion(c);
        },
        cancelarArrastre(c) {
            const a = this.arrastre;
            if (!a || a.id !== c.id) return;
            c.x = a.x0; c.y = a.y0; this.arrastre = null;
        },
        async guardarPosicion(c) {
            let causa = '';
            try {
                const r = await fetch(`${URL}/${c.id}/posicion`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ pos_x: Math.round(c.x), pos_y: Math.round(c.y) })
                });
                if (!r.ok) {
                    const d = await r.json().catch(() => ({}));
                    const msg = d.message ? ': ' + d.message : '';
                    causa = r.status === 419 ? 'tu sesión expiró, recarga la página'
                          : r.status === 403 ? 'tu rol no tiene permiso para editar consultorios'
                          : r.status === 404 ? 'no existe la ruta PATCH consultorios/{id}/posicion (corre php artisan optimize:clear)'
                          : r.status === 422 ? 'datos no válidos' + msg
                          : /pos_x|pos_y|column/i.test(d.message || '') ? 'faltan las columnas pos_x y pos_y (corre php artisan migrate)'
                          : 'error ' + r.status + msg;
                    throw new Error(causa);
                }
                const locales = lee('plano_consultorios', '{}'); delete locales[c.id]; guarda('plano_consultorios', locales);
            } catch (e) {
                const locales = lee('plano_consultorios', '{}'); locales[c.id] = { x: c.x, y: c.y }; guarda('plano_consultorios', locales);
                console.warn('[Consultorios] No se guardó la posición:', e);
                avisar('No se guardó la posición en el servidor (' + (causa || 'sin conexión') + '). Quedó guardada en este navegador.', 'warning');
            }
        },
        rebotar(id) {
            if (reduce) return;
            const el = this.$root.querySelector(`.co-sala[data-id="${id}"]`);
            if (el && el.animate) el.animate([{ transform: 'translateZ(0)' }, { transform: 'translateZ(34px)', offset: .35 }, { transform: 'translateZ(4px)', offset: .65 }, { transform: 'translateZ(0)' }], { duration: 900, easing: 'ease-out' });
        },

        /* ---------- Estado en vivo ---------- */
        async sincronizar() {
            try {
                const r = await fetch(`${URL}/estados`, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                (await r.json()).forEach(e => {
                    const c = this.P.find(x => x.id === e.id);
                    if (!c) return;
                    const nuevo = ['disponible', 'ocupado', 'mantenimiento'].includes(String(e.estado).toLowerCase()) ? String(e.estado).toLowerCase() : 'desconocido';
                    if (c.estado !== nuevo) {
                        c.estado = nuevo;
                        this.rebotar(c.id);
                        avisar(`${c.nombre}: ${this.info(nuevo).texto.toLowerCase()}`, 'info');
                    }
                    c.ocupadoPor = e.ocupado_por || null;
                });
                this.actualizado = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });
            } catch (e) { /* se reintenta en el siguiente ciclo */ }
        },

        /* ---------- Alta y edición (la placa se voltea) ---------- */
        nuevo() {
            if (this.editando) return;
            this.form = { id: null, nombre: '', piso: this.piso || (this.pisos[0] ?? '1'), estado: 'Disponible' };
            this.abrirForm();
        },
        editar(c) {
            if (this.editando || !c) return;
            this.seleccionar(c);
            this.form = { id: c.id, nombre: c.nombre, piso: c.piso, estado: (this.opciones.find(o => o.slug === c.estado) || this.opciones[0]).valor };
            this.abrirForm();
        },
        abrirForm() {
            this.errores = {}; this.mensajeForm = 'Ctrl+Enter guarda';
            this.editando = true;
            this.$root.querySelector('.co-placa').scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
            setTimeout(() => this.$refs.campoNombre.focus({ preventScroll: true }), reduce ? 0 : 450);
        },
        cancelar() {
            this.editando = false; this.guardando = false;
            // El foco regresa al frente (el formulario queda oculto)
            this.$nextTick(() => { const b = this.$root.querySelector('.co-acc .co-prim'); if (b) b.focus({ preventScroll: true }); else document.activeElement && document.activeElement.blur(); });
        },
        async guardar() {
            if (this.guardando) return;
            const f = this.form;
            f.nombre = f.nombre.trim(); f.piso = String(f.piso || '').trim().toUpperCase();
            this.errores = {};
            if (!f.nombre) this.errores.nombre = 'Escribe el nombre del consultorio.';
            if (this.P.some(c => c.id !== f.id && norm(c.nombre) === norm(f.nombre) && c.piso === f.piso)) this.errores.nombre = 'Ya hay un consultorio con ese nombre en este piso.';
            if (Object.keys(this.errores).length) { this.mensajeForm = 'Revisa el dato marcado.'; window.mtSacudir && mtSacudir('.co-reverso'); return; }

            this.guardando = true; this.mensajeForm = 'Guardando…';
            try {
                const r = await fetch(f.id ? `${URL}/${f.id}` : URL, {
                    method: f.id ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ nombre: f.nombre, piso: f.piso, estado: f.id ? f.estado : 'Disponible' })
                });
                const d = await r.json().catch(() => ({}));
                if (r.status === 422) {
                    Object.entries(d.errors || {}).forEach(([k, v]) => { this.errores[k] = v[0]; });
                    this.mensajeForm = d.message || 'Revisa los datos.'; this.guardando = false; window.mtSacudir && mtSacudir('.co-reverso'); return;
                }
                if (!r.ok) throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo guardar.'));

                const datos = d.consultorio || { id: f.id, nombre: f.nombre, piso: f.piso, estado: f.estado.toLowerCase() };
                let c = this.P.find(x => x.id === datos.id);
                if (c) {
                    const cambioPiso = c.piso !== datos.piso;
                    Object.assign(c, { nombre: datos.nombre, piso: datos.piso, estado: datos.estado, ocupadoPor: datos.ocupadoPor ?? c.ocupadoPor });
                    if (cambioPiso) { Object.assign(c, this.casillaLibre(c.piso, c.id)); this.guardarPosicion(c); }
                } else {
                    c = Object.assign({ ocupadoPor: null }, datos);
                    Object.assign(c, this.casillaLibre(c.piso, c.id));
                    this.P.push(c);
                    this.guardarPosicion(c);
                    this.pisosExtra = this.pisosExtra.filter(p => p !== c.piso); guarda('pisos_extra_consultorios', this.pisosExtra);
                }
                this.cancelar();
                this.sel = c;
                if (c.piso !== this.piso) this.cambiarPiso(c.piso);
                this.$nextTick(() => this.rebotar(c.id));
                avisar(d.message || 'Consultorio guardado.');
                this.$nextTick(() => window.mtDestello && mtDestello('.co-fila.sel'));
            } catch (e) {
                this.guardando = false; this.mensajeForm = e.message;
                avisar(e.message, 'error');
            }
        },
        async cambiarEstado(op) {
            const c = this.sel;
            if (!c || c.estado === op.slug || this.guardando) return;
            const antes = c.estado;
            c.estado = op.slug; this.guardando = true;
            try {
                const r = await fetch(`${URL}/${c.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ nombre: c.nombre, piso: c.piso, estado: op.valor })
                });
                const d = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo cambiar el estado.'));
                if (d.consultorio) c.ocupadoPor = d.consultorio.ocupadoPor;
                this.rebotar(c.id);
                avisar(`${c.nombre}: ${this.info(op.slug).texto.toLowerCase()}`);
            } catch (e) {
                c.estado = antes; avisar(e.message, 'error');
            }
            this.guardando = false;
        },
        eliminar(c) {
            if (!c || this.editando) return;
            Swal.fire({
                title: '¿Eliminar este consultorio?',
                html: `<p style="font-size:14px">Se eliminará <b>${String(c.nombre).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]))}</b>. Si tiene citas registradas no se podrá eliminar.</p>`,
                icon: 'warning', iconColor: '#c8102e', showCancelButton: true, reverseButtons: true, focusCancel: true,
                confirmButtonText: 'Eliminar consultorio', cancelButtonText: 'Cancelar', confirmButtonColor: '#c8102e', cancelButtonColor: '#56625f',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const r = await fetch(`${URL}/${c.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                        const d = await r.json().catch(() => ({}));
                        if (!r.ok) throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo eliminar.'));
                        return d;
                    } catch (e) { Swal.showValidationMessage(e.message); }
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then(res => {
                if (!res.isConfirmed) return;
                const quitar = () => {
                    const i = this.P.findIndex(x => x.id === c.id);
                    this.P.splice(i, 1);
                    if (!this.P.some(x => x.piso === c.piso) && !this.pisosExtra.includes(c.piso)) { this.pisosExtra.push(c.piso); guarda('pisos_extra_consultorios', this.pisosExtra); }
                    this.sel = this.planoVisible[0] || this.P[0] || null;
                };
                window.mtQuitar ? mtQuitar('.co-fila.sel', quitar) : quitar();
                avisar((res.value && res.value.message) || 'Consultorio eliminado.');
            });
        },

        /* ---------- Teclado ---------- */
        tecla(e) {
            const enCampo = /INPUT|SELECT|TEXTAREA/.test(e.target.tagName) || e.target.isContentEditable;
            if (this.editando) {
                if (e.key === 'Escape') { e.preventDefault(); this.cancelar(); }
                else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.guardar(); }
                return;
            }
            if (enCampo || e.ctrlKey || e.metaKey || e.altKey || document.querySelector('.swal2-popup:not(.swal2-toast)') || document.querySelector('.ly-paleta:not([style*="none"])')) return;
            if (e.key === '/') { e.preventDefault(); this.$refs.buscar.focus(); }
            else if (e.key === 'n' || e.key === 'N') { e.preventDefault(); this.nuevo(); }
            else if ((e.key === 'e' || e.key === 'E') && this.sel) { e.preventDefault(); this.editar(this.sel); }
        }
    };
}
</script>
@endverbatim
@endsection
