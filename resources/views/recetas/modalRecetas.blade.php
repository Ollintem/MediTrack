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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Médico Prescriptor</label>
                        <div class="w-full bg-slate-100 border border-gray-200 rounded-2xl p-3 text-xs font-bold text-teal-800 flex items-center gap-2 truncate">
                            <i class="bi bi-person-badge-fill text-teal-600"></i>
                            <span class="truncate">
                                Dr. {{ auth()->user()->personal->nombre_completo ?? auth()->user()->nombre_completo }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Paciente *</label>
                        <select name="paciente_id" required class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500">
                            <option value="">Selecciona un paciente</option>
                            @foreach($pacientes as $pac)
                                <option value="{{ $pac->id }}">{{ $pac->nombre_completo ?? trim(($pac->nombre ?? '') . ' ' . ($pac->apellido ?? '')) }}</option>
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
                        <button type="button" @click="addMedicamento()" class="text-xs bg-teal-100 text-teal-800 font-bold px-3 py-1.5 rounded-xl flex items-center gap-1 hover:bg-teal-200 transition-colors">
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
                    <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-2xl text-sm font-semibold hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 bg-teal-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-teal-600/20 hover:bg-teal-700 transition-colors">Guardar Receta</button>
                </div>
            </form>
        </div>
    </div>
</template>