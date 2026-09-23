<!-- MODAL EDITAR PACIENTE -->
<template x-teleport="body">
    <div x-show="openEditModal" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[9999] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
        
        <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full border border-teal-100 flex flex-col overflow-hidden my-auto max-h-[90vh]">
            
            <!-- HEADER DEL MODAL -->
            <div class="border-b border-gray-100 px-6 py-5 bg-gradient-to-r from-amber-500 to-amber-600 text-white flex-shrink-0 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/20 text-white flex items-center justify-center font-bold">
                        <i class="bi bi-pencil-square text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold">Editar Expediente Clínico</h3>
                        <p class="text-xs text-amber-100">Modifica los datos del paciente <span class="font-bold uppercase" x-text="`${pacienteEditar?.primer_nombre || ''} ${pacienteEditar?.apellido_paterno || ''}`"></span></p>
                    </div>
                </div>
                <button type="button" @click="openEditModal = false" class="text-white/80 hover:text-white text-2xl font-bold transition-colors focus:outline-none">&times;</button>
            </div>

            <!-- FORMULARIO DE EDICIÓN -->
            <form :action="`/pacientes/${pacienteEditar.id}`" method="POST" x-data="{ tabEditar: 'datos' }" class="flex flex-col flex-1 min-h-0">
                @csrf
                @method('PUT')

                <!-- BARRA DE PESTAÑAS (NAVEGACIÓN INTERNA) -->
                <div class="flex border-b border-gray-200 bg-slate-50 px-6 pt-3 text-xs font-bold gap-2 flex-shrink-0">
                    <button type="button" @click="tabEditar = 'datos'" :class="tabEditar === 'datos' ? 'border-amber-500 text-amber-600 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2 border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                        <i class="bi bi-person-vcard text-sm"></i> Datos Personales
                    </button>
                    <button type="button" @click="tabEditar = 'contacto'" :class="tabEditar === 'contacto' ? 'border-amber-500 text-amber-600 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2 border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                        <i class="bi bi-telephone text-sm"></i> Contacto y Emergencia
                    </button>
                    <button type="button" @click="tabEditar = 'medico'" :class="tabEditar === 'medico' ? 'border-amber-500 text-amber-600 bg-white shadow-xs' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2 border-b-2 rounded-t-xl transition-all flex items-center gap-2">
                        <i class="bi bi-heart-pulse text-sm"></i> Ficha Médica
                    </button>
                </div>

                <!-- CUERPO DEL FORMULARIO CON SCROLL -->
                <div class="p-6 overflow-y-auto space-y-4 text-xs flex-1">
                    
                    <!-- PESTAÑA 1: DATOS PERSONALES -->
                    <div x-show="tabEditar === 'datos'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Nombre(s) *</label>
                                <input type="text" name="primer_nombre" x-model="pacienteEditar.primer_nombre" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Apellido Paterno *</label>
                                <input type="text" name="apellido_paterno" x-model="pacienteEditar.apellido_paterno" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Apellido Materno</label>
                                <input type="text" name="apellido_materno" x-model="pacienteEditar.apellido_materno" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CURP</label>
                                <input type="text" name="rut" x-model="pacienteEditar.rut" maxlength="18" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" x-model="pacienteEditar.fecha_nacimiento" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Género</label>
                                <select name="genero" x-model="pacienteEditar.genero" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    <option value="">Seleccione género...</option>
                                    <option value="MASCULINO">MASCULINO</option>
                                    <option value="FEMENINO">FEMENINO</option>
                                    <option value="OTRO">OTRO</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Estado Civil</label>
                                <select name="estado_civil" x-model="pacienteEditar.estado_civil" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    <option value="">Seleccione estado civil...</option>
                                    <option value="SOLTERO/A">SOLTERO/A</option>
                                    <option value="CASADO/A">CASADO/A</option>
                                    <option value="DIVORCIADO/A">DIVORCIADO/A</option>
                                    <option value="VIUDO/A">VIUDO/A</option>
                                    <option value="UNION LIBRE">UNIÓN LIBRE</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Nacionalidad</label>
                                <input type="text" name="nacionalidad" x-model="pacienteEditar.nacionalidad" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Grupo Sanguíneo</label>
                                <select name="grupo_sanguineo" x-model="pacienteEditar.grupo_sanguineo" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    <option value="">Seleccione grupo...</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA 2: CONTACTO Y EMERGENCIA -->
                    <div x-show="tabEditar === 'contacto'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Teléfono Principal</label>
                                <input type="text" name="telefono" x-model="pacienteEditar.telefono" maxlength="10" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Teléfono Celular</label>
                                <input type="text" name="celular" x-model="pacienteEditar.celular" maxlength="10" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Correo Electrónico</label>
                            <input type="email" name="email" x-model="pacienteEditar.email" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Domicilio Particular</label>
                            <input type="text" name="direccion" x-model="pacienteEditar.direccion" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-semibold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div class="p-4 bg-sky-50 rounded-2xl border border-sky-100 space-y-3">
                            <h5 class="font-bold text-sky-800 text-xs uppercase flex items-center gap-1.5">
                                <i class="bi bi-telephone-outbound-fill text-sky-600"></i> Contacto de Emergencia
                            </h5>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Nombre</label>
                                    <input type="text" name="contacto_emerg_nombre" x-model="pacienteEditar.contacto_emerg_nombre" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold uppercase bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Parentesco</label>
                                    <input type="text" name="contacto_emerg_relacion" x-model="pacienteEditar.contacto_emerg_relacion" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold uppercase bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-600 uppercase mb-1">Teléfono</label>
                                    <input type="text" name="contacto_emerg_telefono" x-model="pacienteEditar.contacto_emerg_telefono" maxlength="10" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-semibold bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA 3: FICHA MÉDICA -->
                    <div x-show="tabEditar === 'medico'" class="space-y-5">
                        
                        <!-- Alergias Dinámicas -->
                        <div class="p-4 bg-rose-50/60 rounded-2xl border border-rose-100 space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="font-extrabold text-rose-800 text-xs uppercase flex items-center gap-1.5">
                                    <i class="bi bi-exclamation-triangle-fill text-rose-600"></i> Alergias Registradas
                                </h5>
                                <button type="button" @click="pacienteEditar.alergias_list.push('')" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-[10px] font-bold shadow-xs transition-all">
                                    + Agregar Alergia
                                </button>
                            </div>
                            
                            <div class="space-y-2">
                                <template x-for="(alergia, index) in pacienteEditar.alergias_list" :key="index">
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="alergias[]" x-model="pacienteEditar.alergias_list[index]" placeholder="Ej. PENICILINA" class="w-full px-3 py-2 rounded-xl border border-rose-200 text-xs font-semibold uppercase bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                                        <button type="button" @click="pacienteEditar.alergias_list.splice(index, 1)" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-100 rounded-xl transition-colors" title="Eliminar">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Condiciones Médicas Dinámicas -->
                        <div class="p-4 bg-amber-50/60 rounded-2xl border border-amber-100 space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="font-extrabold text-amber-800 text-xs uppercase flex items-center gap-1.5">
                                    <i class="bi bi-activity text-amber-600"></i> Condiciones preexistentes / Enfermedades
                                </h5>
                                <button type="button" @click="pacienteEditar.condiciones_list.push('')" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-[10px] font-bold shadow-xs transition-all">
                                    + Agregar Condición
                                </button>
                            </div>
                            
                            <div class="space-y-2">
                                <template x-for="(cond, index) in pacienteEditar.condiciones_list" :key="index">
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="condiciones[]" x-model="pacienteEditar.condiciones_list[index]" placeholder="Ej. HIPERTENSIÓN ARTERIAL" class="w-full px-3 py-2 rounded-xl border border-amber-200 text-xs font-semibold uppercase bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        <button type="button" @click="pacienteEditar.condiciones_list.splice(index, 1)" class="p-2 text-amber-500 hover:text-amber-700 hover:bg-amber-100 rounded-xl transition-colors" title="Eliminar">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Medicamentos Dinámicos -->
                        <div class="p-4 bg-teal-50/60 rounded-2xl border border-teal-100 space-y-3">
                            <div class="flex items-center justify-between">
                                <h5 class="font-extrabold text-teal-800 text-xs uppercase flex items-center gap-1.5">
                                    <i class="bi bi-capsule text-teal-600"></i> Medicamentos Prescritos
                                </h5>
                                <button type="button" @click="pacienteEditar.medicamentos_list.push('')" class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-[10px] font-bold shadow-xs transition-all">
                                    + Agregar Medicamento
                                </button>
                            </div>
                            
                            <div class="space-y-2">
                                <template x-for="(med, index) in pacienteEditar.medicamentos_list" :key="index">
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="medicamentos[]" x-model="pacienteEditar.medicamentos_list[index]" placeholder="Ej. PARACETAMOL 500MG" class="w-full px-3 py-2 rounded-xl border border-teal-200 text-xs font-semibold uppercase bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <button type="button" @click="pacienteEditar.medicamentos_list.splice(index, 1)" class="p-2 text-teal-500 hover:text-teal-700 hover:bg-teal-100 rounded-xl transition-colors" title="Eliminar">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- FOOTER FIJO EDITAR PACIENTE -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex-shrink-0">
                    <div class="flex items-center gap-2 select-none">
                        <input type="checkbox" 
                            name="acepta_aviso_privacidad" 
                            id="acepta_aviso_edit"
                            value="1" 
                            x-model="pacienteEditar.acepta_aviso_privacidad" 
                            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                        
                        <div class="text-[11px] text-gray-600 font-semibold flex items-center gap-1">
                            <label for="acepta_aviso_edit" class="cursor-pointer">Acepta</label>
                            
                            <button type="button" 
                                    @click.prevent.stop="openAvisoModal = true" 
                                    class="text-teal-600 hover:text-teal-700 hover:underline font-bold focus:outline-none cursor-pointer">
                                aviso de privacidad
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="openEditModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-semibold transition-all">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl text-xs font-bold shadow-lg shadow-amber-500/20 active:scale-95 transition-all">Actualizar Paciente</button>
                    </div>
                </div>
                <!-- Incluir el Modal de Aviso de Privacidad Reutilizable -->
                @include('pacientes.modalAvisoPrivacidad')

            </form>

        </div>
    </div>
</template>

