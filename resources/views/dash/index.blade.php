+@extends('layouts.admin')

@section('content')
@php
    $datosDashboard = $datos ?? [];
    $urlDatos = \Illuminate\Support\Facades\Route::has('home.datos') ? route('home.datos') : null;

    $rutaSegura = fn ($nombre) => \Illuminate\Support\Facades\Route::has($nombre) ? route($nombre) : null;

    $acciones = [
        ['texto' => 'Nueva cita',         'sub' => 'Agendar en el calendario', 'icono' => 'bi-calendar-plus-fill', 'url' => $rutaSegura('citas.index'),       'principal' => true],
        ['texto' => 'Registrar paciente', 'sub' => 'Alta de expediente',       'icono' => 'bi-person-plus-fill',   'url' => $rutaSegura('pacientes.index'),   'principal' => false],
        ['texto' => 'Generar factura',    'sub' => 'Cobro de consulta',        'icono' => 'bi-receipt',            'url' => $rutaSegura('facturacion.index'), 'principal' => false],
        ['texto' => 'Ver inventario',     'sub' => 'Medicamentos e insumos',   'icono' => 'bi-box-seam-fill',      'url' => $rutaSegura('inventario.index'),  'principal' => false],
    ];
@endphp

<div x-data="dashboardMeditrack(@js($datosDashboard), @js($urlDatos))" class="space-y-6">

    {{-- ===================== TÍTULO ===================== --}}
    <div class="dash-aparece flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div class="flex items-center gap-3.5">
            <div class="relative w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center shadow-md shadow-teal-600/25">
                <span class="absolute inset-0 rounded-2xl bg-teal-500 dash-onda"></span>
                <i class="bi bi-heart-pulse-fill text-xl relative dash-latido"></i>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-800">Panel general</h1>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    Resumen de la clínica en tiempo real
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 text-[11px] font-bold text-slate-400">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-slate-200">
                <span class="w-1.5 h-1.5 rounded-full" :class="error ? 'bg-rose-500' : 'bg-emerald-500'"></span>
                <span x-text="error ? 'Sin conexión' : 'Actualizado ' + d.generado"></span>
            </span>
            <button type="button" @click="refrescar(true)" :disabled="cargando || !url"
                    class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-teal-700 hover:border-teal-300 disabled:opacity-40 flex items-center justify-center transition"
                    title="Actualizar ahora">
                <i class="bi bi-arrow-clockwise" :class="cargando && 'animate-spin'"></i>
            </button>
        </div>
    </div>

    {{-- ===================== KPIs ===================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Pacientes de hoy --}}
        <div class="dash-aparece dash-tarjeta" style="animation-delay:60ms">
            <div class="flex items-start justify-between">
                <p class="dash-etiqueta">Pacientes de hoy</p>
                <span class="dash-icono bg-teal-600 text-white"><i class="bi bi-people-fill"></i></span>
            </div>
            <div class="flex items-end justify-between gap-3 mt-2">
                <div>
                    <p class="text-4xl font-black text-slate-800 tabular-nums" x-text="animado.pacientes"></p>
                    <template x-if="k.pacientesHoy.delta !== null">
                        <span class="dash-delta" :class="k.pacientesHoy.delta >= 0 ? 'text-emerald-700 bg-emerald-50' : 'text-rose-600 bg-rose-50'">
                            <i class="bi" :class="k.pacientesHoy.delta >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>
                            <span x-text="Math.abs(k.pacientesHoy.delta) + '% vs. semana pasada'"></span>
                        </span>
                    </template>
                    <template x-if="k.pacientesHoy.delta === null">
                        <span class="dash-delta text-slate-500 bg-slate-100">Sin datos de la semana pasada</span>
                    </template>
                </div>

                {{-- Mini barras de 7 días --}}
                <div class="flex items-end gap-1 h-12" aria-label="Últimos 7 días">
                    <template x-for="(p, i) in k.pacientesHoy.serie" :key="i">
                        <div class="flex flex-col items-center gap-1 group relative">
                            <div class="w-2.5 rounded-full transition-all duration-700"
                                 :class="i === k.pacientesHoy.serie.length - 1 ? 'bg-teal-600' : 'bg-teal-200 group-hover:bg-teal-400'"
                                 :style="`height:${Math.max(4, p.valor / maxSerie * 40)}px`"></div>
                            <span class="absolute -top-6 left-1/2 -translate-x-1/2 px-1.5 py-0.5 rounded-md bg-slate-800 text-white text-[9px] font-bold whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none"
                                  x-text="p.dia + ': ' + p.valor"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Citas pendientes --}}
        <div class="dash-aparece dash-tarjeta" style="animation-delay:120ms">
            <div class="flex items-start justify-between">
                <p class="dash-etiqueta">Citas por atender</p>
                <span class="dash-icono bg-amber-500 text-white"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <p class="text-4xl font-black text-slate-800 tabular-nums mt-2" x-text="animado.pendientes"></p>
            <div class="flex flex-wrap gap-1.5 mt-1">
                <span class="dash-delta text-amber-700 bg-amber-50"><i class="bi bi-calendar-day"></i> <span x-text="k.pendientes.hoy + ' hoy'"></span></span>
                <span class="dash-delta text-emerald-700 bg-emerald-50"><i class="bi bi-check2"></i> <span x-text="k.pendientes.confirmadas + ' confirmadas'"></span></span>
            </div>
        </div>

        {{-- Ingresos / consultas del mes --}}
        <div class="dash-aparece dash-tarjeta" style="animation-delay:180ms">
            <div class="flex items-start justify-between">
                <p class="dash-etiqueta" x-text="k.ingresos.modo === 'ingresos' ? 'Ingresos del mes' : 'Consultas atendidas del mes'"></p>
                <span class="dash-icono bg-sky-600 text-white">
                    <i class="bi" :class="k.ingresos.modo === 'ingresos' ? 'bi-wallet2' : 'bi-clipboard2-check-fill'"></i>
                </span>
            </div>
            <p class="text-4xl font-black text-slate-800 tabular-nums mt-2 truncate"
               x-text="k.ingresos.modo === 'ingresos' ? dinero(animado.ingresos) : animado.ingresos"></p>
            <template x-if="k.ingresos.delta !== null">
                <span class="dash-delta" :class="k.ingresos.delta >= 0 ? 'text-emerald-700 bg-emerald-50' : 'text-rose-600 bg-rose-50'">
                    <i class="bi" :class="k.ingresos.delta >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right'"></i>
                    <span x-text="Math.abs(k.ingresos.delta) + '% vs. mes anterior'"></span>
                </span>
            </template>
            <template x-if="k.ingresos.delta === null">
                <span class="dash-delta text-slate-500 bg-slate-100">Sin datos del mes anterior</span>
            </template>
        </div>

        {{-- Ocupación de consultorios --}}
        <div class="dash-aparece dash-tarjeta" style="animation-delay:240ms">
            <div class="flex items-start justify-between">
                <p class="dash-etiqueta">Ocupación de consultorios</p>
                <span class="dash-icono bg-violet-600 text-white"><i class="bi bi-hospital-fill"></i></span>
            </div>
            <p class="text-4xl font-black text-slate-800 tabular-nums mt-2"><span x-text="animado.ocupacion"></span><span class="text-2xl text-slate-400">%</span></p>
            <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full bg-violet-500 transition-all duration-1000" :style="`width:${k.ocupacion.porcentaje}%`"></div>
            </div>
            <p class="text-[11px] font-semibold text-slate-500 mt-2">
                <span class="text-violet-700 font-black" x-text="k.ocupacion.ocupados"></span> ocupados ·
                <span class="text-emerald-600 font-black" x-text="k.ocupacion.disponibles"></span> libres
                <template x-if="k.ocupacion.mantenimiento > 0">
                    <span> · <span class="text-amber-600 font-black" x-text="k.ocupacion.mantenimiento"></span> en mant.</span>
                </template>
            </p>
        </div>
    </div>

    {{-- ===================== CUERPO ===================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- ---------- COLUMNA IZQUIERDA ---------- --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Gráfica --}}
            <section class="dash-aparece dash-panel" style="animation-delay:300ms">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <div>
                        <h2 class="text-base font-black text-slate-800" x-text="vistaGrafica === 'ingresos' ? 'Ingresos mensuales' : 'Citas por mes'"></h2>
                        <p class="text-[11px] font-semibold text-slate-400">Últimos 6 meses</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div x-show="vistaGrafica === 'citas'" class="hidden md:flex items-center gap-3 text-[10px] font-bold text-slate-500">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-teal-600"></span>Atendidas</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-teal-200"></span>Programadas</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-rose-300"></span>Canceladas</span>
                        </div>
                        <div x-show="d.grafica.hayIngresos" class="flex p-1 rounded-xl bg-slate-100 text-[11px] font-bold">
                            <button type="button" @click="vistaGrafica = 'citas'"
                                    :class="vistaGrafica === 'citas' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'"
                                    class="px-3 py-1 rounded-lg transition">Citas</button>
                            <button type="button" @click="vistaGrafica = 'ingresos'"
                                    :class="vistaGrafica === 'ingresos' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'"
                                    class="px-3 py-1 rounded-lg transition">Ingresos</button>
                        </div>
                    </div>
                </div>

                <div class="relative h-60 pl-10">
                    {{-- Líneas guía --}}
                    <template x-for="n in [4,3,2,1,0]" :key="n">
                        <div class="absolute left-10 right-0 flex items-center" :style="`bottom:${n * 25}%`">
                            <span class="absolute -left-10 w-8 text-right text-[10px] font-semibold text-slate-400 -translate-y-1/2"
                                  x-text="vistaGrafica === 'ingresos' ? dineroCorto(escala * n / 4) : Math.round(escala * n / 4)"></span>
                            <div class="w-full border-t" :class="n === 0 ? 'border-slate-200' : 'border-dashed border-slate-100'"></div>
                        </div>
                    </template>

                    {{-- Barras --}}
                    <div class="absolute inset-0 left-10 flex items-end justify-around gap-2">
                        <template x-for="(m, i) in d.grafica.meses" :key="m.clave">
                            <div class="relative flex-1 max-w-[64px] h-full flex items-end justify-center group"
                                 @mouseenter="barraActiva = i" @mouseleave="barraActiva = null">

                                {{-- Tooltip --}}
                                <div x-show="barraActiva === i" x-cloak x-transition.opacity.duration.150ms
                                     class="absolute z-10 left-1/2 -translate-x-1/2 px-3 py-2 rounded-xl bg-slate-800 text-white text-[11px] shadow-xl whitespace-nowrap pointer-events-none"
                                     :style="`bottom: calc(${altura(m) }% + 10px)`">
                                    <p class="font-black mb-1" x-text="m.etiqueta + ' ' + m.anio"></p>
                                    <template x-if="vistaGrafica === 'citas'">
                                        <div class="space-y-0.5 font-semibold text-slate-200">
                                            <p><span class="inline-block w-2 h-2 rounded-sm bg-teal-400 mr-1"></span>Atendidas: <b x-text="m.finalizadas"></b></p>
                                            <p><span class="inline-block w-2 h-2 rounded-sm bg-teal-200 mr-1"></span>Programadas: <b x-text="m.activas"></b></p>
                                            <p><span class="inline-block w-2 h-2 rounded-sm bg-rose-300 mr-1"></span>Canceladas: <b x-text="m.canceladas"></b></p>
                                        </div>
                                    </template>
                                    <template x-if="vistaGrafica === 'ingresos'">
                                        <p class="font-semibold text-slate-200" x-text="dinero(m.ingresos)"></p>
                                    </template>
                                </div>

                                {{-- Barra apilada --}}
                                <div class="w-full rounded-t-xl overflow-hidden flex flex-col-reverse transition-all duration-700 ease-out"
                                     :class="barraActiva !== null && barraActiva !== i ? 'opacity-40' : ''"
                                     :style="`height:${montado ? altura(m) : 0}%`">
                                    <template x-if="vistaGrafica === 'citas'">
                                        <div class="h-full w-full flex flex-col-reverse">
                                            <div class="bg-teal-600" :style="`height:${parte(m, m.finalizadas)}%`"></div>
                                            <div class="bg-teal-200" :style="`height:${parte(m, m.activas)}%`"></div>
                                            <div class="bg-rose-300" :style="`height:${parte(m, m.canceladas)}%`"></div>
                                        </div>
                                    </template>
                                    <template x-if="vistaGrafica === 'ingresos'">
                                        <div class="h-full w-full bg-teal-600"></div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Sin datos --}}
                    <div x-show="escalaReal === 0" x-cloak class="absolute inset-0 left-10 flex items-center justify-center">
                        <p class="px-4 py-2 rounded-xl bg-white/90 border border-slate-200 text-xs font-bold text-slate-400">Aún no hay registros en estos meses</p>
                    </div>
                </div>

                {{-- Etiquetas de meses --}}
                <div class="flex justify-around gap-2 pl-10 mt-2">
                    <template x-for="(m, i) in d.grafica.meses" :key="'e' + m.clave">
                        <span class="flex-1 max-w-[64px] text-center text-[11px] font-bold"
                              :class="i === d.grafica.meses.length - 1 ? 'text-teal-700' : 'text-slate-400'"
                              x-text="m.etiqueta"></span>
                    </template>
                </div>
            </section>

            {{-- Agenda del día --}}
            <section class="dash-aparece dash-panel" style="animation-delay:360ms">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-800">Agenda de hoy</h2>
                        <p class="text-[11px] font-semibold text-slate-400">
                            <span x-text="d.resumenHoy.finalizadas"></span> de
                            <span x-text="d.resumenHoy.total - d.resumenHoy.canceladas"></span> citas atendidas
                        </p>
                    </div>
                    @if($rutaSegura('citas.index'))
                        <a href="{{ route('citas.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-teal-700 hover:text-teal-800 px-3 py-1.5 rounded-xl hover:bg-teal-50 transition">
                            Ver calendario <i class="bi bi-arrow-right"></i>
                        </a>
                    @endif
                </div>

                {{-- Avance del día --}}
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden flex mb-5" x-show="d.resumenHoy.total > 0">
                    <div class="bg-teal-600 transition-all duration-700" :style="`width:${pct(d.resumenHoy.finalizadas)}%`"></div>
                    <div class="bg-sky-400 transition-all duration-700" :style="`width:${pct(d.resumenHoy.enCurso)}%`"></div>
                    <div class="bg-rose-300 transition-all duration-700" :style="`width:${pct(d.resumenHoy.canceladas)}%`"></div>
                </div>

                {{-- Filtro rápido --}}
                <div class="flex flex-wrap gap-1.5 mb-4" x-show="d.agenda.length">
                    <template x-for="f in filtros" :key="f.clave">
                        <button type="button" @click="filtroAgenda = f.clave"
                                class="px-3 py-1 rounded-full text-[11px] font-bold border transition"
                                :class="filtroAgenda === f.clave ? 'bg-teal-600 border-teal-600 text-white' : 'bg-white border-slate-200 text-slate-500 hover:border-teal-300'">
                            <span x-text="f.texto"></span>
                            <span class="ml-1 opacity-70" x-text="contarFiltro(f.clave)"></span>
                        </button>
                    </template>
                </div>

                {{-- Lista --}}
                <div class="relative max-h-[420px] overflow-y-auto pr-1 -mr-1">
                    <template x-for="(c, i) in agendaFiltrada" :key="c.id">
                        <div>
                            {{-- Marca de "ahora" --}}
                            <template x-if="i === indiceAhora">
                                <div class="flex items-center gap-2 my-1.5">
                                    <span class="text-[10px] font-black text-rose-500 w-12 text-right" x-text="horaActual"></span>
                                    <span class="w-2 h-2 rounded-full bg-rose-500 ring-4 ring-rose-100"></span>
                                    <span class="flex-1 border-t-2 border-rose-200"></span>
                                </div>
                            </template>

                            <div class="flex items-stretch gap-3 group">
                                <div class="w-12 text-right pt-3 flex-shrink-0">
                                    <p class="text-sm font-black tabular-nums" :class="c.estado === 'Cancelada' ? 'text-slate-300 line-through' : 'text-slate-800'" x-text="c.hora"></p>
                                    <p class="text-[10px] font-semibold text-slate-400 tabular-nums" x-text="c.fin"></p>
                                </div>

                                <div class="relative flex flex-col items-center flex-shrink-0">
                                    <span class="mt-4 w-3 h-3 rounded-full ring-4 ring-white" :class="estilo(c.estado).punto"></span>
                                    <span class="flex-1 w-px bg-slate-200" x-show="i < agendaFiltrada.length - 1"></span>
                                </div>

                                <div class="flex-1 min-w-0 mb-2 p-3 rounded-2xl border transition group-hover:shadow-sm"
                                     :class="c.estado === 'En curso' ? 'border-sky-200 bg-sky-50/60' : 'border-slate-100 bg-slate-50/60 group-hover:bg-white group-hover:border-teal-200'">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-black text-slate-800 truncate" :class="c.estado === 'Cancelada' && 'line-through text-slate-400'" x-text="c.paciente"></p>
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full" :class="estilo(c.estado).chip" x-text="c.estado"></span>
                                        <span x-show="c.tipo" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-500" x-text="c.tipo"></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-[11px] font-semibold text-slate-500">
                                        <span class="flex items-center gap-1"><i class="bi bi-person-badge text-teal-600"></i><span x-text="c.doctor"></span></span>
                                        <span x-show="c.consultorio" class="flex items-center gap-1"><i class="bi bi-door-open text-teal-600"></i><span x-text="c.consultorio"></span></span>
                                        <span x-show="c.motivo" class="flex items-center gap-1 min-w-0"><i class="bi bi-chat-left-text text-teal-600"></i><span class="truncate max-w-[220px]" x-text="c.motivo"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- "Ahora" al final si ya pasaron todas --}}
                    <template x-if="agendaFiltrada.length && indiceAhora === agendaFiltrada.length">
                        <div class="flex items-center gap-2 my-1.5">
                            <span class="text-[10px] font-black text-rose-500 w-12 text-right" x-text="horaActual"></span>
                            <span class="w-2 h-2 rounded-full bg-rose-500 ring-4 ring-rose-100"></span>
                            <span class="flex-1 border-t-2 border-rose-200"></span>
                        </div>
                    </template>

                    {{-- Vacío --}}
                    <div x-show="!agendaFiltrada.length" class="py-10 text-center">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-teal-50 text-teal-500 flex items-center justify-center mb-3">
                            <i class="bi bi-calendar2-check text-2xl"></i>
                        </div>
                        <p class="text-sm font-black text-slate-600" x-text="d.agenda.length ? 'No hay citas con este filtro' : 'No hay citas para hoy'"></p>
                        <p class="text-xs text-slate-400 mt-1">Las citas agendadas aparecerán aquí automáticamente.</p>
                    </div>
                </div>
            </section>
        </div>

        {{-- ---------- COLUMNA DERECHA ---------- --}}
        <div class="space-y-6">

            {{-- Acciones rápidas --}}
            <section class="dash-aparece dash-panel" style="animation-delay:420ms">
                <h2 class="text-base font-black text-slate-800 mb-4">Acciones rápidas</h2>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($acciones as $a)
                        @if($a['url'])
                            <a href="{{ $a['url'] }}"
                               class="group relative p-4 rounded-2xl border transition-all duration-200 hover:-translate-y-0.5
                                      {{ $a['principal'] ? 'bg-teal-600 border-teal-600 text-white hover:bg-teal-700 shadow-md shadow-teal-600/20' : 'bg-white border-slate-200 text-slate-700 hover:border-teal-300 hover:shadow-md hover:shadow-teal-900/5' }}">
                                <span class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 transition-transform group-hover:scale-110
                                             {{ $a['principal'] ? 'bg-white/20 text-white' : 'bg-teal-50 text-teal-600' }}">
                                    <i class="bi {{ $a['icono'] }} text-lg"></i>
                                </span>
                                <p class="text-xs font-black leading-tight">{{ $a['texto'] }}</p>
                                <p class="text-[10px] font-semibold mt-0.5 {{ $a['principal'] ? 'text-teal-100' : 'text-slate-400' }}">{{ $a['sub'] }}</p>
                                <i class="bi bi-arrow-up-right absolute top-3 right-3 text-xs opacity-0 group-hover:opacity-70 transition"></i>
                            </a>
                        @else
                            <div class="relative p-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-slate-400 cursor-not-allowed" title="Módulo aún no disponible">
                                <span class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 bg-slate-100">
                                    <i class="bi {{ $a['icono'] }} text-lg"></i>
                                </span>
                                <p class="text-xs font-black leading-tight">{{ $a['texto'] }}</p>
                                <p class="text-[10px] font-semibold mt-0.5">Próximamente</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>

            {{-- Estado de consultorios --}}
            <section class="dash-aparece dash-panel" style="animation-delay:480ms">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-black text-slate-800">Consultorios</h2>
                    <span class="text-[11px] font-bold text-slate-400" x-text="k.ocupacion.total + ' en total'"></span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-2xl bg-emerald-50 p-3">
                        <p class="text-2xl font-black text-emerald-700" x-text="k.ocupacion.disponibles"></p>
                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide">Libres</p>
                    </div>
                    <div class="rounded-2xl bg-violet-50 p-3">
                        <p class="text-2xl font-black text-violet-700" x-text="k.ocupacion.ocupados"></p>
                        <p class="text-[10px] font-bold text-violet-600 uppercase tracking-wide">Ocupados</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 p-3">
                        <p class="text-2xl font-black text-amber-700" x-text="k.ocupacion.mantenimiento"></p>
                        <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wide">Mant.</p>
                    </div>
                </div>
            </section>

            {{-- Alerta de stock --}}
            <section class="dash-aparece rounded-3xl border p-5 transition-colors" style="animation-delay:540ms"
                     :class="d.stock.total > 0 ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-200'">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="flex items-center gap-2 text-base font-black" :class="d.stock.total > 0 ? 'text-amber-800' : 'text-slate-800'">
                        <i class="bi" :class="d.stock.total > 0 ? 'bi-exclamation-triangle-fill text-amber-500' : 'bi-shield-check text-emerald-500'"></i>
                        <span x-text="d.stock.total > 0 ? 'Alerta de stock' : 'Inventario en orden'"></span>
                    </h2>
                    <span x-show="d.stock.total > 0" class="text-[11px] font-black px-2 py-0.5 rounded-full bg-amber-500 text-white" x-text="d.stock.total"></span>
                </div>

                <template x-if="d.stock.total > 0">
                    <div class="space-y-3">
                        <template x-for="(p, i) in d.stock.items" :key="i">
                            <div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-amber-900 truncate pr-2" x-text="p.nombre"></span>
                                    <span class="font-black tabular-nums" :class="p.disponible === 0 ? 'text-rose-600' : 'text-amber-700'">
                                        <span x-text="p.disponible"></span><span class="text-amber-500 font-semibold" x-text="' / ' + p.minimo"></span>
                                    </span>
                                </div>
                                <div class="mt-1 h-1.5 rounded-full bg-amber-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700" :class="p.disponible === 0 ? 'bg-rose-500' : 'bg-amber-500'"
                                         :style="`width:${Math.min(100, p.minimo ? p.disponible / p.minimo * 100 : 0)}%`"></div>
                                </div>
                            </div>
                        </template>
                        <p x-show="d.stock.total > d.stock.items.length" class="text-[11px] font-semibold text-amber-700"
                           x-text="'y ' + (d.stock.total - d.stock.items.length) + ' productos más bajo el mínimo'"></p>
                    </div>
                </template>

                <p x-show="d.stock.total === 0" class="text-xs font-semibold text-slate-500"
                   x-text="d.stock.disponible ? 'Ningún producto está por debajo de su stock mínimo.' : 'No se encontró el modelo de inventario para revisar el stock.'"></p>

                @if($rutaSegura('inventario.index'))
                    <a href="{{ route('inventario.index') }}"
                       class="mt-4 inline-flex items-center gap-1 text-xs font-bold transition"
                       :class="d.stock.total > 0 ? 'text-amber-800 hover:text-amber-900' : 'text-teal-700 hover:text-teal-800'">
                        Ir a inventario <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </section>
        </div>
    </div>
</div>

<style>
    .dash-tarjeta {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 1.5rem;
        padding: 1.25rem;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .dash-tarjeta:hover {
        transform: translateY(-2px);
        border-color: rgb(153 246 228);
        box-shadow: 0 12px 30px -12px rgba(15, 118, 110, .18);
    }
    .dash-panel {
        background: #fff;
        border: 1px solid rgb(226 232 240);
        border-radius: 1.5rem;
        padding: 1.5rem;
    }
    .dash-etiqueta { font-size: .78rem; font-weight: 800; color: rgb(100 116 139); }
    .dash-icono {
        width: 2.5rem; height: 2.5rem; border-radius: .9rem;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    }
    .dash-delta {
        display: inline-flex; align-items: center; gap: .25rem;
        margin-top: .35rem; padding: .2rem .55rem; border-radius: 999px;
        font-size: .68rem; font-weight: 800;
    }

    .dash-aparece { animation: dashAparece .5s cubic-bezier(.2,.8,.2,1) both; }
    @keyframes dashAparece {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .dash-latido { display: inline-block; animation: dashLatido 1.4s ease-in-out infinite; }
    @keyframes dashLatido {
        0%, 100% { transform: scale(1); }
        14%      { transform: scale(1.2); }
        28%      { transform: scale(1); }
        42%      { transform: scale(1.12); }
        70%      { transform: scale(1); }
    }
    .dash-onda { animation: dashOnda 1.4s ease-out infinite; }
    @keyframes dashOnda {
        0%   { transform: scale(1);   opacity: .5; }
        100% { transform: scale(1.45); opacity: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .dash-aparece, .dash-latido, .dash-onda { animation: none !important; }
    }
</style>

<script>
    function dashboardMeditrack(datosIniciales, urlDatos) {
        const vacio = {
            generado: '--:--',
            kpis: {
                pacientesHoy: { valor: 0, anterior: 0, delta: null, serie: [] },
                pendientes:   { valor: 0, hoy: 0, confirmadas: 0 },
                ingresos:     { modo: 'consultas', valor: 0, anterior: 0, delta: null },
                ocupacion:    { total: 0, ocupados: 0, disponibles: 0, mantenimiento: 0, porcentaje: 0 },
            },
            grafica: { meses: [], hayIngresos: false },
            agenda: [],
            resumenHoy: { total: 0, finalizadas: 0, enCurso: 0, canceladas: 0 },
            stock: { disponible: false, total: 0, items: [] },
        };

        const fusionar = (base, extra) => {
            const r = { ...base, ...(extra || {}) };
            r.kpis = { ...base.kpis, ...((extra || {}).kpis || {}) };
            return r;
        };

        const estilos = {
            'Pendiente':  { chip: 'bg-amber-50 text-amber-700',     punto: 'bg-amber-400' },
            'Confirmada': { chip: 'bg-emerald-50 text-emerald-700', punto: 'bg-emerald-500' },
            'En curso':   { chip: 'bg-sky-100 text-sky-700',        punto: 'bg-sky-500' },
            'Finalizada': { chip: 'bg-slate-100 text-slate-600',    punto: 'bg-slate-400' },
            'Cancelada':  { chip: 'bg-rose-50 text-rose-600',       punto: 'bg-rose-400' },
        };

        return {
            d: fusionar(vacio, datosIniciales),
            url: urlDatos,
            cargando: false,
            error: false,
            montado: false,
            barraActiva: null,
            vistaGrafica: 'citas',
            filtroAgenda: 'todas',
            ahora: new Date(),
            animado: { pacientes: 0, pendientes: 0, ingresos: 0, ocupacion: 0 },

            filtros: [
                { clave: 'todas',     texto: 'Todas' },
                { clave: 'porAtender', texto: 'Por atender' },
                { clave: 'En curso',  texto: 'En curso' },
                { clave: 'Finalizada', texto: 'Atendidas' },
                { clave: 'Cancelada', texto: 'Canceladas' },
            ],

            get k() { return this.d.kpis; },

            init() {
                requestAnimationFrame(() => { this.montado = true; });
                this.animarNumeros();

                // Reloj para la marca de "ahora"
                setInterval(() => { this.ahora = new Date(); }, 30000);

                // Actualización automática cada minuto (solo con la pestaña visible)
                if (this.url) {
                    setInterval(() => { if (!document.hidden) this.refrescar(false); }, 60000);
                    document.addEventListener('visibilitychange', () => { if (!document.hidden) this.refrescar(false); });
                }
            },

            async refrescar(manual) {
                if (!this.url || this.cargando) return;
                this.cargando = true;
                try {
                    const r = await fetch(this.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    this.d = fusionar(vacio, await r.json());
                    this.error = false;
                    this.animarNumeros();
                    if (manual && window.notificar) window.notificar('Panel actualizado', 'success');
                } catch (e) {
                    console.error('No se pudo actualizar el dashboard:', e);
                    this.error = true;
                    if (manual && window.notificar) window.notificar('No se pudo actualizar el panel', 'error');
                } finally {
                    this.cargando = false;
                }
            },

            // ----- Contadores animados -----
            animarNumeros() {
                const destino = {
                    pacientes: this.k.pacientesHoy.valor,
                    pendientes: this.k.pendientes.valor,
                    ingresos: this.k.ingresos.valor,
                    ocupacion: this.k.ocupacion.porcentaje,
                };
                const inicio = { ...this.animado };
                const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (reducido) { this.animado = destino; return; }

                const t0 = performance.now(), dur = 900;
                const paso = (t) => {
                    const p = Math.min(1, (t - t0) / dur);
                    const e = 1 - Math.pow(1 - p, 3);
                    const nuevo = {};
                    for (const c in destino) {
                        const v = inicio[c] + (destino[c] - inicio[c]) * e;
                        nuevo[c] = c === 'ingresos' && this.k.ingresos.modo === 'ingresos' ? v : Math.round(v);
                    }
                    this.animado = nuevo;
                    if (p < 1) requestAnimationFrame(paso);
                };
                requestAnimationFrame(paso);
            },

            // ----- Gráfica -----
            get maxSerie() { return Math.max(1, ...this.k.pacientesHoy.serie.map(p => p.valor)); },

            valorBarra(m) { return this.vistaGrafica === 'ingresos' ? Number(m.ingresos || 0) : Number(m.total || 0); },

            get escalaReal() { return Math.max(0, ...this.d.grafica.meses.map(m => this.valorBarra(m))); },

            get escala() {
                const v = this.escalaReal;
                if (v <= 0) return 4;
                const bruto = v / 4;
                const pot = Math.pow(10, Math.floor(Math.log10(bruto)));
                const n = bruto / pot;
                const paso = (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pot;
                return Math.max(4, Math.ceil(paso) * 4);
            },

            altura(m) { return this.valorBarra(m) / this.escala * 100; },
            parte(m, n) { return m.total ? n / m.total * 100 : 0; },

            // ----- Agenda -----
            estilo(e) { return estilos[e] || estilos['Pendiente']; },

            coincide(c, f) {
                if (f === 'todas') return true;
                if (f === 'porAtender') return c.estado === 'Pendiente' || c.estado === 'Confirmada';
                return c.estado === f;
            },
            contarFiltro(f) { return this.d.agenda.filter(c => this.coincide(c, f)).length; },
            get agendaFiltrada() { return this.d.agenda.filter(c => this.coincide(c, this.filtroAgenda)); },

            get horaActual() {
                return String(this.ahora.getHours()).padStart(2, '0') + ':' + String(this.ahora.getMinutes()).padStart(2, '0');
            },
            get indiceAhora() {
                const i = this.agendaFiltrada.findIndex(c => c.hora > this.horaActual);
                return i === -1 ? this.agendaFiltrada.length : i;
            },
            pct(n) { return this.d.resumenHoy.total ? n / this.d.resumenHoy.total * 100 : 0; },

            // ----- Formatos -----
            dinero(v) {
                return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(v || 0);
            },
            dineroCorto(v) {
                if (v >= 1000000) return '$' + (v / 1000000).toFixed(1).replace('.0', '') + 'M';
                if (v >= 1000) return '$' + Math.round(v / 1000) + 'k';
                return '$' + Math.round(v);
            },
        };
    }
</script>
@endsection