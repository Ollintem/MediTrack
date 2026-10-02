@extends('layouts.admin')

@section('content')
@php
    $personal = $personal ?? collect();
    if ($personal instanceof \Illuminate\Database\Eloquent\Collection) {
        $personal->loadMissing('usuario');
    }

    // Permisos (se consultan una sola vez, no en cada fila)
    $puedeVerRoles = auth()->user()->tienePermiso('Roles', 'ver');
    $puedeCrear    = auth()->user()->tienePermiso('Personal', 'crear');
    $puedeEditar   = auth()->user()->tienePermiso('Personal', 'editar');
    $puedeEliminar = auth()->user()->tienePermiso('Personal', 'eliminar');

    $miUsuarioId = auth()->id();

    $personalJs = $personal->map(function ($p) use ($miUsuarioId) {
        $nombre = $p->nombre_completo ?: trim(($p->nombre ?? '') . ' ' . ($p->apellidos ?? $p->apellido_paterno ?? ''));
        $estado = $p->estado ?? 'Activo';
        $rol    = data_get($p, 'usuario.rol.nombre') ?: data_get($p, 'usuario.role.nombre')
               ?: (is_string(data_get($p, 'usuario.rol')) ? data_get($p, 'usuario.rol') : null);

        return [
            'id'           => $p->id,
            'nombre'       => $nombre ?: 'Sin nombre',
            'email'        => data_get($p, 'usuario.email'),
            'especialidad' => $p->especialidad_principal ?: null,
            'telefono'     => $p->telefono ?: null,
            'cedula'       => $p->cedula_profesional ?? $p->cedula ?? $p->rut ?? null,
            'rol'          => $rol,
            'activo'       => mb_strtolower((string) $estado) === 'activo',
            'estado'       => $estado,
            'soyYo'        => $miUsuarioId && (int) data_get($p, 'usuario.id') === (int) $miUsuarioId,
            'editar'       => route('personal.edit', $p->id),
            'nomina'       => \Illuminate\Support\Facades\Route::has('personal.nomina.empleado') ? route('personal.nomina.empleado', $p->id) : '#',
            'salario'      => (float) ($p->salario_diario ?? 0),
            'tipo_pago'    => $p->tipo_pago ?? null,
            'buscar'       => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(implode(' ', array_filter([
                $nombre, data_get($p, 'usuario.email'), $p->especialidad_principal, $p->telefono, $rol,
            ])))),
        ];
    })->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values();

    $tarjetas = [
        ['Total de personal', 'total',          'bi-people-fill',       'bg-teal-50 text-teal-600'],
        ['Cuentas activas',   'activos',        'bi-person-check-fill', 'bg-emerald-50 text-emerald-600'],
        ['Inactivas',         'inactivos',      'bi-person-dash-fill',  'bg-slate-100 text-slate-500'],
        ['Especialidades',    'especialidades', 'bi-heart-pulse-fill',  'bg-sky-50 text-sky-600'],
    ];
@endphp

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="space-y-6" x-data="moduloPersonal()">

    @include('personal._pestanas', ['activa' => 'plantilla'])

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-48 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        {{-- Línea de latido --}}
        <svg class="absolute inset-x-0 bottom-2 w-full h-12 opacity-25 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        {{-- Avatares decorativos --}}
        <div class="absolute right-10 top-8 hidden lg:flex -space-x-3 opacity-25 pointer-events-none">
            <span class="w-12 h-12 rounded-full bg-white border-4 border-teal-800 flotar"></span>
            <span class="w-12 h-12 rounded-full bg-emerald-200 border-4 border-teal-800 flotar" style="animation-delay:-1s"></span>
            <span class="w-12 h-12 rounded-full bg-teal-200 border-4 border-teal-800 flotar" style="animation-delay:-2s"></span>
        </div>

        <div class="relative p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wide text-emerald-300 backdrop-blur-md">
                    <i class="bi bi-heart-pulse-fill latido-lento"></i> Gestión institucional
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-3">
                    Directorio de personal <i class="bi bi-heart-pulse-fill text-emerald-300 text-2xl latido-lento"></i>
                </h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Administra las cuentas del equipo, sus cargos, datos profesionales y acceso a MediTrack.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                @if ($puedeVerRoles)
                    <a href="{{ route('roles.index') }}"
                       class="bg-white/10 hover:bg-white/20 text-white font-bold px-4 py-2.5 rounded-2xl text-xs border border-white/20 backdrop-blur-md transition-all hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-emerald-300"></i> Roles y permisos
                    </a>
                @endif
                @if ($puedeCrear)
                    <a href="{{ route('personal.create') }}"
                       class="group bg-white text-teal-900 hover:bg-emerald-50 font-black px-5 py-2.5 rounded-2xl text-xs shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2">
                        <i class="bi bi-person-plus-fill text-teal-700 text-sm transition-transform duration-300 group-hover:scale-110"></i> Registrar personal
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== RESUMEN ===================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $color])
            <button type="button" @click="filtrarPorTarjeta('{{ $clave }}')"
                    class="tarjeta-stat group text-left bg-white rounded-2xl border shadow-sm p-4 flex items-center justify-between gap-3 transition-all hover:shadow-md hover:-translate-y-1 cursor-pointer"
                    :class="tarjetaActiva('{{ $clave }}') ? 'border-teal-300 ring-2 ring-teal-100' : 'border-slate-100 hover:border-slate-200'"
                    style="animation-delay: {{ $i * 70 }}ms">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $titulo }}</p>
                    <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="resumenMostrado.{{ $clave }}">0</p>
                </div>
                <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $color }}">
                    <i class="bi {{ $icono }}"></i>
                </span>
            </button>
        @endforeach
    </div>

    {{-- ===================== DIRECTORIO ===================== --}}
    <div class="aparece bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" style="--d: 220ms">

        <div class="p-5 sm:p-6 border-b border-slate-100 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-person-lines-fill"></i></span>
                    <div>
                        <h3 class="text-base font-black text-slate-800">Miembros del equipo</h3>
                        <p class="text-[11px] font-semibold text-slate-400"
                           x-text="filtrados.length === personal.length ? personal.length + ' registrados' : filtrados.length + ' de ' + personal.length + ' registrados'"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1 md:w-80">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="search" x-ref="buscar" x-model.debounce.150ms="busqueda" placeholder="Nombre, correo, especialidad, teléfono…"
                               class="w-full pl-9 pr-9 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-semibold focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all">
                        <kbd class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-400 border border-slate-200 rounded px-1.5 py-0.5 hidden sm:block" x-show="!busqueda">/</kbd>
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

            {{-- Filtro por especialidad --}}
            <div class="flex flex-wrap items-center gap-2" x-show="especialidades.length > 1">
                <button type="button" @click="filtroEspecialidad = ''"
                        :class="filtroEspecialidad === '' ? 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-600/20' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'"
                        class="px-3 py-1.5 rounded-full border text-[11px] font-bold transition-all cursor-pointer">Todas</button>
                <template x-for="e in especialidades" :key="e.nombre">
                    <button type="button" @click="filtroEspecialidad = filtroEspecialidad === e.nombre ? '' : e.nombre"
                            :class="filtroEspecialidad === e.nombre ? 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-600/20' : 'bg-white text-slate-500 border-slate-200 hover:border-teal-300 hover:text-teal-700'"
                            class="px-3 py-1.5 rounded-full border text-[11px] font-bold transition-all cursor-pointer flex items-center gap-1.5">
                        <span x-text="e.nombre"></span>
                        <span class="text-[9px] font-black opacity-70" x-text="e.total"></span>
                    </button>
                </template>
                <button type="button" x-show="hayFiltros" x-cloak @click="limpiarFiltros()" class="ml-auto text-[11px] font-black text-teal-700 hover:text-teal-900 cursor-pointer">Limpiar filtros</button>
            </div>
        </div>

        {{-- ---------- TABLA ---------- --}}
        <div x-show="vista === 'lista' && filtrados.length" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 text-[10px] text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-5">Nombre</th>
                        <th class="py-3.5 px-5">Contacto</th>
                        <th class="py-3.5 px-5">Cargo / especialidad</th>
                        <th class="py-3.5 px-5">Estado</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    <template x-for="(p, i) in pagina" :key="p.id">
                        <tr class="fila-in group hover:bg-teal-50/30 transition-colors" :style="'--d:' + Math.min(i * 35, 400) + 'ms'" :class="p._saliendo && 'fila-sale'">
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <span class="relative w-10 h-10 rounded-2xl flex items-center justify-center text-[11px] font-black shrink-0 transition-transform group-hover:scale-110"
                                          :class="colorAvatar(p.nombre)">
                                        <span x-text="iniciales(p.nombre)"></span>
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full ring-2 ring-white" :class="p.activo ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 text-sm truncate max-w-[240px] flex items-center gap-1.5">
                                            <span x-text="p.nombre"></span>
                                            <span x-show="p.soyYo" class="text-[9px] font-black text-teal-700 bg-teal-50 border border-teal-200 rounded-full px-1.5 py-0.5">Tú</span>
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-400" x-show="p.rol" x-text="p.rol"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-5">
                                <div class="space-y-1">
                                    <p class="flex items-center gap-1.5 text-slate-600">
                                        <i class="bi bi-envelope text-teal-600"></i>
                                        <template x-if="p.email">
                                            <button type="button" @click="copiar(p.email)" class="hover:text-teal-700 hover:underline cursor-pointer truncate max-w-[200px]" :title="'Copiar ' + p.email" x-text="p.email"></button>
                                        </template>
                                        <span x-show="!p.email" class="text-slate-300 italic">Sin correo</span>
                                    </p>
                                    <p class="flex items-center gap-1.5 text-slate-500" x-show="p.telefono">
                                        <i class="bi bi-telephone text-slate-400"></i>
                                        <a :href="'tel:' + p.telefono" class="hover:text-teal-700 tabular-nums" x-text="p.telefono"></a>
                                    </p>
                                </div>
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold border"
                                      :class="p.especialidad ? 'bg-teal-50 text-teal-700 border-teal-200/60' : 'bg-slate-50 text-slate-400 border-slate-200'">
                                    <i class="bi bi-shield-check"></i>
                                    <span x-text="p.especialidad || 'Sin asignar'"></span>
                                </span>
                                <p class="text-[10px] font-bold text-slate-400 mt-1" x-show="p.cedula" x-text="'Céd. ' + p.cedula"></p>
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black border"
                                      :class="p.activo ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="p.activo ? 'bg-emerald-500 latido' : 'bg-slate-400'"></span>
                                    <span x-text="p.estado"></span>
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a :href="p.nomina" title="Nómina de este empleado"
                                       class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center transition-all hover:scale-110">
                                        <i class="bi bi-cash-coin text-[12px]"></i>
                                    </a>
                                    @if ($puedeEditar)
                                        <a :href="p.editar" title="Editar"
                                           class="h-8 px-3 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 text-[11px] font-black flex items-center gap-1.5 transition-all hover:scale-105 active:scale-95">
                                            <i class="bi bi-pencil-fill"></i><span class="hidden sm:inline">Editar</span>
                                        </a>
                                    @endif
                                    @if ($puedeEliminar)
                                        <button type="button" @click="eliminar(p)" :disabled="p.soyYo"
                                                :title="p.soyYo ? 'No puedes eliminar tu propia cuenta' : 'Eliminar'"
                                                class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition-all hover:scale-110 active:scale-95 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:scale-100">
                                            <i class="bi bi-trash-fill text-[11px]"></i>
                                        </button>
                                    @endif
                                    @if (!$puedeEditar && !$puedeEliminar)
                                        <span class="text-[11px] text-slate-300 italic">Sin acciones</span>
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
                <div class="fila-in group relative bg-white rounded-3xl border border-slate-200 overflow-hidden hover:border-teal-300 hover:shadow-lg hover:shadow-teal-900/5 hover:-translate-y-1 transition-all"
                     :style="'--d:' + Math.min(i * 40, 400) + 'ms'" :class="p._saliendo && 'fila-sale'">
                    <div class="h-16 bg-gradient-to-r" :class="p.activo ? 'from-teal-600 to-emerald-500' : 'from-slate-300 to-slate-400'"></div>
                    <div class="px-5 pb-5 -mt-8">
                        <div class="flex items-end justify-between">
                            <span class="relative w-16 h-16 rounded-2xl flex items-center justify-center text-lg font-black ring-4 ring-white shadow-md transition-transform group-hover:scale-105"
                                  :class="colorAvatar(p.nombre)">
                                <span x-text="iniciales(p.nombre)"></span>
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full ring-2 ring-white" :class="p.activo ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black border mb-1"
                                  :class="p.activo ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="p.activo ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span x-text="p.estado"></span>
                            </span>
                        </div>

                        <h4 class="mt-3 font-black text-slate-900 text-sm truncate flex items-center gap-1.5">
                            <span x-text="p.nombre" class="truncate"></span>
                            <span x-show="p.soyYo" class="text-[9px] font-black text-teal-700 bg-teal-50 border border-teal-200 rounded-full px-1.5 py-0.5 shrink-0">Tú</span>
                        </h4>
                        <p class="text-[11px] font-extrabold text-teal-700 truncate" x-text="p.especialidad || 'Sin especialidad asignada'"></p>
                        <p class="text-[10px] font-bold text-slate-400 truncate" x-text="[p.rol, p.cedula ? 'Céd. ' + p.cedula : ''].filter(Boolean).join(' · ')"></p>

                        <div class="mt-4 space-y-1.5 text-[11px] font-semibold text-slate-600">
                            <p class="flex items-center gap-2 truncate">
                                <i class="bi bi-envelope text-teal-600"></i>
                                <span x-text="p.email || 'Sin correo'" :class="!p.email && 'text-slate-300 italic'" class="truncate"></span>
                            </p>
                            <p class="flex items-center gap-2">
                                <i class="bi bi-telephone text-slate-400"></i>
                                <span x-text="p.telefono || 'Sin teléfono'" :class="!p.telefono && 'text-slate-300 italic'"></span>
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-1.5">
                            <button type="button" x-show="p.email" @click="copiar(p.email)" title="Copiar correo"
                                    class="w-8 h-8 rounded-xl bg-slate-50 text-slate-500 hover:bg-teal-50 hover:text-teal-700 flex items-center justify-center transition-all cursor-pointer">
                                <i class="bi bi-clipboard"></i>
                            </button>
                            <a x-show="p.telefono" :href="'tel:' + p.telefono" title="Llamar"
                               class="w-8 h-8 rounded-xl bg-slate-50 text-slate-500 hover:bg-teal-50 hover:text-teal-700 flex items-center justify-center transition-all">
                                <i class="bi bi-telephone-fill"></i>
                            </a>
                            <span class="mr-auto"></span>
                            @if ($puedeEliminar)
                                <button type="button" @click="eliminar(p)" :disabled="p.soyYo" :title="p.soyYo ? 'No puedes eliminar tu propia cuenta' : 'Eliminar'"
                                        class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition-all cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed">
                                    <i class="bi bi-trash-fill text-[11px]"></i>
                                </button>
                            @endif
                            <a :href="p.nomina" title="Nómina" class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center transition-all">
                                <i class="bi bi-cash-coin"></i>
                            </a>
                            @if ($puedeEditar)
                                <a :href="p.editar" class="h-8 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-black flex items-center gap-1.5 transition-all">
                                    <i class="bi bi-pencil-fill"></i> Editar
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Mostrar más --}}
        <div x-show="filtrados.length > limite" class="px-6 pb-6 pt-2 text-center">
            <button type="button" @click="limite += 24"
                    class="px-5 py-2.5 rounded-2xl border border-slate-200 text-xs font-black text-slate-600 hover:border-teal-300 hover:text-teal-700 transition-all cursor-pointer"
                    x-text="'Mostrar más (' + (filtrados.length - limite) + ' restantes)'"></button>
        </div>

        {{-- Vacío --}}
        <div x-show="!filtrados.length" x-cloak class="py-16 text-center px-6">
            <span class="flotar inline-flex w-16 h-16 rounded-3xl items-center justify-center text-3xl mb-3"
                  :class="personal.length ? 'bg-slate-100 text-slate-400' : 'bg-teal-50 text-teal-500'">
                <i class="bi" :class="personal.length ? 'bi-search' : 'bi-person-plus'"></i>
            </span>
            <p class="text-sm font-black text-slate-600" x-text="personal.length ? 'Nadie coincide con la búsqueda' : 'Aún no hay personal registrado'"></p>
            <button type="button" x-show="personal.length" @click="limpiarFiltros()" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">Quitar filtros</button>
            @if ($puedeCrear)
                <a x-show="!personal.length" href="{{ route('personal.create') }}" class="mt-2 inline-block text-xs font-black text-teal-700 hover:text-teal-900">+ Registrar al primero</a>
            @endif
        </div>
    </div>
</div>

<style>
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes filaIn   { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes filaSale { to { opacity: 0; transform: translateX(30px); } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes latido   { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .6; } }
    @keyframes latidoLento { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @keyframes ecg      { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .fila-in      { animation: filaIn .4s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .fila-sale    { animation: filaSale .35s ease-in forwards !important; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .latido       { animation: latido 1.4s ease-in-out infinite; }
    .latido-lento { display: inline-block; animation: latidoLento 1.6s ease-in-out infinite; }
    .ecg-linea    { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .fila-in, .flotar, .latido, .latido-lento, .ecg-linea { animation: none !important; }
    }
</style>

<script>
    const URL_PERSONAL = @js(url('personal'));
    const CSRF_PERSONAL = @js(csrf_token());

    // Usa la notificación del layout (window.notificar(mensaje, icono)); si no existe, un toast de SweetAlert
    function avisoPersonal(mensaje, icono = 'success') {
        if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
    }

    function moduloPersonal() {
        return {
            personal: @json($personalJs),
            mensajeExito: @js(session('success')),

            busqueda: '',
            filtroEspecialidad: '',
            filtroEstado: '',          // '', 'activos', 'inactivos'
            vista: 'lista',
            limite: 24,
            resumenMostrado: { total: 0, activos: 0, inactivos: 0, especialidades: 0 },

            init() {
                try { this.vista = localStorage.getItem('personal_vista') || (window.innerWidth < 768 ? 'tarjetas' : 'lista'); } catch (e) {}
                ['busqueda', 'filtroEspecialidad', 'filtroEstado'].forEach(k => this.$watch(k, () => { this.limite = 24; }));
                this.$nextTick(() => this.animarResumen());

                window.addEventListener('keydown', e => {
                    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                        e.preventDefault();
                        this.$refs.buscar.focus();
                    }
                });

                if (this.mensajeExito) setTimeout(() => avisoPersonal(this.mensajeExito, 'success'), 300);
            },

            // ----- Resumen -----
            get resumen() {
                const activos = this.personal.filter(p => p.activo).length;
                return {
                    total: this.personal.length,
                    activos,
                    inactivos: this.personal.length - activos,
                    especialidades: this.especialidades.length
                };
            },
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
            filtrarPorTarjeta(clave) {
                if (clave === 'activos' || clave === 'inactivos') this.filtroEstado = this.filtroEstado === clave ? '' : clave;
                else if (clave === 'total') this.limpiarFiltros();
            },
            tarjetaActiva(clave) {
                if (clave === 'total') return !this.hayFiltros;
                return this.filtroEstado === clave;
            },

            // ----- Filtros -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
            get especialidades() {
                const cuenta = {};
                this.personal.forEach(p => { if (p.especialidad) cuenta[p.especialidad] = (cuenta[p.especialidad] || 0) + 1; });
                return Object.entries(cuenta).map(([nombre, total]) => ({ nombre, total }))
                    .sort((a, b) => b.total - a.total || a.nombre.localeCompare(b.nombre));
            },
            get filtrados() {
                const q = this.normalizar(this.busqueda);
                return this.personal.filter(p =>
                    (!q || p.buscar.includes(q)) &&
                    (!this.filtroEspecialidad || p.especialidad === this.filtroEspecialidad) &&
                    (!this.filtroEstado || (this.filtroEstado === 'activos' ? p.activo : !p.activo))
                );
            },
            get pagina() { return this.filtrados.slice(0, this.limite); },
            get hayFiltros() { return this.busqueda.trim() !== '' || this.filtroEspecialidad !== '' || this.filtroEstado !== ''; },
            limpiarFiltros() { this.busqueda = ''; this.filtroEspecialidad = ''; this.filtroEstado = ''; },
            cambiarVista(v) {
                this.vista = v;
                try { localStorage.setItem('personal_vista', v); } catch (e) {}
            },

            // ----- Formato -----
            iniciales(n) { return (n || '?').trim().split(/\s+/).slice(0, 2).map(x => x[0]).join('').toUpperCase(); },
            colorAvatar(n) {
                const colores = ['bg-teal-100 text-teal-800', 'bg-sky-100 text-sky-800', 'bg-violet-100 text-violet-800', 'bg-amber-100 text-amber-800', 'bg-rose-100 text-rose-800', 'bg-emerald-100 text-emerald-800'];
                let h = 0;
                for (const ch of String(n || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
                return colores[h % colores.length];
            },

            // ----- Acciones -----
            async copiar(texto) {
                try {
                    await navigator.clipboard.writeText(texto);
                    avisoPersonal('Correo copiado: ' + texto, 'success');
                } catch (e) {
                    avisoPersonal('No se pudo copiar el correo', 'warning');
                }
            },

            eliminar(p) {
                if (p.soyYo) return;
                Swal.fire({
                    title: '¿Eliminar a este miembro?',
                    html: `Se eliminará a <b>${this.escapar(p.nombre)}</b>${p.email ? ' y su acceso (' + this.escapar(p.email) + ')' : ''}.<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f43f5e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    showLoaderOnConfirm: true,
                    customClass: { popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try {
                            const res = await fetch(`${URL_PERSONAL}/${p.id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': CSRF_PERSONAL, 'Accept': 'application/json' }
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || data.success === false) {
                                throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (data.message || 'No se pudo eliminar el registro.'));
                            }
                            return data;
                        } catch (e) {
                            Swal.showValidationMessage(e.message || 'Error de conexión');
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(result => {
                    if (!result.isConfirmed) return;
                    // Sale con animación y se quita sin recargar la página
                    p._saliendo = true;
                    setTimeout(() => {
                        this.personal = this.personal.filter(x => x.id !== p.id);
                        this.animarResumen();
                    }, 330);
                    avisoPersonal((result.value && result.value.message) || 'Registro eliminado.', 'success');
                });
            },
            escapar(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
        };
    }
</script>
@endsection