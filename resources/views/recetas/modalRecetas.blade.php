<!-- MODAL CREAR RECETA MÉDICA - WIZARD ANIMADO PREMIUM -->
<template x-teleport="body">
    <div x-show="openCreateModal" 
         class="fixed inset-0 z-[9999] bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-3 sm:p-6" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-4xl w-full max-h-[90vh] border border-slate-100 flex flex-col overflow-hidden my-auto" 
             @click.outside="openCreateModal = false"
             x-data="{
                step: 1,
                vitals: {
                    pa_sistolica: '{{ old('pa_sistolica') }}',
                    pa_diastolica: '{{ old('pa_diastolica') }}',
                    frecuencia_cardiaca: '{{ old('frecuencia_cardiaca') }}',
                    frecuencia_respiratoria: '{{ old('frecuencia_respiratoria') }}',
                    temperatura: '{{ old('temperatura') }}',
                    saturacion_oxigeno: '{{ old('saturacion_oxigeno') }}',
                    peso_kg: '{{ old('peso_kg') }}',
                    talla_cm: '{{ old('talla_cm') }}'
                },
                errors: {},

                validateField(field, min, max, name) {
                    let val = parseFloat(this.vitals[field]);
                    if (this.vitals[field] !== '' && !isNaN(val)) {
                        if (val < min || val > max) {
                            this.errors[field] = `${name} fuera de rango clínico (${min} - ${max})`;
                        } else {
                            delete this.errors[field];
                        }
                    } else {
                        delete this.errors[field];
                    }
                },

                get hasErrors() {
                    return Object.keys(this.errors).length > 0;
                }
             }">
            
            <!-- ENCABEZADO CON GRADIENTE -->
            <div class="px-8 py-6 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white flex flex-col gap-4 shadow-md shrink-0 relative overflow-hidden">
                <div class="flex justify-between items-center z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-white flex items-center justify-center shadow-inner">
                            <i class="bi bi-file-earmark-medical-fill text-2xl text-emerald-300"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black tracking-tight text-white flex items-center gap-2">
                                Nueva Receta Médica
                                <span class="text-[10px] bg-emerald-500/30 text-emerald-200 border border-emerald-400/30 px-2.5 py-0.5 rounded-full uppercase font-bold tracking-wider">Flujo Guiado</span>
                            </h3>
                            <p class="text-xs text-teal-100/90 font-medium">Completa la prescripción médica en 3 sencillos pasos</p>
                        </div>
                    </div>
                    <button @click="openCreateModal = false" type="button" class="text-teal-200 hover:text-white hover:bg-white/10 transition-all text-lg font-bold w-10 h-10 rounded-2xl flex items-center justify-center">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- BARRA PROGRESIVA DE PESTAÑAS -->
                <div class="grid grid-cols-3 gap-2 pt-2 z-10">
                    <button type="button" @click="step = 1" class="flex items-center gap-2 p-2 rounded-xl text-left transition-all" :class="step === 1 ? 'bg-white/20 text-white font-extrabold border border-white/30' : 'bg-black/10 text-teal-200/70 hover:bg-white/10'">
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold" :class="step === 1 ? 'bg-emerald-400 text-slate-900' : 'bg-white/10'">1</span>
                        <span class="text-xs hidden sm:inline">Emisión</span>
                    </button>

                    <button type="button" @click="step = 2" class="flex items-center gap-2 p-2 rounded-xl text-left transition-all" :class="step === 2 ? 'bg-white/20 text-white font-extrabold border border-white/30' : 'bg-black/10 text-teal-200/70 hover:bg-white/10'">
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold" :class="step === 2 ? 'bg-emerald-400 text-slate-900' : 'bg-white/10'">2</span>
                        <span class="text-xs hidden sm:inline">Signos Vitales</span>
                    </button>

                    <button type="button" @click="step = 3" class="flex items-center gap-2 p-2 rounded-xl text-left transition-all" :class="step === 3 ? 'bg-white/20 text-white font-extrabold border border-white/30' : 'bg-black/10 text-teal-200/70 hover:bg-white/10'">
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold" :class="step === 3 ? 'bg-emerald-400 text-slate-900' : 'bg-white/10'">3</span>
                        <span class="text-xs hidden sm:inline">Fármacos</span>
                    </button>
                </div>
            </div>

            <!-- CUERPO DEL FORMULARIO -->
            <form action="{{ route('recetas.store') }}" method="POST" class="flex flex-col flex-1 min-h-0 bg-slate-50/50">
                @csrf

                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">

                    <!-- PASO 1: EMISIÓN DE DATOS GENERALES -->
                    <div x-show="step === 1" 
                         x-transition:enter="transition ease-out duration-300 transform"
                         x-transition:enter-start="opacity-0 translate-x-8"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         class="space-y-6">
                        
                        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-6">
                            <h4 class="text-xs font-black uppercase tracking-wider text-teal-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                                <i class="bi bi-person-badge-fill text-teal-600 text-base"></i> Datos del Prescriptor y Paciente
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- MÉDICO PRESCRIPTOR -->
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
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2 flex items-center gap-1.5">
                                        <i class="bi bi-person-workspace text-teal-600"></i> Médico Prescriptor *
                                    </label>
                                    
                                    @if(auth()->user()->isSuperAdmin() || in_array(strtolower(auth()->user()->rol->nombre ?? ''), ['administrador', 'admin', 'super admin']))
                                        <input type="hidden" name="personal_id" :value="selectedDocId" required>
                                        
                                        <button type="button" @click="openDoc = !openDoc" class="w-full bg-white border-2 border-slate-200 hover:border-teal-500 rounded-2xl p-3.5 text-left text-xs font-bold text-slate-800 flex justify-between items-center focus:ring-4 focus:ring-teal-500/10 transition-all shadow-xs">
                                            <span class="truncate" x-text="selectedDocName || 'Selecciona un médico'"></span>
                                            <i class="bi bi-chevron-down text-slate-400 text-xs"></i>
                                        </button>

                                        <div x-show="openDoc" @click.outside="openDoc = false" class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 p-2 space-y-2" x-cloak>
                                            <div class="relative">
                                                <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                                                <input type="text" x-model="searchDoc" placeholder="Buscar médico..." class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
                                            </div>
                                            <div class="max-h-40 overflow-y-auto space-y-1">
                                                <template x-for="doc in filteredDoctores" :key="doc.id">
                                                    <button type="button" @click="selectedDocId = doc.id; selectedDocName = doc.nombre; openDoc = false" class="w-full text-left px-3 py-2 text-xs hover:bg-teal-50 rounded-xl font-bold text-slate-700 transition-colors flex items-center justify-between">
                                                        <span x-text="doc.nombre"></span>
                                                        <i class="bi bi-check-lg text-teal-600" x-show="selectedDocId == doc.id"></i>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    @else
                                        <div class="w-full bg-slate-100 border-2 border-slate-200 rounded-2xl p-3.5 text-xs font-bold text-teal-800 flex items-center gap-2 truncate">
                                            <i class="bi bi-person-badge-fill text-teal-600"></i>
                                            <span class="truncate">
                                                Dr. {{ auth()->user()->personal->nombre_completo ?? auth()->user()->nombre_completo }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- PACIENTE -->
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
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2 flex items-center gap-1.5">
                                        <i class="bi bi-person-fill text-teal-600"></i> Paciente Seleccionado *
                                    </label>
                                    
                                    <input type="hidden" name="paciente_id" :value="selectedPacId" required>
                                    
                                    <button type="button" @click="openPac = !openPac" class="w-full bg-white border-2 border-slate-200 hover:border-teal-500 rounded-2xl p-3.5 text-left text-xs font-bold text-slate-800 flex justify-between items-center focus:ring-4 focus:ring-teal-500/10 transition-all shadow-xs">
                                        <span class="truncate" x-text="selectedPacName || 'Selecciona un paciente'"></span>
                                        <i class="bi bi-chevron-down text-slate-400 text-xs"></i>
                                    </button>

                                    <div x-show="openPac" @click.outside="openPac = false" class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 p-2 space-y-2" x-cloak>
                                        <div class="relative">
                                            <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                                            <input type="text" x-model="searchPac" placeholder="Buscar paciente por nombre..." class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        </div>
                                        <div class="max-h-40 overflow-y-auto space-y-1">
                                            <template x-for="pac in filteredPacientes" :key="pac.id">
                                                <button type="button" @click="selectedPacId = pac.id; selectedPacName = pac.nombre; openPac = false" class="w-full text-left px-3 py-2 text-xs hover:bg-teal-50 rounded-xl font-bold text-slate-700 transition-colors flex items-center justify-between">
                                                    <span x-text="pac.nombre"></span>
                                                    <i class="bi bi-check-lg text-teal-600" x-show="selectedPacId == pac.id"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- FECHA DE EMISIÓN CON HOMOLOGACIÓN EXACTA DE ESTILOS -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-2 flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <i class="bi bi-calendar-check-fill text-teal-600"></i> Fecha de Emisión *
                                    </span>
                                    <span class="text-[10px] text-teal-700 font-extrabold bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">Prescripción Oficial</span>
                                </label>
                                
                                <div class="relative flex items-center group">
                                    <!-- Ícono izquierdo -->
                                    <div class="absolute left-3.5 z-10 text-teal-600 flex items-center justify-center pointer-events-none">
                                        <i class="bi bi-calendar3 text-sm font-bold"></i>
                                    </div>
                                    
                                    <!-- Input idéntico en padding, bordes y fuentes a los otros selects -->
                                    <input type="text" 
                                           id="fecha_emision_input" 
                                           name="fecha_emision" 
                                           value="{{ date('Y-m-d') }}" 
                                           required 
                                           readonly 
                                           class="w-full bg-white border-2 border-slate-200 hover:border-teal-500 rounded-2xl p-3.5 pl-11 pr-10 text-xs font-bold text-slate-800 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all shadow-xs cursor-pointer">

                                    <!-- Flecha derecha -->
                                    <i class="bi bi-chevron-down absolute right-4 text-slate-400 text-xs pointer-events-none transition-transform group-hover:translate-y-0.5"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 2: SIGNOS VITALES -->
                    <div x-show="step === 2" 
                         x-transition:enter="transition ease-out duration-300 transform"
                         x-transition:enter-start="opacity-0 translate-x-8"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         class="space-y-6">
                        
                        <div x-show="hasErrors" x-cloak class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs shadow-xs space-y-1">
                            <p class="font-black flex items-center gap-2 text-rose-700">
                                <i class="bi bi-exclamation-triangle-fill text-base"></i> Valores Fuera de Rango Clínico:
                            </p>
                            <ul class="list-disc pl-6 space-y-0.5 text-xs text-rose-600 font-bold">
                                <template x-for="(msg, key) in errors" :key="key">
                                    <li x-text="msg"></li>
                                </template>
                            </ul>
                        </div>

                        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-6">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                <h4 class="text-xs font-black uppercase tracking-wider text-teal-900 flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm shadow-xs">
                                        <i class="bi bi-heart-pulse-fill"></i>
                                    </span>
                                    Captura de Signos Vitales
                                </h4>
                                <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">Lecturas Opcionales</span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.pa_sistolica ? 'text-rose-600' : 'text-slate-600'">
                                        P.A. Sistólica <span class="text-slate-400 font-normal">(40-300)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="pa_sistolica" x-model="vitals.pa_sistolica" @input="validateField('pa_sistolica', 40, 300, 'P.A. Sistólica')" placeholder="120" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-12 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.pa_sistolica ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">mmHg</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.pa_diastolica ? 'text-rose-600' : 'text-slate-600'">
                                        P.A. Diastólica <span class="text-slate-400 font-normal">(20-200)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="pa_diastolica" x-model="vitals.pa_diastolica" @input="validateField('pa_diastolica', 20, 200, 'P.A. Diastólica')" placeholder="80" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-12 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.pa_diastolica ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">mmHg</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.frecuencia_cardiaca ? 'text-rose-600' : 'text-slate-600'">
                                        Frec. Cardíaca <span class="text-slate-400 font-normal">(30-250)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="frecuencia_cardiaca" x-model="vitals.frecuencia_cardiaca" @input="validateField('frecuencia_cardiaca', 30, 250, 'Frecuencia Cardíaca')" placeholder="75" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-12 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.frecuencia_cardiaca ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">BPM</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.frecuencia_respiratoria ? 'text-rose-600' : 'text-slate-600'">
                                        Frec. Resp. <span class="text-slate-400 font-normal">(5-80)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="frecuencia_respiratoria" x-model="vitals.frecuencia_respiratoria" @input="validateField('frecuencia_respiratoria', 5, 80, 'Frecuencia Respiratoria')" placeholder="18" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-12 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.frecuencia_respiratoria ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">RPM</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.temperatura ? 'text-rose-600' : 'text-slate-600'">
                                        Temperatura <span class="text-slate-400 font-normal">(30-45)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" step="0.1" name="temperatura" x-model="vitals.temperatura" @input="validateField('temperatura', 30.0, 45.0, 'Temperatura')" placeholder="36.5" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-10 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.temperatura ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">°C</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.saturacion_oxigeno ? 'text-rose-600' : 'text-slate-600'">
                                        Sat. Oxígeno <span class="text-slate-400 font-normal">(0-100)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" step="0.1" name="saturacion_oxigeno" x-model="vitals.saturacion_oxigeno" @input="validateField('saturacion_oxigeno', 0, 100, 'Sat. Oxígeno')" placeholder="98" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-10 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.saturacion_oxigeno ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">%</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.peso_kg ? 'text-rose-600' : 'text-slate-600'">
                                        Peso <span class="text-slate-400 font-normal">(0.5-500)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" step="0.1" name="peso_kg" x-model="vitals.peso_kg" @input="validateField('peso_kg', 0.5, 500.0, 'Peso')" placeholder="70.5" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-10 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.peso_kg ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">kg</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase mb-1.5" :class="errors.talla_cm ? 'text-rose-600' : 'text-slate-600'">
                                        Estatura / Talla <span class="text-slate-400 font-normal">(20-300)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" step="0.1" name="talla_cm" x-model="vitals.talla_cm" @input="validateField('talla_cm', 20.0, 300.0, 'Estatura')" placeholder="170" class="w-full bg-slate-50 border-2 rounded-2xl p-3 pr-10 text-xs font-bold focus:bg-white outline-none transition-all" :class="errors.talla_cm ? 'border-rose-400 ring-4 ring-rose-400/20 text-rose-600' : 'border-slate-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10'">
                                        <span class="absolute right-3.5 top-3 text-[10px] font-bold text-slate-400 pointer-events-none">cm</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- PASO 3: FÁRMACOS E INDICACIONES -->
                    <div x-show="step === 3" 
                         x-transition:enter="transition ease-out duration-300 transform"
                         x-transition:enter-start="opacity-0 translate-x-8"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         class="space-y-6">

                        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 flex items-center gap-2 mb-2">
                                    <i class="bi bi-stethoscope text-teal-600"></i> Diagnóstico Clínico *
                                </label>
                                <textarea name="diagnostico" rows="2" placeholder="Ej. Faringoamigdalitis aguda bacteriana, Hipertensión arterial sistémica primaria..." required class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all uppercase"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-700 flex items-center gap-2 mb-2">
                                    <i class="bi bi-journal-text text-teal-600"></i> Indicaciones y Recomendaciones
                                </label>
                                <textarea name="indicaciones_generales" rows="2" placeholder="Cuidados generales, dieta, reposo o recomendaciones adicionales..." class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all uppercase"></textarea>
                            </div>
                        </div>
                        
                        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-5">
                            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                                <h4 class="text-xs font-black uppercase tracking-wider text-teal-900 flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-sm shadow-xs">
                                        <i class="bi bi-capsule-fill"></i>
                                    </span>
                                    Prescripción Farmacológica
                                </h4>
                                <button type="button" @click="addMedicamento()" class="text-xs bg-teal-600 hover:bg-teal-700 text-white font-extrabold px-4 py-2 rounded-xl flex items-center gap-2 shadow-sm transition-all active:scale-95">
                                    <i class="bi bi-plus-lg"></i> Agregar Fármaco
                                </button>
                            </div>

                            <div class="space-y-4">
                                <template x-for="(med, index) in medicamentosLista" :key="index">
                                    <div class="p-5 border-2 border-slate-200/80 rounded-2xl bg-slate-50/50 space-y-3 relative group transition-all hover:border-teal-300">
                                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                                            <span class="text-xs font-black text-teal-800 uppercase flex items-center gap-2">
                                                <i class="bi bi-prescription2"></i> Medicamento #<span x-text="index + 1"></span>
                                            </span>
                                            <button type="button" @click="removeMedicamento(index)" x-show="medicamentosLista.length > 1" class="text-rose-500 hover:text-rose-700 hover:bg-rose-100/60 px-3 py-1 rounded-xl text-xs font-bold transition-colors flex items-center gap-1">
                                                <i class="bi bi-trash-fill"></i> Quitar
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nombre Fármaco *</label>
                                                <input type="text" :name="`medicamentos[${index}][nombre]`" x-model="med.nombre" placeholder="Ej. Amoxicilina 500mg" required class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none uppercase">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Dosis Prescrita</label>
                                                <input type="text" :name="`medicamentos[${index}][dosis]`" x-model="med.dosis" placeholder="Ej. 1 cápsula C/8 hrs" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none uppercase">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Frecuencia / Duración</label>
                                                <input type="text" :name="`medicamentos[${index}][frecuencia]`" x-model="med.frecuencia" placeholder="Ej. Por 7 días consecutivos" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none uppercase">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- CONTROLES Y NAVEGACIÓN -->
                <div class="px-8 py-5 bg-white border-t border-slate-200/80 flex justify-between items-center shrink-0 rounded-b-[2.5rem]">
                    <div>
                        <button type="button" @click="step--" x-show="step > 1" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5">
                            <i class="bi bi-arrow-left"></i> Anterior
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded-2xl text-xs transition-all">
                            Cancelar
                        </button>
                        
                        <button type="button" @click="step++" x-show="step < 3" class="px-6 py-2.5 bg-teal-700 hover:bg-teal-800 text-white font-extrabold rounded-2xl text-xs transition-all flex items-center gap-1.5 shadow-md">
                            Siguiente <i class="bi bi-arrow-right"></i>
                        </button>

                        <button type="submit" 
                                x-show="step === 3"
                                :disabled="hasErrors" 
                                :class="hasErrors ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' : 'bg-gradient-to-r from-teal-700 to-emerald-600 hover:from-teal-800 hover:to-emerald-700 text-white shadow-lg shadow-teal-700/20 active:scale-95'" 
                                class="px-8 py-2.5 rounded-2xl text-xs font-black transition-all flex items-center gap-2">
                            <i class="bi bi-check-circle-fill text-sm"></i> Guardar Receta
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof flatpickr !== 'undefined') {
            const fp = flatpickr("#fecha_emision_input", {
                locale: "es",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j \\de F, Y",
                defaultDate: "today",
                animate: true,
                disableMobile: true,
                monthSelectorType: "static",
                // Posicionamiento inteligente: Forzamos que se abra hacia ARRIBA
                position: "above",
                onReady: function(selectedDates, dateStr, instance) {
                    // Forzar clases idénticas al input original en el altInput generado por Flatpickr
                    if (instance.altInput) {
                        instance.altInput.className = "w-full bg-white border-2 border-slate-200 hover:border-teal-500 rounded-2xl p-3.5 pl-11 pr-10 text-xs font-bold text-slate-800 focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 outline-none transition-all shadow-xs cursor-pointer";
                    }
                }
            });
        }

        @if ($errors->any())
            let mainDiv = document.querySelector('[x-data]');
            if (mainDiv && mainDiv.__x) {
                mainDiv.__x.$data.openCreateModal = true;
            }

            Swal.fire({
                icon: 'error',
                title: 'Errores en la Receta',
                html: `<ul style="text-align: left; font-size: 13px; color: #991b1b; font-weight: 600;">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                       </ul>`,
                confirmButtonColor: '#0d9488',
                confirmButtonText: 'Entendido'
            });
        @endif
    });
</script>