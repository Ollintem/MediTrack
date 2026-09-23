<!-- MODAL DE CREAR / EDITAR CITA -->
<template x-teleport="body">
    <div x-show="openCitaModal" 
         class="fixed inset-0 z-[99999] bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-4" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-xl w-full border border-slate-100 flex flex-col overflow-hidden my-auto" @click.outside="openCitaModal = false">
            
            <!-- ENCABEZADO MODAL -->
            <div class="px-8 py-5 flex justify-between items-center text-white shrink-0" :class="modoEdicion ? 'bg-gradient-to-r from-sky-900 to-sky-700' : 'bg-gradient-to-r from-teal-900 to-emerald-800'">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center">
                        <i class="bi text-lg" :class="modoEdicion ? 'bi-pencil-square text-sky-300' : 'bi-calendar-plus text-emerald-300'"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black tracking-tight" x-text="modoEdicion ? 'Editar Cita Médica' : 'Agendar Nueva Cita'"></h3>
                        <p class="text-[11px] text-white/80 font-medium">Gestión rápida de citas en el sistema</p>
                    </div>
                </div>
                <button type="button" @click="openCitaModal = false" class="text-white/70 hover:text-white hover:bg-white/10 w-8 h-8 rounded-xl flex items-center justify-center transition-all">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- FORMULARIO -->
            <form @submit.prevent="guardarCita" class="p-8 space-y-4 bg-slate-50/50 max-h-[75vh] overflow-y-auto">
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Motivo de la Cita *</label>
                    <input type="text" x-model="form.motivo" required placeholder="Ej. Consulta General, Valoración..." class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Paciente *</label>
                        <select x-model="form.paciente_id" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none cursor-pointer">
                            <option value="" disabled>Selecciona un paciente...</option>
                            @forelse($pacientes ?? [] as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre_completo ?? $p->nombre ?? 'Paciente #' . $p->id }}</option>
                            @empty
                                <option value="" disabled>No hay pacientes registrados</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Médico (Personal) *</label>
                        <select x-model="form.personal_id" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none cursor-pointer">
                            <option value="" disabled>Selecciona un médico...</option>
                            @forelse($medicos ?? [] as $m)
                                <option value="{{ $m->id }}">Dr. {{ $m->nombre_completo ?? $m->nombre ?? 'Personal #' . $m->id }}</option>
                            @empty
                                <option value="" disabled>No hay personal registrado</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Consultorio *</label>
                        <select x-model="form.consultorio_id" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none cursor-pointer">
                            <option value="" disabled>Selecciona un consultorio...</option>
                            @forelse($consultorios ?? [] as $cons)
                                <option value="{{ $cons->id }}">{{ $cons->nombre }} (Piso {{ $cons->piso }})</option>
                            @empty
                                <option value="" disabled>No hay consultorios registrados</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Clínica ID *</label>
                        <input type="number" x-model="form.clinica_id" required placeholder="Ej. 1" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Fecha *</label>
                        <input type="date" x-model="form.fecha" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Hora *</label>
                        <input type="time" x-model="form.hora" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Duración (Min)</label>
                        <input type="number" x-model="form.duracion_min" required min="15" step="15" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Tipo de Consulta *</label>
                        <select x-model="form.tipo_consulta" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none cursor-pointer">
                            <option value="Primera Vez">Primera Vez</option>
                            <option value="Seguimiento">Seguimiento</option>
                            <option value="Urgencia">Urgencia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5">Estado *</label>
                        <select x-model="form.estado" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none cursor-pointer">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Confirmada">Confirmada</option>
                            <option value="En curso">En curso</option>
                            <option value="Finalizada">Finalizada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200/80">
                    <button type="button" @click="openCitaModal = false" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-2xl text-xs transition-all">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 text-white font-black rounded-2xl text-xs transition-all shadow-lg active:scale-95 flex items-center gap-2 cursor-pointer" :class="modoEdicion ? 'bg-sky-600 hover:bg-sky-700 shadow-sky-600/20' : 'bg-gradient-to-r from-teal-700 to-emerald-600 hover:from-teal-800 hover:to-emerald-700 shadow-teal-700/20'">
                        <i class="bi bi-floppy-fill"></i> <span x-text="modoEdicion ? 'Actualizar Cita' : 'Guardar Cita'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>