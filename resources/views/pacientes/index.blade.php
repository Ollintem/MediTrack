@extends('layouts.admin')

@section('content')
@php
    $pacientes = $pacientes ?? collect();
    // Relaciones cargadas una sola vez (antes se cargaban por cada fila)
    if ($pacientes instanceof \Illuminate\Database\Eloquent\Collection) {
        $pacientes->loadMissing(['alergias', 'condiciones', 'medicamentos']);
    }

    $puedeCrear    = auth()->user()->tienePermiso('Pacientes', 'crear');
    $puedeEditar   = auth()->user()->tienePermiso('Pacientes', 'editar');
    $puedeEliminar = auth()->user()->tienePermiso('Pacientes', 'eliminar');

    $pacientesJs = $pacientes->map(function ($p) {
        $nombre = trim(implode(' ', array_filter([$p->primer_nombre ?? null, $p->segundo_nombre ?? null, $p->apellido_paterno ?? null, $p->apellido_materno ?? null])))
               ?: ($p->nombre_completo ?? $p->nombre ?? 'Sin nombre');

        $nacimiento = null; $edad = null;
        if (!empty($p->fecha_nacimiento)) {
            try {
                $f = \Illuminate\Support\Carbon::parse($p->fecha_nacimiento);
                $nacimiento = $f->format('Y-m-d');
                $edad = $f->age;
            } catch (\Throwable $e) {}
        }

        $faltantes = [];
        if (empty($p->rut)) $faltantes[] = 'CURP';
        if (!$p->acepta_aviso_privacidad) $faltantes[] = 'Aviso de privacidad';
        if (empty($p->telefono) && empty($p->celular)) $faltantes[] = 'Teléfono';

        $alergias     = collect($p->alergias ?? [])->pluck('descripcion')->filter()->values();
        $condiciones  = collect($p->condiciones ?? [])->pluck('descripcion')->filter()->values();
        $medicamentos = collect($p->medicamentos ?? [])->pluck('nombre')->filter()->values();

        return [
            'id'                => $p->id,
            'nombre'            => $nombre,
            'codigo'            => $p->codigo ?? null,
            'curp'              => $p->rut ?: null,
            'genero'            => $p->genero ?: null,
            'grupo_sanguineo'   => $p->grupo_sanguineo ?: null,
            'fecha_nacimiento'  => $nacimiento,
            'edad'              => $edad,
            'estado_civil'      => $p->estado_civil ?? null,
            'nacionalidad'      => $p->nacionalidad ?? null,
            'estado'            => $p->estado ?: 'Activo',
            'direccion'         => $p->direccion ?? null,
            'telefono'          => $p->telefono ?: null,
            'celular'           => $p->celular ?? null,
            'email'             => $p->email ?: null,
            'emerg_nombre'      => $p->contacto_emerg_nombre ?? null,
            'emerg_relacion'    => $p->contacto_emerg_relacion ?? null,
            'emerg_telefono'    => $p->contacto_emerg_telefono ?? null,
            'aviso'             => (bool) $p->acepta_aviso_privacidad,
            'alergias'          => $alergias,
            'condiciones'       => $condiciones,
            'medicamentos'      => $medicamentos,
            'faltantes'         => $faltantes,
            'buscar'            => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(implode(' ', array_filter([
                $nombre, $p->codigo ?? null, $p->rut, $p->telefono, $p->celular ?? null, $p->email,
                $alergias->implode(' '), $condiciones->implode(' '),
            ])))),
        ];
    })->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();

    $tarjetas = [
        // título, clave (también filtro), icono, color
        ['Pacientes',           'todos',        'bi-people-fill',          'bg-sky-50 text-sky-600'],
        ['Activos',             'activos',      'bi-person-check-fill',    'bg-emerald-50 text-emerald-600'],
        ['Con alergias',        'alergias',     'bi-exclamation-triangle-fill', 'bg-rose-50 text-rose-500'],
        ['Expedientes incompletos', 'incompletos', 'bi-shield-exclamation', 'bg-amber-50 text-amber-600'],
    ];
@endphp

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="space-y-6 w-full block" x-data="moduloPacientes()">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-48 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        <svg class="absolute inset-x-0 bottom-2 w-full h-12 opacity-25 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        <i class="bi bi-clipboard2-pulse-fill flotar absolute right-14 top-6 text-[100px] leading-none text-white/10 pointer-events-none hidden md:block" style="animation-delay:-1s"></i>

        <div class="relative p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wide text-emerald-300 backdrop-blur-md">
                    <i class="bi bi-heart-pulse-fill latido-lento"></i> Expedientes clínicos
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-3">
                    Pacientes <i class="bi bi-heart-pulse-fill text-emerald-300 text-2xl latido-lento"></i>
                </h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Consulta y administra las fichas médicas: alergias, condiciones preexistentes, medicamentos y datos de contacto.
                </p>
            </div>
            @if ($puedeCrear)
                <button @click="openCreateModal = true" type="button"
                        class="group shrink-0 bg-white text-teal-900 hover:bg-emerald-50 font-black px-5 py-3 rounded-2xl text-xs shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
                    <i class="bi bi-person-plus-fill text-sm text-teal-700 transition-transform duration-300 group-hover:scale-110"></i> Nuevo paciente
                </button>
            @endif
        </div>
    </div>

    {{-- ===================== RESUMEN (también filtran) ===================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $color])
            <button type="button" @click="filtro = (filtro === '{{ $clave }}' ? 'todos' : '{{ $clave }}')"
                    class="tarjeta-stat group text-left bg-white rounded-2xl border shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1 cursor-pointer"
                    :class="filtro === '{{ $clave }}' ? 'border-teal-300 ring-2 ring-teal-100' : 'border-slate-100 hover:border-slate-200'"
                    style="animation-delay: {{ $i * 70 }}ms">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $titulo }}</p>
                        <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="resumenMostrado.{{ $clave }}">0</p>
                    </div>
                    <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $color }}">
                        <i class="bi {{ $icono }}"></i>
                    </span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-2"
                   x-text="'{{ $clave }}' === 'todos' ? 'Ver todos' : porcentaje('{{ $clave }}') + '% del total · clic para filtrar'"></p>
            </button>
        @endforeach
    </div>

    {{-- ===================== LISTADO ===================== --}}
    <div class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d: 220ms">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h3 class="text-base font-black text-slate-800">Pacientes registrados</h3>
                    <p class="text-[11px] font-semibold text-slate-400"
                       x-text="filtrados.length === pacientes.length ? pacientes.length + ' en total' : filtrados.length + ' de ' + pacientes.length + ' pacientes'"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative flex-1 md:w-80">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="search" x-ref="buscar" x-model.debounce.150ms="searchQuery" placeholder="Nombre, CURP, teléfono, alergia…"
                           class="w-full pl-9 pr-9 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-semibold focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all">
                    <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-400 border border-slate-200 rounded px-1.5 py-0.5 hidden sm:block" x-show="!searchQuery">/</kbd>
                </div>
                <div class="relative flex bg-slate-100 p-1 rounded-2xl shrink-0">
                    <span class="absolute top-1 bottom-1 w-9 rounded-xl bg-white shadow-sm transition-all duration-300" :style="vista === 'lista' ? 'left:4px' : 'left:40px'"></span>
                    <button @click="cambiarVista('lista')" type="button" title="Vista de tabla" class="relative z-10 w-9 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" :class="vista === 'lista' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'">
                        <i class="bi bi-list-task"></i>
                    </button>
                    <button @click="cambiarVista('tarjetas')" type="button" title="Vista de tarjetas" class="relative z-10 w-9 h-8 rounded-xl flex items-center justify-center transition-colors cursor-pointer" :class="vista === 'tarjetas' ? 'text-teal-700' : 'text-slate-400 hover:text-slate-600'">
                        <i class="bi bi-grid-fill"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Aviso de filtro activo --}}
        <div x-show="filtro !== 'todos'" x-cloak x-transition.opacity class="mx-5 sm:mx-6 mt-4 flex items-center gap-2 text-[11px] font-bold bg-teal-50 border border-teal-100 text-teal-800 rounded-2xl px-3.5 py-2">
            <i class="bi bi-funnel-fill"></i>
            <span x-text="'Mostrando: ' + ({ activos: 'pacientes activos', alergias: 'pacientes con alergias', incompletos: 'expedientes incompletos' }[filtro] || '')"></span>
            <button type="button" @click="filtro = 'todos'" class="ml-auto underline underline-offset-2 cursor-pointer">Quitar filtro</button>
        </div>

        {{-- ---------- TABLA ---------- --}}
        <div x-show="vista === 'lista' && filtrados.length" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 text-[10px] text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-5">Paciente</th>
                        <th class="py-3.5 px-5">Identificación</th>
                        <th class="py-3.5 px-5 hidden lg:table-cell">Contacto</th>
                        <th class="py-3.5 px-5">Antecedentes</th>
                        <th class="py-3.5 px-5">Expediente</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    <template x-for="(p, i) in pagina" :key="p.id">
                        <tr class="fila-in group hover:bg-teal-50/30 transition-colors cursor-pointer" :style="'--d:' + Math.min(i * 35, 400) + 'ms'"
                            :class="p._saliendo && 'fila-sale'" @click="verDetalles(p)">
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <span class="relative w-10 h-10 rounded-2xl flex items-center justify-center text-[11px] font-black shrink-0 transition-transform group-hover:scale-110" :class="colorAvatar(p.nombre)">
                                        <span x-text="iniciales(p.nombre)"></span>
                                        <span x-show="p.alergias.length" class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[8px] flex items-center justify-center ring-2 ring-white" title="Tiene alergias">
                                            <i class="bi bi-exclamation"></i>
                                        </span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 text-sm capitalize truncate max-w-[220px]" x-text="p.nombre.toLowerCase()"></p>
                                        <p class="text-[10px] font-bold text-slate-400">
                                            <span x-text="p.edad !== null ? p.edad + ' años' : 'Edad no registrada'"></span>
                                            <span x-show="p.estado !== 'Activo'" class="ml-1 text-slate-500 bg-slate-100 rounded px-1.5" x-text="p.estado"></span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-5">
                                <p class="font-mono font-bold uppercase tracking-wider text-[11px]" :class="p.curp ? 'text-slate-800' : 'text-amber-600'" x-text="p.curp || 'Sin CURP'"></p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">
                                    <span x-text="p.genero || 'Sin género'"></span>
                                    <span x-show="p.grupo_sanguineo" class="ml-1 text-rose-600 bg-rose-50 rounded px-1.5" x-text="p.grupo_sanguineo"></span>
                                </p>
                            </td>
                            <td class="py-3.5 px-5 hidden lg:table-cell" @click.stop>
                                <a x-show="p.telefono || p.celular" :href="'tel:' + (p.celular || p.telefono)" class="flex items-center gap-1.5 hover:text-teal-700 tabular-nums">
                                    <i class="bi bi-telephone text-teal-600"></i><span x-text="p.celular || p.telefono"></span>
                                </a>
                                <p x-show="!(p.telefono || p.celular)" class="text-slate-300 italic">Sin teléfono</p>
                                <p class="text-[10px] text-slate-400 truncate max-w-[170px]" x-show="p.email"><i class="bi bi-envelope"></i> <span x-text="p.email"></span></p>
                            </td>
                            <td class="py-3.5 px-5">
                                <div class="flex flex-wrap gap-1 max-w-[260px]">
                                    <template x-for="a in p.alergias.slice(0, 2)" :key="'a' + a">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 truncate max-w-[120px]">
                                            <i class="bi bi-exclamation-triangle-fill text-rose-500"></i> <span x-text="a"></span>
                                        </span>
                                    </template>
                                    <template x-for="c in p.condiciones.slice(0, 2)" :key="'c' + c">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 truncate max-w-[120px]">
                                            <i class="bi bi-activity text-amber-600"></i> <span x-text="c"></span>
                                        </span>
                                    </template>
                                    <span x-show="p.alergias.length + p.condiciones.length > 4" class="text-[10px] font-black text-slate-400"
                                          x-text="'+' + (p.alergias.length + p.condiciones.length - Math.min(2, p.alergias.length) - Math.min(2, p.condiciones.length))"></span>
                                    <span x-show="!p.alergias.length && !p.condiciones.length" class="text-slate-300 italic text-[11px]">Sin antecedentes</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-2" :title="p.faltantes.length ? 'Falta: ' + p.faltantes.join(', ') : 'Expediente completo'">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-700" :class="p.faltantes.length ? 'bg-amber-400' : 'bg-emerald-500'" :style="'width:' + completitud(p) + '%'"></div>
                                    </div>
                                    <span class="text-[10px] font-black" :class="p.faltantes.length ? 'text-amber-600' : 'text-emerald-600'"
                                          x-text="p.faltantes.length ? 'Falta ' + p.faltantes.length : 'Completo'"></span>
                                </div>
                            </td>
                            <td class="py-3.5 px-5 text-right" @click.stop>
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="verDetalles(p)" title="Ver expediente"
                                            class="h-8 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-black flex items-center gap-1.5 shadow-sm transition-all active:scale-95 cursor-pointer">
                                        <i class="bi bi-eye-fill"></i><span class="hidden xl:inline">Ver</span>
                                    </button>
                                    @if ($puedeEditar)
                                        <button type="button" @click="abrirEditar(p.id)" title="Editar" :disabled="cargandoEditar === p.id"
                                                class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 hover:scale-110 flex items-center justify-center transition-all active:scale-95 cursor-pointer">
                                            <span x-show="cargandoEditar === p.id" class="w-3.5 h-3.5 rounded-full border-2 border-sky-500 border-t-transparent animate-spin"></span>
                                            <i x-show="cargandoEditar !== p.id" class="bi bi-pencil-fill text-[11px]"></i>
                                        </button>
                                    @endif
                                    @if ($puedeEliminar)
                                        <button type="button" @click="eliminar(p)" title="Eliminar"
                                                class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:scale-110 flex items-center justify-center transition-all active:scale-95 cursor-pointer">
                                            <i class="bi bi-trash-fill text-[11px]"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- ---------- TARJETAS ---------- --}}
        <div x-show="vista === 'tarjetas' && filtrados.length" x-cloak class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="(p, i) in pagina" :key="p.id">
                <div class="fila-in group relative bg-white rounded-3xl border border-slate-200 p-5 hover:border-teal-300 hover:shadow-lg hover:shadow-teal-900/5 hover:-translate-y-1 transition-all cursor-pointer overflow-hidden"
                     :style="'--d:' + Math.min(i * 40, 400) + 'ms'" :class="p._saliendo && 'fila-sale'" @click="verDetalles(p)">
                    <div x-show="p.alergias.length" class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-rose-400 to-rose-500"></div>
                    <div class="flex items-center gap-3">
                        <span class="w-12 h-12 rounded-2xl flex items-center justify-center text-sm font-black shrink-0" :class="colorAvatar(p.nombre)" x-text="iniciales(p.nombre)"></span>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-black text-slate-900 text-sm capitalize truncate" x-text="p.nombre.toLowerCase()"></h4>
                            <p class="text-[10px] font-bold text-slate-400 truncate"
                               x-text="[p.edad !== null ? p.edad + ' años' : null, p.genero, p.grupo_sanguineo].filter(Boolean).join(' · ') || 'Datos básicos pendientes'"></p>
                        </div>
                        <span class="text-[10px] font-black px-2 py-1 rounded-full shrink-0"
                              :class="p.faltantes.length ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                              x-text="completitud(p) + '%'"></span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1 min-h-[22px]">
                        <template x-for="a in p.alergias.slice(0, 3)" :key="'a' + a">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200"><i class="bi bi-exclamation-triangle-fill text-rose-500"></i> <span x-text="a"></span></span>
                        </template>
                        <template x-for="c in p.condiciones.slice(0, 2)" :key="'c' + c">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200"><i class="bi bi-activity text-amber-600"></i> <span x-text="c"></span></span>
                        </template>
                        <span x-show="!p.alergias.length && !p.condiciones.length" class="text-slate-300 italic text-[11px]">Sin antecedentes registrados</span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-1.5" @click.stop>
                        <a x-show="p.celular || p.telefono" :href="'tel:' + (p.celular || p.telefono)" title="Llamar"
                           class="w-8 h-8 rounded-xl bg-slate-50 text-slate-500 hover:bg-teal-50 hover:text-teal-700 flex items-center justify-center transition-all"><i class="bi bi-telephone-fill"></i></a>
                        <p class="text-[10px] font-mono font-bold uppercase truncate mr-auto" :class="p.curp ? 'text-slate-400' : 'text-amber-600'" x-text="p.curp || 'Sin CURP'"></p>
                        @if ($puedeEliminar)
                            <button type="button" @click="eliminar(p)" title="Eliminar" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition-all cursor-pointer"><i class="bi bi-trash-fill text-[11px]"></i></button>
                        @endif
                        @if ($puedeEditar)
                            <button type="button" @click="abrirEditar(p.id)" title="Editar" class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 flex items-center justify-center transition-all cursor-pointer">
                                <span x-show="cargandoEditar === p.id" class="w-3.5 h-3.5 rounded-full border-2 border-sky-500 border-t-transparent animate-spin"></span>
                                <i x-show="cargandoEditar !== p.id" class="bi bi-pencil-fill text-[11px]"></i>
                            </button>
                        @endif
                        <button type="button" @click="verDetalles(p)" class="h-8 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-black flex items-center gap-1.5 transition-all cursor-pointer">
                            <i class="bi bi-eye-fill"></i> Ver
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filtrados.length > limite" class="px-6 pb-6 pt-2 text-center">
            <button type="button" @click="limite += 30"
                    class="px-5 py-2.5 rounded-2xl border border-slate-200 text-xs font-black text-slate-600 hover:border-teal-300 hover:text-teal-700 transition-all cursor-pointer"
                    x-text="'Mostrar más (' + (filtrados.length - limite) + ' restantes)'"></button>
        </div>

        <div x-show="!filtrados.length" x-cloak class="py-16 text-center px-6">
            <span class="flotar inline-flex w-16 h-16 rounded-3xl items-center justify-center text-3xl mb-3"
                  :class="pacientes.length ? 'bg-slate-100 text-slate-400' : 'bg-teal-50 text-teal-500'">
                <i class="bi" :class="pacientes.length ? 'bi-search' : 'bi-person-plus'"></i>
            </span>
            <p class="text-sm font-black text-slate-600" x-text="pacientes.length ? 'Ningún paciente coincide con la búsqueda' : 'Aún no hay pacientes registrados'"></p>
            <button type="button" x-show="pacientes.length" @click="searchQuery = ''; filtro = 'todos'" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">Quitar filtros</button>
            @if ($puedeCrear)
                <button type="button" x-show="!pacientes.length" @click="openCreateModal = true" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">+ Registrar el primero</button>
            @endif
        </div>
    </div>

    {{-- ===================== MODAL: EXPEDIENTE ===================== --}}
    <template x-teleport="body">
        <div x-show="openModalDetalles" x-cloak
             @keydown.escape.window="openModalDetalles = false"
             class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-3xl flex flex-col overflow-hidden max-h-[94vh] sm:max-h-[90vh]"
                 @click.outside="openModalDetalles = false" x-show="openModalDetalles"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">
                <template x-if="pacienteSeleccionado">
                    <div class="flex flex-col min-h-0 flex-1">
                        {{-- Encabezado --}}
                        <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white shrink-0 overflow-hidden">
                            <div class="absolute -right-8 -top-10 w-36 h-36 rounded-full bg-white/5"></div>
                            <div class="relative flex items-center gap-4">
                                <span class="w-14 h-14 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center text-lg font-black text-emerald-300 shrink-0 icono-pop" x-text="iniciales(pacienteSeleccionado.nombre)"></span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-lg font-black tracking-tight capitalize truncate" x-text="pacienteSeleccionado.nombre.toLowerCase()"></h3>
                                    <p class="text-[11px] text-teal-100/80 font-bold truncate"
                                       x-text="[pacienteSeleccionado.codigo ? 'Código ' + pacienteSeleccionado.codigo : null, pacienteSeleccionado.curp ? 'CURP ' + pacienteSeleccionado.curp : 'Sin CURP', pacienteSeleccionado.edad !== null ? pacienteSeleccionado.edad + ' años' : null].filter(Boolean).join(' · ')"></p>
                                </div>
                                {{-- Anillo de completitud --}}
                                <div class="relative w-14 h-14 shrink-0 hidden sm:block" :title="'Expediente ' + completitud(pacienteSeleccionado) + '% completo'">
                                    <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56" aria-hidden="true">
                                        <circle cx="28" cy="28" r="23" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="5"/>
                                        <circle cx="28" cy="28" r="23" fill="none" stroke-width="5" stroke-linecap="round"
                                                :stroke="pacienteSeleccionado.faltantes.length ? '#fcd34d' : '#6ee7b7'" stroke-dasharray="144.5"
                                                :stroke-dashoffset="144.5 - 144.5 * completitud(pacienteSeleccionado) / 100" style="transition: stroke-dashoffset 1s"/>
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-[11px] font-black" x-text="completitud(pacienteSeleccionado) + '%'"></span>
                                </div>
                                <button type="button" @click="openModalDetalles = false" title="Cerrar (Esc)"
                                        class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer shrink-0">
                                    <i class="bi bi-x-lg text-xs"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Alerta de alergias (siempre visible, sin importar la pestaña) --}}
                        <div x-show="pacienteSeleccionado.alergias.length" class="alerta-alergia shrink-0 flex items-center gap-2.5 px-6 py-2.5 bg-rose-600 text-white text-xs font-black">
                            <i class="bi bi-exclamation-octagon-fill text-base"></i>
                            <span class="uppercase tracking-wide">Alérgico a:</span>
                            <span class="truncate" x-text="pacienteSeleccionado.alergias.join(', ')"></span>
                        </div>

                        {{-- Pestañas --}}
                        <div class="relative flex border-b border-slate-200 bg-slate-50/80 px-4 sm:px-6 pt-3 text-xs font-bold gap-1 shrink-0 overflow-x-auto">
                            <template x-for="t in pestanas" :key="t.id">
                                <button type="button" @click="activeTab = t.id"
                                        :class="activeTab === t.id ? 'border-teal-600 text-teal-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                        class="px-4 py-2.5 border-b-2 rounded-t-xl transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer">
                                    <i class="bi text-sm" :class="t.icono"></i> <span x-text="t.texto"></span>
                                    <span x-show="t.id === 'contacto' && pacienteSeleccionado.faltantes.length" class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                </button>
                            </template>
                        </div>

                        <div class="p-5 sm:p-6 overflow-y-auto text-xs flex-1 min-h-0">
                            {{-- General --}}
                            <div x-show="activeTab === 'general'" class="pestana space-y-4">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <template x-for="d in [
                                        ['Fecha de nacimiento', pacienteSeleccionado.fecha_nacimiento ? fechaLarga(pacienteSeleccionado.fecha_nacimiento) : null, 'bi-cake2'],
                                        ['Género', pacienteSeleccionado.genero, 'bi-gender-ambiguous'],
                                        ['Grupo sanguíneo', pacienteSeleccionado.grupo_sanguineo, 'bi-droplet-fill'],
                                        ['Estado civil', pacienteSeleccionado.estado_civil, 'bi-people'],
                                        ['Nacionalidad', pacienteSeleccionado.nacionalidad, 'bi-flag'],
                                        ['Estado en el sistema', pacienteSeleccionado.estado, 'bi-toggle-on']
                                    ]" :key="d[0]">
                                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                                            <span class="text-slate-400 font-bold uppercase flex items-center gap-1.5 text-[10px]"><i class="bi" :class="d[2]"></i><span x-text="d[0]"></span></span>
                                            <span class="font-black text-xs mt-0.5 block" :class="d[1] ? (d[0] === 'Grupo sanguíneo' ? 'text-rose-600' : 'text-slate-800') : 'text-slate-300'" x-text="d[1] || 'No registrado'"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-slate-400 font-bold uppercase text-[10px] flex items-center gap-1.5"><i class="bi bi-house-door"></i> Domicilio</span>
                                    <p class="font-bold text-xs leading-relaxed mt-0.5" :class="pacienteSeleccionado.direccion ? 'text-slate-800' : 'text-slate-300'" x-text="pacienteSeleccionado.direccion || 'Sin dirección registrada'"></p>
                                </div>
                            </div>

                            {{-- Antecedentes --}}
                            <div x-show="activeTab === 'clinico'" class="pestana space-y-4">
                                <template x-for="grupo in [
                                    { titulo: 'Alergias conocidas', lista: pacienteSeleccionado.alergias, icono: 'bi-exclamation-triangle-fill', caja: 'bg-rose-50/60 border-rose-100', tit: 'text-rose-800', chip: 'text-rose-800 border-rose-200', vacio: 'Sin alergias registradas.' },
                                    { titulo: 'Condiciones preexistentes', lista: pacienteSeleccionado.condiciones, icono: 'bi-activity', caja: 'bg-amber-50/60 border-amber-100', tit: 'text-amber-800', chip: 'text-amber-800 border-amber-200', vacio: 'Sin condiciones registradas.' },
                                    { titulo: 'Medicamentos actuales', lista: pacienteSeleccionado.medicamentos, icono: 'bi-capsule', caja: 'bg-teal-50/60 border-teal-100', tit: 'text-teal-800', chip: 'text-teal-800 border-teal-200', vacio: 'Sin medicamentos registrados.' }
                                ]" :key="grupo.titulo">
                                    <div class="p-4 rounded-2xl border" :class="grupo.caja">
                                        <h4 class="font-black uppercase tracking-wider mb-2.5 flex items-center gap-2 text-[11px]" :class="grupo.tit">
                                            <i class="bi" :class="grupo.icono"></i> <span x-text="grupo.titulo"></span>
                                            <span class="ml-auto text-[10px] font-black bg-white rounded-full px-2 py-0.5" x-text="grupo.lista.length"></span>
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="(item, k) in grupo.lista" :key="k">
                                                <span class="chip-in px-3 py-1 rounded-xl text-xs font-bold bg-white border shadow-sm" :class="grupo.chip" :style="'--d:' + (k * 50) + 'ms'" x-text="item"></span>
                                            </template>
                                            <span x-show="!grupo.lista.length" class="text-slate-400 italic text-xs" x-text="grupo.vacio"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Contacto y legales --}}
                            <div x-show="activeTab === 'contacto'" class="pestana space-y-4">
                                <div x-show="pacienteSeleccionado.faltantes.length" class="flex items-center gap-2 p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 font-bold">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                    <span x-text="'Para completar el expediente falta: ' + pacienteSeleccionado.faltantes.join(', ')"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <template x-for="c in [
                                        ['Teléfono', pacienteSeleccionado.telefono, 'tel:', 'bi-telephone-fill'],
                                        ['Celular', pacienteSeleccionado.celular, 'tel:', 'bi-phone-fill'],
                                        ['Correo', pacienteSeleccionado.email, 'mailto:', 'bi-envelope-fill']
                                    ]" :key="c[0]">
                                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 min-w-0">
                                            <span class="text-slate-400 font-bold uppercase text-[10px]" x-text="c[0]"></span>
                                            <a x-show="c[1]" :href="c[2] + c[1]" class="font-black text-teal-700 hover:underline text-xs flex items-center gap-1.5 mt-0.5 truncate">
                                                <i class="bi" :class="c[3]"></i><span class="truncate" x-text="c[1]"></span>
                                            </a>
                                            <span x-show="!c[1]" class="font-bold text-slate-300 text-xs block mt-0.5">No registrado</span>
                                        </div>
                                    </template>
                                </div>

                                <div class="p-4 bg-sky-50/60 rounded-2xl border border-sky-100">
                                    <h5 class="font-black text-sky-800 uppercase text-[11px] flex items-center gap-1.5 mb-2">
                                        <i class="bi bi-telephone-outbound-fill text-sky-600"></i> Contacto de emergencia
                                    </h5>
                                    <div x-show="pacienteSeleccionado.emerg_nombre || pacienteSeleccionado.emerg_telefono" class="flex items-center gap-3">
                                        <span class="w-10 h-10 rounded-xl bg-white text-sky-700 flex items-center justify-center font-black shrink-0" x-text="iniciales(pacienteSeleccionado.emerg_nombre)"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-black text-slate-800" x-text="pacienteSeleccionado.emerg_nombre || 'Sin nombre'"></p>
                                            <p class="text-[11px] text-slate-500 font-bold" x-text="pacienteSeleccionado.emerg_relacion || 'Relación no indicada'"></p>
                                        </div>
                                        <a x-show="pacienteSeleccionado.emerg_telefono" :href="'tel:' + pacienteSeleccionado.emerg_telefono"
                                           class="h-9 px-3 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-[11px] font-black flex items-center gap-1.5 transition-all">
                                            <i class="bi bi-telephone-fill"></i><span x-text="pacienteSeleccionado.emerg_telefono"></span>
                                        </a>
                                    </div>
                                    <p x-show="!pacienteSeleccionado.emerg_nombre && !pacienteSeleccionado.emerg_telefono" class="text-slate-400 italic">Sin contacto de emergencia registrado.</p>
                                </div>

                                <div class="p-3.5 rounded-2xl border flex items-center justify-between gap-3"
                                     :class="pacienteSeleccionado.aviso ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
                                    <div class="flex items-center gap-3">
                                        <i class="bi text-xl" :class="pacienteSeleccionado.aviso ? 'bi-shield-check text-emerald-600' : 'bi-shield-exclamation text-amber-600'"></i>
                                        <div>
                                            <p class="font-black text-xs text-slate-800">Aviso de privacidad / NOM-004</p>
                                            <p class="text-[10px] text-slate-500" x-text="pacienteSeleccionado.aviso ? 'El paciente autorizó el tratamiento de sus datos personales.' : 'Pendiente de aceptación.'"></p>
                                        </div>
                                    </div>
                                    <span class="font-black text-[10px] uppercase px-2.5 py-1 rounded-full border shrink-0"
                                          :class="pacienteSeleccionado.aviso ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300'"
                                          x-text="pacienteSeleccionado.aviso ? 'Firmado' : 'Pendiente'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex justify-end gap-2 shrink-0">
                            <button type="button" @click="openModalDetalles = false" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">Cerrar</button>
                            @if ($puedeEditar)
                                <button type="button" @click="abrirEditar(pacienteSeleccionado.id)" :disabled="cargandoEditar"
                                        class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer disabled:opacity-60">
                                    <span x-show="cargandoEditar" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                    <i x-show="!cargandoEditar" class="bi bi-pencil-fill"></i>
                                    <span x-text="pacienteSeleccionado.faltantes.length ? 'Completar expediente' : 'Editar expediente'"></span>
                                </button>
                            @endif
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Modales de crear y editar (usan: openCreateModal, openEditModal, openAvisoModal, pacienteEditar) --}}
    @include('pacientes.modalPacientes')
    @include('pacientes.modalEditarPaciente')
</div>

<style>
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes filaIn   { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes filaSale { to { opacity: 0; transform: translateX(30px); } }
    @keyframes iconoPop { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes latidoLento { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @keyframes ecg      { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
    @keyframes pestana  { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: none; } }
    @keyframes chipIn   { from { opacity: 0; transform: scale(.8); } to { opacity: 1; transform: none; } }
    @keyframes alerta   { 0%, 100% { background-color: #e11d48; } 50% { background-color: #be123c; } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .fila-in      { animation: filaIn .4s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .fila-sale    { animation: filaSale .35s ease-in forwards !important; }
    .icono-pop    { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .latido-lento { display: inline-block; animation: latidoLento 1.6s ease-in-out infinite; }
    .ecg-linea    { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }
    .pestana      { animation: pestana .3s cubic-bezier(.16, 1, .3, 1); }
    .chip-in      { animation: chipIn .35s cubic-bezier(.34, 1.56, .64, 1) backwards; animation-delay: var(--d, 0ms); }
    .alerta-alergia { animation: alerta 2s ease-in-out infinite; }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .fila-in, .icono-pop, .flotar, .latido-lento, .ecg-linea, .pestana, .chip-in, .alerta-alergia { animation: none !important; }
    }
</style>

<script>
    const URL_PACIENTES = @js(url('pacientes'));
    const CSRF_PACIENTES = @js(csrf_token());

    function avisoPacientes(mensaje, icono = 'success') {
        if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
    }

    function moduloPacientes() {
        return {
            pacientes: @json($pacientesJs),
            errores: @json($errors->all()),

            // ----- Vista y filtros -----
            searchQuery: '',
            filtro: 'todos',       // todos | activos | alergias | incompletos
            vista: 'lista',
            limite: 30,
            resumenMostrado: { todos: 0, activos: 0, alergias: 0, incompletos: 0 },

            // ----- Modales (los de crear/editar se usan en los archivos incluidos) -----
            openCreateModal: @js($errors->any()),
            openModalDetalles: false,
            openEditModal: false,
            openAvisoModal: false,
            pacienteSeleccionado: null,
            pacienteEditar: {},
            activeTab: 'general',
            cargandoEditar: null,
            pestanas: [
                { id: 'general',  texto: 'Información general', icono: 'bi-person-vcard' },
                { id: 'clinico',  texto: 'Antecedentes',        icono: 'bi-heart-pulse' },
                { id: 'contacto', texto: 'Contacto y legales',  icono: 'bi-shield-check' }
            ],

            init() {
                try { this.vista = localStorage.getItem('pacientes_vista') || (window.innerWidth < 768 ? 'tarjetas' : 'lista'); } catch (e) {}
                ['searchQuery', 'filtro'].forEach(k => this.$watch(k, () => { this.limite = 30; }));
                this.$nextTick(() => this.animarResumen());

                window.addEventListener('keydown', e => {
                    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) && !this.openCreateModal && !this.openEditModal && !this.openModalDetalles) {
                        e.preventDefault();
                        this.$refs.buscar.focus();
                    }
                });

                // Errores de validación al guardar
                if (this.errores.length) {
                    setTimeout(() => Swal.fire({
                        title: 'Revisa los datos',
                        html: '<div style="text-align:left;font-size:13px;color:#475569">' + this.errores.map(e => '• ' + this.escapar(e)).join('<br>') + '</div>',
                        icon: 'warning', iconColor: '#f59e0b',
                        confirmButtonText: 'Entendido', confirmButtonColor: '#0d9488',
                        customClass: { container: 'z-[10050]', popup: 'rounded-3xl' }
                    }), 200);
                }
                @if (session('success'))
                    setTimeout(() => avisoPacientes(@js(session('success')), 'success'), 300);
                @endif
            },

            // ----- Resumen -----
            get resumen() {
                return {
                    todos: this.pacientes.length,
                    activos: this.pacientes.filter(p => p.estado === 'Activo').length,
                    alergias: this.pacientes.filter(p => p.alergias.length).length,
                    incompletos: this.pacientes.filter(p => p.faltantes.length).length
                };
            },
            porcentaje(clave) { return this.pacientes.length ? Math.round(this.resumen[clave] / this.pacientes.length * 100) : 0; },
            animarResumen() {
                const meta = this.resumen;
                const sinMov = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                Object.keys(meta).forEach(k => {
                    const destino = meta[k], origen = this.resumenMostrado[k];
                    if (sinMov || origen === destino) { this.resumenMostrado[k] = destino; return; }
                    const t0 = performance.now(), dur = 800;
                    const paso = t => {
                        const p = Math.min(1, (t - t0) / dur);
                        this.resumenMostrado[k] = Math.round(origen + (destino - origen) * (1 - Math.pow(1 - p, 3)));
                        if (p < 1) requestAnimationFrame(paso);
                    };
                    requestAnimationFrame(paso);
                });
            },

            // ----- Filtros -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
            get filtrados() {
                const q = this.normalizar(this.searchQuery);
                return this.pacientes.filter(p =>
                    (!q || p.buscar.includes(q)) &&
                    (this.filtro === 'todos' ||
                     (this.filtro === 'activos' && p.estado === 'Activo') ||
                     (this.filtro === 'alergias' && p.alergias.length) ||
                     (this.filtro === 'incompletos' && p.faltantes.length))
                );
            },
            get pagina() { return this.filtrados.slice(0, this.limite); },
            cambiarVista(v) { this.vista = v; try { localStorage.setItem('pacientes_vista', v); } catch (e) {} },

            // ----- Formato -----
            iniciales(n) { return (n || '?').trim().split(/\s+/).slice(0, 2).map(x => x[0]).join('').toUpperCase(); },
            colorAvatar(n) {
                const colores = ['bg-teal-100 text-teal-800', 'bg-sky-100 text-sky-800', 'bg-violet-100 text-violet-800', 'bg-amber-100 text-amber-800', 'bg-rose-100 text-rose-800', 'bg-emerald-100 text-emerald-800'];
                let h = 0;
                for (const ch of String(n || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
                return colores[h % colores.length];
            },
            completitud(p) { return Math.round((3 - Math.min(3, p.faltantes.length)) / 3 * 100); },
            fechaLarga(f) {
                const t = new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
                return t;
            },

            // ----- Acciones -----
            verDetalles(p) {
                this.pacienteSeleccionado = p;
                this.activeTab = 'general';
                this.openModalDetalles = true;
            },

            // Trae el expediente completo y abre el modal de edición (pacientes.modalEditarPaciente)
            async abrirEditar(id) {
                if (this.cargandoEditar) return;
                this.cargandoEditar = id;
                try {
                    const response = await fetch(`${URL_PACIENTES}/${id}/edit`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) throw new Error();
                    const p = await response.json();

                    p.acepta_aviso_privacidad = Boolean(p.acepta_aviso_privacidad);
                    p.alergias_list = p.alergias && p.alergias.length ? p.alergias.map(a => a.descripcion) : [''];
                    p.condiciones_list = p.condiciones && p.condiciones.length ? p.condiciones.map(c => c.descripcion) : [''];
                    p.medicamentos_list = p.medicamentos && p.medicamentos.length ? p.medicamentos.map(m => m.nombre) : [''];

                    this.pacienteEditar = p;
                    this.openModalDetalles = false;
                    this.openEditModal = true;
                } catch (e) {
                    avisoPacientes('No se pudieron consultar los datos del paciente.', 'error');
                } finally {
                    this.cargandoEditar = null;
                }
            },

            eliminar(p) {
                const extra = p.alergias.length || p.condiciones.length || p.medicamentos.length
                    ? '<br><span style="font-size:12px;color:#b45309">También se borrarán sus alergias, condiciones y medicamentos registrados.</span>' : '';
                Swal.fire({
                    title: '¿Eliminar expediente?',
                    html: `Se eliminará el expediente de <b>${this.escapar(p.nombre)}</b>.${extra}<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonColor: '#f43f5e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    showLoaderOnConfirm: true,
                    customClass: { popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try {
                            const res = await fetch(`${URL_PACIENTES}/${p.id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': CSRF_PACIENTES, 'Accept': 'application/json' }
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || data.success === false) {
                                throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (data.message || 'No se pudo eliminar el expediente.'));
                            }
                            return data;
                        } catch (e) {
                            Swal.showValidationMessage(e.message || 'Error de conexión');
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(result => {
                    if (!result.isConfirmed) return;
                    this.openModalDetalles = false;
                    p._saliendo = true;
                    setTimeout(() => {
                        this.pacientes = this.pacientes.filter(x => x.id !== p.id);
                        this.animarResumen();
                    }, 330);
                    avisoPacientes((result.value && result.value.message) || 'Expediente eliminado.', 'success');
                });
            },
            escapar(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
        };
    }
</script>
@endsection