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
                 
                 // Nombres y Apellidos
                 primerNombre: @js(old('primer_nombre', '')),
                 apellidoPaterno: @js(old('apellido_paterno', '')),
                 apellidoMaterno: @js(old('apellido_materno', '')),

                 // CURP y Fecha
                 curp: @js(old('rut', '')),
                 curpInvalida: false,
                 fechaNacimiento: @js(old('fecha_nacimiento', '')),
                 errorCoincidenciaFecha: false,
                 errorCoincidenciaNombre: false,

                 // Contacto
                 telefono: @js(old('telefono', '')),
                 celular: @js(old('celular', '')),
                 email: @js(old('email', '')),
                 contactoEmergTelefono: @js(old('contacto_emerg_telefono', '')),

                 filtrarNumeros(valor) { 
                     return (valor || '').replace(/\D/g, '').slice(0, 10); 
                 },
                 esTelefonoValido(valor) { 
                     return !valor || valor.length === 10; 
                 },
                 esEmailValido(valor) {
                     if (!valor) return true;
                     return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(valor);
                 },

                 // Algoritmo para extraer las 4 iniciales oficiales de la CURP (Reglas RENAPO)
                 obtenerInicialesCURP() {
                     let paterno = (this.apellidoPaterno || '').trim().toUpperCase();
                     let materno = (this.apellidoMaterno || '').trim().toUpperCase();
                     let nombres = (this.primerNombre || '').trim().toUpperCase().split(/\s+/);

                     if (!paterno || !nombres[0]) return '';

                     // 1. Primera letra del primer apellido
                     let c1 = paterno.charAt(0);

                     // 2. Primera vocal interna del primer apellido
                     let vocalInterna = paterno.slice(1).match(/[AEIOU]/);
                     let c2 = vocalInterna ? vocalInterna[0] : 'X';

                     // 3. Primera letra del segundo apellido (o X)
                     let c3 = materno ? materno.charAt(0) : 'X';

                     // 4. Primera letra del nombre (omitir JOSE/MARIA si existe un segundo nombre)
                     let primerNombre = nombres[0];
                     if ((primerNombre === 'JOSE' || primerNombre === 'MARIA' || primerNombre === 'MA.' || primerNombre === 'MA') && nombres.length > 1) {
                         primerNombre = nombres[1];
                     }
                     let c4 = primerNombre.charAt(0);

                     // Remplazo de 'Ñ' por 'X' según la norma
                     return `${c1}${c2}${c3}${c4}`.replace(/Ñ/g, 'X');
                 },

                 // Validar CURP y auto-completar fecha
                 validarYExtraerCURP() {
                     this.curp = (this.curp || '').toUpperCase().trim();
                     const regexCurp = /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/;
                     
                     if (this.curp.length === 18 && regexCurp.test(this.curp)) {
                         this.curpInvalida = false;
                         
                         const anioDigitos = parseInt(this.curp.substring(4, 6), 10);
                         const mes = this.curp.substring(6, 8);
                         const dia = this.curp.substring(8, 10);
                         
                         const anioActualCorto = parseInt(new Date().getFullYear().toString().substr(-2), 10);
                         const siglo = anioDigitos > anioActualCorto ? '19' : '20';
                         const anioCompleto = `${siglo}${this.curp.substring(4, 6)}`;
                         
                         const fechaCalculada = `${anioCompleto}-${mes}-${dia}`;
                         if (!isNaN(Date.parse(fechaCalculada))) {
                             this.fechaNacimiento = fechaCalculada;
                         }
                     } else {
                         this.curpInvalida = this.curp.length > 0 && !regexCurp.test(this.curp);
                     }
                     this.validarCoincidencias();
                 },

                 // Validar Coincidencias de Fecha e Iniciales
                 validarCoincidencias() {
                     // 1. Validar Coincidencia de Fecha
                     const regexCurp = /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/;
                     if (this.curp.length === 18 && regexCurp.test(this.curp) && this.fechaNacimiento) {
                         const anioDigitos = this.curp.substring(4, 6);
                         const mes = this.curp.substring(6, 8);
                         const dia = this.curp.substring(8, 10);

                         const partesFecha = this.fechaNacimiento.split('-');
                         if (partesFecha.length === 3) {
                             const anioInputDigitos = partesFecha[0].substr(-2);
                             const mesInput = partesFecha[1];
                             const diaInput = partesFecha[2];

                             this.errorCoincidenciaFecha = (anioDigitos !== anioInputDigitos || mes !== mesInput || dia !== diaInput);
                         } else {
                             this.errorCoincidenciaFecha = false;
                         }
                     } else {
                         this.errorCoincidenciaFecha = false;
                     }

                     // 2. Validar Coincidencia de Iniciales del Nombre
                     const inicialesEsperadas = this.obtenerInicialesCURP();
                     if (this.curp.length >= 4 && inicialesEsperadas.length === 4) {
                         const inicialesCurp = this.curp.substring(0, 4);
                         this.errorCoincidenciaNombre = (inicialesCurp !== inicialesEsperadas);
                     } else {
                         this.errorCoincidenciaNombre = false;
                     }
                 },

                 // Ficha médica dinámica
                 alergias: @js(old('alergias', [''])),
                 condiciones: @js(old('condiciones', [''])),
                 medicamentos: @js(old('medicamentos', [''])),

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

                <!-- Pestañas de Navegación -->
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
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Nombre(s) *</label>
                                    <input type="text" name="primer_nombre" x-model="primerNombre" @input="validarCoincidencias()" required placeholder="Ej. María" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Apellido Paterno *</label>
                                    <input type="text" name="apellido_paterno" x-model="apellidoPaterno" @input="validarCoincidencias()" required placeholder="Ej. Cortés" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase">
                                </div>
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Apellido Materno</label>
                                    <input type="text" name="apellido_materno" x-model="apellidoMaterno" @input="validarCoincidencias()" placeholder="Ej. Moreno" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">CURP</label>
                                    <input type="text" name="rut" x-model="curp" @input="validarYExtraerCURP()" maxlength="18" placeholder="Ej. CORM031028HDFRR09" :class="(curpInvalida || errorCoincidenciaFecha || errorCoincidenciaNombre) ? 'border-rose-400 focus:ring-rose-400 bg-rose-50/20' : 'border-gray-300 focus:ring-teal-500'" class="w-full border rounded-2xl p-2.5 text-xs focus:ring-2 focus:outline-none uppercase tracking-wider font-semibold transition-all">
                                    
                                    <span x-show="curpInvalida" class="text-[10px] text-rose-600 font-bold mt-1 block">
                                        <i class="bi bi-exclamation-circle-fill"></i> El formato de la CURP no es válido (18 caracteres).
                                    </span>
                                    <span x-show="errorCoincidenciaNombre && !curpInvalida" class="text-[10px] text-rose-600 font-bold mt-1 block">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Las iniciales de la CURP no coinciden con los nombres/apellidos ingresados.
                                    </span>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" x-model="fechaNacimiento" @change="validarCoincidencias()" min="1900-01-01" max="{{ date('Y-m-d') }}" :class="errorCoincidenciaFecha ? 'border-rose-400 focus:ring-rose-400 bg-rose-50/20' : 'border-gray-300 focus:ring-teal-500'" class="w-full border rounded-2xl p-2.5 text-xs focus:ring-2 focus:outline-none bg-white font-medium transition-all">
                                    <span x-show="errorCoincidenciaFecha" class="text-[10px] text-rose-600 font-bold mt-1 block">
                                        <i class="bi bi-exclamation-triangle-fill"></i> La fecha no coincide con los dígitos de la CURP.
                                    </span>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Género</label>
                                    <div class="relative">
                                        <select name="genero" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white appearance-none pr-8">
                                            <option value="">Seleccione género...</option>
                                            <option value="FEMENINO" {{ old('genero') == 'FEMENINO' ? 'selected' : '' }}>FEMENINO</option>
                                            <option value="MASCULINO" {{ old('genero') == 'MASCULINO' ? 'selected' : '' }}>MASCULINO</option>
                                            <option value="OTRO" {{ old('genero') == 'OTRO' ? 'selected' : '' }}>OTRO</option>
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Estado Civil</label>
                                    <div class="relative">
                                        <select name="estado_civil" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white appearance-none pr-8">
                                            <option value="">Seleccione estado civil...</option>
                                            <option value="SOLTERO(A)" {{ old('estado_civil') == 'SOLTERO(A)' ? 'selected' : '' }}>SOLTERO(A)</option>
                                            <option value="CASADO(A)" {{ old('estado_civil') == 'CASADO(A)' ? 'selected' : '' }}>CASADO(A)</option>
                                            <option value="DIVORCIADO(A)" {{ old('estado_civil') == 'DIVORCIADO(A)' ? 'selected' : '' }}>DIVORCIADO(A)</option>
                                            <option value="VIUDO(A)" {{ old('estado_civil') == 'VIUDO(A)' ? 'selected' : '' }}>VIUDO(A)</option>
                                            <option value="UNIÓN LIBRE" {{ old('estado_civil') == 'UNIÓN LIBRE' ? 'selected' : '' }}>UNIÓN LIBRE</option>
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Nacionalidad</label>
                                    <input type="text" name="nacionalidad" value="{{ old('nacionalidad') }}" placeholder="Ej. MEXICANA" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase">
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Grupo Sanguíneo</label>
                                    <div class="relative">
                                        <select name="grupo_sanguineo" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white appearance-none pr-8">
                                            <option value="">Seleccione grupo...</option>
                                            @foreach(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'] as $gs)
                                                <option value="{{ $gs }}" {{ old('grupo_sanguineo') == $gs ? 'selected' : '' }}>{{ $gs }}</option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: CONTACTO Y EMERGENCIA -->
                        <div x-show="tab === 'contacto'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Teléfono Principal (10 dígitos)</label>
                                    <input type="text" name="telefono" x-model="telefono" @input="telefono = filtrarNumeros($event.target.value)" maxlength="10" placeholder="Ej. 5551234567" :class="!esTelefonoValido(telefono) ? 'border-rose-400 focus:ring-rose-400 bg-rose-50/20' : 'border-gray-300 focus:ring-teal-500'" class="w-full border rounded-2xl p-2.5 text-xs focus:ring-2 focus:outline-none transition-all">
                                    <span x-show="telefono && telefono.length > 0 && telefono.length < 10" class="text-[10px] text-rose-600 font-bold mt-1 block">
                                        <i class="bi bi-exclamation-circle-fill"></i> Debe contener exactamente 10 dígitos.
                                    </span>
                                </div>

                                <div>
                                    <label class="block font-bold text-gray-700 mb-1">Celular (10 dígitos)</label>
                                    <input type="text" name="celular" x-model="celular" @input="celular = filtrarNumeros($event.target.value)" maxlength="10" placeholder="Ej. 5510445986" :class="!esTelefonoValido(celular) ? 'border-rose-400 focus:ring-rose-400 bg-rose-50/20' : 'border-gray-300 focus:ring-teal-500'" class="w-full border rounded-2xl p-2.5 text-xs focus:ring-2 focus:outline-none transition-all">
                                    <span x-show="celular && celular.length > 0 && celular.length < 10" class="text-[10px] text-rose-600 font-bold mt-1 block">
                                        <i class="bi bi-exclamation-circle-fill"></i> Debe contener exactamente 10 dígitos.
                                    </span>
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="block font-bold text-gray-700 mb-1">Correo Electrónico</label>
                                    <input type="email" name="email" x-model="email" placeholder="paciente@ejemplo.com" :class="!esEmailValido(email) ? 'border-rose-400 focus:ring-rose-400 bg-rose-50/20' : 'border-gray-300 focus:ring-teal-500'" class="w-full border rounded-2xl p-2.5 text-xs focus:ring-2 focus:outline-none transition-all">
                                    <span x-show="!esEmailValido(email)" class="text-[10px] text-rose-600 font-bold mt-1 block">
                                        <i class="bi bi-exclamation-circle-fill"></i> Ingrese una dirección de correo válida.
                                    </span>
                                </div>

                                <div class="col-span-1 md:col-span-2">
                                    <label class="block font-bold text-gray-700 mb-1">Dirección Particular</label>
                                    <input type="text" name="direccion" value="{{ old('direccion') }}" placeholder="Calle, Número, Colonia, Ciudad" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase">
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-3 space-y-3">
                                <h5 class="font-bold text-gray-700 text-[11px] uppercase tracking-wider text-rose-600 flex items-center gap-1">
                                    <i class="bi bi-shield-exclamation"></i> En Caso de Emergencia
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Nombre Contacto</label>
                                        <input type="text" name="contacto_emerg_nombre" value="{{ old('contacto_emerg_nombre') }}" placeholder="Familiar o Tutor" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Parentesco / Relación</label>
                                        <input type="text" name="contacto_emerg_relacion" value="{{ old('contacto_emerg_relacion') }}" placeholder="Ej. Madre, Esposo" class="w-full border border-gray-300 rounded-2xl p-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 mb-1">Teléfono Emergencia (10 dígitos)</label>
                                        <input type="text" name="contacto_emerg_telefono" x-model="contactoEmergTelefono" @input="contactoEmergTelefono = filtrarNumeros($event.target.value)" maxlength="10" placeholder="Ej. 5559876543" :class="!esTelefonoValido(contactoEmergTelefono) ? 'border-rose-400 focus:ring-rose-400 bg-rose-50/20' : 'border-gray-300 focus:ring-teal-500'" class="w-full border rounded-2xl p-2.5 text-xs focus:ring-2 focus:outline-none transition-all">
                                        <span x-show="contactoEmergTelefono && contactoEmergTelefono.length > 0 && contactoEmergTelefono.length < 10" class="text-[10px] text-rose-600 font-bold mt-1 block">
                                            <i class="bi bi-exclamation-circle-fill"></i> Debe contener 10 dígitos.
                                        </span>
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
                                        <input type="text" name="alergias[]" x-model="alergias[index]" placeholder="Ej. Penicilina" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white uppercase">
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
                                        <input type="text" name="condiciones[]" x-model="condiciones[index]" placeholder="Ej. Diabetes Tipo 2" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white uppercase">
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
                                        <input type="text" name="medicamentos[]" x-model="medicamentos[index]" placeholder="Ej. Losartán 50mg" class="flex-1 border border-gray-300 rounded-xl p-2 text-xs focus:outline-none bg-white uppercase">
                                        <button type="button" @click="removeMedicamento(index)" class="p-2 bg-teal-50 text-teal-600 rounded-xl"><i class="bi bi-trash"></i></button>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Fijo -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="acepta_aviso_privacidad" value="1" {{ old('acepta_aviso_privacidad', '1') ? 'checked' : '' }} class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-[11px] text-gray-600 font-semibold">
                                Acepta 
                                <a href="#" target="_blank" class="text-teal-600 hover:underline inline-flex items-center gap-0.5">
                                    aviso de privacidad <i class="bi bi-box-arrow-up-right text-[9px]"></i>
                                </a>
                            </span>
                        </label>

                        <div class="flex gap-3">
                            <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-semibold">Cancelar</button>
                            <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-teal-600/20 active:scale-95 transition-all">Guardar Paciente</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>
@endif