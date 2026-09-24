<!-- MODAL CREAR / EDITAR CONSULTORIO -->
<template x-teleport="body">
    <div x-show="openModal"
         @keydown.escape.window="openModal = false"
         class="fixed inset-0 z-[99999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
         x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden my-auto"
             @click.outside="openModal = false"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100">

            <!-- Encabezado -->
            <div class="relative px-7 py-6 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white overflow-hidden">
                <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                <div class="absolute right-14 -bottom-10 w-20 h-20 rounded-full bg-white/5"></div>

                <div class="relative flex items-center gap-4">
                    <span class="w-11 h-11 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center shrink-0">
                        <i class="bi text-lg text-emerald-300" :class="modoEdicion ? 'bi-pencil-square' : 'bi-door-open-fill'"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-black tracking-tight" x-text="modoEdicion ? 'Editar consultorio' : 'Nuevo consultorio'"></h3>
                        <p class="text-[11px] text-teal-100/80 font-medium mt-0.5"
                           x-text="modoEdicion ? 'Actualiza los datos del espacio.' : 'Registra un nuevo espacio para asignar citas.'"></p>
                    </div>
                    <button type="button" @click="openModal = false"
                            class="ml-auto w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <form @submit.prevent="guardar" class="p-7 space-y-5">

                <!-- Nombre -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide mb-1.5">Nombre del consultorio</label>
                    <div class="relative">
                        <i class="bi bi-door-open absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" x-model="form.nombre" required maxlength="80"
                               placeholder="Ej. Consultorio 101, Pediatría..."
                               class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl pl-11 pr-4 py-3 text-xs font-bold text-slate-800 placeholder:font-medium placeholder:text-slate-300 outline-none transition-all focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                    </div>
                </div>

                <!-- Piso -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide mb-1.5">Piso</label>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="form.piso = Math.max(0, (parseInt(form.piso) || 0) - 1)"
                                class="w-11 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Bajar piso">
                            <i class="bi bi-dash-lg text-sm"></i>
                        </button>
                        <div class="relative flex-1">
                            <i class="bi bi-layers absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="number" min="0" x-model="form.piso" required placeholder="1"
                                   class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl pl-11 pr-4 py-3 text-xs font-bold text-slate-800 text-center outline-none transition-all focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                        </div>
                        <button type="button"
                                @click="form.piso = (parseInt(form.piso) || 0) + 1"
                                class="w-11 h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-all cursor-pointer shrink-0" title="Subir piso">
                            <i class="bi bi-plus-lg text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wide mb-1.5">Estado</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="form.estado = '1'"
                                :class="String(form.estado) === '1'
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                    : 'border-slate-200 bg-white text-slate-400 hover:border-slate-300'"
                                class="flex items-center justify-center gap-2 rounded-2xl border-2 py-3 text-xs font-black transition-all cursor-pointer">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Activo
                        </button>
                        <button type="button" @click="form.estado = '0'"
                                :class="String(form.estado) === '0'
                                    ? 'border-rose-400 bg-rose-50 text-rose-600'
                                    : 'border-slate-200 bg-white text-slate-400 hover:border-slate-300'"
                                class="flex items-center justify-center gap-2 rounded-2xl border-2 py-3 text-xs font-black transition-all cursor-pointer">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span> Inactivo
                        </button>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex justify-end gap-3 pt-5 border-t border-slate-100">
                    <button type="button" @click="openModal = false"
                            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-check-lg text-sm"></i>
                        <span x-text="modoEdicion ? 'Guardar cambios' : 'Registrar consultorio'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>