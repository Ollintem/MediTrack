<!-- MODAL EDITAR PACIENTE -->
@if(auth()->user()->tienePermiso('Pacientes', 'editar'))
    <template x-teleport="body">
        <div x-show="openEditModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[9999] bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4" 
             x-cloak
             x-data="{ tabEdit: 'general' }">
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full h-[90vh] max-h-[750px] border border-amber-100 flex flex-col overflow-hidden my-auto">
                <!-- Header Fijo -->
                <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                            <i class="bi bi-pencil-square text-xl text-amber-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-800">Editar Expediente Clínico</h3>
                            <p class="text-xs text-gray-400">Modifica los datos del paciente <span class="font-bold text-amber-600" x-text="pacienteEditar.nombre"></span></p>
                        </div>
                    </div>
                    <button @click="openEditModal = false" type="button" class="text-gray-400 hover:text-gray-600 text-2xl font-bold transition-colors">&times;</button>
                </div>

                <!-- Pestañas de Navegación -->
                <div class="flex border-b border-gray-100 bg-slate-50/50 px-6 pt-2 text-xs font-bold gap-2 flex-shrink-0">
                    <button type="button" @click="tabEdit = 'general'" :class="tabEdit === 'general' ? 'bg-white text-amber-700 border-b-2 border-amber-600 shadow-xs' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 rounded-t-xl transition-all">
                        <i class="bi bi-person-vcard mr-1"></i> Datos Personales
                    </button>
                    <button type="button" @click="tabEdit = 'contacto'" :class="tabEdit === 'contacto' ? 'bg-white text-amber-700 border-b-2 border-amber-600 shadow-xs' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 rounded-t-xl transition-all">
                        <i class="bi bi-telephone-plus mr-1"></i> Contacto y Emergencia
                    </button>
                    <button type="button" @click="tabEdit = 'clinica'" :class="tabEdit === 'clinica' ? 'bg-white text-amber-700 border-b-2 border-amber-600 shadow-xs' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 rounded-t-xl transition-all">
                        <i class="bi bi-heart-pulse mr-1"></i> Ficha Médica
                    </button>
                </div>

                <!-- Formulario Principal -->
                <form :action="`/pacientes/${pacienteEditar.id}`" method="POST" class="flex flex-col flex-1 overflow-hidden">
                    @csrf
                    @method('PUT')
                    
                    <div class="p-6 space-y-4 flex-1 overflow-y-auto min-h-0 text-xs">
                        
                        <!-- TAB 1: DATOS PERSONALES -->
                        <div x-show="tabEdit === 'general'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Nombre(s) *</label>
                                    <input type="text" name="primer_nombre" x-model="pacienteEditar.primer_nombre" required class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Apellido Paterno *</label>
                                    <input type="text" name="apellido_paterno" x-model="pacienteEditar.apellido_paterno" required class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Apellido Materno</label>
                                    <input type="text" name="apellido_materno" x-model="pacienteEditar.apellido_materno" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">CURP</label>
                                    <input type="text" name="rut" x-model="pacienteEditar.rut" maxlength="18" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase tracking-wider font-semibold">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" x-model="pacienteEditar.fecha_nacimiento" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none bg-white font-medium">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Género</label>
                                    <div class="relative">
                                        <select name="genero" x-model="pacienteEditar.genero" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none bg-white appearance-none pr-8">
                                            <option value="">Seleccione género...</option>
                                            <option value="FEMENINO">FEMENINO</option>
                                            <option value="MASCULINO">MASCULINO</option>
                                            <option value="OTRO">OTRO</option>
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Estado Civil</label>
                                    <div class="relative">
                                        <select name="estado_civil" x-model="pacienteEditar.estado_civil" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none bg-white appearance-none pr-8">
                                            <option value="">Seleccione estado civil...</option>
                                            <option value="SOLTERO(A)">SOLTERO(A)</option>
                                            <option value="CASADO(A)">CASADO(A)</option>
                                            <option value="DIVORCIADO(A)">DIVORCIADO(A)</option>
                                            <option value="VIUDO(A)">VIUDO(A)</option>
                                            <option value="UNIÓN LIBRE">UNIÓN LIBRE</option>
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Nacionalidad</label>
                                    <input type="text" name="nacionalidad" x-model="pacienteEditar.nacionalidad" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Grupo Sanguíneo</label>
                                    <div class="relative">
                                        <select name="grupo_sanguineo" x-model="pacienteEditar.grupo_sanguineo" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none bg-white appearance-none pr-8">
                                            <option value="">Seleccione grupo...</option>
                                            @foreach(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'] as $gs)
                                                <option value="{{ $gs }}">{{ $gs }}</option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: CONTACTO Y EMERGENCIA -->
                        <div x-show="tabEdit === 'contacto'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Teléfono Principal</label>
                                    <input type="text" name="telefono" x-model="pacienteEditar.telefono" maxlength="10" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Celular</label>
                                    <input type="text" name="celular" x-model="pacienteEditar.celular" maxlength="10" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="block font-bold text-gray-700 mb-1">Correo Electrónico</label>
                                    <input type="email" name="email" x-model="pacienteEditar.email" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="block font-bold text-gray-700 mb-1">Dirección Particular</label>
                                    <input type="text" name="direccion" x-model="pacienteEditar.direccion" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-3 space-y-3">
                                <h5 class="font-bold text-gray-700 text-[11px] uppercase tracking-wider text-rose-600 flex items-center gap-1">
                                    <i class="bi bi-shield-exclamation"></i> En Caso de Emergencia
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Nombre Contacto</label>
                                        <input type="text" name="contacto_emerg_nombre" x-model="pacienteEditar.contacto_emerg_nombre" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Parentesco / Relación</label>
                                        <input type="text" name="contacto_emerg_relacion" x-model="pacienteEditar.contacto_emerg_relacion" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none uppercase">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Teléfono Emergencia</label>
                                        <input type="text" name="contacto_emerg_telefono" x-model="pacienteEditar.contacto_emerg_telefono" maxlength="10" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: FICHA MÉDICA DINÁMICA -->
                        <div x-show="tabEdit === 'clinica'" class="space-y-4">
                            <!-- Alergias -->
                            <div class="bg-slate-50/70 p-3.5 rounded-2xl border border-gray-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-rose-700 flex items-center gap-1 text-[11px]">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Alergias
                                    </label>
                                    <button type="button" @click="pacienteEditar.alergias_list.push('')" class="text-[11px] bg-rose-100 text-rose-800 hover:bg-rose-200 font-bold px-2.5 py-1 rounded-xl transition-all">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                                <template x-for="(alergia, idx) in pacienteEditar.alergias_list" :key="idx">
                                    <div class="flex gap-2">
                                        <input type="text" name="alergias[]" x-model="pacienteEditar.alergias_list[idx]" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white uppercase">
                                        <button type="button" @click="if(pacienteEditar.alergias_list.length > 1) pacienteEditar.alergias_list.splice(idx, 1)" class="p-2 bg-rose-50 text-rose-600 rounded-xl"><i class="bi bi-trash"></i></button>
                                    </div>
                                </template>
                            </div>

                            <!-- Condiciones -->
                            <div class="bg-slate-50/70 p-3.5 rounded-2xl border border-gray-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-amber-700 flex items-center gap-1 text-[11px]">
                                        <i class="bi bi-activity"></i> Condiciones / Patologías
                                    </label>
                                    <button type="button" @click="pacienteEditar.condiciones_list.push('')" class="text-[11px] bg-amber-100 text-amber-800 hover:bg-amber-200 font-bold px-2.5 py-1 rounded-xl transition-all">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                                <template x-for="(cond, idx) in pacienteEditar.condiciones_list" :key="idx">
                                    <div class="flex gap-2">
                                        <input type="text" name="condiciones[]" x-model="pacienteEditar.condiciones_list[idx]" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white uppercase">
                                        <button type="button" @click="if(pacienteEditar.condiciones_list.length > 1) pacienteEditar.condiciones_list.splice(idx, 1)" class="p-2 bg-amber-50 text-amber-600 rounded-xl"><i class="bi bi-trash"></i></button>
                                    </div>
                                </template>
                            </div>

                            <!-- Medicamentos -->
                            <div class="bg-slate-50/70 p-3.5 rounded-2xl border border-gray-200/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-teal-700 flex items-center gap-1 text-[11px]">
                                        <i class="bi bi-capsule"></i> Medicamentos en Uso
                                    </label>
                                    <button type="button" @click="pacienteEditar.medicamentos_list.push('')" class="text-[11px] bg-teal-100 text-teal-800 hover:bg-teal-200 font-bold px-2.5 py-1 rounded-xl transition-all">
                                        <i class="bi bi-plus"></i> Agregar
                                    </button>
                                </div>
                                <template x-for="(med, idx) in pacienteEditar.medicamentos_list" :key="idx">
                                    <div class="flex gap-2">
                                        <input type="text" name="medicamentos[]" x-model="pacienteEditar.medicamentos_list[idx]" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white uppercase">
                                        <button type="button" @click="if(pacienteEditar.medicamentos_list.length > 1) pacienteEditar.medicamentos_list.splice(idx, 1)" class="p-2 bg-teal-50 text-teal-600 rounded-xl"><i class="bi bi-trash"></i></button>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Fijo -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
                        <button type="button" @click="openEditModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-semibold">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl text-xs font-bold shadow-lg shadow-amber-500/20 active:scale-95 transition-all">Actualizar Paciente</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
@endif