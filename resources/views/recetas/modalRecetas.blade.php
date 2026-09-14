<!-- MODAL CREAR RECETA -->
<template x-teleport="body">
    <div x-show="openCreateModal" class="fixed inset-0 z-[9999] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] border border-teal-100 flex flex-col overflow-hidden my-auto" @click.outside="openCreateModal = false">
            
            <!-- Encabezado del Modal -->
            <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-gradient-to-r from-teal-50 to-emerald-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center shadow-md shadow-teal-600/20">
                        <i class="bi bi-file-earmark-medical text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-800">Nueva Receta Médica</h3>
                        <p class="text-xs text-gray-500">Expedición de receta y toma de signos vitales</p>
                    </div>
                </div>
                <button @click="openCreateModal = false" type="button" class="text-gray-400 hover:text-gray-600 transition-colors text-2xl font-bold w-8 h-8 rounded-full flex items-center justify-center hover:bg-gray-100">&times;</button>
            </div>

            <form action="{{ route('recetas.store') }}" method="POST" class="flex flex-col flex-1 overflow-y-auto p-6 space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    
                    <!-- SELECTOR DE MÉDICO CON BÚSQUEDA -->
                    <div x-data="{
                        openDoc: false,
                        searchDoc: '',
                        selectedDocId: '{{ auth()->user()->personal->id ?? '' }}',
                        selectedDocName: 'Dr. {{ auth()->user()->personal->nombre_completo ?? auth()->user()->nombre_completo }}',
                        doctores: {{ json_encode($doctores->map(fn($d) => ['id' => $d->id, 'nombre' => 'Dr. ' . $d->nombre_completo])) }},
                        get filteredDoctores() {
                            if (!this.searchDoc) return this.doctores;
                            return this.doctores.filter(d => d.nombre.toLowerCase().includes(this.searchDoc.toLowerCase()));
                        }
                    }" class="relative">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5 flex items-center gap-1">
                            <i class="bi bi-person-badge text-teal-600"></i> Médico Prescriptor *
                        </label>
                        
                        @if(auth()->user()->isSuperAdmin() || in_array(strtolower(auth()->user()->rol->nombre ?? ''), ['administrador', 'admin', 'super admin']))
                            <input type="hidden" name="personal_id" :value="selectedDocId" required>
                            
                            <button type="button" @click="openDoc = !openDoc" class="w-full bg-slate-50 border border-gray-300 rounded-2xl p-3 text-left text-xs font-semibold text-gray-800 flex justify-between items-center focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all shadow-xs">
                                <span class="truncate" x-text="selectedDocName || 'Selecciona un médico'"></span>
                                <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
                            </button>

                            <div x-show="openDoc" @click.outside="openDoc = false" class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-xl border border-teal-100 z-50 p-2 space-y-2" x-cloak>
                                <div class="relative">
                                    <i class="bi bi-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                                    <input type="text" x-model="searchDoc" placeholder="Buscar médico..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-500">
                                </div>
                                <div class="max-h-40 overflow-y-auto space-y-1">
                                    <template x-for="doc in filteredDoctores" :key="doc.id">
                                        <button type="button" @click="selectedDocId = doc.id; selectedDocName = doc.nombre; openDoc = false" class="w-full text-left px-3 py-2 text-xs hover:bg-teal-50 rounded-xl font-medium text-gray-700 transition-colors flex items-center justify-between">
                                            <span x-text="doc.nombre"></span>
                                            <i class="bi bi-check-lg text-teal-600" x-show="selectedDocId == doc.id"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        @else
                            <div class="w-full bg-slate-100 border border-gray-200 rounded-2xl p-3 text-xs font-bold text-teal-800 flex items-center gap-2 truncate">
                                <i class="bi bi-person-badge-fill text-teal-600"></i>
                                <span class="truncate">
                                    Dr. {{ auth()->user()->personal->nombre_completo ?? auth()->user()->nombre_completo }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- SELECTOR DE PACIENTE CON BÚSQUEDA -->
                    <div x-data="{
                        openPac: false,
                        searchPac: '',
                        selectedPacId: '',
                        selectedPacName: '',
                        pacientes: {{ json_encode($pacientes->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre_completo ?? trim(($p->nombre ?? '') . ' ' . ($p->apellido ?? ''))])) }},
                        get filteredPacientes() {
                            if (!this.searchPac) return this.pacientes;
                            return this.pacientes.filter(p => p.nombre.toLowerCase().includes(this.searchPac.toLowerCase()));
                        }
                    }" class="relative">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5 flex items-center gap-1">
                            <i class="bi bi-person text-teal-600"></i> Paciente *
                        </label>
                        
                        <input type="hidden" name="paciente_id" :value="selectedPacId" required>
                        
                        <button type="button" @click="openPac = !openPac" class="w-full bg-slate-50 border border-gray-300 rounded-2xl p-3 text-left text-xs font-semibold text-gray-800 flex justify-between items-center focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all shadow-xs">
                            <span class="truncate" x-text="selectedPacName || 'Selecciona un paciente'"></span>
                            <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
                        </button>

                        <div x-show="openPac" @click.outside="openPac = false" class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-xl border border-teal-100 z-50 p-2 space-y-2" x-cloak>
                            <div class="relative">
                                <i class="bi bi-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                                <input type="text" x-model="searchPac" placeholder="Buscar paciente..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-teal-500">
                            </div>
                            <div class="max-h-40 overflow-y-auto space-y-1">
                                <template x-for="pac in filteredPacientes" :key="pac.id">
                                    <button type="button" @click="selectedPacId = pac.id; selectedPacName = pac.nombre; openPac = false" class="w-full text-left px-3 py-2 text-xs hover:bg-teal-50 rounded-xl font-medium text-gray-700 transition-colors flex items-center justify-between">
                                        <span x-text="pac.nombre"></span>
                                        <i class="bi bi-check-lg text-teal-600" x-show="selectedPacId == pac.id"></i>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- FECHA DE EMISIÓN -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5 flex items-center gap-1">
                            <i class="bi bi-calendar-event text-teal-600"></i> Fecha de Emisión *
                        </label>
                        <input type="date" name="fecha_emision" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-50 border border-gray-300 rounded-2xl p-2.5 px-3 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all shadow-xs">
                    </div>
                </div>

                <!-- CAPTURA DE SIGNOS VITALES (Mapeados a la tabla signos_vitales) -->
                <div class="p-4 bg-slate-50 border border-teal-100 rounded-2xl space-y-3">
                    <h4 class="text-xs font-extrabold text-teal-900 uppercase tracking-wide flex items-center gap-1.5 border-b border-teal-200/60 pb-2">
                        <i class="bi bi-heart-pulse-fill text-rose-500"></i> Signos Vitales de la Consulta
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">P.A. Sistólica (mmHg)</label>
                            <input type="number" step="0.1" name="pa_sistolica" placeholder="Ej. 120" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">P.A. Diastólica (mmHg)</label>
                            <input type="number" step="0.1" name="pa_diastolica" placeholder="Ej. 80" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Frec. Cardíaca (bpm)</label>
                            <input type="number" name="frecuencia_cardiaca" placeholder="Ej. 75" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Frec. Respiratoria (rpm)</label>
                            <input type="number" name="frecuencia_respiratoria" placeholder="Ej. 18" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Temperatura (°C)</label>
                            <input type="number" step="0.1" name="temperatura" placeholder="Ej. 36.5" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Sat. Oxígeno (%)</label>
                            <input type="number" step="0.1" name="saturacion_oxigeno" placeholder="Ej. 98" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Peso (kg)</label>
                            <input type="number" step="0.1" name="peso_kg" placeholder="Ej. 70.5" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Talla / Estatura (cm)</label>
                            <input type="number" step="0.1" name="talla_cm" placeholder="Ej. 170" class="w-full bg-white border border-gray-200 rounded-xl p-2 text-xs focus:ring-1 focus:ring-teal-500">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE MEDICAMENTOS -->
                <div class="space-y-4 pt-2">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <h4 class="text-xs font-extrabold text-teal-900 uppercase tracking-wide">Prescripción de Medicamentos</h4>
                        <button type="button" @click="addMedicamento()" class="text-xs bg-teal-600 hover:bg-teal-700 text-white font-bold px-3 py-2 rounded-xl flex items-center gap-1.5 shadow-md transition-all active:scale-95">
                            <i class="bi bi-plus-lg text-sm"></i> Agregar Fármaco
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(med, index) in medicamentosLista" :key="index">
                            <div class="p-4 border border-teal-100 rounded-2xl bg-gradient-to-r from-slate-50/70 to-teal-50/20 space-y-3 relative group">
                                <div class="flex items-center justify-between border-b border-gray-100/60 pb-2">
                                    <span class="text-[11px] font-bold text-teal-700 uppercase flex items-center gap-1">
                                        <i class="bi bi-capsule"></i> Medicamento #<span x-text="index + 1"></span>
                                    </span>
                                    <button type="button" @click="removeMedicamento(index)" x-show="medicamentosLista.length > 1" class="text-rose-400 hover:text-rose-600 hover:bg-rose-50 p-1 rounded-lg text-xs font-bold transition-colors flex items-center gap-1">
                                        <i class="bi bi-trash-fill"></i> Quitar
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nombre Fármaco</label>
                                        <input type="text" :name="`medicamentos[${index}][nombre]`" x-model="med.nombre" placeholder="Ej. Paracetamol" required class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-teal-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Dosis</label>
                                        <input type="text" :name="`medicamentos[${index}][dosis]`" x-model="med.dosis" placeholder="Ej. 500 mg" class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-teal-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Frecuencia / Duración</label>
                                        <input type="text" :name="`medicamentos[${index}][frecuencia]`" x-model="med.frecuencia" placeholder="Ej. C/8 hrs por 5 días" class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-teal-500">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5 flex items-center gap-1">
                        <i class="bi bi-card-text text-teal-600"></i> Indicaciones Generales
                    </label>
                    <textarea name="indicaciones_generales" rows="2" placeholder="Indicaciones dietéticas, reposo o recomendaciones generales..." class="w-full bg-slate-50 border border-gray-300 rounded-2xl p-3 text-xs focus:ring-2 focus:ring-teal-500"></textarea>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-white sticky bottom-0">
                    <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-bold">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 bg-teal-600 text-white rounded-2xl text-xs font-extrabold shadow-lg hover:bg-teal-700 transition-all">
                        <i class="bi bi-check-circle-fill text-sm"></i> Guardar Receta
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>