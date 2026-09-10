<!-- MODAL CREAR NUEVO PACIENTE -->
@if(auth()->user()->tienePermiso('Pacientes', 'crear'))
    <template x-teleport="body">
        <div x-show="openCreateModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[9999] bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4" 
             x-cloak
             x-data="{
                 tab: 'general',
                 alergias: [''],
                 condiciones: [''],
                 medicamentos: [''],
                 addAlergia() { this.alergias.push('') },
                 removeAlergia(index) { if(this.alergias.length > 1) this.alergias.splice(index, 1) },
                 addCondicion() { this.condiciones.push('') },
                 removeCondicion(index) { if(this.condiciones.length > 1) this.condiciones.splice(index, 1) },
                 addMedicamento() { this.medicamentos.push('') },
                 removeMedicamento(index) { if(this.medicamentos.length > 1) this.medicamentos.splice(index, 1) }
             }">
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full h-[90vh] max-h-[750px] border border-teal-100 flex flex-col overflow-hidden my-auto">
                <!-- Header Fijo -->
                <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold">
                            <i class="bi bi-person-plus-fill text-xl text-teal-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-800">Registrar Expediente Clínico</h3>
                            <p class="text-xs text-gray-400">Completa la información personal y médica del paciente</p>
                        </div>
                    </div>
                    <button @click="openCreateModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-2xl font-bold transition-colors">&times;</button>
                </div>

                <!-- Navegación por pestañas simples dentro del modal -->
                <div class="flex border-b border-gray-100 bg-slate-50/50 px-6 pt-2 text-xs font-bold gap-2 flex-shrink-0">
                    <button type="button" @click="tab = 'general'" :class="tab === 'general' ? 'bg-white text-teal-700 border-b-2 border-teal-600 shadow-xs' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 rounded-t-xl transition-all">
                        <i class="bi bi-person-vcard mr-1"></i> Datos Personales
                    </button>
                    <button type="button" @click="tab = 'contacto'" :class="tab === 'contacto' ? 'bg-white text-teal-700 border-b-2 border-teal-600 shadow-xs' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 rounded-t-xl transition-all">
                        <i class="bi bi-telephone-plus mr-1"></i> Contacto y Emergencia
                    </button>
                    <button type="button" @click="tab = 'clinica'" :class="tab === 'clinica' ? 'bg-white text-teal-700 border-b-2 border-teal-600 shadow-xs' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 rounded-t-xl transition-all">
                        <i class="bi bi-heart-pulse mr-1"></i> Ficha Médica
                    </button>
                </div>

                <!-- Formulario Principal -->
                <form action="{{ route('pacientes.store') }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                    @csrf
                    <div class="p-6 space-y-4 flex-1 overflow-y-auto min-h-0 text-xs">
                        
                        <!-- TAB 1: DATOS PERSONALES -->
                        <div x-show="tab === 'general'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block font-bold text-gray-700 mb-1">Nombre Completo *</label>
                                    <input type="text" name="nombre" required placeholder="Ej. María Cortés Moreno" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">RUT / Identificación</label>
                                    <input type="text" name="rut" placeholder="Ej. 12345678-9" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Género</label>
                                    <select name="genero" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                                        <option value="">Seleccione...</option>
                                        <option value="Femenino">Femenino</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Estado Civil</label>
                                    <input type="text" name="estado_civil" placeholder="Ej. Soltero(a), Casado(a)" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Nacionalidad</label>
                                    <input type="text" name="nacionalidad" placeholder="Ej. Mexicana" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Grupo Sanguíneo</label>
                                    <select name="grupo_sanguineo" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                                        <option value="">Seleccione...</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: CONTACTO Y EMERGENCIA -->
                        <div x-show="tab === 'contacto'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Teléfono Principal</label>
                                    <input type="text" name="telefono" placeholder="Ej. 5551234567" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Celular</label>
                                    <input type="text" name="celular" placeholder="Ej. 5510445986" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="block font-bold text-gray-700 mb-1">Correo Electrónico</label>
                                    <input type="email" name="email" placeholder="paciente@ejemplo.com" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="block font-bold text-gray-700 mb-1">Dirección Particular</label>
                                    <input type="text" name="direccion" placeholder="Calle, Número, Colonia, Ciudad" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-3 space-y-3">
                                <h5 class="font-bold text-gray-700 text-[11px] uppercase tracking-wider text-rose-600 flex items-center gap-1">
                                    <i class="bi bi-shield-exclamation"></i> En Caso de Emergencia
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Nombre Contacto</label>
                                        <input type="text" name="contacto_emerg_nombre" placeholder="Familiar o Tutor" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Parentesco / Relación</label>
                                        <input type="text" name="contacto_emerg_relacion" placeholder="Ej. Madre, Esposo" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Teléfono Emergencia</label>
                                        <input type="text" name="contacto_emerg_telefono" placeholder="Ej. 5559876543" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: FICHA MÉDICA DINÁMICA -->
                        <div x-show="tab === 'clinica'" class="space-y-4">
                            <!-- Alergias -->
                            <div class="bg-slate-50/70 p-3.5 rounded-2xl border border-gray-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-rose-700 flex items-center gap-1 text-[11px]">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Alergias
                                    </label>
                                    <button type="button" @click="addAlergia()" class="text-[11px] bg-rose-100 text-rose-800 hover:bg-rose-200 font-bold px-2.5 py-1 rounded-xl transition-all">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                                <template x-for="(alergia, index) in alergias" :key="index">
                                    <div class="flex gap-2">
                                        <input type="text" name="alergias[]" x-model="alergias[index]" placeholder="Ej. Penicilina" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white">
                                        <button type="button" @click="removeAlergia(index)" class="p-2 bg-rose-50 text-rose-600 rounded-xl"><i class="bi bi-trash"></i></button>
                                    </div>
                                </template>
                            </div>

                            <!-- Condiciones -->
                            <div class="bg-slate-50/70 p-3.5 rounded-2xl border border-gray-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-amber-700 flex items-center gap-1 text-[11px]">
                                        <i class="bi bi-activity"></i> Condiciones / Patologías
                                    </label>
                                    <button type="button" @click="addCondicion()" class="text-[11px] bg-amber-100 text-amber-800 hover:bg-amber-200 font-bold px-2.5 py-1 rounded-xl transition-all">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                                <template x-for="(cond, index) in condiciones" :key="index">
                                    <div class="flex gap-2">
                                        <input type="text" name="condiciones[]" x-model="condiciones[index]" placeholder="Ej. Diabetes Tipo 2" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white">
                                        <button type="button" @click="removeCondicion(index)" class="p-2 bg-amber-50 text-amber-600 rounded-xl"><i class="bi bi-trash"></i></button>
                                    </div>
                                </template>
                            </div>

                            <!-- Medicamentos -->
                            <div class="bg-slate-50/70 p-3.5 rounded-2xl border border-gray-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-teal-700 flex items-center gap-1 text-[11px]">
                                        <i class="bi bi-capsule"></i> Medicamentos en Uso
                                    </label>
                                    <button type="button" @click="addMedicamento()" class="text-[11px] bg-teal-100 text-teal-800 hover:bg-teal-200 font-bold px-2.5 py-1 rounded-xl transition-all">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                                <template x-for="(med, index) in medicamentos" :key="index">
                                    <div class="flex gap-2">
                                        <input type="text" name="medicamentos[]" x-model="medicamentos[index]" placeholder="Ej. Losartán 50mg" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white">
                                        <button type="button" @click="removeMedicamento(index)" class="p-2 bg-teal-50 text-teal-600 rounded-xl"><i class="bi bi-trash"></i></button>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Fijo -->
                    <div class="flex justify-between items-center px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="acepta_aviso_privacidad" value="1" checked class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-[11px] text-gray-500 font-semibold">Acepta aviso de privacidad</span>
                        </label>

                        <div class="flex gap-3">
                            <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-semibold">Cancelar</button>
                            <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-teal-600/20">Guardar Paciente</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>
@endif