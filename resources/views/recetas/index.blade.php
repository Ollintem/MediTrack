@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-stagger {
        animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
</style>

<div class="space-y-8 w-full block" x-data="{ 
    openCreateModal: false, 
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

    verImpresion(receta) {
        this.printReceta = receta;
        this.openPrintModal = true;
    }
}">
    
    <!-- BANNER HERO -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-file-earmark-medical-fill text-emerald-300"></i> Módulo de Consultas
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Recetas Médicas</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Emite, gestiona e imprime las prescripciones e indicaciones médicas vinculadas a los pacientes.
                </p>
            </div>

            <button @click="openCreateModal = true" type="button" class="group bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2">
                <i class="bi bi-plus-circle-fill text-teal-600 group-hover:rotate-90 transition-transform duration-300 text-base"></i>
                <span>Nueva Receta</span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 text-sm text-teal-900 bg-teal-50/90 rounded-2xl border border-teal-200 shadow-sm flex items-center gap-3 animate-stagger">
            <i class="bi bi-check-circle-fill text-teal-600 text-xl"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- TABLA DE RECETAS -->
    <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden animate-stagger">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-gray-800 text-base">Historial de Recetas Emitidas</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-gray-500 font-bold uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="p-4">N° Receta</th>
                        <th class="p-4">Paciente</th>
                        <th class="p-4">Médico</th>
                        <th class="p-4">Medicamentos</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($recetas as $rec)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4 font-extrabold text-teal-700">#{{ str_pad($rec->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="p-4 font-bold text-gray-800">
                                {{ $rec->paciente->nombre ?? 'N/A' }} {{ $rec->paciente->apellido ?? '' }}
                            </td>
                            <td class="p-4 font-medium">{{ $rec->personal->nombre ?? 'Dr. Asignado' }}</td>
                            <td class="p-4">
                                <span class="bg-teal-50 text-teal-700 px-2.5 py-1 rounded-lg border border-teal-200 font-bold">
                                    {{ $rec->detalles->count() }} Medicamento(s)
                                </span>
                            </td>
                            <td class="p-4 font-semibold text-gray-500">{{ \Carbon\Carbon::parse($rec->fecha_emision)->format('d/m/Y') }}</td>
                            <td class="p-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('recetas.pdf', $rec->id) }}" target="_blank" class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs transition-all flex items-center justify-center" title="Descargar / Imprimir PDF">
                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                    </a>

                                    <button @click="verImpresion({{ json_encode($rec) }})" type="button" class="bg-teal-500 hover:bg-teal-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs" title="Vista Previa">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    <button onclick="confirmarEliminacion({{ $rec->id }})" type="button" class="bg-gray-400 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs transition-colors" title="Eliminar Receta">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400 italic">No hay recetas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL CREAR RECETA -->
    <template x-teleport="body">
        <div x-show="openCreateModal" class="fixed inset-0 z-[9999] bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full max-h-[85vh] border border-teal-100 flex flex-col overflow-hidden my-auto">
                <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-white">
                    <h3 class="text-lg font-extrabold text-gray-800">Nueva Receta Médica</h3>
                    <button @click="openCreateModal = false" class="text-gray-400 text-2xl font-bold">&times;</button>
                </div>

                <form action="{{ route('recetas.store') }}" method="POST" class="flex flex-col flex-1 overflow-y-auto p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Paciente *</label>
                            <select name="paciente_id" required class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500">
                                <option value="">Selecciona un paciente</option>
                                @foreach($pacientes as $pac)
                                    <option value="{{ $pac->id }}">{{ $pac->nombre }} {{ $pac->apellido ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha de Emisión *</label>
                            <input type="date" name="fecha_emision" value="{{ date('Y-m-d') }}" required class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500">
                        </div>
                    </div>

                    <!-- DETALLES DE MEDICAMENTOS -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Medicamentos e Dosis *</label>
                            <button type="button" @click="addMedicamento()" class="text-xs bg-teal-100 text-teal-800 font-bold px-3 py-1.5 rounded-xl flex items-center gap-1">
                                <i class="bi bi-plus-lg"></i> Agregar Medicamento
                            </button>
                        </div>

                        <template x-for="(med, index) in medicamentosLista" :key="index">
                            <div class="p-3 border border-gray-200 rounded-2xl bg-slate-50/50 space-y-2 relative">
                                <button type="button" @click="removeMedicamento(index)" x-show="medicamentosLista.length > 1" class="absolute top-2 right-2 text-rose-500 hover:text-rose-700 text-sm font-bold">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                    <div>
                                        <input type="text" :name="`medicamentos[${index}][nombre]`" placeholder="Nombre del medicamento" required class="w-full border border-gray-300 rounded-xl p-2 text-xs">
                                    </div>
                                    <div>
                                        <input type="text" :name="`medicamentos[${index}][dosis]`" placeholder="Dosis (Ej. 500mg)" class="w-full border border-gray-300 rounded-xl p-2 text-xs">
                                    </div>
                                    <div>
                                        <input type="text" :name="`medicamentos[${index}][frecuencia]`" placeholder="Frecuencia (c/8hrs por 7 días)" class="w-full border border-gray-300 rounded-xl p-2 text-xs">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Indicaciones Generales</label>
                        <textarea name="indicaciones_generales" rows="2" placeholder="Reposo relativo, abundante agua..." class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-2xl text-sm font-semibold">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-teal-600/20">Guardar Receta</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL VISTA PREVIA IMPRESIÓN -->
    <template x-teleport="body">
        <div x-show="openPrintModal" class="fixed inset-0 z-[9999] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full border border-teal-100 flex flex-col overflow-hidden my-auto">
                <div class="p-8 space-y-6 bg-white text-gray-800">
                    <div class="flex justify-between items-center border-b-2 border-teal-600 pb-4">
                        <div class="flex items-center gap-2 text-teal-600 font-extrabold text-2xl">
                            <i class="bi bi-heart-pulse-fill"></i>
                            <span>MediTrack</span>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            <p>N° Receta: <span class="text-teal-700 font-bold" x-text="`#${String(printReceta.id).padStart(5, '0')}`"></span></p>
                            <p>Fecha: <span x-text="printReceta.fecha_emision"></span></p>
                        </div>
                    </div>

                    <div class="space-y-1 text-xs bg-slate-50 p-3 rounded-xl border border-gray-100">
                        <p><strong>Paciente:</strong> <span x-text="printReceta.paciente ? `${printReceta.paciente.nombre} ${printReceta.paciente.apellido || ''}` : 'N/A'"></span></p>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-xs font-extrabold uppercase text-teal-700">Medicamentos Prescritos</h4>
                        <div class="space-y-2">
                            <template x-for="d in printReceta.detalles">
                                <div class="p-3 bg-teal-50/40 rounded-xl border border-teal-100 text-xs">
                                    <p class="font-bold text-gray-800" x-text="d.medicamento"></p>
                                    <p class="text-gray-600" x-text="`${d.dosis || ''} - ${d.frecuencia || ''}`"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <template x-if="printReceta.indicaciones_generales">
                        <div class="space-y-1">
                            <h4 class="text-xs font-extrabold uppercase text-gray-500">Indicaciones Generales</h4>
                            <p class="text-xs text-gray-600 bg-slate-50 p-3 rounded-xl border border-gray-100" x-text="printReceta.indicaciones_generales"></p>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <button type="button" @click="openPrintModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-2xl text-sm font-semibold">Cerrar</button>
                    <a :href="`/recetas/${printReceta.id}/pdf`" target="_blank" class="px-5 py-2.5 bg-teal-600 text-white rounded-2xl text-sm font-bold flex items-center gap-2 hover:bg-teal-700 transition-all">
                        <i class="bi bi-file-earmark-pdf"></i> Abrir PDF
                    </a>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function confirmarEliminacion(id) {
    Swal.fire({
        title: '¿Eliminar receta?',
        text: `Se borrará la receta #${String(id).padStart(5, '0')} y sus detalles.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Eliminar'
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
                    window.location.reload();
                }
            });
        }
    });
}
</script>
@endsection