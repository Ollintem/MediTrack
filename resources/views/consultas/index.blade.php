@extends('layouts.admin')

{{--
    MÓDULO DE CONSULTAS · MediTrack (espacio de trabajo del médico)
    Resumen del día y siguiente paciente, semana, fila de pacientes del día y la hoja de consulta
    (consultas/modal.blade.php). Un médico solo ve sus citas; el administrador puede elegir médico.
--}}

@section('content')
@php
    $datosConsultas = [
        'esAdmin'      => $esAdmin ?? false,
        'nombreDoctor' => $nombreDoctor ?? null,
        'doctores'     => collect($doctores ?? [])->values(),
        'csrf'         => csrf_token(),
        'rutas'        => [
            'citas'     => route('consultas.citas'),
            'detalle'   => url('consultas/detalle'),
            'iniciar'   => url('consultas/iniciar'),
            'liberar'   => url('consultas/liberar'),
            'guardar'   => route('consultas.store'),
            'pacientes' => \Illuminate\Support\Facades\Route::has('pacientes.index') ? route('pacientes.index') : null,
        ],
    ];
@endphp

<div class="cs" x-data="modConsultas()" :class="{ 'enfoque': enfocado }" @keydown.window="tecla($event)" @atender-cita.window="abrirCita($event.detail.id)">

    <!-- CABECERA: resumen del día y siguiente paciente -->
    <section class="cs-cab">
        <div class="cs-g">
            <b class="t" x-text="delDia.filter(c => c.estado !== 'Cancelada').length">0</b>
            <span>
                <em x-text="dia === hoy ? 'consultas hoy' : (delDia.filter(c => c.estado !== 'Cancelada').length === 1 ? 'consulta' : 'consultas')"></em>
                <span x-text="fechaLarga(dia)"></span>
            </span>
        </div>
        <p class="cs-res t">
            <template x-for="e in INDICADORES" :key="e.k">
                <span :class="'e-' + e.slug" x-show="contar(e.estados)"><i></i><b x-text="contar(e.estados)"></b> <span x-text="e.t.toLowerCase()"></span></span>
            </template>
        </p>

        <!-- Turno: siguiente paciente (o el que está en consulta) -->
        <div class="cs-turno" x-show="turno" x-cloak :class="turno && turno.estado === 'En curso' ? 'en-curso' : ''">
            <template x-if="turno">
                <div class="cs-turno-in">
                    <span class="cs-turno-k" x-text="turno.estado === 'En curso' ? 'En consulta' : 'Siguiente paciente'"></span>
                    <b x-text="turno.paciente_nombre"></b>
                    <span class="cs-turno-h t" x-text="turno.estado === 'En curso' ? 'Desde las ' + turno.hora : turno.hora + ' · ' + (faltan(turno) > 0 ? 'en ' + duracionTexto(faltan(turno)) : 'ya es su hora')"></span>
                    <button type="button" @click="abrirCita(turno.cita_id)" x-text="turno.estado === 'En curso' ? 'Continuar' : 'Ver ficha'"></button>
                </div>
            </template>
        </div>
    </section>

    <!-- SEMANA -->
    <section class="cs-semana" aria-label="Semana">
        <button type="button" class="cs-ico" @click="moverSemana(-1)" aria-label="Semana anterior"><i class="bi bi-chevron-left"></i></button>
        <div class="cs-dias">
            <template x-for="d in diasSemana" :key="'s' + d.fecha">
                <button type="button" :class="{ 'sel': d.fecha === dia, 'hoy': d.fecha === hoy, 'pasado': d.fecha < hoy }" @click="elegirDia(d.fecha)" :aria-pressed="(d.fecha === dia).toString()">
                    <span x-text="d.nombre"></span>
                    <b class="t" x-text="Number(d.fecha.slice(8))"></b>
                    <small class="t" x-text="d.citas.filter(c => c.estado !== 'Cancelada').length || ''"></small>
                </button>
            </template>
        </div>
        <button type="button" class="cs-ico" @click="moverSemana(1)" aria-label="Semana siguiente"><i class="bi bi-chevron-right"></i></button>
        <button type="button" class="cs-hoy" @click="elegirDia(hoy)" :disabled="dia === hoy">Hoy</button>

        <!-- Médico (solo administrador) -->
        <div class="cs-filtro" x-show="esAdmin" x-cloak @click.outside="popMed = false" @keydown.escape.stop="popMed = false">
            <button type="button" class="cs-pick" :class="{ 'abierto': popMed }" @click="popMed = !popMed" :aria-expanded="popMed.toString()">
                <span x-text="medico ? nombreMedico(medico) : 'Todos los médicos'"></span><i class="bi bi-chevron-down"></i>
            </button>
            <div class="cs-pop" x-show="popMed" x-cloak>
                <button type="button" class="cs-op" :class="{ 'on': !medico }" @click="elegirMedico('')"><b>Todos los médicos</b></button>
                <template x-for="m in doctores" :key="'m' + m.id">
                    <button type="button" class="cs-op" :class="{ 'on': String(m.id) === medico }" @click="elegirMedico(String(m.id))"><b x-text="m.nombre"></b><small x-text="m.especialidad || 'Médico'"></small></button>
                </template>
            </div>
        </div>
    </section>

    <section class="cs-cuerpo">
        <!-- FILA DEL DÍA -->
        <aside class="cs-fila" aria-label="Pacientes del día">
            <h2>Pacientes <small class="t" x-text="delDia.length"></small></h2>
            <div class="cs-fila-l">
                <template x-for="(c, i) in delDia" :key="'f' + c.cita_id">
                    <div>
                        <p class="cs-ahora t" x-show="dia === hoy && esPrimeraDespuesDeAhora(i)"><span x-text="'Ahora · ' + horaAhora"></span></p>
                        <button type="button" class="cs-fi" :class="'e-' + slug(c.estado) + (sel && sel.cita_id === c.cita_id ? ' sel' : '')" @click="abrirCita(c.cita_id)">
                            <span class="cs-fi-h t"><b x-text="c.hora"></b><small x-text="c.hora_fin"></small></span>
                            <span class="cs-fi-n" :title="c.personal_nombre">
                                <b x-text="c.paciente_nombre"></b>
                                <small><i x-show="c.alergia" class="bi bi-exclamation-triangle-fill" :title="'Alergia: ' + c.alergia"></i><span x-text="c.motivo || c.tipo_consulta"></span></small>
                            </span>
                            <i class="cs-fi-e bi" :class="{ 'Finalizada': 'bi-check2', 'Cancelada': 'bi-x', 'En curso': 'bi-record-fill' }[c.estado] || ''"></i>
                        </button>
                    </div>
                </template>
                <p class="cs-vacio" x-show="!cargando && !delDia.length">Sin pacientes este día.</p>
                <p class="cs-vacio" x-show="cargando">Cargando…</p>
            </div>
        </aside>

        {{-- Hoja de consulta del paciente elegido --}}
        @include('consultas.modal')
    </section>
</div>

<script type="application/json" id="cs-datos">{!! json_encode($datosConsultas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

{{-- Recordatorios de citas próximas --}}
@include('layouts.recordatorios-citas')

@verbatim
<style>
.cs {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.cs * { box-sizing: border-box; }
.cs :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.cs button { cursor: pointer; }
.cs button:disabled { cursor: default; }
.cs .t { font-variant-numeric: tabular-nums; }
.cs kbd { font: 600 11px var(--mono); border: 1px solid rgba(255, 255, 255, .3); border-radius: 6px; padding: 1px 6px; color: #cfe; }
[x-cloak] { display: none !important; }

.cs .e-pendiente { --c: var(--ambar); --cv: var(--ambarv); }
.cs .e-confirmada { --c: var(--verde); --cv: var(--verdev); }
.cs .e-en-curso { --c: var(--cred); --cv: var(--velo); }
.cs .e-finalizada { --c: var(--media); --cv: var(--niebla); }
.cs .e-cancelada { --c: var(--rosa); --cv: var(--rosav); }

/* ---------- Cabecera ---------- */
.cs-cab { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 24px; }
.cs-g { display: flex; align-items: flex-end; gap: 12px; }
.cs-g > b { font: 800 72px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); }
.cs-g > span { display: flex; flex-direction: column; padding-bottom: 1px; font-size: 14px; color: var(--media); line-height: 1.25; }
.cs-g em { font-style: normal; font: 700 19px/1.1 var(--disp); color: var(--tinta); }
.cs-g span span:first-letter { text-transform: uppercase; }
.cs-res { flex: 1; min-width: 220px; display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: 13.5px; color: var(--texto); }
.cs-res > span { display: inline-flex; align-items: center; gap: 6px; }
.cs-res i { width: 8px; height: 8px; border-radius: 50%; background: var(--c); }
.cs-res b { font-weight: 650; color: var(--tinta); }
.cs-turno { flex: 0 1 340px; min-width: 250px; }
.cs-turno-in { display: grid; grid-template-columns: minmax(0, 1fr) auto; column-gap: 12px; align-items: center; padding: 10px 10px 10px 14px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); box-shadow: inset 3px 0 0 var(--ambar); }
.cs-turno.en-curso .cs-turno-in { background: var(--cred); border-color: var(--cred); box-shadow: none; }
.cs-turno-k { grid-column: 1; font-size: 12px; font-weight: 600; color: var(--ambar); }
.cs-turno.en-curso .cs-turno-k { color: var(--foil); }
.cs-turno-in b { grid-column: 1; font: 700 16px/1.2 var(--disp); color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cs-turno.en-curso .cs-turno-in b { color: #fff; }
.cs-turno-h { grid-column: 1; font: 500 12.5px var(--mono); color: var(--media); }
.cs-turno.en-curso .cs-turno-h { color: #cdeee7; }
.cs-turno-in button { grid-column: 2; grid-row: 1 / 4; height: 38px; padding: 0 14px; border-radius: 10px; background: var(--tinta); color: #fff; font: 600 13.5px var(--ui); white-space: nowrap; }
.cs-turno.en-curso .cs-turno-in button { background: #fff; color: var(--cred); }
@container (max-width: 759px) { .cs-g > b { font-size: 60px; } .cs-turno { flex-basis: 100%; } }

/* ---------- Semana ---------- */
.cs-semana { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.cs-dias { flex: 1; min-width: 0; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; padding: 4px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); }
.cs-dias button { display: flex; align-items: baseline; justify-content: center; gap: 6px; height: 40px; border-radius: 10px; background: none; min-width: 0; padding: 0 6px; }
.cs-dias button:hover { background: var(--niebla); }
.cs-dias span { font-size: 12.5px; font-weight: 500; color: var(--media); text-transform: capitalize; }
.cs-dias b { font: 700 17px var(--disp); color: var(--tinta); }
.cs-dias small { min-width: 18px; padding: 0 5px; border-radius: 9px; background: var(--velo); color: var(--cred); font: 600 11px/18px var(--mono); text-align: center; }
.cs-dias small:empty { display: none; }
.cs-dias button.pasado b { color: #7d8986; }
.cs-dias button.hoy span { color: var(--cred); font-weight: 650; }
.cs-dias button.sel { background: var(--tinta); }
.cs-dias button.sel span, .cs-dias button.sel b { color: #fff; }
.cs-dias button.sel small { background: rgba(255, 255, 255, .18); color: #fff; }
.cs-ico { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; border: 1px solid var(--linea); background: var(--papel); color: var(--texto); }
.cs-ico:hover { background: var(--niebla); }
.cs-hoy { height: 38px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); }
.cs-hoy:disabled { color: var(--cred); background: var(--velo); border-color: var(--velo); }
.cs-filtro { position: relative; width: 230px; }
.cs-pick { width: 100%; height: 38px; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 0 12px; border: 1px solid var(--linea); border-radius: 10px; background: var(--papel); font: 600 13px var(--ui); color: var(--tinta); }
.cs-pick span { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cs-pick .bi { color: var(--cred); transition: transform .2s; }
.cs-pick.abierto { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.cs-pick.abierto .bi { transform: rotate(180deg); }
.cs-pop { position: absolute; right: 0; left: 0; top: calc(100% + 6px); z-index: 30; padding: 4px; max-height: 300px; overflow-y: auto; background: var(--papel); border: 1px solid var(--linea); border-radius: 12px; box-shadow: 0 20px 40px -22px rgba(11, 30, 28, .55); }
.cs-op { width: 100%; display: flex; flex-direction: column; align-items: flex-start; padding: 7px 10px; border-radius: 8px; text-align: left; background: none; }
.cs-op:hover { background: var(--niebla); }
.cs-op.on { background: var(--velo); box-shadow: inset 3px 0 0 var(--cred); }
.cs-op b { font-size: 13.5px; font-weight: 600; color: var(--tinta); }
.cs-op small { font-size: 12px; color: var(--media); }
@container (max-width: 759px) { .cs-dias { grid-template-columns: repeat(7, 64px); overflow-x: auto; } .cs-dias button { flex-direction: column; align-items: center; gap: 0; height: 52px; justify-content: center; } .cs-filtro { width: 100%; } }

/* ---------- Cuerpo ---------- */
.cs-cuerpo { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
@container (min-width: 980px) { .cs-cuerpo { grid-template-columns: 270px minmax(0, 1fr); } }

/* Fila del día */
.cs-fila { padding: 2px 0; }
@container (min-width: 980px) { .cs-fila { position: sticky; top: 96px; } }
.cs-fila h2 { padding: 0 8px 8px; font: 700 16px var(--disp); color: var(--tinta); }
.cs-fila h2 small { font: 500 13px var(--ui); color: var(--media); margin-left: 4px; }
.cs-fila-l { max-height: 640px; overflow-y: auto; scrollbar-width: thin; }
.cs-fi { width: 100%; display: grid; grid-template-columns: 48px minmax(0, 1fr) 16px; align-items: center; gap: 10px; padding: 8px; border-radius: 12px; text-align: left; background: none; transition: background .15s; }
.cs-fi:hover { background: rgba(11, 90, 84, .05); }
.cs-fi.sel { background: var(--papel); box-shadow: 0 1px 0 var(--linea), 0 8px 18px -14px rgba(11, 30, 28, .55); }
.cs-fi-h { font: 600 14px var(--mono); color: var(--c); text-align: center; }
.cs-fi-h small { display: none; }
.cs-fi.e-en-curso .cs-fi-h { color: #fff; background: var(--cred); border-radius: 8px; padding: 3px 0; }
.cs-fi-n { min-width: 0; display: flex; flex-direction: column; }
.cs-fi-n b { font-size: 13.5px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cs-fi.e-finalizada .cs-fi-n b { color: var(--media); font-weight: 500; }
.cs-fi.e-cancelada .cs-fi-n b { color: var(--media); text-decoration: line-through; font-weight: 500; }
.cs-fi-n small { display: flex; gap: 5px; align-items: center; font-size: 12px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cs-fi-n small .bi { color: var(--rosa); font-size: 11px; }
.cs-fi-e { font-size: 14px; color: var(--media); }
.cs-fi.e-en-curso .cs-fi-e { color: var(--cred); font-size: 9px; }
.cs-ahora { position: relative; margin: 2px 8px; height: 16px; }
.cs-ahora:before { content: ""; position: absolute; left: 0; right: 0; top: 50%; height: 1.5px; background: var(--teal); }
.cs-ahora span { position: relative; padding: 0 6px; border-radius: 5px; background: var(--teal); color: #fff; font: 600 11px/16px var(--mono); }
.cs-vacio { padding: 10px 8px; font-size: 13px; color: var(--media); }

/* ---------- Hoja ---------- */
.cs-hoja { min-width: 0; container-type: inline-size; }
.cs-hoja-vacia { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; min-height: 380px; text-align: center; border: 1.5px dashed var(--linea); border-radius: 20px; color: var(--media); }
.cs-hoja-vacia svg { width: 36px; height: 36px; fill: #c9d6d2; margin-bottom: 6px; }
.cs-hoja-vacia b { font: 700 19px var(--disp); color: var(--tinta); }
.cs-hoja-in { display: grid; gap: 12px; }

.cs-pac { position: relative; overflow: hidden; display: flex; justify-content: space-between; gap: 16px; padding: 18px 20px; border-radius: 20px; color: #eafaf6; background: linear-gradient(155deg, #0d6a63, var(--cred) 45%, var(--cred2)); box-shadow: 0 20px 40px -30px rgba(7, 63, 59, .9); }
.cs-gl, .cs-gl svg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.cs-pac-a, .cs-pac-b { position: relative; min-width: 0; }
.cs-k { font: 500 12px var(--mono); color: var(--foil); }
.cs-pac h2 { margin-top: 6px; font: 780 28px/1.02 var(--disp); font-stretch: 78%; color: #fff; text-transform: uppercase; }
.cs-pac-d { margin-top: 6px; font-size: 13.5px; color: #cdeee7; }
.cs-alg { margin-top: 10px; display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; background: var(--rosav); color: var(--rosa); font-size: 12.5px; font-weight: 650; }
.cs-pac-b { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex: none; }
.cs-estado { padding: 3px 10px; border-radius: 999px; background: rgba(255, 255, 255, .92); color: var(--c); font-size: 12.5px; font-weight: 650; }
.cs-crono { font: 600 28px/1 var(--mono); color: #fff; }

.cs-contexto { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 16px; padding: 0 4px; }
.cs-contexto p { display: flex; gap: 8px; align-items: baseline; min-width: 0; font-size: 14px; }
.cs-contexto p span { font-size: 12.5px; color: var(--media); }
.cs-contexto p b { font-weight: 600; color: var(--tinta); }
.cs-ant-bt { display: inline-flex; align-items: center; gap: 4px; background: none; font-size: 13px; font-weight: 600; color: var(--cred); }
.cs-ant-bt span { font-weight: 500; color: var(--media); }
.cs-ant-bt .bi { font-size: 11px; transition: transform .2s; }
.cs-ant-bt .bi.gira { transform: rotate(180deg); }
.cs-ant { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; padding: 14px 16px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); font-size: 13px; }
.cs-ant h4 { font-size: 12.5px; font-weight: 650; color: var(--tinta); margin-bottom: 2px; }
.cs-ant p { color: var(--texto); }
.cs-ant-h { grid-column: 1 / -1; display: grid; gap: 4px; }
.cs-ant-h b { font: 600 12.5px var(--mono); color: var(--cred); margin-right: 4px; }
.cs-link { justify-self: start; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--cred); }
.cs-link:hover { text-decoration: underline; }

.cs-iniciar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border-radius: 16px; background: var(--papel); border: 1px solid var(--linea); }
.cs-iniciar b { font: 700 16px var(--disp); color: var(--tinta); }
.cs-iniciar p { font-size: 13px; color: var(--media); }
.cs-aviso { padding: 12px 16px; border-radius: 14px; background: var(--rosav); color: var(--rosa); font-weight: 600; }

/* Hoja por pestañas */
.cs-papel { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; }
.cs-tabs { display: flex; gap: 4px; padding: 6px; border-bottom: 1px solid var(--linea2); background: #fbfdfc; }
.cs-tabs button { display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 14px; border-radius: 10px; background: none; font: 600 13.5px var(--ui); color: var(--media); }
.cs-tabs button[aria-selected=true] { background: var(--papel); color: var(--tinta); box-shadow: 0 1px 0 var(--linea), 0 6px 14px -12px rgba(11, 30, 28, .5); }
.cs-tabs i { width: 8px; height: 8px; border-radius: 50%; background: var(--linea); }
.cs-tabs i.lleno { background: var(--teal); }
.cs-tabs i.falta { background: var(--rosa); }
.cs-panel { padding: 16px; }
.cs-signos { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
@container (max-width: 639px) { .cs-signos { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.cs-sv { display: flex; flex-direction: column; gap: 2px; padding: 9px 11px; border-radius: 12px; border: 1px solid var(--linea2); background: #fbfdfc; transition: border-color .15s, background .15s; cursor: text; }
.cs-sv > span { font-size: 12px; font-weight: 500; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cs-sv-in { display: flex; align-items: baseline; gap: 4px; }
.cs-sv-in input { width: 100%; min-width: 0; border: 0; background: none; outline: 0; padding: 0; font: 600 21px/1.15 var(--mono); color: var(--tinta); }
.cs-sv-in input:first-child:nth-last-child(4) { width: 3.1ch; flex: none; }
.cs-sv-in input:nth-child(3) { width: 3.1ch; flex: none; }
.cs-sv-in input::placeholder { color: #cdd8d4; }
.cs-sv-in b { font: 600 18px var(--mono); color: var(--media); }
.cs-sv-in em { margin-left: auto; font-style: normal; font: 500 11.5px var(--mono); color: var(--media); white-space: nowrap; }
.cs-sv small { min-height: 15px; font-size: 11.5px; color: var(--media); }
.cs-sv:focus-within { border-color: var(--teal); background: var(--papel); }
.cs-sv.r-alto, .cs-sv.r-bajo { border-color: #e9c98e; background: var(--ambarv); }
.cs-sv.r-alto small, .cs-sv.r-bajo small { color: var(--ambar); font-weight: 600; }
.cs-sv.r-critico { border-color: #f1a7b4; background: var(--rosav); }
.cs-sv.r-critico small { color: var(--rosa); font-weight: 650; }
.cs-fila2 { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 20px; margin-top: 12px; }
.cs-imc { font-size: 13px; color: var(--media); }
.cs-imc b { font: 600 15px var(--mono); color: var(--tinta); margin: 0 4px; }
.cs-dolor { display: flex; flex-wrap: wrap; align-items: center; gap: 3px; }
.cs-dolor > span { font-size: 12.5px; color: var(--media); margin-right: 6px; }
.cs-dolor button { width: 28px; height: 28px; border-radius: 7px; background: none; font: 500 12.5px var(--mono); color: var(--media); }
.cs-dolor button:hover { background: var(--niebla); }
.cs-dolor button[aria-pressed=true] { color: #fff; background: hsl(calc(160 - var(--n) * 16), 62%, 34%); }

.cs-area { width: 100%; border: 1px solid var(--linea); border-radius: 12px; padding: 10px 12px; font: 450 14px/1.5 var(--ui); color: var(--tinta); background: var(--papel); outline: 0; resize: vertical; }
.cs-area:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.cs-area.mal { border-color: var(--rosa); box-shadow: 0 0 0 3px rgba(200, 16, 46, .12); }
.cs-cuenta { display: block; margin-top: 4px; text-align: right; font: 500 11.5px var(--mono); color: var(--media); }
.cs-sistemas { margin-top: 12px; display: grid; }
.cs-sis { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 6px 12px; padding: 8px 2px; border-top: 1px solid var(--linea2); }
.cs-sis:first-child { border-top: 0; }
.cs-sis > span { font-size: 13.5px; color: var(--texto); }
.cs-sis-bt { display: inline-flex; padding: 2px; border-radius: 9px; background: var(--niebla); }
.cs-sis-bt button { height: 30px; padding: 0 12px; border-radius: 7px; background: none; font: 600 12.5px var(--ui); color: var(--media); }
.cs-sis.normal .cs-sis-bt button:first-child { background: var(--papel); color: var(--verde); box-shadow: 0 1px 2px rgba(11, 30, 28, .12); }
.cs-sis.hallazgo .cs-sis-bt button:last-child { background: var(--papel); color: var(--ambar); box-shadow: 0 1px 2px rgba(11, 30, 28, .12); }
.cs-sis input { grid-column: 1 / -1; height: 38px; border: 1px solid var(--linea); border-radius: 10px; padding: 0 11px; font: 450 14px var(--ui); color: var(--tinta); outline: 0; }
.cs-sis input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }

/* ---------- Modo enfoque (consulta en curso) ---------- */
.cs-foco { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; padding: 10px 12px 10px 16px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); }
.cs-foco-k { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: var(--tinta); }
.cs-foco-k:before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--rosa); animation: csLate 1.6s ease-in-out infinite; }
@keyframes csLate { 50% { opacity: .3; } }
.cs-foco-k span { font-weight: 500; color: var(--media); }
.cs-foco button { margin-left: auto; height: 36px; display: inline-flex; align-items: center; gap: 8px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--tinta); }
.cs-foco button:hover { background: var(--niebla); }
.cs-foco button .bi { color: var(--cred); }
.cs.enfoque .cs-cab, .cs.enfoque .cs-semana, .cs.enfoque .cs-fila { display: none; }
.cs.enfoque .cs-cuerpo { grid-template-columns: minmax(0, 1fr); }
@container (min-width: 900px) {
    .cs.enfoque .cs-hoja-in { grid-template-columns: 300px minmax(0, 1fr); align-items: start; gap: 14px 20px; }
    .cs.enfoque .cs-hoja-in > * { grid-column: 1; }
    .cs.enfoque .cs-hoja-in > .cs-foco { grid-column: 1 / -1; grid-row: 1; }
    .cs.enfoque .cs-hoja-in > .cs-papel { grid-column: 2; grid-row: 2 / span 8; }
    .cs.enfoque .cs-pac { flex-direction: column; gap: 10px; }
    .cs.enfoque .cs-pac h2 { font-size: 26px; }
    .cs.enfoque .cs-pac-b { flex-direction: row; align-items: center; justify-content: space-between; }
    .cs.enfoque .cs-contexto { flex-direction: column; align-items: stretch; padding: 12px 14px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); }
    .cs.enfoque .cs-contexto p { flex-direction: column; gap: 2px; }
    .cs.enfoque .cs-ant-bt { display: none; }
    .cs.enfoque .cs-ant { display: grid !important; grid-template-columns: minmax(0, 1fr); }
    .cs.enfoque .cs-panel { padding: 20px; }
    .cs.enfoque .cs-sistemas { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); column-gap: 28px; }
    .cs.enfoque .cs-sis:nth-child(2) { border-top: 0; }
    .cs.enfoque .cs-area[rows="7"] { min-height: 260px; }
}
@media (prefers-reduced-motion: reduce) { .cs-foco-k:before { animation: none; } }

/* Resumen de consulta finalizada */
.cs-resumen { display: grid; gap: 10px; justify-items: start; }
.cs-res-sig { width: 100%; display: flex; flex-wrap: wrap; gap: 6px; }
.cs-res-sig span { display: flex; flex-direction: column; padding: 7px 12px; border-radius: 10px; background: var(--papel); border: 1px solid var(--linea); }
.cs-res-sig small { font-size: 11.5px; color: var(--media); }
.cs-res-sig b { font: 600 15px var(--mono); color: var(--tinta); }
.cs-res-sig .r-alto, .cs-res-sig .r-bajo { background: var(--ambarv); border-color: #e9c98e; }
.cs-res-sig .r-critico { background: var(--rosav); border-color: #f1a7b4; }
.cs-res-b { width: 100%; padding: 14px 16px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); }
.cs-res-b h4 { font: 700 15px var(--disp); color: var(--tinta); margin-bottom: 4px; }
.cs-res-b p { font-size: 14px; color: var(--texto); white-space: pre-line; }

/* Barra de acciones */
.cs-pie { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 10px 16px; border-top: 1px solid var(--linea2); background: #fbfdfc; }
.cs-guardado { flex: 1; min-width: 120px; display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--media); }
.cs-guardado i { width: 7px; height: 7px; border-radius: 50%; background: var(--verde); }
.cs-guardado i.pend { background: var(--ambar); }
.cs-txt { height: 38px; padding: 0 8px; background: none; font: 600 13.5px var(--ui); color: var(--ambar); }
.cs-txt:hover { text-decoration: underline; }
.cs-btn { height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 16px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); white-space: nowrap; }
.cs-btn:hover { background: #f7faf9; }
.cs-btn:disabled { opacity: .55; }
.cs-prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.cs-prim:hover { background: var(--cred2); }

@media (prefers-reduced-motion: reduce) { .cs * { transition: none !important; } }
</style>

<script>
function modConsultas() {
    const D = JSON.parse(document.getElementById('cs-datos').textContent);
    const R = D.rutas, CSRF = D.csrf;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const avisar = (m, t = 'success') => { if (window.notificar) window.notificar(m, t); };
    const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const aMin = h => { const [a, b] = String(h || '0:0').split(':').map(Number); return a * 60 + (b || 0); };
    const num = v => { const n = parseFloat(String(v ?? '').replace(',', '.')); return isNaN(n) ? null : n; };
    const lunesDe = f => { const d = new Date(f + 'T00:00:00'); d.setDate(d.getDate() - ((d.getDay() + 6) % 7)); return iso(d); };
    const sumar = (f, n) => { const d = new Date(f + 'T00:00:00'); d.setDate(d.getDate() + n); return iso(d); };
    const CAMPOS = ['exploracion_fisica', 'cabeza_cuello', 'torax', 'abdomen', 'extremidades', 'piel_faneras', 'neurologico', 'notas_generales'];
    const SIGNOS_VACIOS = { pa_sistolica: '', pa_diastolica: '', frecuencia_cardiaca: '', frecuencia_respiratoria: '', temperatura: '', saturacion_oxigeno: '', peso_kg: '', talla_cm: '', glucosa_sangre: '', nivel_dolor: '' };

    async function api(url, metodo = 'GET', cuerpo = null) {
        const r = await fetch(url, {
            method: metodo,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, ...(cuerpo ? { 'Content-Type': 'application/json' } : {}) },
            body: cuerpo ? JSON.stringify(cuerpo) : undefined
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) {
            const msg = r.status === 419 ? 'Tu sesión expiró. Recarga la página.'
                      : r.status === 403 ? 'Esta cita no está asignada a ti.'
                      : (d.errors ? Object.values(d.errors)[0][0] : (d.message || 'No se pudo completar la acción.'));
            throw new Error(msg);
        }
        return d;
    }

    return {
        esAdmin: !!D.esAdmin, doctores: D.doctores || [],
        SIGNOS: [
            { k: 'frecuencia_cardiaca', t: 'Frecuencia cardiaca', u: 'lpm', ej: '72' },
            { k: 'frecuencia_respiratoria', t: 'Frecuencia respiratoria', u: 'rpm', ej: '16' },
            { k: 'temperatura', t: 'Temperatura', u: '°C', ej: '36.6', dec: true },
            { k: 'saturacion_oxigeno', t: 'Saturación O₂', u: '%', ej: '98' },
            { k: 'glucosa_sangre', t: 'Glucosa', u: 'mg/dL', ej: '95' },
            { k: 'peso_kg', t: 'Peso', u: 'kg', ej: '70.5', dec: true },
            { k: 'talla_cm', t: 'Talla', u: 'cm', ej: '168' }
        ],
        SISTEMAS: [
            { k: 'cabeza_cuello', t: 'Cabeza y cuello' }, { k: 'torax', t: 'Tórax' }, { k: 'abdomen', t: 'Abdomen' },
            { k: 'extremidades', t: 'Extremidades' }, { k: 'piel_faneras', t: 'Piel y faneras' }, { k: 'neurologico', t: 'Neurológico' }
        ],
        PESTANAS: [{ k: 'sig', t: 'Signos vitales' }, { k: 'exp', t: 'Exploración' }, { k: 'dx', t: 'Diagnóstico' }],
        tab: 'sig', verAnt: false, abiertos: [], enfoque: true,
        INDICADORES: [
            { k: 'p', t: 'Pendientes', slug: 'pendiente', estados: ['Pendiente', 'Confirmada'] },
            { k: 'c', t: 'En consulta', slug: 'en-curso', estados: ['En curso'] },
            { k: 'a', t: 'Atendidas', slug: 'confirmada', estados: ['Finalizada'] },
            { k: 'x', t: 'Canceladas', slug: 'cancelada', estados: ['Cancelada'] }
        ],
        hoy: iso(new Date()), dia: iso(new Date()), semanaIni: lunesDe(iso(new Date())),
        semana: [], cargando: false, medico: '', popMed: false,
        ahora: Date.now(),
        sel: null, pac: {}, historial: [], signosPrevios: null, recetaUrl: null, cargado: false,
        form: {}, signos: { ...SIGNOS_VACIOS }, sucio: false, guardando: false, ocupado: false, guardadoA: '', errorDx: false,
        inicioCrono: 0,

        // Modo enfoque: mientras se atiende, solo queda la consulta en pantalla
        get enfocado() { return this.enfoque && !!this.sel && this.sel.estado === 'En curso'; },
        alternarEnfoque() {
            this.enfoque = !this.enfoque;
            this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }));
            const m = document.querySelector('.cs').closest('.overflow-y-auto');
            if (m) m.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        },

        init() {
            this.$watch('sel', (n, a) => { if (!n || !a || n.cita_id !== a.cita_id) window.mtRefresca && mtRefresca('.cs-hoja-in'); });
            this.form = Object.fromEntries(CAMPOS.map(k => [k, '']));
            this.cargarSemana().then(() => {
                const id = new URLSearchParams(location.search).get('atender');
                if (id) { this.abrirCita(Number(id)); history.replaceState(null, '', location.pathname); return; }
                const enCurso = this.delDia.find(c => c.estado === 'En curso');
                if (enCurso) this.abrirCita(enCurso.cita_id);
            });
            setInterval(() => { this.ahora = Date.now(); const h = iso(new Date()); if (h !== this.hoy) this.hoy = h; }, 1000);
            setInterval(() => { if (!document.hidden) this.cargarSemana(true); }, 60000);
            setInterval(() => { if (this.sucio && this.editable && !this.ocupado) this.guardar(false, true); }, 30000);
            window.addEventListener('beforeunload', e => { if (this.sucio && this.editable) { e.preventDefault(); e.returnValue = ''; } });
        },

        /* ---------- Utilidades ---------- */
        slug(e) { return { 'Pendiente': 'pendiente', 'Confirmada': 'confirmada', 'En curso': 'en-curso', 'Finalizada': 'finalizada', 'Cancelada': 'cancelada' }[e] || 'pendiente'; },
        etiquetaEstado(e) { return { 'Pendiente': 'Sin confirmar', 'Confirmada': 'Confirmada', 'En curso': 'En consulta', 'Finalizada': 'Atendida', 'Cancelada': 'Cancelada' }[e] || e; },
        etiquetaCorta(e) { return { 'Pendiente': 'Pend.', 'Confirmada': 'Conf.', 'En curso': 'Ahora', 'Finalizada': '✓', 'Cancelada': 'Canc.' }[e] || ''; },
        aHora(m) { return String(Math.floor(m / 60) % 24).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0'); },
        get minAhora() { const d = new Date(this.ahora); return d.getHours() * 60 + d.getMinutes(); },
        get horaAhora() { return this.aHora(this.minAhora); },
        faltan(c) { return c.fecha === this.hoy ? aMin(c.hora) - this.minAhora : (c.fecha > this.hoy ? 99999 : -99999); },
        duracionTexto(m) { return m >= 60 ? Math.floor(m / 60) + ' h ' + (m % 60 ? (m % 60) + ' min' : '') : m + ' min'; },
        fechaLarga(f) { return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }); },
        fechaCorta(f) { if (!f) return ''; if (f === this.hoy) return 'Hoy'; return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }).replace('.', ''); },
        nombreMedico(id) { const m = this.doctores.find(x => String(x.id) === String(id)); return m ? m.nombre : '—'; },
        get urlPaciente() { return R.pacientes && this.pac.id ? R.pacientes + '?p=' + this.pac.id : '#'; },
        guilloche(seed) {
            let h = 2166136261; for (const ch of String(seed)) { h ^= ch.charCodeAt(0); h = Math.imul(h, 16777619); } h >>>= 0;
            const w = 900, al = 220, n = 22 + h % 10, cx = w * .86, cy = al * .5; let d = '';
            for (let k = 0; k < 12; k++) {
                const A = 40 + k * 9, B = 6 + (k % 4) * 2, ph = k * .22 + (h % 100) / 50;
                for (let i = 0; i <= 260; i++) { const t = i / 260 * 2 * Math.PI, r = A + B * Math.cos(n * t + ph); d += (i ? 'L' : 'M') + (cx + r * Math.cos(t)).toFixed(1) + ',' + (cy + r * Math.sin(t)).toFixed(1); }
                d += 'Z';
            }
            for (let k = 0; k < 5; k++) { const y0 = 30 + k * 40; for (let x = 0; x <= w; x += 8) d += (x ? 'L' : 'M') + x + ',' + (y0 + 7 * Math.sin(x / 40 + k * .9 + h % 5) + 3 * Math.sin(x / 11 + k)).toFixed(1); }
            return `<svg viewBox="0 0 ${w} ${al}" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="${d}" fill="none" stroke="#a6efe2" stroke-width=".6" opacity=".2"/></svg>`;
        },

        /* ---------- Semana y fila ---------- */
        async cargarSemana(silencioso) {
            if (!silencioso) this.cargando = true;
            try {
                const q = new URLSearchParams({ start: this.semanaIni, end: sumar(this.semanaIni, 7) });
                if (this.medico) q.set('personal_id', this.medico);
                const ev = await api(R.citas + '?' + q.toString());
                this.semana = ev.map(e => e.extendedProps);
                // Mantiene la cita elegida al día
                if (this.sel) { const n = this.semana.find(c => c.cita_id === this.sel.cita_id); if (n && n.estado !== this.sel.estado && !this.sucio) Object.assign(this.sel, n); }
            } catch (e) { if (!silencioso) avisar(e.message, 'error'); }
            this.cargando = false;
        },
        get diasSemana() {
            return [0, 1, 2, 3, 4, 5, 6].map(i => {
                const f = sumar(this.semanaIni, i);
                return { fecha: f, nombre: new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'short' }).replace('.', ''), citas: this.semana.filter(c => c.fecha === f).sort((a, b) => a.hora.localeCompare(b.hora)) };
            });
        },
        get delDia() { return this.semana.filter(c => c.fecha === this.dia).sort((a, b) => a.hora.localeCompare(b.hora)); },
        contar(estados) { return this.delDia.filter(c => estados.includes(c.estado)).length; },
        esPrimeraDespuesDeAhora(i) {
            const l = this.delDia, m = this.minAhora;
            return aMin(l[i].hora) >= m && (i === 0 || aMin(l[i - 1].hora) < m);
        },
        get turno() {
            if (this.dia !== this.hoy) return null;
            const l = this.semana.filter(c => c.fecha === this.hoy).sort((a, b) => a.hora.localeCompare(b.hora));
            return l.find(c => c.estado === 'En curso') || l.find(c => ['Pendiente', 'Confirmada'].includes(c.estado) && aMin(c.hora) + c.duracion_min > this.minAhora) || null;
        },
        elegirDia(f) {
            this.dia = f;
            const ini = lunesDe(f);
            if (ini !== this.semanaIni) { this.semanaIni = ini; this.cargarSemana(); }
        },
        moverSemana(n) { this.semanaIni = sumar(this.semanaIni, 7 * n); this.dia = this.semanaIni <= this.hoy && this.hoy < sumar(this.semanaIni, 7) ? this.hoy : this.semanaIni; this.cargarSemana(); },
        elegirMedico(id) { this.medico = id; this.popMed = false; this.cargarSemana(); },

        /* ---------- Hoja de consulta ---------- */
        get editable() { return !!this.sel && this.sel.estado === 'En curso' && this.cargado; },
        get cronometro() {
            const s = Math.max(0, Math.floor((this.ahora - this.inicioCrono) / 1000));
            return [Math.floor(s / 3600), Math.floor(s / 60) % 60, s % 60].map(x => String(x).padStart(2, '0')).join(':');
        },
        get sistemasLlenos() { return this.SISTEMAS.filter(s => (this.form[s.k] || '').trim()).length; },
        get imc() {
            const p = num(this.signos.peso_kg), t = num(this.signos.talla_cm);
            return p && t ? (p / Math.pow(t / 100, 2)).toFixed(1) : '';
        },
        rango(k) {
            const v = k === 'imc' ? num(this.imc) : (k === 'pa' ? null : num(this.signos[k]));
            if (k === 'pa') {
                const s = num(this.signos.pa_sistolica), d = num(this.signos.pa_diastolica);
                if (s === null && d === null) return 'vacio';
                if ((s !== null && (s >= 180 || s < 80)) || (d !== null && d >= 120)) return 'critico';
                if ((s !== null && s >= 140) || (d !== null && d >= 90)) return 'alto';
                if ((s !== null && s < 90) || (d !== null && d < 60)) return 'bajo';
                return 'normal';
            }
            if (v === null) return 'vacio';
            const r = { frecuencia_cardiaca: [60, 100, 40, 130], frecuencia_respiratoria: [12, 20, 8, 30], temperatura: [36, 37.5, 35, 39.5], saturacion_oxigeno: [95, 100, 90, 101], glucosa_sangre: [70, 140, 54, 300], imc: [18.5, 24.9, 16, 40] }[k];
            if (!r) return 'dato';
            if (v < r[2] || v > r[3]) return 'critico';
            if (v < r[0]) return 'bajo';
            if (v > r[1]) return 'alto';
            return 'normal';
        },
        leyenda(k) {
            const r = this.rango(k), p = this.signosPrevios;
            const previo = () => {
                if (!p) return '';
                if (k === 'pa') return p.pa_sistolica ? 'Antes ' + Math.round(p.pa_sistolica) + '/' + Math.round(p.pa_diastolica || 0) : '';
                if (k === 'imc') return p.imc ? 'Antes ' + p.imc : '';
                return p[k] !== null && p[k] !== undefined ? 'Antes ' + p[k] : '';
            };
            if (k === 'imc' && r !== 'vacio') { const v = num(this.imc); return v < 18.5 ? 'bajo peso' : v < 25 ? 'normal' : v < 30 ? 'sobrepeso' : 'obesidad'; }
            if (k === 'temperatura' && num(this.signos.temperatura) >= 38) return 'Fiebre';
            return { alto: 'Alto', bajo: 'Bajo', critico: 'Valor crítico' }[r] || previo();
        },
        marcar() { if (this.editable) this.sucio = true; },
        get datosPaciente() {
            const p = this.pac;
            return [p.edad !== null && p.edad !== undefined ? p.edad + ' años' : '', p.sexo, p.sangre ? 'Sangre ' + p.sangre : '', p.telefono].filter(Boolean).join(' · ');
        },
        estadoTab(k) {
            if (k === 'sig') return Object.values(this.signos).some(v => String(v).trim() !== '') ? 'lleno' : '';
            if (k === 'exp') return (this.form.exploracion_fisica || '').trim() || this.sistemasLlenos ? 'lleno' : '';
            return (this.form.notas_generales || '').trim() ? 'lleno' : (this.errorDx ? 'falta' : '');
        },
        esNormal(k) { return (this.form[k] || '').trim() === 'Sin hallazgos'; },
        conHallazgo(k) { const v = (this.form[k] || '').trim(); return v !== '' && v !== 'Sin hallazgos'; },
        ponerNormal(k) { this.form[k] = this.esNormal(k) ? '' : 'Sin hallazgos'; this.abiertos = this.abiertos.filter(x => x !== k); this.marcar(); },
        abrirHallazgo(k) {
            if (this.abiertos.includes(k) || this.conHallazgo(k)) { if (!this.conHallazgo(k)) this.abiertos = this.abiertos.filter(x => x !== k); return; }
            if (this.esNormal(k)) this.form[k] = '';
            this.abiertos.push(k);
            this.$nextTick(() => { const el = document.querySelector('.cs .cs-sis.hallazgo input[aria-label="Hallazgo en ' + this.SISTEMAS.find(s => s.k === k).t + '"]'); el && el.focus(); });
        },
        get resumenSignos() {
            const s = this.signos, out = [];
            if (s.pa_sistolica) out.push({ t: 'Presión', v: s.pa_sistolica + '/' + (s.pa_diastolica || '—'), r: this.rango('pa') });
            this.SIGNOS.forEach(x => { if (String(s[x.k] || '').trim()) out.push({ t: x.t, v: s[x.k] + ' ' + x.u, r: this.rango(x.k) }); });
            if (this.imc) out.push({ t: 'IMC', v: this.imc, r: this.rango('imc') });
            if (String(s.nivel_dolor) !== '') out.push({ t: 'Dolor', v: s.nivel_dolor + '/10', r: 'dato' });
            return out;
        },

        async abrirCita(id) {
            this.enfoque = true;
            if (this.sel && this.sel.cita_id === id) return;
            if (this.sucio && this.editable) await this.guardar(false, true);
            let c = this.semana.find(x => x.cita_id === id);
            this.cargado = false; this.errorDx = false;
            try {
                const d = await api(R.detalle + '/' + id);
                c = Object.assign(c || {}, d.cita);
                if (c.fecha !== this.dia) { this.dia = c.fecha; const ini = lunesDe(c.fecha); if (ini !== this.semanaIni) { this.semanaIni = ini; this.cargarSemana(true); } }
                this.sel = c;
                this.pac = d.paciente || {};
                this.historial = d.historial || [];
                this.signosPrevios = d.signos_previos;
                this.recetaUrl = d.receta_url;
                this.guardadoA = d.guardado || '';
                this.form = Object.fromEntries(CAMPOS.map(k => [k, (d.consulta && d.consulta[k]) || '']));
                this.signos = { ...SIGNOS_VACIOS };
                if (d.signos) Object.keys(SIGNOS_VACIOS).forEach(k => { if (d.signos[k] !== null && d.signos[k] !== undefined) this.signos[k] = String(k === 'nivel_dolor' ? Math.round(d.signos[k]) : d.signos[k]); });
                this.inicioCrono = Date.now() - (d.segundos || 0) * 1000;
                this.sucio = false; this.cargado = true; this.tab = 'sig'; this.verAnt = false; this.abiertos = [];
                if (window.matchMedia('(max-width: 979px)').matches) this.$nextTick(() => document.querySelector('.cs ' + '.cs-hoja').scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' }));
            } catch (e) { avisar(e.message, 'error'); }
        },
        async iniciar() {
            if (!this.sel || this.ocupado) return;
            this.enfoque = true;
            this.ocupado = true;
            try {
                const d = await api(R.iniciar + '/' + this.sel.cita_id, 'POST', {});
                const id = this.sel.cita_id; this.sel = null;
                await this.cargarSemana(true);
                await this.abrirCita(id);
                avisar(d.message || 'Consulta iniciada.');
                this.$nextTick(() => { const el = document.querySelector('.cs .cs-sv input'); el && el.focus(); });
            } catch (e) { avisar(e.message, 'error'); }
            this.ocupado = false;
        },
        liberar() {
            if (!this.sel) return;
            Swal.fire({
                title: '¿Liberar el consultorio?', icon: 'question', iconColor: '#9a5800',
                html: '<p style="font-size:14px">La consulta se pausa y la cita vuelve a «Confirmada». Lo que escribiste se guarda como borrador.</p>',
                showCancelButton: true, reverseButtons: true, confirmButtonText: 'Liberar', cancelButtonText: 'Seguir en consulta',
                confirmButtonColor: '#0b5a54', cancelButtonColor: '#56625f'
            }).then(async r => {
                if (!r.isConfirmed) return;
                this.ocupado = true;
                try {
                    if (this.sucio) await this.guardar(false, true);
                    const d = await api(R.liberar + '/' + this.sel.cita_id, 'POST', {});
                    this.sel.estado = 'Confirmada'; this.sucio = false;
                    await this.cargarSemana(true);
                    avisar(d.message || 'Consultorio liberado.');
                } catch (e) { avisar(e.message, 'error'); }
                this.ocupado = false;
            });
        },
        async guardar(finalizar, silencioso) {
            if (!this.sel || this.guardando) return;
            if (finalizar && !String(this.form.notas_generales || '').trim()) {
                this.errorDx = true; this.tab = 'dx'; this.$nextTick(() => window.mtSacudir && mtSacudir('.cs-area.mal')); avisar('Escribe el diagnóstico para finalizar la consulta.', 'warning');
                this.$refs.dx && this.$refs.dx.focus(); return;
            }
            const signos = {};
            Object.keys(SIGNOS_VACIOS).forEach(k => { const v = num(this.signos[k]); signos[k] = v === null ? null : v; });
            this.guardando = true; if (!silencioso) this.ocupado = true;
            try {
                const d = await api(R.guardar, 'POST', Object.assign({ cita_id: this.sel.cita_id, finalizar: !!finalizar, signos }, this.form));
                this.sucio = false; this.guardadoA = d.guardado || this.horaAhora;
                if (finalizar) {
                    this.sel.estado = 'Finalizada'; this.recetaUrl = d.redireccion_receta;
                    await this.cargarSemana(true);
                    if (d.redireccion_receta) {
                        Swal.fire({ title: 'Consulta finalizada', icon: 'success', iconColor: '#08734f', html: '<p style="font-size:14px">¿Quieres emitir la receta ahora?</p>', showCancelButton: true, reverseButtons: true, confirmButtonText: 'Emitir receta', cancelButtonText: 'Más tarde', confirmButtonColor: '#0b5a54', cancelButtonColor: '#56625f' })
                            .then(r => { if (r.isConfirmed) location.href = d.redireccion_receta; });
                    } else avisar(d.message || 'Consulta finalizada.');
                } else if (!silencioso) avisar(d.message || 'Borrador guardado.');
            } catch (e) { avisar(e.message, 'error'); }
            this.guardando = false; this.ocupado = false;
        },

        /* ---------- Teclado ---------- */
        tecla(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && this.editable) { e.preventDefault(); this.guardar(true); return; }
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's' && this.editable) { e.preventDefault(); this.guardar(false); return; }
            const enCampo = /INPUT|SELECT|TEXTAREA/.test(e.target.tagName) || e.target.isContentEditable;
            if (enCampo || e.ctrlKey || e.metaKey || e.altKey || document.querySelector('.swal2-popup:not(.swal2-toast)') || document.querySelector('.ly-paleta:not([style*="none"])')) return;
            const l = this.delDia, i = this.sel ? l.findIndex(c => c.cita_id === this.sel.cita_id) : -1;
            if (e.key === 'ArrowDown' && l.length) { e.preventDefault(); this.abrirCita(l[Math.min(l.length - 1, i + 1)].cita_id); }
            else if (e.key === 'ArrowUp' && l.length) { e.preventDefault(); this.abrirCita(l[Math.max(0, i - 1)].cita_id); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); this.elegirDia(sumar(this.dia, -1)); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); this.elegirDia(sumar(this.dia, 1)); }
            else if (e.key.toLowerCase() === 't') this.elegirDia(this.hoy);
        }
    };
}
</script>
@endverbatim
@endsection
