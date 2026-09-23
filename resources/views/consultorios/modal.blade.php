<!-- MODAL CREAR / EDITAR CONSULTORIO -->
<template x-teleport="body">
    <div x-show="openModal" 
         class="fixed inset-0 z-[99999] bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-4" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full border border-slate-100 flex flex-col overflow-hidden my-auto" @click.outside="openModal = false">
            
            <div class="px-8 py-5 bg-gradient-to-r from-teal-900 to-emerald-800 text-white flex justify-between items-center">
                <h3 class="text-base font-black tracking-tight" x-text="modoEdicion ? 'Editar Consultorio' : 'Nuevo Consultorio'"></h3>
                <button type="button" @click="openModal = false" class="text-white/70 hover:text-white cursor-pointer"><i class="bi bi-x-lg"></i></button>
            </div>

            <form @submit.prevent="guardar" class="p-8 space-y-4 bg-slate-50/50">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Clínica ID *</label>
                    <input type="number" x-model="form.clinica_id" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nombre del Consultorio *</label>
                    <input type="text" x-model="form.nombre" required placeholder="Ej. Consultorio 101, Pediatría..." class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Piso *</label>
                        <input type="text" x-model="form.piso" required placeholder="Ej. 1, 2..." class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Estado *</label>
                        <select x-model="form.estado" required class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none cursor-pointer">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="openModal = false" class="px-5 py-2.5 bg-slate-100 text-slate-700 font-extrabold rounded-2xl text-xs cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 cursor-pointer">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</template>