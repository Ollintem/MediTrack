<!-- MODAL DE ATENCIÓN MÉDICA — resources/views/consultas/modal.blade.php
     Usa el estado del componente moduloConsultasDoctor() del index (form, paciente, historial, cronometro, etc.). -->
<template x-teleport="body">
    <div x-show="openModalConsulta" x-cloak
         class="fixed inset-0 z-[99998] bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <form @submit.prevent="guardarAtencion(true)" novalidate
              x-show="openModalConsulta"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 translate-y-8 scale-95"
              x-transition:enter-end="opacity-100 translate-y-0 scale-100"
              x-transition:leave="transition ease-in duration-150"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-95"
              class="relative bg-white rounded-[1.75rem] shadow-2xl shadow-slate-900/30 w-full max-w-5xl border border-slate-200/60 grid grid-cols-1 md:grid-cols-[270px_1fr] overflow-hidden my-auto max-h-[92vh] min-h-0">

            <!-- ============ RIEL LATERAL ============ -->
            <div class="relative text-white p-6 flex flex-col overflow-y-auto min-h-0 bg-gradient-to-b from-teal-900 to-emerald-900">
                <div class="absolute -left-10 -bottom-12 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="absolute right-[-2.5rem] top-16 w-24 h-24 rounded-full bg-white/5 pointer-events-none"></div>

                <!-- Encabezado -->
                <div class="relative flex items-start justify-between mb-5">
                    <span class="w-12 h-12 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center text-sm font-black text-emerald-300"
                          :class="openModalConsulta && 'icono-pop'"
                          x-text="iniciales(form.paciente_nombre)"></span>
                    <button type="button" @click="openModalConsulta = false"
                            class="text-white/60 hover:text-white hover:bg-white/10 w-7 h-7 rounded-lg flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                        <i class="bi bi-x-lg text-[11px]"></i>
                    </button>
                </div>

                <p class="relative text-[10px] font-black uppercase tracking-wide text-emerald-300"
                   x-text="soloLectura ? 'Consulta finalizada' : 'Atendiendo a'"></p>
                <h3 class="relative text-base font-black leading-tight mt-0.5" x-text="form.paciente_nombre"></h3>

                <!-- Cronómetro -->
                <div x-show="cronometroActivo && !soloLectura" x-cloak
                     class="relative mt-4 bg-white/10 border border-white/10 rounded-2xl px-4 py-3 flex items-center gap-3">
                    <span class="relative flex w-2.5 h-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                    </span>
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wide text-white/50">Tiempo de consulta</p>
                        <p class="text-lg font-black tabular-nums leading-tight" x-text="cronometro"></p>
                    </div>
                </div>

                <!-- Alergias (alerta) -->
                <div x-show="paciente.alergias" x-cloak class="relative mt-4 bg-rose-500/20 border border-rose-300/30 rounded-2xl px-4 py-3">
                    <p class="text-[9px] font-black uppercase tracking-wide text-rose-200 flex items-center gap-1.5">
                        <i class="bi bi-exclamation-triangle-fill"></i> Alergias
                    </p>
                    <p class="text-xs font-bold mt-1 leading-relaxed" x-text="paciente.alergias"></p>
                </div>

                <!-- Datos del paciente -->
                <div class="relative mt-4 space-y-2 text-[11px] font-semibold" x-show="paciente.edad || paciente.sexo || paciente.telefono" x-cloak>
                    <p class="text-[9px] font-black uppercase tracking-wide text-white/50">Paciente</p>
                    <p x-show="paciente.edad" class="flex items-center gap-2"><i class="bi bi-person-fill text-white/50"></i><span x-text="paciente.edad + ' años'"></span></p>
                    <p x-show="paciente.sexo" class="flex items-center gap-2"><i class="bi bi-gender-ambiguous text-white/50"></i><span x-text="paciente.sexo"></span></p>
                    <p x-show="paciente.telefono" class="flex items-center gap-2"><i class="bi bi-telephone-fill text-white/50"></i><span x-text="paciente.telefono"></span></p>
                </div>

                <!-- Datos de la cita -->
                <div class="relative mt-4 space-y-2 text-[11px] font-semibold" x-show="citaActual">
                    <p class="text-[9px] font-black uppercase tracking-wide text-white/50">Cita</p>
                    <template x-if="citaActual">
                        <div class="space-y-2">
                            <p class="flex items-start gap-2"><i class="bi bi-calendar3 text-white/50 mt-0.5"></i>
                                <span x-text="citaActual.fecha_larga + ' · ' + citaActual.hora + ' – ' + citaActual.hora_fin"></span></p>
                            <p class="flex items-start gap-2"><i class="bi bi-door-open text-white/50 mt-0.5"></i>
                                <span x-text="(citaActual.consultorio_nombre || 'Sin consultorio') + (citaActual.consultorio_piso ? ' · Piso ' + citaActual.consultorio_piso : '')"></span></p>
                            <p class="flex items-start gap-2"><i class="bi bi-clipboard2-pulse text-white/50 mt-0.5"></i>
                                <span x-text="citaActual.tipo_consulta"></span></p>
                            <p class="flex items-start gap-2"><i class="bi bi-chat-left-text text-white/50 mt-0.5"></i>
                                <span class="leading-relaxed" x-text="citaActual.motivo || 'Sin motivo registrado'"></span></p>
                        </div>
                    </template>
                </div>

                <!-- Historial reciente -->
                <div class="relative mt-5 mb-1" x-show="historial.length > 0" x-cloak>
                    <p class="text-[9px] font-black uppercase tracking-wide text-white/50 mb-2">Consultas anteriores</p>
                    <div class="space-y-2">
                        <template x-for="h in historial" :key="h.id">
                            <div class="bg-white/10 border border-white/10 rounded-xl px-3 py-2">
                                <p class="text-[10px] font-black text-emerald-300" x-text="h.fecha ? fechaCorta(h.fecha) + ' ' + h.fecha.slice(0, 4) : 'Sin fecha'"></p>
                                <p class="text-[11px] font-medium text-white/80 leading-snug mt-0.5" x-text="h.resumen || 'Sin notas registradas'"></p>
                            </div>
                        </template>
                    </div>
                </div>
                <p class="relative mt-5 text-[10px] font-medium text-white/40 leading-relaxed" x-show="historial.length === 0" x-cloak>
                    Sin consultas anteriores registradas para este paciente.
                </p>
            </div>

            <!-- ============ FORMULARIO ============ -->
            <div class="flex flex-col min-h-0">
                <div class="p-7 space-y-6 overflow-y-auto bg-slate-50/50 flex-1 min-h-0">

                    <!-- Aviso de estado -->
                    <div x-show="!soloLectura" class="rounded-2xl bg-sky-50 border border-sky-100 px-4 py-3 flex items-start gap-3">
                        <i class="bi bi-info-circle-fill text-sky-500 mt-0.5"></i>
                        <p class="text-[11px] font-semibold text-sky-800 leading-relaxed">
                            El consultorio figura como <strong>ocupado</strong> mientras dura la consulta.
                            Si cierras esta ventana sin finalizar, la consulta sigue en curso y puedes continuarla después.
                        </p>
                    </div>
                    <div x-show="soloLectura" x-cloak class="rounded-2xl bg-slate-100 border border-slate-200 px-4 py-3 flex items-center gap-3">
                        <i class="bi bi-lock-fill text-slate-400"></i>
                        <p class="text-[11px] font-semibold text-slate-600">Consulta finalizada: solo lectura.</p>
                    </div>

                    <!-- Exploración general -->
                    <div class="reveal-item" :class="openModalConsulta && 'reveal-on'" style="--d:1">
                        <label class="etiqueta"><i class="bi bi-activity text-teal-600"></i> Exploración física general</label>
                        <textarea x-model="form.exploracion_fisica" :disabled="soloLectura" rows="3" maxlength="5000"
                                  class="campo resize-none" placeholder="Signos vitales, peso, talla, temperatura, presión arterial..."></textarea>
                    </div>

                    <!-- Exploración por sistemas -->
                    <div class="reveal-item" :class="openModalConsulta && 'reveal-on'" style="--d:2">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                            Exploración por sistemas <span class="flex-1 h-px bg-slate-200"></span>
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="s in sistemas" :key="s.key">
                                <div>
                                    <label class="etiqueta"><i class="bi text-teal-600" :class="s.icono"></i> <span x-text="s.label"></span></label>
                                    <input type="text" x-model="form[s.key]" :disabled="soloLectura" maxlength="2000"
                                           class="campo" placeholder="Sin hallazgos">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Diagnóstico -->
                    <div class="reveal-item" :class="openModalConsulta && 'reveal-on'" style="--d:3">
                        <label class="etiqueta">
                            <i class="bi bi-journal-medical text-teal-600"></i> Notas generales / Diagnóstico
                            <span class="text-rose-500" x-show="!soloLectura">*</span>
                        </label>
                        <textarea x-model="form.notas_generales" :disabled="soloLectura" rows="4" maxlength="5000"
                                  class="campo resize-none" placeholder="Diagnóstico clínico, indicaciones y observaciones..."></textarea>
                        <p class="text-[10px] font-semibold text-slate-300 mt-1 text-right" x-show="!soloLectura"
                           x-text="(form.notas_generales || '').length + ' / 5000'"></p>
                    </div>
                </div>

                <!-- ACCIONES -->
                <div class="flex flex-wrap items-center gap-2.5 px-7 py-4 border-t border-slate-100 bg-white shrink-0">
                    <!-- Liberar consultorio sin finalizar -->
                    <button type="button" x-show="!soloLectura && citaActual && citaActual.estado === 'En curso'" x-cloak
                            @click="pedirLiberar()" :disabled="guardando"
                            class="px-4 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="bi bi-door-open"></i> Liberar consultorio
                    </button>

                    <div class="ml-auto flex flex-wrap items-center gap-2.5">
                        <button type="button" @click="openModalConsulta = false" :disabled="guardando"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer disabled:opacity-50"
                                x-text="soloLectura ? 'Cerrar' : 'Cerrar sin finalizar'"></button>

                        <button type="button" x-show="!soloLectura" @click="guardarAtencion(false)" :disabled="guardando"
                                class="px-5 py-2.5 bg-white border-2 border-teal-200 hover:border-teal-400 text-teal-700 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                            <i class="bi bi-floppy"></i> Guardar borrador
                        </button>

                        <a x-show="soloLectura && recetaUrl" :href="recetaUrl"
                           class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all flex items-center gap-1.5">
                            <i class="bi bi-prescription2"></i> Ver receta
                        </a>

                        <button type="submit" x-show="!soloLectura" :disabled="guardando"
                                class="px-6 py-2.5 bg-gradient-to-r from-teal-700 to-emerald-600 hover:from-teal-800 hover:to-emerald-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                            <span x-show="guardando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                            <i x-show="!guardando" class="bi bi-prescription2"></i>
                            <span x-text="guardando ? 'Guardando...' : 'Finalizar y emitir receta'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>