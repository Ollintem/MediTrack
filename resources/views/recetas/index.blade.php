@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* FORZAR MAYÚSCULAS ÚNICAMENTE EN LOS CAMPOS DE TEXTO QUE ESCRIBE EL USUARIO */
    input[type="text"], 
    textarea {
        text-transform: uppercase !important;
    }

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

    /* ESTILOS DE IMPRESIÓN */
    @media print {
        body * {
            visibility: hidden !important;
        }
        #hoja-receta-medica, #hoja-receta-medica * {
            visibility: visible !important;
        }
        #hoja-receta-medica {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
            box-shadow: none !important;
            border: none !important;
            background: white !important;
        }
        .no-print {
            display: none !important;
        }
    }
</style>

<div class="space-y-8 w-full block" x-data="{ 
    searchQuery: '',
    viewMode: 'list',
    openCreateModal: {{ $errors->any() ? 'true' : 'false' }},
    openPrintModal: false,
    printReceta: {},
    medicamentosLista: [
        { nombre: '', dosis: '', frecuencia: '' }
    ],

    addMedicamento() {
        this.medicamentosLista.push({ nombre: '', dosis: '', frecuencia: '' });
    },

    removeMedicamento(index) {
        if (this.medicamentosLista.length > 1) {
            this.medicamentosLista.splice(index, 1);
        }
    },

    abrirReceta(receta) {
        this.printReceta = receta;
        this.openPrintModal = true;
    }
}">
    
    <!-- BANNER HERO -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger" style="animation-delay: 0ms;">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-journal-medical text-emerald-300"></i> Módulo de Consultas
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Recetas Médicas</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Emite, gestiona e imprime las prescripciones e indicaciones médicas vinculadas a los pacientes.
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if(auth()->user()->tienePermiso('Recetas', 'crear'))
                    <button @click="openCreateModal = true" type="button" class="group bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                        <i class="bi bi-plus-circle-fill text-teal-600 group-hover:scale-110 transition-transform duration-300 text-base"></i>
                        <span>Nueva Receta</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-stagger" style="animation-delay: 100ms;">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold border border-teal-100">
                <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Recetas</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $recetas->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold border border-rose-100">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pacientes Prescritos</p>
                <h3 class="text-2xl font-bold text-gray-800">
                    {{ $recetas->pluck('paciente_id')->unique()->count() }}
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

    <!-- TABLA DE RECETAS -->
    <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden animate-stagger" style="animation-delay: 200ms;">
        
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-50 via-teal-50/20 to-transparent">
            <div>
                <h3 class="font-extrabold text-gray-800 text-lg">Historial de Recetas Emitidas</h3>
                <p class="text-xs text-gray-500">Listado general de prescripciones médicas</p>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="relative w-full md:w-80">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" x-model="searchQuery" placeholder="Buscar por paciente, médico o folio..." class="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-gray-200 bg-white text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all uppercase">
                </div>

                <div class="flex items-center bg-slate-100 p-1 rounded-2xl border border-gray-200 shrink-0">
                    <button @click="viewMode = 'list'" type="button" :class="viewMode === 'list' ? 'bg-white text-teal-700 shadow-xs' : 'text-gray-400 hover:text-gray-600'" class="p-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1" title="Vista de Tabla">
                        <i class="bi bi-list-task text-base"></i>
                    </button>
                    <button @click="viewMode = 'grid'" type="button" :class="viewMode === 'grid' ? 'bg-white text-teal-700 shadow-xs' : 'text-gray-400 hover:text-gray-600'" class="p-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1" title="Vista de Tarjetas">
                        <i class="bi bi-grid-fill text-base"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div x-show="viewMode === 'list'" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-gray-400 font-bold uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="p-4">N° RECETA</th>
                        <th class="p-4">PACIENTE</th>
                        <th class="p-4">DIAGNÓSTICO</th>
                        <th class="p-4">MÉDICO</th>
                        <th class="p-4">FECHA</th>
                        <th class="p-4 text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 font-semibold">
                    @forelse ($recetas as $rec)
                        @php
                            $nombrePac = $rec->paciente->nombre_completo ?? trim(($rec->paciente->nombre ?? 'N/A') . ' ' . ($rec->paciente->apellido ?? ''));
                            $nombreMed = 'Dr. ' . ($rec->personal->nombre_completo ?? auth()->user()->nombre_completo);
                            $folio = '#' . str_pad($rec->id, 5, '0', STR_PAD_LEFT);
                            $fechaFmt = \Carbon\Carbon::parse($rec->fecha_emision ?? $rec->fecha)->format('d/m/Y');
                        @endphp
                        <tr class="hover:bg-teal-50/20 transition-colors" x-show="!searchQuery || '{{ strtolower($folio) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($nombrePac) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($nombreMed) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($rec->diagnostico ?? '') }}'.includes(searchQuery.toLowerCase())">
                            <td class="p-4 font-bold text-teal-800">{{ $folio }}</td>
                            <td class="p-4 font-bold text-gray-800 uppercase">{{ $nombrePac }}</td>
                            <td class="p-4 text-teal-800 font-extrabold truncate max-w-[180px] uppercase" title="{{ $rec->diagnostico ?? 'Sin diagnóstico' }}">
                                {{ $rec->diagnostico ?? 'N/D' }}
                            </td>
                            <td class="p-4 text-gray-600 uppercase">{{ $nombreMed }}</td>
                            <td class="p-4 text-gray-500">{{ $fechaFmt }}</td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="abrirReceta({{ json_encode($rec->load(['paciente', 'personal', 'detalles'])) }})" class="bg-teal-600 hover:bg-teal-700 text-white p-2 px-3.5 rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center gap-1.5" title="Ver Receta">
                                        <i class="bi bi-eye-fill text-sm"></i>
                                        <span>Ver Receta</span>
                                    </button>

                                    @if(auth()->user()->tienePermiso('Recetas', 'eliminar'))
                                        <button type="button" onclick="confirmarEliminacionReceta({{ $rec->id }})" class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center" title="Eliminar Receta">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-gray-400 italic">
                                <i class="bi bi-prescription2 text-4xl mb-2 block text-gray-300"></i>
                                No hay recetas registradas en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- TARJETAS GRID -->
        <div x-show="viewMode === 'grid'" class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($recetas as $rec)
                @php
                    $nombrePac = $rec->paciente->nombre_completo ?? trim(($rec->paciente->nombre ?? 'N/A') . ' ' . ($rec->paciente->apellido ?? ''));
                    $nombreMed = 'Dr. ' . ($rec->personal->nombre_completo ?? auth()->user()->nombre_completo);
                    $folio = '#' . str_pad($rec->id, 5, '0', STR_PAD_LEFT);
                    $fechaFmt = \Carbon\Carbon::parse($rec->fecha_emision ?? $rec->fecha)->format('d/m/Y');
                    $inicial = strtoupper(substr($nombrePac, 0, 2));
                @endphp
                
                <div x-show="!searchQuery || '{{ strtolower($folio) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($nombrePac) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($nombreMed) }}'.includes(searchQuery.toLowerCase())" 
                     class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs hover:border-teal-500 hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <span class="text-xs font-extrabold text-teal-800 bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-200">
                                {{ $folio }}
                            </span>
                            <span class="text-xs text-gray-400 font-bold flex items-center gap-1">
                                <i class="bi bi-calendar3"></i> {{ $fechaFmt }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-800 font-black flex items-center justify-center text-xs shrink-0">
                                {{ $inicial }}
                            </div>
                            <div class="truncate">
                                <h4 class="font-bold text-gray-800 text-xs truncate uppercase">{{ $nombrePac }}</h4>
                                <p class="text-[11px] text-teal-700 font-extrabold truncate uppercase">{{ $rec->diagnostico ?? 'Sin diagnóstico' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="abrirReceta({{ json_encode($rec->load(['paciente', 'personal', 'detalles'])) }})" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5 transition">
                            <i class="bi bi-eye-fill"></i> Ver Receta
                        </button>
                        @if(auth()->user()->tienePermiso('Recetas', 'eliminar'))
                            <button onclick="confirmarEliminacionReceta({{ $rec->id }})" type="button" class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs transition" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center text-gray-400 italic">
                    No hay recetas registradas en el sistema.
                </div>
            @endforelse
        </div>
    </div>

    <!-- HOJA CLÍNICA Y DE IMPRESIÓN OFICIAL -->
    <template x-teleport="body">
        <div x-show="openPrintModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[9999] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full border border-teal-100 flex flex-col overflow-hidden my-auto max-h-[92vh]">
                
                <!-- CÓDIGO NUEVO (Abre el PDF generado por DomPDF en pestaña nueva) -->
<a :href="'/recetas/' + printReceta.id + '/pdf'" 
   target="_blank" 
   class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-2 cursor-pointer decoration-none">
    <i class="bi bi-printer-fill"></i>
    <span>Imprimir Ahora</span>
</a>

                <!-- HOJA IMPRESA DE RECETA -->
                <div id="hoja-receta-medica" class="p-8 sm:p-10 space-y-6 text-slate-800 bg-white overflow-y-auto">
                    
                    @php
    $clinicaData = \App\Models\Clinica::first();
    $configData = \App\Models\Configuracion::pluck('valor', 'clave')->toArray();
@endphp

<!-- REEMPLAZO DINÁMICO CON DATOS DE LA CLÍNICA -->
<div class="border-b-2 border-teal-600 pb-4 flex justify-between items-start gap-4">
    <div>
        <!-- Nombre de la Clínica -->
        <h2 class="text-xl font-black text-teal-800 uppercase tracking-tight">
            {{ $clinicaData->nombre ?? ($clinica->nombre ?? 'MEDITRACK') }}
        </h2>
        
        <!-- Dirección -->
        <p class="text-xs text-slate-600 font-bold mt-0.5">
            {{ $clinicaData->direccion ?? ($clinica->direccion ?? 'DIRECCIÓN NO REGISTRADA') }}
        </p>

        <!-- Teléfono, Correo y RUT/RFC -->
        <p class="text-[10px] text-slate-500 font-medium mt-0.5">
            TEL: {{ $clinicaData->telefono ?? ($clinica->telefono ?? 'S/N') }}
            @if(!empty($configData['email']) || !empty($clinicaData->email) || !empty($clinica->email))
                <span class="mx-1">|</span> EMAIL: {{ $configData['email'] ?? ($clinicaData->email ?? $clinica->email) }}
            @endif
            @if(!empty($clinicaData->rut_empresa) || !empty($clinica->rut_empresa))
                <span class="mx-1">|</span> RFC/RUT: {{ $clinicaData->rut_empresa ?? $clinica->rut_empresa }}
            @endif
        </p>
    </div>

    <div class="text-right shrink-0">
        <span class="px-3 py-1 bg-teal-50 text-teal-700 font-bold text-xs rounded-full border border-teal-200 block mb-1">
            RECETA: #<span x-text="String(recetaSeleccionada?.id || 0).padStart(5, '0')"></span>
        </span>
        <span class="text-[11px] font-semibold text-slate-400">
            Fecha: <span x-text="recetaSeleccionada?.fecha_emision || ''"></span>
        </span>
    </div>
</div>

                    <!-- INFORMACIÓN DEL MÉDICO Y PACIENTE -->
                    <div class="grid grid-cols-2 gap-4 bg-slate-50/80 p-4 rounded-2xl border border-slate-200 text-xs">
                        <div class="space-y-1">
                            <p class="text-[10px] font-black uppercase text-teal-700">Médico Prescriptor</p>
                            <p class="font-black text-slate-900 text-sm uppercase" x-text="printReceta.personal ? `Dr. ${printReceta.personal.nombre_completo}` : 'Dr. {{ auth()->user()->nombre_completo }}'"></p>
                            <p class="text-[11px] text-slate-600 font-bold uppercase" x-text="`Céd. Prof: ${printReceta.personal?.cedula_profesional || printReceta.personal?.cedula || '12345678'}`"></p>
                            <p class="text-[11px] text-slate-500 font-semibold uppercase" x-text="printReceta.personal?.especialidad || 'Médico Cirujano y Partero'"></p>
                        </div>

                        <div class="space-y-1 border-l border-slate-200 pl-4">
                            <p class="text-[10px] font-black uppercase text-teal-700">Paciente Atendido</p>
                            <p class="font-black text-slate-900 text-sm uppercase" x-text="printReceta.paciente ? (printReceta.paciente.nombre_completo || `${printReceta.paciente.nombre || ''} ${printReceta.paciente.apellido || ''}`) : 'N/A'"></p>
                            <p class="text-[11px] text-slate-500 font-semibold uppercase" x-text="printReceta.paciente?.rut ? `CURP: ${printReceta.paciente.rut}` : 'Expediente Verificado'"></p>
                        </div>
                    </div>

                    <!-- DIAGNÓSTICO CLÍNICO RESALTADO -->
                    <div class="p-4 bg-teal-50 border-2 border-teal-200 rounded-2xl text-xs space-y-1">
                        <span class="text-[10px] font-black uppercase text-teal-800 flex items-center gap-1.5">
                            <i class="bi bi-stethoscope"></i> Diagnóstico Clínico:
                        </span>
                        <p class="font-black text-slate-900 text-sm uppercase" x-text="printReceta.diagnostico ? printReceta.diagnostico : 'No especificado en la consulta'"></p>
                    </div>

                    <!-- PRESCRIPCIÓN FARMACOLÓGICA -->
                    <div class="space-y-3">
                        <h4 class="text-[11px] font-black uppercase tracking-wider text-teal-800 border-b border-slate-100 pb-1 flex items-center gap-1.5">
                            <i class="bi bi-capsule"></i> Prescripción de Medicamentos
                        </h4>
                        <div class="space-y-2">
                            <template x-for="(d, idx) in printReceta.detalles" :key="idx">
                                <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl flex items-start gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-teal-700 text-white flex items-center justify-center font-black text-xs shrink-0" x-text="idx + 1"></span>
                                    <div class="space-y-0.5 text-xs">
                                        <p class="font-black text-slate-900 text-sm uppercase" x-text="d.medicamento"></p>
                                        <p class="text-teal-800 font-bold uppercase" x-text="`Dosis: ${d.dosis || 'Según indicaciones'} | Frecuencia: ${d.frecuencia || 'N/A'}`"></p>
                                        <p class="text-slate-500 font-semibold uppercase" x-show="d.duracion" x-text="`Duración: ${d.duracion}`"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- INDICACIONES GENERALES -->
                    <template x-if="printReceta.indicaciones_generales">
                        <div class="space-y-1 pt-1">
                            <h4 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Indicaciones y Recomendaciones Generales</h4>
                            <p class="text-xs text-slate-700 bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 font-semibold leading-relaxed uppercase" x-text="printReceta.indicaciones_generales"></p>
                        </div>
                    </template>

                    <!-- FIRMA Y SELLO MÉDICO -->
                    <div class="pt-12 flex justify-center text-center">
                        <div class="w-72 border-t-2 border-slate-300 pt-2 space-y-1">
                            <p class="font-black text-xs text-slate-800 uppercase" x-text="printReceta.personal ? `Dr. ${printReceta.personal.nombre_completo}` : 'Dr. {{ auth()->user()->nombre_completo }}'"></p>
                            <p class="text-[10px] font-extrabold text-slate-600 uppercase" x-text="`Céd. Prof. ${printReceta.personal?.cedula_profesional || printReceta.personal?.cedula || '12345678'}`"></p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Firma y Cédula Profesional</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </template>

    @include('recetas.modalRecetas')
</div>

<script>
function confirmarEliminacionReceta(id) {
    Swal.fire({
        title: '¿Eliminar receta médica?',
        html: `Estás a punto de eliminar la receta <b class="text-teal-700">#${String(id).padStart(5, '0')}</b>.<br><span class="text-xs text-rose-500">Esta acción no se puede deshacer.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="bi bi-trash-fill"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        target: 'body',
        customClass: {
            container: 'z-[10050]',
            popup: 'rounded-3xl p-6 border border-slate-100 shadow-2xl bg-white font-sans',
            title: 'text-xl font-black text-slate-800',
            confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95',
            cancelButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm transition-all active:scale-95'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/recetas/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Dispara la notificación flotante en la esquina superior derecha
                    window.notificar(data.message || 'La receta ha sido eliminada del sistema.', 'success');
                    
                    // Recarga la pantalla tras unos milisegundos para actualizar la lista
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    window.notificar('No se pudo eliminar la receta.', 'error');
                }
            })
            .catch(() => {
                window.notificar('Ocurrió un error al procesar la solicitud.', 'error');
            });
        }
    });
}
</script>
@endsection