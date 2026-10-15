@extends('layouts.admin')

{{--
    MÓDULO DE RECETAS · MediTrack
    Lista de recetas por fecha, la hoja de la receta tal como se imprime y el formulario para emitir
    (recetas/modalRecetas.blade.php). Las recetas que vienen de una consulta llegan con ?consulta_id=ID.
    Guarda por AJAX: RecetaController responde JSON. El PDF se genera en recetas/pdf.blade.php.
--}}

@section('content')
@php
    $datosVista = array_merge($datosRecetas ?? ['recetas' => [], 'pacientes' => [], 'medicos' => [], 'productos' => [], 'prefill' => null, 'clinica' => ['nombre' => 'MediTrack']], [
        'url'  => url('recetas'),
        'csrf' => csrf_token(),
    ]);
@endphp

<div class="rx" x-data="modRecetas()" :class="{ 'editando': editando }" @keydown.window="tecla($event)">

    <!-- BÚSQUEDA Y ALTA -->
    <header class="rx-top">
        <label class="rx-cmd">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" x-ref="buscar" x-model.debounce.150ms="q" autocomplete="off" placeholder="Busca por paciente, folio, diagnóstico o medicamento" aria-label="Buscar receta">
            <kbd aria-hidden="true">/</kbd>
        </label>
        <button type="button" class="rx-dark" @click="nueva()">Nueva receta <kbd>N</kbd></button>
    </header>

    <!-- CIFRA -->
    <section class="rx-cab">
        <div class="rx-g">
            <b class="t" x-text="delMes.length">0</b>
            <span><em x-text="delMes.length === 1 ? 'receta este mes' : 'recetas este mes'"></em><span x-text="nombreMes"></span></span>
        </div>
        <p class="rx-res t">
            <span><b x-text="R.filter(r => r.fecha === hoy).length"></b> hoy</span>
            <span><b x-text="delMes.reduce((n, r) => n + r.detalles.length, 0)"></b> medicamentos recetados</span>
            <span x-show="masRecetado"><span>Más recetado:</span> <b x-text="masRecetado"></b></span>
        </p>
    </section>

    <section class="rx-cuerpo">
        <!-- LISTA POR FECHA -->
        <div class="rx-lista-r" aria-label="Recetas">
            <template x-for="g in grupos" :key="'g' + g.fecha">
                <div class="rx-grupo">
                    <h3 x-text="tituloFecha(g.fecha)"></h3>
                    <template x-for="r in g.recetas" :key="'r' + r.id">
                        <button type="button" class="rx-fila" :class="{ 'sel': sel && sel.id === r.id }" @click="seleccionar(r)">
                            <span class="rx-folio t" x-text="'#' + r.folio"></span>
                            <span class="rx-fila-n">
                                <b><span x-text="r.paciente"></span><i x-show="r.alergias.length" class="bi bi-exclamation-triangle-fill" :title="'Alergias: ' + r.alergias.join(', ')"></i></b>
                                <small x-text="r.diagnostico"></small>
                            </span>
                            <span class="rx-fila-m"><b class="t" x-text="r.detalles.length"></b><small x-text="r.detalles.length === 1 ? 'medicamento' : 'medicamentos'"></small></span>
                        </button>
                    </template>
                </div>
            </template>
            <div class="rx-vacio" x-show="!grupos.length">
                <b x-text="q.trim() ? 'Ninguna receta coincide' : 'Aún no hay recetas'"></b>
                <p x-text="q.trim() ? 'Prueba con otro nombre, folio o medicamento.' : 'Emite la primera con «Nueva receta» o al finalizar una consulta.'"></p>
            </div>
        </div>

        <!-- HOJA DE RECETA (frente) Y FORMULARIO (reverso) -->
        <aside class="rx-lado">
            @include('recetas.modalRecetas')
        </aside>
    </section>
</div>

<script type="application/json" id="rx-datos">{!! json_encode($datosVista, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@verbatim
<style>
.rx {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f;
    --e: cubic-bezier(.2, .8, .2, 1); --flip: cubic-bezier(.3, .7, .1, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.rx * { box-sizing: border-box; }
.rx :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.rx button { cursor: pointer; }
.rx .t { font-variant-numeric: tabular-nums; }
.rx kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); }
.rx .may { text-transform: uppercase; }
.rx .may::placeholder { text-transform: none; }
[x-cloak] { display: none !important; }

/* Búsqueda */
.rx-top { display: flex; align-items: center; gap: 12px; }
.rx-cmd { flex: 1; min-width: 0; max-width: 760px; height: 50px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 12px 30px -22px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 16px; }
.rx-cmd > .bi { color: var(--cred); }
.rx-cmd input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 450 15px var(--ui); color: var(--tinta); }
.rx-cmd input::placeholder { color: var(--media); }
.rx-dark { height: 44px; border-radius: 12px; background: var(--tinta); color: #fff; font: 600 14px var(--ui); display: flex; align-items: center; gap: 10px; padding: 0 12px 0 16px; white-space: nowrap; }
.rx-dark kbd { border-color: rgba(255, 255, 255, .25); color: #cfe; }
@container (max-width: 639px) { .rx-cmd > kbd, .rx-dark kbd { display: none; } .rx-dark { padding: 0 14px; } }

/* Cifra */
.rx-cab { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px 26px; }
.rx-g { display: flex; align-items: flex-end; gap: 12px; }
.rx-g > b { font: 800 72px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); }
.rx-g > span { display: flex; flex-direction: column; font-size: 14px; color: var(--media); line-height: 1.25; }
.rx-g em { font-style: normal; font: 700 19px/1.1 var(--disp); color: var(--tinta); text-transform: none; }
.rx-res { display: flex; flex-wrap: wrap; gap: 4px 18px; padding-bottom: 2px; font-size: 13.5px; }
.rx-res b { font-weight: 650; color: var(--tinta); }
.rx-res span span { color: var(--media); }

/* Cuerpo */
.rx-cuerpo { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
@container (min-width: 960px) { .rx-cuerpo { grid-template-columns: minmax(0, 1fr) 440px; } }
.rx-lado { display: grid; gap: 12px; }
@container (min-width: 960px) { .rx-lado { position: sticky; top: 96px; } }

/* Lista */
.rx-lista-r { display: grid; gap: 14px; transition: opacity .3s, filter .3s; }
.rx-grupo h3 { padding: 0 10px 4px; font-size: 12.5px; font-weight: 600; color: var(--media); }
.rx-grupo h3:first-letter { text-transform: uppercase; }
.rx-fila { width: 100%; display: grid; grid-template-columns: 64px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 10px; border-radius: 12px; text-align: left; background: none; transition: background .15s; }
.rx-fila:hover { background: rgba(11, 90, 84, .05); }
.rx-fila.sel { background: var(--papel); box-shadow: 0 1px 0 var(--linea), 0 8px 18px -14px rgba(11, 30, 28, .55); }
.rx-folio { font: 500 13px var(--mono); color: var(--cred); }
.rx-fila-n { min-width: 0; display: flex; flex-direction: column; }
.rx-fila-n b { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rx-fila-n b span { overflow: hidden; text-overflow: ellipsis; }
.rx-fila-n b .bi { flex: none; font-size: 11px; color: var(--rosa); }
.rx-fila-n small { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase; }
.rx-fila-m { display: flex; flex-direction: column; align-items: flex-end; }
.rx-fila-m b { font: 600 15px var(--mono); color: var(--tinta); }
.rx-fila-m small { font-size: 11.5px; color: var(--media); }
.rx-vacio { padding: 40px 16px; text-align: center; border: 1.5px dashed var(--linea); border-radius: 18px; color: var(--media); }
.rx-vacio b { display: block; font: 700 19px var(--disp); color: var(--tinta); margin-bottom: 4px; }
@container (max-width: 559px) { .rx-fila { grid-template-columns: minmax(0, 1fr) auto; } .rx-folio { display: none; } }

/* Hoja: frente (papel) y reverso (formulario) */
.rx-hoja { perspective: 1600px; }
.rx-hoja-in { position: relative; height: 560px; transform-style: preserve-3d; transition: transform .45s var(--flip), height .3s var(--e); }
.rx-hoja.volteada .rx-hoja-in { transform: rotateY(180deg); height: 760px; }
.rx-cara { position: absolute; inset: 0; border-radius: 18px; overflow: hidden; backface-visibility: hidden; -webkit-backface-visibility: hidden; }

.rx-papel { background: #fffefb; border: 1px solid var(--linea); box-shadow: 0 30px 50px -36px rgba(11, 30, 28, .6), 0 1px 0 rgba(11, 30, 28, .04); }
.rx-papel:before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 6px; background: var(--cred); }
.rx-doc { position: relative; height: 100%; display: flex; flex-direction: column; padding: 22px 24px 18px; overflow-y: auto; scrollbar-width: thin; }
.rx-doc-h { display: flex; justify-content: space-between; gap: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--linea); }
.rx-doc-h b { font: 750 18px/1.1 var(--disp); color: var(--cred); }
.rx-doc-h small { display: block; margin-top: 3px; font-size: 12px; color: var(--media); }
.rx-doc-h p { display: flex; flex-direction: column; align-items: flex-end; text-align: right; }
.rx-doc-h p span { font-size: 11.5px; color: var(--media); }
.rx-doc-h p b { font: 600 17px var(--mono); color: var(--tinta); }
.rx-doc-h p small { font-size: 12px; }
.rx-doc-p { display: grid; gap: 8px; padding: 12px 0; border-bottom: 1px solid var(--linea2); }
.rx-doc-p p { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; }
.rx-doc-p p > span:first-child { width: 78px; font-size: 12px; color: var(--media); }
.rx-doc-p b { font-weight: 650; color: var(--tinta); }
.rx-doc-p em { font-style: normal; font-size: 13px; color: var(--media); }
.rx-dx { flex: 1; min-width: 0; text-transform: uppercase; font-size: 13.5px; }
.rx-alg { align-self: flex-start; display: inline-flex !important; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: var(--rosav); color: var(--rosa); font-size: 12.5px; font-weight: 650; }
.rx-doc-rx { position: relative; flex: 1; padding: 14px 0 6px 48px; }
.rx-simbolo { position: absolute; left: 0; top: 8px; font: 700 40px/1 var(--disp); color: var(--cred); }
.rx-doc-rx ol { display: grid; gap: 12px; counter-reset: med; }
.rx-doc-rx li { list-style: none; counter-increment: med; position: relative; }
.rx-doc-rx li:before { content: counter(med) "."; position: absolute; left: -18px; font: 600 13px var(--mono); color: var(--media); }
.rx-doc-rx li b { display: block; font-size: 14.5px; font-weight: 700; color: var(--tinta); text-transform: uppercase; }
.rx-doc-rx li span { font-size: 13px; color: var(--texto); text-transform: uppercase; }
.rx-doc-ind { display: flex; flex-direction: column; gap: 2px; padding: 10px 12px; margin-top: 6px; border-radius: 10px; background: var(--niebla); font-size: 13px; text-transform: uppercase; }
.rx-doc-ind span:first-child { font-size: 11.5px; color: var(--media); text-transform: none; }
.rx-doc-f { display: flex; flex-direction: column; align-items: center; margin-top: 16px; padding-top: 8px; }
.rx-doc-f i { width: 200px; height: 1px; background: var(--texto); margin-bottom: 6px; }
.rx-doc-f b { font-size: 13.5px; font-weight: 650; color: var(--tinta); }
.rx-doc-f small { font-size: 12px; color: var(--media); }
.rx-doc-vacio { align-items: center; justify-content: center; text-align: center; color: var(--media); }
.rx-doc-vacio .rx-simbolo { position: static; font-size: 56px; color: #c9d6d2; margin-bottom: 8px; }
.rx-doc-vacio b { font: 700 19px var(--disp); color: var(--tinta); }

.rx-form { transform: rotateY(180deg); background: #fbfdfc; border: 1px solid var(--linea); box-shadow: 0 24px 44px -30px rgba(11, 30, 28, .55); display: flex; flex-direction: column; }
.rx-form-k { padding: 16px 18px 8px; font: 500 12px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: var(--cred); }
.rx-form-c { flex: 1; min-height: 0; overflow-y: auto; padding: 4px 18px 14px; display: flex; flex-direction: column; gap: 12px; scrollbar-width: thin; }
.rx-form-c > * { flex-shrink: 0; }
.rx-campo { position: relative; display: flex; flex-direction: column; gap: 5px; }
.rx-campo > span { font-size: 13px; font-weight: 600; color: var(--tinta); }
.rx-campo > span em { font-style: normal; font-weight: 400; color: var(--media); }
.rx-campo input, .rx-campo textarea { width: 100%; border: 1px solid var(--linea); border-radius: 10px; padding: 0 10px; height: 40px; font: 500 14px var(--ui); color: var(--tinta); background: var(--papel); outline: 0; transition: border-color .15s, box-shadow .15s; }
.rx-campo textarea { height: auto; padding: 8px 10px; resize: vertical; line-height: 1.4; }
.rx-campo input:focus, .rx-campo textarea:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.rx-campo input:disabled { background: var(--niebla); }
.rx-campo input.elegido::placeholder { color: var(--tinta); font-weight: 600; }
.rx-campo.mal > input, .rx-campo.mal > textarea, .rx-campo.mal > .rx-pick { border-color: var(--rosa); }
.rx-campo > small { font-size: 12px; font-weight: 500; color: var(--rosa); }
.rx-pick { height: 40px; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 0 12px; border: 1px solid var(--linea); border-radius: 10px; background: var(--papel); font: 500 14px var(--ui); color: var(--tinta); text-align: left; }
.rx-pick span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rx-pick .bi { color: var(--cred); transition: transform .2s; }
.rx-pick.abierto { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.rx-pick.abierto .bi { transform: rotate(180deg); }
.rx-lista { margin-top: 2px; padding: 4px; max-height: 220px; overflow-y: auto; background: var(--papel); border: 1px solid var(--linea); border-radius: 12px; box-shadow: 0 16px 32px -22px rgba(11, 30, 28, .55); }
.rx-lista button { width: 100%; display: grid; grid-template-columns: minmax(0, 1fr) auto; column-gap: 8px; padding: 7px 9px; border-radius: 8px; text-align: left; background: none; }
.rx-lista button:hover, .rx-lista button.on { background: var(--velo); }
.rx-lista b { font-size: 13px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rx-lista small { grid-column: 1; font-size: 11.5px; color: var(--media); }
.rx-lista small.agotado { color: var(--ambar); }
.rx-lista em { grid-column: 2; grid-row: 1 / 3; align-self: center; font-style: normal; font-size: 11.5px; font-weight: 650; color: var(--rosa); background: var(--rosav); border-radius: 999px; padding: 1px 8px; }
.rx-alg-f { display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 9px; background: var(--rosav); color: var(--rosa); font-size: 12.5px; font-weight: 650; text-transform: uppercase; }
.rx-cons { border: 1px solid var(--linea); border-radius: 12px; background: var(--papel); padding: 8px 12px; font-size: 13px; }
.rx-cons button { display: flex; align-items: center; justify-content: space-between; width: 100%; background: none; font-weight: 600; color: var(--cred); }
.rx-cons .gira { transform: rotate(180deg); }
.rx-cons p { margin-top: 6px; color: var(--texto); }

.rx-med { position: relative; display: grid; grid-template-columns: 22px minmax(0, 1fr) auto; gap: 8px; padding: 10px; border-radius: 12px; border: 1px solid var(--linea); background: var(--papel); }
.rx-med.alerta { border-color: #f1a7b4; background: #fff8f9; }
.rx-med-n { font: 600 13px/40px var(--mono); color: var(--media); text-align: center; }
.rx-med-c { position: relative; min-width: 0; display: grid; gap: 6px; }
.rx-med-c input { height: 36px; }
.rx-med-c .rx-med-nom { height: 40px; font-weight: 600; }
.rx-sug { position: absolute; left: 0; right: 0; top: 42px; z-index: 5; }
.rx-med-d { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 6px; }
.rx-med-alg { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 650; color: var(--rosa); }
.rx-rapidos { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
.rx-rapidos i { width: 1px; height: 16px; background: var(--linea); margin: 0 4px; }
.rx-rapidos button { height: 26px; padding: 0 8px; border-radius: 7px; border: 1px solid var(--linea2); background: var(--niebla); font: 500 12px var(--mono); color: var(--texto); }
.rx-rapidos button.on { background: var(--velo); border-color: var(--cred); color: var(--cred); }
.rx-med-x { width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; background: none; color: var(--media); }
.rx-med-x:hover { background: var(--rosav); color: var(--rosa); }
.rx-mas { height: 38px; border-radius: 10px; border: 1.5px dashed var(--linea); background: none; font: 600 13.5px var(--ui); color: var(--cred); }
.rx-mas:hover { border-color: var(--cred); background: var(--papel); }
.rx-form-f { display: flex; align-items: center; gap: 8px; padding: 12px 18px; border-top: 1px solid var(--linea2); background: var(--papel); }

/* Columnas del formulario (en angosto se apilan como siempre) */
.rx-form-a, .rx-form-b { display: contents; }

/* Emitir a lo ancho: como al crear un paciente, la hoja ocupa toda la vista */
@container (min-width: 640px) {
    .rx.editando .rx-cuerpo { grid-template-columns: minmax(0, 1fr); }
    .rx.editando .rx-lista-r, .rx.editando .rx-acc { display: none; }
    .rx.editando .rx-lado { position: static; }
    .rx.editando .rx-top { opacity: .35; pointer-events: none; }
    .rx-hoja.volteada .rx-hoja-in { height: 900px; }
    .rx.editando .rx-form-k { padding: 20px 28px 10px; font-size: 13px; }
    .rx.editando .rx-form-c { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; padding: 4px 28px 18px; align-content: start; }
    .rx.editando .rx-form-a, .rx.editando .rx-form-b { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
    .rx.editando .rx-form-a > *, .rx.editando .rx-form-b > * { flex-shrink: 0; }
    .rx.editando .rx-form-f { padding: 14px 28px; }
}
@container (min-width: 1000px) {
    .rx-hoja.volteada .rx-hoja-in { height: 720px; }
    .rx.editando .rx-form-c { grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr); gap: 0 32px; }
    .rx.editando .rx-form-b { padding-left: 32px; margin-left: -1px; border-left: 1px solid var(--linea2); }
    .rx.editando .rx-form-a textarea[rows="2"] { min-height: 92px; }
}
.rx-msg { flex: 1; min-width: 0; font-size: 12px; color: var(--media); line-height: 1.3; }
.rx-msg.mal { color: var(--rosa); font-weight: 600; }

/* Botones y acciones */
.rx-btn { height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border-radius: 11px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); white-space: nowrap; text-decoration: none; transition: background .15s; }
.rx-btn:hover { background: #f7faf9; }
.rx-prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.rx-prim:hover { background: var(--cred2); }
.rx-prim:disabled { opacity: .6; }
.rx-prim kbd { border-color: rgba(255, 255, 255, .3); color: #cfe; }
.rx-borrar { width: 40px; padding: 0; color: var(--rosa); border-color: #f3c6cf; }
.rx-borrar:hover { background: var(--rosav); }
.rx-acc { display: flex; gap: 8px; }
.rx-acc .rx-prim { flex: 1; }

.rx.editando .rx-lista-r, .rx.editando .rx-cab { opacity: .35; filter: saturate(.6); pointer-events: none; }

@media (prefers-reduced-motion: reduce) {
    .rx-hoja-in { transition: none; }
    .rx-hoja.volteada .rx-hoja-in { transform: none; }
    .rx-hoja.volteada .rx-papel { visibility: hidden; }
    .rx-form { transform: none; visibility: hidden; }
    .rx-hoja.volteada .rx-form { visibility: visible; }
}
</style>

<script>
function modRecetas() {
    const D = JSON.parse(document.getElementById('rx-datos').textContent);
    const URL = D.url, CSRF = D.csrf;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const avisar = (m, t = 'success') => { if (window.notificar) window.notificar(m, t); };
    const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    let uid = 0;
    const medVacio = (m = {}) => ({ uid: ++uid, nombre: m.medicamento || m.nombre || '', dosis: m.dosis || '', frecuencia: m.frecuencia || '', duracion: m.duracion || '', sug: false });

    return {
        R: D.recetas || [], PAC: D.pacientes || [], MED: D.medicos || [], PROD: D.productos || [], PREFILL: D.prefill, CLINICA: D.clinica || { nombre: 'MediTrack' },
        hoy: iso(new Date()), q: '', sel: null,
        editando: false, guardando: false, form: {}, errores: {}, mensajeForm: '',
        bPac: { q: '', abierto: false, i: 0 }, popMed: false, verResumen: false,

        init() {
            this.$watch('sel', () => window.mtRefresca && mtRefresca('.rx-papel .rx-doc'));
            this.sel = this.R[0] || null;
            this.form = this.formNuevo();
            if (this.PREFILL) {
                this.form = this.formNuevo({ paciente_id: String(this.PREFILL.paciente_id), personal_id: this.PREFILL.personal_id ? String(this.PREFILL.personal_id) : '', diagnostico: this.PREFILL.diagnostico || '', consulta_id: this.PREFILL.consulta_id });
                this.$nextTick(() => this.abrirForm());
                history.replaceState(null, '', location.pathname);
            }
        },

        /* ---------- Datos derivados ---------- */
        get visibles() {
            const q = norm(this.q);
            if (!q) return this.R;
            return this.R.filter(r => norm([r.folio, r.paciente, r.diagnostico, r.medico, ...r.detalles.map(d => d.medicamento)].join(' ')).includes(q));
        },
        get grupos() {
            const g = {};
            this.visibles.forEach(r => { (g[r.fecha] = g[r.fecha] || []).push(r); });
            return Object.keys(g).sort().reverse().map(f => ({ fecha: f, recetas: g[f] }));
        },
        get delMes() { return this.R.filter(r => r.fecha.slice(0, 7) === this.hoy.slice(0, 7)); },
        get nombreMes() { const t = new Date(this.hoy + 'T00:00:00').toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }); return t.charAt(0).toUpperCase() + t.slice(1); },
        get masRecetado() {
            const c = {};
            this.delMes.forEach(r => r.detalles.forEach(d => { const k = d.medicamento.split(/\s+/)[0]; c[k] = (c[k] || 0) + 1; }));
            const top = Object.entries(c).sort((a, b) => b[1] - a[1])[0];
            return top && top[1] > 1 ? top[0] : '';
        },
        tituloFecha(f) {
            if (f === this.hoy) return 'Hoy';
            const a = new Date(this.hoy + 'T00:00:00'); a.setDate(a.getDate() - 1);
            if (f === iso(a)) return 'Ayer';
            return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
        },
        fechaLarga(f) { return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' }); },
        nombrePaciente(id) { const p = this.PAC.find(x => String(x.id) === String(id)); return p ? p.nombre : ''; },
        nombreMedico(id) { const m = this.MED.find(x => String(x.id) === String(id)); return m ? m.nombre : '—'; },

        /* ---------- Selección ---------- */
        seleccionar(r) { if (!this.editando) this.sel = r; },

        /* ---------- Formulario ---------- */
        formNuevo(d = {}) {
            return Object.assign({ paciente_id: '', personal_id: D.medicoActual ? String(D.medicoActual) : (this.MED.length === 1 ? String(this.MED[0].id) : ''), diagnostico: '', indicaciones_generales: '', consulta_id: null, medicamentos: [medVacio()] }, d);
        },
        nueva() { if (this.editando) return; this.form = this.formNuevo(); this.abrirForm(); },
        duplicar(r) {
            if (!r || this.editando) return;
            this.form = this.formNuevo({ paciente_id: String(r.paciente_id), personal_id: r.personal_id ? String(r.personal_id) : '', diagnostico: r.diagnostico || '', indicaciones_generales: r.indicaciones || '', medicamentos: r.detalles.map(d => medVacio(d)) });
            this.abrirForm();
        },
        abrirForm() {
            this.errores = {}; this.mensajeForm = 'Ctrl+Enter emite la receta'; this.bPac = { q: '', abierto: false, i: 0 }; this.popMed = false; this.verResumen = false;
            this.editando = true;
            this.$nextTick(() => { const h = document.querySelector('.rx .rx-hoja'); h && h.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' }); });
            setTimeout(() => {
                const el = !this.form.paciente_id ? this.$refs.campoPaciente : (!this.form.diagnostico ? document.querySelector('.rx-form textarea') : document.querySelector('.rx-med-nom'));
                el && el.focus({ preventScroll: true });
            }, reduce ? 0 : 450);
        },
        cancelar() {
            this.editando = false; this.guardando = false;
            this.$nextTick(() => { const b = document.querySelector('.rx-acc .rx-btn'); if (b) b.focus({ preventScroll: true }); else if (document.activeElement) document.activeElement.blur(); });
        },
        get pacientesFiltrados() {
            const q = norm(this.bPac.q);
            return (q ? this.PAC.filter(p => norm(p.nombre + ' ' + (p.curp || '')).includes(q)) : this.PAC).slice(0, 8);
        },
        elegirPaciente(p) { this.form.paciente_id = String(p.id); this.bPac = { q: '', abierto: false, i: 0 }; this.errores.paciente_id = ''; },
        get alergiasForm() { const p = this.PAC.find(x => String(x.id) === String(this.form.paciente_id)); return p ? p.alergias : []; },
        alergiaDe(nombre) {
            const n = norm(nombre); if (n.length < 3) return '';
            const a = this.alergiasForm.find(x => { const k = norm(x); return k.length >= 3 && (n.includes(k) || k.includes(n.split(/\s+/)[0])); });
            return a || '';
        },
        get hayAlertaAlergia() { return this.editando && this.form.medicamentos.some(m => this.alergiaDe(m.nombre)); },
        sugerencias(t) {
            const q = norm(t); if (q.length < 2) return [];
            return this.PROD.filter(p => norm(p.nombre).includes(q) && norm(p.nombre) !== q).slice(0, 6);
        },
        agregarMed() { this.form.medicamentos.push(medVacio()); this.$nextTick(() => { const l = document.querySelectorAll('.rx-med-nom'); l.length && l[l.length - 1].focus(); }); },
        quitarMed(i) { this.form.medicamentos.splice(i, 1); },

        async emitir() {
            if (this.guardando) return;
            const f = this.form, e = {};
            const meds = f.medicamentos.filter(m => m.nombre.trim());
            if (!f.paciente_id) e.paciente_id = 'Elige al paciente.';
            if (!f.personal_id) e.personal_id = 'Elige al médico que firma la receta.';
            if (!String(f.diagnostico).trim()) e.diagnostico = 'Escribe el diagnóstico.';
            if (!meds.length) e.medicamentos = 'Agrega al menos un medicamento.';
            this.errores = e;
            if (Object.keys(e).length) { this.mensajeForm = 'Revisa los datos marcados.'; window.mtSacudir && mtSacudir('.rx-form-c'); return; }
            if (this.hayAlertaAlergia) {
                const r = await Swal.fire({ title: 'Posible alergia', icon: 'warning', iconColor: '#c8102e', html: '<p style="font-size:14px">Un medicamento coincide con una alergia registrada del paciente. ¿Emitir de todos modos?</p>', showCancelButton: true, reverseButtons: true, focusCancel: true, confirmButtonText: 'Emitir de todos modos', cancelButtonText: 'Revisar', confirmButtonColor: '#c8102e', cancelButtonColor: '#0b5a54' });
                if (!r.isConfirmed) return;
            }
            this.guardando = true; this.mensajeForm = 'Emitiendo…';
            try {
                const r = await fetch(URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ paciente_id: f.paciente_id, personal_id: f.personal_id || null, diagnostico: f.diagnostico, indicaciones_generales: f.indicaciones_generales, consulta_id: f.consulta_id, medicamentos: meds.map(m => ({ nombre: m.nombre, dosis: m.dosis, frecuencia: m.frecuencia, duracion: m.duracion })) })
                });
                const d = await r.json().catch(() => ({}));
                if (r.status === 422) {
                    Object.entries(d.errors || {}).forEach(([k, v]) => { this.errores[k.split('.')[0]] = v[0]; });
                    this.mensajeForm = d.message || 'Revisa los datos.'; this.guardando = false; window.mtSacudir && mtSacudir('.rx-form-c'); return;
                }
                if (!r.ok) throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo emitir la receta.'));
                this.R.unshift(d.receta); this.sel = this.R[0];
                this.PREFILL = null; this.cancelar();
                avisar((d.message || 'Receta emitida.') + ' Ya puedes imprimirla.');
                this.$nextTick(() => window.mtDestello && mtDestello('.rx-fila.sel'));
            } catch (err) { this.guardando = false; this.mensajeForm = err.message; avisar(err.message, 'error'); }
        },
        eliminar(r) {
            if (!r) return;
            Swal.fire({
                title: '¿Eliminar la receta #' + r.folio + '?', icon: 'warning', iconColor: '#c8102e',
                html: '<p style="font-size:14px">Se eliminará con todos sus medicamentos. Esta acción no se puede deshacer.</p>',
                showCancelButton: true, reverseButtons: true, focusCancel: true, confirmButtonText: 'Eliminar receta', cancelButtonText: 'Cancelar', confirmButtonColor: '#c8102e', cancelButtonColor: '#56625f',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const res = await fetch(`${URL}/${r.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                        const d = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo eliminar la receta.'));
                        return d;
                    } catch (err) { Swal.showValidationMessage(err.message); }
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then(res => {
                if (!res.isConfirmed) return;
                const quitar = () => { const i = this.R.findIndex(x => x.id === r.id); this.R.splice(i, 1); this.sel = this.R[Math.min(i, this.R.length - 1)] || null; };
                window.mtQuitar ? mtQuitar('.rx-fila.sel', quitar) : quitar();
                avisar((res.value && res.value.message) || 'Receta eliminada.');
            });
        },

        /* ---------- Teclado ---------- */
        tecla(e) {
            if (this.editando) {
                if (e.key === 'Escape') { e.preventDefault(); if (this.bPac.abierto || this.popMed || this.form.medicamentos.some(m => m.sug)) { this.bPac.abierto = false; this.popMed = false; this.form.medicamentos.forEach(m => m.sug = false); } else this.cancelar(); }
                else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.emitir(); }
                return;
            }
            const enCampo = /INPUT|SELECT|TEXTAREA/.test(e.target.tagName) || e.target.isContentEditable;
            if (enCampo) { if (e.key === 'Escape' && e.target === this.$refs.buscar) { this.q = ''; e.target.blur(); } return; }
            if (e.ctrlKey || e.metaKey || e.altKey || document.querySelector('.swal2-popup:not(.swal2-toast)') || document.querySelector('.ly-paleta:not([style*="none"])')) return;
            const l = this.visibles, i = this.sel ? l.findIndex(r => r.id === this.sel.id) : -1;
            if (e.key === '/') { e.preventDefault(); this.$refs.buscar.focus(); }
            else if (e.key.toLowerCase() === 'n') { e.preventDefault(); this.nueva(); }
            else if (e.key.toLowerCase() === 'p' && this.sel) { e.preventDefault(); window.open(this.sel.pdf, '_blank', 'noopener'); }
            else if (e.key === 'ArrowDown' && l.length) { e.preventDefault(); this.sel = l[Math.min(l.length - 1, i + 1)]; }
            else if (e.key === 'ArrowUp' && l.length) { e.preventDefault(); this.sel = l[Math.max(0, i - 1)]; }
        }
    };
}
</script>
@endverbatim
@endsection
