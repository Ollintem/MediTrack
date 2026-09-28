@extends('layouts.admin')

@section('content')
@php
    // Valores por defecto: evitan un error si el controlador todavía no envía estos datos
    $esAdmin      = $esAdmin ?? false;
    $nombreDoctor = $nombreDoctor ?? null;

    // Lista de médicos simplificada para el filtro (funciona con modelos o arreglos)
    $doctoresJs = collect($doctores ?? [])->map(fn ($d) => [
        'id'     => data_get($d, 'id'),
        'nombre' => data_get($d, 'nombre_completo') ?: (trim(data_get($d, 'nombre', '') . ' ' . (data_get($d, 'apellidos') ?? data_get($d, 'apellido_paterno') ?? '')) ?: data_get($d, 'nombre')),
    ])->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
@endphp

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js"></script>

<div class="py-6 space-y-6" x-data="moduloConsultasDoctor()">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-48 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        <svg class="absolute inset-x-0 bottom-2 w-full h-12 opacity-20 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>

        <div class="relative p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center gap-6">
            {{-- Saludo --}}
            <div class="flex-1 min-w-0 space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                    <i class="bi bi-heart-pulse-fill latido-lento"></i>
                    {{ $esAdmin ? 'Vista de administrador' : 'Panel médico' }}
                    <span x-show="sinConexion" x-cloak class="ml-1 inline-flex items-center gap-1 text-amber-300 normal-case">
                        · <i class="bi bi-wifi-off"></i> reconectando…
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                    <span x-text="saludo"></span>{{ $nombreDoctor ? ', ' . $nombreDoctor : '' }}
                </h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    @if ($esAdmin)
                        Ves las citas de todos los médicos. Cada médico solo verá las que tiene asignadas.
                    @else
                        Estas son las citas asignadas a tu perfil. Atiende a tus pacientes y emite recetas desde aquí.
                    @endif
                </p>

                {{-- Llamada a la acción: siguiente paciente --}}
                <div class="flex flex-wrap gap-2 pt-1">
                    <template x-if="!agenda.en_curso && agenda.proxima">
                        <button type="button" @click="citaActual = { ...agenda.proxima, fecha_larga: fechaLarga(agenda.proxima.fecha) }; accionPrincipal()"
                                :disabled="procesando"
                                class="group inline-flex items-center gap-3 bg-white text-teal-900 rounded-2xl pl-2 pr-4 py-2 shadow-lg shadow-teal-950/25 hover:-translate-y-0.5 transition-all cursor-pointer disabled:opacity-70">
                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-500 text-white flex items-center justify-center text-[11px] font-black"
                                  x-text="iniciales(agenda.proxima.paciente_nombre)"></span>
                            <span class="text-left leading-tight">
                                <span class="block text-[10px] font-bold text-teal-600 uppercase tracking-wide">Siguiente paciente · <span x-text="etiquetaFaltan(agenda.proxima)"></span></span>
                                <span class="block text-xs font-black" x-text="agenda.proxima.paciente_nombre"></span>
                            </span>
                            <span class="ml-1 inline-flex items-center gap-1 text-xs font-black text-teal-700">
                                <span x-show="!procesando">Atender</span>
                                <span x-show="procesando" class="w-3.5 h-3.5 rounded-full border-2 border-teal-600 border-t-transparent animate-spin"></span>
                                <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
                            </span>
                        </button>
                    </template>
                    <span x-show="agendaLista && !agenda.en_curso && !agenda.proxima" x-cloak
                          class="inline-flex items-center gap-2 bg-white/10 border border-white/15 rounded-2xl px-3.5 py-2 text-[11px] font-bold text-teal-100">
                        <i class="bi bi-cup-hot"></i> No tienes pacientes pendientes
                    </span>
                    <button type="button" x-show="puedeAvisos" x-cloak @click="activarAvisos()"
                            class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/10 text-[11px] font-bold px-3.5 py-2 rounded-2xl transition-all cursor-pointer">
                        <i class="bi bi-bell-fill text-emerald-300 campana"></i> Activar avisos
                    </button>
                </div>
            </div>

            {{-- Reloj + progreso del día --}}
            <div class="flex items-center gap-5 bg-white/10 border border-white/10 rounded-3xl p-4 sm:p-5 backdrop-blur-md self-start lg:self-auto">
                <div class="relative w-20 h-20 shrink-0" :title="'Atendidas ' + (agenda.resumen.atendidas || 0) + ' de ' + (agenda.resumen.hoy || 0)">
                    <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
                        <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="7"/>
                        <circle cx="40" cy="40" r="34" fill="none" stroke="url(#gradAnillo)" stroke-width="7" stroke-linecap="round"
                                stroke-dasharray="213.6" :stroke-dashoffset="213.6 - 213.6 * progresoDia / 100"
                                style="transition: stroke-dashoffset 1s cubic-bezier(.16,1,.3,1)"/>
                        <defs>
                            <linearGradient id="gradAnillo" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#6ee7b7"/><stop offset="1" stop-color="#5eead4"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                        <span class="text-lg font-black tabular-nums" x-text="progresoDia + '%'"></span>
                        <span class="text-[8px] font-bold uppercase tracking-wider text-teal-100/70 mt-0.5">del día</span>
                    </div>
                </div>
                <div>
                    <p class="text-4xl font-black tabular-nums leading-none tracking-tight">
                        <span x-text="horaActual.split(':')[0]"></span><span class="parpadeo mx-0.5 text-emerald-300">:</span><span x-text="horaActual.split(':')[1]"></span>
                    </p>
                    <p class="text-[11px] font-semibold text-emerald-200 mt-1.5" x-text="fechaHoy"></p>
                    <p class="text-[10px] font-bold text-teal-100/70 mt-0.5"
                       x-text="(agenda.resumen.atendidas || 0) + ' de ' + (agenda.resumen.hoy || 0) + ' consultas atendidas'"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== CONSULTA EN CURSO ===================== --}}
    <template x-if="agenda.en_curso">
        <div class="aparece relative overflow-hidden rounded-3xl border border-sky-200 bg-gradient-to-r from-sky-50 via-white to-sky-50 p-5 flex flex-col md:flex-row md:items-center gap-4">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-sky-400 to-sky-600"></div>
            <span class="relative w-12 h-12 rounded-2xl bg-sky-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-sky-600/30">
                <i class="bi bi-activity text-xl"></i>
                <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white"></span>
            </span>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wide text-sky-600">Consulta en curso</p>
                <p class="text-base font-black text-slate-800 truncate" x-text="agenda.en_curso.paciente_nombre"></p>
                <p class="text-xs font-medium text-slate-500"
                   x-text="(agenda.en_curso.consultorio_nombre || 'Sin consultorio') + (esAdmin ? ' · Dr(a). ' + agenda.en_curso.personal_nombre : '') + ' · ' + agenda.en_curso.hora + ' – ' + agenda.en_curso.hora_fin"></p>
            </div>
            <div class="md:ml-auto flex flex-wrap gap-2">
                <button type="button" @click="citaActual = { ...agenda.en_curso, fecha_larga: fechaLarga(agenda.en_curso.fecha) }; pedirLiberar()"
                        class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-sky-200 text-sky-700 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="bi bi-door-open"></i> Liberar consultorio
                </button>
                <button type="button" @click="citaActual = { ...agenda.en_curso, fecha_larga: fechaLarga(agenda.en_curso.fecha) }; abrirAtencion()"
                        :disabled="cargandoDetalle"
                        class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-sky-600/25 transition-all hover:-translate-y-0.5 flex items-center gap-1.5 cursor-pointer disabled:opacity-70">
                    <span x-show="cargandoDetalle" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                    <i x-show="!cargandoDetalle" class="bi bi-clipboard2-pulse"></i> Continuar consulta
                </button>
            </div>
        </div>
    </template>

    {{-- ===================== RESUMEN ===================== --}}
    @php
        $tarjetas = [
            ['Citas de hoy', 'hoy',         'bi-calendar-check',  'bg-teal-50 text-teal-600',       'bg-teal-500'],
            ['Por atender',  'por_atender', 'bi-hourglass-split', 'bg-amber-50 text-amber-600',     'bg-amber-400'],
            ['Atendidas',    'atendidas',   'bi-check2-circle',   'bg-emerald-50 text-emerald-600', 'bg-emerald-500'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $colorIcono, $colorBarra])
            <div class="tarjeta-stat group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1 hover:border-slate-200"
                 style="animation-delay: {{ $i * 70 }}ms">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $titulo }}</p>
                        <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="resumenMostrado.{{ $clave }}">0</p>
                    </div>
                    <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $colorIcono }}">
                        <i class="bi {{ $icono }}"></i>
                    </span>
                </div>
                <div class="mt-3 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full {{ $colorBarra }} transition-all duration-700 ease-out" :style="'width:' + porcentaje('{{ $clave }}') + '%'"></div>
                </div>
            </div>
        @endforeach

        <div class="tarjeta-stat group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1 hover:border-slate-200"
             :class="agenda.proxima && urgente(agenda.proxima) && 'ring-2 ring-amber-300 border-amber-200'"
             style="animation-delay: 210ms">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Próxima cita</p>
                    <template x-if="agenda.proxima">
                        <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="agenda.proxima.hora"></p>
                    </template>
                    <p x-show="!agenda.proxima" class="text-2xl font-black text-slate-300 mt-1">—</p>
                </div>
                <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 bg-sky-50 text-sky-600">
                    <i class="bi bi-alarm" :class="agenda.proxima && urgente(agenda.proxima) && 'campana'"></i>
                </span>
            </div>
            <p class="text-[10px] font-bold truncate mt-3" :class="agenda.proxima && urgente(agenda.proxima) ? 'text-amber-600' : 'text-slate-400'"
               x-text="agenda.proxima ? etiquetaFaltan(agenda.proxima) + ' · ' + agenda.proxima.paciente_nombre : 'Sin citas pendientes'"></p>
        </div>
    </div>

    {{-- ===================== LÍNEA DE TIEMPO DE HOY ===================== --}}
    <div class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6" style="--d: 200ms">
        <div class="flex items-center gap-2 mb-4 flex-wrap">
            <span class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-bar-chart-steps"></i></span>
            <div>
                <h3 class="text-sm font-black text-slate-800">Tu día de un vistazo</h3>
                <p class="text-[10px] font-semibold text-slate-400">Toca una cita para ver el detalle</p>
            </div>
            <div class="ml-auto flex flex-wrap gap-2">
                <template x-for="(p, nombre) in paleta" :key="nombre">
                    <span x-show="conteoHoy(nombre)" class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500">
                        <span class="w-2 h-2 rounded-full" :class="p.dot"></span>
                        <span x-text="nombre + ' ' + conteoHoy(nombre)"></span>
                    </span>
                </template>
            </div>
        </div>

        <div x-show="!hoyListo" class="h-24 rounded-2xl bg-slate-50 animate-pulse"></div>

        <div x-show="hoyListo && citasHoy.length === 0" x-cloak class="flex items-center gap-3 rounded-2xl bg-slate-50 border border-dashed border-slate-200 p-5">
            <span class="flotar w-10 h-10 rounded-xl bg-white text-teal-500 flex items-center justify-center text-lg shadow-sm"><i class="bi bi-sun"></i></span>
            <p class="text-xs font-bold text-slate-500">No hay citas programadas para hoy.</p>
        </div>

        <div x-show="hoyListo && citasHoy.length > 0" x-cloak class="overflow-x-auto -mx-1 px-1 pb-1">
            <div class="relative min-w-[640px]">
                {{-- Horas --}}
                <div class="relative h-5 mb-1.5">
                    <template x-for="h in horasEje" :key="h">
                        <span class="absolute -translate-x-1/2 text-[9px] font-black text-slate-300 tabular-nums"
                              :style="'left:' + posicion(h * 60) + '%'" x-text="String(h).padStart(2, '0') + ':00'"></span>
                    </template>
                </div>
                {{-- Pista --}}
                <div class="relative rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden"
                     :style="'height:' + (carrilesHoy * 40 + 12) + 'px'">
                    <template x-for="h in horasEje" :key="'l' + h">
                        <span class="absolute top-0 bottom-0 w-px bg-slate-200/70" :style="'left:' + posicion(h * 60) + '%'"></span>
                    </template>
                    {{-- Tiempo ya transcurrido --}}
                    <span class="absolute top-0 bottom-0 left-0 bg-slate-200/40 pointer-events-none transition-all duration-1000"
                          :style="'width:' + Math.max(0, Math.min(100, posicion(minutosAhora))) + '%'"></span>

                    <template x-for="(b, i) in bloquesHoy" :key="b.c.cita_id">
                        <button type="button" @click="verDetalle(b.c)"
                                class="bloque-hoy absolute h-8 rounded-xl border-l-[3px] px-2 text-left overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:z-10 transition-all cursor-pointer"
                                :class="[chipDe(b.c), bordeDe(b.c), b.c.estado === 'Cancelada' && 'opacity-50 line-through', b.c.estado === 'En curso' && 'ring-2 ring-sky-300']"
                                :style="'left:' + b.left + '%; width:max(' + b.width + '%, 34px); top:' + (6 + b.carril * 40) + 'px; --d:' + (i * 60) + 'ms'"
                                :title="b.c.paciente_nombre + ' · ' + b.c.hora + ' – ' + b.c.hora_fin + ' · ' + b.c.estado">
                            <span class="block text-[10px] font-black truncate leading-tight mt-0.5" x-text="b.c.paciente_nombre"></span>
                            <span class="block text-[9px] font-bold opacity-70 truncate leading-tight tabular-nums" x-text="b.c.hora"></span>
                        </button>
                    </template>

                    {{-- Línea de "ahora" --}}
                    <div x-show="posicion(minutosAhora) >= 0 && posicion(minutosAhora) <= 100"
                         class="absolute top-0 bottom-0 w-0.5 bg-rose-500 z-20 pointer-events-none transition-all duration-1000"
                         :style="'left:' + posicion(minutosAhora) + '%'">
                        <span class="absolute -top-0.5 -left-[5px] w-3 h-3 rounded-full bg-rose-500 ring-4 ring-rose-500/20 latido"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 items-start" :class="layoutPanel ? 'lg:grid-cols-[1fr_320px]' : 'lg:grid-cols-1'">

        {{-- ===================== CALENDARIO ===================== --}}
        <div class="aparece relative bg-white p-4 sm:p-6 rounded-3xl shadow-sm border border-slate-100 min-w-0" style="--d: 260ms">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-calendar-week"></i></span>
                <p class="text-sm font-black text-slate-800">Agenda de citas</p>
                <span class="text-[11px] font-bold px-2.5 py-1 bg-teal-50 text-teal-700 rounded-full tabular-nums"
                      x-text="citasVista + (citasVista === 1 ? ' cita' : ' citas') + ' en esta vista'"></span>

                <div class="ml-auto flex items-center gap-2 flex-wrap">
                    @if ($esAdmin)
                        <label class="relative">
                            <span class="sr-only">Filtrar por médico</span>
                            <i class="bi bi-person-badge absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                            <select x-model="filtroDoctor" @change="cambiarDoctor()"
                                    class="appearance-none bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-8 py-2 text-[11px] font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer max-w-[200px]">
                                <option value="">Todos los médicos</option>
                                <template x-for="d in doctores" :key="d.id">
                                    <option :value="d.id" x-text="d.nombre"></option>
                                </template>
                            </select>
                            <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[9px] pointer-events-none"></i>
                        </label>
                    @endif

                    <button type="button" @click="refrescar()" title="Actualizar"
                            class="w-9 h-9 rounded-xl border border-slate-200 text-slate-500 hover:text-teal-700 hover:border-teal-300 flex items-center justify-center transition-all cursor-pointer">
                        <i class="bi bi-arrow-clockwise" :class="cargando && 'girar'"></i>
                    </button>

                    <button type="button" @click="alternarPanel()"
                            :class="panelAbierto ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300'"
                            :title="panelAbierto ? 'Ocultar próximas citas' : 'Mostrar próximas citas'"
                            class="hidden lg:flex items-center gap-2 border rounded-xl px-3 py-2 text-[11px] font-bold transition-all cursor-pointer">
                        <i class="bi" :class="panelAbierto ? 'bi-layout-sidebar-inset-reverse' : 'bi-layout-sidebar-reverse'"></i>
                        <span>Próximas</span>
                        <span class="min-w-[18px] h-[18px] px-1 rounded-full bg-teal-600 text-white text-[9px] font-black flex items-center justify-center" x-text="proximasLista.length"></span>
                    </button>
                </div>
            </div>

            <div id="calendar" class="w-full"></div>

            {{-- Filtro por estado --}}
            <div class="flex flex-wrap items-center gap-x-2 gap-y-2 mt-5 pt-4 border-t border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide mr-1">Estados</span>
                <template x-for="(p, nombre) in paleta" :key="nombre">
                    <button type="button" @click="alternarEstado(nombre)" :aria-pressed="!estadoOculto(nombre)"
                            :class="estadoOculto(nombre) ? 'opacity-40 line-through' : ''"
                            class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 px-2.5 py-1 rounded-full border border-slate-200 hover:border-teal-300 hover:bg-teal-50/40 transition-all cursor-pointer">
                        <span class="w-2 h-2 rounded-full" :class="p.dot"></span>
                        <span x-text="nombre"></span>
                    </button>
                </template>
                <button type="button" x-show="estadosOcultos.length" x-cloak @click="estadosOcultos = []; aplicarFiltroEstados()"
                        class="text-[10px] font-black text-teal-700 hover:text-teal-900 ml-1 cursor-pointer">Mostrar todos</button>
            </div>

            {{-- Barra de carga (no bloquea el calendario) --}}
            <div x-show="cargando" x-cloak class="absolute top-0 inset-x-6 h-1 rounded-full overflow-hidden bg-teal-100">
                <span class="barra-carga block h-full w-1/3 rounded-full bg-teal-500"></span>
            </div>
        </div>

        {{-- ===================== PRÓXIMAS CITAS ===================== --}}
        <aside x-show="panelAbierto" x-cloak class="aparece hidden lg:block lg:sticky lg:top-6" style="--d: 360ms"
               x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-clock-history"></i></span>
                    <h3 class="text-sm font-black text-slate-800">Próximas citas</h3>
                    <button type="button" @click="alternarPanel()" title="Ocultar panel"
                            class="ml-auto w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 flex items-center justify-center transition-all cursor-pointer">
                        <i class="bi bi-chevron-double-right text-xs"></i>
                    </button>
                </div>

                <div x-show="!agendaLista" class="space-y-2.5">
                    <template x-for="n in 3" :key="n">
                        <div class="rounded-2xl border border-slate-100 p-3 flex gap-3 animate-pulse">
                            <div class="w-10 h-10 rounded-xl bg-slate-100"></div>
                            <div class="flex-1">
                                <div class="h-3 w-2/3 rounded bg-slate-100"></div>
                                <div class="h-2.5 w-1/2 rounded bg-slate-100 mt-2"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="agendaLista && proximasLista.length === 0" x-cloak class="py-8 text-center">
                    <span class="flotar inline-flex w-14 h-14 rounded-2xl bg-teal-50 text-teal-500 items-center justify-center text-2xl mb-3">
                        <i class="bi bi-calendar2-check"></i>
                    </span>
                    <p class="text-xs font-black text-slate-500">Sin citas próximas</p>
                    <p class="text-[10px] font-medium text-slate-400 mt-0.5">Cuando tengas una, aparecerá aquí.</p>
                </div>

                <div class="relative space-y-2 max-h-[560px] overflow-y-auto pr-0.5">
                    <template x-for="(c, i) in proximasLista" :key="c.cita_id">
                        <div class="aparece group relative rounded-2xl border p-3 transition-all hover:-translate-y-0.5 hover:shadow-md"
                             :style="'--d:' + (i * 70) + 'ms'"
                             :class="c.estado === 'En curso' ? 'border-sky-200 bg-sky-50/60' : (urgente(c) ? 'border-amber-200 bg-amber-50/50 brillo-urgente' : 'border-slate-100 hover:border-teal-200')">
                            <div class="flex items-start gap-3">
                                <div class="text-center shrink-0 w-11">
                                    <span class="block w-11 h-11 rounded-xl flex items-center justify-center text-[11px] font-black" :class="chipDe(c)" x-text="iniciales(c.paciente_nombre)"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black text-slate-800 truncate" x-text="c.paciente_nombre"></p>
                                    <p class="text-[10px] font-bold text-slate-400 mt-0.5 tabular-nums"
                                       x-text="(c.fecha === hoy ? 'Hoy' : fechaCorta(c.fecha)) + ' · ' + c.hora + ' – ' + c.hora_fin"></p>
                                    <p class="text-[10px] font-semibold text-slate-500 mt-1 truncate" x-text="c.motivo || 'Sin motivo registrado'"></p>
                                    <p class="text-[10px] font-semibold text-slate-400 mt-0.5 truncate">
                                        <i class="bi bi-door-open"></i>
                                        <span x-text="c.consultorio_nombre || 'Sin consultorio'"></span>
                                        <span x-show="esAdmin" x-text="' · Dr(a). ' + c.personal_nombre"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 mt-2.5">
                                <span class="flex items-center gap-1.5 text-[9px] font-black px-2 py-1 rounded-full whitespace-nowrap"
                                      :class="c.estado === 'En curso' ? 'bg-sky-100 text-sky-700' : (urgente(c) ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500')">
                                    <span x-show="urgente(c) || c.estado === 'En curso'" class="latido w-1.5 h-1.5 rounded-full shrink-0"
                                          :class="c.estado === 'En curso' ? 'bg-sky-500' : 'bg-amber-500'"></span>
                                    <span x-text="etiquetaFaltan(c)"></span>
                                </span>
                                <button type="button" @click="verDetalle(c)"
                                        class="ml-auto px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-500 font-extrabold rounded-xl text-[10px] transition-all cursor-pointer">
                                    Detalle
                                </button>
                                <button type="button" @click="citaActual = { ...c, fecha_larga: fechaLarga(c.fecha) }; accionPrincipal()"
                                        class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-xl text-[10px] transition-all active:scale-95 cursor-pointer flex items-center gap-1"
                                        :class="c.estado === 'En curso' && 'bg-sky-600 hover:bg-sky-700'">
                                    <span x-text="c.estado === 'En curso' ? 'Continuar' : 'Atender'"></span>
                                    <i class="bi bi-arrow-right text-[9px] transition-transform group-hover:translate-x-0.5"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </aside>
    </div>

    {{-- ===================== MODAL: DETALLE DE CITA ===================== --}}
    <template x-teleport="body">
        <div x-show="openModalDetalle" x-cloak
             @keydown.escape.window="!confirmar.abierto && (openModalDetalle = false)"
             class="fixed inset-0 z-[99997] bg-slate-950/70 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-md max-h-[92vh] overflow-y-auto" @click.outside="openModalDetalle = false"
                 x-show="openModalDetalle"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">
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
                                      :class="openModalDetalle && 'icono-pop'" x-text="iniciales(citaActual.paciente_nombre)"></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Paciente</p>
                                    <h3 class="text-base font-black tracking-tight truncate" x-text="citaActual.paciente_nombre"></h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-full" :class="chipDe(citaActual)" x-text="citaActual.estado"></span>
                                        <span x-show="citaActual.faltan_min !== undefined && ['Pendiente','Confirmada'].includes(citaActual.estado)"
                                              class="text-[10px] font-bold text-teal-100/80" x-text="etiquetaFaltan(citaActual)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-7 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item flex items-center gap-3" :class="openModalDetalle && 'reveal-on'" style="--d:1">
                                    <span class="w-9 h-9 rounded-xl bg-white text-teal-600 flex items-center justify-center shadow-sm shrink-0"><i class="bi bi-calendar-event"></i></span>
                                    <div>
                                        <p class="text-xs font-black text-slate-800" x-text="citaActual.fecha_larga"></p>
                                        <p class="text-xs font-bold text-slate-500 tabular-nums" x-text="citaActual.hora + ' – ' + citaActual.hora_fin + (citaActual.duracion_min ? ' (' + citaActual.duracion_min + ' min)' : '')"></p>
                                    </div>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide"><i class="bi bi-person-badge"></i> Médico</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="citaActual.personal_nombre || '—'"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:3">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide"><i class="bi bi-door-open"></i> Consultorio</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5"
                                       x-text="(citaActual.consultorio_nombre || 'Sin asignar') + (citaActual.consultorio_piso ? ' · Piso ' + citaActual.consultorio_piso : '')"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:4">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide"><i class="bi bi-tag"></i> Tipo de consulta</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="citaActual.tipo_consulta || '—'"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openModalDetalle && 'reveal-on'" style="--d:5">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide"><i class="bi bi-chat-left-text"></i> Motivo de consulta</p>
                                    <p class="text-xs font-bold text-slate-700 mt-0.5 leading-relaxed" x-text="citaActual.motivo || 'Sin motivo registrado'"></p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                                <button type="button" @click="openModalDetalle = false"
                                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                                    Cerrar
                                </button>
                                <button type="button" x-show="citaActual.estado !== 'Cancelada'" @click="accionPrincipal()" :disabled="procesando || cargandoDetalle"
                                        class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                    <span x-show="procesando || cargandoDetalle" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                    <i x-show="!procesando && !cargandoDetalle" class="bi" :class="citaActual.estado === 'Finalizada' ? 'bi-eye' : 'bi-clipboard2-pulse'"></i>
                                    <span x-text="citaActual.estado === 'En curso' ? 'Continuar consulta' : (citaActual.estado === 'Finalizada' ? 'Ver consulta' : 'Iniciar consulta')"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Modal de atención médica (resources/views/consultas/modal.blade.php) --}}
    @include('consultas.modal')

    {{-- ===================== CONFIRMACIÓN ===================== --}}
    <template x-teleport="body">
        <div x-show="confirmar.abierto" x-cloak
             @keydown.escape.window="!confirmar.cargando && (confirmar.abierto = false)"
             @click.stop="if ($event.target === $el && !confirmar.cargando) confirmar.abierto = false"
             class="fixed inset-0 z-[100000] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-7 text-center"
                 x-show="confirmar.abierto"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-6 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <span class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl mb-4" :class="confirmar.abierto && 'icono-pop'">
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

    {{-- ===================== NOTIFICACIONES ===================== --}}
    <template x-teleport="body">
        <div class="fixed top-5 right-5 z-[100001] flex flex-col gap-2.5 w-[calc(100vw-2.5rem)] max-w-sm pointer-events-none" aria-live="polite">
            <template x-for="t in toasts" :key="t.id">
                <div x-show="t.visible"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-8"
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
                    <span class="absolute left-0 bottom-0 h-1" :class="estilosToast[t.tipo].barra" :style="`animation: barraToast ${t.ms}ms linear forwards`"></span>
                </div>
            </template>
        </div>
    </template>
</div>

{{-- Recordatorios de citas próximas --}}
@include('layouts.recordatorios-citas')

<style>
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes popIn    { 0% { opacity: 0; transform: scale(.8); } 60% { opacity: 1; transform: scale(1.05); } 100% { opacity: 1; transform: none; } }
    @keyframes iconoPop { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes pulsoHoy { 0%, 100% { box-shadow: 0 0 0 0 rgba(15, 118, 110, .35); } 50% { box-shadow: 0 0 0 6px rgba(15, 118, 110, 0); } }
    @keyframes latido   { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .6; } }
    @keyframes latidoLento { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes parpadeo { 0%, 45% { opacity: 1; } 55%, 100% { opacity: .2; } }
    @keyframes campana  { 0%, 88%, 100% { transform: rotate(0); } 90% { transform: rotate(16deg); } 93% { transform: rotate(-14deg); } 96% { transform: rotate(8deg); } 98% { transform: rotate(-4deg); } }
    @keyframes ecg      { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
    @keyframes cargaBarra { from { transform: translateX(-100%); } to { transform: translateX(300%); } }
    @keyframes girar    { to { transform: rotate(360deg); } }
    @keyframes brillo   { 0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); } 50% { box-shadow: 0 0 0 4px rgba(245, 158, 11, .15); } }
    @keyframes crecerX  { from { opacity: 0; transform: scaleX(.3); } to { opacity: 1; transform: none; } }
    @keyframes barraToast { from { width: 100%; } to { width: 0; } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .icono-pop    { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .latido       { animation: latido 1.4s ease-in-out infinite; }
    .latido-lento { display: inline-block; animation: latidoLento 1.6s ease-in-out infinite; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .parpadeo     { animation: parpadeo 1s steps(1, end) infinite; }
    .campana      { display: inline-block; transform-origin: 50% 0; animation: campana 5s ease-in-out infinite; }
    .ecg-linea    { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }
    .barra-carga  { animation: cargaBarra 1.1s ease-in-out infinite; }
    .girar        { display: inline-block; animation: girar .8s linear infinite; }
    .brillo-urgente { animation: brillo 2s ease-in-out infinite; }
    .bloque-hoy   { transform-origin: left center; animation: crecerX .5s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }

    .reveal-item { opacity: 0; }
    .reveal-on   { animation: fadeUp .45s cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--d, 0) * 70ms); }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .icono-pop, .latido, .latido-lento, .flotar, .parpadeo, .campana, .reveal-on,
        .ecg-linea, .barra-carga, .brillo-urgente, .bloque-hoy, #calendar .fc-day-today .fc-daygrid-day-number {
            animation: none !important;
        }
        .reveal-item { opacity: 1 !important; }
    }

    [x-cloak] { display: none !important; }

    /* Campos del formulario de consulta (los usa consultas.modal) */
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

    /* ============ FullCalendar con la paleta del sistema ============ */
    #calendar {
        --fc-border-color: #eef2f6;
        --fc-today-bg-color: #f0fdfa;
        --fc-page-bg-color: #fff;
        --fc-neutral-bg-color: #f8fafc;
        --fc-list-event-hover-bg-color: #f0fdfa;
        --fc-now-indicator-color: #f43f5e;
        font-size: .8rem;
    }
    #calendar .fc-toolbar { gap: .75rem; flex-wrap: wrap; }
    #calendar .fc-toolbar-title { font-size: 1.15rem; font-weight: 900; color: #1e293b; text-transform: capitalize; letter-spacing: -.01em; }

    /* Botones tipo "pastilla" */
    #calendar .fc-button-group { background: #f1f5f9; border-radius: .9rem; padding: 3px; gap: 2px; }
    #calendar .fc-button {
        background: transparent !important; border: 0 !important; color: #64748b !important;
        font-size: .7rem; font-weight: 800; border-radius: .7rem !important; padding: .4rem .75rem;
        text-transform: capitalize; box-shadow: none !important; transition: all .15s;
    }
    #calendar .fc-button:hover { color: #0f766e !important; background: rgba(255,255,255,.7) !important; }
    #calendar .fc-button-active, #calendar .fc-button-active:hover { background: #fff !important; color: #0f766e !important; box-shadow: 0 1px 3px rgba(15,23,42,.08) !important; }
    #calendar .fc-today-button { background: #0f766e !important; color: #fff !important; border-radius: .9rem !important; padding: .5rem .9rem; margin-left: .5rem !important; }
    #calendar .fc-today-button:disabled { opacity: .35; }

    #calendar .fc-theme-standard td, #calendar .fc-theme-standard th, #calendar .fc-theme-standard .fc-scrollgrid { border-color: #eef2f6; }
    #calendar .fc-scrollgrid { border-radius: 1rem; overflow: hidden; }
    #calendar .fc-col-header-cell { background: #f8fafc; }
    #calendar .fc-col-header-cell-cushion { font-size: .68rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; padding: .65rem 0; letter-spacing: .04em; }
    #calendar .fc-daygrid-day-number { font-size: .72rem; font-weight: 800; color: #475569; padding: 6px 8px; }
    #calendar .fc-day-other .fc-daygrid-day-number { color: #cbd5e1; }
    #calendar .fc-day-sat, #calendar .fc-day-sun { background: #fbfcfd; }
    #calendar .fc-daygrid-day:hover { background: #f8fffe; }
    #calendar .fc-day-today .fc-daygrid-day-number {
        background: #0f766e; color: #fff; border-radius: 9999px;
        min-width: 1.6rem; height: 1.6rem; padding: 0; margin: 4px;
        display: flex; align-items: center; justify-content: center;
        animation: pulsoHoy 2.6s ease-in-out infinite;
    }
    #calendar .fc-timegrid-slot { height: 2.4rem; }
    #calendar .fc-timegrid-slot-label-cushion { font-size: .65rem; font-weight: 800; color: #94a3b8; }
    #calendar .fc-timegrid-now-indicator-line { border-width: 2px 0 0; }
    #calendar .fc-daygrid-more-link { font-size: .65rem; font-weight: 900; color: #0f766e; }

    /* Citas: tarjetita con borde de color según el estado */
    #calendar .fc-event.ev-mt {
        --c: #0d9488;
        background: color-mix(in srgb, var(--c) 13%, #fff) !important;
        border: 0 !important; border-left: 3px solid var(--c) !important;
        border-radius: .55rem; color: #1e293b !important; cursor: pointer;
        transition: transform .12s, box-shadow .12s; margin-bottom: 2px;
    }
    #calendar .fc-event.ev-mt:hover { transform: translateY(-1px); box-shadow: 0 4px 12px -4px color-mix(in srgb, var(--c) 60%, transparent); }
    #calendar .ev-contenido { display: flex; align-items: center; gap: 4px; padding: 2px 4px; overflow: hidden; font-size: .68rem; line-height: 1.25; }
    #calendar .ev-contenido b { font-weight: 900; color: color-mix(in srgb, var(--c) 75%, #0f172a); font-variant-numeric: tabular-nums; flex: none; }
    #calendar .ev-contenido span { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #calendar .ev-bloque { padding: 4px 6px; font-size: .7rem; line-height: 1.3; overflow: hidden; }
    #calendar .ev-bloque b { display: block; font-weight: 900; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #calendar .ev-bloque small { display: block; font-weight: 700; opacity: .7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #calendar .ev-cancelada { opacity: .5; text-decoration: line-through; }
    #calendar .ev-en-curso { box-shadow: 0 0 0 2px color-mix(in srgb, var(--c) 45%, transparent); }
    #calendar .fc-list-event-title { font-weight: 800; }
    #calendar .fc-list-day-cushion { background: #f8fafc !important; font-weight: 900; }

    @media (max-width: 640px) {
        #calendar .fc-toolbar { flex-direction: column; align-items: stretch; }
        #calendar .fc-toolbar-chunk { display: flex; justify-content: center; }
    }
</style>

<script>
    let calendarInstance = null;

    const RUTAS = {
        citas:   @js(route('consultas.citas')),
        agenda:  @js(route('consultas.agenda')),
        store:   @js(route('consultas.store')),
        detalle: id => @js(route('consultas.detalle', ['cita' => '__ID__'])).replace('__ID__', id),
        iniciar: id => @js(route('consultas.iniciar', ['cita' => '__ID__'])).replace('__ID__', id),
        liberar: id => @js(route('consultas.liberar', ['cita' => '__ID__'])).replace('__ID__', id)
    };
    const CSRF = @js(csrf_token());

    const aMinutos = h => { if (!h) return NaN; const [a, b] = String(h).split(':').map(Number); return a * 60 + (b || 0); };
    const horaDeIso = iso => (typeof iso === 'string' && iso.length >= 16) ? iso.slice(11, 16) : '';
    const sumarMin = (h, m) => { const t = aMinutos(h) + m; return String(Math.floor(t / 60) % 24).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0'); };

    function avisoConsultas(icon, title, ms = 3500) {
        window.dispatchEvent(new CustomEvent('toast-consulta', { detail: { icon, title, ms } }));
    }

    async function api(url, method = 'GET', body = null) {
        const r = await fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: body ? JSON.stringify(body) : null
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok) {
            let mensaje = data.message || 'Ocurrió un error. Inténtalo de nuevo.';
            if (data.errors) mensaje = data.errors[Object.keys(data.errors)[0]][0];
            if (r.status === 403) mensaje = 'No tienes permiso para atender esta cita.';
            if (r.status === 419) mensaje = 'Tu sesión expiró. Recarga la página.';
            const e = new Error(mensaje);
            e.status = r.status;
            throw e;
        }
        return data;
    }

    function moduloConsultasDoctor() {
        return {
            // ----- Contexto -----
            esAdmin: @js($esAdmin),
            doctores: @json($doctoresJs),
            filtroDoctor: '',
            hoy: @js(date('Y-m-d')),

            // ----- Agenda -----
            agenda: { resumen: { hoy: 0, por_atender: 0, atendidas: 0, en_curso: 0 }, proxima: null, proximas: [], en_curso: null },
            agendaCargadaEn: Date.now(),
            ahora: new Date(),
            sinConexion: false,

            // ----- Citas de hoy (línea de tiempo) -----
            citasHoy: [],
            hoyListo: false,

            // ----- Calendario -----
            cargando: true,
            citasVista: 0,
            estadosOcultos: [],
            modoRespaldo: false,
            errorCalendarioAvisado: false,
            cacheEventos: { clave: '', lista: [] },   // para filtrar por estado sin volver a pedir al servidor
            usarCache: false,
            coloresEvento: { 'Pendiente': '#f59e0b', 'Confirmada': '#10b981', 'En curso': '#0ea5e9', 'Finalizada': '#94a3b8', 'Cancelada': '#f43f5e' },
            paleta: {
                'Pendiente':  { chip: 'bg-amber-50 text-amber-700',     dot: 'bg-amber-400',   borde: 'border-amber-400' },
                'Confirmada': { chip: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500', borde: 'border-emerald-500' },
                'En curso':   { chip: 'bg-sky-50 text-sky-700',         dot: 'bg-sky-500',     borde: 'border-sky-500' },
                'Finalizada': { chip: 'bg-slate-100 text-slate-600',    dot: 'bg-slate-400',   borde: 'border-slate-400' },
                'Cancelada':  { chip: 'bg-red-50 text-red-600',         dot: 'bg-red-500',     borde: 'border-red-500' }
            },

            panelAbierto: true,
            layoutPanel: true,

            // ----- Modales (varios los usa consultas.modal) -----
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

            confirmar: { abierto: false, cargando: false, titulo: '', texto: '', boton: 'Confirmar' },
            accionConfirmada: null,

            toasts: [],
            toastId: 0,
            estilosToast: {
                success: { titulo: 'Listo',    bi: 'bi-check-lg',       icono: 'bg-emerald-50 text-emerald-600', barra: 'bg-emerald-400' },
                warning: { titulo: 'Atención', bi: 'bi-exclamation-lg', icono: 'bg-amber-50 text-amber-600',     barra: 'bg-amber-400' },
                error:   { titulo: 'Error',    bi: 'bi-x-lg',           icono: 'bg-rose-50 text-rose-600',       barra: 'bg-rose-400' },
                info:    { titulo: 'Aviso',    bi: 'bi-info-lg',        icono: 'bg-sky-50 text-sky-600',         barra: 'bg-sky-400' }
            },
            puedeAvisos: false,

            agendaLista: false,
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

                setInterval(() => {
                    this.ahora = new Date();
                    // Si cambia el día con la página abierta, se recarga la información
                    const f = this.fechaIso(this.ahora);
                    if (f !== this.hoy) { this.hoy = f; this.refrescar(); }
                }, 1000);

                this.cargarAgenda();
                setInterval(() => { if (!document.hidden) this.cargarAgenda(); }, 30000);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) { this.cargarAgenda(); if (calendarInstance) calendarInstance.refetchEvents(); }
                });

                this.$nextTick(() => {
                    if (typeof FullCalendar === 'undefined') {
                        this.cargando = false;
                        avisoConsultas('error', 'No se pudo cargar el calendario (sin acceso a cdn.jsdelivr.net).', 8000);
                    } else {
                        this.initCalendar();
                    }
                    const id = new URLSearchParams(location.search).get('atender');
                    if (id) this.abrirPorId(id);
                });
            },

            // =========================================================== RELOJ Y FORMATOS
            fechaIso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); },
            get saludo() {
                const h = this.ahora.getHours();
                return h < 12 ? 'Buenos días' : (h < 19 ? 'Buenas tardes' : 'Buenas noches');
            },
            get horaActual() { return this.ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }); },
            get fechaHoy() {
                const t = this.ahora.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
                return t.charAt(0).toUpperCase() + t.slice(1);
            },
            get minutosAhora() { return this.ahora.getHours() * 60 + this.ahora.getMinutes(); },
            get cronometro() {
                let s = this.segundosBase;
                if (this.cronometroActivo) s += Math.max(0, Math.floor((this.ahora.getTime() - this.segundosDesde) / 1000));
                const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), seg = s % 60;
                const dos = n => String(n).padStart(2, '0');
                return (h > 0 ? h + ':' : '') + dos(m) + ':' + dos(seg);
            },
            get progresoDia() {
                const r = this.agenda.resumen || {};
                return r.hoy ? Math.min(100, Math.round((r.atendidas || 0) / r.hoy * 100)) : 0;
            },
            porcentaje(clave) {
                const r = this.agenda.resumen || {};
                if (clave === 'hoy') return r.hoy ? 100 : 0;
                return r.hoy ? Math.min(100, Math.round((r[clave] || 0) / r.hoy * 100)) : 0;
            },

            iniciales(nombre) { return (nombre || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase(); },
            fechaLarga(f) {
                if (!f) return '';
                const t = new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                return t.charAt(0).toUpperCase() + t.slice(1);
            },
            fechaCorta(f) {
                if (f === this.hoy) return 'Hoy';
                return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' });
            },
            chipDe(c)  { return (this.paleta[c.estado] || this.paleta['Pendiente']).chip; },
            puntoDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).dot; },
            bordeDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).borde; },

            faltanDe(c) {
                if (c.faltan_min === undefined || c.faltan_min === null) {
                    if (!c.fecha || !c.hora) return NaN;
                    return Math.round((new Date(c.fecha + 'T' + c.hora).getTime() - this.ahora.getTime()) / 60000);
                }
                return c.faltan_min - Math.floor((this.ahora.getTime() - this.agendaCargadaEn) / 60000);
            },
            etiquetaFaltan(c) {
                if (c.estado === 'En curso') return 'En curso';
                const f = this.faltanDe(c);
                if (isNaN(f)) return c.fecha ? this.fechaCorta(c.fecha) : '';
                if (f > 0 && f < 60) return 'En ' + f + ' min';
                if (f === 0) return 'Ahora';
                if (f < 0 && f > -720) return 'Hace ' + (-f) + ' min';
                if (f >= 60 && f < 720) return 'En ' + Math.floor(f / 60) + ' h' + (f % 60 ? ' ' + (f % 60) + ' min' : '');
                return this.fechaCorta(c.fecha);
            },
            urgente(c) {
                const f = this.faltanDe(c);
                return c.estado !== 'En curso' && f <= 15 && f > -60;
            },

            // =========================================================== AGENDA
            async cargarAgenda() {
                try {
                    const url = RUTAS.agenda + (this.filtroDoctor ? '?personal_id=' + encodeURIComponent(this.filtroDoctor) : '');
                    const d = await api(url);
                    this.agenda = {
                        resumen: d.resumen || { hoy: 0, por_atender: 0, atendidas: 0, en_curso: 0 },
                        proxima: d.proxima || null,
                        proximas: Array.isArray(d.proximas) ? d.proximas : [],
                        en_curso: d.en_curso || null
                    };
                    this.agendaCargadaEn = Date.now();
                    this.sinConexion = false;
                    this.animarResumen();
                    if (this.modoRespaldo && calendarInstance) calendarInstance.refetchEvents();
                } catch (e) {
                    this.sinConexion = !e.status;   // sin respuesta del servidor
                }
                this.agendaLista = true;
                this.cargarHoy();
            },

            // Citas de hoy para la línea de tiempo (usa la misma ruta que el calendario)
            async cargarHoy() {
                const manana = new Date(this.hoy + 'T00:00:00');
                manana.setDate(manana.getDate() + 1);
                const params = new URLSearchParams({ start: this.hoy, end: this.fechaIso(manana) });
                if (this.filtroDoctor) params.set('personal_id', this.filtroDoctor);
                try {
                    const lista = await api(RUTAS.citas + '?' + params.toString());
                    if (!Array.isArray(lista)) throw new Error();
                    this.citasHoy = lista.map(e => {
                        const x = e.extendedProps || {};
                        const hora = x.hora || horaDeIso(e.start);
                        return {
                            ...x,
                            cita_id: x.cita_id ?? e.id,
                            paciente_nombre: x.paciente_nombre || e.title || 'Paciente',
                            fecha: x.fecha || (typeof e.start === 'string' ? e.start.slice(0, 10) : this.hoy),
                            hora,
                            hora_fin: x.hora_fin || horaDeIso(e.end) || (hora ? sumarMin(hora, Number(x.duracion_min || 30)) : ''),
                            estado: x.estado || 'Pendiente'
                        };
                    }).filter(c => c.hora && c.fecha === this.hoy).sort((a, b) => a.hora.localeCompare(b.hora));
                } catch (e) {
                    // Si falla, se usan las próximas citas de hoy que ya llegaron en la agenda
                    if (!this.citasHoy.length) this.citasHoy = (this.agenda.proximas || []).filter(c => c.fecha === this.hoy);
                }
                this.hoyListo = true;
            },

            conteoHoy(estado) { return this.citasHoy.filter(c => c.estado === estado).length; },
            get rangoHoy() {
                let ini = 8 * 60, fin = 20 * 60;
                for (const c of this.citasHoy) {
                    const s = aMinutos(c.hora), e = aMinutos(c.hora_fin) || s + 30;
                    if (!isNaN(s)) ini = Math.min(ini, Math.floor(s / 60) * 60);
                    if (!isNaN(e)) fin = Math.max(fin, Math.ceil(e / 60) * 60);
                }
                return { ini, fin };
            },
            posicion(min) { const r = this.rangoHoy; return (min - r.ini) / (r.fin - r.ini) * 100; },
            get horasEje() {
                const r = this.rangoHoy, horas = [];
                const paso = (r.fin - r.ini) / 60 > 12 ? 2 : 1;
                for (let h = r.ini / 60; h <= r.fin / 60; h += paso) horas.push(h);
                return horas;
            },
            // Acomoda las citas en carriles para que no se encimen
            get bloquesHoy() {
                const finCarril = [];
                return this.citasHoy.map(c => {
                    const s = aMinutos(c.hora);
                    const e = Math.max(s + 10, aMinutos(c.hora_fin) || s + 30);
                    let carril = finCarril.findIndex(fin => fin <= s);
                    if (carril < 0) { carril = finCarril.length; finCarril.push(e); } else finCarril[carril] = e;
                    return { c, carril, left: this.posicion(s), width: this.posicion(e) - this.posicion(s) };
                });
            },
            get carrilesHoy() { return Math.max(1, ...this.bloquesHoy.map(b => b.carril + 1)); },

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
            eventosDesdeAgenda() {
                return (this.agenda.proximas || []).map(c => ({
                    id: c.cita_id, title: c.paciente_nombre, start: c.inicio_iso, end: c.fin_iso,
                    color: this.coloresEvento[c.estado] || '#0d9488', extendedProps: c
                }));
            },
            get proximasLista() {
                return (this.agenda.proximas || []).filter(c => !this.estadosOcultos.includes(c.estado)).slice(0, 10);
            },
            async refrescar() {
                await this.cargarAgenda();
                if (calendarInstance) calendarInstance.refetchEvents();
            },
            cambiarDoctor() {
                this.hoyListo = false;
                this.citasHoy = [];
                this.refrescar();
            },
            alternarPanel() {
                const abrir = !this.panelAbierto;
                this.panelAbierto = abrir;
                if (abrir) this.layoutPanel = true;
                else setTimeout(() => { if (!this.panelAbierto) this.layoutPanel = false; }, 180);
                try { localStorage.setItem('consultas_panel_abierto', abrir ? '1' : '0'); } catch (e) {}
                this.ajustarCalendario();
            },
            ajustarCalendario() {
                [0, 200, 420].forEach(ms => setTimeout(() => { if (calendarInstance) calendarInstance.updateSize(); }, ms));
            },
            alternarEstado(nombre) {
                this.estadosOcultos = this.estadoOculto(nombre)
                    ? this.estadosOcultos.filter(e => e !== nombre)
                    : [...this.estadosOcultos, nombre];
                this.aplicarFiltroEstados();
            },
            // Vuelve a dibujar con las citas ya descargadas (sin pedirlas otra vez al servidor)
            aplicarFiltroEstados() {
                if (!calendarInstance) return;
                this.usarCache = true;
                calendarInstance.refetchEvents();
            },
            estadoOculto(nombre) { return this.estadosOcultos.includes(nombre); },

            // =========================================================== CALENDARIO
            // Contenido de cada cita en la vista de mes (hora + paciente)
            contenidoEvento(arg, bloque) {
                const p = arg.event.extendedProps || {};
                const hora = p.hora || horaDeIso(arg.event.startStr);
                const div = document.createElement('div');
                if (bloque) {
                    div.className = 'ev-bloque';
                    const b = document.createElement('b'); b.textContent = arg.event.title;
                    const s = document.createElement('small');
                    s.textContent = hora + (p.hora_fin ? ' – ' + p.hora_fin : '') + (p.consultorio_nombre ? ' · ' + p.consultorio_nombre : '');
                    div.append(b, s);
                } else {
                    div.className = 'ev-contenido';
                    const b = document.createElement('b'); b.textContent = hora;
                    const s = document.createElement('span'); s.textContent = arg.event.title;
                    div.append(b, s);
                }
                return { domNodes: [div] };
            },

            initCalendar() {
                const self = this;
                const calendarEl = document.getElementById('calendar');
                if (!calendarEl) return;

                let montados = 0;
                const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const movil = window.innerWidth < 768;

                calendarInstance = new FullCalendar.Calendar(calendarEl, {
                    locale: 'es',
                    initialView: movil ? 'listWeek' : 'dayGridMonth',
                    headerToolbar: movil
                        ? { left: 'prev,next', center: 'title', right: 'today' }
                        : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
                    footerToolbar: movil ? { center: 'listWeek,timeGridDay,dayGridMonth' } : false,
                    buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista' },
                    height: 'auto',
                    nowIndicator: true,
                    navLinks: true,
                    dayMaxEvents: 3,
                    allDaySlot: false,
                    slotMinTime: '07:00:00',
                    slotMaxTime: '21:00:00',
                    scrollTime: '08:00:00',
                    eventDisplay: 'block',
                    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                    noEventsContent: 'No hay citas en este periodo',
                    views: {
                        dayGridMonth: { eventContent: arg => self.contenidoEvento(arg, false) },
                        timeGrid:     { eventContent: arg => self.contenidoEvento(arg, true) }
                    },

                    events: (info, ok) => {
                        const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
                        if (self.filtroDoctor) params.set('personal_id', self.filtroDoctor);
                        const clave = params.toString();
                        const filtrar = lista => lista.filter(e => !self.estadosOcultos.includes((e.extendedProps || {}).estado));

                        // Solo cambió el filtro de estados: se reutiliza lo descargado
                        if (self.usarCache && self.cacheEventos.clave === clave) {
                            self.usarCache = false;
                            ok(filtrar(self.cacheEventos.lista));
                            return;
                        }
                        self.usarCache = false;

                        fetch(RUTAS.citas + '?' + clave, { headers: { 'Accept': 'application/json' } })
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
                                self.cacheEventos = { clave, lista };
                                ok(filtrar(lista));
                            })
                            .catch(err => {
                                console.error('Calendario:', err);
                                if (!self.errorCalendarioAvisado) {
                                    self.errorCalendarioAvisado = true;
                                    let causa = 'El calendario no pudo cargar todas las citas';
                                    if (err.status === 404) causa += ' (Error 404: la ruta consultas.citas no responde)';
                                    else if (err.status === 403) causa += ' (Error 403: sin permiso)';
                                    else if (err.status) causa += ' (Error ' + err.status + '; revisa storage/logs/laravel.log)';
                                    else causa += ' (' + err.message + ')';
                                    avisoConsultas('error', causa + '. Se muestran solo las próximas citas.', 9000);
                                }
                                self.modoRespaldo = true;
                                ok(filtrar(self.eventosDesdeAgenda()));
                            });
                    },

                    loading: v => { self.cargando = v; },
                    eventsSet: eventos => { self.citasVista = eventos.length; montados = 0; },
                    eventClassNames: arg => {
                        const estado = (arg.event.extendedProps || {}).estado;
                        return ['ev-mt', estado === 'Cancelada' ? 'ev-cancelada' : '', estado === 'En curso' ? 'ev-en-curso' : ''];
                    },
                    eventDidMount: info => {
                        const estado = (info.event.extendedProps || {}).estado;
                        info.el.style.setProperty('--c', self.coloresEvento[estado] || info.event.backgroundColor || '#0d9488');
                        const p = info.event.extendedProps || {};
                        info.el.title = [info.event.title, p.hora && (p.hora + (p.hora_fin ? ' – ' + p.hora_fin : '')), estado, p.motivo].filter(Boolean).join(' · ');
                        if (sinMovimiento) return;
                        info.el.style.animation = 'popIn .4s cubic-bezier(.34, 1.56, .64, 1) backwards';
                        info.el.style.animationDelay = Math.min(montados++ * 35, 420) + 'ms';
                    },
                    eventClick: arg => {
                        arg.jsEvent.preventDefault();
                        self.verDetalle(arg.event.extendedProps);
                    }
                });
                calendarInstance.render();

                if ('ResizeObserver' in window) {
                    let ultimoAncho = Math.round(calendarEl.getBoundingClientRect().width);
                    let cuadro = null;
                    new ResizeObserver(entradas => {
                        const ancho = Math.round(entradas[0].contentRect.width);
                        if (ancho === ultimoAncho) return;
                        ultimoAncho = ancho;
                        cancelAnimationFrame(cuadro);
                        cuadro = requestAnimationFrame(() => calendarInstance && calendarInstance.updateSize());
                    }).observe(calendarEl);
                }
            },

            verDetalle(props) {
                const fecha = props.fecha || this.hoy;
                this.citaActual = { ...props, fecha, fecha_larga: this.fechaLarga(fecha) };
                this.openModalDetalle = true;
            },

            async abrirPorId(id) {
                try {
                    const d = await api(RUTAS.detalle(id));
                    this.citaActual = { ...d.cita, fecha_larga: this.fechaLarga(d.cita.fecha) };
                    this.openModalDetalle = true;
                } catch (e) {
                    avisoConsultas('warning', e.message, 5000);
                }
            },

            // =========================================================== ATENCIÓN
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
                    avisoConsultas('success', r.message || 'Consulta iniciada', 5000);
                    this.citaActual.estado = 'En curso';
                    this.refrescar();
                    await this.abrirAtencion();
                } catch (e) {
                    avisoConsultas('warning', e.message, 6500);
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
                    avisoConsultas('error', e.message, 6000);
                } finally {
                    this.cargandoDetalle = false;
                }
            },

            async guardarAtencion(finalizar = true) {
                if (this.guardando || this.soloLectura) return;
                if (finalizar && !String(this.form.notas_generales || '').trim()) {
                    avisoConsultas('warning', 'Escribe las notas generales / diagnóstico para finalizar la consulta.');
                    return;
                }
                this.guardando = true;
                try {
                    const data = await api(RUTAS.store, 'POST', { ...this.form, finalizar });
                    if (!finalizar) {
                        avisoConsultas('success', data.message || 'Borrador guardado');
                        return;
                    }
                    this.openModalConsulta = false;
                    this.cronometroActivo = false;
                    this.refrescar();
                    avisoConsultas('success', 'Consulta finalizada. El consultorio quedó disponible.', 5000);
                    if (data.redireccion_receta) {
                        setTimeout(() => { window.location.href = data.redireccion_receta; }, 1400);
                    }
                } catch (e) {
                    avisoConsultas('warning', e.message, 6000);
                } finally {
                    this.guardando = false;
                }
            },

            pedirLiberar() {
                this.pedirConfirmacion({
                    titulo: '¿Liberar el consultorio?',
                    texto: 'La consulta no se finalizará: el consultorio quedará disponible y la cita volverá a Confirmada. Lo escrito solo se conserva si guardaste un borrador.',
                    boton: 'Sí, liberar',
                    accion: async () => {
                        const r = await api(RUTAS.liberar(this.citaActual.cita_id), 'POST');
                        this.openModalConsulta = false;
                        this.openModalDetalle = false;
                        this.cronometroActivo = false;
                        avisoConsultas('success', r.message || 'Consultorio liberado', 5000);
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
                    avisoConsultas('error', e.message, 6000);
                }
                this.confirmar.cargando = false;
            },

            // =========================================================== AVISOS DEL NAVEGADOR
            async activarAvisos() {
                try {
                    const permiso = await Notification.requestPermission();
                    this.puedeAvisos = permiso === 'default';
                    if (permiso === 'granted') avisoConsultas('success', 'Avisos activados. Te avisaremos antes de cada cita.');
                    else avisoConsultas('warning', 'No se activaron los avisos del navegador.');
                } catch (e) {}
            },

            // =========================================================== NOTIFICACIONES
            agregarToast(d) {
                const id = ++this.toastId;
                const ms = d.ms || 3500;
                this.toasts.push({ id, tipo: this.estilosToast[d.icon] ? d.icon : 'info', mensaje: d.title, ms, visible: false });
                setTimeout(() => { const t = this.toasts.find(x => x.id === id); if (t) t.visible = true; }, 30);
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