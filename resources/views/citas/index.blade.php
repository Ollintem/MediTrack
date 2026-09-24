@extends('layouts.admin')

@section('content')
@php
    // Mes/año actuales o los que vengan por query string (navegación) — evita hardcodear el mes.
    $currentYear  = (int) request('anio', date('Y'));
    $currentMonth = (int) request('mes', date('m'));

    $inicioMes    = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1);
    $mesAnterior  = $inicioMes->copy()->subMonth();
    $mesSiguiente = $inicioMes->copy()->addMonth();

    $daysInMonth = $inicioMes->daysInMonth;
    // Offset para que la cuadrícula inicie en Lunes (1 = Lunes ... 7 = Domingo).
    $offset = $inicioMes->dayOfWeekIso - 1;

    // Para animar el cambio de mes según la dirección (adelante / atrás)
    $ordinalActual = $currentYear * 12 + $currentMonth;
    $ordinalHoy    = (int) date('Y') * 12 + (int) date('m');
    $dirHoy        = $ordinalHoy >= $ordinalActual ? 'siguiente' : 'anterior';

    $citas = collect($citas ?? []);
    $citasMes = $citas->filter(function ($c) use ($currentYear, $currentMonth) {
        $f = \Carbon\Carbon::parse($c->fecha);
        return $f->year == $currentYear && $f->month == $currentMonth;
    });

    // Catálogos para el modal (el controlador debe enviarlos: $pacientes, $personal, $consultorios)
    $listaPacientes    = collect($pacientes ?? []);
    $listaPersonal     = collect($personal ?? $doctores ?? $medicos ?? []);
    $listaConsultorios = collect($consultorios ?? []);

    $nombreDe = fn ($m) => data_get($m, 'nombre_completo') ?: trim(
        data_get($m, 'nombre', '') . ' ' . (data_get($m, 'apellidos') ?? data_get($m, 'apellido_paterno') ?? '')
    );

    $opPacientes    = $listaPacientes->map(fn ($m) => ['id' => data_get($m, 'id'), 'nombre' => $nombreDe($m)])
                        ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
    $opPersonal     = $listaPersonal->map(fn ($m) => ['id' => data_get($m, 'id'), 'nombre' => $nombreDe($m)])
                        ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();
    $opConsultorios = $listaConsultorios->map(fn ($m) => [
        'id' => data_get($m, 'id'), 'nombre' => data_get($m, 'nombre'),
        'piso' => data_get($m, 'piso'), 'estado' => data_get($m, 'estado'),
    ])->values();

    // Todas las citas en formato simple para Alpine (evita armar JS a mano con addslashes)
    $citasJs = $citas->map(fn ($c) => [
        'id'             => $c->id,
        'fecha'          => \Carbon\Carbon::parse($c->fecha)->format('Y-m-d'),
        'hora'           => \Carbon\Carbon::parse($c->hora)->format('H:i'),
        'duracion_min'   => (int) ($c->duracion_min ?? 30),
        'estado'         => $c->estado ?? 'Pendiente',
        'motivo'         => $c->motivo ?? '',
        'tipo_consulta'  => $c->tipo_consulta ?? 'Primera Vez',
        'paciente_id'    => $c->paciente_id,
        'personal_id'    => $c->personal_id,
        'consultorio_id' => $c->consultorio_id,
        'paciente'       => data_get($c, 'paciente.nombre_completo') ?: (data_get($c, 'paciente.nombre') ?: 'Paciente'),
    ])->values();

    $mesesAbrev = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
@endphp

<div x-data="moduloCitas()" class="space-y-6 animate-fade-in">

    <!-- Encabezado -->
    <div class="aparece flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Calendario de citas</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Visualización general del calendario y programación de la clínica.</p>
        </div>
        <button @click="nuevaCita()" type="button"
                class="group inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-md shadow-teal-700/20 hover:shadow-lg hover:shadow-teal-700/30 transition-all transform hover:-translate-y-0.5 active:scale-95 cursor-pointer">
            <i class="bi bi-plus-lg text-sm transition-transform duration-300 group-hover:rotate-90"></i>
            <span>Agendar nueva cita</span>
        </button>
    </div>

    <!-- Resumen rápido del mes -->
    @php
        $tarjetas = [
            ['Citas del mes', $citasMes->count(),                                 'bi-calendar3',      'bg-teal-50 text-teal-600',       'bg-teal-500'],
            ['Pendientes',    $citasMes->where('estado', 'Pendiente')->count(),   'bi-hourglass-split', 'bg-amber-50 text-amber-600',     'bg-amber-400'],
            ['Confirmadas',   $citasMes->where('estado', 'Confirmada')->count(),  'bi-check2-circle',  'bg-emerald-50 text-emerald-600', 'bg-emerald-500'],
            ['Canceladas',    $citasMes->where('estado', 'Cancelada')->count(),   'bi-x-circle',       'bg-red-50 text-red-500',         'bg-red-500'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $valor, $icono, $colorIcono, $colorPunto])
            <div class="tarjeta-stat group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center justify-between gap-3 transition-all hover:shadow-md hover:-translate-y-1 hover:border-slate-200"
                 style="animation-delay: {{ $i * 70 }}ms">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $colorPunto }}"></span> {{ $titulo }}
                    </p>
                    <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-data="contador({{ $valor }})" x-text="valor">{{ $valor }}</p>
                </div>
                <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $colorIcono }}">
                    <i class="bi {{ $icono }}"></i>
                </span>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 items-start" :class="layoutPanel ? 'lg:grid-cols-[1fr_300px]' : 'lg:grid-cols-1'">

        <!-- Contenedor principal del calendario -->
        <div class="aparece bg-white p-6 rounded-3xl border border-slate-100 shadow-sm min-w-0" style="--d: 260ms">
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3"
                 x-data="{ openSelector: false, verAno: {{ $currentYear }}, base: '{{ request()->url() }}' }">

                <!-- Disparador: abre el selector rápido de mes / año -->
                <div class="relative">
                    <button type="button" @click="openSelector = !openSelector; verAno = {{ $currentYear }};"
                            class="text-sm font-bold text-slate-800 flex items-center gap-2 px-2.5 py-1.5 -ml-2.5 rounded-xl hover:bg-slate-50 transition-all cursor-pointer">
                        <i class="bi bi-calendar-event text-teal-600"></i>
                        <span>{{ ucfirst($inicioMes->translatedFormat('F Y')) }}</span>
                        <i class="bi bi-chevron-down text-[10px] text-slate-400 transition-transform" :class="openSelector && 'rotate-180'"></i>
                    </button>

                    <!-- Panel: año + cuadrícula de meses -->
                    <div x-show="openSelector" @click.outside="openSelector = false" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-0 mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-900/10 z-30 p-3 w-64 origin-top-left">

                        <div class="flex items-center justify-between mb-3">
                            <button type="button" @click="verAno--" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 cursor-pointer">
                                <i class="bi bi-chevron-left text-[10px]"></i>
                            </button>
                            <span class="text-xs font-black text-slate-800" x-text="verAno"></span>
                            <button type="button" @click="verAno++" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 cursor-pointer">
                                <i class="bi bi-chevron-right text-[10px]"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-3 gap-1.5">
                            @foreach ($mesesAbrev as $indice => $nombreMes)
                                <a :href="base + '?mes={{ $indice + 1 }}&anio=' + verAno"
                                   @click="recordarDireccion((verAno * 12 + {{ $indice + 1 }}) > {{ $ordinalActual }} ? 'siguiente' : 'anterior')"
                                   class="text-center text-[11px] font-bold py-2 rounded-xl transition-all"
                                   :class="(verAno == {{ $currentYear }} && {{ $indice + 1 }} == {{ $currentMonth }}) ? 'bg-teal-700 text-white' : 'text-slate-600 hover:bg-slate-100'">
                                    {{ $nombreMes }}
                                </a>
                            @endforeach
                        </div>

                        <a href="{{ request()->fullUrlWithQuery(['mes' => date('m'), 'anio' => date('Y')]) }}"
                           @click="recordarDireccion('{{ $dirHoy }}')"
                           class="block text-center w-full mt-3 pt-3 border-t border-slate-100 text-[10px] font-black text-teal-700 hover:text-teal-800 uppercase tracking-wide">
                            Ir a hoy
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Mostrar / ocultar el panel de próximas citas -->
                    <button type="button" @click="alternarPanel()"
                            :class="panelAbierto ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300'"
                            :title="panelAbierto ? 'Ocultar próximas citas' : 'Mostrar próximas citas'"
                            class="flex items-center gap-2 border rounded-xl px-3 py-2 text-[11px] font-bold transition-all cursor-pointer">
                        <i class="bi" :class="panelAbierto ? 'bi-layout-sidebar-inset-reverse' : 'bi-layout-sidebar-reverse'"></i>
                        <span>Próximas citas</span>
                        <span class="min-w-[18px] h-[18px] px-1 rounded-full bg-teal-600 text-white text-[9px] font-black flex items-center justify-center" x-text="proximas.length"></span>
                    </button>

                    <!-- Filtro por doctor -->
                    <select x-model="filtroDoctor" x-show="personal.length" x-cloak
                            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                        <option value="">Todos los doctores</option>
                        <template x-for="p in personal" :key="p.id">
                            <option :value="p.id" x-text="p.nombre"></option>
                        </template>
                    </select>

                    <!-- Navegación rápida de un mes -->
                    <div class="flex items-center gap-1.5">
                        <a href="{{ request()->fullUrlWithQuery(['mes' => $mesAnterior->month, 'anio' => $mesAnterior->year]) }}"
                           @click="recordarDireccion('anterior')"
                           class="w-8 h-8 rounded-xl border border-slate-200 hover:bg-slate-50 hover:-translate-x-0.5 flex items-center justify-center text-slate-500 transition-all">
                            <i class="bi bi-chevron-left text-xs"></i>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['mes' => date('m'), 'anio' => date('Y')]) }}"
                           @click="recordarDireccion('{{ $dirHoy }}')"
                           class="text-[11px] font-bold text-slate-500 hover:text-teal-700 bg-slate-50 hover:bg-teal-50 px-3 py-1.5 rounded-xl transition-all">
                            Hoy
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['mes' => $mesSiguiente->month, 'anio' => $mesSiguiente->year]) }}"
                           @click="recordarDireccion('siguiente')"
                           class="w-8 h-8 rounded-xl border border-slate-200 hover:bg-slate-50 hover:translate-x-0.5 flex items-center justify-center text-slate-500 transition-all">
                            <i class="bi bi-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cuadrícula del calendario -->
            <div class="grid grid-cols-7 gap-2 text-center" :class="dirAnim === 'siguiente' ? 'grid-slide-derecha' : (dirAnim === 'anterior' ? 'grid-slide-izquierda' : '')">
                @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dia)
                    <span class="text-[10px] font-bold text-slate-400 uppercase py-2">{{ $dia }}</span>
                @endforeach

                {{-- Celdas vacías antes del día 1, para alinear con el día de la semana correcto --}}
                @for ($i = 0; $i < $offset; $i++)
                    <div></div>
                @endfor

                @for ($dia = 1; $dia <= $daysInMonth; $dia++)
                    @php
                        $fechaDia = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $dia);
                        $esHoy = $fechaDia === date('Y-m-d');
                        $esFinDeSemana = (($offset + $dia - 1) % 7) >= 5;
                    @endphp

                    <div @click="ripple($event); nuevaCita('{{ $fechaDia }}')"
                         style="animation-delay: {{ min(($dia - 1) * 12, 300) }}ms"
                         title="Clic para agendar una cita el {{ $dia }}"
                         class="celda-dia min-h-[104px] p-2 rounded-2xl border hover:bg-teal-50/40 hover:border-teal-200 hover:shadow-sm hover:-translate-y-0.5 transition-all text-left flex flex-col gap-1.5 group cursor-pointer relative overflow-hidden
                                {{ $esHoy ? 'border-teal-300 ring-1 ring-teal-200 bg-teal-50/30 hoy-pulso' : 'border-slate-100 ' . ($esFinDeSemana ? 'bg-slate-100/50' : 'bg-slate-50/50') }}">

                        <div class="flex items-center justify-between">
                        <span class="text-xs font-black w-5 h-5 flex items-center justify-center rounded-full shrink-0 transition-all {{ $esHoy ? 'bg-teal-700 text-white shadow-[0_0_0_4px_rgba(15,118,110,0.15)]' : 'text-slate-500 group-hover:text-teal-700 group-hover:bg-teal-100' }}">
                                {{ $dia }}
                        </span>
                            <span class="w-5 h-5 rounded-full bg-teal-600 text-white flex items-center justify-center opacity-0 scale-50 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200 shadow-sm shadow-teal-600/30">
                                <i class="bi bi-plus text-sm leading-none"></i>
                            </span>
                        </div>

                        <div class="space-y-1">
                            <template x-for="(c, i) in citasDia('{{ $fechaDia }}').slice(0, maxVisibles)" :key="c.id">
                                <div @click.stop="verCita(c.id)"
                                     :class="[chipDe(c), bordeDe(c)]"
                                     :style="'--d:' + (220 + i * 70) + 'ms'"
                                     class="chip-cita border-l-[3px] text-[9px] font-bold px-1.5 py-1 rounded-lg truncate hover:scale-[1.03] hover:shadow-sm transition-all"
                                     :title="c.paciente + ' · ' + c.hora + (c.motivo ? ' · ' + c.motivo : '')">
                                    <span class="opacity-70" x-text="c.hora"></span>
                                    <span x-text="c.paciente"></span>
                                </div>
                            </template>

                            <button type="button"
                                    x-show="citasDia('{{ $fechaDia }}').length > maxVisibles" x-cloak
                                    @click.stop="abrirDia('{{ $fechaDia }}')"
                                    class="text-[9px] font-black text-slate-400 hover:text-teal-700 px-1.5 cursor-pointer"
                                    x-text="'+' + (citasDia('{{ $fechaDia }}').length - maxVisibles) + ' más'"></button>
                        </div>
                    </div>
                @endfor
            </div>

            <!-- Leyenda / filtro por estado -->
            <div class="flex flex-wrap items-center gap-x-2 gap-y-2 mt-6 pt-4 border-t border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide mr-1">Estados</span>
                <template x-for="(p, nombre) in paleta" :key="nombre">
                    <button type="button" @click="alternarEstado(nombre)"
                            :class="estadoOculto(nombre) ? 'opacity-40 line-through' : ''"
                            class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 px-2.5 py-1 rounded-full border border-slate-200 hover:border-teal-300 hover:bg-teal-50/40 transition-all cursor-pointer">
                        <span class="w-2 h-2 rounded-full" :class="p.dot"></span>
                        <span x-text="nombre"></span>
                    </button>
                </template>
                <span class="text-[10px] font-semibold text-slate-300 ml-auto hidden sm:inline">Haz clic en un estado para ocultarlo o mostrarlo</span>
            </div>
        </div>

        <!-- Próximas citas -->
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

                <!-- Sin citas próximas -->
                <div x-show="proximas.length === 0" x-cloak class="py-8 text-center">
                    <span class="flotar inline-flex w-14 h-14 rounded-2xl bg-teal-50 text-teal-500 items-center justify-center text-2xl mb-3">
                        <i class="bi bi-calendar2-check"></i>
                    </span>
                    <p class="text-xs font-black text-slate-500">Sin citas próximas</p>
                    <p class="text-[10px] font-medium text-slate-400 mt-0.5">Cuando agendes una, aparecerá aquí.</p>
                </div>

                <div class="space-y-1.5">
                    <template x-for="(c, i) in proximas" :key="c.id">
                        <button type="button" @click="verCita(c.id)"
                                :style="'--d:' + (i * 70) + 'ms'"
                                class="aparece w-full text-left flex items-center gap-3 p-2.5 rounded-2xl border border-transparent hover:bg-slate-50 hover:border-slate-100 hover:translate-x-0.5 transition-all cursor-pointer">
                            <span class="w-1 self-stretch rounded-full" :class="puntoDe(c)"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-black text-slate-800 truncate" x-text="c.paciente"></span>
                                <span class="flex items-center gap-1.5 text-[10px] font-bold" :class="pronto(c) ? 'text-amber-600' : 'text-slate-400'">
                                    <span x-show="pronto(c)" class="latido w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                                    <span x-text="etiquetaRelativa(c)"></span>
                                </span>
                            </span>
                            <span class="text-[9px] font-black px-2 py-0.5 rounded-full" :class="chipDe(c)" x-text="c.estado"></span>
                        </button>
                    </template>
                </div>
            </div>
        </aside>
    </div>

    <!-- MODAL: AGENDA DEL DÍA (cuando hay más citas de las que caben en la celda) -->
    <template x-teleport="body">
        <div x-show="openDia" x-cloak
             @keydown.escape.window="!confirmar.abierto && (openDia = false)"
             class="fixed inset-0 z-[99997] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[85vh] flex flex-col overflow-hidden" @click.outside="openDia = false"
                 x-show="openDia"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="relative px-7 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white overflow-hidden shrink-0">
                    <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                    <div class="relative flex items-center gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Agenda del día</p>
                            <h3 class="text-base font-black tracking-tight" x-text="fechaLarga(diaSeleccionado)"></h3>
                        </div>
                        <button type="button" @click="openDia = false"
                                class="ml-auto w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <div class="p-5 space-y-2 overflow-y-auto">
                    <template x-for="(c, i) in citasDia(diaSeleccionado)" :key="c.id">
                        <button type="button" @click="openDia = false; verCita(c.id)"
                                :class="openDia && 'reveal-on'" :style="'--d:' + i"
                                class="reveal-item w-full text-left flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:bg-slate-50 hover:border-teal-200 hover:translate-x-0.5 transition-all cursor-pointer">
                            <span class="w-1 self-stretch rounded-full" :class="puntoDe(c)"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-black text-slate-800 truncate" x-text="c.paciente"></span>
                                <span class="block text-[10px] font-bold text-slate-400" x-text="c.hora + ' – ' + horaFin(c)"></span>
                            </span>
                            <span class="text-[9px] font-black px-2 py-0.5 rounded-full" :class="chipDe(c)" x-text="c.estado"></span>
                        </button>
                    </template>
                </div>

                <div class="px-5 py-4 border-t border-slate-100 flex justify-end shrink-0">
                    <button type="button" @click="openDia = false; nuevaCita(diaSeleccionado)"
                            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="bi bi-plus-lg"></i> Nueva cita este día
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: DETALLE DE CITA -->
    <template x-teleport="body">
        <div x-show="openDetalle" x-cloak
             @keydown.escape.window="!confirmar.abierto && (openDetalle = false)"
             class="fixed inset-0 z-[99998] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.outside="openDetalle = false"
                 x-show="openDetalle"
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
                            <button type="button" @click="openDetalle = false"
                                    class="absolute right-4 top-4 w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                            <div class="relative flex items-center gap-4">
                                <span class="w-14 h-14 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-lg font-black text-emerald-300 shrink-0"
                                      :class="openDetalle && 'icono-pop'"
                                      x-text="iniciales(citaActual.paciente)"></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Paciente</p>
                                    <h3 class="text-base font-black tracking-tight truncate" x-text="citaActual.paciente"></h3>
                                    <span class="inline-block mt-1 text-[9px] font-black px-2 py-0.5 rounded-full" :class="chipDe(citaActual)" x-text="citaActual.estado"></span>
                                </div>
                            </div>
                        </div>

                        <div class="p-7 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openDetalle && 'reveal-on'" style="--d:1">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Fecha y horario</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="fechaLarga(citaActual.fecha)"></p>
                                    <p class="text-xs font-bold text-slate-500" x-text="citaActual.hora + ' – ' + horaFin(citaActual) + ' (' + citaActual.duracion_min + ' min)'"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openDetalle && 'reveal-on'" style="--d:2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Doctor</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="nombreDe('personal', citaActual.personal_id)"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openDetalle && 'reveal-on'" style="--d:3">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Consultorio</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="nombreDe('consultorios', citaActual.consultorio_id)"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openDetalle && 'reveal-on'" style="--d:4">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Tipo de consulta</p>
                                    <p class="text-xs font-black text-slate-800 mt-0.5" x-text="citaActual.tipo_consulta"></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 col-span-2 reveal-item" :class="openDetalle && 'reveal-on'" style="--d:5">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Motivo</p>
                                    <p class="text-xs font-bold text-slate-700 mt-0.5 leading-relaxed" x-text="citaActual.motivo || 'Sin motivo registrado'"></p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                                <button type="button" @click="openDetalle = false"
                                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                                    Cerrar
                                </button>
                                <button type="button" @click="eliminarCita(citaActual.id)"
                                        class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                    <i class="bi bi-trash-fill"></i> Eliminar
                                </button>
                                <button type="button" @click="editarCita(citaActual.id)"
                                        class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                                    <i class="bi bi-pencil-fill"></i> Editar
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- CONFIRMAR ELIMINACIÓN (por encima de los demás modales) -->
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
                <span class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-2xl mb-4"
                      :class="confirmar.abierto && 'icono-pop'">
                    <i class="bi bi-trash3-fill"></i>
                </span>
                <h3 class="text-base font-black text-slate-800">¿Eliminar esta cita?</h3>
                <p class="text-xs font-medium text-slate-500 mt-2 leading-relaxed">
                    Se eliminará la cita de <strong class="text-slate-700" x-text="confirmar.paciente"></strong>
                    <span x-show="confirmar.fecha" x-text="'del ' + confirmar.fecha"></span>.
                    Esta acción no se puede deshacer.
                </p>

                <div class="flex gap-2.5 mt-6">
                    <button type="button" @click="confirmar.abierto = false" :disabled="confirmar.cargando"
                            class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer disabled:opacity-50">
                        Cancelar
                    </button>
                    <button type="button" @click="confirmarEliminar()" :disabled="confirmar.cargando"
                            class="flex-1 px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-black rounded-2xl text-xs shadow-lg shadow-rose-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="confirmar.cargando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <i x-show="!confirmar.cargando" class="bi bi-trash-fill"></i>
                        <span x-text="confirmar.cargando ? 'Eliminando...' : 'Sí, eliminar'"></span>
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
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-base icono-pop"
                          :class="estilosToast[t.tipo].icono">
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

    <!-- MODAL CREAR / EDITAR CITA -->
    @include('citas.modal')

</div>

<style>
    /* ============ Animaciones ============
       Los estados finales quedan en "none" y se usa fill-mode "backwards" para que
       los efectos hover (translate/scale) sigan funcionando después de la entrada. */
    @keyframes fadeIn   { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes celdaIn  { from { opacity: 0; transform: translateY(10px) scale(.96); } to { opacity: 1; transform: none; } }
    @keyframes popIn    { 0% { opacity: 0; transform: scale(.8); } 60% { opacity: 1; transform: scale(1.05); } 100% { opacity: 1; transform: none; } }
    @keyframes iconoPop { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes slideDerecha   { from { opacity: 0; transform: translateX(40px); }  to { opacity: 1; transform: none; } }
    @keyframes slideIzquierda { from { opacity: 0; transform: translateX(-40px); } to { opacity: 1; transform: none; } }
    @keyframes pulsoHoy { 0%, 100% { box-shadow: 0 0 0 0 rgba(20, 184, 166, .35); } 50% { box-shadow: 0 0 0 6px rgba(20, 184, 166, 0); } }
    @keyframes ripple   { to { transform: scale(4); opacity: 0; } }
    @keyframes latido   { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .6; } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }

    .animate-fade-in { animation: fadeIn .35s cubic-bezier(.16, 1, .3, 1) backwards; }
    .aparece         { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat    { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .celda-dia       { animation: celdaIn .45s cubic-bezier(.16, 1, .3, 1) backwards; }
    .chip-cita       { animation: popIn .4s cubic-bezier(.34, 1.56, .64, 1) backwards; animation-delay: var(--d, 0ms); }
    .icono-pop       { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .grid-slide-derecha   { animation: slideDerecha .45s cubic-bezier(.16, 1, .3, 1); }
    .grid-slide-izquierda { animation: slideIzquierda .45s cubic-bezier(.16, 1, .3, 1); }
    .hoy-pulso { animation: celdaIn .45s cubic-bezier(.16, 1, .3, 1) backwards, pulsoHoy 2.6s ease-in-out 1s infinite; }
    .latido    { animation: latido 1.4s ease-in-out infinite; }
    .flotar    { animation: flotar 3s ease-in-out infinite; }

    /* Onda al hacer clic en un día */
    .ripple-onda { position: absolute; border-radius: 9999px; background: rgba(13, 148, 136, .22); transform: scale(0); animation: ripple .6s ease-out forwards; pointer-events: none; }

    /* Elementos de los modales que aparecen escalonados cada vez que se abren */
    .reveal-item { opacity: 0; }
    .reveal-on   { animation: fadeUp .45s cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--d, 0) * 70ms); }

    /* Barra de tiempo de las notificaciones */
    @keyframes barraToast { from { width: 100%; } to { width: 0; } }

    @media (prefers-reduced-motion: reduce) {
        .animate-fade-in, .aparece, .tarjeta-stat, .celda-dia, .chip-cita, .icono-pop, .hoy-pulso,
        .grid-slide-derecha, .grid-slide-izquierda, .latido, .flotar, .reveal-on, .ripple-onda {
            animation: none !important;
        }
        .reveal-item { opacity: 1 !important; }
    }

    [x-cloak] { display: none !important; }

</style>

<script>
    const URL_CITAS = '{{ url('citas') }}';

    // Contador que sube desde 0 hasta el valor (tarjetas de resumen)
    function contador(meta) {
        return {
            valor: 0,
            init() {
                if (meta === 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    this.valor = meta;
                    return;
                }
                const duracion = 800;
                setTimeout(() => {
                    const inicio = performance.now();
                    const paso = (t) => {
                        const p = Math.min(1, (t - inicio) / duracion);
                        this.valor = Math.round(meta * (1 - Math.pow(1 - p, 3)));   // desacelera al final
                        if (p < 1) requestAnimationFrame(paso);
                    };
                    requestAnimationFrame(paso);
                }, 300);
            }
        };
    }

    // Notificación flotante (esquina superior derecha). Se dibuja en el componente, por encima de los modales.
    function notificar(icon, title, ms = 3500) {
        window.dispatchEvent(new CustomEvent('toast-cita', { detail: { icon, title, ms } }));
    }

    // Muestra el toast después de recargar la página
    function toastYRecargar(icon, title) {
        sessionStorage.setItem('toast_citas', JSON.stringify({ icon, title }));
        location.reload();
    }

    function moduloCitas() {
        return {
            // ----- Datos -----
            citas: @json($citasJs),
            pacientes: @json($opPacientes),
            personal: @json($opPersonal),
            consultorios: @json($opConsultorios),
            hoy: '{{ date('Y-m-d') }}',

            paleta: {
                'Pendiente':  { chip: 'bg-amber-50 text-amber-700',     dot: 'bg-amber-400', borde: 'border-amber-400' },
                'Confirmada': { chip: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500', borde: 'border-emerald-500' },
                'En curso':   { chip: 'bg-sky-50 text-sky-700',         dot: 'bg-sky-500', borde: 'border-sky-500' },
                'Finalizada': { chip: 'bg-slate-100 text-slate-600',    dot: 'bg-slate-400', borde: 'border-slate-400' },
                'Cancelada':  { chip: 'bg-red-50 text-red-600',         dot: 'bg-red-500', borde: 'border-red-500' }
            },

            // ----- Animaciones -----
            dirAnim: '',            // dirección del último cambio de mes
            ahora: Date.now(),      // se actualiza cada 30 s para las etiquetas "En 25 min"

            // ----- Filtros -----
            filtroDoctor: '',
            estadosOcultos: [],

            // ----- Modales -----
            openCitaModal: false,
            modoEdicion: false,
            guardando: false,
            openDetalle: false,
            citaActual: null,
            openDia: false,
            diaSeleccionado: '',
            form: {},

            // ----- Panel de próximas citas (plegable) -----
            panelAbierto: true,
            layoutPanel: true,   // columnas del diseño (cambia con retraso al ocultar el panel)
            get maxVisibles() { return this.panelAbierto ? 2 : 3; },
            alternarPanel() {
                const abrir = !this.panelAbierto;
                this.panelAbierto = abrir;
                // Al cerrar se espera a que termine la animación, para que el panel no salte debajo del calendario
                if (abrir) this.layoutPanel = true;
                else setTimeout(() => { if (!this.panelAbierto) this.layoutPanel = false; }, 180);
                try { localStorage.setItem('citas_panel_abierto', abrir ? '1' : '0'); } catch (e) {}
            },

            // ----- Confirmación de eliminar -----
            confirmar: { abierto: false, cargando: false, id: null, paciente: '', fecha: '' },

            // ----- Notificaciones -----
            toasts: [],
            toastId: 0,
            estilosToast: {
                success: { titulo: 'Listo',     bi: 'bi-check-lg',       icono: 'bg-emerald-50 text-emerald-600', barra: 'bg-emerald-400' },
                warning: { titulo: 'Atención',  bi: 'bi-exclamation-lg', icono: 'bg-amber-50 text-amber-600',     barra: 'bg-amber-400' },
                error:   { titulo: 'Error',     bi: 'bi-x-lg',           icono: 'bg-rose-50 text-rose-600',       barra: 'bg-rose-400' },
                info:    { titulo: 'Aviso',     bi: 'bi-info-lg',        icono: 'bg-sky-50 text-sky-600',         barra: 'bg-sky-400' }
            },
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
            },

            init() {
                this.form = this.formNuevo();

                // Reloj para las etiquetas de tiempo relativo
                setInterval(() => { this.ahora = Date.now(); }, 30000);

                // Dirección con la que se llegó a este mes (para deslizar el calendario)
                const dir = sessionStorage.getItem('citas_dir');
                if (dir) {
                    sessionStorage.removeItem('citas_dir');
                    this.dirAnim = dir;
                }

                window.addEventListener('toast-cita', e => this.agregarToast(e.detail));

                try {
                    const guardado = localStorage.getItem('citas_panel_abierto');
                    if (guardado !== null) this.panelAbierto = guardado === '1';
                } catch (e) {}
                this.layoutPanel = this.panelAbierto;

                const pendiente = sessionStorage.getItem('toast_citas');
                if (pendiente) {
                    sessionStorage.removeItem('toast_citas');
                    const t = JSON.parse(pendiente);
                    this.$nextTick(() => notificar(t.icon, t.title));
                }
            },

            // ----- Utilidades de estado / formato -----
            chipDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).chip; },
            puntoDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).dot; },
            bordeDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).borde; },

            // ----- Tiempo relativo de las próximas citas -----
            minutosPara(c) {
                return Math.round((new Date(c.fecha + 'T' + c.hora).getTime() - this.ahora) / 60000);
            },
            pronto(c) {
                const m = this.minutosPara(c);
                return c.estado !== 'En curso' && m <= 60 && m > -30;
            },
            etiquetaRelativa(c) {
                if (c.estado === 'En curso') return 'En curso ahora';
                const m = this.minutosPara(c);
                if (m <= 0 && m > -30) return 'Ahora · ' + c.hora;
                if (m > 0 && m < 60) return 'En ' + m + ' min · ' + c.hora;
                return this.fechaCorta(c.fecha) + ' · ' + c.hora;
            },

            // ----- Efectos -----
            recordarDireccion(dir) {
                try { sessionStorage.setItem('citas_dir', dir); } catch (e) {}
            },
            // Onda que nace en el punto donde se hizo clic
            ripple(e) {
                const el = e.currentTarget;
                if (!el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                const r = el.getBoundingClientRect();
                const d = Math.max(r.width, r.height);
                const onda = document.createElement('span');
                onda.className = 'ripple-onda';
                onda.style.width = onda.style.height = d + 'px';
                onda.style.left = (e.clientX - r.left - d / 2) + 'px';
                onda.style.top = (e.clientY - r.top - d / 2) + 'px';
                el.appendChild(onda);
                setTimeout(() => onda.remove(), 650);
            },

            alternarEstado(nombre) {
                this.estadosOcultos = this.estadoOculto(nombre)
                    ? this.estadosOcultos.filter(e => e !== nombre)
                    : [...this.estadosOcultos, nombre];
            },
            estadoOculto(nombre) { return this.estadosOcultos.includes(nombre); },

            iniciales(nombre) {
                return (nombre || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
            },

            nombreDe(lista, id) {
                const item = this[lista].find(x => String(x.id) === String(id));
                return item ? item.nombre : '—';
            },

            horaFin(c) {
                const [h, m] = c.hora.split(':').map(Number);
                const total = h * 60 + m + Number(c.duracion_min || 30);
                return String(Math.floor(total / 60) % 24).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
            },

            fechaCorta(f) {
                if (f === this.hoy) return 'Hoy';
                return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' });
            },

            fechaLarga(f) {
                if (!f) return '';
                const txt = new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                return txt.charAt(0).toUpperCase() + txt.slice(1);   // solo la primera letra en mayúscula
            },

            // ----- Citas filtradas -----
            get citasFiltradas() {
                return this.citas.filter(c =>
                    !this.estadosOcultos.includes(c.estado) &&
                    (this.filtroDoctor === '' || String(c.personal_id) === String(this.filtroDoctor))
                );
            },

            citasDia(fecha) {
                return this.citasFiltradas
                    .filter(c => c.fecha === fecha)
                    .sort((a, b) => a.hora.localeCompare(b.hora));
            },

            get proximas() {
                const desde = new Date(Date.now() - 30 * 60000);
                return this.citasFiltradas
                    .filter(c => ['Pendiente', 'Confirmada', 'En curso'].includes(c.estado) &&
                                 new Date(c.fecha + 'T' + c.hora) >= desde)
                    .sort((a, b) => (a.fecha + a.hora).localeCompare(b.fecha + b.hora))
                    .slice(0, 6);
            },

            // ----- Abrir modales -----
            abrirDia(fecha) {
                this.diaSeleccionado = fecha;
                this.openDia = true;
            },

            verCita(id) {
                const c = this.citas.find(x => x.id === id);
                if (!c) return;
                this.citaActual = { ...c };
                this.openDetalle = true;
            },

            formNuevo(fecha = null) {
                return {
                    id: null,
                    motivo: '',
                    paciente_id: '',
                    personal_id: '',
                    consultorio_id: '',
                    fecha: fecha || this.hoy,
                    hora: '09:00',
                    duracion_min: '30',
                    tipo_consulta: 'Primera Vez',
                    estado: 'Pendiente'
                };
            },

            nuevaCita(fecha = null) {
                this.modoEdicion = false;
                this.form = this.formNuevo(fecha);
                this.openCitaModal = true;
            },

            editarCita(id) {
                const c = this.citas.find(x => x.id === id);
                if (!c) return;
                this.modoEdicion = true;
                // El id se guarda en el formulario: sin él, el guardado creaba una cita nueva (duplicada)
                this.form = {
                    id: c.id,
                    motivo: c.motivo,
                    paciente_id: c.paciente_id,
                    personal_id: c.personal_id,
                    consultorio_id: c.consultorio_id,
                    fecha: c.fecha,
                    hora: c.hora,
                    duracion_min: String(c.duracion_min),
                    tipo_consulta: c.tipo_consulta,
                    estado: c.estado
                };
                this.openDetalle = false;
                this.openDia = false;
                this.openCitaModal = true;
            },

            // ----- Guardar (crear o actualizar) -----
            async guardar() {
                if (this.guardando) return;

                const f = this.form;
                if (!f.paciente_id || !f.personal_id || !f.consultorio_id) {
                    notificar('warning', 'Selecciona el paciente, el médico y el consultorio.');
                    return;
                }
                if (!f.fecha || !f.hora) {
                    notificar('warning', 'Indica la fecha y la hora de la cita.');
                    return;
                }
                if (!String(f.motivo || '').trim()) {
                    notificar('warning', 'Escribe el motivo de la consulta.');
                    return;
                }

                this.guardando = true;

                const editando = this.modoEdicion && this.form.id;
                const url = editando ? `${URL_CITAS}/${this.form.id}` : URL_CITAS;
                const payload = {
                    paciente_id: this.form.paciente_id,
                    personal_id: this.form.personal_id,
                    consultorio_id: this.form.consultorio_id,
                    fecha: this.form.fecha,
                    hora: this.form.hora,
                    duracion_min: this.form.duracion_min,
                    tipo_consulta: this.form.tipo_consulta,
                    estado: this.form.estado,
                    motivo: this.form.motivo
                };

                try {
                    const r = await fetch(url, {
                        method: editando ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });
                    const res = await r.json().catch(() => ({}));

                    if (!r.ok) {
                        let mensaje = res.message || 'No se pudo guardar la cita.';
                        if (res.errors) mensaje = res.errors[Object.keys(res.errors)[0]][0];
                        notificar('warning', mensaje, 5000);
                        this.guardando = false;
                        return;
                    }

                    this.openCitaModal = false;
                    toastYRecargar('success', res.message || (editando ? 'Cita actualizada' : 'Cita agendada'));
                } catch (e) {
                    console.error('Error de red:', e);
                    notificar('error', 'No se pudo comunicar con el servidor');
                    this.guardando = false;
                }
            },

            // ----- Eliminar -----
            eliminarCita(id) {
                const c = this.citas.find(x => x.id === id);
                this.confirmar = {
                    abierto: true,
                    cargando: false,
                    id: id,
                    paciente: c ? c.paciente : 'este paciente',
                    fecha: c ? this.fechaLarga(c.fecha).toLowerCase() + ' a las ' + c.hora : ''
                };
            },

            async confirmarEliminar() {
                if (this.confirmar.cargando) return;
                this.confirmar.cargando = true;

                try {
                    const r = await fetch(`${URL_CITAS}/${this.confirmar.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const res = await r.json().catch(() => ({}));

                    if (!r.ok) {
                        this.confirmar.abierto = false;
                        this.confirmar.cargando = false;
                        notificar('error', res.message || 'No se pudo eliminar la cita.', 6000);
                        return;
                    }

                    this.confirmar.abierto = false;
                    this.openDetalle = false;
                    this.openCitaModal = false;
                    this.openDia = false;
                    toastYRecargar('success', res.message || 'Cita eliminada');
                } catch (e) {
                    this.confirmar.abierto = false;
                    this.confirmar.cargando = false;
                    notificar('error', 'No se pudo comunicar con el servidor');
                }
            }
        };
    }
</script>
@endsection