@extends('layouts.admin')

{{--
    MÓDULO DE CITAS · MediTrack
    Tablero del día (columnas por médico o por consultorio, línea de «ahora», clic en un espacio libre
    para agendar), vista de mes, calendario chico, pase de la cita que se voltea para editar
    (citas/modal.blade.php) y próximas citas. Guarda por AJAX: CitaController responde JSON.
--}}

@section('content')
@php
    $datosVista = array_merge($datosCitas ?? ['citas' => [], 'pacientes' => [], 'medicos' => [], 'consultorios' => []], [
        'url'  => url('citas'),
        'csrf' => csrf_token(),
    ]);
@endphp

<div class="ci" x-data="modCitas()" :class="{ 'editando': editando }" @keydown.window="tecla($event)">

    <!-- BÚSQUEDA Y ALTA -->
    <header class="ci-top">
        <label class="ci-cmd">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="ci-sr">Buscar cita</span>
            <input type="search" x-ref="buscar" x-model.debounce.150ms="q" autocomplete="off" placeholder="Busca una cita por paciente o motivo">
            <kbd aria-hidden="true">/</kbd>
        </label>
        <button type="button" class="ci-dark" @click="nueva()">Agendar cita <kbd>N</kbd></button>
    </header>

    <!-- CABECERA: fecha, navegación, indicadores y vista -->
    <section class="ci-cab">
        <div class="ci-g">
            <b class="t" x-text="vista === 'dia' ? Number(dia.slice(8)) : nombreMes(mesNav)"></b>
            <span>
                <em x-text="vista === 'dia' ? diaSemana(dia) : mesNav.slice(0, 4)"></em>
                <span x-text="vista === 'dia' ? nombreMes(dia.slice(0, 7)).replace(/^./, l => l.toUpperCase()) + ' ' + dia.slice(0, 4) : (contarMes() + (contarMes() === 1 ? ' cita' : ' citas'))"></span>
            </span>
        </div>
        <div class="ci-nav">
            <button type="button" class="ci-ico" @click="mover(-1)" :aria-label="vista === 'dia' ? 'Día anterior' : 'Mes anterior'"><i class="bi bi-chevron-left"></i></button>
            <button type="button" class="ci-hoy" @click="irHoy()" :disabled="vista === 'dia' ? dia === hoy : mesNav === hoy.slice(0, 7)">Hoy</button>
            <button type="button" class="ci-ico" @click="mover(1)" :aria-label="vista === 'dia' ? 'Día siguiente' : 'Mes siguiente'"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="ci-ind">
            <template x-for="e in ESTADOS" :key="'i' + e.valor">
                <button type="button" :class="'e-' + e.slug" @click="filtroEstado = filtroEstado === e.valor ? '' : e.valor" :aria-pressed="(filtroEstado === e.valor).toString()">
                    <span x-text="e.plural"></span>
                    <b class="t" x-text="resumen[e.valor] || 0"></b>
                </button>
            </template>
        </div>
        <div class="ci-ctrl">
            <div class="ci-seg-mini" role="group" aria-label="Vista">
                <button type="button" :aria-pressed="(vista === 'dia').toString()" @click="cambiarVista('dia')">Día</button>
                <button type="button" :aria-pressed="(vista === 'mes').toString()" @click="cambiarVista('mes')">Mes</button>
            </div>
            <div class="ci-seg-mini" role="group" aria-label="Columnas" x-show="vista === 'dia'">
                <button type="button" :aria-pressed="(agrupar === 'medico').toString()" @click="agrupar = 'medico'">Por médico</button>
                <button type="button" :aria-pressed="(agrupar === 'consultorio').toString()" @click="agrupar = 'consultorio'">Por consultorio</button>
            </div>
            <div class="ci-filtro" @click.outside="popMed = false" @keydown.escape.stop="popMed = false">
                <button type="button" class="ci-pick ci-pick-s" :class="{ 'abierto': popMed }" @click="popMed = !popMed" :aria-expanded="popMed.toString()" aria-label="Filtrar por médico">
                    <span x-text="filtroMed ? nombreMedico(filtroMed) : 'Todos los médicos'"></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="ci-pop ci-pop-flot" x-show="popMed" x-cloak>
                    <button type="button" class="ci-op" :class="{ 'on': !filtroMed }" @click="filtroMed = ''; popMed = false"><b>Todos los médicos</b></button>
                    <template x-for="m in doctores" :key="'om' + m.id">
                        <button type="button" class="ci-op" :class="{ 'on': filtroMed === String(m.id) }" @click="filtroMed = String(m.id); popMed = false">
                            <b x-text="m.nombre"></b><small x-text="m.especialidad || 'Médico'"></small>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </section>

    <section class="ci-escenario">
        <div class="ci-centro">
            <!-- TABLERO DEL DÍA -->
            <div class="ci-tablero" x-show="vista === 'dia'">
                <div class="ci-tb-scroll" x-ref="tablero">
                    <div class="ci-tb-grid" :style="'grid-template-columns: 58px repeat(' + columnas.length + ', minmax(158px, 1fr))'">
                        <div class="ci-tb-esq"></div>
                        <template x-for="col in columnas" :key="'h' + col.clave + col.id">
                            <div class="ci-tb-th">
                                <b x-text="col.titulo"></b>
                                <small x-text="col.sub"></small>
                                <span class="t" x-text="bloques(col).filter(b => b.c.estado !== 'Cancelada').length"></span>
                            </div>
                        </template>

                        <div class="ci-tb-horas" :style="'height:' + altoTablero + 'px'">
                            <template x-for="h in horas" :key="'hr' + h">
                                <span class="t" :style="'top:' + ((h - rango.ini) * 60 * PX) + 'px'" x-text="String(h).padStart(2, '0') + ':00'"></span>
                            </template>
                            <b class="ci-ahora-et t" x-show="esHoyDia && minAhora >= rango.ini * 60 && minAhora <= rango.fin * 60" :style="'top:' + ((minAhora - rango.ini * 60) * PX) + 'px'" x-text="horaAhora"></b>
                        </div>

                        <template x-for="col in columnas" :key="'c' + col.clave + col.id">
                            <div class="ci-tb-col" :style="'height:' + altoTablero + 'px; background-size: 100% ' + (60 * PX) + 'px, 100% ' + (30 * PX) + 'px'"
                                 :class="{ 'pasado': dia < hoy }"
                                 @mousemove="fantasma = $event.target === $event.currentTarget ? { col: col.clave + col.id, min: minutoDe($event) } : null" @mouseleave="fantasma = null"
                                 @click.self="clicEspacio(col, $event)">
                                <div class="ci-fantasma t" x-show="fantasma && fantasma.col === col.clave + col.id && dia >= hoy && libreEn(col, fantasma.min)"
                                     :style="fantasma ? 'top:' + ((fantasma.min - rango.ini * 60) * PX) + 'px; height:' + (30 * PX) + 'px' : ''"
                                     x-text="fantasma ? '+ ' + aHora(fantasma.min) : ''"></div>
                                <template x-for="b in bloques(col)" :key="'b' + b.c.id">
                                    <button type="button" class="ci-bloque" :class="'e-' + slug(b.c.estado) + (sel && sel.id === b.c.id ? ' sel' : '') + (b.alto < 30 ? ' bajo' : '')"
                                            :style="'top:' + b.top + 'px; height:' + b.alto + 'px; left: calc(' + b.izq + '% + 4px); width: calc(' + b.ancho + '% - 8px)'"
                                            @click="seleccionar(b.c)" :title="b.c.hora + '–' + horaFin(b.c) + ' · ' + b.c.paciente + ' · ' + b.c.estado">
                                        <span class="ci-b-h t"><span x-text="b.c.hora"></span><i x-show="b.c.alergia" class="bi bi-exclamation-triangle-fill" title="Tiene alergias"></i></span>
                                        <b x-text="b.c.paciente"></b>
                                        <small x-show="b.alto >= 54" x-text="b.c.motivo"></small>
                                    </button>
                                </template>
                                <div class="ci-ahora" x-show="esHoyDia && minAhora >= rango.ini * 60 && minAhora <= rango.fin * 60" :style="'top:' + ((minAhora - rango.ini * 60) * PX) + 'px'"></div>
                            </div>
                        </template>
                    </div>
                </div>
                <footer class="ci-tb-pie">
                    <span x-text="delDia.length ? delDia.filter(c => c.estado !== 'Cancelada').length + ' citas este día' : 'Sin citas este día'"></span>
                    <span class="ci-pista">Toca un espacio libre para agendar · <kbd>←</kbd><kbd>→</kbd> cambian de día</span>
                </footer>
            </div>

            <!-- MES -->
            <div class="ci-mes" x-show="vista === 'mes'" x-cloak>
                <div class="ci-mes-sem">
                    <template x-for="d in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']" :key="'s' + d"><span x-text="d"></span></template>
                </div>
                <div class="ci-mes-grid">
                    <template x-for="cel in celdasMes(mesNav)" :key="'m' + cel.fecha">
                        <button type="button" class="ci-mes-cel" :class="{ 'fuera': !cel.dentro, 'hoy': cel.fecha === hoy, 'sel': cel.fecha === dia, 'pasado': cel.fecha < hoy }"
                                @click="irDia(cel.fecha, true)">
                            <span class="ci-mes-n t" x-text="Number(cel.fecha.slice(8))"></span>
                            <template x-for="c in porFecha(cel.fecha).slice(0, 3)" :key="'mc' + c.id">
                                <span class="ci-mes-chip" :class="'e-' + slug(c.estado)"><i class="t" x-text="c.hora"></i><span x-text="c.paciente"></span></span>
                            </template>
                            <small x-show="porFecha(cel.fecha).length > 3" x-text="'+' + (porFecha(cel.fecha).length - 3) + ' más'"></small>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <aside class="ci-lado">
            {{-- Pase de la cita (frente) y su formulario (reverso) --}}
            @include('citas.modal')

            <!-- Calendario chico -->
            <div class="ci-mini">
                <div class="ci-mini-h">
                    <button type="button" class="ci-ico ci-ico-s" @click="mesNav = sumarMes(mesNav, -1)" aria-label="Mes anterior"><i class="bi bi-chevron-left"></i></button>
                    <b x-text="nombreMes(mesNav) + ' ' + mesNav.slice(0, 4)"></b>
                    <button type="button" class="ci-ico ci-ico-s" @click="mesNav = sumarMes(mesNav, 1)" aria-label="Mes siguiente"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="ci-mini-g">
                    <template x-for="d in ['L', 'M', 'M', 'J', 'V', 'S', 'D']"><span class="ci-mini-s" x-text="d"></span></template>
                    <template x-for="cel in celdasMes(mesNav)" :key="'n' + cel.fecha">
                        <button type="button" class="t" :class="{ 'fuera': !cel.dentro, 'hoy': cel.fecha === hoy, 'sel': cel.fecha === dia }"
                                :data-n="Math.min(3, Math.ceil(porFecha(cel.fecha).filter(c => c.estado !== 'Cancelada').length / 2))"
                                @click="irDia(cel.fecha, true)" :aria-label="fechaLarga(cel.fecha) + ', ' + porFecha(cel.fecha).length + ' citas'">
                            <span x-text="Number(cel.fecha.slice(8))"></span>
                        </button>
                    </template>
                </div>
            </div>


            <!-- Próximas o coincidencias de la búsqueda -->
            <div class="ci-prox">
                <h3 x-text="q.trim() ? 'Coincidencias' : 'Próximas citas'"></h3>
                <template x-for="c in listaLateral" :key="'p' + c.id">
                    <button type="button" :class="{ 'sel': sel && sel.id === c.id }" @click="seleccionar(c, true)">
                        <span class="ci-prox-h t" :class="'e-' + slug(c.estado)"><b x-text="c.hora"></b><small x-text="fechaCorta(c.fecha)"></small></span>
                        <span class="ci-prox-n"><b x-text="c.paciente"></b><small x-text="cuando(c)"></small></span>
                    </button>
                </template>
                <p class="ci-vacio" x-show="!listaLateral.length" x-text="q.trim() ? 'Ninguna cita coincide.' : 'No hay citas próximas.'"></p>
            </div>
        </aside>
    </section>
</div>

<script type="application/json" id="ci-datos">{!! json_encode($datosVista, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@verbatim
<style>
.ci {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1); --flip: cubic-bezier(.3, .7, .1, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.ci * { box-sizing: border-box; }
.ci :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.ci button { cursor: pointer; }
.ci button:disabled { cursor: default; }
.ci .t { font-variant-numeric: tabular-nums; }
.ci-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.ci kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); background: transparent; }
[x-cloak] { display: none !important; }

/* Estados de la cita (color fijo por significado) */
.ci .e-pendiente { --c: var(--ambar); --cv: var(--ambarv); }
.ci .e-confirmada { --c: var(--verde); --cv: var(--verdev); }
.ci .e-en-curso { --c: var(--cred); --cv: var(--velo); }
.ci .e-finalizada { --c: var(--media); --cv: var(--niebla); }
.ci .e-cancelada { --c: var(--rosa); --cv: var(--rosav); }
.ci .e-vacio { --c: var(--media); --cv: var(--niebla); }

/* ---------- Búsqueda ---------- */
.ci-top { display: flex; align-items: center; gap: 12px; }
.ci-cmd { flex: 1; min-width: 0; max-width: 760px; height: 50px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 12px 30px -22px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 16px; }
.ci-cmd > .bi { color: var(--cred); }
.ci-cmd input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 450 15px var(--ui); color: var(--tinta); }
.ci-cmd input::placeholder { color: var(--media); }
.ci-dark { height: 44px; border-radius: 12px; background: var(--tinta); color: #fff; font: 600 14px var(--ui); display: flex; align-items: center; gap: 10px; padding: 0 12px 0 16px; white-space: nowrap; }
.ci-dark kbd { border-color: rgba(255, 255, 255, .25); color: #cfe; }
@container (max-width: 639px) { .ci-cmd > kbd, .ci-dark kbd { display: none; } .ci-dark { padding: 0 14px; } }

/* ---------- Cabecera ---------- */
.ci-cab { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px 24px; }
.ci-g { display: flex; align-items: flex-end; gap: 12px; min-width: 0; }
.ci-g > b { font: 800 84px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); text-transform: capitalize; white-space: nowrap; }
.ci-g > span { display: flex; flex-direction: column; padding-bottom: 2px; font-size: 15px; font-weight: 500; color: var(--texto); line-height: 1.2; }
.ci-g em { text-transform: capitalize; }
.ci-g em { font-style: normal; font: 700 20px/1.1 var(--disp); color: var(--tinta); }
.ci-nav { display: flex; align-items: center; gap: 6px; padding-bottom: 2px; }
.ci-ico { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; border: 1px solid var(--linea); background: var(--papel); color: var(--texto); transition: background .15s; }
.ci-ico:hover { background: var(--niebla); color: var(--tinta); }
.ci-ico-s { width: 30px; height: 30px; border-radius: 8px; }
.ci-hoy { height: 38px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); }
.ci-hoy:disabled { color: var(--cred); background: var(--velo); border-color: var(--velo); }
.ci-ind { flex: 1; min-width: 300px; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 0 14px; }
.ci-ind button { display: flex; justify-content: space-between; align-items: baseline; gap: 6px; padding: 8px 0 6px; text-align: left; background: none; border-top: 2px solid var(--linea); font-size: 13px; font-weight: 500; color: var(--texto); white-space: nowrap; }
.ci-ind button span { overflow: hidden; text-overflow: ellipsis; }
.ci-ind button b { font: 750 22px/1 var(--disp); color: var(--c); }
.ci-ind button[aria-pressed=true] { border-top-color: var(--c); }
.ci-ind button[aria-pressed=true] span { color: var(--tinta); font-weight: 650; }
.ci-ctrl { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; width: 100%; }
.ci-seg-mini { display: inline-flex; padding: 3px; border-radius: 11px; background: var(--papel); border: 1px solid var(--linea); }
.ci-seg-mini button { height: 32px; padding: 0 12px; border-radius: 8px; font: 600 13px var(--ui); color: var(--media); background: none; }
.ci-seg-mini button[aria-pressed=true] { background: var(--tinta); color: #fff; }
.ci-filtro { position: relative; min-width: 220px; }
.ci-pick { width: 100%; height: 40px; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 0 12px; border: 1px solid var(--linea); border-radius: 10px; background: var(--papel); font: 500 14px var(--ui); color: var(--tinta); text-align: left; transition: border-color .15s, box-shadow .15s; }
.ci-pick span { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-pick.t span { font-family: var(--mono); }
.ci-pick .bi { flex: none; font-size: 13px; color: var(--cred); transition: transform .2s var(--e); }
.ci-pick.vacio span { color: var(--media); }
.ci-pick:hover { border-color: #b9cbc6; }
.ci-pick.abierto { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.ci-pick.abierto .bi-chevron-down { transform: rotate(180deg); }
.ci-pick-s { font-weight: 600; font-size: 13px; }
.ci-campo.mal .ci-pick { border-color: var(--rosa); }
.ci-pop { margin-top: 2px; padding: 4px; max-height: 260px; overflow-y: auto; background: var(--papel); border: 1px solid var(--linea); border-radius: 12px; box-shadow: 0 16px 32px -22px rgba(11, 30, 28, .55); scrollbar-width: thin; }
.ci-pop-flot { position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 30; margin: 0; }
.ci-pop-rej { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; }
.ci-op { width: 100%; display: flex; flex-direction: column; align-items: flex-start; padding: 7px 10px; border-radius: 8px; text-align: left; background: none; min-width: 0; }
.ci-op:hover:not(:disabled) { background: var(--niebla); }
.ci-op.on { background: var(--velo); box-shadow: inset 3px 0 0 var(--cred); }
.ci-op:disabled { opacity: .45; cursor: not-allowed; }
.ci-op b { max-width: 100%; font-size: 13.5px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-op small { font-size: 12px; color: var(--media); }
.ci-pop-vacio { padding: 8px 10px; font-size: 13px; color: var(--media); }
.ci-pop-cal, .ci-pop-hora { max-height: none; padding: 10px; }
.ci-pop-cal .ci-mini-g button { height: 32px; }
.ci-pop-cal .ci-mini-g button:disabled { color: #b7c2bf; cursor: not-allowed; background: none; }
.ci-pop-t { font-size: 12px; font-weight: 600; color: var(--media); margin: 2px 2px 6px; }
.ci-horas { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; margin-bottom: 10px; }
.ci-mins { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 4px; }
.ci-horas button, .ci-mins button { height: 36px; border-radius: 9px; border: 1px solid var(--linea); background: var(--papel); font: 500 14px var(--mono); color: var(--tinta); }
.ci-horas button:hover, .ci-mins button:hover:not(:disabled) { border-color: var(--teal); }
.ci-horas button.lleno { color: #a3b0ad; background: repeating-linear-gradient(135deg, #f4f7f6 0 4px, #fff 4px 8px); }
.ci-horas button.on, .ci-mins button.on { background: var(--cred); border-color: var(--cred); color: #fff; }
.ci-mins button:disabled { opacity: .4; cursor: not-allowed; }
.ci-exacta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
.ci-exacta input { width: 58px; height: 48px; border: 1px solid var(--linea); border-radius: 10px; text-align: center; font: 600 24px var(--mono); color: var(--tinta); background: var(--papel); outline: 0; transition: border-color .15s, box-shadow .15s; }
.ci-exacta input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.ci-exacta input::placeholder { color: #b7c2bf; font-size: 18px; }
.ci-exacta b { font: 600 24px var(--mono); color: var(--media); }
.ci-exacta span { flex-basis: 100%; font-size: 12px; color: var(--media); }
.ci-pop-nota { margin-top: 8px; font-size: 12px; color: var(--media); }
.ci-err { margin-top: -6px; font-size: 12px; font-weight: 500; color: var(--rosa); }
.ci-sel { height: 40px; border: 1px solid var(--linea); border-radius: 11px; padding: 0 32px 0 12px; font: 600 13px var(--ui); color: var(--tinta); background-color: var(--papel); max-width: 260px; }
@container (max-width: 759px) { .ci-g > b { font-size: 64px; } .ci-ind { min-width: 100%; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 12px; } }

/* ---------- Escenario ---------- */
.ci-escenario { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
@container (min-width: 1100px) { .ci-escenario { grid-template-columns: minmax(0, 1fr) 340px; } }
.ci-centro { min-width: 0; transition: opacity .35s, filter .35s; }

/* Tablero del día */
.ci-tablero { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; box-shadow: 0 18px 40px -32px rgba(11, 30, 28, .5); }
.ci-tb-scroll { max-height: 640px; overflow: auto; overscroll-behavior: contain; scrollbar-width: thin; }
.ci-tb-grid { display: grid; grid-template-rows: auto 1fr; min-width: 100%; }
.ci-tb-esq, .ci-tb-th { position: sticky; top: 0; z-index: 6; background: #f8fbfa; border-bottom: 1px solid var(--linea); }
.ci-tb-esq { left: 0; z-index: 8; border-right: 1px solid var(--linea2); }
.ci-tb-th { position: sticky; display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; column-gap: 8px; padding: 10px 12px; border-left: 1px solid var(--linea2); min-width: 0; }
.ci-tb-th b { font: 700 15px/1.15 var(--disp); font-stretch: 88%; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-tb-th small { grid-column: 1; font-size: 12px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-tb-th span { grid-column: 2; grid-row: 1 / 3; min-width: 26px; height: 26px; padding: 0 6px; border-radius: 8px; display: grid; place-items: center; background: var(--velo); color: var(--cred); font: 600 12px var(--mono); }
.ci-tb-horas { position: sticky; left: 0; z-index: 5; background: #fbfdfc; border-right: 1px solid var(--linea2); }
.ci-tb-horas span { position: absolute; right: 8px; transform: translateY(-50%); font: 500 12px var(--mono); color: var(--media); }
.ci-tb-horas span:first-child { transform: none; top: 4px !important; }
.ci-ahora-et { position: absolute; right: 4px; transform: translateY(-50%); z-index: 2; padding: 1px 5px; border-radius: 5px; background: var(--teal); color: #fff; font: 600 11.5px var(--mono); }
.ci-tb-col { position: relative; border-left: 1px solid var(--linea2); cursor: copy; background-image: linear-gradient(var(--linea) 1px, transparent 1px), linear-gradient(var(--linea2) 1px, transparent 1px); }
.ci-tb-col.pasado { cursor: default; background-color: #fafcfb; }
.ci-fantasma { position: absolute; left: 4px; right: 4px; z-index: 1; pointer-events: none; border: 1.5px dashed var(--teal); border-radius: 8px; background: rgba(14, 124, 114, .07); color: var(--cred); font: 600 12px var(--mono); padding: 3px 8px; }
.ci-ahora { position: absolute; left: 0; right: 0; z-index: 4; height: 2px; background: var(--teal); pointer-events: none; }

.ci-bloque { position: absolute; z-index: 2; display: flex; flex-direction: column; gap: 0; padding: 3px 8px 3px 10px; overflow: hidden; text-align: left; border-radius: 8px; background: var(--cv); color: var(--tinta); box-shadow: inset 3px 0 0 var(--c), 0 1px 0 rgba(11, 30, 28, .04); transition: box-shadow .15s, transform .15s; }
.ci-bloque:hover { transform: translateY(-1px); box-shadow: inset 3px 0 0 var(--c), 0 8px 16px -10px rgba(11, 30, 28, .5); z-index: 3; }
.ci-bloque.sel { box-shadow: inset 3px 0 0 var(--c), 0 0 0 2px var(--teal), 0 10px 20px -12px rgba(11, 30, 28, .6); z-index: 3; }
.ci-b-h { display: flex; align-items: center; gap: 6px; font: 600 12px/1.2 var(--mono); color: var(--c); }
.ci-b-h i { font-size: 11px; color: var(--rosa); }
.ci-bloque b { font-size: 13px; font-weight: 650; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-bloque small { font-size: 12px; color: var(--media); line-height: 1.25; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.ci-bloque.bajo { flex-direction: row; align-items: center; gap: 6px; padding-top: 0; padding-bottom: 0; }
.ci-bloque.e-en-curso { background: linear-gradient(135deg, #0d6a63, var(--cred2)); color: #fff; box-shadow: inset 3px 0 0 var(--foil); }
.ci-bloque.e-en-curso .ci-b-h { color: var(--foil); }
.ci-bloque.e-en-curso small { color: #cdeee7; }
.ci-bloque.e-en-curso.sel { box-shadow: inset 3px 0 0 var(--foil), 0 0 0 2px var(--teal); }
.ci-bloque.e-cancelada { background: repeating-linear-gradient(135deg, var(--rosav) 0 6px, #fff 6px 12px); opacity: .75; }
.ci-bloque.e-cancelada b { text-decoration: line-through; color: var(--media); }
.ci-bloque.e-finalizada b { color: var(--texto); }
.ci-tb-pie { display: flex; flex-wrap: wrap; gap: 6px 16px; align-items: center; justify-content: space-between; padding: 12px 16px; border-top: 1px solid var(--linea2); font-size: 12.5px; color: var(--media); }
.ci-tb-pie kbd { margin: 0 1px; }
@container (max-width: 639px) { .ci-pista { display: none; } }

/* Mes */
.ci-mes { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; }
.ci-mes-sem, .ci-mes-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
.ci-mes-sem span { padding: 10px 10px 8px; font-size: 12px; font-weight: 600; color: var(--media); border-bottom: 1px solid var(--linea); }
.ci-mes-cel { min-height: 112px; display: flex; flex-direction: column; gap: 3px; padding: 8px; text-align: left; background: var(--papel); border-right: 1px solid var(--linea2); border-bottom: 1px solid var(--linea2); min-width: 0; transition: background .15s; }
.ci-mes-cel:nth-child(7n) { border-right: 0; }
.ci-mes-cel:hover { background: #f7faf9; }
.ci-mes-cel.fuera { background: #fafcfb; }
.ci-mes-cel.fuera .ci-mes-n { color: #a3b0ad; }
.ci-mes-n { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font: 600 13px var(--mono); color: var(--tinta); }
.ci-mes-cel.hoy .ci-mes-n { background: var(--cred); color: #fff; }
.ci-mes-cel.sel { box-shadow: inset 0 0 0 2px var(--teal); }
.ci-mes-chip { display: flex; gap: 5px; align-items: center; min-width: 0; padding: 2px 6px; border-radius: 6px; background: var(--cv); font-size: 12px; font-weight: 600; color: var(--tinta); }
.ci-mes-chip i { font-style: normal; font: 600 11px var(--mono); color: var(--c); }
.ci-mes-chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-mes-chip.e-cancelada span { text-decoration: line-through; color: var(--media); }
.ci-mes-cel small { font-size: 12px; font-weight: 600; color: var(--cred); padding-left: 4px; }
@container (max-width: 759px) { .ci-mes-cel { min-height: 64px; padding: 4px; } .ci-mes-chip span { display: none; } }

/* ---------- Lado ---------- */
.ci-lado { display: grid; gap: 14px; align-content: start; }
@container (min-width: 700px) and (max-width: 1099px) { .ci-lado { grid-template-columns: 1fr 1fr; } .ci-pase, .ci-acc { grid-column: 1; } .ci-mini { grid-column: 2; grid-row: 1; } .ci-prox { grid-column: 2; grid-row: 2 / 4; } .ci-pase { grid-row: 1; } .ci-acc { grid-row: 2; } }

.ci-mini { background: var(--papel); border: 1px solid var(--linea); border-radius: 16px; padding: 12px; }
.ci-mini-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.ci-mini-h b { font: 700 16px var(--disp); color: var(--tinta); text-transform: capitalize; }
.ci-mini-g { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.ci-mini-s { text-align: center; font-size: 12px; font-weight: 600; color: var(--media); padding: 2px 0 4px; }
.ci-mini-g button { position: relative; height: 34px; border-radius: 9px; font: 500 13px var(--mono); color: var(--tinta); background: none; }
.ci-mini-g button:hover { background: var(--niebla); }
.ci-mini-g button.fuera { color: #a3b0ad; }
.ci-mini-g button.hoy { font-weight: 700; color: var(--cred); }
.ci-mini-g button.sel { background: var(--cred); color: #fff; }
.ci-mini-g button:after { content: ""; position: absolute; left: 50%; bottom: 4px; width: 5px; height: 5px; margin-left: -2.5px; border-radius: 50%; background: transparent; }
.ci-mini-g button[data-n="1"]:after { background: #9fd3c8; }
.ci-mini-g button[data-n="2"]:after { background: var(--teal); }
.ci-mini-g button[data-n="3"]:after { background: var(--cred2); width: 7px; margin-left: -3.5px; }
.ci-mini-g button.sel:after { background: var(--foil) !important; }

/* Pase (frente y reverso) */
.ci-pase { perspective: 1400px; }
.ci-pase-in { position: relative; height: 410px; transform-style: preserve-3d; transition: transform .85s var(--flip), height .5s var(--e); }
.ci-pase.volteado .ci-pase-in { transform: rotateY(180deg); height: 700px; }
.ci-cara { position: absolute; inset: 0; border-radius: 22px; overflow: hidden; backface-visibility: hidden; -webkit-backface-visibility: hidden; }
.ci-frente { color: #eafaf6; background: linear-gradient(155deg, #0d6a63, var(--cred) 45%, var(--cred2)); box-shadow: 0 24px 44px -26px rgba(7, 63, 59, .9), inset 0 0 0 1px rgba(166, 239, 226, .18); }
.ci-gl, .ci-gl svg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.ci-fr { position: relative; height: 100%; display: flex; flex-direction: column; padding: 18px 22px 0; }
.ci-fr header { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.ci-k { font: 500 12px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: var(--foil); }
.ci-k b { font-weight: 600; color: #fff; }
.ci-tipo { padding: 2px 9px; border-radius: 999px; border: 1px solid rgba(166, 239, 226, .4); font-size: 12px; font-weight: 600; color: #dff7f1; }
.ci-hora { margin-top: 14px; display: flex; align-items: baseline; gap: 8px; font: 600 40px/1 var(--mono); letter-spacing: -.02em; color: #fff; }
.ci-hora small { font: 500 17px var(--mono); color: var(--foil); }
.ci-fecha { margin-top: 6px; font-size: 13.5px; color: #cdeee7; }
.ci-fecha:first-letter { text-transform: uppercase; }
.ci-fr h2 { margin-top: 14px; font: 780 27px/1.02 var(--disp); font-stretch: 78%; color: #fff; text-transform: uppercase; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ci-alg { margin-top: 8px; align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: var(--rosav); color: var(--rosa); font-size: 12.5px; font-weight: 650; }
.ci-fr dl { margin-top: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.ci-fr dt { font-size: 12px; color: #a9d8cf; }
.ci-fr dd { font-size: 14px; font-weight: 600; color: #fff; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ci-motivo { margin-top: 10px; font-size: 13.5px; color: #dff7f1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ci-franja { margin: auto -22px 0; display: flex; align-items: center; gap: 10px; min-height: 46px; padding: 10px 22px; font-size: 13.5px; font-weight: 600; color: #fff; background: rgba(4, 36, 33, .55); border-top: 1px solid rgba(166, 239, 226, .2); }
.ci-franja i { width: 9px; height: 9px; border-radius: 50%; background: var(--c); box-shadow: 0 0 0 3px rgba(255, 255, 255, .85); }
.ci-frente.e-pendiente .ci-franja { background: rgba(154, 88, 0, .72); }
.ci-frente.e-confirmada .ci-franja { background: rgba(8, 115, 79, .6); }
.ci-frente.e-cancelada .ci-franja { background: rgba(200, 16, 46, .7); }
.ci-frente.e-finalizada .ci-franja { background: rgba(44, 61, 58, .7); }
.ci-frente.e-en-curso .ci-franja i { background: var(--foil); }
.ci-fr-vacio h2 { text-transform: none; font-size: 30px; margin-top: 24px; }
.ci-fr-vacio p { margin-top: 10px; font-size: 14px; color: #cdeee7; }

.ci-reverso { transform: rotateY(180deg); background: #fbfdfc; border: 1px solid var(--linea); box-shadow: 0 24px 44px -30px rgba(11, 30, 28, .55); display: flex; flex-direction: column; }
.ci-rv-k { padding: 16px 18px 8px; font: 500 12px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: var(--cred); }
.ci-rv-cuerpo { flex: 1; min-height: 0; overflow-y: auto; padding: 4px 18px 14px; display: flex; flex-direction: column; gap: 12px; scrollbar-width: thin; }
.ci-rv-cuerpo > * { flex-shrink: 0; }
.ci-campo { position: relative; display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.ci-campo > span { font-size: 13px; font-weight: 600; color: var(--tinta); }
.ci-ayuda { font-size: 12px; font-weight: 500; color: var(--media); }
.ci-campo input, .ci-campo select { width: 100%; height: 40px; border: 1px solid var(--linea); border-radius: 10px; padding: 0 10px; font: 500 14px var(--ui); color: var(--tinta); background-color: var(--papel); outline: 0; transition: border-color .15s, box-shadow .15s; }
.ci-campo input:focus, .ci-campo select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.ci-campo.mal input, .ci-campo.mal select { border-color: var(--rosa); }
.ci-campo > small { font-size: 12px; font-weight: 500; color: var(--rosa); }
.ci-dos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.ci-combo { position: relative; }
.ci-combo input.elegido::placeholder { color: var(--tinta); font-weight: 600; }
.ci-combo-lista { position: static; margin-top: 4px; max-height: 240px; overflow-y: auto; padding: 4px; background: var(--papel); border: 1px solid var(--linea); border-radius: 12px; box-shadow: 0 20px 40px -20px rgba(11, 30, 28, .5); }
.ci-combo-lista button { width: 100%; display: grid; grid-template-columns: minmax(0, 1fr) auto; column-gap: 8px; padding: 7px 9px; border-radius: 8px; text-align: left; background: none; }
.ci-combo-lista button.on { background: var(--velo); }
.ci-combo-lista b { font-size: 13px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-combo-lista small { grid-column: 1; font: 500 11.5px var(--mono); color: var(--media); }
.ci-combo-lista em { grid-column: 2; grid-row: 1 / 3; align-self: center; font-style: normal; font-size: 11.5px; font-weight: 650; color: var(--rosa); background: var(--rosav); border-radius: 999px; padding: 1px 8px; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ci-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.ci-chip { height: 34px; padding: 0 11px; border-radius: 9px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--texto); }
.ci-chip.t { font-family: var(--mono); font-weight: 500; }
.ci-chip.on { border-color: var(--cred); background: var(--velo); color: var(--cred); }
.ci-seg { display: grid; gap: 5px; }
.ci-seg-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.ci-seg-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.ci-seg button { height: 36px; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 0 4px; border-radius: 9px; border: 1px solid var(--linea); background: var(--papel); font: 600 12.5px var(--ui); color: var(--texto); white-space: nowrap; overflow: hidden; }
.ci-seg button i { flex: none; width: 7px; height: 7px; border-radius: 50%; background: var(--c); }
.ci-seg button[aria-pressed=true] { border-color: var(--c, var(--cred)); background: var(--cv, var(--velo)); color: var(--c, var(--cred)); }
.ci-rv-pie { display: flex; align-items: center; gap: 8px; padding: 12px 18px; border-top: 1px solid var(--linea2); background: var(--papel); }
.ci-msg { flex: 1; min-width: 0; font-size: 12px; color: var(--media); line-height: 1.3; }
.ci-msg.mal { color: var(--rosa); font-weight: 600; }

/* Botones */
.ci-btn { height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border-radius: 11px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); white-space: nowrap; transition: background .15s, border-color .15s; }
.ci-btn:hover { background: #f7faf9; }
.ci-prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.ci-prim:hover { background: var(--cred2); }
.ci-prim:disabled { opacity: .6; }
.ci-prim kbd { border-color: rgba(255, 255, 255, .3); color: #cfe; }
.ci-borrar { color: var(--rosa); border-color: #f3c6cf; width: 40px; padding: 0; }
.ci-borrar:hover { background: var(--rosav); }
.ci-ancho { width: 100%; }
.ci-acc-in { display: grid; gap: 8px; }
.ci-acc-fila { display: flex; gap: 8px; }
.ci-acc-fila .ci-btn:not(.ci-borrar) { flex: 1; }

/* Próximas */
.ci-prox { background: var(--papel); border: 1px solid var(--linea); border-radius: 16px; padding: 12px 8px 8px; }
.ci-prox h3 { padding: 0 8px 6px; font: 700 16px var(--disp); color: var(--tinta); }
.ci-prox button { width: 100%; display: flex; align-items: center; gap: 12px; padding: 8px; border-radius: 10px; text-align: left; background: none; }
.ci-prox button:hover, .ci-prox button.sel { background: var(--niebla); }
.ci-prox-h { flex: none; width: 58px; display: flex; flex-direction: column; align-items: center; padding: 4px 0; border-radius: 8px; background: var(--cv); }
.ci-prox-h b { font: 600 14px var(--mono); color: var(--c); }
.ci-prox-h small { font-size: 11.5px; color: var(--media); }
.ci-prox-n { min-width: 0; display: flex; flex-direction: column; }
.ci-prox-n b { font-size: 13.5px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-prox-n small { font-size: 12px; color: var(--media); }
.ci-vacio { padding: 6px 8px 8px; font-size: 13px; color: var(--media); }

/* Edición: lo demás se atenúa */
.ci.editando .ci-centro, .ci.editando .ci-prox, .ci.editando .ci-mini, .ci.editando .ci-cab { opacity: .35; filter: saturate(.6); pointer-events: none; }

@media (prefers-reduced-motion: reduce) {
    .ci-pase-in { transition: none; }
    .ci-pase.volteado .ci-pase-in { transform: none; }
    .ci-pase.volteado .ci-frente { visibility: hidden; }
    .ci-reverso { transform: none; visibility: hidden; }
    .ci-pase.volteado .ci-reverso { visibility: visible; }
    .ci-bloque { transition: none; }
}
</style>

<script>
function modCitas() {
    const D = JSON.parse(document.getElementById('ci-datos').textContent);
    const URL = D.url, CSRF = D.csrf;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const avisar = (m, t = 'success') => { if (window.notificar) window.notificar(m, t); };
    const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const aMin = h => { const [a, b] = String(h || '0:0').split(':').map(Number); return a * 60 + (b || 0); };
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const ESTADOS = [
        { valor: 'Pendiente',  slug: 'pendiente',  corto: 'Pend.',   plural: 'Pendientes',  franja: 'Pendiente de confirmar' },
        { valor: 'Confirmada', slug: 'confirmada', corto: 'Conf.',   plural: 'Confirmadas', franja: 'Confirmada por el paciente' },
        { valor: 'En curso',   slug: 'en-curso',   corto: 'Curso',   plural: 'En consulta', franja: 'En consulta ahora' },
        { valor: 'Finalizada', slug: 'finalizada', corto: 'Fin.',    plural: 'Finalizadas', franja: 'Consulta finalizada' },
        { valor: 'Cancelada',  slug: 'cancelada',  corto: 'Canc.',   plural: 'Canceladas',  franja: 'Cita cancelada' }
    ];

    return {
        C: D.citas || [], PAC: D.pacientes || [], MED: D.medicos || [], CON: D.consultorios || [], ESTADOS,
        PX: 1.4,                                  // píxeles por minuto en el tablero
        hoy: iso(new Date()), dia: iso(new Date()), mesNav: iso(new Date()).slice(0, 7),
        vista: 'dia', agrupar: 'medico', filtroMed: '', filtroEstado: '', q: '',
        ahora: Date.now(), fantasma: null,
        sel: null, editando: false, guardando: false,
        form: {}, errores: {}, mensajeForm: '', bPac: { q: '', abierto: false, i: 0 },
        pop: '', mesForm: iso(new Date()).slice(0, 7), popMed: false, exH: '', exM: '',

        init() {
            // Fecha y vista desde la URL (?fecha=AAAA-MM-DD&vista=mes, o el formato anterior ?mes=&anio=)
            const u = new URLSearchParams(location.search);
            if (/^\d{4}-\d{2}-\d{2}$/.test(u.get('fecha') || '')) this.dia = u.get('fecha');
            else if (u.get('mes') && u.get('anio')) { const m = `${u.get('anio')}-${String(u.get('mes')).padStart(2, '0')}`; if (m !== this.hoy.slice(0, 7)) this.dia = m + '-01'; this.vista = 'mes'; }
            if (u.get('vista') === 'mes') this.vista = 'mes';
            try { const a = localStorage.getItem('citas_agrupar'); if (a) this.agrupar = a; } catch (e) {}
            this.mesNav = this.dia.slice(0, 7);
            this.sel = this.delDia.find(c => this.minFin(c) >= this.minAhora && c.estado !== 'Cancelada') || this.delDia[0] || null;
            this.form = this.formNuevo();

            this.$watch('dia', v => { this.mesNav = v.slice(0, 7); this.urlSync(); this.$nextTick(() => this.enfocarTablero()); });
            this.$watch('vista', () => this.urlSync());
            this.$watch('agrupar', v => { try { localStorage.setItem('citas_agrupar', v); } catch (e) {} });
            // Al elegir médico, fecha o duración en una cita nueva: consultorio habitual y primer horario libre
            const autollenar = () => {
                if (!this.editando || this.form.id) return;
                if (this.form.personal_id && !this.form.consultorio_id) this.form.consultorio_id = this.consultorioHabitual(this.form.personal_id);
                if ((!this.form.hora || this.choqueForm) && this.libres.length) this.form.hora = this.libres[0];
            };
            this.$watch('form.personal_id', autollenar);
            this.$watch('form.fecha', autollenar);
            this.$watch('form.consultorio_id', autollenar);
            this.$watch('form.duracion_min', autollenar);
            setInterval(() => { this.ahora = Date.now(); const h = iso(new Date()); if (h !== this.hoy) this.hoy = h; }, 30000);
            this.$nextTick(() => this.enfocarTablero());
        },

        /* ---------- Utilidades ---------- */
        slug(estado) { return (ESTADOS.find(e => e.valor === estado) || ESTADOS[0]).slug; },
        aHora(min) { return String(Math.floor(min / 60) % 24).padStart(2, '0') + ':' + String(min % 60).padStart(2, '0'); },
        horaFin(c) { return this.aHora(aMin(c.hora) + Number(c.duracion_min || 30)); },
        minFin(c) { return c.fecha === this.hoy ? aMin(c.hora) + Number(c.duracion_min || 30) : (c.fecha < this.hoy ? -1 : 99999); },
        get minAhora() { const d = new Date(this.ahora); return d.getHours() * 60 + d.getMinutes(); },
        get horaAhora() { return this.aHora(this.minAhora); },
        get esHoyDia() { return this.dia === this.hoy; },
        nombreMes(ym) { return MESES[Number(ym.slice(5, 7)) - 1]; },
        diaSemana(f) { return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long' }); },
        fechaLarga(f) { return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }); },
        fechaCorta(f) {
            if (f === this.hoy) return 'Hoy';
            const m = new Date(this.hoy + 'T00:00:00'); m.setDate(m.getDate() + 1);
            if (f === iso(m)) return 'Mañana';
            return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }).replace('.', '');
        },
        sumarMes(ym, n) { const d = new Date(ym + '-01T00:00:00'); d.setMonth(d.getMonth() + n); return iso(d).slice(0, 7); },
        nombreMedico(id) { const m = this.MED.find(x => String(x.id) === String(id)); return m ? m.nombre : '—'; },
        nombreConsultorio(id) { const c = this.CON.find(x => String(x.id) === String(id)); return c ? c.nombre + (c.piso ? ' · Piso ' + c.piso : '') : '—'; },
        nombrePaciente(id) { const p = this.PAC.find(x => String(x.id) === String(id)); return p ? p.nombre : ''; },
        franja(c) {
            if (c.estado === 'En curso') return 'En consulta ahora · ' + this.nombreMedico(c.personal_id);
            if (['Pendiente', 'Confirmada'].includes(c.estado) && c.fecha === this.hoy) {
                const m = aMin(c.hora) - this.minAhora;
                if (m > 0 && m <= 90) return (c.estado === 'Pendiente' ? 'Sin confirmar · ' : '') + 'empieza en ' + m + ' min';
                if (m <= 0 && m > -Number(c.duracion_min)) return 'Debería estar en consulta';
            }
            return (ESTADOS.find(e => e.valor === c.estado) || ESTADOS[0]).franja;
        },
        cuando(c) {
            if (c.estado === 'En curso') return 'En consulta · ' + this.nombreMedico(c.personal_id);
            if (c.fecha === this.hoy) { const m = aMin(c.hora) - this.minAhora; if (m > 0 && m < 60) return 'En ' + m + ' min · ' + this.nombreMedico(c.personal_id); }
            return this.nombreMedico(c.personal_id);
        },
        siguientePaso(c) {
            return { 'Pendiente': { valor: 'Confirmada', accion: 'Confirmar cita' }, 'Confirmada': { valor: 'En curso', accion: 'Iniciar consulta' }, 'En curso': { valor: 'Finalizada', accion: 'Finalizar consulta' } }[c.estado] || null;
        },
        guilloche(seed) {
            let h = 2166136261; for (const ch of String(seed)) { h ^= ch.charCodeAt(0); h = Math.imul(h, 16777619); } h >>>= 0;
            const w = 340, al = 410, n = 20 + h % 10, cx = w * .8, cy = al * .22; let d = '';
            for (let k = 0; k < 12; k++) {
                const A = 50 + k * 8, B = 6 + (k % 4) * 2, ph = k * .22 + (h % 100) / 50;
                for (let i = 0; i <= 240; i++) { const t = i / 240 * 2 * Math.PI, r = A + B * Math.cos(n * t + ph); d += (i ? 'L' : 'M') + (cx + r * Math.cos(t)).toFixed(1) + ',' + (cy + r * Math.sin(t)).toFixed(1); }
                d += 'Z';
            }
            for (let k = 0; k < 7; k++) { const y0 = 190 + k * 32; for (let x = 0; x <= w; x += 6) d += (x ? 'L' : 'M') + x + ',' + (y0 + 6 * Math.sin(x / 27 + k * .9 + h % 5) + 2.5 * Math.sin(x / 8 + k)).toFixed(1); }
            return `<svg viewBox="0 0 ${w} ${al}" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="${d}" fill="none" stroke="#a6efe2" stroke-width=".6" opacity=".2"/></svg>`;
        },

        get doctores() { return this.MED.filter(m => m.esMedico !== false); },
        aMinutos(h) { return aMin(h); },
        citasDeMedico(id, f) { return this.C.filter(c => String(c.personal_id) === String(id) && c.fecha === f && c.estado !== 'Cancelada').length; },
        abrirPop(k) {
            this.pop = this.pop === k ? '' : k;
            if (k === 'fecha') this.mesForm = (this.form.fecha || this.hoy).slice(0, 7);
            if (k === 'hora') { this.exH = this.form.hora ? this.form.hora.slice(0, 2) : ''; this.exM = this.form.hora ? this.form.hora.slice(3, 5) : ''; this.$nextTick(() => this.$refs.horaH && this.$refs.horaH.focus()); }
        },
        horaConEspacio(h) {
            return [0, 15, 30, 45].some(m => {
                const t = h * 60 + m;
                if (this.form.fecha === this.hoy && t < this.minAhora + 5) return false;
                return !this.conflicto(Object.assign({}, this.form, { hora: this.aHora(t) }));
            });
        },
        elegirHora(h, m) { this.form.hora = this.aHora(h * 60 + m); this.exH = this.form.hora.slice(0, 2); this.exM = this.form.hora.slice(3, 5); this.errores.hora = ''; },
        // Hora exacta: cualquier minuto de 00:00 a 23:59 (los campos se editan libremente y se aplican al ser válidos)
        aplicarExacta() {
            this.exH = this.exH.replace(/\D/g, ''); this.exM = this.exM.replace(/\D/g, '');
            if (this.exH === '') return;
            const h = Math.min(23, parseInt(this.exH, 10)), m = this.exM === '' ? 0 : Math.min(59, parseInt(this.exM, 10));
            this.form.hora = this.aHora(h * 60 + m); this.errores.hora = '';
        },
        pasoParte(parte, d) {
            const [h, m] = (this.form.hora || '09:00').split(':').map(Number);
            this.form.hora = parte === 'h' ? this.aHora(((h + d + 24) % 24) * 60 + m) : this.aHora(h * 60 + (m + d + 60) % 60);
            this.exH = this.form.hora.slice(0, 2); this.exM = this.form.hora.slice(3, 5); this.errores.hora = '';
        },

        /* ---------- Filtros y datos derivados ---------- */
        get visibles() {
            const q = norm(this.q.trim());
            return this.C.filter(c =>
                (!this.filtroMed || String(c.personal_id) === this.filtroMed) &&
                (!this.filtroEstado || c.estado === this.filtroEstado) &&
                (!q || norm(c.paciente + ' ' + c.motivo).includes(q)));
        },
        porFecha(f) { return this.visibles.filter(c => c.fecha === f).sort((a, b) => a.hora.localeCompare(b.hora)); },
        get delDia() { return this.porFecha(this.dia); },
        get resumen() {
            const q = norm(this.q.trim()), r = {};
            this.C.filter(c => (this.vista === 'dia' ? c.fecha === this.dia : c.fecha.startsWith(this.mesNav)) &&
                (!this.filtroMed || String(c.personal_id) === this.filtroMed) && (!q || norm(c.paciente + ' ' + c.motivo).includes(q)))
                .forEach(c => { r[c.estado] = (r[c.estado] || 0) + 1; });
            return r;
        },
        contarMes() { return this.visibles.filter(c => c.fecha.startsWith(this.mesNav) && c.estado !== 'Cancelada').length; },
        get listaLateral() {
            if (this.q.trim()) return [...this.visibles].sort((a, b) => Math.abs(new Date(a.fecha) - new Date(this.hoy)) - Math.abs(new Date(b.fecha) - new Date(this.hoy)) || a.hora.localeCompare(b.hora)).slice(0, 8);
            return this.visibles.filter(c => ['Pendiente', 'Confirmada', 'En curso'].includes(c.estado) && (c.fecha > this.hoy || (c.fecha === this.hoy && this.minFin(c) >= this.minAhora)))
                .sort((a, b) => (a.fecha + a.hora).localeCompare(b.fecha + b.hora)).slice(0, 5);
        },

        /* ---------- Tablero ---------- */
        get columnas() {
            const delDia = this.delDia, porMed = this.agrupar === 'medico';
            const clave = porMed ? 'personal_id' : 'consultorio_id';
            let base = porMed ? this.doctores.map(m => ({ id: m.id, titulo: m.nombre, sub: m.especialidad || 'Médico' }))
                              : this.CON.map(c => ({ id: c.id, titulo: c.nombre, sub: (c.piso ? 'Piso ' + c.piso : 'Sin piso') + (c.estado === 'Mantenimiento' ? ' · mantenimiento' : ''), mant: c.estado === 'Mantenimiento' }));
            if (porMed && this.filtroMed) base = base.filter(m => String(m.id) === this.filtroMed);
            const usados = new Set(delDia.map(c => String(c[clave])));
            // Citas de un médico o consultorio que ya no está en la lista
            usados.forEach(id => { if (!base.some(b => String(b.id) === id)) base.push({ id: Number(id), titulo: '—', sub: 'No disponible' }); });
            const con = base.filter(b => usados.has(String(b.id)));
            const extra = base.filter(b => !usados.has(String(b.id)) && !b.mant).slice(0, Math.max(0, (porMed ? 3 : 4) - con.length));
            return [...con, ...extra].map(b => Object.assign(b, { clave }));
        },
        get rango() {
            let ini = 8, fin = 20;
            this.delDia.forEach(c => { ini = Math.min(ini, Math.floor(aMin(c.hora) / 60)); fin = Math.max(fin, Math.ceil((aMin(c.hora) + Number(c.duracion_min)) / 60)); });
            if (this.esHoyDia) { ini = Math.min(ini, Math.floor(this.minAhora / 60)); fin = Math.max(fin, Math.ceil(this.minAhora / 60)); }
            return { ini: Math.max(0, Math.min(ini, 7)), fin: Math.min(24, fin) };
        },
        get horas() { const h = []; for (let i = this.rango.ini; i < this.rango.fin; i++) h.push(i); return h; },
        get altoTablero() { return (this.rango.fin - this.rango.ini) * 60 * this.PX; },
        bloques(col) {
            const lista = this.delDia.filter(c => String(c[col.clave]) === String(col.id))
                .map(c => ({ c, ini: aMin(c.hora), fin: aMin(c.hora) + Number(c.duracion_min || 30) }));
            // Carriles para citas que se traslapan (por ejemplo, canceladas en el mismo horario)
            const grupos = []; let g = null;
            lista.forEach(b => { if (!g || b.ini >= g.fin) { g = { fin: b.fin, items: [] }; grupos.push(g); } g.items.push(b); g.fin = Math.max(g.fin, b.fin); });
            grupos.forEach(gr => {
                const carriles = [];
                gr.items.forEach(b => { let k = carriles.findIndex(f => f <= b.ini); if (k < 0) { k = carriles.length; carriles.push(0); } carriles[k] = b.fin; b.k = k; });
                gr.items.forEach(b => { b.n = carriles.length; });
            });
            return lista.map(b => ({
                c: b.c, top: (b.ini - this.rango.ini * 60) * this.PX, alto: Math.max(18, (b.fin - b.ini) * this.PX - 2),
                izq: b.k / b.n * 100, ancho: 100 / b.n
            }));
        },
        minutoDe(e) {
            const m = this.rango.ini * 60 + e.offsetY / this.PX;
            return Math.max(this.rango.ini * 60, Math.min(this.rango.fin * 60 - 30, Math.floor(m / 15) * 15));
        },
        libreEn(col, min) {
            return !this.delDia.some(c => String(c[col.clave]) === String(col.id) && c.estado !== 'Cancelada' && aMin(c.hora) < min + 30 && min < aMin(c.hora) + Number(c.duracion_min));
        },
        clicEspacio(col, e) {
            if (this.dia < this.hoy) { avisar('No se pueden agendar citas en días pasados.', 'info'); return; }
            const min = this.minutoDe(e);
            if (this.esHoyDia && min + 15 < this.minAhora) { avisar('Esa hora ya pasó.', 'info'); return; }
            const datos = { fecha: this.dia, hora: this.aHora(min) };
            datos[col.clave] = String(col.id);
            this.nueva(datos);
        },
        enfocarTablero() {
            const el = this.$refs.tablero; if (!el || this.vista !== 'dia') return;
            const objetivo = this.esHoyDia ? this.minAhora : (this.delDia[0] ? aMin(this.delDia[0].hora) : 8 * 60);
            el.scrollTo({ top: Math.max(0, (objetivo - this.rango.ini * 60) * this.PX - 90), behavior: reduce ? 'auto' : 'smooth' });
        },

        /* ---------- Mes ---------- */
        celdasMes(ym) {
            const primero = new Date(ym + '-01T00:00:00'), offset = (primero.getDay() + 6) % 7;
            const inicio = new Date(primero); inicio.setDate(1 - offset);
            const dias = new Date(primero.getFullYear(), primero.getMonth() + 1, 0).getDate();
            const total = Math.ceil((offset + dias) / 7) * 7, out = [];
            for (let i = 0; i < total; i++) { const d = new Date(inicio); d.setDate(inicio.getDate() + i); const f = iso(d); out.push({ fecha: f, dentro: f.startsWith(ym) }); }
            return out;
        },

        /* ---------- Navegación ---------- */
        irDia(f, aDia) { if (this.editando) return; this.dia = f; if (aDia) this.vista = 'dia'; const d = this.delDia; if (!this.sel || this.sel.fecha !== f) this.sel = d.find(c => this.minFin(c) >= this.minAhora && c.estado !== 'Cancelada') || d[0] || this.sel; },
        mover(n) {
            if (this.vista === 'mes') { this.mesNav = this.sumarMes(this.mesNav, n); return; }
            const d = new Date(this.dia + 'T00:00:00'); d.setDate(d.getDate() + n); this.irDia(iso(d));
        },
        irHoy() { if (this.vista === 'mes') this.mesNav = this.hoy.slice(0, 7); else this.irDia(this.hoy); },
        cambiarVista(v) { this.vista = v; if (v === 'mes') this.mesNav = this.dia.slice(0, 7); else this.$nextTick(() => this.enfocarTablero()); },
        urlSync() { try { history.replaceState(null, '', location.pathname + '?fecha=' + this.dia + (this.vista === 'mes' ? '&vista=mes' : '')); } catch (e) {} },
        seleccionar(c, irA) { if (this.editando) return; this.sel = c; if (irA && c.fecha !== this.dia) { this.dia = c.fecha; this.vista = 'dia'; } },

        /* ---------- Formulario (el pase se voltea) ---------- */
        formNuevo(d = {}) {
            return Object.assign({ id: null, paciente_id: '', personal_id: this.filtroMed || '', consultorio_id: '', fecha: this.dia < this.hoy ? this.hoy : this.dia, hora: '', duracion_min: 30, tipo_consulta: 'Primera Vez', estado: 'Pendiente', motivo: '' }, d);
        },
        nueva(datos = {}) {
            if (this.editando) return;
            this.form = this.formNuevo(datos);
            if (this.form.personal_id && !this.form.consultorio_id) this.form.consultorio_id = this.consultorioHabitual(this.form.personal_id);
            if (!this.form.hora && this.libres.length) this.form.hora = this.libres[0];
            this.abrirForm();
        },
        editar(c) {
            if (this.editando || !c) return;
            this.form = { id: c.id, paciente_id: String(c.paciente_id), personal_id: String(c.personal_id), consultorio_id: String(c.consultorio_id || ''), fecha: c.fecha, hora: c.hora, duracion_min: Number(c.duracion_min), tipo_consulta: c.tipo_consulta, estado: c.estado, motivo: c.motivo };
            this.abrirForm();
        },
        abrirForm() {
            this.errores = {}; this.mensajeForm = 'Ctrl+Enter guarda'; this.bPac = { q: '', abierto: false, i: 0 }; this.pop = '';
            this.editando = true;
            this.$nextTick(() => { const p = this.$root.querySelector('.ci-pase'); if (p) p.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' }); });
            setTimeout(() => { const el = this.form.paciente_id ? this.$root.querySelector('.ci-reverso .ci-pick') : this.$refs.campoPaciente; el && el.focus({ preventScroll: true }); }, reduce ? 0 : 450);
        },
        cancelar() {
            this.editando = false; this.guardando = false;
            this.$nextTick(() => { const b = this.$root.querySelector('.ci-acc .ci-btn'); if (b) b.focus({ preventScroll: true }); else if (document.activeElement) document.activeElement.blur(); });
        },
        get pacientesFiltrados() {
            const q = norm(this.bPac.q.trim());
            const l = q ? this.PAC.filter(p => norm(p.nombre + ' ' + (p.curp || '')).includes(q)) : this.PAC;
            return l.slice(0, 8);
        },
        elegirPaciente(p) { this.form.paciente_id = String(p.id); this.bPac = { q: '', abierto: false, i: 0 }; this.errores.paciente_id = ''; },
        consultorioHabitual(medId) {
            const previas = this.C.filter(c => String(c.personal_id) === String(medId) && c.consultorio_id).sort((a, b) => (b.fecha + b.hora).localeCompare(a.fecha + a.hora));
            return previas[0] ? String(previas[0].consultorio_id) : '';
        },
        conflicto(f) {
            if (!f.fecha || !f.hora || f.estado === 'Cancelada') return null;
            const ini = aMin(f.hora), fin = ini + Number(f.duracion_min || 30);
            const c = this.C.find(x => x.fecha === f.fecha && String(x.id) !== String(f.id) && x.estado !== 'Cancelada' &&
                ((f.personal_id && String(x.personal_id) === String(f.personal_id)) || (f.consultorio_id && String(x.consultorio_id) === String(f.consultorio_id))) &&
                aMin(x.hora) < fin && ini < aMin(x.hora) + Number(x.duracion_min || 30));
            if (!c) return null;
            const quien = String(c.personal_id) === String(f.personal_id) ? 'El médico' : 'El consultorio';
            return `${quien} ya tiene cita de ${c.hora} a ${this.horaFin(c)} (${c.paciente}).`;
        },
        get choqueForm() { return this.editando ? this.conflicto(this.form) : null; },
        get libres() {
            const f = this.form; if (!f.fecha || !f.personal_id) return [];
            const out = [], dur = Number(f.duracion_min || 30);
            for (let m = 8 * 60; m + dur <= 20 * 60; m += 30) {
                if (f.fecha === this.hoy && m < this.minAhora + 5) continue;
                if (!this.conflicto(Object.assign({}, f, { hora: this.aHora(m) }))) out.push(this.aHora(m));
                if (out.length >= 10) break;
            }
            return out;
        },
        async guardar() {
            if (this.guardando) return;
            const f = this.form; f.motivo = String(f.motivo || '').trim();
            const e = {};
            if (!f.paciente_id) e.paciente_id = 'Elige al paciente.';
            if (!f.personal_id) e.personal_id = 'Elige al médico.';
            if (!f.consultorio_id) e.consultorio_id = 'Elige el consultorio.';
            if (!f.fecha) e.fecha = 'Indica la fecha.';
            if (!f.hora) e.hora = 'Indica la hora.';
            if (!f.motivo) e.motivo = 'Escribe el motivo de la consulta.';
            if (!f.id && f.fecha && f.hora && new Date(f.fecha + 'T' + f.hora).getTime() < Date.now() - 5 * 60000) e.hora = 'Esa fecha y hora ya pasaron.';
            this.errores = e;
            if (Object.keys(e).length) { this.mensajeForm = 'Revisa los datos marcados.'; return; }
            if (this.choqueForm) return;

            this.guardando = true; this.mensajeForm = 'Guardando…';
            try {
                const r = await fetch(f.id ? `${URL}/${f.id}` : URL, {
                    method: f.id ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ paciente_id: f.paciente_id, personal_id: f.personal_id, consultorio_id: f.consultorio_id, fecha: f.fecha, hora: f.hora, duracion_min: Number(f.duracion_min), tipo_consulta: f.tipo_consulta, estado: f.id ? f.estado : 'Pendiente', motivo: f.motivo })
                });
                const d = await r.json().catch(() => ({}));
                if (r.status === 422) {
                    Object.entries(d.errors || {}).forEach(([k, v]) => { this.errores[k] = v[0]; });
                    this.mensajeForm = d.message || 'Revisa los datos.'; this.guardando = false; return;
                }
                if (!r.ok) throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo guardar la cita.'));
                if (d.cita) {
                    const i = this.C.findIndex(x => x.id === d.cita.id);
                    if (i >= 0) this.C.splice(i, 1, d.cita); else this.C.push(d.cita);
                    this.sel = this.C.find(x => x.id === d.cita.id);
                    this.cancelar();
                    this.dia = d.cita.fecha; this.vista = 'dia';
                    avisar(d.message || 'Cita guardada.');
                } else { avisar(d.message || 'Cita guardada.'); location.reload(); }
            } catch (err) {
                this.guardando = false; this.mensajeForm = err.message; avisar(err.message, 'error');
            }
        },
        async cambiarEstado(c, estado) {
            if (!c || this.guardando || c.estado === estado) return;
            const antes = c.estado; c.estado = estado; this.guardando = true;
            try {
                const r = await fetch(`${URL}/${c.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ paciente_id: c.paciente_id, personal_id: c.personal_id, consultorio_id: c.consultorio_id, fecha: c.fecha, hora: c.hora, duracion_min: c.duracion_min, tipo_consulta: c.tipo_consulta, estado, motivo: c.motivo || 'Sin motivo' })
                });
                const d = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo cambiar el estado.'));
                avisar(`Cita de ${c.paciente}: ${estado.toLowerCase()}.`);
            } catch (err) { c.estado = antes; avisar(err.message, 'error'); }
            this.guardando = false;
        },
        eliminar(c) {
            if (!c) return;
            const esc = s => String(s).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
            Swal.fire({
                title: '¿Eliminar esta cita?',
                html: `<p style="font-size:14px">Se eliminará la cita de <b>${esc(c.paciente)}</b> del ${esc(this.fechaLarga(c.fecha))} a las ${esc(c.hora)}. Si solo no asistirá, es mejor cancelarla y conservar el historial.</p>`,
                icon: 'warning', iconColor: '#c8102e', showCancelButton: true, showDenyButton: c.estado !== 'Cancelada', reverseButtons: true, focusCancel: true,
                confirmButtonText: 'Eliminar', denyButtonText: 'Mejor cancelarla', cancelButtonText: 'Volver',
                confirmButtonColor: '#c8102e', denyButtonColor: '#0b5a54', cancelButtonColor: '#56625f',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const r = await fetch(`${URL}/${c.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                        const d = await r.json().catch(() => ({}));
                        if (!r.ok) throw new Error(r.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo eliminar la cita.'));
                        return d;
                    } catch (err) { Swal.showValidationMessage(err.message); }
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then(res => {
                if (res.isDenied) { this.cambiarEstado(c, 'Cancelada'); return; }
                if (!res.isConfirmed) return;
                this.C.splice(this.C.findIndex(x => x.id === c.id), 1);
                this.sel = this.delDia[0] || null;
                avisar((res.value && res.value.message) || 'Cita eliminada.');
            });
        },

        /* ---------- Teclado ---------- */
        tecla(e) {
            if (this.editando) {
                if (e.key === 'Escape') { e.preventDefault(); if (this.pop) this.pop = ''; else if (this.bPac.abierto) this.bPac.abierto = false; else this.cancelar(); }
                else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.guardar(); }
                return;
            }
            const enCampo = /INPUT|SELECT|TEXTAREA/.test(e.target.tagName) || e.target.isContentEditable;
            if (enCampo) { if (e.key === 'Escape' && e.target === this.$refs.buscar) { this.q = ''; e.target.blur(); } return; }
            if (e.ctrlKey || e.metaKey || e.altKey || document.querySelector('.swal2-popup:not(.swal2-toast)') || document.querySelector('.ly-paleta:not([style*="none"])')) return;
            const k = e.key.toLowerCase();
            if (e.key === 'ArrowLeft') { e.preventDefault(); this.mover(-1); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); this.mover(1); }
            else if (k === 't') this.irHoy();
            else if (k === 'n') { e.preventDefault(); this.nueva(); }
            else if (k === 'v') this.cambiarVista(this.vista === 'dia' ? 'mes' : 'dia');
            else if (k === 'e' && this.sel) { e.preventDefault(); this.editar(this.sel); }
            else if (e.key === '/') { e.preventDefault(); this.$refs.buscar.focus(); }
        }
    };
}
</script>
@endverbatim
@endsection