@extends('layouts.admin')

@section('content')
@php
    use Carbon\Carbon;

    // Mes/año actuales o los que vengan por query string (navegación) — evita hardcodear el mes.
    $currentYear  = (int) request('anio', date('Y'));
    $currentMonth = (int) request('mes', date('m'));

    $inicioMes   = Carbon::createFromDate($currentYear, $currentMonth, 1);
    $mesAnterior = $inicioMes->copy()->subMonth();
    $mesSiguiente = $inicioMes->copy()->addMonth();

    $daysInMonth = $inicioMes->daysInMonth;
    // Offset para que la cuadrícula inicie en Lunes (1 = Lunes ... 7 = Domingo).
    $offset = $inicioMes->dayOfWeekIso - 1;

    $citas = $citas ?? collect();
    $citasMes = $citas->filter(function ($c) use ($currentYear, $currentMonth) {
        return Carbon::parse($c->fecha)->year == $currentYear && Carbon::parse($c->fecha)->month == $currentMonth;
    });

    $coloresEstado = [
        'Pendiente'  => ['bg' => 'bg-amber-500',   'chip' => 'bg-amber-50 text-amber-700',   'dot' => 'bg-amber-400'],
        'Confirmada' => ['bg' => 'bg-emerald-600', 'chip' => 'bg-emerald-50 text-emerald-700','dot' => 'bg-emerald-500'],
        'En curso'   => ['bg' => 'bg-sky-600',     'chip' => 'bg-sky-50 text-sky-700',       'dot' => 'bg-sky-500'],
        'Finalizada' => ['bg' => 'bg-slate-400',   'chip' => 'bg-slate-100 text-slate-600',  'dot' => 'bg-slate-400'],
        'Cancelada'  => ['bg' => 'bg-red-500',     'chip' => 'bg-red-50 text-red-600',       'dot' => 'bg-red-500'],
    ];

    $mesesAbrev = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
@endphp

<div x-data="{
        openCitaModal: false,
        modoEdicion: false,
        form: { motivo: '', paciente_id: '', personal_id: '', consultorio_id: '', fecha: '{{ date('Y-m-d') }}', hora: '09:00', duracion_min: '30', tipo_consulta: 'Primera Vez', estado: 'Pendiente' },
        nuevaCita(fecha = null) {
            this.modoEdicion = false;
            this.form = { motivo: '', paciente_id: '', personal_id: '', consultorio_id: '', fecha: fecha || '{{ date('Y-m-d') }}', hora: '09:00', duracion_min: '30', tipo_consulta: 'Primera Vez', estado: 'Pendiente' };
            this.openCitaModal = true;
        }
    }"
    class="space-y-6 animate-fade-in">

    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Calendario de citas</h1>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Visualización general del calendario y programación de la clínica.</p>
        </div>
        <button @click="nuevaCita()" type="button"
                class="inline-flex items-center gap-2 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-md shadow-teal-700/20 transition-all transform hover:-translate-y-0.5 cursor-pointer">
            <i class="bi bi-plus-lg text-sm"></i>
            <span>Agendar nueva cita</span>
        </button>
    </div>

    <!-- Resumen rápido del mes -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-0.5" style="animation-delay: 0ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Citas del mes</p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $citasMes->count() }}</p>
        </div>
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-0.5" style="animation-delay: 60ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Pendientes
            </p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $citasMes->where('estado', 'Pendiente')->count() }}</p>
        </div>
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-0.5" style="animation-delay: 120ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Confirmadas
            </p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $citasMes->where('estado', 'Confirmada')->count() }}</p>
        </div>
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-0.5" style="animation-delay: 180ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Canceladas
            </p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $citasMes->where('estado', 'Cancelada')->count() }}</p>
        </div>
    </div>

    <!-- Contenedor principal del calendario -->
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
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
                               class="text-center text-[11px] font-bold py-2 rounded-xl transition-all"
                               :class="(verAno == {{ $currentYear }} && {{ $indice + 1 }} == {{ $currentMonth }}) ? 'bg-teal-700 text-white' : 'text-slate-600 hover:bg-slate-100'">
                                {{ $nombreMes }}
                            </a>
                        @endforeach
                    </div>

                    <a href="{{ request()->fullUrlWithQuery(['mes' => date('m'), 'anio' => date('Y')]) }}"
                       class="block text-center w-full mt-3 pt-3 border-t border-slate-100 text-[10px] font-black text-teal-700 hover:text-teal-800 uppercase tracking-wide">
                        Ir a hoy
                    </a>
                </div>
            </div>

            <!-- Navegación rápida de un mes -->
            <div class="flex items-center gap-1.5">
                <a href="{{ request()->fullUrlWithQuery(['mes' => $mesAnterior->month, 'anio' => $mesAnterior->year]) }}"
                   class="w-8 h-8 rounded-xl border border-slate-200 hover:bg-slate-50 hover:-translate-x-0.5 flex items-center justify-center text-slate-500 transition-all">
                    <i class="bi bi-chevron-left text-xs"></i>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['mes' => date('m'), 'anio' => date('Y')]) }}"
                   class="text-[11px] font-bold text-slate-500 hover:text-teal-700 bg-slate-50 hover:bg-teal-50 px-3 py-1.5 rounded-xl transition-all">
                    Hoy
                </a>
                <a href="{{ request()->fullUrlWithQuery(['mes' => $mesSiguiente->month, 'anio' => $mesSiguiente->year]) }}"
                   class="w-8 h-8 rounded-xl border border-slate-200 hover:bg-slate-50 hover:translate-x-0.5 flex items-center justify-center text-slate-500 transition-all">
                    <i class="bi bi-chevron-right text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Cuadrícula del calendario -->
        <div class="grid grid-cols-7 gap-2 text-center">
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
                    $citasDelDia = $citas->filter(fn ($c) => $c->fecha == $fechaDia)->sortBy('hora');
                    $esHoy = $fechaDia === date('Y-m-d');
                    $maxVisibles = 2;
                @endphp

                <div @click="nuevaCita('{{ $fechaDia }}')"
                     style="animation-delay: {{ min(($dia - 1) * 12, 300) }}ms"
                     class="celda-dia min-h-[104px] p-2 rounded-2xl border bg-slate-50/50 hover:bg-teal-50/40 hover:border-teal-200 hover:shadow-sm hover:-translate-y-0.5 transition-all text-left flex flex-col gap-1.5 group cursor-pointer relative overflow-hidden"
                     :class="'{{ $esHoy ? 'border-teal-300 ring-1 ring-teal-200 bg-teal-50/30' : 'border-slate-100' }}'">

                    <span class="text-xs font-black w-5 h-5 flex items-center justify-center rounded-full shrink-0 transition-all {{ $esHoy ? 'bg-teal-700 text-white shadow-[0_0_0_4px_rgba(15,118,110,0.15)]' : 'text-slate-500 group-hover:text-teal-700 group-hover:bg-teal-100' }}">
                        {{ $dia }}
                    </span>

                    <div class="space-y-1 overflow-y-auto">
                        @foreach ($citasDelDia->take($maxVisibles) as $c)
                            @php
                                $paleta = $coloresEstado[$c->estado] ?? $coloresEstado['Pendiente'];
                            @endphp
                            <div @click.stop="modoEdicion = true; openCitaModal = true; form = {
                                        motivo: '{{ addslashes($c->motivo ?? '') }}',
                                        paciente_id: '{{ $c->paciente_id }}',
                                        personal_id: '{{ $c->personal_id }}',
                                        consultorio_id: '{{ $c->consultorio_id }}',
                                        fecha: '{{ $c->fecha }}',
                                        hora: '{{ \Carbon\Carbon::parse($c->hora)->format('H:i') }}',
                                        duracion_min: '{{ $c->duracion_min ?? 30 }}',
                                        tipo_consulta: '{{ $c->tipo_consulta ?? 'Primera Vez' }}',
                                        estado: '{{ $c->estado ?? 'Pendiente' }}'
                                    }"
                                 class="{{ $paleta['chip'] }} text-[9px] font-bold px-1.5 py-1 rounded-lg truncate hover:opacity-80 transition-all"
                                 title="{{ $c->motivo }} · {{ $c->paciente->nombre ?? 'Paciente' }}">
                                <span class="opacity-70">{{ \Carbon\Carbon::parse($c->hora)->format('H:i') }}</span>
                                {{ $c->paciente->nombre ?? 'Cita' }}
                            </div>
                        @endforeach

                        @if ($citasDelDia->count() > $maxVisibles)
                            <p class="text-[9px] font-bold text-slate-400 px-1.5">+{{ $citasDelDia->count() - $maxVisibles }} más</p>
                        @endif
                    </div>
                </div>
            @endfor
        </div>

        <!-- Leyenda de estados -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-6 pt-4 border-t border-slate-100">
            @foreach ($coloresEstado as $estado => $paleta)
                <span class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500">
                    <span class="w-2 h-2 rounded-full {{ $paleta['dot'] }}"></span>
                    {{ $estado }}
                </span>
            @endforeach
        </div>
    </div>

    <!-- INCLUIMOS TU MODAL EXTERNO -->
    @include('citas.modal')

</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Entrada escalonada de las tarjetas de resumen */
    .tarjeta-stat {
        opacity: 0;
        animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Entrada escalonada de los días del calendario */
    .celda-dia {
        opacity: 0;
        animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .animate-fade-in, .tarjeta-stat, .celda-dia {
            animation: none !important;
            opacity: 1 !important;
        }
    }
</style>
@endsection