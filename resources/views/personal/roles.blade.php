@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .animate-stagger {
        animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
</style>

<div class="space-y-8" x-data="{ 
    openCreateModal: false, 
    openEditModal: false,
    editRolId: null,
    editRolNombre: '',
    editPermisos: {},

    toggleCheckboxes(containerId, value) {
        const container = document.getElementById(containerId);
        if (container) {
            const checkboxes = container.querySelectorAll('input[type=checkbox]');
            checkboxes.forEach(cb => cb.checked = value);
        }
    },

    cargarEdicion(rol) {
        this.editRolId = rol.id;
        this.editRolNombre = rol.nombre;
        this.editPermisos = {};
        
        if (rol.permisos) {
            rol.permisos.forEach(p => {
                this.editPermisos[p.modulo_id] = {
                    ver: p.puede_ver == 1,
                    crear: p.puede_crear == 1,
                    editar: p.puede_editar == 1,
                    eliminar: p.puede_eliminar == 1
                };
            });
        }
        this.openEditModal = true;
    }
}">
    
    <!-- BANNER HERO PREMIUM -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger" style="animation-delay: 0ms;">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-shield-check text-emerald-300"></i> Control de Seguridad & Accesos
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Gestión de Roles y Permisos</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Establece el alcance operativo de cada cargo dentro de los módulos del sistema MediTrack.
                </p>
            </div>

            <!-- Botón Crear (Protegido por Permiso) -->
            @if(auth()->user()->tienePermiso('Roles', 'crear'))
                <button @click="openCreateModal = true" type="button" class="group bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                    <i class="bi bi-plus-circle-fill text-teal-600 group-hover:rotate-90 transition-transform duration-300 text-base"></i>
                    <span>Nuevo Cargo / Rol</span>
                </button>
            @endif
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-stagger" style="animation-delay: 100ms;">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold border border-teal-100">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Roles Registrados</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $roles->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold border border-sky-100">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Módulos del Sistema</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $modulos->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold border border-emerald-100">
                <i class="bi bi-check-all"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado del Sistema</p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 mt-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Activo & Sincronizado
                </span>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 text-sm text-teal-900 bg-teal-50/90 rounded-2xl border border-teal-200 shadow-sm flex items-center gap-3 animate-stagger" style="animation-delay: 150ms;">
            <i class="bi bi-check-circle-fill text-teal-600 text-xl"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- GRID DE TARJETAS DE ROLES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($roles as $index => $rol)
            <div class="group bg-white rounded-3xl border border-gray-200/70 shadow-sm hover:shadow-xl hover:border-teal-300 transition-all duration-300 flex flex-col justify-between overflow-hidden animate-stagger transform hover:-translate-y-1" style="animation-delay: {{ 200 + ($index * 80) }}ms;">
                <div>
                    <!-- Encabezado de la Tarjeta -->
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-slate-50 via-teal-50/30 to-emerald-50/20 flex items-center justify-between">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-teal-600/20 group-hover:scale-105 transition-transform duration-300">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-gray-800 text-lg leading-snug group-hover:text-teal-700 transition-colors">{{ $rol->nombre }}</h3>
                                <span class="text-[11px] text-teal-700 font-semibold bg-teal-100/70 px-2.5 py-0.5 rounded-md border border-teal-200/50">Cargo Activo</span>
                            </div>
                        </div>

                        <!-- Botones de Acción Verticales (Protegidos por Permisos) -->
                        <div class="flex flex-col gap-1.5">
                            @if(auth()->user()->tienePermiso('Roles', 'editar'))
                                <button type="button" @click="cargarEdicion({{ json_encode($rol) }})" class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" title="Editar Permisos">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            @endif

                            @if(auth()->user()->tienePermiso('Roles', 'eliminar'))
                                <button type="button" onclick="confirmarEliminacion({{ $rol->id }}, '{{ $rol->nombre }}')" class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" title="Eliminar Rol">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Módulos del Sidebar y Permisos -->
                    <div class="p-6 space-y-3">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Módulos Asignados</p>
                            <span class="text-[11px] font-semibold text-gray-500">{{ $modulos->count() }} Módulos</span>
                        </div>
                        
                        @forelse ($modulos as $mod)
                            @php
                                $permiso = $rol->permisos->firstWhere('modulo_id', $mod->id);
                                $tieneAcceso = $permiso && ($permiso->puede_ver || $permiso->puede_crear || $permiso->puede_editar || $permiso->puede_eliminar);
                                
                                $iconos = [
                                    'Pacientes' => 'bi-people-fill',
                                    'Citas' => 'bi-calendar-event-fill',
                                    'Facturación' => 'bi-credit-card-fill',
                                    'Inventario' => 'bi-box-seam-fill',
                                    'Personal' => 'bi-person-badge-fill',
                                    'Roles' => 'bi-shield-lock-fill',
                                    'Reportes' => 'bi-graph-up-arrow',
                                    'Configuración' => 'bi-gear-fill'
                                ];
                                $iconoModulo = $iconos[$mod->nombre] ?? 'bi-app-indicator';
                            @endphp
                            
                            <div class="p-3 rounded-2xl border transition-all duration-200 {{ $tieneAcceso ? 'bg-slate-50/70 border-gray-200/80 hover:border-teal-200 hover:bg-teal-50/20' : 'bg-gray-50/30 border-dashed border-gray-200 opacity-60' }} flex items-center justify-between">
                                <span class="text-xs font-bold {{ $tieneAcceso ? 'text-gray-800' : 'text-gray-400' }} flex items-center gap-2.5">
                                    <i class="bi {{ $iconoModulo }} {{ $tieneAcceso ? 'text-teal-600' : 'text-gray-300' }} text-sm"></i> 
                                    {{ $mod->nombre }}
                                </span>

                                <div class="flex gap-1 text-[10px] font-bold">
                                    <span class="px-2 py-0.5 rounded-md transition-colors {{ ($permiso && $permiso->puede_ver) ? 'bg-teal-100 text-teal-800 border border-teal-200/80 shadow-2xs' : 'bg-gray-100 text-gray-300' }}">V</span>
                                    <span class="px-2 py-0.5 rounded-md transition-colors {{ ($permiso && $permiso->puede_crear) ? 'bg-emerald-100 text-emerald-800 border border-emerald-200/80 shadow-2xs' : 'bg-gray-100 text-gray-300' }}">C</span>
                                    <span class="px-2 py-0.5 rounded-md transition-colors {{ ($permiso && $permiso->puede_editar) ? 'bg-amber-100 text-amber-800 border border-amber-200/80 shadow-2xs' : 'bg-gray-100 text-gray-300' }}">E</span>
                                    <span class="px-2 py-0.5 rounded-md transition-colors {{ ($permiso && $permiso->puede_eliminar) ? 'bg-rose-100 text-rose-800 border border-rose-200/80 shadow-2xs' : 'bg-gray-100 text-gray-300' }}">D</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic text-center py-4">No hay módulos configurados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-3xl p-12 border border-gray-200 text-center text-gray-500 animate-stagger" style="animation-delay: 200ms;">
                <i class="bi bi-shield-x text-4xl text-gray-300 mb-2 block"></i>
                <p class="font-semibold text-gray-700">No hay roles registrados en el sistema.</p>
            </div>
        @endforelse
    </div>

    <!-- MODAL CREAR NUEVO ROL (TELEPORTADO AL BODY) -->
    @if(auth()->user()->tienePermiso('Roles', 'crear'))
        <template x-teleport="body">
            <div x-show="openCreateModal" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fixed inset-0 z-[9999] bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
                
                <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full h-[80vh] max-h-[620px] border border-teal-100 flex flex-col overflow-hidden my-auto">
                    <!-- Header Fijo -->
                    <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-white flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold">
                                <i class="bi bi-plus-circle-fill text-xl text-teal-600"></i>
                            </div>
                            <h3 class="text-lg font-extrabold text-gray-800">Crear Nuevo Cargo / Rol</h3>
                        </div>
                        <button @click="openCreateModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold transition-colors">&times;</button>
                    </div>

                    <!-- Formulario Flex -->
                    <form action="{{ route('roles.store') }}" method="POST" id="formCrearRol" class="flex flex-col flex-1 overflow-hidden">
                        @csrf
                        <div class="p-6 space-y-4 flex-1 flex flex-col overflow-hidden">
                            <!-- Campo Nombre -->
                            <div class="flex-shrink-0">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nombre del Rol *</label>
                                <input type="text" name="nombre" required placeholder="Ej. Odontólogo, Recepcionista" class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
                            </div>

                            <!-- Cabecera de Permisos -->
                            <div class="flex items-center justify-between flex-shrink-0">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Asignar Permisos</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="toggleCheckboxes('formCrearRol', true)" class="text-xs bg-teal-100/80 text-teal-800 hover:bg-teal-200/80 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                        <i class="bi bi-check-all"></i> Marcar Todos
                                    </button>
                                    <button type="button" @click="toggleCheckboxes('formCrearRol', false)" class="text-xs bg-gray-100 text-gray-600 hover:bg-gray-200 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                        <i class="bi bi-x"></i> Desmarcar Todos
                                    </button>
                                </div>
                            </div>

                            <!-- Tabla con Scroll Exclusivo Interno -->
                            <div class="border border-gray-200/80 rounded-2xl flex-1 overflow-y-auto min-h-0 shadow-2xs">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-teal-800 text-white uppercase font-bold sticky top-0 z-10">
                                        <tr>
                                            <th class="p-3.5">Módulo del Sidebar</th>
                                            <th class="p-3.5 text-center">Ver</th>
                                            <th class="p-3.5 text-center">Crear</th>
                                            <th class="p-3.5 text-center">Editar</th>
                                            <th class="p-3.5 text-center">Eliminar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($modulos as $mod)
                                            <tr class="hover:bg-teal-50/40 transition-colors">
                                                <td class="p-3.5 font-bold text-gray-800">{{ $mod->nombre }}</td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][ver]" value="1" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][crear]" value="1" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][editar]" value="1" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][eliminar]" value="1" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer Fijo -->
                        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
                            <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-sm font-semibold transition-all">Cancelar</button>
                            <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-sm font-bold shadow-lg shadow-teal-600/20 transition-all">Guardar Rol</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif

    <!-- MODAL EDITAR ROL Y PERMISOS (TELEPORTADO AL BODY) -->
    @if(auth()->user()->tienePermiso('Roles', 'editar'))
        <template x-teleport="body">
            <div x-show="openEditModal" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fixed inset-0 z-[9999] bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
                
                <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full h-[80vh] max-h-[620px] border border-teal-100 flex flex-col overflow-hidden my-auto">
                    <!-- Header Fijo -->
                    <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-white flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                                <i class="bi bi-pencil-square text-xl text-amber-600"></i>
                            </div>
                            <h3 class="text-lg font-extrabold text-gray-800">Editar Rol y Permisos</h3>
                        </div>
                        <button @click="openEditModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold transition-colors">&times;</button>
                    </div>

                    <!-- Formulario Flex -->
                    <form :action="`/roles/${editRolId}`" method="POST" id="formEditarRol" class="flex flex-col flex-1 overflow-hidden">
                        @csrf
                        @method('PUT')
                        <div class="p-6 space-y-4 flex-1 flex flex-col overflow-hidden">
                            <!-- Campo Nombre -->
                            <div class="flex-shrink-0">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nombre del Rol *</label>
                                <input type="text" name="nombre" x-model="editRolNombre" required class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
                            </div>

                            <!-- Cabecera de Permisos -->
                            <div class="flex items-center justify-between flex-shrink-0">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Modificar Permisos</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="toggleCheckboxes('formEditarRol', true)" class="text-xs bg-teal-100/80 text-teal-800 hover:bg-teal-200/80 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                        <i class="bi bi-check-all"></i> Marcar Todos
                                    </button>
                                    <button type="button" @click="toggleCheckboxes('formEditarRol', false)" class="text-xs bg-gray-100 text-gray-600 hover:bg-gray-200 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                        <i class="bi bi-x"></i> Desmarcar Todos
                                    </button>
                                </div>
                            </div>

                            <!-- Tabla con Scroll Exclusivo Interno -->
                            <div class="border border-gray-200/80 rounded-2xl flex-1 overflow-y-auto min-h-0 shadow-2xs">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-teal-800 text-white uppercase font-bold sticky top-0 z-10">
                                        <tr>
                                            <th class="p-3.5">Módulo del Sidebar</th>
                                            <th class="p-3.5 text-center">Ver</th>
                                            <th class="p-3.5 text-center">Crear</th>
                                            <th class="p-3.5 text-center">Editar</th>
                                            <th class="p-3.5 text-center">Eliminar</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($modulos as $mod)
                                            <tr class="hover:bg-teal-50/40 transition-colors">
                                                <td class="p-3.5 font-bold text-gray-800">{{ $mod->nombre }}</td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][ver]" value="1" :checked="editPermisos[{{ $mod->id }}]?.ver" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][crear]" value="1" :checked="editPermisos[{{ $mod->id }}]?.crear" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][editar]" value="1" :checked="editPermisos[{{ $mod->id }}]?.editar" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                                <td class="p-3.5 text-center"><input type="checkbox" name="permisos[{{ $mod->id }}][eliminar]" value="1" :checked="editPermisos[{{ $mod->id }}]?.eliminar" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500"></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer Fijo -->
                        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
                            <button type="button" @click="openEditModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-sm font-semibold transition-all">Cancelar</button>
                            <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-sm font-bold shadow-lg shadow-teal-600/20 transition-all">Actualizar Rol</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    @endif
</div>

<script>
function confirmarEliminacion(id, nombre) {
    Swal.fire({
        title: '¿Eliminar rol?',
        text: `Estás a punto de borrar el rol "${nombre}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/roles/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Eliminado', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
</script>
@endsection