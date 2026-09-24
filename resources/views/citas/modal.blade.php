<!-- MODAL DE CREAR / EDITAR CITA — VERSIÓN OPTIMIZADA CON SCROLL CUIDADO -->
<template x-teleport="body">
    <div x-show="openCitaModal"
         class="fixed inset-0 z-[99999] bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <form action="{{ route('citas.store') }}" method="POST"
              class="relative bg-white rounded-[1.75rem] shadow-2xl shadow-slate-900/30 max-w-3xl w-full border border-slate-200/60 grid grid-cols-1 md:grid-cols-[230px_1fr] overflow-hidden my-auto max-h-[85vh] h-full min-h-0"
              @click.outside="openCitaModal = false">
            @csrf

            <!-- ============ RIEL LATERAL (FIJO / SIN DESPLAZAMIENTO) ============ -->
            <div class="relative text-white p-6 flex flex-col shrink-0 overflow-hidden h-full"
                 :class="modoEdicion ? 'bg-gradient-to-b from-slate-900 to-sky-900' : 'bg-gradient-to-b from-teal-900 to-emerald-900'">

                <div class="absolute -left-10 -bottom-12 w-40 h-40 rounded-full bg-white/5"></div>
                <div class="absolute right-[-2.5rem] top-16 w-24 h-24 rounded-full bg-white/5"></div>

                <!-- Encabezado -->
                <div class="relative flex items-start justify-between mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center">
                        <i class="bi text-base" :class="modoEdicion ? 'bi-pencil-square text-sky-300' : 'bi-calendar-heart text-emerald-300'"></i>
                    </div>
                    <button type="button" @click="openCitaModal = false"
                            class="text-white/60 hover:text-white hover:bg-white/10 w-7 h-7 rounded-lg flex items-center justify-center transition-all cursor-pointer">
                        <i class="bi bi-x-lg text-[11px]"></i>
                    </button>
                </div>

                <h3 class="relative text-base font-black leading-tight" x-text="modoEdicion ? 'Editar cita' : 'Nueva cita'"></h3>
                <p class="relative text-[11px] text-white/55 font-medium mt-1 mb-6">Sigue los pasos para completarla</p>

                <!-- Timeline de pasos -->
                <div class="relative space-y-0 mb-6">
                    <!-- Paso 1 -->
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 transition-all"
                                 :class="(form.paciente_id && form.personal_id) ? 'bg-white text-teal-800' : 'bg-white/15 text-white/70'">
                                <i class="bi bi-check-lg" x-show="form.paciente_id && form.personal_id"></i>
                                <span x-show="!(form.paciente_id && form.personal_id)">1</span>
                            </div>
                            <div class="w-px flex-1 bg-white/15 my-1"></div>
                        </div>
                        <div class="pb-4">
                            <p class="text-xs font-bold">Paciente y médico</p>
                            <p class="text-[10px] text-white/50 font-medium mt-0.5">Quién asiste y quién atiende</p>
                        </div>
                    </div>
                    <!-- Paso 2 -->
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 transition-all"
                                 :class="(form.fecha && form.hora) ? 'bg-white text-teal-800' : 'bg-white/15 text-white/70'">
                                <i class="bi bi-check-lg" x-show="form.fecha && form.hora"></i>
                                <span x-show="!(form.fecha && form.hora)">2</span>
                            </div>
                            <div class="w-px flex-1 bg-white/15 my-1"></div>
                        </div>
                        <div class="pb-4">
                            <p class="text-xs font-bold">Fecha y hora</p>
                            <p class="text-[10px] text-white/50 font-medium mt-0.5">Cuándo se realiza</p>
                        </div>
                    </div>
                    <!-- Paso 3 -->
                    <div class="flex gap-3">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 bg-white/15 text-white/70">3</div>
                        <div>
                            <p class="text-xs font-bold">Detalles</p>
                            <p class="text-[10px] text-white/50 font-medium mt-0.5">Tipo, duración y estado</p>
                        </div>
                    </div>
                </div>

                <!-- Resumen en vivo -->
                <div class="relative mt-auto bg-white/10 border border-white/10 rounded-2xl p-3.5 space-y-2 backdrop-blur-sm">
                    <p class="text-[9px] font-black text-white/50 tracking-wide">Resumen</p>
                    <div class="flex items-center gap-2 text-[11px] font-bold">
                        <i class="bi bi-person-fill text-white/50 text-[10px]"></i>
                        <span class="truncate" x-text="form.paciente_id ? ({{ json_encode($pacientes ?? []) }}.find(p => p.id == form.paciente_id)?.nombre_completo || {{ json_encode($pacientes ?? []) }}.find(p => p.id == form.paciente_id)?.nombre) : 'Sin paciente'"></span>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] font-bold">
                        <i class="bi bi-calendar3 text-white/50 text-[10px]"></i>
                        <span x-text="form.fecha ? new Date(form.fecha + 'T00:00:00').toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }) : 'Sin fecha'"></span>
                        <span class="text-white/30">·</span>
                        <span x-text="form.hora || '--:--'"></span>
                    </div>
                </div>
            </div>

            <!-- ============ CONTENIDO DERECHO (CON SCROLL ACTIVO Y FLUIDO) ============ -->
            <div class="overflow-y-auto p-7 flex flex-col justify-between h-full min-h-0 custom-scrollbar">
                <div class="space-y-8">

                    <!-- PACIENTE Y MÉDICO -->
                    <section class="space-y-3">
                        <p class="text-[13px] font-bold text-slate-700 flex items-center gap-1.5">
                            <span class="w-1.5 h-4 rounded-full" :class="modoEdicion ? 'bg-sky-600' : 'bg-teal-600'"></span>
                            Paciente y médico
                        </p>

                        <!-- PACIENTE -->
                        <div x-data="{ openPac: false, searchPac: '' }">
                            <input type="hidden" name="paciente_id" x-model="form.paciente_id" required>
                            <div @click="openPac = !openPac; $nextTick(() => $refs.searchPacInput && $refs.searchPacInput.focus())"
                                 class="flex items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 cursor-pointer transition-all border"
                                 :class="openPac ? (modoEdicion ? 'border-sky-400 bg-sky-50/50' : 'border-teal-400 bg-teal-50/50') : 'border-transparent bg-slate-50 hover:bg-slate-100'">
                                <span class="flex items-center gap-2.5 min-w-0">
                                    <i class="bi bi-person-circle text-slate-400 text-base shrink-0"></i>
                                    <span class="truncate text-xs font-bold" :class="form.paciente_id ? 'text-slate-800' : 'text-slate-400'"
                                          x-text="form.paciente_id ? ({{ json_encode($pacientes ?? []) }}.find(p => p.id == form.paciente_id)?.nombre_completo || {{ json_encode($pacientes ?? []) }}.find(p => p.id == form.paciente_id)?.nombre) : 'Elegir paciente'"></span>
                                </span>
                                <i class="bi bi-chevron-down text-slate-400 text-[10px] transition-transform shrink-0" :class="openPac && 'rotate-180'"></i>
                            </div>
                            <div x-show="openPac" x-collapse class="mt-1.5 pl-1">
                                <div class="relative mb-1.5">
                                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[11px]"></i>
                                    <input type="text" x-ref="searchPacInput" x-model="searchPac" placeholder="Buscar..."
                                           class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs outline-none font-medium focus:border-slate-300">
                                </div>
                                <div class="max-h-32 overflow-y-auto space-y-0.5">
                                    @forelse($pacientes ?? [] as $p)
                                        <div @click="form.paciente_id = '{{ $p->id }}'; openPac = false; searchPac = '';"
                                             x-show="'{{ strtolower($p->nombre_completo ?? $p->nombre ?? '') }}'.includes(searchPac.toLowerCase())"
                                             class="px-3 py-1.5 text-xs font-bold text-slate-600 rounded-lg cursor-pointer hover:bg-slate-50 flex items-center justify-between"
                                             :class="form.paciente_id == '{{ $p->id }}' && (modoEdicion ? 'text-sky-800' : 'text-teal-800')">
                                            <span>{{ $p->nombre_completo ?? $p->nombre ?? 'Paciente #' . $p->id }}</span>
                                            <i class="bi bi-check-lg text-[10px]" x-show="form.paciente_id == '{{ $p->id }}'"></i>
                                        </div>
                                    @empty
                                        <p class="text-[11px] text-slate-400 font-semibold text-center py-2">Sin pacientes</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- MÉDICO -->
                        <div x-data="{ openMed: false, searchMed: '' }">
                            <input type="hidden" name="personal_id" x-model="form.personal_id" required>
                            <div @click="openMed = !openMed; $nextTick(() => $refs.searchMedInput && $refs.searchMedInput.focus())"
                                 class="flex items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 cursor-pointer transition-all border"
                                 :class="openMed ? (modoEdicion ? 'border-sky-400 bg-sky-50/50' : 'border-teal-400 bg-teal-50/50') : 'border-transparent bg-slate-50 hover:bg-slate-100'">
                                <span class="flex items-center gap-2.5 min-w-0">
                                    <i class="bi bi-heart-pulse text-slate-400 text-base shrink-0"></i>
                                    <span class="truncate text-xs font-bold" :class="form.personal_id ? 'text-slate-800' : 'text-slate-400'"
                                          x-text="form.personal_id ? 'Dr. ' + ({{ json_encode($medicos ?? []) }}.find(m => m.id == form.personal_id)?.nombre_completo || {{ json_encode($medicos ?? []) }}.find(m => m.id == form.personal_id)?.nombre) : 'Elegir médico'"></span>
                                </span>
                                <i class="bi bi-chevron-down text-slate-400 text-[10px] transition-transform shrink-0" :class="openMed && 'rotate-180'"></i>
                            </div>
                            <div x-show="openMed" x-collapse class="mt-1.5 pl-1">
                                <div class="relative mb-1.5">
                                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[11px]"></i>
                                    <input type="text" x-ref="searchMedInput" x-model="searchMed" placeholder="Buscar..."
                                           class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs outline-none font-medium focus:border-slate-300">
                                </div>
                                <div class="max-h-32 overflow-y-auto space-y-0.5">
                                    @forelse($medicos ?? [] as $m)
                                        <div @click="form.personal_id = '{{ $m->id }}'; openMed = false; searchMed = '';"
                                             x-show="'{{ strtolower($m->nombre_completo ?? $m->nombre ?? '') }}'.includes(searchMed.toLowerCase())"
                                             class="px-3 py-1.5 text-xs font-bold text-slate-600 rounded-lg cursor-pointer hover:bg-slate-50 flex items-center justify-between"
                                             :class="form.personal_id == '{{ $m->id }}' && (modoEdicion ? 'text-sky-800' : 'text-teal-800')">
                                            <span>Dr. {{ $m->nombre_completo ?? $m->nombre ?? 'Personal #' . $m->id }}</span>
                                            <i class="bi bi-check-lg text-[10px]" x-show="form.personal_id == '{{ $m->id }}'"></i>
                                        </div>
                                    @empty
                                        <p class="text-[11px] text-slate-400 font-semibold text-center py-2">Sin médicos</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- CONSULTORIO -->
                        <div x-data="{ openCons: false, searchCons: '' }">
                            <input type="hidden" name="consultorio_id" x-model="form.consultorio_id" required>
                            <div @click="openCons = !openCons; $nextTick(() => $refs.searchConsInput && $refs.searchConsInput.focus())"
                                 class="flex items-center justify-between gap-2 rounded-xl px-3.5 py-2.5 cursor-pointer transition-all border"
                                 :class="openCons ? (modoEdicion ? 'border-sky-400 bg-sky-50/50' : 'border-teal-400 bg-teal-50/50') : 'border-transparent bg-slate-50 hover:bg-slate-100'">
                                <span class="flex items-center gap-2.5 min-w-0">
                                    <i class="bi bi-door-open text-slate-400 text-base shrink-0"></i>
                                    <span class="truncate text-xs font-bold" :class="form.consultorio_id ? 'text-slate-800' : 'text-slate-400'"
                                          x-text="form.consultorio_id ? ({{ json_encode($consultorios ?? []) }}.find(c => c.id == form.consultorio_id)?.nombre) : 'Elegir consultorio'"></span>
                                </span>
                                <i class="bi bi-chevron-down text-slate-400 text-[10px] transition-transform shrink-0" :class="openCons && 'rotate-180'"></i>
                            </div>
                            <div x-show="openCons" x-collapse class="mt-1.5 pl-1">
                                <div class="relative mb-1.5">
                                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[11px]"></i>
                                    <input type="text" x-ref="searchConsInput" x-model="searchCons" placeholder="Buscar..."
                                           class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs outline-none font-medium focus:border-slate-300">
                                </div>
                                <div class="max-h-32 overflow-y-auto space-y-0.5">
                                    @forelse($consultorios ?? [] as $cons)
                                        <div @click="form.consultorio_id = '{{ $cons->id }}'; openCons = false; searchCons = '';"
                                             x-show="'{{ strtolower($cons->nombre ?? '') }}'.includes(searchCons.toLowerCase())"
                                             class="px-3 py-1.5 text-xs font-bold text-slate-600 rounded-lg cursor-pointer hover:bg-slate-50 flex items-center justify-between"
                                             :class="form.consultorio_id == '{{ $cons->id }}' && (modoEdicion ? 'text-sky-800' : 'text-teal-800')">
                                            <span>{{ $cons->nombre }}</span>
                                            <span class="text-[9px] text-slate-400 font-bold">Piso {{ $cons->piso ?? 1 }}</span>
                                        </div>
                                    @empty
                                        <p class="text-[11px] text-slate-400 font-semibold text-center py-2">Sin consultorios</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- MOTIVO -->
                    <section class="space-y-2">
                        <p class="text-[13px] font-bold text-slate-700 flex items-center gap-1.5">
                            <span class="w-1.5 h-4 rounded-full" :class="modoEdicion ? 'bg-sky-600' : 'bg-teal-600'"></span>
                            Motivo de la consulta
                        </p>
                        <input type="text" name="motivo" x-model="form.motivo" required placeholder="¿Qué se va a revisar?"
                               class="w-full border-0 border-b-2 border-slate-200 focus:ring-0 outline-none px-0 py-1.5 text-sm font-bold text-slate-800 bg-transparent placeholder:text-slate-300 placeholder:font-semibold transition-colors"
                               :class="modoEdicion ? 'focus:border-sky-600' : 'focus:border-teal-600'">
                    </section>

                    <!-- FECHA Y HORA -->
                    <section class="space-y-3"
                             x-data="{
                                showCal: false,
                                viewDate: form.fecha ? new Date(form.fecha + 'T00:00:00') : new Date(),
                                meses: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
                                diasSemana: ['L','M','X','J','V','S','D'],
                                proximosDias() {
                                    const dias = [];
                                    const hoy = new Date(); hoy.setHours(0,0,0,0);
                                    for (let i = 0; i < 30; i++) { const d = new Date(hoy); d.setDate(hoy.getDate() + i); dias.push(d); }
                                    return dias;
                                },
                                iso(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); },
                                esSel(d) { return form.fecha === this.iso(d); },
                                esHoy(d) { const t = new Date(); return t.toDateString() === d.toDateString(); },
                                get diasMes() {
                                    const y = this.viewDate.getFullYear(), m = this.viewDate.getMonth();
                                    const first = new Date(y, m, 1), last = new Date(y, m + 1, 0);
                                    let offset = first.getDay() === 0 ? 6 : first.getDay() - 1;
                                    let out = [];
                                    for (let i = 0; i < offset; i++) out.push(null);
                                    for (let d = 1; d <= last.getDate(); d++) out.push(d);
                                    return out;
                                },
                                elegirDiaMes(d) {
                                    if (!d) return;
                                    form.fecha = this.viewDate.getFullYear() + '-' + String(this.viewDate.getMonth()+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                                    this.showCal = false;
                                },
                                horas: Array.from({length: 25}, (_, i) => { const h = 7 + Math.floor(i/2); const m = i % 2 === 0 ? '00' : '30'; return String(h).padStart(2,'0') + ':' + m; }),
                                showExact: false,
                                hh: form.hora ? form.hora.split(':')[0] : '09',
                                mm: form.hora ? form.hora.split(':')[1] : '00'
                              }"
                             x-init="$watch('hh', v => form.hora = String(v).padStart(2,'0') + ':' + String(mm).padStart(2,'0'));
                                     $watch('mm', v => form.hora = String(hh).padStart(2,'0') + ':' + String(v).padStart(2,'0'));">
                        <input type="hidden" name="fecha" x-model="form.fecha" required>
                        <input type="hidden" name="hora" x-model="form.hora" required>

                        <div class="flex items-center justify-between">
                            <p class="text-[13px] font-bold text-slate-700 flex items-center gap-1.5">
                                <span class="w-1.5 h-4 rounded-full" :class="modoEdicion ? 'bg-sky-600' : 'bg-teal-600'"></span>
                                Fecha y hora
                            </p>
                            <button type="button" @click="showCal = !showCal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                                <i class="bi bi-calendar3 text-sm"></i>
                            </button>
                        </div>

                        <!-- Franja de días -->
                        <div class="flex gap-1.5 overflow-x-auto pb-1" x-show="!showCal" style="scroll-snap-type: x proximity;">
                            <template x-for="(d, i) in proximosDias()" :key="i">
                                <button type="button" @click="form.fecha = iso(d)"
                                        class="w-11 shrink-0 rounded-2xl py-2 flex flex-col items-center gap-0.5 transition-all"
                                        style="scroll-snap-align: start;"
                                        :class="esSel(d) ? (modoEdicion ? 'bg-sky-700 text-white' : 'bg-teal-700 text-white') : (esHoy(d) ? 'bg-slate-100 text-slate-700 ring-1 ring-slate-300' : 'bg-slate-50 text-slate-500 hover:bg-slate-100')">
                                    <span class="text-[8px] font-bold uppercase opacity-70" x-text="d.toLocaleDateString('es-MX', { weekday: 'short' })"></span>
                                    <span class="text-xs font-black" x-text="d.getDate()"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Mini calendario (mes completo) -->
                        <div x-show="showCal" x-collapse class="border border-slate-100 rounded-2xl p-3">
                            <div class="flex items-center justify-between mb-2">
                                <button type="button" @click="viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth()-1, 1)" class="w-6 h-6 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 cursor-pointer"><i class="bi bi-chevron-left text-[10px]"></i></button>
                                <span class="text-xs font-black text-slate-700" x-text="meses[viewDate.getMonth()] + ' ' + viewDate.getFullYear()"></span>
                                <button type="button" @click="viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth()+1, 1)" class="w-6 h-6 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500 cursor-pointer"><i class="bi bi-chevron-right text-[10px]"></i></button>
                            </div>
                            <div class="grid grid-cols-7 mb-1">
                                <template x-for="dia in diasSemana"><span class="text-center text-[8px] font-black text-slate-300" x-text="dia"></span></template>
                            </div>
                            <div class="grid grid-cols-7 gap-0.5">
                                <template x-for="(d, idx) in diasMes" :key="idx">
                                    <button type="button" @click="elegirDiaMes(d)" :disabled="!d"
                                            class="aspect-square rounded-lg text-[10px] font-bold flex items-center justify-center"
                                            :class="!d ? 'invisible' : (form.fecha === (viewDate.getFullYear() + '-' + String(viewDate.getMonth()+1).padStart(2,'0') + '-' + String(d).padStart(2,'0')) ? (modoEdicion ? 'bg-sky-700 text-white' : 'bg-teal-700 text-white') : 'text-slate-600 hover:bg-slate-100 cursor-pointer')"
                                            x-text="d"></button>
                                </template>
                            </div>
                        </div>

                        <!-- Chips de hora -->
                        <div class="flex flex-wrap gap-1.5 pt-1" x-show="!showExact">
                            <template x-for="h in horas" :key="h">
                                <button type="button" @click="form.hora = h; hh = h.split(':')[0]; mm = h.split(':')[1];"
                                        class="px-2.5 py-1.5 rounded-full text-[11px] font-bold transition-all"
                                        :class="form.hora === h ? (modoEdicion ? 'bg-sky-700 text-white' : 'bg-teal-700 text-white') : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                                        x-text="h"></button>
                            </template>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="showExact = !showExact" class="text-[10px] font-black uppercase tracking-wide cursor-pointer" :class="modoEdicion ? 'text-sky-700' : 'text-teal-700'">
                                <span x-text="showExact ? 'Usar horarios sugeridos' : 'Elegir hora exacta'"></span>
                            </button>
                        </div>

                        <!-- Selector de hora exacta -->
                        <div x-show="showExact" x-collapse class="flex items-center gap-2 pt-1">
                            <input type="number" min="0" max="23" x-model.number="hh"
                                   class="w-12 text-center border-0 border-b-2 border-slate-200 focus:ring-0 outline-none text-lg font-black text-slate-800 bg-transparent transition-colors"
                                   :class="modoEdicion ? 'focus:border-sky-600' : 'focus:border-teal-600'">
                            <span class="text-lg font-black text-slate-300">:</span>
                            <input type="number" min="0" max="59" x-model.number="mm"
                                   class="w-12 text-center border-0 border-b-2 border-slate-200 focus:ring-0 outline-none text-lg font-black text-slate-800 bg-transparent transition-colors"
                                   :class="modoEdicion ? 'focus:border-sky-600' : 'focus:border-teal-600'">
                            <span class="text-[10px] text-slate-400 font-semibold ml-1">24 hrs</span>
                        </div>
                    </section>

                    <!-- DETALLES -->
                    <section class="space-y-4"
                             x-data="{
                                tipos: [
                                    { v: 'Primera Vez', icon: 'bi-person-plus-fill' },
                                    { v: 'Seguimiento', icon: 'bi-arrow-repeat' },
                                    { v: 'Urgencia', icon: 'bi-exclamation-triangle-fill' }
                                ],
                                estados: [
                                    { v: 'Pendiente', dot: 'bg-amber-400' },
                                    { v: 'Confirmada', dot: 'bg-emerald-500' },
                                    { v: 'En curso', dot: 'bg-sky-500' },
                                    { v: 'Finalizada', dot: 'bg-slate-400' },
                                    { v: 'Cancelada', dot: 'bg-red-500' }
                                ]
                              }">
                        <input type="hidden" name="duracion_min" x-model="form.duracion_min">
                        <input type="hidden" name="tipo_consulta" x-model="form.tipo_consulta">
                        <input type="hidden" name="estado" x-model="form.estado">

                        <p class="text-[13px] font-bold text-slate-700 flex items-center gap-1.5">
                            <span class="w-1.5 h-4 rounded-full" :class="modoEdicion ? 'bg-sky-600' : 'bg-teal-600'"></span>
                            Detalles
                        </p>

                        <!-- Duración -->
                        <div class="space-y-1.5">
                            <p class="text-[10px] font-bold text-slate-400">Duración</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="d in [15, 30, 45, 60]" :key="d">
                                    <button type="button" @click="form.duracion_min = d"
                                            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all"
                                            :class="form.duracion_min == d ? (modoEdicion ? 'bg-sky-700 text-white' : 'bg-teal-700 text-white') : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                                            x-text="d + ' min'"></button>
                                </template>
                            </div>
                        </div>

                        <!-- Tipo -->
                        <div class="space-y-1.5">
                            <p class="text-[10px] font-bold text-slate-400">Tipo de consulta</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="t in tipos" :key="t.v">
                                    <button type="button" @click="form.tipo_consulta = t.v"
                                            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all flex items-center gap-1.5"
                                            :class="form.tipo_consulta == t.v ? (modoEdicion ? 'bg-sky-700 text-white' : 'bg-teal-700 text-white') : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                                        <i class="bi text-[10px]" :class="t.icon"></i>
                                        <span x-text="t.v"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="space-y-1.5">
                            <p class="text-[10px] font-bold text-slate-400">Estado</p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="e in estados" :key="e.v">
                                    <button type="button" @click="form.estado = e.v"
                                            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all flex items-center gap-1.5 border"
                                            :class="form.estado == e.v ? 'border-slate-300 bg-slate-100 text-slate-800' : 'border-transparent bg-slate-50 text-slate-500 hover:bg-slate-100'">
                                        <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="e.dot"></span>
                                        <span x-text="e.v"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </section>

                </div>

                <!-- ACCIONES (STICKY INFERIOR) -->
                <div class="sticky -bottom-7 bg-white/95 backdrop-blur -mx-7 px-7 pt-4 pb-4 border-t border-slate-100 flex justify-end gap-2.5 mt-8 shrink-0">
                    <button type="button" @click="openCitaModal = false" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 text-white font-black rounded-2xl text-xs transition-all shadow-md active:scale-95 flex items-center gap-1.5 cursor-pointer" :class="modoEdicion ? 'bg-sky-600 hover:bg-sky-700 shadow-sky-600/20' : 'bg-teal-700 hover:bg-teal-800 shadow-teal-700/20'">
                        <i class="bi bi-check2-all text-xs"></i>
                        <span x-text="modoEdicion ? 'Actualizar cita' : 'Guardar cita'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>