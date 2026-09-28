@extends('layouts.admin')

@section('content')
@php
    $listaConsultorios = collect($consultorios ?? []);

    // Estados reales de la BD: enum('Disponible', 'Ocupado', 'Mantenimiento')
    $estadosCfg = [
        'disponible'    => ['texto' => 'Disponible',    'badge' => 'bg-emerald-50 text-emerald-600 border border-emerald-200'],
        'ocupado'       => ['texto' => 'Ocupado',       'badge' => 'bg-rose-50 text-rose-600 border border-rose-200'],
        'mantenimiento' => ['texto' => 'Mantenimiento', 'badge' => 'bg-amber-50 text-amber-600 border border-amber-200'],
        'desconocido'   => ['texto' => 'Sin definir',   'badge' => 'bg-slate-100 text-slate-500 border border-slate-200'],
    ];
    $slugEstado = function ($c) {
        $v = mb_strtolower(trim((string) $c->estado));
        return in_array($v, ['disponible', 'ocupado', 'mantenimiento'], true) ? $v : 'desconocido';
    };

    $totalConsultorios = $listaConsultorios->count();
    $pisosDisponibles = $listaConsultorios->pluck('piso')
        ->filter(fn ($p) => $p !== null && $p !== '')->unique()->sort(SORT_NATURAL)->values();

    // Posición inicial (si aún no tienen pos_x / pos_y guardadas): cuadrícula automática dentro de cada piso
    $maxPorPiso = $listaConsultorios->groupBy(fn ($c) => (string) ($c->piso ?? ''))->map->count()->max() ?? 1;
    $columnasPlano = max(2, min(5, (int) ceil(sqrt(max($maxPorPiso, 1)))));
    $contadorPiso = [];
    $datosPlano = $listaConsultorios->values()->map(function ($c) use (&$contadorPiso, $columnasPlano, $slugEstado) {
        $piso = (string) ($c->piso ?? '');
        $i = $contadorPiso[$piso] = ($contadorPiso[$piso] ?? -1) + 1;

        return [
            'id'         => $c->id,
            'clinica_id' => $c->clinica_id,
            'nombre'     => $c->nombre,
            'piso'       => $piso,
            'estado'     => $slugEstado($c),
            'creado'     => $c->created_at ? \Illuminate\Support\Carbon::parse($c->created_at)->format('d/m/Y') : null,
            'guardado'   => $c->pos_x !== null && $c->pos_y !== null,
            'x'          => (int) ($c->pos_x ?? ($i % $columnasPlano) * 120 + 20),
            'y'          => (int) ($c->pos_y ?? intdiv($i, $columnasPlano) * 120 + 20),
            'ocupadoPor' => null,
        ];
    });

    $tarjetas = [
        // [título, clave, icono, color icono, color barra]
        ['Total',         'total',         'bi-building',  'bg-teal-50 text-teal-600',       'bg-teal-500'],
        ['Disponibles',   'disponible',    'bi-door-open', 'bg-emerald-50 text-emerald-600', 'bg-emerald-500'],
        ['Ocupados',      'ocupado',       'bi-activity',  'bg-rose-50 text-rose-500',       'bg-rose-400'],
        ['Mantenimiento', 'mantenimiento', 'bi-tools',     'bg-amber-50 text-amber-600',     'bg-amber-400'],
    ];
@endphp

<div class="py-6 space-y-6" x-data="moduloConsultorios()">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-56 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        {{-- Silueta de edificio --}}
        <svg class="absolute right-6 bottom-0 h-32 opacity-[.08] pointer-events-none hidden md:block" viewBox="0 0 200 120" fill="#fff" aria-hidden="true">
            <rect x="10" y="40" width="50" height="80"/><rect x="70" y="10" width="60" height="110"/><rect x="140" y="55" width="50" height="65"/>
            <rect x="92" y="0" width="16" height="12"/><rect x="96" y="-6" width="8" height="24"/>
        </svg>

        <div class="relative p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center gap-6">
            <div class="flex-1 min-w-0 space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                    <i class="bi bi-hospital-fill"></i> Infraestructura médica
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Consultorios</h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Acomoda los consultorios en el plano de cada piso y revisa en vivo cuáles están libres, en consulta o en mantenimiento.
                </p>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="button" @click="abrirModalCrear()"
                            class="group bg-white text-teal-900 hover:bg-emerald-50 px-5 py-2.5 rounded-2xl text-xs font-black shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-plus-lg text-sm text-teal-700 transition-transform duration-300 group-hover:rotate-90"></i> Registrar consultorio
                    </button>
                    <span class="inline-flex items-center gap-2 bg-white/10 border border-white/15 rounded-2xl px-3.5 py-2 text-[11px] font-bold text-teal-100">
                        <span class="relative flex w-2 h-2">
                            <span class="absolute inline-flex w-full h-full rounded-full opacity-75" :class="sinConexion ? 'bg-amber-400' : 'bg-emerald-400 animate-ping'"></span>
                            <span class="relative inline-flex w-2 h-2 rounded-full" :class="sinConexion ? 'bg-amber-400' : 'bg-emerald-400'"></span>
                        </span>
                        <span x-text="sinConexion ? 'Sin conexión, reintentando…' : 'En vivo · ' + haceCuanto"></span>
                    </span>
                </div>
            </div>

            {{-- Ocupación actual --}}
            <div class="flex items-center gap-5 bg-white/10 border border-white/10 rounded-3xl p-4 sm:p-5 backdrop-blur-md self-start lg:self-auto">
                <div class="relative w-20 h-20 shrink-0">
                    <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
                        <circle cx="40" cy="40" r="34" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="7"/>
                        <circle cx="40" cy="40" r="34" fill="none" stroke="#fda4af" stroke-width="7" stroke-linecap="round"
                                stroke-dasharray="213.6" :stroke-dashoffset="213.6 - 213.6 * ocupacion / 100"
                                style="transition: stroke-dashoffset 1s cubic-bezier(.16,1,.3,1)"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center leading-none">
                        <span class="text-lg font-black tabular-nums" x-text="ocupacion + '%'"></span>
                        <span class="text-[8px] font-bold uppercase tracking-wider text-teal-100/70 mt-0.5">ocupado</span>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-300">Ahora mismo</p>
                    <p class="text-xs font-bold flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span><span x-text="contar('disponible') + ' libres'"></span></p>
                    <p class="text-xs font-bold flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-400"></span><span x-text="contar('ocupado') + ' en consulta'"></span></p>
                    <p class="text-xs font-bold flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span><span x-text="contar('mantenimiento') + ' en mantenimiento'"></span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== RESUMEN ===================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $colorIcono, $colorBarra])
            <button type="button" @click="filtrarPorTarjeta('{{ $clave }}')"
                    class="tarjeta-stat group text-left bg-white rounded-2xl border shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1 cursor-pointer"
                    :class="(filtroEstado === '{{ $clave }}' || ('{{ $clave }}' === 'total' && filtroEstado === 'todos')) ? 'border-teal-300 ring-2 ring-teal-100' : 'border-slate-100 hover:border-slate-200'"
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
                    <div class="h-full rounded-full {{ $colorBarra }} transition-all duration-700 ease-out"
                         :style="'width:' + ('{{ $clave }}' === 'total' ? (plano.length ? 100 : 0) : porcentaje('{{ $clave }}')) + '%'"></div>
                </div>
            </button>
        @endforeach
    </div>

    @if ($listaConsultorios->isNotEmpty())
    {{-- ===================== PLANO 3D ===================== --}}
    <div x-ref="seccionPlano" class="aparece bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden" style="--d: 220ms">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3 px-5 sm:px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-bounding-box-circles"></i></span>
                <div>
                    <p class="text-sm font-black text-slate-800">Plano del edificio</p>
                    <p class="text-[10px] font-semibold text-slate-400">Arrastra para acomodar · clic para ver detalles · flechas para mover</p>
                </div>
            </div>

            {{-- Estado del guardado --}}
            <span class="text-[10px] font-bold flex items-center gap-1.5 transition-all"
                  :class="{ 'text-slate-400': estadoGuardado === 'guardando', 'text-emerald-600': estadoGuardado === 'ok', 'text-amber-600': estadoGuardado === 'local' }"
                  x-show="estadoGuardado" x-transition.opacity>
                <span x-show="estadoGuardado === 'guardando'" class="w-3 h-3 rounded-full border-2 border-slate-400 border-t-transparent animate-spin"></span>
                <i x-show="estadoGuardado === 'ok'" class="bi bi-cloud-check-fill"></i>
                <i x-show="estadoGuardado === 'local'" class="bi bi-cloud-slash-fill"></i>
                <span x-text="{ guardando: 'Guardando…', ok: 'Guardado', local: 'Guardado solo en este navegador' }[estadoGuardado]"></span>
            </span>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <button type="button" @click="deshacer()" :disabled="!historial.length" title="Deshacer último movimiento (Ctrl + Z)"
                        class="h-8 px-3 rounded-xl border border-slate-200 bg-white text-[11px] font-bold text-slate-500 hover:text-teal-700 hover:border-teal-300 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="bi bi-arrow-counterclockwise"></i> Deshacer
                </button>
                <button type="button" @click="ordenarPiso()" title="Acomodar los consultorios de este piso en cuadrícula"
                        class="h-8 px-3 rounded-xl border border-slate-200 bg-white text-[11px] font-bold text-slate-500 hover:text-teal-700 hover:border-teal-300 transition-all flex items-center gap-1.5 cursor-pointer">
                    <i class="bi bi-grid-3x3-gap"></i> Ordenar
                </button>
                <div class="flex items-center bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <button type="button" @click="cambiarZoom(-0.1)" class="w-8 h-8 text-slate-500 hover:bg-slate-50 hover:text-teal-700 cursor-pointer" title="Alejar"><i class="bi bi-dash-lg"></i></button>
                    <button type="button" @click="ajustarZoom(true)" class="h-8 px-2 text-[10px] font-black text-slate-500 hover:text-teal-700 tabular-nums cursor-pointer" title="Ajustar al ancho" x-text="Math.round(zoom * 100) + '%'"></button>
                    <button type="button" @click="cambiarZoom(0.1)" class="w-8 h-8 text-slate-500 hover:bg-slate-50 hover:text-teal-700 cursor-pointer" title="Acercar"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row">

            {{-- Selector de piso: el piso más alto queda arriba, como en el edificio --}}
            <div class="md:w-48 shrink-0 p-4 border-b md:border-b-0 md:border-r border-slate-100 bg-slate-50/60">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="bi bi-building"></i> Pisos
                </p>
                <div class="flex md:flex-col-reverse gap-2 overflow-x-auto md:overflow-visible pb-1 md:pb-0">
                    <template x-for="p in pisosPlano" :key="p">
                        <button type="button" @click="cambiarPiso(p)"
                                :class="pisoActivo === p
                                    ? 'bg-teal-600 text-white border-teal-600 shadow-lg shadow-teal-600/25 md:translate-x-1'
                                    : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'"
                                class="shrink-0 min-w-[130px] text-left border-2 rounded-2xl px-3.5 py-2.5 transition-all active:scale-95 cursor-pointer">
                            <span class="flex items-center justify-between gap-3 text-xs font-black">
                                <span class="flex items-center gap-2">
                                    <i class="bi bi-layers-fill"></i>
                                    <span x-text="etiquetaPiso(p)"></span>
                                    <span x-show="ocupadosEnPiso(p) > 0" x-cloak class="latido w-1.5 h-1.5 rounded-full"
                                          :class="pisoActivo === p ? 'bg-white' : 'bg-rose-400'"></span>
                                </span>
                                <span class="text-[10px] font-bold opacity-70" x-text="contarPiso(p)"></span>
                            </span>
                            {{-- Composición del piso --}}
                            <span class="mt-2 flex h-1.5 rounded-full overflow-hidden" :class="pisoActivo === p ? 'bg-white/25' : 'bg-slate-100'">
                                <span class="bg-emerald-400 transition-all duration-500" :style="'width:' + composicion(p, 'disponible') + '%'"></span>
                                <span class="bg-rose-400 transition-all duration-500" :style="'width:' + composicion(p, 'ocupado') + '%'"></span>
                                <span class="bg-amber-400 transition-all duration-500" :style="'width:' + composicion(p, 'mantenimiento') + '%'"></span>
                            </span>
                        </button>
                    </template>
                </div>

                {{-- Leyenda --}}
                <div class="hidden md:block mt-5 pt-4 border-t border-slate-200/70 space-y-2 text-[10px] font-bold text-slate-500">
                    <p class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded" style="background:linear-gradient(135deg,#dfb187,#c98f5b)"></span> Disponible</p>
                    <p class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded" style="background:linear-gradient(135deg,#f0c2b4,#e08f7c)"></span> Ocupado</p>
                    <p class="flex items-center gap-2"><span class="w-3.5 h-3.5 rounded border border-amber-400" style="background:repeating-linear-gradient(45deg,#fde68a 0 3px,#fef3c7 3px 6px)"></span> Mantenimiento</p>
                </div>
            </div>

            {{-- Plano del piso seleccionado --}}
            <div x-ref="contenedorPlano" class="flex-1 min-w-0 overflow-auto bg-gradient-to-br from-slate-100 via-slate-50 to-white max-h-[760px]">
                <div class="relative mx-auto" :style="`width:${940 * zoom}px; height:${740 * zoom}px;`">
                    <div class="absolute left-0 top-0 origin-top-left select-none" :style="`width:940px; height:740px; transform:scale(${zoom});`">
                        <div class="absolute inset-0" :class="animando ? 'anim-piso' : ''">

                            {{-- Rótulo del piso --}}
                            <div class="absolute left-6 top-4 pointer-events-none">
                                <p class="text-5xl font-black text-slate-200 leading-none" x-text="etiquetaPiso(pisoActivo)"></p>
                                <p class="text-[10px] font-bold text-slate-300 uppercase tracking-wider mt-1"
                                   x-text="planoVisible.length + (planoVisible.length === 1 ? ' consultorio' : ' consultorios')"></p>
                            </div>

                            {{-- Rosa de los vientos --}}
                            <div class="absolute right-8 top-6 w-14 h-14 rounded-full border-2 border-slate-200 flex items-center justify-center text-[10px] font-black text-slate-300 pointer-events-none">
                                <span class="absolute -top-0.5">N</span><i class="bi bi-compass text-xl"></i>
                            </div>

                            {{-- Piso isométrico: 720 x 540 px --}}
                            <div class="plano-piso absolute" :style="`left:110px; top:100px; width:720px; height:540px; --tinte:${tinte};`">
                                <div class="muro-ext-t"></div>
                                <div class="muro-ext-i"></div>
                                <div class="remate-ext-t"></div>
                                <div class="remate-ext-i"></div>

                                {{-- Casilla de destino mientras se arrastra --}}
                                <div class="absolute rounded-md pointer-events-none" :style="estiloDestino()"></div>

                                <template x-for="(c, i) in planoVisible" :key="c.id">
                                    <div class="sala" tabindex="0" role="button"
                                         :data-id="c.id"
                                         :aria-label="c.nombre + ', ' + estadosInfo[c.estado].texto"
                                         :class="['e-' + c.estado,
                                                  arrastre && arrastre.id === c.id && arrastre.movio ? 'cursor-grabbing' : 'cursor-grab',
                                                  resaltado === c.id && 'resaltada',
                                                  !coincideTexto(c.nombre) && 'atenuada']"
                                         :style="estiloSala(c, i)"
                                         :title="c.nombre + ' · ' + estadosInfo[c.estado].texto + (c.estado === 'ocupado' && c.ocupadoPor ? ' · En consulta con ' + c.ocupadoPor : '')"
                                         @pointerdown="iniciarArrastre($event, c)"
                                         @pointermove="mover($event, c)"
                                         @pointerup="soltar(c)"
                                         @pointercancel="cancelar(c)"
                                         @keydown.enter.prevent="verInfo(c)"
                                         @keydown.arrow-up.prevent="moverTeclado(c, 0, -1)"
                                         @keydown.arrow-down.prevent="moverTeclado(c, 0, 1)"
                                         @keydown.arrow-left.prevent="moverTeclado(c, -1, 0)"
                                         @keydown.arrow-right.prevent="moverTeclado(c, 1, 0)">
                                        <div class="piso"></div>
                                        <div class="muro-t"></div>
                                        <div class="muro-i"></div>
                                        <div class="remate-t"></div>
                                        <div class="remate-i"></div>
                                        {{-- Mueble: camilla (disponible / ocupado) o cono (mantenimiento) --}}
                                        <div class="mueble" x-show="c.estado !== 'mantenimiento'"></div>
                                        <div class="etiqueta-sala">
                                            <div>
                                                <i class="bi" :class="{ 'bi-activity latido': c.estado === 'ocupado', 'bi-cone-striped': c.estado === 'mantenimiento', 'bi-door-open': c.estado === 'disponible' || c.estado === 'desconocido' }"></i>
                                                <span x-text="c.nombre"></span>
                                                <small x-text="c.estado === 'ocupado' && c.ocupadoPor ? c.ocupadoPor : estadosInfo[c.estado].texto"></small>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-[10px] text-slate-400 font-semibold px-6 py-3 border-t border-slate-100 flex items-center gap-2">
            <i class="bi bi-info-circle"></i>
            Cada piso tiene su propio plano. Las posiciones se guardan al soltar cada consultorio.
        </p>
    </div>
    @endif

    {{-- ===================== TABLA ===================== --}}
    <div class="aparece bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden" style="--d: 320ms">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h3 class="text-base font-black text-slate-800">Consultorios registrados</h3>
                <span class="text-xs font-bold px-3 py-1 bg-teal-50 text-teal-700 rounded-full tabular-nums"
                      x-text="hayFiltros ? visibles + ' de ' + plano.length : plano.length + ' en total'">{{ $totalConsultorios }} en total</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[11px]"></i>
                    <input type="search" x-model.debounce.150ms="busqueda" placeholder="Buscar por nombre…"
                           class="bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-xs font-medium outline-none focus:border-teal-500 focus:bg-white transition-all w-48">
                </div>
                <select x-model="filtroPiso" aria-label="Filtrar por piso" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                    <option value="todos">Todos los pisos</option>
                    @foreach ($pisosDisponibles as $p)
                        <option value="{{ $p }}">Piso {{ $p }}</option>
                    @endforeach
                </select>
                <select x-model="filtroEstado" aria-label="Filtrar por estado" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                    <option value="todos">Todos los estados</option>
                    <option value="disponible">Disponible</option>
                    <option value="ocupado">Ocupado</option>
                    <option value="mantenimiento">Mantenimiento</option>
                </select>
                <button type="button" x-show="hayFiltros" x-cloak @click="limpiarFiltros()"
                        class="text-[11px] font-black text-teal-700 hover:text-teal-900 px-2 cursor-pointer">Limpiar</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                        <th class="py-3.5 px-6">Consultorio</th>
                        <th class="py-3.5 px-6">Piso</th>
                        <th class="py-3.5 px-6">Estado</th>
                        <th class="py-3.5 px-6 hidden md:table-cell">Registrado</th>
                        <th class="py-3.5 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-bold text-slate-700">
                    @forelse($listaConsultorios as $c)
                        @php $slug = $slugEstado($c); @endphp
                        <tr x-show="coincide(@js($c->nombre), @js((string) ($c->piso ?? '')), estadoDe({{ $c->id }}))"
                            style="--d: {{ min($loop->index * 45, 500) }}ms"
                            class="fila-in group hover:bg-teal-50/30 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110"
                                          :class="{ 'bg-emerald-50 text-emerald-600': estadoDe({{ $c->id }}) === 'disponible', 'bg-rose-50 text-rose-500': estadoDe({{ $c->id }}) === 'ocupado', 'bg-amber-50 text-amber-600': estadoDe({{ $c->id }}) === 'mantenimiento', 'bg-slate-100 text-slate-400': estadoDe({{ $c->id }}) === 'desconocido' }">
                                        <i class="bi" :class="{ 'bi-activity': estadoDe({{ $c->id }}) === 'ocupado', 'bi-cone-striped': estadoDe({{ $c->id }}) === 'mantenimiento', 'bi-door-open': ['disponible', 'desconocido'].includes(estadoDe({{ $c->id }})) }"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-slate-900 font-black">{{ $c->nombre }}</p>
                                        <p class="text-[10px] font-semibold text-slate-400">
                                            #{{ $c->id }}
                                            <span x-show="ocupadoPorDe({{ $c->id }})" x-cloak class="text-rose-500" x-text="'· En consulta con ' + ocupadoPorDe({{ $c->id }})"></span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-6">{{ ($c->piso !== null && $c->piso !== '') ? 'Piso ' . $c->piso : '—' }}</td>
                            <td class="py-3.5 px-6">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black border"
                                      :class="estadosInfo[estadoDe({{ $c->id }})].badge">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="[estadosInfo[estadoDe({{ $c->id }})].punto, estadoDe({{ $c->id }}) === 'ocupado' && 'latido']"></span>
                                    <span x-text="estadosInfo[estadoDe({{ $c->id }})].texto">{{ $estadosCfg[$slug]['texto'] }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-slate-400 font-semibold hidden md:table-cell">
                                {{ $c->created_at ? \Illuminate\Support\Carbon::parse($c->created_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="py-3.5 px-6 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="verEnPlano({{ $c->id }})"
                                            class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 hover:bg-teal-100 hover:scale-110 active:scale-95 transition-all flex items-center justify-center cursor-pointer" title="Ver en el plano">
                                        <i class="bi bi-geo-alt-fill text-[11px]"></i>
                                    </button>
                                    <button type="button" @click="abrirModalEditar({{ $c->id }})"
                                            class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 hover:scale-110 active:scale-95 transition-all flex items-center justify-center cursor-pointer" title="Editar">
                                        <i class="bi bi-pencil-fill text-[11px]"></i>
                                    </button>
                                    <button type="button" @click="eliminarConsultorio({{ $c->id }})"
                                            class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:scale-110 active:scale-95 transition-all flex items-center justify-center cursor-pointer" title="Eliminar">
                                        <i class="bi bi-trash-fill text-[11px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-14 text-center">
                                <span class="flotar inline-flex w-16 h-16 rounded-3xl bg-teal-50 text-teal-500 items-center justify-center text-3xl mb-3"><i class="bi bi-door-open"></i></span>
                                <p class="text-sm font-black text-slate-600">Aún no hay consultorios</p>
                                <button type="button" @click="abrirModalCrear()" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">+ Registrar el primero</button>
                            </td>
                        </tr>
                    @endforelse

                    @if ($listaConsultorios->isNotEmpty())
                        <tr x-show="visibles === 0" x-cloak>
                            <td colspan="5" class="py-12 text-center">
                                <span class="flotar inline-flex w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 items-center justify-center text-2xl mb-3"><i class="bi bi-search"></i></span>
                                <p class="text-xs font-black text-slate-500">Sin resultados</p>
                                <button type="button" @click="limpiarFiltros()" class="mt-1 text-[11px] font-black text-teal-700 hover:text-teal-900 cursor-pointer">Quitar filtros</button>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===================== MODAL: INFORMACIÓN ===================== --}}
    <template x-teleport="body">
        <div x-show="openInfo" x-cloak
             @keydown.escape.window="!confirmar.abierto && (openInfo = false)"
             class="fixed inset-0 z-[99998] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-sm overflow-hidden" @click.outside="openInfo = false"
                 x-show="openInfo"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">
                <div class="relative px-7 pt-7 pb-5 text-white overflow-hidden transition-colors duration-500"
                     :class="{ 'bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800': infoActual.estado === 'disponible' || infoActual.estado === 'desconocido',
                               'bg-gradient-to-r from-rose-900 via-rose-800 to-pink-800': infoActual.estado === 'ocupado',
                               'bg-gradient-to-r from-amber-800 via-amber-700 to-orange-700': infoActual.estado === 'mantenimiento' }">
                    <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                    <button type="button" @click="openInfo = false"
                            class="absolute right-4 top-4 w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                    <div class="relative flex items-center gap-4">
                        <span class="w-14 h-14 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-2xl shrink-0" :class="openInfo && 'icono-pop'">
                            <i class="bi" :class="{ 'bi-activity latido': infoActual.estado === 'ocupado', 'bi-cone-striped': infoActual.estado === 'mantenimiento', 'bi-door-open-fill': infoActual.estado === 'disponible' || infoActual.estado === 'desconocido' }"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-white/70" x-text="etiquetaPiso(infoActual.piso) + ' · #' + infoActual.id"></p>
                            <h3 class="text-base font-black tracking-tight truncate" x-text="infoActual.nombre"></h3>
                            <p class="text-[11px] font-bold text-white/80" x-text="estadosInfo[infoActual.estado].texto"></p>
                        </div>
                    </div>
                </div>

                <div class="p-7 space-y-4">
                    <div x-show="infoActual.estado === 'ocupado'" x-cloak class="rounded-2xl bg-rose-50/70 border border-rose-100 p-3.5 reveal-item" :class="openInfo && 'reveal-on'" style="--d:1">
                        <p class="text-[10px] font-bold text-rose-400 uppercase tracking-wide flex items-center gap-1.5"><i class="bi bi-activity"></i> En consulta ahora</p>
                        <p class="text-sm font-black text-slate-800 mt-0.5" x-text="infoActual.ocupadoPor || 'Consulta en curso'"></p>
                        <p class="text-[10px] font-semibold text-slate-400 mt-1">Se libera automáticamente al terminar la consulta.</p>
                    </div>

                    {{-- Cambio rápido de estado --}}
                    <div x-show="infoActual.estado !== 'ocupado'" class="reveal-item" :class="openInfo && 'reveal-on'" style="--d:1">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-2 flex items-center gap-2">
                            Estado
                            <span x-show="cambiandoEstado" class="w-3 h-3 rounded-full border-2 border-teal-500 border-t-transparent animate-spin"></span>
                        </p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="cambiarEstadoRapido('Disponible')" :disabled="cambiandoEstado || infoActual.estado === 'disponible'"
                                    :class="infoActual.estado === 'disponible' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-500 hover:border-emerald-300'"
                                    class="flex items-center justify-center gap-2 border-2 rounded-2xl py-2.5 text-xs font-black transition-all cursor-pointer disabled:cursor-default">
                                <i class="bi bi-door-open"></i> Disponible
                            </button>
                            <button type="button" @click="cambiarEstadoRapido('Mantenimiento')" :disabled="cambiandoEstado || infoActual.estado === 'mantenimiento'"
                                    :class="infoActual.estado === 'mantenimiento' ? 'border-amber-400 bg-amber-50 text-amber-700' : 'border-slate-200 text-slate-500 hover:border-amber-300'"
                                    class="flex items-center justify-center gap-2 border-2 rounded-2xl py-2.5 text-xs font-black transition-all cursor-pointer disabled:cursor-default">
                                <i class="bi bi-tools"></i> Mantenimiento
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openInfo && 'reveal-on'" style="--d:2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Piso</p>
                            <p class="text-sm font-black text-slate-800 mt-0.5" x-text="infoActual.piso ? 'Piso ' + infoActual.piso : '—'"></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openInfo && 'reveal-on'" style="--d:3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Registrado</p>
                            <p class="text-sm font-black text-slate-800 mt-0.5" x-text="infoActual.creado || '—'"></p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" @click="eliminarDesdeInfo()"
                                class="mr-auto px-3 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer" title="Eliminar">
                            <i class="bi bi-trash-fill"></i><span class="hidden sm:inline">Eliminar</span>
                        </button>
                        <button type="button" @click="openInfo = false"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                            Cerrar
                        </button>
                        <button type="button" @click="editarDesdeInfo()"
                                class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-pencil-fill"></i> Editar
                        </button>
                    </div>
                </div>
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
                <h3 class="text-base font-black text-slate-800">¿Eliminar este consultorio?</h3>
                <p class="text-xs font-medium text-slate-500 mt-2 leading-relaxed">
                    Se eliminará <strong class="text-slate-700" x-text="confirmar.nombre"></strong> del sistema.
                    Esta acción no se puede deshacer.
                </p>
                <p x-show="confirmar.ocupado" x-cloak class="mt-3 text-[11px] font-bold text-rose-600 bg-rose-50 border border-rose-100 rounded-xl px-3 py-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> Este consultorio tiene una consulta en curso.
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

    {{-- Modal crear / editar (usa: openModal, modoEdicion, form, opcionesEstado, guardar()) --}}
    @include('consultorios.modal')

</div>

<style>
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes filaIn   { from { opacity: 0; transform: translateX(-12px); } to { opacity: 1; transform: none; } }
    @keyframes iconoPop { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes latido   { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .6; } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes barraToast { from { width: 100%; } to { width: 0; } }
    @keyframes salaCae  { from { transform: translateZ(110px); } to { transform: translateZ(0); } }
    @keyframes cambioPiso { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
    @keyframes brilloOcupado { 0%, 100% { box-shadow: inset 0 0 0 1px rgba(0,0,0,.08), inset 0 0 0 0 rgba(244,63,94,0); } 50% { box-shadow: inset 0 0 0 1px rgba(0,0,0,.08), inset 0 0 22px 4px rgba(244,63,94,.45); } }
    @keyframes franjas  { to { background-position: 32px 0; } }
    @keyframes resaltar { 0%, 100% { box-shadow: inset 0 0 0 3px rgba(20,184,166,.9), 0 0 0 0 rgba(20,184,166,.6); } 50% { box-shadow: inset 0 0 0 3px rgba(20,184,166,.9), 0 0 0 14px rgba(20,184,166,0); } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .fila-in      { animation: filaIn .45s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .icono-pop    { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .latido       { animation: latido 1.4s ease-in-out infinite; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .anim-piso    { animation: cambioPiso .4s cubic-bezier(.16, 1, .3, 1); }

    .reveal-item { opacity: 0; }
    .reveal-on   { animation: fadeUp .45s cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--d, 0) * 70ms); }

    [x-cloak] { display: none !important; }

    /* ---------- Plano isométrico ---------- */
    .plano-piso {
        transform: rotateX(52deg) rotateZ(45deg);
        transform-style: preserve-3d;
        background-color: var(--tinte, #eef2f6);
        background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
        background-size: 20px 20px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        box-shadow: 0 10px 0 rgba(15, 23, 42, .08), 0 40px 60px -20px rgba(15, 23, 42, .25);
        transition: background-color .5s;
    }
    .muro-ext-t, .muro-ext-i, .remate-ext-t, .remate-ext-i { position: absolute; pointer-events: none; }
    .muro-ext-t { left: 0; top: 0; width: 100%; height: 70px; background: linear-gradient(to top, #ffffff, #f1f5f9); transform-origin: 0 0; transform: rotateX(90deg); }
    .muro-ext-i { left: 0; top: 0; width: 70px; height: 100%; background: linear-gradient(to left, #e2e8f0, #cbd5e1); transform-origin: 0 0; transform: rotateY(-90deg); }
    .remate-ext-t { left: -8px; top: -8px; width: calc(100% + 8px); height: 8px; background: #0f172a; transform: translateZ(70px); }
    .remate-ext-i { left: -8px; top: -8px; width: 8px; height: calc(100% + 8px); background: #0f172a; transform: translateZ(70px); }

    .sala { position: absolute; transform-style: preserve-3d; touch-action: none; outline: none; animation: salaCae .6s cubic-bezier(.34, 1.3, .64, 1) backwards; animation-delay: var(--d, 0ms); }
    .sala:not(.cursor-grabbing):hover { transform: translateZ(10px) !important; }
    .sala > * { position: absolute; transform-style: preserve-3d; }

    .sala .piso { inset: 0; background: linear-gradient(135deg, #dfb187, #c98f5b); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08); transition: background .4s; }
    .sala.e-ocupado .piso { background: linear-gradient(135deg, #f0c2b4, #e08f7c); animation: brilloOcupado 2.2s ease-in-out infinite; }
    .sala.e-mantenimiento .piso { background: repeating-linear-gradient(45deg, #fde68a 0 8px, #fef3c7 8px 16px); background-size: 32px 32px; animation: franjas 1.6s linear infinite; }
    .sala.e-desconocido .piso { background: repeating-linear-gradient(45deg, #e2e8f0 0 8px, #f1f5f9 8px 16px); }
    .sala:focus-visible .piso { box-shadow: inset 0 0 0 3px #14b8a6; }
    .sala.resaltada .piso { animation: resaltar .9s ease-in-out 3; }
    .sala.atenuada .piso { background: #e5e7eb !important; animation: none; }
    .sala.atenuada .etiqueta-sala > div { background: rgba(255,255,255,.55); }
    .sala.atenuada .etiqueta-sala span, .sala.atenuada .etiqueta-sala i, .sala.atenuada .etiqueta-sala small { color: #94a3b8 !important; }

    .sala .muro-t { left: 0; top: 0; width: 100%; height: 56px; background: linear-gradient(to top, #ffffff, #f8fafc); transform-origin: 0 0; transform: rotateX(90deg); }
    .sala .muro-i { left: 0; top: 0; width: 56px; height: 100%; background: linear-gradient(to left, #e2e8f0, #cbd5e1); transform-origin: 0 0; transform: rotateY(-90deg); }
    .sala .remate-t { left: -5px; top: -5px; width: calc(100% + 5px); height: 5px; background: #1e293b; transform: translateZ(56px); }
    .sala .remate-i { left: -5px; top: -5px; width: 5px; height: calc(100% + 5px); background: #1e293b; transform: translateZ(56px); }
    .sala.e-ocupado .remate-t, .sala.e-ocupado .remate-i { background: #fb7185; }
    .sala.e-mantenimiento .remate-t, .sala.e-mantenimiento .remate-i { background: #f59e0b; }
    .sala.e-desconocido .remate-t, .sala.e-desconocido .remate-i { background: #94a3b8; }

    /* Camilla sencilla sobre el piso */
    .sala .mueble { right: 12px; bottom: 12px; width: 26px; height: 46px; background: #f8fafc; border-radius: 4px; transform: translateZ(8px); box-shadow: 0 0 0 1px rgba(15,23,42,.12); }
    .sala .mueble::before { content: ''; position: absolute; left: 3px; right: 3px; top: 3px; height: 10px; border-radius: 3px; background: #99f6e4; }
    .sala.e-ocupado .mueble::before { background: #fecdd3; }

    .sala .etiqueta-sala { inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; transform: translateZ(1px); }
    .sala .etiqueta-sala > div {
        transform: translateZ(30px) rotateZ(-45deg) rotateX(-52deg);
        display: flex; flex-direction: column; align-items: center; gap: 1px;
        max-width: 96px; text-align: center; line-height: 1.15;
        background: rgba(255, 255, 255, .94);
        border-radius: 9px; padding: 4px 8px;
        box-shadow: 0 4px 10px -2px rgba(15, 23, 42, .3);
    }
    .sala .etiqueta-sala i { font-size: 14px; color: #0f766e; }
    .sala.e-ocupado .etiqueta-sala i { color: #e11d48; }
    .sala.e-mantenimiento .etiqueta-sala i { color: #d97706; }
    .sala .etiqueta-sala span { font-size: 11px; font-weight: 900; color: #0f172a; word-break: break-word; }
    .sala .etiqueta-sala small { font-size: 9px; font-weight: 700; color: #64748b; max-width: 88px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sala.e-ocupado .etiqueta-sala small { color: #e11d48; }
    .sala.e-mantenimiento .etiqueta-sala small { color: #b45309; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .fila-in, .icono-pop, .latido, .flotar, .reveal-on, .anim-piso, .sala,
        .sala .piso { animation: none !important; }
        .reveal-item { opacity: 1 !important; }
    }
</style>

<script>
    const URL_CONSULTORIOS = @js(url('consultorios'));
    const CSRF = @js(csrf_token());

    function avisoConsultorios(icon, title, ms = 3500) {
        window.dispatchEvent(new CustomEvent('toast-consultorio', { detail: { icon, title, ms } }));
    }

    function toastYRecargar(icon, title) {
        try { sessionStorage.setItem('toast_consultorios', JSON.stringify({ icon, title })); } catch (e) {}
        location.reload();
    }

    async function peticion(url, method, body) {
        const r = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: body ? JSON.stringify(body) : undefined
        });
        const res = await r.json().catch(() => ({}));
        let mensaje = res.message;
        if (!r.ok && res.errors) mensaje = res.errors[Object.keys(res.errors)[0]][0];
        if (r.status === 419) mensaje = 'Tu sesión expiró. Recarga la página.';
        return { ok: r.ok, status: r.status, res, mensaje };
    }

    function moduloConsultorios() {
    return {
        // ----- Modal crear / editar (lo usa consultorios.modal) -----
        openModal: false,
        modoEdicion: false,
        idSeleccionado: null,
        guardando: false,
        form: { clinica_id: 1, nombre: '', piso: '1', estado: 'Disponible' },

        estadosInfo: {
            disponible:    { texto: 'Disponible',    badge: 'bg-emerald-50 text-emerald-600 border-emerald-200', punto: 'bg-emerald-500' },
            ocupado:       { texto: 'Ocupado',       badge: 'bg-rose-50 text-rose-600 border-rose-200',         punto: 'bg-rose-400' },
            mantenimiento: { texto: 'Mantenimiento', badge: 'bg-amber-50 text-amber-600 border-amber-200',      punto: 'bg-amber-400' },
            desconocido:   { texto: 'Sin definir',   badge: 'bg-slate-100 text-slate-500 border-slate-200',     punto: 'bg-slate-400' }
        },
        opcionesEstado: [
            { valor: 'Disponible',    punto: 'bg-emerald-500', activo: 'border-emerald-500 bg-emerald-50 text-emerald-700' },
            { valor: 'Ocupado',       punto: 'bg-rose-400',    activo: 'border-rose-400 bg-rose-50 text-rose-600' },
            { valor: 'Mantenimiento', punto: 'bg-amber-400',   activo: 'border-amber-400 bg-amber-50 text-amber-600' }
        ],

        // ----- Información (clic en el plano) -----
        openInfo: false,
        cambiandoEstado: false,
        infoActual: { id: '', clinica_id: 1, nombre: '', piso: '', estado: 'disponible', creado: null, ocupadoPor: null },

        // ----- Plano 3D -----
        plano: @json($datosPlano),
        tam: 100,
        paso: 20,
        ancho: 720,
        alto: 540,
        anguloZ: 45,
        anguloX: 52,
        arrastre: null,
        zoom: 1,
        historial: [],          // movimientos para "Deshacer" (cada entrada: lista de { id, x, y })
        resaltado: null,
        estadoGuardado: '',     // '', 'guardando', 'ok', 'local'
        _timerGuardado: null,
        _timersTeclado: {},

        // ----- En vivo -----
        ultimaSync: null,
        ahora: Date.now(),
        sinConexion: false,

        resumenMostrado: { total: 0, disponible: 0, ocupado: 0, mantenimiento: 0 },
        animacionesResumen: {},

        confirmar: { abierto: false, cargando: false, id: null, nombre: '', ocupado: false },

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

        // =========================================================== INICIO
        init() {
            window.addEventListener('toast-consultorio', e => this.agregarToast(e.detail));

            // Posiciones guardadas solo en este navegador (cuando el servidor no las pudo guardar)
            try {
                const locales = JSON.parse(localStorage.getItem('plano_consultorios') || '{}');
                this.plano.forEach(c => { if (!c.guardado && locales[c.id]) { c.x = locales[c.id].x; c.y = locales[c.id].y; } });
            } catch (e) {}

            let guardado = null;
            try { guardado = sessionStorage.getItem('piso_activo_consultorios'); } catch (e) {}
            this.pisoActivo = (guardado !== null && this.pisosPlano.includes(guardado)) ? guardado : (this.pisosPlano[0] ?? '');

            this.animarResumen();

            this.sincronizarEstados();
            setInterval(() => { if (!document.hidden && !this.arrastre) this.sincronizarEstados(); }, 15000);
            setInterval(() => { this.ahora = Date.now(); }, 5000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) this.sincronizarEstados(); });

            // Zoom inicial para que el plano quepa en pantallas pequeñas
            this.$nextTick(() => this.ajustarZoom(false));
            window.addEventListener('resize', () => { if (this.zoomAuto) this.ajustarZoom(false); });

            // Ctrl + Z = deshacer último movimiento
            window.addEventListener('keydown', e => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)
                    && !this.openModal && !this.openInfo && this.historial.length) {
                    e.preventDefault();
                    this.deshacer();
                }
            });

            try {
                const pendiente = sessionStorage.getItem('toast_consultorios');
                if (pendiente) {
                    sessionStorage.removeItem('toast_consultorios');
                    const t = JSON.parse(pendiente);
                    this.$nextTick(() => avisoConsultorios(t.icon, t.title));
                }
            } catch (e) {}
        },

        // =========================================================== RESUMEN
        contar(slug) { return this.plano.filter(c => c.estado === slug).length; },
        porcentaje(slug) { return this.plano.length ? Math.round(this.contar(slug) / this.plano.length * 100) : 0; },
        get ocupacion() {
            const utiles = this.plano.filter(c => c.estado !== 'mantenimiento').length;
            return utiles ? Math.round(this.contar('ocupado') / utiles * 100) : 0;
        },
        get haceCuanto() {
            if (!this.ultimaSync) return 'conectando…';
            const s = Math.max(0, Math.round((this.ahora - this.ultimaSync) / 1000));
            return s < 10 ? 'actualizado ahora' : 'hace ' + (s < 60 ? s + ' s' : Math.floor(s / 60) + ' min');
        },
        animarResumen() {
            const sinMov = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            ['total', 'disponible', 'ocupado', 'mantenimiento'].forEach(k => {
                const destino = k === 'total' ? this.plano.length : this.contar(k);
                const origen = this.resumenMostrado[k];
                cancelAnimationFrame(this.animacionesResumen[k]);
                if (sinMov || origen === destino) { this.resumenMostrado[k] = destino; return; }
                const t0 = performance.now(), dur = 700;
                const paso = t => {
                    const p = Math.min(1, (t - t0) / dur);
                    this.resumenMostrado[k] = Math.round(origen + (destino - origen) * (1 - Math.pow(1 - p, 3)));
                    if (p < 1) this.animacionesResumen[k] = requestAnimationFrame(paso);
                };
                this.animacionesResumen[k] = requestAnimationFrame(paso);
            });
        },
        filtrarPorTarjeta(clave) {
            this.filtroEstado = (clave === 'total' || this.filtroEstado === clave) ? 'todos' : clave;
        },

        // =========================================================== ESTADO EN VIVO
        estadoDe(id) { const c = this.plano.find(x => x.id === id); return c ? c.estado : 'desconocido'; },
        ocupadoPorDe(id) { const c = this.plano.find(x => x.id === id); return c && c.estado === 'ocupado' ? c.ocupadoPor : null; },
        slugEstado(txt) {
            const v = String(txt || '').trim().toLowerCase();
            return ['disponible', 'ocupado', 'mantenimiento'].includes(v) ? v : 'desconocido';
        },
        async sincronizarEstados() {
            try {
                const r = await fetch(URL_CONSULTORIOS + '/estados', { headers: { 'Accept': 'application/json' } });
                if (!r.ok) { this.sinConexion = false; return; }
                const lista = await r.json();
                let huboCambios = false;
                (Array.isArray(lista) ? lista : []).forEach(e => {
                    const c = this.plano.find(x => x.id === e.id);
                    if (!c) return;
                    const nuevo = this.slugEstado(e.estado);
                    if (c.estado !== nuevo) {
                        c.estado = nuevo;
                        huboCambios = true;
                        this.rebotar(c.id);
                        avisoConsultorios(nuevo === 'ocupado' ? 'info' : 'success', c.nombre + ' ahora está ' + this.estadosInfo[nuevo].texto.toLowerCase(), 4000);
                    }
                    c.ocupadoPor = e.ocupado_por || null;
                });
                this.ultimaSync = Date.now();
                this.ahora = Date.now();
                this.sinConexion = false;
                if (huboCambios) this.animarResumen();
            } catch (err) {
                this.sinConexion = true;
            }
        },
        rebotar(id) {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const el = document.querySelector('.sala[data-id="' + id + '"]');
            if (!el || !el.animate) return;
            el.animate([
                { transform: 'translateZ(0)' },
                { transform: 'translateZ(34px)', offset: .35 },
                { transform: 'translateZ(4px)',  offset: .65 },
                { transform: 'translateZ(12px)', offset: .82 },
                { transform: 'translateZ(0)' }
            ], { duration: 900, easing: 'ease-out' });
        },

        // =========================================================== PISOS
        pisoActivo: '',
        animando: false,
        tintes: ['#eef2f6', '#ecf7f1', '#f3eefb', '#fdf3e7', '#eaf3fb'],

        get pisosPlano() {
            return [...new Set(this.plano.map(c => c.piso))].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
        },
        get planoVisible() { return this.plano.filter(c => c.piso === this.pisoActivo); },
        get tinte() {
            const i = Math.max(0, this.pisosPlano.indexOf(this.pisoActivo));
            return this.tintes[i % this.tintes.length];
        },
        etiquetaPiso(p) { return p === '' || p === null || p === undefined ? 'Sin piso' : 'Piso ' + p; },
        contarPiso(p) { return this.plano.filter(c => c.piso === p).length; },
        ocupadosEnPiso(p) { return this.plano.filter(c => c.piso === p && c.estado === 'ocupado').length; },
        composicion(p, slug) {
            const total = this.contarPiso(p);
            return total ? this.plano.filter(c => c.piso === p && c.estado === slug).length / total * 100 : 0;
        },
        cambiarPiso(p) {
            if (this.arrastre || this.pisoActivo === p) return;
            this.pisoActivo = p;
            try { sessionStorage.setItem('piso_activo_consultorios', p); } catch (e) {}
            this.animando = false;
            this.$nextTick(() => { this.animando = true; });
        },
        verEnPlano(id) {
            const c = this.plano.find(x => x.id === id);
            if (!c || !this.$refs.seccionPlano) return;
            this.pisoActivo = c.piso;
            this.$refs.seccionPlano.scrollIntoView({ behavior: 'smooth', block: 'start' });
            this.resaltado = null;
            this.$nextTick(() => {
                this.resaltado = id;
                setTimeout(() => this.rebotar(id), 450);
                setTimeout(() => { if (this.resaltado === id) this.resaltado = null; }, 3000);
            });
        },

        // =========================================================== ZOOM
        zoomAuto: true,
        ajustarZoom(manual) {
            const cont = this.$refs.contenedorPlano;
            if (!cont) return;
            this.zoomAuto = true;
            const ajuste = Math.max(0.4, Math.min(1, (cont.clientWidth - 8) / 940));
            this.zoom = Math.round(ajuste * 100) / 100;
            if (manual) avisoConsultorios('info', 'Plano ajustado al ancho de la pantalla', 2000);
        },
        cambiarZoom(delta) {
            this.zoomAuto = false;
            this.zoom = Math.round(Math.max(0.4, Math.min(1.6, this.zoom + delta)) * 100) / 100;
        },

        // =========================================================== ARRASTRE
        get maxX() { return Math.floor((this.ancho - this.tam) / this.paso) * this.paso; },
        get maxY() { return Math.floor((this.alto - this.tam) / this.paso) * this.paso; },
        snap(v, max) { return Math.max(0, Math.min(max, Math.round(v / this.paso) * this.paso)); },

        estiloSala(c, i = 0) {
            const alzado = this.arrastre && this.arrastre.id === c.id && this.arrastre.movio;
            return `left:${c.x}px; top:${c.y}px; width:${this.tam}px; height:${this.tam}px; --d:${i * 60}ms;`
                 + `transform:${alzado ? 'translateZ(22px)' : 'translateZ(0)'};`
                 + `z-index:${alzado ? 20 : 10};`
                 + `transition:${alzado ? 'none' : 'left .2s cubic-bezier(.16,1,.3,1), top .2s cubic-bezier(.16,1,.3,1), transform .15s'};`;
        },
        estiloDestino() {
            const a = this.arrastre;
            if (!a || !a.movio) return 'display:none;';
            const color = a.choque ? '244,63,94' : '13,148,136';
            return `left:${a.sx}px; top:${a.sy}px; width:${this.tam}px; height:${this.tam}px;`
                 + `background:rgba(${color},0.18); border:2px dashed rgba(${color},0.8);`;
        },
        iniciarArrastre(e, item) {
            if (e.button !== undefined && e.button !== 0) return;
            e.currentTarget.setPointerCapture(e.pointerId);
            this.arrastre = { id: item.id, cx: e.clientX, cy: e.clientY, x0: item.x, y0: item.y, movio: false, sx: item.x, sy: item.y, choque: false };
        },
        // Convierte el movimiento en pantalla al movimiento sobre el plano inclinado (considera el zoom)
        mover(e, item) {
            const a = this.arrastre;
            if (!a || a.id !== item.id) return;
            if (!a.movio) {
                if (Math.hypot(e.clientX - a.cx, e.clientY - a.cy) < 5) return;
                a.movio = true;
            }
            const t = this.anguloZ * Math.PI / 180;
            const f = this.anguloX * Math.PI / 180;
            const u = (e.clientX - a.cx) / this.zoom;
            const v = (e.clientY - a.cy) / this.zoom / Math.cos(f);
            const px = u * Math.cos(t) + v * Math.sin(t);
            const py = -u * Math.sin(t) + v * Math.cos(t);
            item.x = Math.max(0, Math.min(a.x0 + px, this.ancho - this.tam));
            item.y = Math.max(0, Math.min(a.y0 + py, this.alto - this.tam));
            a.sx = this.snap(item.x, this.maxX);
            a.sy = this.snap(item.y, this.maxY);
            a.choque = this.hayColision(item.id, a.sx, a.sy);
        },
        soltar(item) {
            const a = this.arrastre;
            if (!a || a.id !== item.id) return;
            this.arrastre = null;
            if (!a.movio) { this.verInfo(item); return; }
            if (a.choque) {
                item.x = a.x0; item.y = a.y0;
                avisoConsultorios('warning', 'Ese espacio ya está ocupado por otro consultorio');
                return;
            }
            item.x = a.sx; item.y = a.sy;
            if (item.x !== a.x0 || item.y !== a.y0) {
                this.registrarHistorial([{ id: item.id, x: a.x0, y: a.y0 }]);
                this.guardarPosicion(item);
            }
        },
        cancelar(item) {
            const a = this.arrastre;
            if (!a || a.id !== item.id) return;
            item.x = a.x0; item.y = a.y0;
            this.arrastre = null;
        },
        hayColision(id, x, y) {
            const yo = this.plano.find(o => o.id === id);
            return this.plano.some(o => o.id !== id && o.piso === yo.piso && Math.abs(o.x - x) < this.tam && Math.abs(o.y - y) < this.tam);
        },

        // Mover con las flechas del teclado (se guarda al dejar de presionar)
        moverTeclado(item, dx, dy) {
            const nx = this.snap(item.x + dx * this.paso, this.maxX);
            const ny = this.snap(item.y + dy * this.paso, this.maxY);
            if ((nx === item.x && ny === item.y)) return;
            if (this.hayColision(item.id, nx, ny)) { avisoConsultorios('warning', 'Ese espacio ya está ocupado', 1800); return; }
            if (!this._timersTeclado[item.id]) this.registrarHistorial([{ id: item.id, x: item.x, y: item.y }]);
            item.x = nx; item.y = ny;
            clearTimeout(this._timersTeclado[item.id]);
            this._timersTeclado[item.id] = setTimeout(() => { delete this._timersTeclado[item.id]; this.guardarPosicion(item); }, 600);
        },

        // =========================================================== DESHACER Y ORDENAR
        registrarHistorial(movs) {
            this.historial.push(movs);
            if (this.historial.length > 30) this.historial.shift();
        },
        deshacer() {
            const movs = this.historial.pop();
            if (!movs) return;
            const items = [];
            movs.forEach(m => {
                const c = this.plano.find(x => x.id === m.id);
                if (!c) return;
                c.x = m.x; c.y = m.y;
                items.push(c);
            });
            if (items[0] && items[0].piso !== this.pisoActivo) this.cambiarPiso(items[0].piso);
            items.forEach(c => this.guardarPosicion(c));
        },
        ordenarPiso() {
            const salas = [...this.planoVisible].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), undefined, { numeric: true }));
            const sep = this.tam + this.paso;
            const cols = Math.floor((this.ancho - this.tam) / sep) + 1;
            const filas = Math.floor((this.alto - this.tam) / sep) + 1;
            if (salas.length > cols * filas) { avisoConsultorios('warning', 'Hay demasiados consultorios en este piso para ordenarlos en cuadrícula.'); return; }

            const antes = salas.map(c => ({ id: c.id, x: c.x, y: c.y }));
            const cambiados = [];
            salas.forEach((c, i) => {
                const x = 20 + (i % cols) * sep, y = 20 + Math.floor(i / cols) * sep;
                const nx = Math.min(x, this.maxX), ny = Math.min(y, this.maxY);
                if (c.x !== nx || c.y !== ny) { c.x = nx; c.y = ny; cambiados.push(c); }
            });
            if (!cambiados.length) { avisoConsultorios('info', 'Los consultorios de este piso ya están ordenados', 2500); return; }
            this.registrarHistorial(antes);
            cambiados.forEach(c => this.guardarPosicion(c, true));
            avisoConsultorios('success', 'Consultorios ordenados. Puedes deshacerlo con Ctrl + Z.', 4000);
        },

        // =========================================================== GUARDAR POSICIÓN
        guardarEnServidor: true,
        _pendientes: 0,
        marcarGuardado(estado) {
            this.estadoGuardado = estado;
            clearTimeout(this._timerGuardado);
            if (estado === 'ok') this._timerGuardado = setTimeout(() => { if (this.estadoGuardado === 'ok') this.estadoGuardado = ''; }, 2500);
        },
        async guardarPosicion(item, silencioso = false) {
            if (!this.guardarEnServidor) { this.guardarLocal(item); this.marcarGuardado('local'); return; }

            this._pendientes++;
            this.marcarGuardado('guardando');
            let fallo = null;
            try {
                const { ok, status, res, mensaje } = await peticion(`${URL_CONSULTORIOS}/${item.id}/posicion`, 'PATCH', { pos_x: item.x, pos_y: item.y });
                if (!ok) fallo = { status, message: mensaje };
                else if (res.pos_x !== undefined && (Number(res.pos_x) !== item.x || Number(res.pos_y) !== item.y)) {
                    fallo = { custom: true, message: 'El servidor guardó otra posición (' + res.pos_x + ', ' + res.pos_y + ')' };
                } else {
                    item.guardado = true;
                    this.borrarLocal(item.id);
                }
            } catch (e) {
                fallo = {};
            }
            this._pendientes--;

            if (fallo) {
                this.guardarLocal(item);
                this.marcarGuardado('local');
                if (silencioso && this._pendientes > 0) return;
                let causa = 'No se pudo conectar con el servidor';
                if (fallo.custom) causa = fallo.message;
                else if (fallo.status === 404) causa = 'Error 404: falta la ruta PATCH /consultorios/{id}/posicion';
                else if (fallo.status === 405) causa = 'Error 405: la ruta de posición no acepta PATCH';
                else if (fallo.status === 419) causa = 'Tu sesión expiró, recarga la página';
                else if (fallo.status === 422) causa = 'Datos no válidos: ' + (fallo.message || '');
                else if (fallo.status) causa = 'Error ' + fallo.status + (fallo.message ? ': ' + fallo.message : '');
                avisoConsultorios('warning', causa + '. Se guardó solo en este navegador.', 6000);
            } else if (this._pendientes === 0 && this.estadoGuardado !== 'local') {
                this.marcarGuardado('ok');
            }
        },
        guardarLocal(item) {
            try {
                const locales = JSON.parse(localStorage.getItem('plano_consultorios') || '{}');
                locales[item.id] = { x: item.x, y: item.y };
                localStorage.setItem('plano_consultorios', JSON.stringify(locales));
            } catch (e) {}
        },
        borrarLocal(id) {
            try {
                const locales = JSON.parse(localStorage.getItem('plano_consultorios') || '{}');
                delete locales[id];
                localStorage.setItem('plano_consultorios', JSON.stringify(locales));
            } catch (e) {}
        },

        // =========================================================== INFORMACIÓN
        verInfo(item) {
            this.infoActual = {
                id: item.id, clinica_id: item.clinica_id, nombre: item.nombre, piso: item.piso,
                estado: item.estado, creado: item.creado, ocupadoPor: item.ocupadoPor || null
            };
            this.openInfo = true;
        },
        editarDesdeInfo() { this.openInfo = false; this.abrirModalEditar(this.infoActual.id); },
        eliminarDesdeInfo() { this.openInfo = false; this.eliminarConsultorio(this.infoActual.id); },

        // Cambia entre Disponible y Mantenimiento sin recargar la página
        async cambiarEstadoRapido(estado) {
            const c = this.plano.find(x => x.id === this.infoActual.id);
            if (!c || this.cambiandoEstado) return;
            this.cambiandoEstado = true;
            try {
                const { ok, mensaje } = await peticion(`${URL_CONSULTORIOS}/${c.id}`, 'PUT', { clinica_id: c.clinica_id, nombre: c.nombre, piso: c.piso, estado });
                if (!ok) { avisoConsultorios('warning', mensaje || 'No se pudo cambiar el estado.', 5000); return; }
                c.estado = this.slugEstado(estado);
                this.infoActual.estado = c.estado;
                this.animarResumen();
                this.rebotar(c.id);
                avisoConsultorios('success', c.nombre + ' ahora está ' + estado.toLowerCase());
            } catch (e) {
                avisoConsultorios('error', 'No se pudo comunicar con el servidor');
            } finally {
                this.cambiandoEstado = false;
            }
        },

        // =========================================================== FILTROS
        busqueda: '',
        filtroPiso: 'todos',
        filtroEstado: 'todos',
        normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); },
        coincideTexto(nombre) {
            const q = this.normalizar(this.busqueda.trim());
            return q === '' || this.normalizar(nombre).includes(q);
        },
        coincide(nombre, piso, estado) {
            return this.coincideTexto(nombre)
                && (this.filtroPiso === 'todos' || String(piso) === String(this.filtroPiso))
                && (this.filtroEstado === 'todos' || estado === this.filtroEstado);
        },
        get visibles() { return this.plano.filter(c => this.coincide(c.nombre, c.piso, c.estado)).length; },
        get hayFiltros() { return this.busqueda.trim() !== '' || this.filtroPiso !== 'todos' || this.filtroEstado !== 'todos'; },
        limpiarFiltros() { this.busqueda = ''; this.filtroPiso = 'todos'; this.filtroEstado = 'todos'; },

        // =========================================================== CRUD
        abrirModalCrear() {
            this.modoEdicion = false;
            this.idSeleccionado = null;
            this.form = { clinica_id: this.plano[0]?.clinica_id ?? 1, nombre: '', piso: this.pisoActivo || '1', estado: 'Disponible' };
            this.openModal = true;
        },
        abrirModalEditar(id) {
            const c = this.plano.find(x => x.id === id);
            if (!c) return;
            this.modoEdicion = true;
            this.idSeleccionado = c.id;
            this.form = {
                clinica_id: c.clinica_id, nombre: c.nombre, piso: c.piso,
                estado: c.estado === 'desconocido' ? 'Disponible' : this.estadosInfo[c.estado].texto
            };
            this.openModal = true;
        },
        async guardar() {
            if (this.guardando) return;
            if (!String(this.form.nombre || '').trim()) { avisoConsultorios('warning', 'Escribe el nombre del consultorio.'); return; }
            const nombre = this.normalizar(this.form.nombre.trim());
            const repetido = this.plano.find(c => c.id !== this.idSeleccionado && this.normalizar(c.nombre) === nombre && String(c.piso) === String(this.form.piso));
            if (repetido) { avisoConsultorios('warning', 'Ya existe un consultorio con ese nombre en el mismo piso.'); return; }

            this.guardando = true;
            try {
                const url = this.modoEdicion ? `${URL_CONSULTORIOS}/${this.idSeleccionado}` : URL_CONSULTORIOS;
                const { ok, res, mensaje } = await peticion(url, this.modoEdicion ? 'PUT' : 'POST', this.form);
                if (!ok) {
                    avisoConsultorios('warning', mensaje || 'Revisa que todos los campos requeridos estén llenos.', 5000);
                    this.guardando = false;
                    return;
                }
                this.openModal = false;
                toastYRecargar('success', res.message || 'Consultorio guardado');
            } catch (e) {
                console.error('Error de red:', e);
                avisoConsultorios('error', 'No se pudo comunicar con el servidor');
                this.guardando = false;
            }
        },
        eliminarConsultorio(id) {
            const c = this.plano.find(x => x.id === id);
            this.confirmar = { abierto: true, cargando: false, id, nombre: c ? c.nombre : 'este consultorio', ocupado: c ? c.estado === 'ocupado' : false };
        },
        async confirmarEliminar() {
            if (this.confirmar.cargando) return;
            this.confirmar.cargando = true;
            try {
                const { ok, res, mensaje } = await peticion(`${URL_CONSULTORIOS}/${this.confirmar.id}`, 'DELETE');
                if (!ok) {
                    this.confirmar.abierto = false;
                    this.confirmar.cargando = false;
                    avisoConsultorios('error', mensaje || 'No se pudo eliminar el consultorio.', 6000);
                    return;
                }
                this.confirmar.abierto = false;
                this.borrarLocal(this.confirmar.id);
                toastYRecargar('success', res.message || 'Consultorio eliminado');
            } catch (e) {
                this.confirmar.abierto = false;
                this.confirmar.cargando = false;
                avisoConsultorios('error', 'No se pudo comunicar con el servidor');
            }
        }
    };
    }
</script>
@endsection