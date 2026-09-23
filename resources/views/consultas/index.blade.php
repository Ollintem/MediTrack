@extends('layouts.admin')

@section('content')
<!-- FullCalendar Scripts -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js'></script>

<div class="py-6 space-y-6" x-data="moduloConsultasDoctor()" x-init="initCalendar()">
    
    <!-- BANNER INSTITUCIONAL -->
    <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
        <div class="space-y-2 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                <i class="bi bi-heart-pulse-fill"></i> Panel Médico Personalizado
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Mi Agenda y Consultas Médicas</h1>
            <p class="text-xs text-slate-200 font-medium max-w-xl leading-relaxed">
                Visualiza únicamente las citas asignadas a tu perfil, atiende a los pacientes completando su exploración física y emite recetas médicas integradas.
            </p>
        </div>
    </div>

    <!-- CALENDARIO PERSONALIZADO DEL DOCTOR -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
        <div id="calendar" class="w-full"></div>
    </div>

    <!-- MODAL DE ATENCIÓN MÉDICA (HISTORIAL CLÍNICO) -->
    <template x-teleport="body">
        <div x-show="openModalConsulta" 
             class="fixed inset-0 z-[99999] bg-slate-950/75 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto" 
             x-cloak>
            
            <div class="bg-white rounded-[2rem] shadow-2xl max-w-3xl w-full border border-slate-100 flex flex-col overflow-hidden my-auto" @click.outside="openModalConsulta = false">
                
                <!-- HEADER -->
                <div class="px-8 py-5 bg-gradient-to-r from-teal-900 to-emerald-800 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-black tracking-tight" x-text="'Atendiendo a: ' + form.paciente_nombre"></h3>
                        <p class="text-[11px] text-emerald-200">Registro de exploración clínica y signos vitales</p>
                    </div>
                    <button type="button" @click="openModalConsulta = false" class="text-white/70 hover:text-white"><i class="bi bi-x-lg text-lg"></i></button>
                </div>

                <!-- FORMULARIO DE CONSULTA (Campos exactos de tu base de datos) -->
                <form @submit.prevent="guardarAtencion" class="p-8 space-y-4 max-h-[70vh] overflow-y-auto bg-slate-50/50">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Exploración Física General</label>
                        <textarea x-model="form.exploracion_fisica" rows="2" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none" placeholder="Signos vitales, peso, talla..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Cabeza y Cuello[cite: 5]</label>
                            <input type="text" x-model="form.cabeza_cuello" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tórax[cite: 5]</label>
                            <input type="text" x-model="form.torax" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Abdomen[cite: 5]</label>
                            <input type="text" x-model="form.abdomen" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Extremidades[cite: 5]</label>
                            <input type="text" x-model="form.extremidades" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Piel y Faneras[cite: 5]</label>
                            <input type="text" x-model="form.piel_faneras" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Neurológico[cite: 5]</label>
                            <input type="text" x-model="form.neurologico" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Notas Generales / Diagnóstico[cite: 5]</label>
                        <textarea x-model="form.notas_generales" rows="3" class="w-full bg-white border-2 border-slate-200 rounded-2xl p-3 text-xs font-bold text-slate-800 outline-none" placeholder="Diagnóstico clínico y observaciones..."></textarea>
                    </div>

                    <!-- BOTONES DE ACCIÓN Y CONEXIÓN CON RECETAS -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="openModalConsulta = false" class="px-5 py-2.5 bg-slate-100 text-slate-700 font-extrabold rounded-2xl text-xs">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-teal-700 to-emerald-600 text-white font-black rounded-2xl text-xs shadow-lg flex items-center gap-2 cursor-pointer">
                            <i class="bi bi-prescription2"></i> Finalizar y Emitir Receta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<script>
    let calendarInstance = null;

    function moduloConsultasDoctor() {
        return {
            openModalConsulta: false,
            form: {
                cita_id: '',
                paciente_id: '',
                paciente_nombre: '',
                exploracion_fisica: '',
                cabeza_cuello: '',
                torax: '',
                abdomen: '',
                extremidades: '',
                piel_faneras: '',
                neurologico: '',
                notas_generales: ''
            },

            initCalendar() {
                let self = this;
                let calendarEl = document.getElementById('calendar');
                if (!calendarEl) return;

                calendarInstance = new FullCalendar.Calendar(calendarEl, {
                    locale: 'es',
                    initialView: 'dayGridMonth',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                    events: '{{ route("consultas.citas") }}', // Endpoint filtrado por doctor

                    eventClick: function(arg) {
                        let ev = arg.event;
                        Swal.fire({
                            title: ev.title,
                            text: `Motivo: ${ev.extendedProps.motivo}`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Atender Cita',
                            cancelButtonText: 'Cerrar',
                            confirmButtonColor: '#0f766e'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                self.abrirAtencion(ev);
                            }
                        });
                    }
                });
                calendarInstance.render();
            },

            abrirAtencion(ev) {
                let p = ev.extendedProps;
                this.form = {
                    cita_id: p.cita_id,
                    paciente_id: p.paciente_id,
                    paciente_nombre: p.paciente_nombre,
                    exploracion_fisica: '',
                    cabeza_cuello: '',
                    torax: '',
                    abdomen: '',
                    extremidades: '',
                    piel_faneras: '',
                    neurologico: '',
                    notas_generales: ''
                };
                this.openModalConsulta = true;
            },

            guardarAtencion() {
                fetch('{{ route("consultas.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.form)
                })
                .then(r => r.json())
                .then(data => {
                    this.openModalConsulta = false;
                    if(data.status === 'success') {
                        Swal.fire({
                            title: '¡Consulta Atendida!',
                            text: 'Redirigiendo al módulo de recetas...',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Redirección automática al módulo de recetas conectando la consulta
                            window.location.href = data.redireccion_receta;
                        });
                    }
                });
            }
        }
    }
</script>
@endsection