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
    $contar = fn ($slug) => $listaConsultorios->filter(fn ($c) => $slugEstado($c) === $slug)->count();

    $totalConsultorios = $listaConsultorios->count();
    $totalDisponibles = $contar('disponible');
    $totalOcupados = $contar('ocupado');
    $totalMantenimiento = $contar('mantenimiento');
    $pisosDisponibles = $listaConsultorios->pluck('piso')
        ->filter(fn ($p) => $p !== null && $p !== '')->unique()->sort()->values();

    // Posición inicial (si aún no tienen pos_x / pos_y guardadas): cuadrícula automática dentro de cada piso.
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
            'x'          => $c->pos_x ?? ($i % $columnasPlano) * 120 + 20,
            'y'          => $c->pos_y ?? intdiv($i, $columnasPlano) * 120 + 20,
        ];
    });
@endphp

<div class="py-6 space-y-6" x-data="moduloConsultorios()">

    <!-- BANNER INSTITUCIONAL -->
    <div class="aparece relative bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 overflow-hidden">
        <div class="flotar absolute -right-10 -top-14 w-44 h-44 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-16 bottom-[-3rem] w-24 h-24 rounded-full bg-white/5" style="animation-delay:-1.5s"></div>

        <div class="space-y-2 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                <i class="bi bi-hospital-fill"></i> Infraestructura médica
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Directorio de consultorios</h1>
            <p class="text-xs text-slate-200 font-medium max-w-xl leading-relaxed">
                Administra las salas, consultorios físicos y pisos disponibles para la asignación estructurada de citas en el sistema.
            </p>
        </div>
        <button type="button" @click="abrirModalCrear()" class="group z-10 bg-white text-teal-900 hover:bg-emerald-50 px-6 py-3 rounded-2xl text-xs font-black shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
            <i class="bi bi-plus-lg text-sm text-teal-700 transition-transform duration-300 group-hover:rotate-90"></i> Registrar consultorio
        </button>
    </div>

    <!-- RESUMEN -->
    @php
        $tarjetas = [
            ['Total',         'total',         'bi-building',   'bg-teal-50 text-teal-600',       'bg-teal-500'],
            ['Disponibles',   'disponible',    'bi-door-open',  'bg-emerald-50 text-emerald-600', 'bg-emerald-500'],
            ['Ocupados',      'ocupado',       'bi-activity',   'bg-rose-50 text-rose-500',       'bg-rose-400'],
            ['Mantenimiento', 'mantenimiento', 'bi-tools',      'bg-amber-50 text-amber-600',     'bg-amber-400'],
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
    </div>

    @if ($listaConsultorios->isNotEmpty())
    <!-- PLANO 3D EDITABLE (un plano por piso) -->
    <div class="aparece bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden" style="--d: 220ms">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <div class="flex items-center gap-2">
                <i class="bi bi-bounding-box-circles text-teal-600"></i>
                <p class="text-sm font-black text-slate-800">Plano del consultorio</p>
            </div>
            <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrows-move"></i> Arrastra para acomodar · haz clic para ver la información
            </span>
            <div class="ml-auto flex flex-wrap items-center gap-3 text-[10px] font-bold text-slate-500">
                <span class="flex items-center gap-1.5 text-emerald-600" title="El estado de los consultorios se actualiza solo">
                    <span class="relative flex w-2 h-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span> En vivo
                </span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:linear-gradient(135deg,#dfb187,#c98f5b)"></span> Disponible</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background:linear-gradient(135deg,#f0c2b4,#e08f7c)"></span> Ocupado</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm border border-amber-400" style="background:repeating-linear-gradient(45deg,#e2e8f0 0 3px,#f1f5f9 3px 6px)"></span> Mantenimiento</span>
                <span class="text-slate-400" x-text="planoVisible.length + (planoVisible.length === 1 ? ' espacio' : ' espacios') + ' en este piso'"></span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row">

            <!-- Selector de piso: el piso más alto queda arriba, como en el edificio -->
            <div class="md:w-44 shrink-0 p-4 border-b md:border-b-0 md:border-r border-slate-100 bg-slate-50/60">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="bi bi-building"></i> Edificio
                </p>
                <div class="flex md:flex-col-reverse gap-2 overflow-x-auto md:overflow-visible pb-1 md:pb-0">
                    <template x-for="p in pisosPlano" :key="p">
                        <button type="button" @click="cambiarPiso(p)"
                                :class="pisoActivo === p
                                    ? 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-600/20'
                                    : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'"
                                class="flex items-center justify-between gap-3 border-2 rounded-2xl px-4 py-2.5 text-xs font-black transition-all active:scale-95 cursor-pointer whitespace-nowrap">
                            <span class="flex items-center gap-2">
                                <i class="bi bi-layers-fill"></i>
                                <span x-text="etiquetaPiso(p)"></span>
                                <span x-show="ocupadosEnPiso(p) > 0" x-cloak class="latido w-1.5 h-1.5 rounded-full"
                                      :class="pisoActivo === p ? 'bg-white' : 'bg-rose-400'"
                                      :title="ocupadosEnPiso(p) + (ocupadosEnPiso(p) === 1 ? ' consultorio en consulta' : ' consultorios en consulta')"></span>
                            </span>
                            <span class="text-[10px] font-bold opacity-70" x-text="contarPiso(p)"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Plano del piso seleccionado -->
            <div class="flex-1 min-w-0 overflow-x-auto bg-gradient-to-br from-slate-100 to-white">
                <div class="relative mx-auto select-none" :class="animando ? 'anim-piso' : ''" style="width:940px; height:740px;">

                    <!-- Rótulo del piso -->
                    <div class="absolute left-6 top-4 pointer-events-none">
                        <p class="text-4xl font-black text-slate-200 leading-none" x-text="etiquetaPiso(pisoActivo)"></p>
                        <p class="text-[10px] font-bold text-slate-300 uppercase tracking-wider mt-1">Plano de planta</p>
                    </div>

                    <!-- Piso isométrico: 720 x 540 px de superficie -->
                    <div class="plano-piso absolute" :style="`left:110px; top:100px; width:720px; height:540px; --tinte:${tinte};`">

                        <!-- Muros exteriores del edificio (lados del fondo) -->
                        <div class="muro-ext-t"></div>
                        <div class="muro-ext-i"></div>
                        <div class="remate-ext-t"></div>
                        <div class="remate-ext-i"></div>

                        <!-- Casilla de destino mientras se arrastra -->
                        <div class="absolute rounded-sm pointer-events-none" :style="estiloDestino()"></div>

                        <template x-for="(c, i) in planoVisible" :key="c.id">
                            <div class="sala"
                                 :data-id="c.id"
                                 :class="['e-' + c.estado, arrastre && arrastre.id === c.id && arrastre.movio ? 'cursor-grabbing' : 'cursor-grab']"
                                 :style="estiloSala(c, i)"
                                 :title="c.nombre + ' · ' + estadosInfo[c.estado].texto + (c.estado === 'ocupado' && c.ocupadoPor ? ' · En consulta con ' + c.ocupadoPor : '')"
                                 @pointerdown="iniciarArrastre($event, c)"
                                 @pointermove="mover($event, c)"
                                 @pointerup="soltar(c)"
                                 @pointercancel="cancelar(c)">
                                <div class="piso"></div>
                                <div class="muro-t"></div>
                                <div class="muro-i"></div>
                                <div class="remate-t"></div>
                                <div class="remate-i"></div>
                                <!-- Etiqueta contra-rotada para que siempre se lea de frente -->
                                <div class="etiqueta-sala">
                                    <div>
                                        <i class="bi" :class="c.estado === 'ocupado' ? 'bi-activity latido' : 'bi-door-open'"></i>
                                        <span x-text="c.nombre"></span>
                                        <small x-text="c.piso ? 'Piso ' + c.piso : ''"></small>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-[10px] text-slate-400 font-semibold px-6 py-4 border-t border-slate-100">
            Cada piso tiene su propio plano. Los cambios de posición se guardan automáticamente al soltar cada consultorio.
        </p>
    </div>
    @endif

    <!-- TABLA DE CONSULTORIOS -->
    <div class="aparece bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden" style="--d: 320ms">
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h3 class="text-base font-black text-slate-800">Consultorios registrados</h3>
                <span class="text-xs font-bold px-3 py-1 bg-teal-50 text-teal-700 rounded-full">{{ $totalConsultorios }} en total</span>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[11px]"></i>
                    <input type="text" x-model="busqueda" placeholder="Buscar por nombre..."
                           class="bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-xs font-medium outline-none focus:border-teal-500 focus:bg-white transition-all w-44">
                </div>
                <select x-model="filtroPiso" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                    <option value="todos">Todos los pisos</option>
                    @foreach ($pisosDisponibles as $p)
                        <option value="{{ $p }}">Piso {{ $p }}</option>
                    @endforeach
                </select>
                <select x-model="filtroEstado" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                    <option value="todos">Todos los estados</option>
                    <option value="disponible">Disponible</option>
                    <option value="ocupado">Ocupado</option>
                    <option value="mantenimiento">Mantenimiento</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Nombre del consultorio</th>
                        <th class="py-4 px-6">Piso</th>
                        <th class="py-4 px-6">Estado</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-bold text-slate-700">
                    @forelse($listaConsultorios as $c)
                        @php $slug = $slugEstado($c); @endphp
                        <tr x-show="coincide(@js($c->nombre), @js((string) ($c->piso ?? '')), estadoDe({{ $c->id }}))"
                            x-transition
                            style="--d: {{ min($loop->index * 45, 500) }}ms"
                            class="fila-in hover:bg-slate-50/60 transition-colors">
                            <td class="py-4 px-6 text-slate-400">#{{ $c->id }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2 text-slate-900 font-black">
                                    <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                                        <i class="bi bi-door-open text-xs"></i>
                                    </span>
                                    {{ $c->nombre }}
                                </div>
                            </td>
                            <td class="py-4 px-6">{{ ($c->piso !== null && $c->piso !== '') ? 'Piso ' . $c->piso : '—' }}</td>
                            <td class="py-4 px-6">
                                <span :class="'px-2.5 py-1 rounded-full text-[10px] font-black border ' + estadosInfo[estadoDe({{ $c->id }})].badge"
                                      x-text="estadosInfo[estadoDe({{ $c->id }})].texto">{{ $estadosCfg[$slug]['texto'] }}</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-1.5">
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
                            <td colspan="5" class="py-12 text-center">
                                <span class="flotar inline-flex w-14 h-14 rounded-2xl bg-teal-50 text-teal-500 items-center justify-center text-2xl mb-3"><i class="bi bi-door-open"></i></span>
                                <p class="text-xs font-black text-slate-500">Aún no hay consultorios</p>
                                <p class="text-[11px] font-medium text-slate-400 mt-0.5">Crea el primero con el botón "Registrar consultorio".</p>
                            </td>
                        </tr>
                    @endforelse

                    @if ($listaConsultorios->isNotEmpty())
                        <tr x-show="visibles === 0" x-cloak>
                            <td colspan="5" class="py-12 text-center">
                                <span class="flotar inline-flex w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 items-center justify-center text-2xl mb-3"><i class="bi bi-search"></i></span>
                                <p class="text-xs font-black text-slate-500">Sin resultados</p>
                                <p class="text-[11px] font-medium text-slate-400 mt-0.5">Ningún consultorio coincide con los filtros.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL DE INFORMACIÓN DEL CONSULTORIO (clic en el plano) -->
    <template x-teleport="body">
        <div x-show="openInfo" x-cloak
             @keydown.escape.window="openInfo = false"
             class="fixed inset-0 z-[99998] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden" @click.outside="openInfo = false"
                 x-show="openInfo"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-6 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="relative px-7 pt-7 pb-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white overflow-hidden">
                    <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                    <button type="button" @click="openInfo = false"
                            class="absolute right-4 top-4 w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                    <div class="relative flex items-center gap-4">
                        <span class="w-14 h-14 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-2xl text-emerald-300 shrink-0" :class="openInfo && 'icono-pop'">
                            <i class="bi bi-door-open-fill"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Consultorio</p>
                            <h3 class="text-base font-black tracking-tight truncate" x-text="infoActual.nombre"></h3>
                        </div>
                    </div>
                </div>

                <div class="p-7 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openInfo && 'reveal-on'" style="--d:1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Piso</p>
                            <p class="text-sm font-black text-slate-800 mt-0.5" x-text="infoActual.piso ? 'Piso ' + infoActual.piso : '—'"></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openInfo && 'reveal-on'" style="--d:2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Identificador</p>
                            <p class="text-sm font-black text-slate-800 mt-0.5" x-text="'#' + infoActual.id"></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" :class="openInfo && 'reveal-on'" style="--d:3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Estado</p>
                            <span class="inline-flex items-center gap-1.5 mt-1 px-2.5 py-1 rounded-full text-[10px] font-black border"
                                  :class="estadosInfo[infoActual.estado].badge">
                                <span class="w-1.5 h-1.5 rounded-full" :class="estadosInfo[infoActual.estado].punto"></span>
                                <span x-text="estadosInfo[infoActual.estado].texto"></span>
                            </span>
                        </div>
                        <div class="rounded-2xl bg-rose-50/60 border border-rose-100 p-3 col-span-2 reveal-item" :class="openInfo && 'reveal-on'" style="--d:4" x-show="infoActual.estado === 'ocupado' && infoActual.ocupadoPor" x-cloak>
                            <p class="text-[10px] font-bold text-rose-400 uppercase tracking-wide flex items-center gap-1.5">
                                <i class="bi bi-activity"></i> En consulta ahora
                            </p>
                            <p class="text-sm font-black text-slate-800 mt-0.5" x-text="infoActual.ocupadoPor"></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 reveal-item" x-show="infoActual.creado" :class="openInfo && 'reveal-on'" style="--d:5">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Registrado</p>
                            <p class="text-sm font-black text-slate-800 mt-0.5" x-text="infoActual.creado"></p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" @click="openInfo = false"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                            Cerrar
                        </button>
                        <button type="button" @click="eliminarDesdeInfo()"
                                class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-trash-fill"></i> Eliminar
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
                <h3 class="text-base font-black text-slate-800">¿Eliminar este consultorio?</h3>
                <p class="text-xs font-medium text-slate-500 mt-2 leading-relaxed">
                    Se eliminará <strong class="text-slate-700" x-text="confirmar.nombre"></strong> del sistema.
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

    <!-- INCLUIMOS EL MODAL DESDE ARCHIVO APARTE -->
    @include('consultorios.modal')

</div>

<style>
    /* ============ Animaciones ============
       Los estados finales quedan en "none" y se usa fill-mode "backwards" para que
       los efectos hover (translate/scale) sigan funcionando después de la entrada. */
    @keyframes fadeIn   { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes filaIn   { from { opacity: 0; transform: translateX(-12px); } to { opacity: 1; transform: none; } }
    @keyframes iconoPop { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes latido   { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .6; } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes barraToast { from { width: 100%; } to { width: 0; } }

    /* Consultorios en 3D: caen sobre el piso al aparecer (el rebote al cambiar de estado se hace desde JS) */
    @keyframes salaCae { from { transform: translateZ(110px); } to { transform: translateZ(0); } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .fila-in      { animation: filaIn .45s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .icono-pop    { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .latido       { animation: latido 1.4s ease-in-out infinite; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }

    /* Elementos de los modales que aparecen escalonados cada vez que se abren */
    .reveal-item { opacity: 0; }
    .reveal-on   { animation: fadeUp .45s cubic-bezier(.16, 1, .3, 1) both; animation-delay: calc(var(--d, 0) * 70ms); }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .fila-in, .icono-pop, .latido, .flotar, .reveal-on, .anim-piso, .sala {
            animation: none !important;
        }
        .reveal-item { opacity: 1 !important; }
    }

    /* Transición al cambiar de piso (solo opacidad/posición del contenedor, sin afectar el 3D) */
    @keyframes cambioPiso {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .anim-piso { animation: cambioPiso .4s cubic-bezier(0.16, 1, 0.3, 1); }

    /* ---------- Plano isométrico ---------- */
    .plano-piso {
        transform: rotateX(52deg) rotateZ(45deg);
        transform-style: preserve-3d;
        background-color: var(--tinte, #eef2f6);
        background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
        background-size: 20px 20px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        box-shadow: 0 10px 0 rgba(15, 23, 42, 0.08);
    }

    /* Muros exteriores del edificio (alto 70px) */
    .muro-ext-t, .muro-ext-i, .remate-ext-t, .remate-ext-i { position: absolute; pointer-events: none; }
    .muro-ext-t { left: 0; top: 0; width: 100%; height: 70px; background: #ffffff; transform-origin: 0 0; transform: rotateX(90deg); }
    .muro-ext-i { left: 0; top: 0; width: 70px; height: 100%; background: #e2e8f0; transform-origin: 0 0; transform: rotateY(-90deg); }
    .remate-ext-t { left: -8px; top: -8px; width: calc(100% + 8px); height: 8px; background: #0f172a; transform: translateZ(70px); }
    .remate-ext-i { left: -8px; top: -8px; width: 8px; height: calc(100% + 8px); background: #0f172a; transform: translateZ(70px); }

    /* Consultorio = cuarto con piso y dos muros (vista en corte, como una maqueta) */
    .sala { position: absolute; transform-style: preserve-3d; touch-action: none; animation: salaCae .6s cubic-bezier(.34, 1.3, .64, 1) backwards; animation-delay: var(--d, 0ms); }
    .sala:not(.cursor-grabbing):hover { transform: translateZ(10px) !important; }   /* se levanta al pasar el cursor */
    .sala > * { position: absolute; transform-style: preserve-3d; }

    /* Piso según estado */
    .sala .piso { inset: 0; background: linear-gradient(135deg, #dfb187, #c98f5b); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08); }
    .sala.e-ocupado .piso { background: linear-gradient(135deg, #f0c2b4, #e08f7c); }
    .sala.e-mantenimiento .piso,
    .sala.e-desconocido .piso { background: repeating-linear-gradient(45deg, #e2e8f0 0 8px, #f1f5f9 8px 16px); }

    .sala .muro-t { left: 0; top: 0; width: 100%; height: 56px; background: #ffffff; transform-origin: 0 0; transform: rotateX(90deg); }
    .sala .muro-i { left: 0; top: 0; width: 56px; height: 100%; background: #e2e8f0; transform-origin: 0 0; transform: rotateY(-90deg); }

    /* Remate oscuro de los muros según estado */
    .sala .remate-t { left: -5px; top: -5px; width: calc(100% + 5px); height: 5px; background: #1e293b; transform: translateZ(56px); }
    .sala .remate-i { left: -5px; top: -5px; width: 5px; height: calc(100% + 5px); background: #1e293b; transform: translateZ(56px); }
    .sala.e-ocupado .remate-t, .sala.e-ocupado .remate-i { background: #fb7185; }
    .sala.e-mantenimiento .remate-t, .sala.e-mantenimiento .remate-i { background: #f59e0b; }
    .sala.e-desconocido .remate-t, .sala.e-desconocido .remate-i { background: #94a3b8; }

    /* Etiqueta */
    .sala .etiqueta-sala { inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; transform: translateZ(1px); }
    .sala .etiqueta-sala > div {
        /* Se levanta sobre el piso: girada sobre su centro, la mitad de abajo quedaba bajo el suelo y se cortaba */
        transform: translateZ(30px) rotateZ(-45deg) rotateX(-52deg);
        display: flex; flex-direction: column; align-items: center; gap: 1px;
        max-width: 92px; text-align: center; line-height: 1.15;
        background: rgba(255, 255, 255, 0.92);
        border-radius: 8px; padding: 4px 7px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.25);
    }
    .sala .etiqueta-sala i { font-size: 14px; color: #7c2d12; }
    .sala.e-ocupado .etiqueta-sala i { color: #e11d48; }
    .sala .etiqueta-sala span { font-size: 11px; font-weight: 900; color: #431407; word-break: break-word; }
    .sala .etiqueta-sala small { font-size: 9px; font-weight: 700; color: #92400e; opacity: .85; }
    .sala.e-mantenimiento .etiqueta-sala i, .sala.e-desconocido .etiqueta-sala i { color: #94a3b8; }
    .sala.e-mantenimiento .etiqueta-sala span, .sala.e-desconocido .etiqueta-sala span { color: #64748b; }
    .sala.e-mantenimiento .etiqueta-sala small, .sala.e-desconocido .etiqueta-sala small { color: #94a3b8; }
</style>

<script>
    // Notificación flotante (esquina superior derecha). Se dibuja en el componente, por encima de los modales.
    function toast(icon, title, ms = 3500) {
        window.dispatchEvent(new CustomEvent('toast-consultorio', { detail: { icon, title, ms } }));
    }

    // Muestra el toast después de recargar la página
    function toastYRecargar(icon, title) {
        sessionStorage.setItem('toast_consultorios', JSON.stringify({ icon, title }));
        location.reload();
    }

    function moduloConsultorios() {
    return {
        openModal: false,
        modoEdicion: false,
        idSeleccionado: null,
        form: { clinica_id: 1, nombre: '', piso: '1', estado: 'Disponible' },

        // ----- Estados (coinciden con el enum de la BD) -----
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

        // ----- Información del consultorio (clic en el plano) -----
        openInfo: false,
        infoActual: { id: '', clinica_id: 1, nombre: '', piso: '', estado: 'disponible', creado: null, ocupadoPor: null },

        // ----- Plano 3D arrastrable -----
        plano: @json($datosPlano),
        tam: 100,       // tamaño de cada consultorio (px, en el plano)
        paso: 20,       // cuadrícula de imantado
        ancho: 720,     // superficie del piso
        alto: 540,
        anguloZ: 45,    // mismos ángulos que el CSS del piso
        anguloX: 52,
        arrastre: null,

        // ----- Resumen con números animados -----
        resumenMostrado: { total: 0, disponible: 0, ocupado: 0, mantenimiento: 0 },
        animacionesResumen: {},

        // ----- Confirmación de eliminar -----
        confirmar: { abierto: false, cargando: false, id: null, nombre: '' },

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

        // Los números del resumen suben o bajan hasta su valor
        animarResumen() {
            const sinMov = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            ['total', 'disponible', 'ocupado', 'mantenimiento'].forEach(k => {
                const destino = k === 'total' ? this.plano.length : this.contar(k);
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

        // Rebote 3D del cuarto (API de animaciones: no interfiere con la animación de entrada en CSS)
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

        // Consultorios de un piso que están en consulta en este momento
        ocupadosEnPiso(p) {
            return this.plano.filter(c => c.piso === p && c.estado === 'ocupado').length;
        },

        // ----- Un plano por piso -----
        pisoActivo: '',
        animando: false,
        tintes: ['#eef2f6', '#ecf7f1', '#f3eefb', '#fdf3e7', '#eaf3fb'],   // cada piso con un tono distinto

        get pisosPlano() {
            return [...new Set(this.plano.map(c => c.piso))]
                .sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
        },
        get planoVisible() {
            return this.plano.filter(c => c.piso === this.pisoActivo);
        },
        get tinte() {
            const i = Math.max(0, this.pisosPlano.indexOf(this.pisoActivo));
            return this.tintes[i % this.tintes.length];
        },
        etiquetaPiso(p) { return p === '' ? 'Sin piso' : 'Piso ' + p; },
        contarPiso(p) { return this.plano.filter(c => c.piso === p).length; },
        cambiarPiso(p) {
            if (this.arrastre) return;
            this.pisoActivo = p;
            try { sessionStorage.setItem('piso_activo_consultorios', p); } catch (e) {}
            this.animando = false;
            this.$nextTick(() => { this.animando = true; });
        },

        // true = guarda pos_x / pos_y en la BD (ruta PATCH /consultorios/{id}/posicion).
        // false = solo en este navegador.
        guardarEnServidor: true,

        // ----- Estado en vivo: un consultorio figura "Ocupado" mientras un médico lo usa en una consulta -----
        estadoDe(id) {
            const c = this.plano.find(x => x.id === id);
            return c ? c.estado : 'desconocido';
        },
        contar(slug) {
            return this.plano.filter(c => c.estado === slug).length;
        },
        slugEstado(txt) {
            const v = String(txt || '').trim().toLowerCase();
            return ['disponible', 'ocupado', 'mantenimiento'].includes(v) ? v : 'desconocido';
        },
        async sincronizarEstados() {
            try {
                const r = await fetch('{{ url('consultorios/estados') }}', { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const lista = await r.json();
                let huboCambios = false;

                lista.forEach(e => {
                    const c = this.plano.find(x => x.id === e.id);
                    if (!c) return;

                    const nuevo = this.slugEstado(e.estado);
                    if (c.estado !== nuevo) {
                        c.estado = nuevo;
                        huboCambios = true;
                        this.rebotar(c.id);   // el cuarto rebota un momento para llamar la atención
                        toast(nuevo === 'ocupado' ? 'info' : 'success',
                              c.nombre + ' ahora está ' + this.estadosInfo[nuevo].texto.toLowerCase(), 4000);
                    }
                    c.ocupadoPor = e.ocupado_por || null;
                });

                if (huboCambios) this.animarResumen();
            } catch (err) { /* se reintenta en el siguiente ciclo */ }
        },

        init() {
            window.addEventListener('toast-consultorio', e => this.agregarToast(e.detail));
            this.animarResumen();

            // Estado en vivo de los consultorios (cada 15 s, solo con la pestaña visible)
            this.sincronizarEstados();
            setInterval(() => { if (!document.hidden && !this.arrastre) this.sincronizarEstados(); }, 15000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) this.sincronizarEstados(); });

            // Piso que se muestra al abrir: el último que se estaba viendo, o el más bajo
            let guardado = null;
            try { guardado = sessionStorage.getItem('piso_activo_consultorios'); } catch (e) {}
            this.pisoActivo = (guardado !== null && this.pisosPlano.includes(guardado))
                ? guardado
                : (this.pisosPlano[0] ?? '');

            // Toast pendiente tras recargar
            const pendiente = sessionStorage.getItem('toast_consultorios');
            if (pendiente) {
                sessionStorage.removeItem('toast_consultorios');
                const t = JSON.parse(pendiente);
                this.$nextTick(() => toast(t.icon, t.title));
            }

            // Posiciones guardadas solo en este navegador (cuando el servidor no las pudo guardar)
            try {
                const locales = JSON.parse(localStorage.getItem('plano_consultorios') || '{}');
                this.plano.forEach(c => {
                    if (!c.guardado && locales[c.id]) {
                        c.x = locales[c.id].x;
                        c.y = locales[c.id].y;
                    }
                });
            } catch (e) { /* sin almacenamiento local */ }
        },

        get maxX() { return Math.floor((this.ancho - this.tam) / this.paso) * this.paso; },
        get maxY() { return Math.floor((this.alto - this.tam) / this.paso) * this.paso; },
        snap(v, max) { return Math.max(0, Math.min(max, Math.round(v / this.paso) * this.paso)); },

        estiloSala(c, i = 0) {
            const alzado = this.arrastre && this.arrastre.id === c.id && this.arrastre.movio;
            return `left:${c.x}px; top:${c.y}px; width:${this.tam}px; height:${this.tam}px; --d:${i * 60}ms;`
                 + `transform:${alzado ? 'translateZ(22px)' : 'translateZ(0)'};`
                 + `z-index:${alzado ? 20 : 10};`
                 + `transition:${alzado ? 'none' : 'left .15s, top .15s, transform .15s'};`;
        },

        // Casilla que indica dónde caerá el consultorio al soltarlo
        estiloDestino() {
            const a = this.arrastre;
            if (!a || !a.movio) return 'display:none;';
            const color = a.choque ? '244,63,94' : '13,148,136';
            return `left:${a.sx}px; top:${a.sy}px; width:${this.tam}px; height:${this.tam}px;`
                 + `background:rgba(${color},0.18); border:2px dashed rgba(${color},0.8);`;
        },

        iniciarArrastre(e, item) {
            e.currentTarget.setPointerCapture(e.pointerId);
            this.arrastre = { id: item.id, cx: e.clientX, cy: e.clientY, x0: item.x, y0: item.y, movio: false, sx: item.x, sy: item.y, choque: false };
        },

        // Convierte el movimiento del puntero en pantalla al movimiento sobre el plano inclinado
        mover(e, item) {
            const a = this.arrastre;
            if (!a || a.id !== item.id) return;

            // Un desplazamiento pequeño se considera clic, no arrastre
            if (!a.movio) {
                if (Math.hypot(e.clientX - a.cx, e.clientY - a.cy) < 5) return;
                a.movio = true;
            }

            const t = this.anguloZ * Math.PI / 180;
            const f = this.anguloX * Math.PI / 180;
            const u = e.clientX - a.cx;
            const v = (e.clientY - a.cy) / Math.cos(f);
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

            // Fue un clic: mostrar información
            if (!a.movio) {
                this.verInfo(item);
                return;
            }

            if (a.choque) {
                item.x = a.x0;
                item.y = a.y0;
                toast('warning', 'Ese espacio ya está ocupado por otro consultorio');
                return;
            }

            item.x = a.sx;
            item.y = a.sy;
            if (item.x !== a.x0 || item.y !== a.y0) this.guardarPosicion(item);
        },

        cancelar(item) {
            const a = this.arrastre;
            if (!a || a.id !== item.id) return;
            item.x = a.x0;
            item.y = a.y0;
            this.arrastre = null;
        },

        hayColision(id, x, y) {
            const yo = this.plano.find(o => o.id === id);
            return this.plano.some(o => o.id !== id && o.piso === yo.piso &&
                Math.abs(o.x - x) < this.tam && Math.abs(o.y - y) < this.tam);
        },

        guardarPosicion(item) {
            if (!this.guardarEnServidor) {
                this.guardarLocal(item);
                toast('success', 'Posición guardada en este navegador');
                return;
            }

            fetch(`/consultorios/${item.id}/posicion`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ pos_x: item.x, pos_y: item.y })
            })
            .then(async r => {
                const res = await r.json().catch(() => ({}));
                if (!r.ok) {
                    throw { status: r.status, message: res.message };
                }
                // Si el servidor devuelve lo guardado, se compara; si no lo devuelve, se acepta la respuesta OK
                if (res.pos_x !== undefined && (Number(res.pos_x) !== item.x || Number(res.pos_y) !== item.y)) {
                    throw { custom: true, message: 'El servidor respondió OK pero guardó otra posición (' + res.pos_x + ', ' + res.pos_y + ')' };
                }
                item.guardado = true;
                this.borrarLocal(item.id);
                toast('success', 'Posición guardada');
            })
            .catch(err => {
                // El bloque se queda donde lo dejó el usuario; se guarda una copia local
                this.guardarLocal(item);
                let causa = 'No se pudo conectar con el servidor';
                if (err && err.custom) causa = err.message;
                else if (err && err.status === 404) causa = 'Error 404: falta la ruta PATCH /consultorios/{id}/posicion';
                else if (err && err.status === 405) causa = 'Error 405: la ruta de posición no acepta PATCH';
                else if (err && err.status === 419) causa = 'Error 419: la sesión/CSRF expiró, recarga la página';
                else if (err && err.status === 422) causa = 'Error 422: ' + (err.message || 'datos no válidos');
                else if (err && err.status) causa = 'Error ' + err.status + (err.message ? ': ' + err.message : ' en el servidor (revisa storage/logs/laravel.log)');
                toast('warning', causa + '. Se guardó solo en este navegador.', 6000);
            });
        },

        guardarLocal(item) {
            try {
                const locales = JSON.parse(localStorage.getItem('plano_consultorios') || '{}');
                locales[item.id] = { x: item.x, y: item.y };
                localStorage.setItem('plano_consultorios', JSON.stringify(locales));
            } catch (e) { /* sin almacenamiento local */ }
        },

        borrarLocal(id) {
            try {
                const locales = JSON.parse(localStorage.getItem('plano_consultorios') || '{}');
                delete locales[id];
                localStorage.setItem('plano_consultorios', JSON.stringify(locales));
            } catch (e) { /* sin almacenamiento local */ }
        },

        // ----- Información del consultorio -----
        verInfo(item) {
            this.infoActual = {
                id: item.id,
                clinica_id: item.clinica_id,
                nombre: item.nombre,
                piso: item.piso,
                estado: item.estado,
                creado: item.creado,
                ocupadoPor: item.ocupadoPor || null
            };
            this.openInfo = true;
        },

        editarDesdeInfo() {
            this.openInfo = false;
            this.abrirModalEditar(this.infoActual.id);
        },

        eliminarDesdeInfo() {
            this.openInfo = false;
            this.eliminarConsultorio(this.infoActual.id);
        },

        // ----- Filtros de la tabla -----
        busqueda: '',
        filtroPiso: 'todos',
        filtroEstado: 'todos',

        coincide(nombre, piso, estado) {
            const texto = this.busqueda.trim().toLowerCase();
            const pasaTexto = texto === '' || nombre.toLowerCase().includes(texto);
            const pasaPiso = this.filtroPiso === 'todos' || String(piso) === String(this.filtroPiso);
            const pasaEstado = this.filtroEstado === 'todos' || estado === this.filtroEstado;
            return pasaTexto && pasaPiso && pasaEstado;
        },

        get visibles() {
            return this.plano.filter(c => this.coincide(c.nombre, c.piso, c.estado)).length;
        },

        // ----- CRUD -----
        abrirModalCrear() {
            this.modoEdicion = false;
            this.form = { clinica_id: 1, nombre: '', piso: '1', estado: 'Disponible' };
            this.openModal = true;
        },

        // Recibe el id del consultorio y toma sus datos del plano
        abrirModalEditar(id) {
            const c = this.plano.find(x => x.id === id);
            if (!c) return;
            this.modoEdicion = true;
            this.idSeleccionado = c.id;
            this.form = {
                clinica_id: c.clinica_id,
                nombre: c.nombre,
                piso: c.piso,
                estado: c.estado === 'desconocido' ? 'Disponible' : this.estadosInfo[c.estado].texto
            };
            this.openModal = true;
        },

        guardar() {
            let url = this.modoEdicion ? `/consultorios/${this.idSeleccionado}` : '/consultorios';
            let method = this.modoEdicion ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.form)
            })
            .then(async response => {
                let res = await response.json().catch(() => ({}));

                if (!response.ok) {
                    let mensaje = res.message || 'Revisa que todos los campos requeridos estén llenos.';
                    if (res.errors) {
                        let primerCampo = Object.keys(res.errors)[0];
                        mensaje = res.errors[primerCampo][0];
                    }
                    toast('warning', mensaje, 5000);
                    return;
                }

                this.openModal = false;
                toastYRecargar('success', res.message || 'Consultorio guardado');
            })
            .catch(error => {
                console.error('Error de red:', error);
                toast('error', 'No se pudo comunicar con el servidor');
            });
        },

        eliminarConsultorio(id) {
            const c = this.plano.find(x => x.id === id);
            this.confirmar = { abierto: true, cargando: false, id, nombre: c ? c.nombre : 'este consultorio' };
        },

        async confirmarEliminar() {
            if (this.confirmar.cargando) return;
            this.confirmar.cargando = true;

            try {
                const r = await fetch(`/consultorios/${this.confirmar.id}`, {
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
                    toast('error', res.message || 'No se pudo eliminar el consultorio.', 6000);
                    return;
                }

                this.confirmar.abierto = false;
                toastYRecargar('success', res.message || 'Consultorio eliminado');
            } catch (e) {
                this.confirmar.abierto = false;
                this.confirmar.cargando = false;
                toast('error', 'No se pudo comunicar con el servidor');
            }
        }
    }
    }
</script>
@endsection