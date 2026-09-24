@extends('layouts.admin')

@section('content')
@php
    // Valores por defecto: evitan un error si el controlador todavía no envía estos datos
    $esAdmin      = $esAdmin ?? false;
    $doctores     = $doctores ?? collect();
    $nombreDoctor = $nombreDoctor ?? null;
@endphp
<!-- FullCalendar Scripts -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js'></script>

<div class="py-6 space-y-6" x-data="moduloConsultasDoctor()">

    <!-- BANNER INSTITUCIONAL -->
    <div class="aparece relative bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 overflow-hidden">
        <div class="flotar absolute -right-10 -top-14 w-44 h-44 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-16 bottom-[-3rem] w-24 h-24 rounded-full bg-white/5" style="animation-delay:-1.5s"></div>

        <div class="space-y-2 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                <i class="bi bi-heart-pulse-fill"></i>
                {{ $esAdmin ? 'Vista de administrador' : 'Panel médico personalizado' }}
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">
                {{ $nombreDoctor ? 'Hola, ' . $nombreDoctor : 'Mi agenda y consultas médicas' }}
            </h1>
            <p class="text-xs text-slate-200 font-medium max-w-xl leading-relaxed">
                @if ($esAdmin)
                    Como administrador ves las citas de todos los médicos. Cada médico solo verá las que tiene asignadas.
                @else
                    Visualiza únicamente las citas asignadas a tu perfil, atiende a tus pacientes y emite recetas médicas integradas.
                @endif
            </p>
        </div>

        <div class="z-10 flex flex-col items-start md:items-end gap-3">
            <div class="bg-white/10 border border-white/10 rounded-2xl px-5 py-3 backdrop-blur-md md:text-right">
                <p class="text-2xl font-black tabular-nums leading-none"><span x-text="horaActual.split(':')[0]"></span><span class="parpadeo mx-0.5">:</span><span x-text="horaActual.split(':')[1]"></span></p>
                <p class="text-[11px] font-semibold text-emerald-200 mt-1" x-text="fechaHoy"></p>
            </div>
            <button type="button" x-show="puedeAvisos" x-cloak @click="activarAvisos()"
                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/10 text-[11px] font-bold px-3.5 py-2 rounded-xl transition-all cursor-pointer">
                <i class="bi bi-bell-fill text-emerald-300 campana"></i> Activar avisos del navegador
            </button>
        </div>
    </div>

    <!-- CONSULTA EN CURSO -->
    <template x-if="agenda.en_curso">
        <div class="aparece rounded-3xl border border-sky-200 bg-sky-50 p-5 flex flex-col md:flex-row md:items-center gap-4">
            <span class="relative w-11 h-11 rounded-2xl bg-sky-600 text-white flex items-center justify-center shrink-0">
                <i class="bi bi-activity text-lg"></i>
                <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-500"></span>
            </span>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wide text-sky-600">Consulta en curso</p>
                <p class="text-sm font-black text-slate-800 truncate" x-text="agenda.en_curso.paciente_nombre"></p>
                <p class="text-xs font-medium text-slate-500"
                   x-text="(agenda.en_curso.consultorio_nombre || 'Sin consultorio') + (esAdmin ? ' · Dr(a). ' + agenda.en_curso.personal_nombre : '') + ' · ' + agenda.en_curso.hora + ' – ' + agenda.en_curso.hora_fin"></p>
            </div>
            <div class="md:ml-auto flex flex-wrap gap-2">
                <button type="button" @click="citaActual = { ...agenda.en_curso, fecha_larga: fechaLarga(agenda.en_curso.fecha) }; abrirAtencion()"
                        class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-sky-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="bi bi-clipboard2-pulse"></i> Continuar consulta
                </button>
                <button type="button" @click="citaActual = { ...agenda.en_curso, fecha_larga: fechaLarga(agenda.en_curso.fecha) }; pedirLiberar()"
                        class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-sky-200 text-sky-700 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="bi bi-door-open"></i> Liberar consultorio
                </button>
            </div>
        </div>
    </template>

    <!-- RESUMEN DEL DÍA -->
    @php
        $tarjetas = [
            ['Citas de hoy', 'hoy',         'bi-calendar-check', 'bg-teal-50 text-teal-600',       'bg-teal-500'],
            ['Por atender',  'por_atender', 'bi-hourglass-split', 'bg-amber-50 text-amber-600',     'bg-amber-400'],
            ['Atendidas',    'atendidas',   'bi-check2-circle',   'bg-emerald-50 text-emerald-600', 'bg-emerald-500'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $colorIcono, $colorPunto])
            <div class="tarjeta-stat group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center justify-between gap-3 transition-all hover:shadow-md hover:-translate-y-1 hover:border-slate-200"
                 style="animation-delay: {{ $i * 70 }}ms">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $colorPunto }}"></span> {{ $titulo }}
                    </p>
                    <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="resumenMostrado.{{ $clave }}">0</p>
                </div>
                <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $colorIcono }}">
                    <i class="bi {{ $icono }}"></i>
                </span>
            </div>
        @endforeach

        <div class="tarjeta-stat group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center justify-between gap-3 transition-all hover:shadow-md hover:-translate-y-1 hover:border-slate-200"
             style="animation-delay: 210ms">
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Próxima cita
                </p>
                <template x-if="agenda.proxima">
                    <div>
                        <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="agenda.proxima.hora"></p>
                        <p class="text-[10px] font-bold text-slate-400 truncate" x-text="etiquetaFaltan(agenda.proxima) + ' · ' + agenda.proxima.paciente_nombre"></p>
                    </div>
                </template>
                <p x-show="!agenda.proxima" class="text-2xl font-black text-slate-300 mt-1">—</p>
            </div>
            <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 bg-sky-50 text-sky-600">
                <i class="bi bi-alarm"></i>
            </span>
        </div>
    </div>

    <div class="grid gap-6 items-start" :class="layoutPanel ? 'lg:grid-cols-[1fr_320px]' : 'lg:grid-cols-1'">

        <!-- CALENDARIO -->
        <div class="aparece relative bg-white p-6 rounded-3xl shadow-sm border border-slate-100 min-w-0" style="--d: 260ms">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <i class="bi bi-calendar-week text-teal-600"></i>
                <p class="text-sm font-black text-slate-800">Agenda de citas</p>
                <span class="text-xs font-bold px-3 py-1 bg-teal-50 text-teal-700 rounded-full"
                      x-text="citasVista + (citasVista === 1 ? ' cita' : ' citas') + ' en esta vista'"></span>

                <div class="ml-auto flex items-center gap-2 flex-wrap">
                    <!-- Filtro por médico (solo administrador) -->
                    @if ($esAdmin)
                        <select x-model="filtroDoctor" @change="cambiarDoctor()"
                                class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                            <option value="">Todos los médicos</option>
                            <template x-for="d in doctores" :key="d.id">
                                <option :value="d.id" x-text="d.nombre"></option>
                            </template>
                        </select>
                    @endif

                    <!-- Mostrar / ocultar próximas citas -->
                    <button type="button" @click="alternarPanel()"
                            :class="panelAbierto ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300'"
                            :title="panelAbierto ? 'Ocultar próximas citas' : 'Mostrar próximas citas'"
                            class="flex items-center gap-2 border rounded-xl px-3 py-2 text-[11px] font-bold transition-all cursor-pointer">
                        <i class="bi" :class="panelAbierto ? 'bi-layout-sidebar-inset-reverse' : 'bi-layout-sidebar-reverse'"></i>
                        <span>Próximas citas</span>
                        <span class="min-w-[18px] h-[18px] px-1 rounded-full bg-teal-600 text-white text-[9px] font-black flex items-center justify-center" x-text="proximasLista.length"></span>
                    </button>
                </div>
            </div>

            <div id="calendar" class="w-full"></div>

            <!-- Filtro por estado -->
            <div class="flex flex-wrap items-center gap-x-2 gap-y-2 mt-5 pt-4 border-t border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide mr-1">Estados</span>
                <template x-for="(p, nombre) in paleta" :key="nombre">
                    <button type="button" @click="alternarEstado(nombre)"
                            :class="estadoOculto(nombre) ? 'opacity-40 line-through' : ''"
                            class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 px-2.5 py-1 rounded-full border border-slate-200 hover:border-teal-300 hover:bg-teal-50/40 transition-all cursor-pointer">
                        <span class="w-2 h-2 rounded-full" :class="p.dot"></span>
                        <span x-text="nombre"></span>
                    </button>
                </template>
            </div>

            <!-- Indicador de carga -->
            <div x-show="cargando" x-cloak
                 class="absolute inset-0 rounded-3xl bg-white/60 backdrop-blur-[1px] flex items-center justify-center">
                <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-white shadow-lg border border-slate-100 text-xs font-bold text-slate-600">
                    <span class="w-4 h-4 rounded-full border-2 border-teal-600 border-t-transparent animate-spin"></span>
                    Cargando citas...
                </div>
            </div>
        </div>

        <!-- PRÓXIMAS CITAS -->
        <aside x-show="panelAbierto" x-cloak class="aparece lg:sticky lg:top-6" style="--d: 360ms"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0 translate-x-4"
               x-transition:enter-end="opacity-100 translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100 translate-x-0"
               x-transition:leave-end="opacity-0 translate-x-4">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center gap-2 mb-4">
                    <i class="bi bi-clock-history text-teal-600"></i>
                    <h3 class="text-sm font-black text-slate-800">Próximas citas</h3>
                    <button type="button" @click="alternarPanel()" title="Ocultar panel"
                            class="ml-auto w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 flex items-center justify-center transition-all cursor-pointer">
                        <i class="bi bi-chevron-double-right text-xs"></i>
                    </button>
                </div>

                <!-- Cargando (esqueleto) -->
                <div x-show="!agendaLista" class="space-y-2.5">
                    <template x-for="n in 3" :key="n">
                        <div class="rounded-2xl border border-slate-100 p-3 animate-pulse">
                            <div class="h-3 w-2/3 rounded bg-slate-100"></div>
                            <div class="h-2.5 w-1/2 rounded bg-slate-100 mt-2"></div>
                            <div class="h-2.5 w-3/4 rounded bg-slate-100 mt-2"></div>
                        </div>
                    </template>
                </div>

                <!-- Sin citas próximas -->
                <div x-show="agendaLista && proximasLista.length === 0" x-cloak class="py-8 text-center">
                    <span class="flotar inline-flex w-14 h-14 rounded-2xl bg-teal-50 text-teal-500 items-center justify-center text-2xl mb-3">
                        <i class="bi bi-calendar2-check"></i>
                    </span>
                    <p class="text-xs font-black text-slate-500">Sin citas próximas</p>
                    <p class="text-[10px] font-medium text-slate-400 mt-0.5">Cuando tengas una, aparecerá aquí.</p>
                </div>

                <div class="space-y-2.5 max-h-[540px] overflow-y-auto pr-0.5">
                    <template x-for="(c, i) in proximasLista" :key="c.cita_id">
                        <div class="aparece rounded-2xl border p-3 transition-all hover:-translate-y-0.5 hover:shadow-sm"
                             :style="'--d:' + (i * 70) + 'ms'"
                             :class="c.estado === 'En curso' ? 'border-sky-200 bg-sky-50/50' : (urgente(c) ? 'border-amber-200 bg-amber-50/40' : 'border-slate-100 hover:border-teal-200')">
                            <div class="flex items-start gap-3">
                                <span class="w-1 self-stretch rounded-full" :class="puntoDe(c)"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-black text-slate-800 truncate" x-text="c.paciente_nombre"></p>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 mt-0.5"
                                       x-text="(c.fecha === hoy ? '' : fechaCorta(c.fecha) + ' · ') + c.hora + ' – ' + c.hora_fin"></p>
                                    <p class="text-[10px] font-semibold text-slate-500 mt-1 truncate" x-text="c.motivo || 'Sin motivo registrado'"></p>
                                    <p class="text-[10px] font-semibold text-slate-400 mt-0.5 truncate">
                                        <i class="bi bi-door-open"></i>
                                        <span x-text="c.consultorio_nombre || 'Sin consultorio'"></span>
                                        <span x-show="esAdmin" x-text="' · Dr(a). ' + c.personal_nombre"></span>
                                    </p>
                                </div>
                                <span class="flex items-center gap-1.5 text-[9px] font-black px-2 py-0.5 rounded-full shrink-0 whitespace-nowrap"
                                      :class="c.estado === 'En curso' ? 'bg-sky-100 text-sky-700' : (urgente(c) ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500')">
                                    <span x-show="urgente(c) || c.estado === 'En curso'" class="latido w-1.5 h-1.5 rounded-full shrink-0"
                                          :class="c.estado === 'En curso' ? 'bg-sky-500' : 'bg-amber-500'"></span>
                                    <span x-text="etiquetaFaltan(c)"></span>
                                </span>
                            </div>
                            <div class="flex justify-end gap-1.5 mt-2.5">
                                <button type="button" @click="citaActual = { ...c, fecha_larga: fechaLarga(c.fecha) }; openModalDetalle = true"
                                        class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-500 font-extrabold rounded-xl text-[10px] transition-all cursor-pointer">
                                    Detalle
                                </button>
                                <button type="button" @click="citaActual = { ...c, fecha_larga: fechaLarga(c.fecha) }; accionPrincipal()"
                                        class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-xl text-[10px] transition-all active:scale-95 cursor-pointer"
                                        x-text="c.estado === 'En curso' ? 'Continuar' : 'Atender'"></button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </aside>
    </div>

    <!-- MODAL DETALLE DE CITA -->
    <template x-teleport="body">
        <div x-show="openModalDetalle" x-cloak
             @keydown.escape.window="!confirmar.abierto && (openModalDetalle = false)"
             class="fixed inset-0 z-[99997] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="openModalDetalle = false"
                 x-show="openModalDetalle"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <template x-if="citaActual">
                    <div>
                        <div class="relative px-7 pt-7 pb-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white overflow-hidden">
                            <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                            <button type="button" @click="openModalDetalle = false"
                                    class="absolute right-4 top-4 w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                            <div class="relative flex items-center gap-4">
                                <span class="w-14 h-14 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-lg font-black text-emerald-300 shrink-0"
                                      :class="openModalDetalle && 'icono-pop'"
                                      x-text="iniciales(citaActual.paciente_nombre)"></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Paciente</p>
                                    <h3 class="text-base font-black tracking-tight truncate" x-text="citaActual.paciente_nombre"></h3>
                                    <span class="inline-block mt-1 text-[9px] font-black px-2 py-0.5 rounded-full" :class="chipDe(citaActual)" x-text="citaActual.estado"></span>
                                </div>
                            </div>
                        </div>

                        <div class="p-7 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:1">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Fecha y horario</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="citaActual.fecha_larga"></p>
                                    <p class="text-xs font-bold text-slate-500" x-text="citaActual.hora + ' – ' + citaActual.hora_fin + ' (' + citaActual.duracion_min + ' min)'"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Médico</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="citaActual.personal_nombre"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:3">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Consultorio</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5"
                                       x-text="(citaActual.consultorio_nombre || 'Sin asignar') + (citaActual.consultorio_piso ? ' · Piso ' + citaActual.consultorio_piso : '')"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:4">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Tipo de consulta</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="citaActual.tipo_consulta"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:5">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Motivo de consulta</p>
                                    <p class="text-xs font-bold text-slate-700 mt-0.5 leading-relaxed" x-text="citaActual.motivo || 'Sin motivo registrado'"></p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                                <button type="button" @click="openModalDetalle = false"
                                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                                    Cerrar
                                </button>
                                <button type="button" x-show="citaActual.estado !== 'Cancelada'" @click="accionPrincipal()" :disabled="procesando"
                                        class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                    <span x-show="procesando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                    <i x-show="!procesando" class="bi" :class="citaActual.estado === 'Finalizada' ? 'bi-eye' : 'bi-clipboard2-pulse'"></i>
                                    <span x-text="citaActual.estado === 'En curso' ? 'Continuar consulta' : (citaActual.estado === 'Finalizada' ? 'Ver consulta' : 'Iniciar consulta')"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- MODAL DE ATENCIÓN MÉDICA (archivo aparte: resources/views/consultas/modal.blade.php) -->
    @include('consultas.modal')

    <!-- CONFIRMACIÓN (por encima de los demás modales) -->
    <template x-teleport="body">
        <div x-show="confirmar.abierto" x-cloak
             @keydown.escape.window="confirmar.abierto = false"
             @click.stop="if ($event.target === $el && !confirmar.cargando) confirmar.abierto = false"
             class="fixed inset-0 z-[100000] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-7 text-center"
                 x-show="confirmar.abierto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <span class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl mb-4"
                      :class="confirmar.abierto && 'icono-pop'">
                    <i class="bi bi-door-open-fill"></i>
                </span>
                <h3 class="text-base font-black text-slate-800" x-text="confirmar.titulo"></h3>
                <p class="text-xs font-medium text-slate-500 mt-2 leading-relaxed" x-text="confirmar.texto"></p>
                <div class="flex gap-2.5 mt-6">
                    <button type="button" @click="confirmar.abierto = false" :disabled="confirmar.cargando"
                            class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="button" @click="ejecutarConfirmacion()" :disabled="confirmar.cargando"
                            class="flex-1 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-black rounded-2xl text-xs shadow-lg shadow-amber-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="confirmar.cargando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <span x-text="confirmar.cargando ? 'Procesando...' : confirmar.boton"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- NOTIFICACIONES FLOTANTES -->
    <template x-teleport="body">
        <div class="fixed top-5 right-5 z-[100001] flex flex-col gap-2.5 w-[calc(100vw-2.5rem)] max-w-sm pointer-events-none">
            <template x-for="t in toasts" :key="t.id">
                <div x-show="t.visible"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 translate-x-8"
                     class="pointer-events-auto relative overflow-hidden bg-white rounded-2xl shadow-2xl shadow-slate-900/15 border border-slate-100 flex items-start gap-3 p-4 pr-10">
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-base icono-pop" :class="estilosToast[t.tipo].icono">
                        <i class="bi" :class="estilosToast[t.tipo].bi"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-black text-slate-800" x-text="estilosToast[t.tipo].titulo"></p>
                        <p class="text-xs font-medium text-slate-500 mt-0.5 leading-relaxed" x-text="t.mensaje"></p>
                    </div>
                    <button type="button" @click="cerrarToast(t.id)"
                            class="absolute right-3 top-3 w-6 h-6 rounded-lg text-slate-300 hover:text-slate-500 hover:bg-slate-50 flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                        <i class="bi bi-x-lg text-[10px]"></i>
                    </button>
                    <span class="absolute left-0 bottom-0 h-1" :class="estilosToast[t.tipo].barra"
                          :style="`animation: barraToast ${t.ms}ms linear forwards`"></span>
                </div>
            </template>
        </div>
    </template>
</div>

{{-- Recordatorios de citas próximas (se muestra en cualquier página donde se incluya; @once evita duplicados) --}}
@include('layouts.recordatorios-citas')

<style>
    /* ============ Animaciones ============
       Los estados finales quedan en "none" y se usa fill-mode "backwards" para que
       los efectos hover (translate/scale) sigan funcionando después de la entrada. */
    @keyframes fadeIn   { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes popIn    { 0% { opacity: 0; transform: scale(.8); } 60% { opacity: 1; transform: scale(1.05); } 100% { opacity: 1; transform: none; } }
    @keyframes iconoPop { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes pulsoHoy { 0%, 100% { box-shadow: 0 0 0 0 rgba(15, 118, 110, .35); } 50% { box-shadow: 0 0 0 6px rgba(15, 118, 110, 0); } }
    @keyframes latido   { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .6; } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes parpadeo { 0%, 45% { opacity: 1; } 55%, 100% { opacity: .2; } }
    @keyframes campana  { 0%, 88%, 100% { transform: rotate(0); } 90% { transform: rotate(16deg); } 93% { transform: rotate(-14deg); } 96% { transform: rotate(8deg); } 98% { transform: rotate(-4deg); } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .icono-pop    { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .latido       { animation: latido 1.4s ease-in-out infinite; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .parpadeo     { animation: parpadeo 1s steps(1, end) infinite; }
    .campana      { display: inline-block; transform-origin: 50% 0; animation: campana 5s ease-in-out infinite; }

    /* Elementos de los modales que aparecen escalonados cada vez que se abren */
    .reveal-item { opacity: 0; }
    .reveal-on   { animation: fadeUp .45s cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--d, 0) * 70ms); }

    /* Barra de tiempo de las notificaciones */
    @keyframes barraToast { from { width: 100%; } to { width: 0; } }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .icono-pop, .latido, .flotar, .parpadeo, .campana, .reveal-on,
        #calendar .fc-day-today .fc-daygrid-day-number {
            animation: none !important;
        }
        .reveal-item { opacity: 1 !important; }
    }

    [x-cloak] { display: none !important; }

    /* Campos del formulario de consulta */
    .etiqueta {
        display: flex; align-items: center; gap: .4rem;
        font-size: 10px; font-weight: 900; color: #64748b;
        text-transform: uppercase; letter-spacing: .04em; margin-bottom: .375rem;
    }
    .campo {
        width: 100%; background: #fff; border: 2px solid #e2e8f0; border-radius: 1rem;
        padding: .7rem 1rem; font-size: .75rem; font-weight: 700; color: #1e293b;
        outline: none; transition: all .15s;
    }
    .campo::placeholder { font-weight: 500; color: #cbd5e1; }
    .campo:focus { border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20, 184, 166, .12); }
    .campo:disabled, .campo[readonly] { background: #f8fafc; color: #64748b; cursor: default; }

    /* Calendario acorde a la paleta del sistema */
    #calendar {
        --fc-border-color: #e2e8f0;
        --fc-today-bg-color: #f0fdfa;
        --fc-button-bg-color: #0f766e;
        --fc-button-border-color: #0f766e;
        --fc-button-hover-bg-color: #115e59;
        --fc-button-hover-border-color: #115e59;
        --fc-button-active-bg-color: #134e4a;
        --fc-button-active-border-color: #134e4a;
        --fc-event-bg-color: #0d9488;
        --fc-event-border-color: #0d9488;
        --fc-now-indicator-color: #f43f5e;
        font-size: .8rem;
    }
    #calendar .fc-toolbar-title { font-size: 1.1rem; font-weight: 900; color: #1e293b; text-transform: capitalize; }
    #calendar .fc-button { font-size: .7rem; font-weight: 800; border-radius: .75rem; text-transform: capitalize; box-shadow: none !important; }
    #calendar .fc-col-header-cell-cushion { font-size: .7rem; font-weight: 800; color: #64748b; text-transform: uppercase; padding: .6rem 0; }
    #calendar .fc-daygrid-day-number { font-size: .7rem; font-weight: 800; color: #475569; }
    #calendar .fc-event { border-radius: .5rem; padding: 1px 4px; font-weight: 700; cursor: pointer; transition: transform .12s; }
    #calendar .fc-event:hover { transform: translateY(-1px); }
    #calendar .fc-cancelada { opacity: .55; text-decoration: line-through; }
    /* Día actual: número en círculo con un pulso suave */
    #calendar .fc-day-today .fc-daygrid-day-number {
        background: #0f766e; color: #fff; border-radius: 9999px;
        min-width: 1.5rem; height: 1.5rem; padding: 0; margin: 4px;
        display: flex; align-items: center; justify-content: center;
        animation: pulsoHoy 2.6s ease-in-out infinite;
    }
    #calendar .fc-theme-standard td, #calendar .fc-theme-standard th, #calendar .fc-theme-standard .fc-scrollgrid { border-color: #e2e8f0; }
    @media (max-width: 640px) {
        #calendar .fc-toolbar { flex-direction: column; gap: .5rem; }
    }
</style>

<script>
    let calendarInstance = null;

    const RUTAS = {
        citas:   '{{ route("consultas.citas") }}',
        agenda:  '{{ route("consultas.agenda") }}',
        store:   '{{ route("consultas.store") }}',
        detalle: id => '{{ route("consultas.detalle", ["cita" => "__ID__"]) }}'.replace('__ID__', id),
        iniciar: id => '{{ route("consultas.iniciar", ["cita" => "__ID__"]) }}'.replace('__ID__', id),
        liberar: id => '{{ route("consultas.liberar", ["cita" => "__ID__"]) }}'.replace('__ID__', id)
    };

    // Notificación flotante (se dibuja en el componente, por encima de los modales)
    function notificar(icon, title, ms = 3500) {
        window.dispatchEvent(new CustomEvent('toast-consulta', { detail: { icon, title, ms } }));
    }

    // Petición JSON con manejo de errores
    async function api(url, method = 'GET', body = null) {
        const r = await fetch(url, {
            method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: body ? JSON.stringify(body) : null
        });
        const data = await r.json().catch(() => ({}));

        if (!r.ok) {
            let mensaje = data.message || 'Ocurrió un error. Inténtalo de nuevo.';
            if (data.errors) mensaje = data.errors[Object.keys(data.errors)[0]][0];
            if (r.status === 403) mensaje = 'No tienes permiso para atender esta cita.';
            const e = new Error(mensaje);
            e.status = r.status;
            throw e;
        }
        return data;
    }

    function moduloConsultasDoctor() {
        return {
            // ----- Contexto -----
            esAdmin: @json($esAdmin),
            doctores: @json($doctores),
            filtroDoctor: '',
            hoy: '{{ date('Y-m-d') }}',

            // ----- Agenda (se actualiza sola cada 30 s) -----
            agenda: {
                resumen: { hoy: 0, por_atender: 0, atendidas: 0, en_curso: 0 },
                proxima: null, proximas: [], en_curso: null
            },
            agendaCargadaEn: Date.now(),
            ahora: new Date(),

            // ----- Calendario -----
            cargando: true,
            citasVista: 0,
            estadosOcultos: [],
            modoRespaldo: false,             // true si el calendario no pudo cargar sus eventos
            errorCalendarioAvisado: false,
            coloresEvento: { 'Pendiente': '#f59e0b', 'Confirmada': '#10b981', 'En curso': '#0ea5e9', 'Finalizada': '#94a3b8', 'Cancelada': '#f43f5e' },
            paleta: {
                'Pendiente':  { chip: 'bg-amber-50 text-amber-700',     dot: 'bg-amber-400' },
                'Confirmada': { chip: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
                'En curso':   { chip: 'bg-sky-50 text-sky-700',         dot: 'bg-sky-500' },
                'Finalizada': { chip: 'bg-slate-100 text-slate-600',    dot: 'bg-slate-400' },
                'Cancelada':  { chip: 'bg-red-50 text-red-600',         dot: 'bg-red-500' }
            },

            // ----- Panel plegable -----
            panelAbierto: true,

            // ----- Modales -----
            openModalDetalle: false,
            openModalConsulta: false,
            citaActual: null,
            procesando: false,
            guardando: false,
            cargandoDetalle: false,
            soloLectura: false,
            paciente: {},
            historial: [],
            recetaUrl: null,
            segundosBase: 0,
            segundosDesde: 0,
            cronometroActivo: false,
            form: {},
            sistemas: [
                { key: 'cabeza_cuello', label: 'Cabeza y cuello', icono: 'bi-person-fill' },
                { key: 'torax',         label: 'Tórax',           icono: 'bi-heart-pulse' },
                { key: 'abdomen',       label: 'Abdomen',         icono: 'bi-circle' },
                { key: 'extremidades',  label: 'Extremidades',    icono: 'bi-hand-index' },
                { key: 'piel_faneras',  label: 'Piel y faneras',  icono: 'bi-droplet' },
                { key: 'neurologico',   label: 'Neurológico',     icono: 'bi-lightning-charge' }
            ],

            // ----- Confirmación genérica -----
            confirmar: { abierto: false, cargando: false, titulo: '', texto: '', boton: 'Confirmar' },
            accionConfirmada: null,

            // ----- Notificaciones -----
            toasts: [],
            toastId: 0,
            estilosToast: {
                success: { titulo: 'Listo',    bi: 'bi-check-lg',       icono: 'bg-emerald-50 text-emerald-600', barra: 'bg-emerald-400' },
                warning: { titulo: 'Atención', bi: 'bi-exclamation-lg', icono: 'bg-amber-50 text-amber-600',     barra: 'bg-amber-400' },
                error:   { titulo: 'Error',    bi: 'bi-x-lg',           icono: 'bg-rose-50 text-rose-600',       barra: 'bg-rose-400' },
                info:    { titulo: 'Aviso',    bi: 'bi-info-lg',        icono: 'bg-sky-50 text-sky-600',         barra: 'bg-sky-400' }
            },
            puedeAvisos: false,

            // ----- Animaciones y diseño -----
            layoutPanel: true,          // columnas del diseño (cambia con retraso al ocultar el panel)
            agendaLista: false,         // false hasta la primera respuesta (muestra el esqueleto)
            resumenMostrado: { hoy: 0, por_atender: 0, atendidas: 0 },
            animacionesResumen: {},

            // =========================================================== INICIO
            init() {
                window.addEventListener('toast-consulta', e => this.agregarToast(e.detail));
                window.addEventListener('atender-cita', e => this.abrirPorId(e.detail.id));

                try {
                    const guardado = localStorage.getItem('consultas_panel_abierto');
                    if (guardado !== null) this.panelAbierto = guardado === '1';
                } catch (e) {}

                this.layoutPanel = this.panelAbierto;
                this.puedeAvisos = ('Notification' in window) && Notification.permission === 'default';

                // Reloj y cronómetro (1 s)
                setInterval(() => { this.ahora = new Date(); }, 1000);

                // Agenda: actualización automática
                this.cargarAgenda();
                setInterval(() => { if (!document.hidden) this.cargarAgenda(); }, 30000);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) { this.cargarAgenda(); if (calendarInstance) calendarInstance.refetchEvents(); }
                });

                this.$nextTick(() => {
                    this.initCalendar();
                    // Enlace desde un recordatorio: /consultas?atender=ID
                    const id = new URLSearchParams(location.search).get('atender');
                    if (id) this.abrirPorId(id);
                });
            },

            // =========================================================== RELOJ Y FORMATOS
            get horaActual() {
                return this.ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });
            },
            get fechaHoy() {
                const t = this.ahora.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
                return t.charAt(0).toUpperCase() + t.slice(1);
            },
            get cronometro() {
                let s = this.segundosBase;
                if (this.cronometroActivo) s += Math.max(0, Math.floor((this.ahora.getTime() - this.segundosDesde) / 1000));
                const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), seg = s % 60;
                const dos = n => String(n).padStart(2, '0');
                return (h > 0 ? h + ':' : '') + dos(m) + ':' + dos(seg);
            },

            iniciales(nombre) {
                return (nombre || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
            },
            fechaLarga(f) {
                if (!f) return '';
                const t = new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                return t.charAt(0).toUpperCase() + t.slice(1);
            },
            fechaCorta(f) {
                if (f === this.hoy) return 'Hoy';
                return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' });
            },
            chipDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).chip; },
            puntoDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).dot; },

            // Minutos que faltan para la cita, ajustados con el tiempo transcurrido desde la última carga
            faltanDe(c) {
                return c.faltan_min - Math.floor((this.ahora.getTime() - this.agendaCargadaEn) / 60000);
            },
            etiquetaFaltan(c) {
                if (c.estado === 'En curso') return 'En curso';
                const f = this.faltanDe(c);
                if (f > 0 && f < 60) return 'En ' + f + ' min';
                if (f === 0) return 'Ahora';
                if (f < 0 && f > -720) return 'Hace ' + (-f) + ' min';
                if (f >= 60 && f < 720) return 'En ' + Math.floor(f / 60) + ' h ' + (f % 60) + ' min';
                return this.fechaCorta(c.fecha);
            },
            urgente(c) {
                const f = this.faltanDe(c);
                return c.estado !== 'En curso' && f <= 15 && f > -60;
            },

            // =========================================================== AGENDA
            async cargarAgenda() {
                try {
                    const url = RUTAS.agenda + (this.filtroDoctor ? '?personal_id=' + this.filtroDoctor : '');
                    const d = await api(url);
                    this.agenda = d;
                    this.agendaCargadaEn = Date.now();
                    this.animarResumen();
                    if (this.modoRespaldo && calendarInstance) calendarInstance.refetchEvents();
                } catch (e) { /* se reintenta en el siguiente ciclo */ }
                this.agendaLista = true;
            },

            // Los números del resumen suben o bajan hasta su nuevo valor
            animarResumen() {
                const meta = this.agenda.resumen || {};
                const sinMov = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                ['hoy', 'por_atender', 'atendidas'].forEach(k => {
                    const destino = Number(meta[k] || 0);
                    const origen = this.resumenMostrado[k];
                    cancelAnimationFrame(this.animacionesResumen[k]);

                    if (sinMov || origen === destino) { this.resumenMostrado[k] = destino; return; }

                    const t0 = performance.now(), dur = 700;
                    const paso = (t) => {
                        const p = Math.min(1, (t - t0) / dur);
                        this.resumenMostrado[k] = Math.round(origen + (destino - origen) * (1 - Math.pow(1 - p, 3)));
                        if (p < 1) this.animacionesResumen[k] = requestAnimationFrame(paso);
                    };
                    this.animacionesResumen[k] = requestAnimationFrame(paso);
                });
            },
            // Eventos de respaldo construidos con la agenda (próximas citas)
            eventosDesdeAgenda() {
                return (this.agenda.proximas || [])
                    .filter(c => !this.estadosOcultos.includes(c.estado))
                    .map(c => ({
                        id: c.cita_id,
                        title: c.paciente_nombre,
                        start: c.inicio_iso,
                        end: c.fin_iso,
                        color: this.coloresEvento[c.estado] || '#0d9488',
                        extendedProps: c
                    }));
            },
            get proximasLista() {
                return this.agenda.proximas.filter(c => !this.estadosOcultos.includes(c.estado)).slice(0, 10);
            },
            async refrescar() {
                await this.cargarAgenda();
                if (calendarInstance) calendarInstance.refetchEvents();
            },
            cambiarDoctor() {
                this.cargarAgenda();
                if (calendarInstance) calendarInstance.refetchEvents();
            },
            alternarPanel() {
                const abrir = !this.panelAbierto;
                this.panelAbierto = abrir;

                // Al abrir, el diseño cambia de inmediato; al cerrar, se espera a que termine la animación del panel
                // (si no, el panel saltaba debajo del calendario mientras desaparecía)
                if (abrir) this.layoutPanel = true;
                else setTimeout(() => { if (!this.panelAbierto) this.layoutPanel = false; }, 180);

                try { localStorage.setItem('consultas_panel_abierto', abrir ? '1' : '0'); } catch (e) {}
                this.ajustarCalendario();
            },

            // FullCalendar calcula su ancho al dibujarse: hay que avisarle cuando su contenedor cambia de tamaño
            ajustarCalendario() {
                [0, 200, 420].forEach(ms => setTimeout(() => { if (calendarInstance) calendarInstance.updateSize(); }, ms));
            },
            alternarEstado(nombre) {
                this.estadosOcultos = this.estadoOculto(nombre)
                    ? this.estadosOcultos.filter(e => e !== nombre)
                    : [...this.estadosOcultos, nombre];
                if (calendarInstance) calendarInstance.refetchEvents();
            },
            estadoOculto(nombre) { return this.estadosOcultos.includes(nombre); },

            // =========================================================== CALENDARIO
            initCalendar() {
                const self = this;
                const calendarEl = document.getElementById('calendar');
                if (!calendarEl) return;

                let montados = 0;   // para escalonar la aparición de las citas
                const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                calendarInstance = new FullCalendar.Calendar(calendarEl, {
                    locale: 'es',
                    initialView: window.innerWidth < 768 ? 'timeGridDay' : 'dayGridMonth',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                    height: 'auto',
                    nowIndicator: true,
                    navLinks: true,
                    dayMaxEvents: 3,
                    allDaySlot: false,
                    slotMinTime: '07:00:00',
                    slotMaxTime: '21:00:00',
                    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

                    // Solo llegan las citas que el usuario puede ver (el servidor filtra por médico)
                    events: (info, ok) => {
                        const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
                        if (self.filtroDoctor) params.set('personal_id', self.filtroDoctor);

                        fetch(RUTAS.citas + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                            .then(async r => {
                                if (!r.ok) {
                                    const texto = await r.text().catch(() => '');
                                    console.error('Calendario: la ruta ' + RUTAS.citas + ' respondió ' + r.status, texto.slice(0, 800));
                                    const e = new Error('HTTP ' + r.status);
                                    e.status = r.status;
                                    throw e;
                                }
                                const lista = await r.json();
                                if (!Array.isArray(lista)) throw new Error('La respuesta no es una lista de eventos');
                                return lista;
                            })
                            .then(lista => {
                                self.modoRespaldo = false;
                                ok(lista.filter(e => !self.estadosOcultos.includes((e.extendedProps || {}).estado)));
                            })
                            .catch(err => {
                                console.error('Calendario:', err);

                                // Avisa una sola vez, indicando el código del error
                                if (!self.errorCalendarioAvisado) {
                                    self.errorCalendarioAvisado = true;
                                    let causa = 'El calendario no pudo cargar todas las citas';
                                    if (err.status === 404) causa += ' (Error 404: la ruta consultas.citas no responde; revisa las rutas)';
                                    else if (err.status === 403) causa += ' (Error 403: sin permiso)';
                                    else if (err.status) causa += ' (Error ' + err.status + '; revisa storage/logs/laravel.log)';
                                    else causa += ' (' + err.message + ')';
                                    notificar('error', causa + '. Se muestran solo las próximas citas.', 9000);
                                }

                                // Respaldo: se dibujan al menos las próximas citas que ya llegaron en la agenda
                                self.modoRespaldo = true;
                                ok(self.eventosDesdeAgenda());
                            });
                    },

                    loading: (v) => { self.cargando = v; },
                    eventsSet: (eventos) => { self.citasVista = eventos.length; montados = 0; },
                    eventDidMount: (info) => {
                        if (sinMovimiento) return;
                        info.el.style.animation = 'popIn .4s cubic-bezier(.34, 1.56, .64, 1) backwards';
                        info.el.style.animationDelay = Math.min(montados++ * 35, 420) + 'ms';
                    },
                    eventClick: (arg) => {
                        arg.jsEvent.preventDefault();
                        self.verDetalle(arg.event.extendedProps);
                    }
                });
                calendarInstance.render();

                // Reajusta el calendario cada vez que cambia el ancho de su contenedor
                if ('ResizeObserver' in window) {
                    let ultimoAncho = Math.round(calendarEl.getBoundingClientRect().width);
                    let cuadro = null;
                    new ResizeObserver((entradas) => {
                        const ancho = Math.round(entradas[0].contentRect.width);
                        if (ancho === ultimoAncho) return;
                        ultimoAncho = ancho;
                        cancelAnimationFrame(cuadro);
                        cuadro = requestAnimationFrame(() => calendarInstance && calendarInstance.updateSize());
                    }).observe(calendarEl);
                }
            },

            verDetalle(props) {
                this.citaActual = { ...props, fecha_larga: this.fechaLarga(props.fecha) };
                this.openModalDetalle = true;
            },

            async abrirPorId(id) {
                try {
                    const d = await api(RUTAS.detalle(id));
                    this.citaActual = { ...d.cita, fecha_larga: this.fechaLarga(d.cita.fecha) };
                    this.openModalDetalle = true;
                } catch (e) {
                    notificar('warning', e.message, 5000);
                }
            },

            // =========================================================== ATENCIÓN
            // Botón principal según el estado de la cita
            accionPrincipal() {
                const c = this.citaActual;
                if (!c) return;
                if (c.estado === 'En curso') return this.abrirAtencion();
                if (c.estado === 'Finalizada') return this.abrirAtencion(true);
                return this.iniciarAtencion();
            },

            async iniciarAtencion() {
                if (this.procesando) return;
                this.procesando = true;
                try {
                    const r = await api(RUTAS.iniciar(this.citaActual.cita_id), 'POST');
                    notificar('success', r.message, 5000);
                    this.citaActual.estado = 'En curso';
                    this.refrescar();
                    await this.abrirAtencion();
                } catch (e) {
                    notificar('warning', e.message, 6500);
                } finally {
                    this.procesando = false;
                }
            },

            async abrirAtencion(soloLectura = false) {
                const c = this.citaActual;
                if (!c || this.cargandoDetalle) return;
                this.cargandoDetalle = true;

                try {
                    const d = await api(RUTAS.detalle(c.cita_id));
                    const k = d.consulta || {};

                    this.paciente = d.paciente || {};
                    this.historial = d.historial || [];
                    this.recetaUrl = d.receta_url || null;
                    this.soloLectura = soloLectura || d.cita.estado === 'Finalizada';
                    this.segundosBase = d.segundos || 0;
                    this.segundosDesde = Date.now();
                    this.cronometroActivo = d.cita.estado === 'En curso';
                    this.citaActual = { ...d.cita, fecha_larga: this.fechaLarga(d.cita.fecha) };

                    this.form = {
                        cita_id: d.cita.cita_id,
                        paciente_nombre: d.cita.paciente_nombre,
                        exploracion_fisica: k.exploracion_fisica || '',
                        cabeza_cuello: k.cabeza_cuello || '',
                        torax: k.torax || '',
                        abdomen: k.abdomen || '',
                        extremidades: k.extremidades || '',
                        piel_faneras: k.piel_faneras || '',
                        neurologico: k.neurologico || '',
                        notas_generales: k.notas_generales || ''
                    };

                    this.openModalDetalle = false;
                    this.openModalConsulta = true;
                } catch (e) {
                    notificar('error', e.message, 6000);
                } finally {
                    this.cargandoDetalle = false;
                }
            },

            // finalizar = true: cierra la consulta y emite receta. false: guarda un borrador.
            async guardarAtencion(finalizar = true) {
                if (this.guardando || this.soloLectura) return;

                if (finalizar && !String(this.form.notas_generales || '').trim()) {
                    notificar('warning', 'Escribe las notas generales / diagnóstico para finalizar la consulta.');
                    return;
                }

                this.guardando = true;
                try {
                    const data = await api(RUTAS.store, 'POST', { ...this.form, finalizar });

                    if (!finalizar) {
                        notificar('success', data.message || 'Borrador guardado');
                        this.guardando = false;
                        return;
                    }

                    this.openModalConsulta = false;
                    this.refrescar();
                    notificar('success', 'Consulta finalizada. El consultorio quedó disponible.', 5000);

                    if (data.redireccion_receta) {
                        setTimeout(() => { window.location.href = data.redireccion_receta; }, 1400);
                    }
                    this.guardando = false;
                } catch (e) {
                    notificar('warning', e.message, 6000);
                    this.guardando = false;
                }
            },

            // Salir de la consulta sin finalizar: libera el consultorio
            pedirLiberar() {
                this.pedirConfirmacion({
                    titulo: '¿Liberar el consultorio?',
                    texto: 'La consulta no se finalizará: el consultorio quedará disponible y la cita volverá a Confirmada. Lo escrito solo se conserva si guardaste un borrador.',
                    boton: 'Sí, liberar',
                    accion: async () => {
                        const r = await api(RUTAS.liberar(this.citaActual.cita_id), 'POST');
                        this.openModalConsulta = false;
                        this.openModalDetalle = false;
                        notificar('success', r.message, 5000);
                        this.refrescar();
                    }
                });
            },

            // =========================================================== CONFIRMACIÓN
            pedirConfirmacion({ titulo, texto, boton, accion }) {
                this.confirmar = { abierto: true, cargando: false, titulo, texto, boton };
                this.accionConfirmada = accion;
            },
            async ejecutarConfirmacion() {
                if (this.confirmar.cargando || !this.accionConfirmada) return;
                this.confirmar.cargando = true;
                try {
                    await this.accionConfirmada();
                    this.confirmar.abierto = false;
                } catch (e) {
                    this.confirmar.abierto = false;
                    notificar('error', e.message, 6000);
                }
                this.confirmar.cargando = false;
            },

            // =========================================================== AVISOS DEL NAVEGADOR
            async activarAvisos() {
                try {
                    const permiso = await Notification.requestPermission();
                    this.puedeAvisos = permiso === 'default';
                    if (permiso === 'granted') notificar('success', 'Avisos activados. Te avisaremos antes de cada cita.');
                    else notificar('warning', 'No se activaron los avisos del navegador.');
                } catch (e) {}
            },

            // =========================================================== NOTIFICACIONES
            agregarToast(d) {
                const id = ++this.toastId;
                const ms = d.ms || 3500;
                this.toasts.push({ id, tipo: this.estilosToast[d.icon] ? d.icon : 'info', mensaje: d.title, ms, visible: false });
                const t = this.toasts.find(x => x.id === id);
                setTimeout(() => { t.visible = true; }, 30);
                setTimeout(() => this.cerrarToast(id), ms + 30);
            },
            cerrarToast(id) {
                const t = this.toasts.find(x => x.id === id);
                if (!t) return;
                t.visible = false;
                setTimeout(() => { this.toasts = this.toasts.filter(x => x.id !== id); }, 300);
            }
        };
    }
</script>
@endsection