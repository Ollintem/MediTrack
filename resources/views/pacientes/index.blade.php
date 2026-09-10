@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-stagger {
        animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
</style>

<div class="space-y-8 w-full block" x-data="{ 
    searchQuery: '',
    openCreateModal: false,
    openModalDetalles: false,
    pacienteSeleccionado: null,

    verDetalles(paciente) {
        this.pacienteSeleccionado = paciente;
        this.openModalDetalles = true;
    }
}">
    
    <!-- BANNER HERO PREMIUM -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger" style="animation-delay: 0ms;">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-hospital text-emerald-300"></i> Expedientes Clínicos
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Directorio de Pacientes</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Administra las fichas médicas, historiales de alergias, condiciones preexistentes y medicamentos recetados en MediTrack.
                </p>
            </div>

            <!-- Acciones -->
            <div class="flex items-center gap-3">
                @if(auth()->user()->tienePermiso('Pacientes', 'crear'))
                    <button @click="openCreateModal = true" type="button" class="group bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                        <i class="bi bi-person-plus-fill text-teal-600 group-hover:scale-110 transition-transform duration-300 text-base"></i>
                        <span>Nuevo Paciente</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-stagger" style="animation-delay: 100ms;">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold border border-teal-100">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Pacientes</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $pacientes->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold border border-rose-100">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Con Alergias / Cond.</p>
                <h3 class="text-2xl font-bold text-gray-800">
                    {{ $pacientes->filter(fn($p) => $p->alergias->count() > 0 || $p->condiciones->count() > 0)->count() }}
                </h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold border border-emerald-100">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado del Registro</p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 mt-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Al Día
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

    <!-- TABLA Y BUSCADOR -->
    <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden animate-stagger" style="animation-delay: 200ms;">
        <!-- Header de Tabla -->
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-50 via-teal-50/20 to-transparent">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-teal-600/20">
                    <i class="bi bi-person-vcard"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-lg">Pacientes Registrados</h3>
                    <p class="text-xs text-gray-500">Búsqueda rápida e historial médico</p>
                </div>
            </div>

            <!-- Buscador sutil -->
            <div class="relative w-full md:w-72">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" x-model="searchQuery" placeholder="Buscar por nombre, teléfono..." class="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-gray-200 bg-white text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
            </div>
        </div>

        <!-- Cuerpo de Tabla -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-gray-400 uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="p-4">PACIENTE</th>
                        <th class="p-4">CONTACTO</th>
                        <th class="p-4">ALERGIAS / CONDICIONES</th>
                        <th class="p-4">MEDICAMENTOS</th>
                        <th class="p-4 text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pacientes as $pac)
                        <tr class="hover:bg-teal-50/20 transition-colors" x-show="!searchQuery || '{{ strtolower($pac->nombre . ' ' . $pac->telefono) }}'.includes(searchQuery.toLowerCase())">
                            <!-- Nombre + Avatar -->
                            <td class="p-4 font-bold text-gray-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-extrabold text-xs">
                                        {{ strtoupper(substr($pac->nombre, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800 leading-snug">{{ $pac->nombre }}</p>
                                        <span class="text-[11px] text-gray-400 font-normal">
                                            {{ $pac->fecha_nacimiento ? \Carbon\Carbon::parse($pac->fecha_nacimiento)->age . ' años' : 'Edad N/D' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Contacto -->
                            <td class="p-4">
                                <p class="text-gray-700 font-semibold flex items-center gap-1.5">
                                    <i class="bi bi-telephone text-teal-600"></i> {{ $pac->telefono ?? 'N/A' }}
                                </p>
                                <p class="text-[11px] text-gray-400 truncate max-w-[160px]">
                                    <i class="bi bi-envelope"></i> {{ $pac->email ?? 'Sin correo' }}
                                </p>
                            </td>

                            <!-- Alergias -->
                            @forelse ($pac->alergias as $alergia)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    <i class="bi bi-exclamation-triangle-fill text-rose-500"></i> {{ $alergia->descripcion }}
                                </span>
                            @empty
                            @endforelse

                            <!-- Condiciones -->
                            @forelse ($pac->condiciones as $cond)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <i class="bi bi-activity text-amber-600"></i> {{ $cond->descripcion }}
                                </span>
                            @empty
                            @endforelse

                            <!-- Medicamentos -->
                            <td class="p-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse ($pac->medicamentos as $med)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                            <i class="bi bi-capsule text-teal-600"></i> {{ $med->nombre }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 italic text-[11px]">Sin medicamentos</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Acciones -->
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Ver Resumen -->
                                    <button type="button" @click="verDetalles({{ json_encode($pac->load(['alergias', 'condiciones', 'medicamentos'])) }})" class="bg-sky-500 hover:bg-sky-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" title="Ver Expediente">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    @if(auth()->user()->tienePermiso('Pacientes', 'editar'))
                                        <a href="{{ route('pacientes.edit', $pac->id) }}" class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" title="Editar Paciente">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endif

                                    @if(auth()->user()->tienePermiso('Pacientes', 'eliminar'))
                                        <button type="button" onclick="confirmarEliminacionPaciente({{ $pac->id }}, '{{ $pac->nombre }}')" class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" title="Eliminar Paciente">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-400 italic">
                                <i class="bi bi-people text-4xl mb-2 block text-gray-300"></i>
                                No hay pacientes registrados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL DETALLES DEL PACIENTE (EXPEDIENTE RÁPIDO) -->
    <template x-teleport="body">
        <div x-show="openModalDetalles" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[9999] bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full border border-teal-100 flex flex-col overflow-hidden my-auto" x-when="pacienteSeleccionado">
                <!-- Header -->
                <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-teal-800 text-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 text-white flex items-center justify-center font-bold">
                            <i class="bi bi-person-vcard-fill text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold" x-text="pacienteSeleccionado?.nombre"></h3>
                            <p class="text-xs text-teal-200">Resumen Clínico Rápido</p>
                        </div>
                    </div>
                    <button @click="openModalDetalles = false" class="text-white/80 hover:text-white text-2xl font-bold transition-colors">&times;</button>
                </div>

                <!-- Contenido -->
                <div class="p-6 space-y-4 overflow-y-auto max-h-[70vh] text-xs">
                    <!-- Datos Personales -->
                    <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-2xl border border-gray-100">
                        <div>
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Teléfono</span>
                            <span class="font-bold text-gray-800" x-text="pacienteSeleccionado?.telefono || 'N/A'"></span>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Correo</span>
                            <span class="font-bold text-gray-800" x-text="pacienteSeleccionado?.email || 'N/A'"></span>
                        </div>
                    </div>

                    <!-- Alergias -->
                    <div>
                        <h4 class="font-bold text-rose-700 uppercase tracking-wider mb-2 flex items-center gap-1.5 text-[11px]">
                            <i class="bi bi-exclamation-triangle-fill"></i> Alergias Registradas
                        </h4>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="alergia in pacienteSeleccionado?.alergias" :key="alergia.id">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200" x-text="alergia.nombre"></span>
                            </template>
                            <span x-show="!pacienteSeleccionado?.alergias?.length" class="text-gray-400 italic">Sin alergias reportadas</span>
                        </div>
                    </div>

                    <!-- Condiciones -->
                    <div>
                        <h4 class="font-bold text-amber-700 uppercase tracking-wider mb-2 flex items-center gap-1.5 text-[11px]">
                            <i class="bi bi-activity"></i> Condiciones Médicas
                        </h4>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="cond in pacienteSeleccionado?.condiciones" :key="cond.id">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200" x-text="cond.nombre"></span>
                            </template>
                            <span x-show="!pacienteSeleccionado?.condiciones?.length" class="text-gray-400 italic">Sin condiciones patológicas</span>
                        </div>
                    </div>

                    <!-- Medicamentos -->
                    <div>
                        <h4 class="font-bold text-teal-700 uppercase tracking-wider mb-2 flex items-center gap-1.5 text-[11px]">
                            <i class="bi bi-capsule"></i> Medicamentos Actuales
                        </h4>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="med in pacienteSeleccionado?.medicamentos" :key="med.id">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200" x-text="med.nombre"></span>
                            </template>
                            <span x-show="!pacienteSeleccionado?.medicamentos?.length" class="text-gray-400 italic">Sin medicamentos prescritos</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button type="button" @click="openModalDetalles = false" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-2xl text-xs transition-all">Cerrar</button>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL CREAR NUEVO PACIENTE -->
    @include('pacientes.modalPacientes')
</div>

<script>
function confirmarEliminacionPaciente(id, nombre) {
    Swal.fire({
        title: '¿Eliminar expediente?',
        text: `Estás a punto de eliminar el expediente de "${nombre}". Se borrarán sus registros médicos asociados.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/pacientes/${id}`, {
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