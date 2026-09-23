<!-- MODAL DE AVISO DE PRIVACIDAD (TIPO DOCUMENTO) -->
<template x-teleport="body">
    <div x-show="openAvisoModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[10000] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" 
         x-cloak>
        
        <div class="bg-slate-100 rounded-3xl shadow-2xl max-w-2xl w-full border border-gray-200 flex flex-col overflow-hidden my-auto max-h-[85vh]">
            
            <!-- HEADER DEL DOCUMENTO -->
            <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold border border-teal-100">
                        <i class="bi bi-file-earmark-medical-fill text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-gray-800 tracking-tight">Aviso de Privacidad Integral</h3>
                        <p class="text-[11px] text-gray-400">Protección de Datos Personales y Sensibles en Expedientes Clínicos</p>
                    </div>
                </div>
                <button type="button" @click="openAvisoModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold transition-colors">&times;</button>
            </div>

            <!-- CUERPO ESTILO HOJA DE DOCUMENTO OFICIAL -->
            <div class="p-6 overflow-y-auto flex-1 space-y-4 text-xs leading-relaxed text-gray-700">
                <div class="bg-white p-8 rounded-2xl border border-gray-200/80 shadow-xs space-y-4 font-sans text-[11px]">
                    
                    <!-- Encabezado del Documento -->
                    <div class="text-center border-b border-gray-100 pb-4 space-y-1">
                        <h4 class="font-black text-gray-800 text-xs tracking-wider uppercase">
                            {{ $config['nombre_clinica'] ?? 'MEDITRACK - SISTEMA INTEGRAL DE SALUD' }}
                        </h4>
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Aviso de Privacidad para Pacientes y Expedientes Clínicos</p>
                    </div>

                    @if(!empty($config['aviso_privacidad']))
                        <!-- CONTENIDO DINÁMICO DESDE CONFIGURACIÓN DE LA BD -->
                        <div class="whitespace-pre-line text-gray-700 leading-relaxed uppercase">
                            {{ $config['aviso_privacidad'] }}
                        </div>
                    @else
                        <!-- CONTENIDO PREDETERMINADO SI AÚN NO SE EDITA EN CONFIGURACIÓN -->
                        <p class="font-semibold text-gray-800">
                            En cumplimiento con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares y la Norma Oficial Mexicana NOM-004-SSA3-2012, del expediente clínico:
                        </p>

                        <div class="space-y-2">
                            <h5 class="font-extrabold text-teal-800 uppercase text-[10px] tracking-wider">1. Responsable del Tratamiento de Datos</h5>
                            <p class="text-gray-600">
                                <strong>{{ $config['nombre_clinica'] ?? 'MediTrack' }}</strong>, con domicilio en sus instalaciones clínicas registradas, es responsable de recabar sus datos personales, del uso que se le dé a los mismos y de su debida protección.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h5 class="font-extrabold text-teal-800 uppercase text-[10px] tracking-wider">2. Datos Personales Recabados</h5>
                            <p class="text-gray-600">
                                Para las finalidades señaladas en el presente aviso, podemos recabar sus datos personales de forma directa cuando usted nos los proporciona al solicitar una consulta, registro o tratamiento médico. Los datos incluyen:
                            </p>
                            <ul class="list-disc list-inside text-gray-600 space-y-1 pl-2">
                                <li><strong>Datos Identificativos:</strong> Nombre completo, CURP, fecha de nacimiento, género, domicilio y estado civil.</li>
                                <li><strong>Datos de Contacto:</strong> Teléfono principal, celular, correo electrónico y contacto de emergencia.</li>
                                <li><strong>Datos Sensibles/Clínicos:</strong> Historial médico, alergias, diagnósticos, patologías preexistentes y medicamentos prescritos.</li>
                            </ul>
                        </div>

                        <div class="space-y-2">
                            <h5 class="font-extrabold text-teal-800 uppercase text-[10px] tracking-wider">3. Finalidades del Tratamiento</h5>
                            <p class="text-gray-600">
                                Sus datos personales sensibles serán utilizados exclusivamente para la integración del expediente clínico, diagnóstico médico, prescripción de tratamientos, contacto de emergencia y seguimiento de citas.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h5 class="font-extrabold text-teal-800 uppercase text-[10px] tracking-wider">4. Derechos ARCO</h5>
                            <p class="text-gray-600">
                                Usted tiene derecho de acceder, rectificar y cancelar sus datos personales, así como de oponerse al tratamiento de los mismos o revocar el consentimiento que para tal fin nos haya otorgado, solicitándolo directamente en la recepción de la clínica.
                            </p>
                        </div>
                    @endif

                    <div class="bg-teal-50/60 p-3 rounded-xl border border-teal-100 text-teal-900 text-[10px] font-medium flex items-start gap-2">
                        <i class="bi bi-info-circle-fill text-teal-600 text-sm flex-shrink-0"></i>
                        <span>Al marcar la casilla de verificación en el formulario, usted confirma que ha leído, comprendido y aceptado la totalidad de las cláusulas descritas en este Aviso de Privacidad.</span>
                    </div>

                </div>
            </div>

            <!-- FOOTER DEL MODAL CON ACCIONES -->
            <div class="bg-white px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 flex-shrink-0">
                <p class="text-[11px] text-gray-400 font-medium">Documento de conformidad normativo.</p>
                
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" @click="openAvisoModal = false" class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition-all">
                        Cerrar Lectura
                    </button>
                    
                    <button type="button" 
                            @click="if (typeof aceptaAviso !== 'undefined') aceptaAviso = true; openAvisoModal = false;" 
                            class="flex-1 sm:flex-none px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs shadow-md shadow-teal-600/20 transition-all flex items-center justify-center gap-1.5">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Entendido y Aceptar</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>