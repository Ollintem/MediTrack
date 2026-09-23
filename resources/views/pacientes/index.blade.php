@extends('layouts.admin')

@section('content')


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
    openCreateModal: {{ $errors->any() ? 'true' : 'false' }},
    openModalDetalles: false,
    openEditModal: false,
    openAvisoModal: false,
    pacienteSeleccionado: null,
    pacienteEditar: {},
    activeTab: 'general', // Pestaña activa por defecto en el detalle

    verDetalles(paciente) {
        this.pacienteSeleccionado = paciente;
        this.activeTab = 'general';
        this.openModalDetalles = true;
    },

    async abrirEditar(id) {
        try {
            let response = await fetch(`/pacientes/${id}/edit`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Error al obtener expediente');

            let p = await response.json();

            // Mapear campos base y arreglos para la reactividad de Alpine
            p.acepta_aviso_privacidad = Boolean(p.acepta_aviso_privacidad);
            p.alergias_list = p.alergias && p.alergias.length ? p.alergias.map(a => a.descripcion) : [''];
            p.condiciones_list = p.condiciones && p.condiciones.length ? p.condiciones.map(c => c.descripcion) : [''];
            p.medicamentos_list = p.medicamentos && p.medicamentos.length ? p.medicamentos.map(m => m.nombre) : [''];

            this.pacienteEditar = p;
            this.openEditModal = true;
        } catch (error) {
            if (window.notificar) {
                window.notificar('No se pudieron consultar los datos del paciente.', 'error');
            }
        }
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
                    Administra las fichas médicas, historiales de alergias, condiciones preexistentes y medicamentos recetados.
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <!-- Card 1: Total Pacientes -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold border border-sky-100">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Pacientes</p>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 class="text-2xl font-black text-gray-800">{{ $totalPacientes }}</h3>
                    <span class="text-xs font-semibold text-gray-400">registrados</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Pacientes Activos (Estatus Operativo) -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold border border-emerald-100">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pacientes Activos</p>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 class="text-2xl font-black text-gray-800">{{ $pacientesActivos }}</h3>
                    <span class="text-xs font-semibold text-gray-500">en atención</span>
                </div>
            </div>
        </div>
        <div class="hidden sm:block text-right">
            <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-full border border-emerald-200">
                {{ $totalPacientes > 0 ? round(($pacientesActivos / $totalPacientes) * 100) : 0 }}% Activo
            </span>
        </div>
    </div>

    <!-- Card 3: Cumplimiento Normativo -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-100">
                <i class="bi bi-shield-exclamation"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Cumplimiento Normativo</p>
                <div class="flex items-baseline gap-2 mt-0.5">
                    <h3 class="text-2xl font-black text-amber-600">{{ $pendientesNormativa }}</h3>
                    <span class="text-xs font-semibold text-gray-500">incompletos</span>
                </div>
            </div>
        </div>
        <div class="hidden sm:block text-right">
            <span class="text-[11px] font-medium text-gray-400 block">Sin CURP, Privacidad</span>
            <span class="text-[11px] font-medium text-gray-400 block">o Contacto</span>
        </div>
    </div>
</div>


    <!-- TABLA Y BUSCADOR MEJORADOS -->
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
            <div class="relative w-full md:w-80">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" x-model="searchQuery" placeholder="Buscar por nombre, CURP, teléfono..." class="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-gray-200 bg-white text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
            </div>
        </div>

        <!-- Cuerpo de Tabla -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-gray-400 uppercase font-bold tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="p-4">PACIENTE</th>
                        <th class="p-4">IDENTIFICACIÓN / CURP</th>
                        <th class="p-4">CONTACTO</th>
                        <th class="p-4">ALERGIAS / CONDICIONES</th>
                        <th class="p-4">POR CONCLUIR</th>
                        <th class="p-4 text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pacientes as $pac)
                        <tr class="hover:bg-teal-50/20 transition-colors" x-show="!searchQuery || '{{ strtolower($pac->nombre . ' ' . $pac->telefono . ' ' . $pac->rut) }}'.includes(searchQuery.toLowerCase())">
                            
                            <!-- Nombre + Avatar -->
                            <td class="p-4 font-bold text-gray-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-extrabold text-xs flex-shrink-0">
                                        {{ strtoupper(substr($pac->nombre, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-800 leading-snug capitalize">{{ strtolower($pac->nombre) }}</p>
                                        <span class="text-[11px] text-gray-400 font-normal">
                                            {{ $pac->fecha_nacimiento ? \Carbon\Carbon::parse($pac->fecha_nacimiento)->age . ' años' : 'Edad N/D' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- CURP y Género -->
                            <td class="p-4">
                                <p class="font-mono text-gray-800 font-bold uppercase tracking-wider text-[11px]">
                                    {{ $pac->rut ?? 'SIN CURP' }}
                                </p>
                                <span class="text-[10px] text-gray-400 uppercase font-semibold">
                                    {{ $pac->genero ?? 'Sin género' }} • {{ $pac->grupo_sanguineo ?? 'N/D' }}
                                </span>
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

                            <!-- Alergias y Condiciones -->
                            <td class="p-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse ($pac->alergias as $alergia)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                            <i class="bi bi-exclamation-triangle-fill text-rose-500"></i> {{ $alergia->descripcion }}
                                        </span>
                                    @empty
                                    @endforelse

                                    @forelse ($pac->condiciones as $cond)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <i class="bi bi-activity text-amber-600"></i> {{ $cond->descripcion }}
                                        </span>
                                    @empty
                                    @endforelse

                                    @if($pac->alergias->isEmpty() && $pac->condiciones->isEmpty())
                                        <span class="text-gray-400 italic text-[11px]">Sin antecedentes</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Por Concluir (Pendientes de Normativa) -->
                            <td class="p-4">
                                @php
                                    $faltantes = [];
                                    if (empty($pac->rut)) {
                                        $faltantes[] = 'Falta CURP';
                                    }
                                    if (!$pac->acepta_aviso_privacidad) {
                                        $faltantes[] = 'Sin Aviso Privacidad';
                                    }
                                    if (empty($pac->telefono) && empty($pac->celular)) {
                                        $faltantes[] = 'Sin Teléfono/Contacto';
                                    }
                                @endphp

                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse ($faltantes as $faltante)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200/80 flex items-center gap-1">
                                            <i class="bi bi-exclamation-circle-fill text-amber-600"></i> {{ $faltante }}
                                        </span>
                                    @empty
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200/80 flex items-center gap-1">
                                            <i class="bi bi-check-circle-fill text-emerald-600"></i> Expediente Completo
                                        </span>
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

                                    <!-- Botón Editar en la Tabla -->
                                    @if(auth()->user()->tienePermiso('Pacientes', 'editar'))
                                        <button type="button" 
                                                @click="abrirEditar({{ $pac->id }})" 
                                                class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" 
                                                title="Editar Paciente">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    @endif

                                    <!-- Eliminar Paciente -->
                                    @if(auth()->user()->tienePermiso('Pacientes', 'eliminar'))
                                        <button type="button" onclick="confirmarEliminacionPaciente({{ $pac->id }}, '{{ addslashes($pac->nombre) }}')" class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" title="Eliminar Paciente">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-gray-400 italic">
                                <i class="bi bi-people text-4xl mb-2 block text-gray-300"></i>
                                No hay pacientes registrados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL DETALLES DEL PACIENTE (EXPEDIENTE CLÍNICO DETALLADO) -->
<template x-teleport="body">
    <div x-show="openModalDetalles" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[9999] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full border border-teal-100 flex flex-col overflow-hidden my-auto max-h-[90vh]">
            
            <!-- HEADER CON AVATAR Y NOMBRES -->
            <div class="border-b border-gray-100 px-6 py-5 bg-gradient-to-r from-teal-800 to-emerald-700 text-white flex-shrink-0 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 text-white flex items-center justify-center font-extrabold text-lg flex-shrink-0">
                        <span x-text="pacienteSeleccionado?.primer_nombre ? pacienteSeleccionado.primer_nombre.charAt(0) + (pacienteSeleccionado.apellido_paterno ? pacienteSeleccionado.apellido_paterno.charAt(0) : '') : 'P'"></span>
                    </div>
                    <div>
                        <h3 class="text-lg font-black tracking-wide" x-text="`${pacienteSeleccionado?.primer_nombre || ''} ${pacienteSeleccionado?.apellido_paterno || ''} ${pacienteSeleccionado?.apellido_materno || ''}`"></h3>
                        <div class="flex items-center gap-2 text-xs text-teal-200/90 font-medium mt-0.5">
                            <span>Código: <strong x-text="pacienteSeleccionado?.codigo || 'N/A'" class="text-white"></strong></span>
                            <span>•</span>
                            <span x-text="pacienteSeleccionado?.rut ? 'CURP: ' + pacienteSeleccionado.rut : 'SIN CURP'"></span>
                        </div>
                    </div>
                </div>
                <button @click="openModalDetalles = false" class="text-white/80 hover:text-white text-2xl font-bold transition-colors focus:outline-none">&times;</button>
            </div>

            <!-- BARRA DE PESTAÑAS (TABS DE NAVEGACIÓN) -->
            <div class="flex border-b border-gray-200 bg-slate-50/80 px-6 pt-3 text-xs font-bold gap-2 flex-shrink-0">
                <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'border-teal-600 text-teal-700 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                    <i class="bi bi-person-vcard text-sm"></i> Información General
                </button>
                <button @click="activeTab = 'clinico'" :class="activeTab === 'clinico' ? 'border-teal-600 text-teal-700 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                    <i class="bi bi-heart-pulse text-sm"></i> Antecedentes Médicos
                </button>
                <button @click="activeTab = 'contacto'" :class="activeTab === 'contacto' ? 'border-teal-600 text-teal-700 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                    <i class="bi bi-shield-check text-sm"></i> Legales y Contacto
                </button>
            </div>

            <!-- CUERPO PRINCIPAL DEL EXPEDIENTE (SCROLLABLE) -->
            <div class="p-6 overflow-y-auto space-y-6 text-xs flex-1">

                <!-- 1. PESTAÑA: INFORMACIÓN GENERAL -->
                <div x-show="activeTab === 'general'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Fecha de Nacimiento</span>
                            <span class="font-bold text-gray-800 text-xs" x-text="pacienteSeleccionado?.fecha_nacimiento || 'No registrada'"></span>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Género</span>
                            <span class="font-bold text-gray-800 text-xs" x-text="pacienteSeleccionado?.genero || 'No especificado'"></span>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Grupo Sanguíneo</span>
                            <span class="font-extrabold text-teal-700 text-xs" x-text="pacienteSeleccionado?.grupo_sanguineo || 'N/D'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Estado Civil</span>
                            <span class="font-bold text-gray-800 text-xs" x-text="pacienteSeleccionado?.estado_civil || 'N/A'"></span>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Nacionalidad</span>
                            <span class="font-bold text-gray-800 text-xs" x-text="pacienteSeleccionado?.nacionalidad || 'N/A'"></span>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Estado en Sistema</span>
                            <span class="font-bold text-xs inline-block px-2 py-0.5 rounded-full mt-0.5" 
                                  :class="pacienteSeleccionado?.estado === 'Activo' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200'"
                                  x-text="pacienteSeleccionado?.estado || 'Activo'"></span>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-gray-100 space-y-1">
                        <span class="text-gray-400 font-bold uppercase block text-[10px]">Domicilio Particular</span>
                        <p class="font-bold text-gray-800 text-xs leading-relaxed" x-text="pacienteSeleccionado?.direccion || 'Sin dirección asignada'"></p>
                    </div>
                </div>

                <!-- 2. PESTAÑA: ANTECEDENTES MÉDICOS DETALLADOS -->
                <div x-show="activeTab === 'clinico'" class="space-y-5">
                    <!-- Alergias -->
                    <div class="bg-rose-50/50 p-4 rounded-2xl border border-rose-100">
                        <h4 class="font-extrabold text-rose-800 uppercase tracking-wider mb-2.5 flex items-center gap-2 text-xs">
                            <i class="bi bi-exclamation-triangle-fill text-rose-600"></i> Alergias Conocidas
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="alergia in pacienteSeleccionado?.alergias" :key="alergia.id">
                                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-white text-rose-800 border border-rose-200 shadow-xs" x-text="alergia.descripcion"></span>
                            </template>
                            <span x-show="!pacienteSeleccionado?.alergias?.length" class="text-gray-400 italic text-xs">Sin alergias registradas en el expediente.</span>
                        </div>
                    </div>

                    <!-- Condiciones Médicas -->
                    <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-100">
                        <h4 class="font-extrabold text-amber-800 uppercase tracking-wider mb-2.5 flex items-center gap-2 text-xs">
                            <i class="bi bi-activity text-amber-600"></i> Enfermedades o Condiciones Preexistentes
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="cond in pacienteSeleccionado?.condiciones" :key="cond.id">
                                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-white text-amber-800 border border-amber-200 shadow-xs" x-text="cond.descripcion"></span>
                            </template>
                            <span x-show="!pacienteSeleccionado?.condiciones?.length" class="text-gray-400 italic text-xs">Sin condiciones patológicas registradas.</span>
                        </div>
                    </div>

                    <!-- Medicamentos -->
                    <div class="bg-teal-50/50 p-4 rounded-2xl border border-teal-100">
                        <h4 class="font-extrabold text-teal-800 uppercase tracking-wider mb-2.5 flex items-center gap-2 text-xs">
                            <i class="bi bi-capsule text-teal-600"></i> Trátamientos y Medicamentos Actuales
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="med in pacienteSeleccionado?.medicamentos" :key="med.id">
                                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-white text-teal-800 border border-teal-200 shadow-xs" x-text="med.nombre"></span>
                            </template>
                            <span x-show="!pacienteSeleccionado?.medicamentos?.length" class="text-gray-400 italic text-xs">Sin medicamentos prescritos actualmente.</span>
                        </div>
                    </div>
                </div>

                <!-- 3. PESTAÑA: CONTACTO Y NORMATIVA LEGAL -->
                <div x-show="activeTab === 'contacto'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Teléfono Principal</span>
                            <span class="font-bold text-gray-800 text-xs" x-text="pacienteSeleccionado?.telefono || 'N/A'"></span>
                        </div>
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                            <span class="text-gray-400 font-bold uppercase block text-[10px]">Teléfono Celular</span>
                            <span class="font-bold text-gray-800 text-xs" x-text="pacienteSeleccionado?.celular || 'N/A'"></span>
                        </div>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-gray-100">
                        <span class="text-gray-400 font-bold uppercase block text-[10px]">Correo Electrónico</span>
                        <span class="font-bold text-gray-800 text-xs" x-text="pacienteSeleccionado?.email || 'No registrado'"></span>
                    </div>

                    <!-- Contacto de Emergencia -->
                    <div class="p-4 bg-sky-50/60 rounded-2xl border border-sky-100 space-y-2">
                        <h5 class="font-extrabold text-sky-800 uppercase text-[11px] flex items-center gap-1.5">
                            <i class="bi bi-telephone-outbound-fill text-sky-600"></i> Contacto de Emergencia
                        </h5>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px]">Nombre:</span>
                                <strong class="text-gray-800" x-text="pacienteSeleccionado?.contacto_emerg_nombre || 'N/A'"></strong>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px]">Relación:</span>
                                <strong class="text-gray-800" x-text="pacienteSeleccionado?.contacto_emerg_relacion || 'N/A'"></strong>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px]">Teléfono:</span>
                                <strong class="text-gray-800" x-text="pacienteSeleccionado?.contacto_emerg_telefono || 'N/A'"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Estado del Aviso de Privacidad -->
                    <div class="p-3.5 rounded-2xl border flex items-center justify-between"
                         :class="pacienteSeleccionado?.acepta_aviso_privacidad ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
                        <div class="flex items-center gap-3">
                            <i class="bi text-lg" :class="pacienteSeleccionado?.acepta_aviso_privacidad ? 'bi-shield-check text-emerald-600' : 'bi-shield-exclamation text-amber-600'"></i>
                            <div>
                                <p class="font-bold text-xs text-gray-800">Aviso de Privacidad / NOM-004</p>
                                <p class="text-[10px] text-gray-500" x-text="pacienteSeleccionado?.acepta_aviso_privacidad ? 'El paciente firmó y autorizó el tratamiento de datos personales.' : 'Pendiente de aceptación de aviso de privacidad.'"></p>
                            </div>
                        </div>
                        <span class="font-extrabold text-[10px] uppercase px-2.5 py-1 rounded-full border"
                              :class="pacienteSeleccionado?.acepta_aviso_privacidad ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300'"
                              x-text="pacienteSeleccionado?.acepta_aviso_privacidad ? 'Firmado' : 'Pendiente'"></span>
                    </div>
                </div>

            </div>

            <!-- FOOTER -->
            <div class="p-4 border-t border-gray-100 bg-gray-50/80 flex justify-end flex-shrink-0">
                <button type="button" @click="openModalDetalles = false" class="px-6 py-2.5 bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-2xl text-xs transition-all shadow-sm">
                    Cerrar Expediente
                </button>
            </div>

        </div>
    </div>
</template>

    <!-- MODAL CREAR NUEVO PACIENTE -->
    @include('pacientes.modalPacientes')

    <!-- MODAL EDITAR PACIENTE -->
    @include('pacientes.modalEditarPaciente')
</div>


<script>
    // Confirmación de eliminación mejorada con SweetAlert2
function confirmarEliminacionPaciente(id, nombre) {
    Swal.fire({
        title: '¿Eliminar expediente?',
        html: `Estás a punto de eliminar el expediente de <b class="text-teal-700">${nombre}</b>.<br><span class="text-xs text-rose-500">Se eliminarán todos sus historiales médicos y medicamentos registrados.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="bi bi-trash-fill"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-3xl p-6 border border-gray-100 shadow-2xl bg-white font-sans',
            title: 'text-2xl font-extrabold text-gray-800',
            confirmButton: 'px-6 py-2.5 rounded-xl font-bold text-sm shadow-md',
            cancelButton: 'px-6 py-2.5 rounded-xl font-bold text-sm shadow-sm'
        }
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
                    // Muestra el Toast Flotante definido en admin.blade.php
                    window.notificar(data.message || 'Expediente eliminado correctamente.', 'success');
                    
                    // Espera 1 segundo para que sea visible el mensaje y recarga
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    window.notificar(data.message || 'No se pudo eliminar el expediente.', 'error');
                }
            })
            .catch(() => {
                window.notificar('Ocurrió un error al comunicarse con el servidor.', 'error');
            });
        }
    });
}
</script>

<!-- ALERTA DE ERRORES DE VALIDACIÓN CON SWEETALERT2 -->
@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let listaErrores = '';
        @foreach ($errors->all() as $error)
            listaErrores += '• {{ $error }}<br>';
        @endforeach

        Swal.fire({
            title: '¡Atención!',
            html: `<div class="text-sm text-gray-600 mt-2">${listaErrores}</div>`,
            icon: 'warning',
            iconColor: '#f59e0b',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#0d9488',
            target: 'body',
            customClass: {
                container: 'z-[10050]',
                popup: 'rounded-3xl p-6 border border-teal-100 shadow-2xl bg-white',
                title: 'text-2xl font-extrabold text-gray-800',
                confirmButton: 'px-6 py-2.5 rounded-xl font-bold text-sm shadow-md'
            }
        });
    });
</script>
@endif
@endsection