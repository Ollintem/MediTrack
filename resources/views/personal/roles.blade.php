@extends('layouts.admin')

@section('content')
@php
    $roles   = $roles ?? collect();
    $modulos = $modulos ?? collect();
    if ($roles instanceof \Illuminate\Database\Eloquent\Collection) {
        $roles->loadMissing('permisos');
    }

    $puedeCrear    = auth()->user()->tienePermiso('Roles', 'crear');
    $puedeEditar   = auth()->user()->tienePermiso('Roles', 'editar');
    $puedeEliminar = auth()->user()->tienePermiso('Roles', 'eliminar');

    $iconos = [
        'Pacientes'     => 'bi-people-fill',
        'Citas'         => 'bi-calendar-event-fill',
        'Facturación'   => 'bi-credit-card-fill',
        'Inventario'    => 'bi-box-seam-fill',
        'Personal'      => 'bi-person-badge-fill',
        'Roles'         => 'bi-shield-lock-fill',
        'Reportes'      => 'bi-graph-up-arrow',
        'Configuración' => 'bi-gear-fill',
        'Recetas'       => 'bi-prescription2',
        'Consultas'     => 'bi-clipboard2-pulse-fill',
        'Consultorios'  => 'bi-building-fill',
        'Bitácora'      => 'bi-journal-text',
    ];

    $modulosJs = $modulos->map(fn ($m) => [
        'id'     => $m->id,
        'nombre' => $m->nombre,
        'icono'  => $iconos[$m->nombre] ?? 'bi-app-indicator',
    ])->values();

    // Rol del usuario actual (para avisar si se quita a sí mismo el acceso a Roles)
    $miRolId = data_get(auth()->user(), 'rol_id') ?? data_get(auth()->user(), 'role_id') ?? data_get(auth()->user(), 'rol.id');
    $moduloRolesId = optional($modulos->firstWhere('nombre', 'Roles'))->id;

    $rolesJs = $roles->map(function ($rol) use ($modulos) {
        $permisos = [];
        foreach ($modulos as $m) {
            $p = $rol->permisos->firstWhere('modulo_id', $m->id);
            $permisos[$m->id] = [
                'ver'      => (bool) ($p->puede_ver ?? false),
                'crear'    => (bool) ($p->puede_crear ?? false),
                'editar'   => (bool) ($p->puede_editar ?? false),
                'eliminar' => (bool) ($p->puede_eliminar ?? false),
            ];
        }

        // Cuántos usuarios tienen este rol (si el modelo tiene la relación)
        $usuarios = $rol->usuarios_count ?? $rol->users_count ?? null;
        if ($usuarios === null) {
            foreach (['usuarios', 'users'] as $rel) {
                if (method_exists($rol, $rel)) {
                    try { $usuarios = $rol->{$rel}()->count(); } catch (\Throwable $e) {}
                    break;
                }
            }
        }

        return ['id' => $rol->id, 'nombre' => $rol->nombre, 'permisos' => $permisos, 'usuarios' => $usuarios];
    })->values();

    $acciones = [
        // clave, texto, icono, color activo
        ['ver',      'Ver',      'bi-eye-fill',    'bg-teal-100 text-teal-800 border-teal-200'],
        ['crear',    'Crear',    'bi-plus-lg',     'bg-emerald-100 text-emerald-800 border-emerald-200'],
        ['editar',   'Editar',   'bi-pencil-fill', 'bg-amber-100 text-amber-800 border-amber-200'],
        ['eliminar', 'Eliminar', 'bi-trash-fill',  'bg-rose-100 text-rose-800 border-rose-200'],
    ];
@endphp

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="space-y-6 w-full block" x-data="moduloRoles()">

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="flotar absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="flotar absolute right-48 -bottom-24 w-56 h-56 rounded-full bg-emerald-400/10" style="animation-delay:-2.5s"></div>
        <svg class="absolute inset-x-0 bottom-2 w-full h-12 opacity-25 pointer-events-none" viewBox="0 0 800 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
            <path class="ecg-linea" d="M0 20 H300 L315 20 L325 6 L338 34 L350 2 L364 38 L374 20 H520 L532 13 L544 20 H800" stroke="#6ee7b7" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        <i class="bi bi-shield-lock-fill escudo absolute right-12 top-4 text-[110px] leading-none text-white/10 pointer-events-none hidden md:block"></i>

        <div class="relative p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wide text-emerald-300 backdrop-blur-md">
                    <i class="bi bi-heart-pulse-fill latido-lento"></i> Seguridad y accesos
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-3">
                    Roles y permisos <i class="bi bi-heart-pulse-fill text-emerald-300 text-2xl latido-lento"></i>
                </h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Define qué puede ver, crear, editar y eliminar cada cargo dentro de los módulos de MediTrack.
                </p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                @if (Route::has('personal.index'))
                    <a href="{{ route('personal.index') }}"
                       class="bg-white/10 hover:bg-white/20 text-white font-bold px-4 py-2.5 rounded-2xl text-xs border border-white/20 backdrop-blur-md transition-all hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="bi bi-people-fill text-emerald-300"></i> Ver personal
                    </a>
                @endif
                @if ($puedeCrear)
                    <button @click="openCreateModal = true" type="button"
                            class="group bg-white text-teal-900 hover:bg-emerald-50 font-black px-5 py-2.5 rounded-2xl text-xs shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-plus-lg text-sm text-teal-700 transition-transform duration-300 group-hover:rotate-90"></i> Nuevo rol
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== RESUMEN ===================== --}}
    @php
        $tarjetas = [
            ['Roles',               'roles',     'bi-person-badge-fill',   'bg-teal-50 text-teal-600'],
            ['Módulos',             'modulos',   'bi-grid-3x3-gap-fill',   'bg-sky-50 text-sky-600'],
            ['Permisos otorgados',  'permisos',  'bi-key-fill',            'bg-amber-50 text-amber-600'],
            ['Cobertura promedio',  'cobertura', 'bi-pie-chart-fill',      'bg-emerald-50 text-emerald-600'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $color])
            <div class="tarjeta-stat group bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center justify-between gap-3 transition-all hover:shadow-md hover:-translate-y-1 hover:border-slate-200"
                 style="animation-delay: {{ $i * 70 }}ms">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ $titulo }}</p>
                    <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums" x-text="resumenMostrado.{{ $clave }} + ('{{ $clave }}' === 'cobertura' ? '%' : '')">0</p>
                </div>
                <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6 {{ $color }}">
                    <i class="bi {{ $icono }}"></i>
                </span>
            </div>
        @endforeach
    </div>

    {{-- ===================== BARRA DE HERRAMIENTAS ===================== --}}
    <div class="aparece flex flex-col sm:flex-row sm:items-center gap-3" style="--d: 200ms">
        <div class="relative flex-1 sm:max-w-sm">
            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="search" x-model.debounce.150ms="busqueda" placeholder="Buscar rol…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-slate-200 bg-white text-xs font-semibold focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all">
        </div>
        <div class="sm:ml-auto flex items-center gap-3">
            <div class="hidden md:flex items-center gap-2 text-[10px] font-bold text-slate-400">
                @foreach ($acciones as [$clave, $texto, $icono, $colorActivo])
                    <span class="flex items-center gap-1"><span class="w-5 h-5 rounded-md border flex items-center justify-center {{ $colorActivo }}"><i class="bi {{ $icono }} text-[9px]"></i></span>{{ $texto }}</span>
                @endforeach
            </div>
            <div class="relative flex bg-slate-100 p-1 rounded-2xl shrink-0 text-[11px] font-bold">
                <span class="absolute top-1 bottom-1 w-[calc(50%-4px)] rounded-xl bg-white shadow-sm transition-all duration-300" :style="vista === 'tarjetas' ? 'left:4px' : 'left:50%'"></span>
                <button type="button" @click="cambiarVista('tarjetas')" class="relative z-10 px-3 py-1.5 rounded-xl flex items-center gap-1.5 cursor-pointer transition-colors" :class="vista === 'tarjetas' ? 'text-teal-700' : 'text-slate-500'">
                    <i class="bi bi-grid-fill"></i> Tarjetas
                </button>
                <button type="button" @click="cambiarVista('matriz')" class="relative z-10 px-3 py-1.5 rounded-xl flex items-center gap-1.5 cursor-pointer transition-colors" :class="vista === 'matriz' ? 'text-teal-700' : 'text-slate-500'">
                    <i class="bi bi-table"></i> Comparar
                </button>
            </div>
        </div>
    </div>

    {{-- ===================== VISTA: TARJETAS ===================== --}}
    <div x-show="vista === 'tarjetas'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 items-start">
        <template x-for="(rol, i) in rolesFiltrados" :key="rol.id">
            <div class="fila-in group bg-white rounded-3xl border border-slate-200/70 shadow-sm hover:shadow-xl hover:shadow-teal-900/5 hover:border-teal-300 transition-all duration-300 overflow-hidden"
                 :style="'--d:' + Math.min(i * 70, 500) + 'ms'" :class="rol._saliendo && 'fila-sale'">

                {{-- Encabezado de la tarjeta --}}
                <div class="p-5 border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-teal-50/40 flex items-center gap-4">
                    {{-- Anillo de cobertura --}}
                    <div class="relative w-14 h-14 shrink-0" :title="'Cobertura: ' + cobertura(rol) + '% de los permisos posibles'">
                        <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56" aria-hidden="true">
                            <circle cx="28" cy="28" r="23" fill="none" stroke="#e2e8f0" stroke-width="5"/>
                            <circle cx="28" cy="28" r="23" fill="none" stroke-width="5" stroke-linecap="round"
                                    :stroke="colorCobertura(cobertura(rol))" stroke-dasharray="144.5"
                                    :stroke-dashoffset="144.5 - 144.5 * cobertura(rol) / 100"
                                    style="transition: stroke-dashoffset 1s cubic-bezier(.16,1,.3,1)"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-[11px] font-black text-slate-700 tabular-nums" x-text="cobertura(rol) + '%'"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-black text-slate-800 text-base leading-snug truncate group-hover:text-teal-700 transition-colors" x-text="rol.nombre"></h3>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                            <span class="text-[10px] font-bold text-teal-700 bg-teal-50 border border-teal-100 rounded-md px-2 py-0.5"
                                  x-text="modulosConAcceso(rol) + ' de ' + modulos.length + ' módulos'"></span>
                            <span x-show="rol.usuarios !== null" class="text-[10px] font-bold text-slate-500 bg-slate-100 rounded-md px-2 py-0.5 flex items-center gap-1">
                                <i class="bi bi-people-fill"></i><span x-text="rol.usuarios + (rol.usuarios === 1 ? ' usuario' : ' usuarios')"></span>
                            </span>
                            <span x-show="esMiRol(rol)" class="text-[10px] font-black text-sky-700 bg-sky-50 border border-sky-100 rounded-md px-2 py-0.5">Tu rol</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if ($puedeEditar)
                            <button type="button" @click="cargarEdicion(rol)" title="Editar permisos"
                                    class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 hover:scale-110 active:scale-95 flex items-center justify-center transition-all cursor-pointer">
                                <i class="bi bi-pencil-fill text-xs"></i>
                            </button>
                        @endif
                        @if ($puedeEliminar)
                            <button type="button" @click="eliminar(rol)" :disabled="esMiRol(rol)"
                                    :title="esMiRol(rol) ? 'No puedes eliminar tu propio rol' : 'Eliminar rol'"
                                    class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:scale-110 active:scale-95 flex items-center justify-center transition-all cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:scale-100">
                                <i class="bi bi-trash-fill text-xs"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Módulos: primero los que tienen acceso --}}
                <div class="p-5 space-y-2">
                    <template x-for="mod in modulosOrdenados(rol).slice(0, rol._verTodo ? 999 : Math.max(modulosConAcceso(rol), 3))" :key="mod.id">
                        <div class="p-2.5 rounded-2xl border flex items-center justify-between gap-2 transition-all"
                             :class="tieneAcceso(rol, mod.id) ? 'bg-slate-50/70 border-slate-200/80 hover:border-teal-200 hover:bg-teal-50/30' : 'bg-white border-dashed border-slate-200 opacity-60'">
                            <span class="text-xs font-bold flex items-center gap-2.5 min-w-0" :class="tieneAcceso(rol, mod.id) ? 'text-slate-800' : 'text-slate-400'">
                                <i class="bi text-sm" :class="[mod.icono, tieneAcceso(rol, mod.id) ? 'text-teal-600' : 'text-slate-300']"></i>
                                <span class="truncate" x-text="mod.nombre"></span>
                            </span>
                            <div class="flex gap-1 shrink-0">
                                @foreach ($acciones as [$clave, $texto, $icono, $colorActivo])
                                    <span class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] border transition-all"
                                          :class="rol.permisos[mod.id]?.{{ $clave }} ? '{{ $colorActivo }}' : 'bg-slate-100 text-slate-300 border-transparent'" title="{{ $texto }}">
                                        <i class="bi {{ $icono }}"></i>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </template>

                    <button type="button" x-show="modulos.length > Math.max(modulosConAcceso(rol), 3)"
                            @click="rol._verTodo = !rol._verTodo"
                            class="w-full text-[11px] font-black text-slate-400 hover:text-teal-700 py-1.5 rounded-xl hover:bg-slate-50 transition-all cursor-pointer flex items-center justify-center gap-1">
                        <i class="bi" :class="rol._verTodo ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        <span x-text="rol._verTodo ? 'Ocultar módulos sin acceso' : '+' + (modulos.length - Math.max(modulosConAcceso(rol), 3)) + ' módulos sin acceso'"></span>
                    </button>
                    <p x-show="!modulos.length" class="text-xs text-slate-400 italic text-center py-4">No hay módulos configurados.</p>
                </div>
            </div>
        </template>
    </div>

    {{-- ===================== VISTA: MATRIZ PARA COMPARAR ===================== --}}
    <div x-show="vista === 'matriz'" x-cloak class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="sticky left-0 z-10 bg-slate-50 text-left py-3.5 px-5 text-[10px] font-black uppercase tracking-wider text-slate-400">Módulo</th>
                        <template x-for="rol in rolesFiltrados" :key="rol.id">
                            <th class="py-3 px-3 text-center min-w-[130px]">
                                <button type="button" @click="{{ $puedeEditar ? 'cargarEdicion(rol)' : '' }}"
                                        class="text-xs font-black text-slate-700 hover:text-teal-700 {{ $puedeEditar ? 'cursor-pointer' : 'cursor-default' }}" x-text="rol.nombre"></button>
                                <p class="text-[10px] font-bold text-slate-400" x-text="cobertura(rol) + '%'"></p>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="mod in modulos" :key="mod.id">
                        <tr class="hover:bg-teal-50/20 transition-colors">
                            <td class="sticky left-0 bg-white py-3 px-5 font-bold text-slate-700 whitespace-nowrap">
                                <i class="bi text-teal-600 mr-2" :class="mod.icono"></i><span x-text="mod.nombre"></span>
                            </td>
                            <template x-for="rol in rolesFiltrados" :key="rol.id + '-' + mod.id">
                                <td class="py-3 px-3">
                                    <div class="flex justify-center gap-1">
                                        @foreach ($acciones as [$clave, $texto, $icono, $colorActivo])
                                            <span class="w-5 h-5 rounded-md flex items-center justify-center text-[9px] border"
                                                  :class="rol.permisos[mod.id]?.{{ $clave }} ? '{{ $colorActivo }}' : 'bg-slate-50 text-slate-200 border-transparent'" title="{{ $texto }}">
                                                <i class="bi {{ $icono }}"></i>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vacío --}}
    <div x-show="!rolesFiltrados.length" x-cloak class="bg-white rounded-3xl border border-slate-100 py-16 text-center">
        <span class="flotar inline-flex w-16 h-16 rounded-3xl bg-teal-50 text-teal-500 items-center justify-center text-3xl mb-3">
            <i class="bi" :class="roles.length ? 'bi-search' : 'bi-shield-plus'"></i>
        </span>
        <p class="text-sm font-black text-slate-600" x-text="roles.length ? 'Ningún rol coincide con la búsqueda' : 'Aún no hay roles registrados'"></p>
        @if ($puedeCrear)
            <button type="button" x-show="!roles.length" @click="openCreateModal = true" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900 cursor-pointer">+ Crear el primero</button>
        @endif
    </div>

    {{-- Modal para crear (usa: openCreateModal) --}}
    @include('personal.modalRoles')

    {{-- ===================== MODAL: EDITAR ROL ===================== --}}
    @if ($puedeEditar)
        <template x-teleport="body">
            <div x-show="openEditModal" x-cloak
                 @keydown.escape.window="cerrarEdicion()"
                 class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-3xl h-[92vh] sm:h-[86vh] sm:max-h-[720px] flex flex-col overflow-hidden"
                     x-show="openEditModal" @click.outside="cerrarEdicion()"
                     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 sm:translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 translate-y-10 sm:translate-y-0 sm:scale-95">

                    {{-- Encabezado --}}
                    <div class="relative px-6 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white shrink-0 overflow-hidden">
                        <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                        <div class="relative flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-xl text-emerald-300 shrink-0" :class="openEditModal && 'icono-pop'">
                                <i class="bi bi-shield-lock-fill"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Editar rol</p>
                                <h3 class="text-base font-black tracking-tight truncate" x-text="editRolNombre || 'Sin nombre'"></h3>
                                <p class="text-[11px] font-bold text-teal-100/80" x-text="totalEdicion + ' de ' + (modulos.length * 4) + ' permisos activos'"></p>
                            </div>
                            <button type="button" @click="cerrarEdicion()"
                                    class="ml-auto w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar (Esc)">
                                <i class="bi bi-x-lg text-xs"></i>
                            </button>
                        </div>
                        <div class="relative mt-4 h-1.5 rounded-full bg-white/15 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-300 to-teal-200 transition-all duration-500" :style="'width:' + (modulos.length ? totalEdicion / (modulos.length * 4) * 100 : 0) + '%'"></div>
                        </div>
                    </div>

                    <form :action="urlRol(editRolId)" method="POST" x-ref="formEditar" @submit="guardandoEdicion = true" class="flex flex-col flex-1 overflow-hidden">
                        @csrf
                        @method('PUT')

                        <div class="p-5 sm:p-6 space-y-4 flex-1 flex flex-col overflow-hidden">
                            <div class="shrink-0">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Nombre del rol *</label>
                                <input type="text" name="nombre" x-model="editRolNombre" required maxlength="80"
                                       class="w-full border-2 border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold text-slate-800 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all">
                            </div>

                            {{-- Atajos --}}
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider mr-1">Permisos</span>
                                <button type="button" @click="plantilla('todo')" class="text-[11px] bg-teal-50 text-teal-800 hover:bg-teal-100 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="bi bi-check-all"></i> Acceso total
                                </button>
                                <button type="button" @click="plantilla('lectura')" class="text-[11px] bg-sky-50 text-sky-800 hover:bg-sky-100 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="bi bi-eye"></i> Solo lectura
                                </button>
                                <button type="button" @click="plantilla('nada')" class="text-[11px] bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="bi bi-x"></i> Quitar todo
                                </button>
                                <span x-show="hayCambios" x-cloak class="ml-auto text-[10px] font-black text-amber-600 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 latido"></span> Cambios sin guardar
                                </span>
                            </div>

                            {{-- Aviso: quitarse acceso a Roles --}}
                            <div x-show="seQuitaAcceso" x-cloak x-transition.opacity
                                 class="shrink-0 flex items-start gap-2 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-2xl px-3.5 py-2.5">
                                <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                                <span>Este es tu rol: si le quitas "Ver" en <b>Roles</b>, ya no podrás entrar a esta pantalla.</span>
                            </div>

                            {{-- Tabla de permisos --}}
                            <div class="border border-slate-200/80 rounded-2xl flex-1 overflow-y-auto min-h-0">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-800 text-white sticky top-0 z-10">
                                        <tr>
                                            <th class="p-3 text-[10px] font-black uppercase tracking-wider">Módulo</th>
                                            @foreach ($acciones as [$clave, $texto, $icono, $colorActivo])
                                                <th class="p-2 text-center">
                                                    <button type="button" @click="alternarColumna('{{ $clave }}')" title="Marcar o desmarcar toda la columna"
                                                            class="text-[10px] font-black uppercase tracking-wider px-2 py-1 rounded-lg hover:bg-white/10 transition-all cursor-pointer">{{ $texto }}</button>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="mod in modulos" :key="mod.id">
                                            <tr class="hover:bg-teal-50/30 transition-colors">
                                                <td class="p-3">
                                                    <button type="button" @click="alternarFila(mod.id)" title="Marcar o desmarcar todo el módulo"
                                                            class="flex items-center gap-2.5 font-bold text-slate-800 hover:text-teal-700 cursor-pointer text-left">
                                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition-colors"
                                                              :class="filaActiva(mod.id) ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-400'">
                                                            <i class="bi" :class="mod.icono"></i>
                                                        </span>
                                                        <span x-text="mod.nombre"></span>
                                                    </button>
                                                </td>
                                                @foreach ($acciones as [$clave, $texto, $icono, $colorActivo])
                                                    <td class="p-2 text-center">
                                                        <label class="inline-flex items-center justify-center cursor-pointer">
                                                            <input type="checkbox" :name="'permisos[' + mod.id + '][{{ $clave }}]'" value="1"
                                                                   x-model="editPermisos[mod.id].{{ $clave }}" @change="ajustarDependencias(mod.id, '{{ $clave }}')" class="sr-only peer">
                                                            <span class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm border-2 border-transparent bg-slate-100 text-slate-300 transition-all duration-200 hover:scale-110 active:scale-90
                                                                         peer-checked:{{ str_replace(' ', ' peer-checked:', $colorActivo) }} peer-focus-visible:ring-2 peer-focus-visible:ring-teal-400"
                                                                  title="{{ $texto }}">
                                                                <i class="bi {{ $icono }}"></i>
                                                            </span>
                                                        </label>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-[10px] font-semibold text-slate-400 shrink-0">
                                <i class="bi bi-info-circle"></i> Al activar Crear, Editar o Eliminar se activa también Ver, porque sin ver el módulo no se puede usar.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2.5 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
                            <button type="button" @click="cerrarEdicion()" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-2xl text-xs font-extrabold transition-all cursor-pointer">Cancelar</button>
                            <button type="submit" :disabled="guardandoEdicion || !editRolNombre.trim()"
                                    class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-black shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                <span x-show="guardandoEdicion" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                <i x-show="!guardandoEdicion" class="bi bi-check-lg"></i>
                                <span x-text="guardandoEdicion ? 'Guardando…' : 'Guardar cambios'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif
</div>

<style>
    @keyframes fadeUp   { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes filaIn   { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes filaSale { to { opacity: 0; transform: scale(.9); } }
    @keyframes iconoPop { 0% { transform: scale(0) rotate(-25deg); } 70% { transform: scale(1.15) rotate(6deg); } 100% { transform: scale(1) rotate(0); } }
    @keyframes flotar   { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes latido   { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: .6; } }
    @keyframes latidoLento { 0%, 100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @keyframes ecg      { from { stroke-dashoffset: 1000; } to { stroke-dashoffset: 0; } }
    @keyframes escudo   { 0%, 100% { transform: rotate(-6deg) translateY(0); } 50% { transform: rotate(4deg) translateY(-8px); } }

    .aparece      { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .tarjeta-stat { animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) backwards; }
    .fila-in      { animation: filaIn .5s cubic-bezier(.16, 1, .3, 1) backwards; animation-delay: var(--d, 0ms); }
    .fila-sale    { animation: filaSale .35s ease-in forwards !important; }
    .icono-pop    { animation: iconoPop .55s cubic-bezier(.34, 1.56, .64, 1) backwards; }
    .flotar       { animation: flotar 5s ease-in-out infinite; }
    .latido       { animation: latido 1.4s ease-in-out infinite; }
    .latido-lento { display: inline-block; animation: latidoLento 1.6s ease-in-out infinite; }
    .ecg-linea    { stroke-dasharray: 160 840; animation: ecg 4s linear infinite; }
    .escudo       { animation: escudo 6s ease-in-out infinite; }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .aparece, .tarjeta-stat, .fila-in, .icono-pop, .flotar, .latido, .latido-lento, .ecg-linea, .escudo { animation: none !important; }
    }
</style>

<script>
    const URL_ROLES = @js(url('roles'));
    const CSRF_ROLES = @js(csrf_token());

    function avisoRoles(mensaje, icono = 'success') {
        if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
    }

    function moduloRoles() {
        const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

        return {
            roles: @json($rolesJs),
            modulos: @json($modulosJs),
            miRolId: @js($miRolId),
            moduloRolesId: @js($moduloRolesId),
            mensajeExito: @js(session('success')),
            errores: @json($errors->all()),

            busqueda: '',
            vista: 'tarjetas',
            resumenMostrado: { roles: 0, modulos: 0, permisos: 0, cobertura: 0 },

            // ----- Modal crear (lo usa personal.modalRoles) -----
            openCreateModal: false,

            // ----- Modal editar -----
            openEditModal: false,
            editRolId: null,
            editRolNombre: '',
            editPermisos: {},
            _original: '',
            guardandoEdicion: false,

            init() {
                try { this.vista = localStorage.getItem('roles_vista') || 'tarjetas'; } catch (e) {}
                this.$nextTick(() => this.animarResumen());
                if (this.mensajeExito) setTimeout(() => avisoRoles(this.mensajeExito, 'success'), 300);
                if (this.errores.length) setTimeout(() => avisoRoles(this.errores[0], 'warning'), 300);
            },

            // ----- Resumen -----
            get resumen() {
                const permisos = this.roles.reduce((n, r) => n + this.contarPermisos(r.permisos), 0);
                const posibles = this.roles.length * this.modulos.length * 4;
                return {
                    roles: this.roles.length,
                    modulos: this.modulos.length,
                    permisos,
                    cobertura: posibles ? Math.round(permisos / posibles * 100) : 0
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

            // ----- Utilidades -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
            get rolesFiltrados() {
                const q = this.normalizar(this.busqueda);
                return this.roles.filter(r => !q || this.normalizar(r.nombre).includes(q));
            },
            cambiarVista(v) { this.vista = v; try { localStorage.setItem('roles_vista', v); } catch (e) {} },
            contarPermisos(permisos) {
                return Object.values(permisos || {}).reduce((n, p) => n + ACCIONES.filter(a => p && p[a]).length, 0);
            },
            cobertura(rol) {
                const posibles = this.modulos.length * 4;
                return posibles ? Math.round(this.contarPermisos(rol.permisos) / posibles * 100) : 0;
            },
            colorCobertura(pct) { return pct >= 75 ? '#0d9488' : pct >= 40 ? '#0ea5e9' : pct > 0 ? '#f59e0b' : '#cbd5e1'; },
            tieneAcceso(rol, modId) { const p = rol.permisos[modId]; return !!(p && ACCIONES.some(a => p[a])); },
            modulosConAcceso(rol) { return this.modulos.filter(m => this.tieneAcceso(rol, m.id)).length; },
            modulosOrdenados(rol) {
                return [...this.modulos].sort((a, b) => Number(this.tieneAcceso(rol, b.id)) - Number(this.tieneAcceso(rol, a.id)));
            },
            esMiRol(rol) { return this.miRolId !== null && String(rol.id) === String(this.miRolId); },
            urlRol(id) { return id ? `${URL_ROLES}/${id}` : '#'; },

            // ----- Edición -----
            cargarEdicion(rol) {
                this.editRolId = rol.id;
                this.editRolNombre = rol.nombre;
                const p = {};
                this.modulos.forEach(m => {
                    const o = rol.permisos[m.id] || {};
                    p[m.id] = { ver: !!o.ver, crear: !!o.crear, editar: !!o.editar, eliminar: !!o.eliminar };
                });
                this.editPermisos = p;
                this._original = JSON.stringify([this.editRolNombre, p]);
                this.guardandoEdicion = false;
                this.openEditModal = true;
            },
            get hayCambios() { return this.openEditModal && JSON.stringify([this.editRolNombre, this.editPermisos]) !== this._original; },
            get totalEdicion() { return this.contarPermisos(this.editPermisos); },
            get seQuitaAcceso() {
                return this.miRolId !== null && String(this.editRolId) === String(this.miRolId)
                    && this.moduloRolesId && this.editPermisos[this.moduloRolesId] && !this.editPermisos[this.moduloRolesId].ver;
            },
            cerrarEdicion() {
                if (!this.openEditModal || this.guardandoEdicion) return;
                if (this.hayCambios) {
                    Swal.fire({
                        title: '¿Descartar los cambios?',
                        text: 'Los permisos que modificaste no se guardarán.',
                        icon: 'question', showCancelButton: true, reverseButtons: true,
                        confirmButtonText: 'Descartar', cancelButtonText: 'Seguir editando',
                        confirmButtonColor: '#f43f5e', cancelButtonColor: '#0d9488',
                        customClass: { container: 'z-[10050]', popup: 'rounded-3xl' }
                    }).then(r => { if (r.isConfirmed) this.openEditModal = false; });
                    return;
                }
                this.openEditModal = false;
            },
            // Crear / editar / eliminar requieren poder ver; quitar "ver" quita lo demás
            ajustarDependencias(modId, accion) {
                const p = this.editPermisos[modId];
                if (accion !== 'ver' && p[accion]) p.ver = true;
                if (accion === 'ver' && !p.ver) { p.crear = false; p.editar = false; p.eliminar = false; }
            },
            filaActiva(modId) { const p = this.editPermisos[modId]; return !!(p && ACCIONES.some(a => p[a])); },
            alternarFila(modId) {
                const p = this.editPermisos[modId];
                const todo = ACCIONES.every(a => p[a]);
                ACCIONES.forEach(a => { p[a] = !todo; });
            },
            alternarColumna(accion) {
                const todo = this.modulos.every(m => this.editPermisos[m.id][accion]);
                this.modulos.forEach(m => {
                    this.editPermisos[m.id][accion] = !todo;
                    this.ajustarDependencias(m.id, accion);
                });
            },
            plantilla(tipo) {
                this.modulos.forEach(m => {
                    const p = this.editPermisos[m.id];
                    p.ver = tipo !== 'nada';
                    p.crear = p.editar = p.eliminar = tipo === 'todo';
                });
            },

            // ----- Eliminar -----
            eliminar(rol) {
                if (this.esMiRol(rol)) return;
                const aviso = rol.usuarios ? `<br><span style="font-size:12px;color:#b45309">${rol.usuarios} ${rol.usuarios === 1 ? 'usuario tiene' : 'usuarios tienen'} este rol asignado.</span>` : '';
                Swal.fire({
                    title: '¿Eliminar rol?',
                    html: `Se eliminará el rol <b>${this.escapar(rol.nombre)}</b> y todos sus permisos.${aviso}<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>`,
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
                            const res = await fetch(`${URL_ROLES}/${rol.id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': CSRF_ROLES, 'Accept': 'application/json' }
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || data.success === false) {
                                throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (data.message || 'No se pudo eliminar el rol.'));
                            }
                            return data;
                        } catch (e) {
                            Swal.showValidationMessage(e.message || 'Error de conexión');
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(result => {
                    if (!result.isConfirmed) return;
                    rol._saliendo = true;
                    setTimeout(() => {
                        this.roles = this.roles.filter(r => r.id !== rol.id);
                        this.animarResumen();
                    }, 330);
                    avisoRoles((result.value && result.value.message) || 'Rol eliminado.', 'success');
                });
            },
            escapar(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
        };
    }
</script>
@endsection