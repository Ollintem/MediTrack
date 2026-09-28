@extends('layouts.admin')

@section('content')
@php
    // ---------- Mes que se está viendo (con límites para evitar valores inválidos en la URL) ----------
    $currentYear  = max(2000, min(2100, (int) request('anio', date('Y'))));
    $currentMonth = max(1, min(12, (int) request('mes', date('m'))));

    $inicioMes    = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, 1)->startOfDay();
    $mesAnterior  = $inicioMes->copy()->subMonth();
    $mesSiguiente = $inicioMes->copy()->addMonth();

    $daysInMonth = $inicioMes->daysInMonth;
    $offset      = $inicioMes->dayOfWeekIso - 1;                       // la cuadrícula empieza en lunes
    $finales     = (7 - (($offset + $daysInMonth) % 7)) % 7;           // celdas para completar la última semana
    $diasMesAnterior = $mesAnterior->daysInMonth;

    $hoyStr       = date('Y-m-d');
    $esMesActual  = $currentYear == date('Y') && $currentMonth == date('n');
    $mesPrefijo   = sprintf('%04d-%02d', $currentYear, $currentMonth);

    // Dirección de la animación al cambiar de mes
    $ordinalActual = $currentYear * 12 + $currentMonth;
    $ordinalHoy    = (int) date('Y') * 12 + (int) date('m');
    $dirHoy        = $ordinalHoy >= $ordinalActual ? 'siguiente' : 'anterior';

    $urlAnterior  = request()->fullUrlWithQuery(['mes' => $mesAnterior->month,  'anio' => $mesAnterior->year]);
    $urlSiguiente = request()->fullUrlWithQuery(['mes' => $mesSiguiente->month, 'anio' => $mesSiguiente->year]);
    $urlHoy       = request()->fullUrlWithQuery(['mes' => (int) date('m'), 'anio' => (int) date('Y')]);

    $citas = collect($citas ?? []);
    $citasMes = $citas->filter(fn ($c) => \Carbon\Carbon::parse($c->fecha)->format('Y-m') === $mesPrefijo);

    // Catálogos para el modal
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

    $tarjetas = [
        // [título, clave, icono, color icono, color barra]
        ['Citas del mes', 'total',      'bi-calendar3',       'bg-teal-50 text-teal-600',       'bg-teal-500'],
        ['Pendientes',    'Pendiente',  'bi-hourglass-split', 'bg-amber-50 text-amber-600',     'bg-amber-400'],
        ['Confirmadas',   'Confirmada', 'bi-check2-circle',   'bg-emerald-50 text-emerald-600', 'bg-emerald-500'],
        ['Canceladas',    'Cancelada',  'bi-x-circle',        'bg-red-50 text-red-500',         'bg-red-500'],
    ];
@endphp

<div x-data="moduloCitas()" class="space-y-6 animate-fade-in">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white p-6 sm:p-7 shadow-xl shadow-teal-900/20">
        <div class="absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5 flotar-lento"></div>
        <div class="absolute right-40 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10 flotar-lento" style="animation-delay:-3s"></div>
        <svg class="absolute left-0 right-0 bottom-3 w-full h-10 opacity-20 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>

        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-[.2em] text-emerald-300">Agenda médica</p>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight mt-1">Calendario de citas</h1>
                <p class="text-xs text-teal-100/80 font-semibold mt-1" x-text="fechaLarga(hoy)"></p>

                <div class="flex flex-wrap gap-2 mt-4">
                    <span class="inline-flex items-center gap-2 bg-white/10 border border-white/15 rounded-full px-3 py-1.5 text-[11px] font-bold backdrop-blur-sm">
                        <i class="bi bi-calendar-check text-emerald-300"></i>
                        <span x-text="citasHoy.length === 1 ? '1 cita hoy' : citasHoy.length + ' citas hoy'"></span>
                    </span>
                    <template x-if="siguienteHoy">
                        <button type="button" @click="verCita(siguienteHoy.id)"
                                class="inline-flex items-center gap-2 bg-white text-teal-900 rounded-full pl-2 pr-3 py-1.5 text-[11px] font-bold shadow-lg shadow-teal-950/20 hover:-translate-y-0.5 transition-all cursor-pointer">
                            <span class="relative flex w-2 h-2">
                                <span class="absolute inline-flex w-full h-full rounded-full bg-amber-400 opacity-75 animate-ping"></span>
                                <span class="relative inline-flex w-2 h-2 rounded-full bg-amber-500"></span>
                            </span>
                            <span>Siguiente: <span class="font-black" x-text="siguienteHoy.paciente"></span></span>
                            <span class="text-teal-600" x-text="'· ' + etiquetaRelativa(siguienteHoy)"></span>
                        </button>
                    </template>
                    <span x-show="!siguienteHoy" class="inline-flex items-center gap-2 bg-white/10 border border-white/15 rounded-full px-3 py-1.5 text-[11px] font-bold text-teal-100">
                        <i class="bi bi-cup-hot"></i> No quedan citas por hoy
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2.5 lg:w-auto w-full">
                <label class="relative flex-1 sm:w-64">
                    <span class="sr-only">Buscar paciente</span>
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-teal-100/70 text-xs"></i>
                    <input x-ref="buscar" x-model.debounce.200ms="busqueda" type="search" placeholder="Buscar paciente o motivo…"
                           class="w-full bg-white/10 hover:bg-white/15 focus:bg-white focus:text-slate-800 border border-white/20 focus:border-white rounded-2xl pl-9 pr-10 py-2.5 text-xs font-semibold text-white placeholder-teal-100/60 outline-none transition-all">
                    <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-teal-100/60 border border-white/20 rounded px-1.5 py-0.5 hidden sm:block" x-show="!busqueda">/</kbd>
                </label>
                <button @click="nuevaCita()" type="button"
                        class="group inline-flex items-center justify-center gap-2 bg-white text-teal-800 hover:bg-emerald-50 text-xs font-black px-4 py-2.5 rounded-2xl shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 cursor-pointer">
                    <i class="bi bi-plus-lg text-sm transition-transform duration-300 group-hover:rotate-90"></i>
                    <span>Agendar nueva cita</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===================== RESUMEN (reacciona a los filtros) ===================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $colorIcono, $colorBarra])
            @php $inicial = $clave === 'total' ? $citasMes->count() : $citasMes->where('estado', $clave)->count(); @endphp
            <div class="tarjeta-stat group relative overflow-hidden bg-white rounded-2xl border border-slate-100 shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1 hover:border-slate-200"
                 style="animation-delay: {{ $i * 70 }}ms">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $titulo }}</p>
                        <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums"
                           x-data="contador()" x-effect="ir(resumen['{{ $clave }}'])" x-text="valor">{{ $inicial }}</p>
                    </div>
                    <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $colorIcono }}">
                        <i class="bi {{ $icono }}"></i>
                    </span>
                </div>
                <div class="mt-3 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full {{ $colorBarra }} transition-all duration-700 ease-out"
                         :style="'width:' + porcentaje('{{ $clave }}') + '%'"></div>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-1.5"
                   x-text="'{{ $clave }}' === 'total' ? (filtroDoctor ? 'Del doctor seleccionado' : 'Todos los doctores') : porcentaje('{{ $clave }}') + '% del mes'"></p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 items-start" :class="layoutPanel ? 'lg:grid-cols-[1fr_300px]' : 'lg:grid-cols-1'">

        {{-- ===================== CALENDARIO ===================== --}}
        <div class="aparece bg-white p-4 sm:p-6 rounded-3xl border border-slate-100 shadow-sm min-w-0" style="--d: 260ms">

            {{-- Barra de herramientas --}}
            <div class="flex items-center justify-between mb-5 flex-wrap gap-3"
                 x-data="{ openSelector: false, verAno: {{ $currentYear }}, base: @js(request()->url()) }">

                <div class="relative">
                    <button type="button" @click="openSelector = !openSelector; verAno = {{ $currentYear }};"
                            class="titulo-mes text-lg font-black text-slate-800 flex items-center gap-2 px-2.5 py-1.5 -ml-2.5 rounded-xl hover:bg-slate-50 transition-all cursor-pointer">
                        <span>{{ ucfirst($inicioMes->translatedFormat('F')) }}</span>
                        <span class="text-slate-300 font-bold">{{ $currentYear }}</span>
                        <i class="bi bi-chevron-down text-[10px] text-slate-400 transition-transform" :class="openSelector && 'rotate-180'"></i>
                    </button>

                    <div x-show="openSelector" @click.outside="openSelector = false" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-0 mt-2 bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-900/10 z-30 p-3 w-64 origin-top-left">
                        <div class="flex items-center justify-between mb-3">
                            <button type="button" @click="verAno--" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 cursor-pointer" aria-label="Año anterior">
                                <i class="bi bi-chevron-left text-[10px]"></i>
                            </button>
                            <span class="text-xs font-black text-slate-800" x-text="verAno"></span>
                            <button type="button" @click="verAno++" class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 cursor-pointer" aria-label="Año siguiente">
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
                        <a href="{{ $urlHoy }}" @click="recordarDireccion(@js($dirHoy))"
                           class="block text-center w-full mt-3 pt-3 border-t border-slate-100 text-[10px] font-black text-teal-700 hover:text-teal-800 uppercase tracking-wide">
                            Ir a hoy
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Vista mes / lista --}}
                    <div class="relative flex bg-slate-100 rounded-xl p-1 text-[11px] font-bold">
                        <span class="absolute top-1 bottom-1 w-[calc(50%-4px)] rounded-lg bg-white shadow-sm transition-all duration-300 ease-out"
                              :style="vista === 'mes' ? 'left:4px' : 'left:calc(50%)'"></span>
                        <button type="button" @click="cambiarVista('mes')" class="relative z-10 flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                                :class="vista === 'mes' ? 'text-teal-700' : 'text-slate-500 hover:text-slate-700'">
                            <i class="bi bi-grid-3x3-gap"></i> Mes
                        </button>
                        <button type="button" @click="cambiarVista('lista')" class="relative z-10 flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                                :class="vista === 'lista' ? 'text-teal-700' : 'text-slate-500 hover:text-slate-700'">
                            <i class="bi bi-list-ul"></i> Lista
                        </button>
                    </div>

                    <button type="button" @click="alternarPanel()"
                            :class="panelAbierto ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300'"
                            :title="panelAbierto ? 'Ocultar próximas citas' : 'Mostrar próximas citas'"
                            class="hidden lg:flex items-center gap-2 border rounded-xl px-3 py-2 text-[11px] font-bold transition-all cursor-pointer">
                        <i class="bi" :class="panelAbierto ? 'bi-layout-sidebar-inset-reverse' : 'bi-layout-sidebar-reverse'"></i>
                        <span>Próximas</span>
                        <span class="min-w-[18px] h-[18px] px-1 rounded-full bg-teal-600 text-white text-[9px] font-black flex items-center justify-center" x-text="proximas.length"></span>
                    </button>

                    <select x-model="filtroDoctor" x-show="personal.length" x-cloak aria-label="Filtrar por doctor"
                            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer max-w-[180px]">
                        <option value="">Todos los doctores</option>
                        <template x-for="p in personal" :key="p.id">
                            <option :value="p.id" x-text="p.nombre"></option>
                        </template>
                    </select>

                    <div class="flex items-center gap-1.5">
                        <a href="{{ $urlAnterior }}" @click="recordarDireccion('anterior')" title="Mes anterior (←)"
                           class="w-8 h-8 rounded-xl border border-slate-200 hover:bg-slate-50 hover:-translate-x-0.5 flex items-center justify-center text-slate-500 transition-all">
                            <i class="bi bi-chevron-left text-xs"></i>
                        </a>
                        @if ($esMesActual)
                            <span class="text-[11px] font-bold text-teal-700 bg-teal-50 px-3 py-1.5 rounded-xl">Hoy</span>
                        @else
                            <a href="{{ $urlHoy }}" @click="recordarDireccion(@js($dirHoy))" title="Ir al mes actual (T)"
                               class="text-[11px] font-bold text-slate-500 hover:text-teal-700 bg-slate-50 hover:bg-teal-50 px-3 py-1.5 rounded-xl transition-all">
                                Hoy
                            </a>
                        @endif
                        <a href="{{ $urlSiguiente }}" @click="recordarDireccion('siguiente')" title="Mes siguiente (→)"
                           class="w-8 h-8 rounded-xl border border-slate-200 hover:bg-slate-50 hover:translate-x-0.5 flex items-center justify-center text-slate-500 transition-all">
                            <i class="bi bi-chevron-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Aviso de filtros activos --}}
            <div x-show="hayFiltros" x-cloak x-transition.opacity
                 class="mb-4 flex items-center gap-2 flex-wrap text-[11px] font-bold bg-amber-50 border border-amber-100 text-amber-800 rounded-2xl px-3.5 py-2">
                <i class="bi bi-funnel-fill"></i>
                <span x-text="'Mostrando ' + totalVisibleMes + ' de ' + resumen.totalSinFiltro + ' citas del mes'"></span>
                <button type="button" @click="limpiarFiltros()" class="ml-auto text-amber-700 hover:text-amber-900 underline underline-offset-2 cursor-pointer">Quitar filtros</button>
            </div>

            {{-- ---------- VISTA MES ---------- --}}
            <div x-show="vista === 'mes'" :class="dirAnim === 'siguiente' ? 'grid-slide-derecha' : (dirAnim === 'anterior' ? 'grid-slide-izquierda' : '')">
                <div class="grid grid-cols-7 gap-1.5 sm:gap-2 text-center">
                    @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $idx => $dia)
                        <span class="text-[10px] font-black uppercase py-2 {{ $idx >= 5 ? 'text-slate-300' : 'text-slate-400' }}">{{ $dia }}</span>
                    @endforeach

                    {{-- Días del mes anterior --}}
                    @for ($i = 0; $i < $offset; $i++)
                        <a href="{{ $urlAnterior }}" @click="recordarDireccion('anterior')"
                           class="min-h-[64px] sm:min-h-[112px] p-2 rounded-2xl border border-dashed border-slate-100 text-left text-xs font-bold text-slate-300 hover:text-slate-400 hover:bg-slate-50/50 transition-all">
                            {{ $diasMesAnterior - $offset + 1 + $i }}
                        </a>
                    @endfor

                    @for ($dia = 1; $dia <= $daysInMonth; $dia++)
                        @php
                            $fechaDia      = sprintf('%s-%02d', $mesPrefijo, $dia);
                            $esHoy         = $fechaDia === $hoyStr;
                            $esPasado      = $fechaDia < $hoyStr;
                            $esFinDeSemana = (($offset + $dia - 1) % 7) >= 5;
                            $fondo = $esHoy ? 'border-teal-300 ring-1 ring-teal-200 bg-teal-50/40 hoy-pulso'
                                   : ($esPasado ? 'border-slate-100 bg-white'
                                   : 'border-slate-100 ' . ($esFinDeSemana ? 'bg-slate-100/50' : 'bg-slate-50/50'));
                        @endphp

                        <div role="button" tabindex="0"
                             @click="clicDia($event, '{{ $fechaDia }}', {{ $esPasado ? 'true' : 'false' }})"
                             @keydown.enter.prevent="clicDia($event, '{{ $fechaDia }}', {{ $esPasado ? 'true' : 'false' }})"
                             :aria-label="etiquetaDia('{{ $fechaDia }}')"
                             style="animation-delay: {{ min(($dia - 1) * 12, 300) }}ms"
                             class="celda-dia min-h-[64px] sm:min-h-[112px] p-1.5 sm:p-2 rounded-2xl border transition-all text-left flex flex-col gap-1.5 group relative overflow-hidden outline-none focus-visible:ring-2 focus-visible:ring-teal-500
                                    {{ $fondo }} {{ $esPasado ? 'cursor-default' : 'cursor-pointer hover:bg-teal-50/50 hover:border-teal-200 hover:shadow-md hover:shadow-teal-900/5 hover:-translate-y-0.5' }}"
                             :class="coincideBusqueda('{{ $fechaDia }}') && 'ring-2 ring-amber-300 border-amber-200'">

                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[11px] sm:text-xs font-black w-6 h-6 flex items-center justify-center rounded-full shrink-0 transition-all
                                             {{ $esHoy ? 'bg-teal-700 text-white shadow-[0_0_0_4px_rgba(15,118,110,0.15)]' : ($esPasado ? 'text-slate-300' : 'text-slate-600 group-hover:text-teal-700 group-hover:bg-teal-100') }}">
                                    {{ $dia }}
                                </span>
                                <span x-show="citasDia('{{ $fechaDia }}').length" x-cloak
                                      class="hidden sm:inline text-[9px] font-black text-slate-400 group-hover:hidden"
                                      x-text="citasDia('{{ $fechaDia }}').length"></span>
                                @unless ($esPasado)
                                    <span class="hidden sm:flex w-5 h-5 rounded-full bg-teal-600 text-white items-center justify-center opacity-0 scale-50 group-hover:opacity-100 group-hover:scale-100 transition-all duration-200 shadow-sm shadow-teal-600/30">
                                        <i class="bi bi-plus text-sm leading-none"></i>
                                    </span>
                                @endunless
                            </div>

                            {{-- Escritorio: tarjetitas de cita --}}
                            <div class="hidden sm:block space-y-1">
                                <template x-for="(c, i) in citasDia('{{ $fechaDia }}').slice(0, maxVisibles)" :key="c.id">
                                    <div @click.stop="verCita(c.id)"
                                         :class="[chipDe(c), bordeDe(c), c.estado === 'Cancelada' && 'line-through opacity-60']"
                                         :style="'--d:' + (220 + i * 70) + 'ms'"
                                         class="chip-cita border-l-[3px] text-[9px] font-bold px-1.5 py-1 rounded-lg truncate hover:scale-[1.04] hover:shadow-sm transition-all cursor-pointer"
                                         :title="c.paciente + ' · ' + c.hora + ' – ' + horaFin(c) + (c.motivo ? ' · ' + c.motivo : '')">
                                        <span class="opacity-70 tabular-nums" x-text="c.hora"></span>
                                        <span x-text="c.paciente"></span>
                                    </div>
                                </template>
                                <button type="button"
                                        x-show="citasDia('{{ $fechaDia }}').length > maxVisibles" x-cloak
                                        @click.stop="abrirDia('{{ $fechaDia }}')"
                                        class="text-[9px] font-black text-teal-600 hover:text-teal-800 hover:bg-teal-50 rounded-md px-1.5 py-0.5 cursor-pointer"
                                        x-text="'+' + (citasDia('{{ $fechaDia }}').length - maxVisibles) + ' más'"></button>
                            </div>

                            {{-- Móvil: puntos de color --}}
                            <div class="sm:hidden flex flex-wrap gap-0.5">
                                <template x-for="c in citasDia('{{ $fechaDia }}').slice(0, 6)" :key="c.id">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="puntoDe(c)"></span>
                                </template>
                            </div>

                            {{-- Barra de ocupación del día --}}
                            <div x-show="citasDia('{{ $fechaDia }}').length" x-cloak class="mt-auto h-1 rounded-full bg-slate-100 overflow-hidden" aria-hidden="true">
                                <div class="h-full rounded-full bg-gradient-to-r from-teal-400 to-emerald-400 transition-all duration-500"
                                     :style="'width:' + ocupacion('{{ $fechaDia }}') + '%'"></div>
                            </div>
                        </div>
                    @endfor

                    {{-- Días del mes siguiente --}}
                    @for ($i = 1; $i <= $finales; $i++)
                        <a href="{{ $urlSiguiente }}" @click="recordarDireccion('siguiente')"
                           class="min-h-[64px] sm:min-h-[112px] p-2 rounded-2xl border border-dashed border-slate-100 text-left text-xs font-bold text-slate-300 hover:text-slate-400 hover:bg-slate-50/50 transition-all">
                            {{ $i }}
                        </a>
                    @endfor
                </div>
            </div>

            {{-- ---------- VISTA LISTA ---------- --}}
            <div x-show="vista === 'lista'" x-cloak class="space-y-5">
                <template x-for="(g, gi) in agendaMes" :key="g.fecha">
                    <div class="aparece flex gap-3 sm:gap-4" :style="'--d:' + Math.min(gi * 60, 400) + 'ms'">
                        <div class="w-14 shrink-0 text-center pt-1">
                            <p class="text-[10px] font-black uppercase" :class="g.fecha === hoy ? 'text-teal-600' : 'text-slate-400'" x-text="diaSemanaCorto(g.fecha)"></p>
                            <p class="text-2xl font-black leading-none mt-0.5 w-11 h-11 mx-auto flex items-center justify-center rounded-2xl"
                               :class="g.fecha === hoy ? 'bg-teal-700 text-white shadow-lg shadow-teal-700/25' : (g.fecha < hoy ? 'text-slate-300' : 'text-slate-800')"
                               x-text="Number(g.fecha.slice(8))"></p>
                        </div>
                        <div class="flex-1 min-w-0 space-y-2 border-l-2 border-slate-100 pl-3 sm:pl-4 pb-1">
                            <template x-for="c in g.citas" :key="c.id">
                                <button type="button" @click="verCita(c.id)"
                                        class="w-full text-left flex items-center gap-3 p-3 rounded-2xl border border-slate-100 bg-white hover:border-teal-200 hover:shadow-md hover:shadow-teal-900/5 hover:translate-x-1 transition-all cursor-pointer"
                                        :class="c.estado === 'Cancelada' && 'opacity-60'">
                                    <span class="text-center shrink-0 w-12">
                                        <span class="block text-xs font-black text-slate-800 tabular-nums" x-text="c.hora"></span>
                                        <span class="block text-[9px] font-bold text-slate-400 tabular-nums" x-text="horaFin(c)"></span>
                                    </span>
                                    <span class="w-1 self-stretch rounded-full" :class="puntoDe(c)"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs font-black text-slate-800 truncate" :class="c.estado === 'Cancelada' && 'line-through'" x-text="c.paciente"></span>
                                        <span class="block text-[10px] font-semibold text-slate-400 truncate"
                                              x-text="nombreDe('personal', c.personal_id) + ' · ' + nombreDe('consultorios', c.consultorio_id)"></span>
                                    </span>
                                    <span class="hidden sm:inline text-[9px] font-black px-2 py-0.5 rounded-full shrink-0" :class="chipDe(c)" x-text="c.estado"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="agendaMes.length === 0" class="py-14 text-center">
                    <span class="flotar inline-flex w-16 h-16 rounded-3xl bg-teal-50 text-teal-500 items-center justify-center text-3xl mb-3">
                        <i class="bi bi-calendar2-heart"></i>
                    </span>
                    <p class="text-sm font-black text-slate-600" x-text="hayFiltros ? 'Ninguna cita coincide con los filtros' : 'No hay citas este mes'"></p>
                    <button type="button" @click="hayFiltros ? limpiarFiltros() : nuevaCita()"
                            class="mt-3 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer"
                            x-text="hayFiltros ? 'Quitar filtros' : '+ Agendar la primera'"></button>
                </div>
            </div>

            {{-- Leyenda / filtro por estado --}}
            <div class="flex flex-wrap items-center gap-x-2 gap-y-2 mt-6 pt-4 border-t border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wide mr-1">Estados</span>
                <template x-for="(p, nombre) in paleta" :key="nombre">
                    <button type="button" @click="alternarEstado(nombre)" :aria-pressed="!estadoOculto(nombre)"
                            :class="estadoOculto(nombre) ? 'opacity-40 line-through' : ''"
                            class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 px-2.5 py-1 rounded-full border border-slate-200 hover:border-teal-300 hover:bg-teal-50/40 transition-all cursor-pointer">
                        <span class="w-2 h-2 rounded-full" :class="p.dot"></span>
                        <span x-text="nombre"></span>
                    </button>
                </template>
                <span class="text-[10px] font-semibold text-slate-300 ml-auto hidden md:flex items-center gap-1.5">
                    Atajos:
                    <kbd class="atajo">←</kbd><kbd class="atajo">→</kbd> mes
                    <kbd class="atajo">T</kbd> hoy
                    <kbd class="atajo">N</kbd> nueva
                    <kbd class="atajo">V</kbd> vista
                    <kbd class="atajo">/</kbd> buscar
                </span>
            </div>
        </div>

        {{-- ===================== PRÓXIMAS CITAS ===================== --}}
        <aside x-show="panelAbierto" x-cloak class="aparece hidden lg:block lg:sticky lg:top-6" style="--d: 360ms"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0 translate-x-4"
               x-transition:enter-end="opacity-100 translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="opacity-100 translate-x-0"
               x-transition:leave-end="opacity-0 translate-x-4">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-clock-history"></i></span>
                    <h3 class="text-sm font-black text-slate-800">Próximas citas</h3>
                    <button type="button" @click="alternarPanel()" title="Ocultar panel"
                            class="ml-auto w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 flex items-center justify-center transition-all cursor-pointer">
                        <i class="bi bi-chevron-double-right text-xs"></i>
                    </button>
                </div>

                <div x-show="proximas.length === 0" x-cloak class="py-8 text-center">
                    <span class="flotar inline-flex w-14 h-14 rounded-2xl bg-teal-50 text-teal-500 items-center justify-center text-2xl mb-3">
                        <i class="bi bi-calendar2-check"></i>
                    </span>
                    <p class="text-xs font-black text-slate-500">Sin citas próximas</p>
                    <p class="text-[10px] font-medium text-slate-400 mt-0.5">Cuando agendes una, aparecerá aquí.</p>
                </div>

                {{-- Línea de tiempo --}}
                <div class="relative space-y-1">
                    <span x-show="proximas.length > 1" class="absolute left-[18px] top-4 bottom-4 w-px bg-slate-100"></span>
                    <template x-for="(c, i) in proximas" :key="c.id">
                        <button type="button" @click="verCita(c.id)"
                                :style="'--d:' + (i * 70) + 'ms'"
                                class="aparece relative w-full text-left flex items-center gap-3 p-2 rounded-2xl hover:bg-slate-50 transition-all cursor-pointer group">
                            <span class="relative z-10 w-9 h-9 rounded-xl flex items-center justify-center text-[10px] font-black shrink-0 ring-4 ring-white transition-transform group-hover:scale-110"
                                  :class="chipDe(c)" x-text="iniciales(c.paciente)"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-black text-slate-800 truncate" x-text="c.paciente"></span>
                                <span class="flex items-center gap-1.5 text-[10px] font-bold" :class="pronto(c) ? 'text-amber-600' : 'text-slate-400'">
                                    <span x-show="pronto(c)" class="latido w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                                    <span x-text="etiquetaRelativa(c)"></span>
                                </span>
                            </span>
                            <i class="bi bi-chevron-right text-[10px] text-slate-300 group-hover:text-teal-600 group-hover:translate-x-0.5 transition-all"></i>
                        </button>
                    </template>
                </div>
            </div>
        </aside>
    </div>

    {{-- ===================== MODAL: AGENDA DEL DÍA ===================== --}}
    <template x-teleport="body">
        <div x-show="openDia" x-cloak
             @keydown.escape.window="!confirmar.abierto && (openDia = false)"
             class="fixed inset-0 z-[99997] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-md max-h-[85vh] flex flex-col overflow-hidden" @click.outside="openDia = false"
                 x-show="openDia"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">
                <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white overflow-hidden shrink-0">
                    <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                    <div class="relative flex items-center gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300"
                               x-text="'Agenda del día · ' + citasDia(diaSeleccionado).length + (citasDia(diaSeleccionado).length === 1 ? ' cita' : ' citas')"></p>
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
                            <span class="text-center shrink-0 w-11">
                                <span class="block text-xs font-black text-slate-800 tabular-nums" x-text="c.hora"></span>
                                <span class="block text-[9px] font-bold text-slate-400 tabular-nums" x-text="horaFin(c)"></span>
                            </span>
                            <span class="w-1 self-stretch rounded-full" :class="puntoDe(c)"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-black text-slate-800 truncate" x-text="c.paciente"></span>
                                <span class="block text-[10px] font-semibold text-slate-400 truncate" x-text="nombreDe('personal', c.personal_id)"></span>
                            </span>
                            <span class="text-[9px] font-black px-2 py-0.5 rounded-full" :class="chipDe(c)" x-text="c.estado"></span>
                        </button>
                    </template>
                    <p x-show="citasDia(diaSeleccionado).length === 0" class="text-center text-xs font-bold text-slate-400 py-6">No hay citas este día.</p>
                </div>

                <div x-show="diaSeleccionado >= hoy" class="px-5 py-4 border-t border-slate-100 flex justify-end shrink-0">
                    <button type="button" @click="openDia = false; nuevaCita(diaSeleccionado)"
                            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="bi bi-plus-lg"></i> Nueva cita este día
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ===================== MODAL: DETALLE DE CITA ===================== --}}
    <template x-teleport="body">
        <div x-show="openDetalle" x-cloak
             @keydown.escape.window="!confirmar.abierto && (openDetalle = false)"
             class="fixed inset-0 z-[99998] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-md max-h-[92vh] overflow-y-auto" @click.outside="openDetalle = false"
                 x-show="openDetalle"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">
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
                                      :class="openDetalle && 'icono-pop'" x-text="iniciales(citaActual.paciente)"></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Paciente</p>
                                    <h3 class="text-base font-black tracking-tight truncate" x-text="citaActual.paciente"></h3>
                                    <span class="inline-block mt-1 text-[9px] font-black px-2 py-0.5 rounded-full transition-all" :class="chipDe(citaActual)" x-text="citaActual.estado"></span>
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

                                {{-- Cambio rápido de estado --}}
                                <div class="col-span-2 reveal-item" :class="openDetalle && 'reveal-on'" style="--d:6">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-2 flex items-center gap-2">
                                        Cambiar estado
                                        <span x-show="cambiandoEstado" class="w-3 h-3 rounded-full border-2 border-teal-500 border-t-transparent animate-spin"></span>
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="(p, nombre) in paleta" :key="nombre">
                                            <button type="button" @click="cambiarEstado(nombre)"
                                                    :disabled="cambiandoEstado || citaActual.estado === nombre"
                                                    :class="citaActual.estado === nombre ? p.chip + ' ring-2 ring-offset-1 ' + p.anillo : 'bg-white border border-slate-200 text-slate-500 hover:border-teal-300 hover:text-slate-700 hover:-translate-y-0.5'"
                                                    class="flex items-center gap-1.5 text-[10px] font-black px-2.5 py-1.5 rounded-full transition-all cursor-pointer disabled:cursor-default">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="p.dot"></span>
                                                <span x-text="nombre"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                                <button type="button" @click="eliminarCita(citaActual.id)"
                                        class="mr-auto px-3 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer" title="Eliminar cita">
                                    <i class="bi bi-trash-fill"></i><span class="hidden sm:inline">Eliminar</span>
                                </button>
                                <button type="button" @click="openDetalle = false"
                                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                                    Cerrar
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

    {{-- ===================== CONFIRMAR ELIMINACIÓN ===================== --}}
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
                <span class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-2xl mb-4" :class="confirmar.abierto && 'icono-pop'">
                    <i class="bi bi-trash3-fill"></i>
                </span>
                <h3 class="text-base font-black text-slate-800">¿Eliminar esta cita?</h3>
                <p class="text-xs font-medium text-slate-500 mt-2 leading-relaxed">
                    Se eliminará la cita de <strong class="text-slate-700" x-text="confirmar.paciente"></strong>
                    <span x-show="confirmar.fecha" x-text="'del ' + confirmar.fecha"></span>.
                    Esta acción no se puede deshacer.
                </p>
                <p class="text-[10px] font-semibold text-slate-400 mt-2">¿Solo no asistirá? Mejor cámbiala a <button type="button" @click="confirmar.abierto = false; cambiarEstado('Cancelada')" class="font-black text-rose-500 hover:underline cursor-pointer">Cancelada</button> y conserva el historial.</p>

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

    {{-- Modal crear / editar (usa: openCitaModal, modoEdicion, guardando, form, guardar(), pacientes, personal, consultorios) --}}
    @include('citas.modal')

</div>

<style>
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
    @keyframes flotarLento { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-14px, 10px); } }
    @keyframes ecg      { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
    @keyframes barraToast { from { width: 100%; } to { width: 0; } }

    .animate-fade-in { animation: fadeIn .35s cubic-bezier(.16, 1, .3, 1) backwards; }
    .aparece         { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat    { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .celda-dia       { animation: celdaIn .45s cubic-bezier(.16, 1, .3, 1) backwards; }
    .chip-cita       { animation: popIn .4s cubic-bezier(.34, 1.56, .64, 1) backwards; animation-delay: var(--d, 0ms); }
    .icono-pop       { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .grid-slide-derecha   { animation: slideDerecha .45s cubic-bezier(.16, 1, .3, 1); }
    .grid-slide-izquierda { animation: slideIzquierda .45s cubic-bezier(.16, 1, .3, 1); }
    .hoy-pulso   { animation: celdaIn .45s cubic-bezier(.16, 1, .3, 1) backwards, pulsoHoy 2.6s ease-in-out 1s infinite; }
    .latido      { animation: latido 1.4s ease-in-out infinite; }
    .flotar      { animation: flotar 3s ease-in-out infinite; }
    .flotar-lento { animation: flotarLento 9s ease-in-out infinite; }
    .ecg-linea   { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }

    .ripple-onda { position: absolute; border-radius: 9999px; background: rgba(13, 148, 136, .22); transform: scale(0); animation: ripple .6s ease-out forwards; pointer-events: none; }

    .reveal-item { opacity: 0; }
    .reveal-on   { animation: fadeUp .45s cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--d, 0) * 70ms); }

    .atajo { font: 800 9px/1 ui-monospace, monospace; color: #94a3b8; border: 1px solid #e2e8f0; border-bottom-width: 2px; border-radius: 5px; padding: 3px 5px; background: #fff; }

    @media (prefers-reduced-motion: reduce) {
        .animate-fade-in, .aparece, .tarjeta-stat, .celda-dia, .chip-cita, .icono-pop, .hoy-pulso,
        .grid-slide-derecha, .grid-slide-izquierda, .latido, .flotar, .flotar-lento, .ecg-linea, .reveal-on, .ripple-onda {
            animation: none !important;
        }
        .reveal-item { opacity: 1 !important; }
    }

    [x-cloak] { display: none !important; }
</style>

<script>
    const URL_CITAS = @js(url('citas'));
    const CSRF      = @js(csrf_token());
    const NAV       = { anterior: @js($urlAnterior), siguiente: @js($urlSiguiente), hoy: @js($urlHoy), dirHoy: @js($dirHoy), esMesActual: @js($esMesActual) };
    const MES       = @js($mesPrefijo);

    const sinAcentos = s => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const aMinutos   = h => { const [a, b] = String(h).split(':').map(Number); return a * 60 + b; };
    const movimientoReducido = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Contador animado. El estado interno vive fuera del objeto reactivo para que x-effect no se dispare en bucle.
    function contador() {
        let actual = 0, raf = null, primera = true;
        return {
            valor: 0,
            ir(meta) {
                meta = Number(meta) || 0;
                cancelAnimationFrame(raf);
                if (movimientoReducido() || meta === actual) { actual = meta; this.valor = meta; return; }
                const desde = actual, dur = primera ? 800 : 450, retraso = primera ? 300 : 0;
                primera = false;
                setTimeout(() => {
                    const t0 = performance.now();
                    const paso = t => {
                        const p = Math.min(1, (t - t0) / dur);
                        actual = Math.round(desde + (meta - desde) * (1 - Math.pow(1 - p, 3)));
                        this.valor = actual;
                        if (p < 1) raf = requestAnimationFrame(paso);
                    };
                    raf = requestAnimationFrame(paso);
                }, retraso);
            }
        };
    }

    function avisoCitas(icon, title, ms = 3500) {
        window.dispatchEvent(new CustomEvent('toast-cita', { detail: { icon, title, ms } }));
    }

    function toastYRecargar(icon, title) {
        try { sessionStorage.setItem('toast_citas', JSON.stringify({ icon, title })); } catch (e) {}
        location.reload();
    }

    function moduloCitas() {
        return {
            // ----- Datos -----
            citas: @json($citasJs),
            pacientes: @json($opPacientes),
            personal: @json($opPersonal),
            consultorios: @json($opConsultorios),
            hoy: @js($hoyStr),

            paleta: {
                'Pendiente':  { chip: 'bg-amber-50 text-amber-700',     dot: 'bg-amber-400',   borde: 'border-amber-400',   anillo: 'ring-amber-300' },
                'Confirmada': { chip: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500', borde: 'border-emerald-500', anillo: 'ring-emerald-300' },
                'En curso':   { chip: 'bg-sky-50 text-sky-700',         dot: 'bg-sky-500',     borde: 'border-sky-500',     anillo: 'ring-sky-300' },
                'Finalizada': { chip: 'bg-slate-100 text-slate-600',    dot: 'bg-slate-400',   borde: 'border-slate-400',   anillo: 'ring-slate-300' },
                'Cancelada':  { chip: 'bg-red-50 text-red-600',         dot: 'bg-red-500',     borde: 'border-red-500',     anillo: 'ring-red-300' }
            },

            // ----- Estado de la vista -----
            vista: 'mes',
            dirAnim: '',
            ahora: Date.now(),
            filtroDoctor: '',
            estadosOcultos: [],
            busqueda: '',
            mapa: {},            // { 'YYYY-MM-DD': [citas ordenadas] } — se recalcula solo cuando cambian los filtros
            maxDia: 1,

            // ----- Modales -----
            openCitaModal: false,
            modoEdicion: false,
            guardando: false,
            openDetalle: false,
            citaActual: null,
            cambiandoEstado: false,
            openDia: false,
            diaSeleccionado: '',
            form: {},

            // ----- Panel de próximas citas -----
            panelAbierto: true,
            layoutPanel: true,
            get maxVisibles() { return this.panelAbierto ? 2 : 3; },
            alternarPanel() {
                const abrir = !this.panelAbierto;
                this.panelAbierto = abrir;
                if (abrir) this.layoutPanel = true;
                else setTimeout(() => { if (!this.panelAbierto) this.layoutPanel = false; }, 180);
                try { localStorage.setItem('citas_panel_abierto', abrir ? '1' : '0'); } catch (e) {}
            },

            confirmar: { abierto: false, cargando: false, id: null, paciente: '', fecha: '' },

            // ----- Notificaciones -----
            toasts: [],
            toastId: 0,
            estilosToast: {
                success: { titulo: 'Listo',    bi: 'bi-check-lg',       icono: 'bg-emerald-50 text-emerald-600', barra: 'bg-emerald-400' },
                warning: { titulo: 'Atención', bi: 'bi-exclamation-lg', icono: 'bg-amber-50 text-amber-600',     barra: 'bg-amber-400' },
                error:   { titulo: 'Error',    bi: 'bi-x-lg',           icono: 'bg-rose-50 text-rose-600',       barra: 'bg-rose-400' },
                info:    { titulo: 'Aviso',    bi: 'bi-info-lg',        icono: 'bg-sky-50 text-sky-600',         barra: 'bg-sky-400' }
            },
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
            },

            init() {
                this.form = this.formNuevo();

                try {
                    const p = localStorage.getItem('citas_panel_abierto');
                    if (p !== null) this.panelAbierto = p === '1';
                    const v = localStorage.getItem('citas_vista');
                    this.vista = v || (window.matchMedia('(max-width: 639px)').matches ? 'lista' : 'mes');
                } catch (e) {}
                this.layoutPanel = this.panelAbierto;

                this.recalcular();
                this.$watch('filtroDoctor', () => this.recalcular());
                this.$watch('estadosOcultos', () => this.recalcular());
                this.$watch('busqueda', () => this.recalcular());

                setInterval(() => { this.ahora = Date.now(); }, 30000);

                try {
                    const dir = sessionStorage.getItem('citas_dir');
                    if (dir) { sessionStorage.removeItem('citas_dir'); this.dirAnim = dir; }
                } catch (e) {}

                window.addEventListener('toast-cita', e => this.agregarToast(e.detail));
                window.addEventListener('keydown', e => this.atajo(e));

                try {
                    const pendiente = sessionStorage.getItem('toast_citas');
                    if (pendiente) {
                        sessionStorage.removeItem('toast_citas');
                        const t = JSON.parse(pendiente);
                        this.$nextTick(() => avisoCitas(t.icon, t.title));
                    }
                } catch (e) {}
            },

            // ----- Atajos de teclado -----
            get hayModal() { return this.openCitaModal || this.openDetalle || this.openDia || this.confirmar.abierto; },
            atajo(e) {
                if (e.ctrlKey || e.metaKey || e.altKey || this.hayModal) return;
                const el = e.target;
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName) || el.isContentEditable) {
                    if (e.key === 'Escape' && el === this.$refs.buscar) { this.busqueda = ''; el.blur(); }
                    return;
                }
                const ir = (url, dir) => { this.recordarDireccion(dir); location.href = url; };
                switch (e.key) {
                    case 'ArrowLeft':  ir(NAV.anterior, 'anterior'); break;
                    case 'ArrowRight': ir(NAV.siguiente, 'siguiente'); break;
                    case 't': case 'T': if (!NAV.esMesActual) ir(NAV.hoy, NAV.dirHoy); break;
                    case 'n': case 'N': e.preventDefault(); this.nuevaCita(); break;
                    case 'v': case 'V': this.cambiarVista(this.vista === 'mes' ? 'lista' : 'mes'); break;
                    case '/': e.preventDefault(); this.$refs.buscar.focus(); break;
                }
            },

            cambiarVista(v) {
                this.vista = v;
                try { localStorage.setItem('citas_vista', v); } catch (e) {}
            },

            // ----- Filtros -----
            get citasFiltradas() {
                const q = sinAcentos(this.busqueda.trim());
                return this.citas.filter(c =>
                    !this.estadosOcultos.includes(c.estado) &&
                    (this.filtroDoctor === '' || String(c.personal_id) === String(this.filtroDoctor)) &&
                    (!q || sinAcentos(c.paciente + ' ' + c.motivo).includes(q))
                );
            },
            recalcular() {
                const m = {};
                for (const c of this.citasFiltradas) (m[c.fecha] = m[c.fecha] || []).push(c);
                let max = 1;
                for (const f in m) {
                    m[f].sort((a, b) => a.hora.localeCompare(b.hora));
                    if (f.startsWith(MES)) max = Math.max(max, m[f].length);
                }
                this.mapa = m;
                this.maxDia = max;
            },
            citasDia(fecha) { return this.mapa[fecha] || []; },
            ocupacion(fecha) { return Math.max(12, Math.round(this.citasDia(fecha).length / this.maxDia * 100)); },
            coincideBusqueda(fecha) { return this.busqueda.trim() !== '' && this.citasDia(fecha).length > 0; },

            get hayFiltros() { return this.filtroDoctor !== '' || this.estadosOcultos.length > 0 || this.busqueda.trim() !== ''; },
            get totalVisibleMes() {
                return Object.keys(this.mapa).filter(f => f.startsWith(MES)).reduce((n, f) => n + this.mapa[f].length, 0);
            },
            limpiarFiltros() { this.filtroDoctor = ''; this.estadosOcultos = []; this.busqueda = ''; },

            alternarEstado(nombre) {
                this.estadosOcultos = this.estadoOculto(nombre)
                    ? this.estadosOcultos.filter(e => e !== nombre)
                    : [...this.estadosOcultos, nombre];
            },
            estadoOculto(nombre) { return this.estadosOcultos.includes(nombre); },

            // Resumen del mes (respeta doctor y búsqueda, no los estados ocultos)
            get resumen() {
                const q = sinAcentos(this.busqueda.trim());
                const delMes = this.citas.filter(c => c.fecha.startsWith(MES));
                const lista = delMes.filter(c =>
                    (this.filtroDoctor === '' || String(c.personal_id) === String(this.filtroDoctor)) &&
                    (!q || sinAcentos(c.paciente + ' ' + c.motivo).includes(q)));
                const r = { total: lista.length, totalSinFiltro: delMes.length };
                for (const e of Object.keys(this.paleta)) r[e] = lista.filter(c => c.estado === e).length;
                return r;
            },
            porcentaje(clave) {
                if (clave === 'total') return this.resumen.totalSinFiltro ? Math.round(this.resumen.total / this.resumen.totalSinFiltro * 100) : 0;
                return this.resumen.total ? Math.round((this.resumen[clave] || 0) / this.resumen.total * 100) : 0;
            },

            get agendaMes() {
                return Object.keys(this.mapa).filter(f => f.startsWith(MES)).sort()
                    .map(f => ({ fecha: f, citas: this.mapa[f] }));
            },

            // ----- Hoy -----
            get citasHoy() {
                return this.citas.filter(c => c.fecha === this.hoy && c.estado !== 'Cancelada' &&
                    (this.filtroDoctor === '' || String(c.personal_id) === String(this.filtroDoctor)));
            },
            get siguienteHoy() {
                return this.citasHoy
                    .filter(c => ['Pendiente', 'Confirmada', 'En curso'].includes(c.estado) && this.minutosPara(c) > -Number(c.duracion_min || 30))
                    .sort((a, b) => a.hora.localeCompare(b.hora))[0] || null;
            },

            // ----- Formato -----
            chipDe(c)  { return (this.paleta[c.estado] || this.paleta['Pendiente']).chip; },
            puntoDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).dot; },
            bordeDe(c) { return (this.paleta[c.estado] || this.paleta['Pendiente']).borde; },

            minutosPara(c) { return Math.round((new Date(c.fecha + 'T' + c.hora).getTime() - this.ahora) / 60000); },
            pronto(c) { const m = this.minutosPara(c); return c.estado !== 'En curso' && m <= 60 && m > -30; },
            etiquetaRelativa(c) {
                if (c.estado === 'En curso') return 'En curso ahora';
                const m = this.minutosPara(c);
                if (m <= 0 && m > -30) return 'Ahora · ' + c.hora;
                if (m > 0 && m < 60) return 'En ' + m + ' min · ' + c.hora;
                if (c.fecha === this.hoy && m >= 60) return 'Hoy · ' + c.hora;
                return this.fechaCorta(c.fecha) + ' · ' + c.hora;
            },

            iniciales(nombre) { return (nombre || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase(); },
            nombreDe(lista, id) {
                const item = this[lista].find(x => String(x.id) === String(id));
                return item ? item.nombre : '—';
            },
            horaFin(c) {
                const total = aMinutos(c.hora) + Number(c.duracion_min || 30);
                return String(Math.floor(total / 60) % 24).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
            },
            fechaCorta(f) {
                if (f === this.hoy) return 'Hoy';
                return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' });
            },
            fechaLarga(f) {
                if (!f) return '';
                const txt = new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                return txt.charAt(0).toUpperCase() + txt.slice(1);
            },
            diaSemanaCorto(f) { return new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { weekday: 'short' }).replace('.', ''); },
            etiquetaDia(f) {
                const n = this.citasDia(f).length;
                return this.fechaLarga(f) + (n ? ', ' + n + (n === 1 ? ' cita' : ' citas') : ', sin citas');
            },

            // ----- Efectos -----
            recordarDireccion(dir) { try { sessionStorage.setItem('citas_dir', dir); } catch (e) {} },
            ripple(e) {
                const el = e.currentTarget;
                if (!el || movimientoReducido() || (!e.clientX && !e.clientY)) return;
                const r = el.getBoundingClientRect(), d = Math.max(r.width, r.height);
                const onda = document.createElement('span');
                onda.className = 'ripple-onda';
                onda.style.width = onda.style.height = d + 'px';
                onda.style.left = (e.clientX - r.left - d / 2) + 'px';
                onda.style.top  = (e.clientY - r.top - d / 2) + 'px';
                el.appendChild(onda);
                setTimeout(() => onda.remove(), 650);
            },

            // ----- Clic en un día -----
            clicDia(e, fecha, pasado) {
                const n = this.citasDia(fecha).length;
                const movil = window.matchMedia('(max-width: 639px)').matches;
                if (!pasado) this.ripple(e);
                if ((movil || pasado) && n) return this.abrirDia(fecha);
                if (pasado) return avisoCitas('info', 'No se pueden agendar citas en días pasados.');
                this.nuevaCita(fecha);
            },

            get proximas() {
                const desde = this.ahora - 30 * 60000;
                return this.citasFiltradas
                    .filter(c => ['Pendiente', 'Confirmada', 'En curso'].includes(c.estado) && new Date(c.fecha + 'T' + c.hora).getTime() >= desde)
                    .sort((a, b) => (a.fecha + a.hora).localeCompare(b.fecha + b.hora))
                    .slice(0, 6);
            },

            // ----- Modales -----
            abrirDia(fecha) { this.diaSeleccionado = fecha; this.openDia = true; },
            verCita(id) {
                const c = this.citas.find(x => x.id === id);
                if (!c) return;
                this.citaActual = { ...c };
                this.openDetalle = true;
            },
            formNuevo(fecha = null) {
                return {
                    id: null, motivo: '', paciente_id: '', personal_id: '', consultorio_id: '',
                    fecha: fecha || this.hoy, hora: '09:00', duracion_min: '30',
                    tipo_consulta: 'Primera Vez', estado: 'Pendiente'
                };
            },
            nuevaCita(fecha = null) {
                this.modoEdicion = false;
                this.form = this.formNuevo(fecha);
                this.openDia = false;
                this.openCitaModal = true;
            },
            editarCita(id) {
                const c = this.citas.find(x => x.id === id);
                if (!c) return;
                this.modoEdicion = true;
                this.form = {
                    id: c.id, motivo: c.motivo, paciente_id: c.paciente_id, personal_id: c.personal_id,
                    consultorio_id: c.consultorio_id, fecha: c.fecha, hora: c.hora,
                    duracion_min: String(c.duracion_min), tipo_consulta: c.tipo_consulta, estado: c.estado
                };
                this.openDetalle = false;
                this.openDia = false;
                this.openCitaModal = true;
            },

            // ----- Choques de horario (mismo doctor o mismo consultorio) -----
            buscarConflicto(f) {
                if (f.estado === 'Cancelada') return null;
                const ini = aMinutos(f.hora), fin = ini + Number(f.duracion_min || 30);
                const choque = this.citas.find(c =>
                    c.fecha === f.fecha && String(c.id) !== String(f.id) && c.estado !== 'Cancelada' &&
                    (String(c.personal_id) === String(f.personal_id) || String(c.consultorio_id) === String(f.consultorio_id)) &&
                    aMinutos(c.hora) < fin && ini < aMinutos(c.hora) + Number(c.duracion_min || 30));
                if (!choque) return null;
                const quien = String(choque.personal_id) === String(f.personal_id)
                    ? nombreSeguro(this.nombreDe('personal', f.personal_id), 'El doctor')
                    : 'El consultorio ' + this.nombreDe('consultorios', f.consultorio_id);
                return `${quien} ya tiene una cita con ${choque.paciente} de ${choque.hora} a ${this.horaFin(choque)}.`;
            },

            payloadDe(f) {
                return {
                    paciente_id: f.paciente_id, personal_id: f.personal_id, consultorio_id: f.consultorio_id,
                    fecha: f.fecha, hora: f.hora, duracion_min: f.duracion_min, tipo_consulta: f.tipo_consulta,
                    estado: f.estado, motivo: f.motivo
                };
            },

            async enviar(url, metodo, payload) {
                const r = await fetch(url, {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: payload ? JSON.stringify(payload) : undefined
                });
                const res = await r.json().catch(() => ({}));
                let mensaje = res.message;
                if (!r.ok && res.errors) mensaje = res.errors[Object.keys(res.errors)[0]][0];
                return { ok: r.ok, res, mensaje };
            },

            // ----- Guardar (crear o actualizar) -----
            async guardar() {
                if (this.guardando) return;
                const f = this.form;
                if (!f.paciente_id || !f.personal_id || !f.consultorio_id) return avisoCitas('warning', 'Selecciona el paciente, el médico y el consultorio.');
                if (!f.fecha || !f.hora) return avisoCitas('warning', 'Indica la fecha y la hora de la cita.');
                if (!String(f.motivo || '').trim()) return avisoCitas('warning', 'Escribe el motivo de la consulta.');
                if (!this.modoEdicion && new Date(f.fecha + 'T' + f.hora).getTime() < Date.now() - 5 * 60000) {
                    return avisoCitas('warning', 'La fecha y hora de la cita ya pasaron.');
                }
                const choque = this.buscarConflicto(f);
                if (choque) return avisoCitas('warning', choque, 6000);

                this.guardando = true;
                const editando = this.modoEdicion && f.id;
                try {
                    const { ok, res, mensaje } = await this.enviar(editando ? `${URL_CITAS}/${f.id}` : URL_CITAS, editando ? 'PUT' : 'POST', this.payloadDe(f));
                    if (!ok) {
                        avisoCitas('warning', mensaje || 'No se pudo guardar la cita.', 5000);
                        this.guardando = false;
                        return;
                    }
                    this.openCitaModal = false;
                    toastYRecargar('success', res.message || (editando ? 'Cita actualizada' : 'Cita agendada'));
                } catch (e) {
                    console.error('Error de red:', e);
                    avisoCitas('error', 'No se pudo comunicar con el servidor');
                    this.guardando = false;
                }
            },

            // ----- Cambio rápido de estado (sin recargar la página) -----
            async cambiarEstado(estado) {
                const c = this.citaActual && this.citas.find(x => x.id === this.citaActual.id);
                if (!c || this.cambiandoEstado || c.estado === estado) return;
                this.cambiandoEstado = true;
                try {
                    const { ok, mensaje } = await this.enviar(`${URL_CITAS}/${c.id}`, 'PUT', this.payloadDe({ ...c, duracion_min: String(c.duracion_min), estado }));
                    if (!ok) { avisoCitas('warning', mensaje || 'No se pudo cambiar el estado.', 5000); return; }
                    c.estado = estado;
                    this.citaActual.estado = estado;
                    this.recalcular();
                    avisoCitas('success', `La cita de ${c.paciente} ahora está "${estado}".`);
                } catch (e) {
                    avisoCitas('error', 'No se pudo comunicar con el servidor');
                } finally {
                    this.cambiandoEstado = false;
                }
            },

            // ----- Eliminar -----
            eliminarCita(id) {
                const c = this.citas.find(x => x.id === id);
                this.confirmar = {
                    abierto: true, cargando: false, id,
                    paciente: c ? c.paciente : 'este paciente',
                    fecha: c ? this.fechaLarga(c.fecha).toLowerCase() + ' a las ' + c.hora : ''
                };
            },
            async confirmarEliminar() {
                if (this.confirmar.cargando) return;
                this.confirmar.cargando = true;
                try {
                    const { ok, res, mensaje } = await this.enviar(`${URL_CITAS}/${this.confirmar.id}`, 'DELETE');
                    if (!ok) {
                        this.confirmar.abierto = false;
                        this.confirmar.cargando = false;
                        avisoCitas('error', mensaje || 'No se pudo eliminar la cita.', 6000);
                        return;
                    }
                    this.confirmar.abierto = false;
                    this.openDetalle = this.openCitaModal = this.openDia = false;
                    toastYRecargar('success', res.message || 'Cita eliminada');
                } catch (e) {
                    this.confirmar.abierto = false;
                    this.confirmar.cargando = false;
                    avisoCitas('error', 'No se pudo comunicar con el servidor');
                }
            }
        };
    }

    function nombreSeguro(nombre, respaldo) { return nombre && nombre !== '—' ? nombre : respaldo; }
</script>
@endsection