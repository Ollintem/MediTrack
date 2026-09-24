@extends('layouts.admin')

@section('content')
<!-- FullCalendar Scripts -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js'></script>

<div class="py-6 space-y-6" x-data="moduloConsultasDoctor()" x-init="initCalendar()">

    <!-- BANNER INSTITUCIONAL -->
    <div class="relative bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 overflow-hidden">
        <div class="absolute -right-10 -top-14 w-44 h-44 rounded-full bg-white/5"></div>
        <div class="absolute right-16 bottom-[-3rem] w-24 h-24 rounded-full bg-white/5"></div>

        <div class="space-y-2 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                <i class="bi bi-heart-pulse-fill"></i> Panel médico personalizado
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Mi agenda y consultas médicas</h1>
            <p class="text-xs text-slate-200 font-medium max-w-xl leading-relaxed">
                Visualiza únicamente las citas asignadas a tu perfil, atiende a los pacientes completando su exploración física y emite recetas médicas integradas.
            </p>
        </div>
    </div>

    <!-- CALENDARIO PERSONALIZADO DEL DOCTOR -->
    <div class="relative bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <i class="bi bi-calendar-week text-teal-600"></i>
            <p class="text-sm font-black text-slate-800">Agenda de citas</p>
            <span class="text-[10px] font-semibold text-slate-400 hidden sm:inline">Haz clic en una cita para ver el detalle y atenderla</span>
            <span class="ml-auto text-xs font-bold px-3 py-1 bg-teal-50 text-teal-700 rounded-full"
                  x-text="citasVista + (citasVista === 1 ? ' cita en esta vista' : ' citas en esta vista')"></span>
        </div>

        <div id="calendar" class="w-full"></div>

        <!-- Indicador de carga -->
        <div x-show="cargando" x-cloak
             class="absolute inset-0 rounded-3xl bg-white/60 backdrop-blur-[1px] flex items-center justify-center">
            <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-white shadow-lg border border-slate-100 text-xs font-bold text-slate-600">
                <span class="w-4 h-4 rounded-full border-2 border-teal-600 border-t-transparent animate-spin"></span>
                Cargando citas...
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE DE CITA -->
    <template x-teleport="body">
        <div x-show="openModalDetalle" x-cloak
             @keydown.escape.window="openModalDetalle = false"
             class="fixed inset-0 z-[99999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden" @click.outside="openModalDetalle = false">
                <div class="relative px-7 pt-7 pb-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white overflow-hidden">
                    <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                    <button type="button" @click="openModalDetalle = false"
                            class="absolute right-4 top-4 w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                    <div class="relative flex items-center gap-4">
                        <span class="w-14 h-14 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-lg font-black text-emerald-300 shrink-0"
                              x-text="iniciales(citaActual.paciente_nombre)"></span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-300">Paciente</p>
                            <h3 class="text-base font-black tracking-tight truncate" x-text="citaActual.paciente_nombre"></h3>
                        </div>
                    </div>
                </div>

                <div class="p-7 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0"><i class="bi bi-calendar-event"></i></span>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Fecha y hora</p>
                            <p class="text-xs font-black text-slate-800 capitalize" x-text="citaActual.fecha"></p>
                            <p class="text-xs font-bold text-slate-500" x-text="citaActual.hora"></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0"><i class="bi bi-chat-left-text"></i></span>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Motivo de consulta</p>
                            <p class="text-xs font-bold text-slate-700 leading-relaxed" x-text="citaActual.motivo"></p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="openModalDetalle = false"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer">
                            Cerrar
                        </button>
                        <button type="button" @click="abrirAtencion()"
                                class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer">
                            <i class="bi bi-clipboard2-pulse"></i> Atender cita
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL DE ATENCIÓN MÉDICA (HISTORIAL CLÍNICO) -->
    <template x-teleport="body">
        <div x-show="openModalConsulta" x-cloak
             @keydown.escape.window="openModalConsulta = false"
             class="fixed inset-0 z-[99999] bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[92vh] flex flex-col overflow-hidden"
                 @click.outside="openModalConsulta = false">

                <!-- HEADER -->
                <div class="relative px-7 py-5 bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 text-white overflow-hidden shrink-0">
                    <div class="absolute -right-8 -top-10 w-32 h-32 rounded-full bg-white/5"></div>
                    <div class="relative flex items-center gap-4">
                        <span class="w-11 h-11 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-sm font-black text-emerald-300 shrink-0"
                              x-text="iniciales(form.paciente_nombre)"></span>
                        <div class="min-w-0">
                            <h3 class="text-base font-black tracking-tight truncate" x-text="'Atendiendo a: ' + form.paciente_nombre"></h3>
                            <p class="text-[11px] text-teal-100/80 font-medium">Registro de exploración clínica y signos vitales</p>
                        </div>
                        <button type="button" @click="openModalConsulta = false"
                                class="ml-auto w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-all cursor-pointer" title="Cerrar">
                            <i class="bi bi-x-lg text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- FORMULARIO DE CONSULTA -->
                <form @submit.prevent="guardarAtencion" class="flex flex-col min-h-0">
                    <div class="p-7 space-y-6 overflow-y-auto bg-slate-50/50">

                        <!-- Exploración general -->
                        <div>
                            <label class="etiqueta"><i class="bi bi-activity text-teal-600"></i> Exploración física general</label>
                            <textarea x-model="form.exploracion_fisica" rows="2" class="campo resize-none" placeholder="Signos vitales, peso, talla..."></textarea>
                        </div>

                        <!-- Exploración por sistemas -->
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                Exploración por sistemas <span class="flex-1 h-px bg-slate-200"></span>
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <template x-for="s in sistemas" :key="s.key">
                                    <div>
                                        <label class="etiqueta"><i class="bi text-teal-600" :class="s.icono"></i> <span x-text="s.label"></span></label>
                                        <input type="text" x-model="form[s.key]" class="campo" placeholder="Sin hallazgos">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Diagnóstico -->
                        <div>
                            <label class="etiqueta"><i class="bi bi-journal-medical text-teal-600"></i> Notas generales / Diagnóstico <span class="text-rose-500">*</span></label>
                            <textarea x-model="form.notas_generales" rows="3" required class="campo resize-none" placeholder="Diagnóstico clínico y observaciones..."></textarea>
                        </div>
                    </div>

                    <!-- BOTONES DE ACCIÓN Y CONEXIÓN CON RECETAS -->
                    <div class="flex justify-end gap-3 px-7 py-4 border-t border-slate-100 bg-white shrink-0">
                        <button type="button" @click="openModalConsulta = false" :disabled="guardando"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-extrabold rounded-2xl text-xs transition-all cursor-pointer disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardando"
                                class="px-6 py-2.5 bg-gradient-to-r from-teal-700 to-emerald-600 hover:from-teal-800 hover:to-emerald-700 text-white font-black rounded-2xl text-xs shadow-lg shadow-teal-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                            <span x-show="guardando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                            <i x-show="!guardando" class="bi bi-prescription2"></i>
                            <span x-text="guardando ? 'Guardando...' : 'Finalizar y emitir receta'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

<style>
    [x-cloak] { display: none !important; }

    /* Campos del formulario de consulta */
    .etiqueta {
        display: flex; align-items: center; gap: .4rem;
        font-size: 10px; font-weight: 900; color: #64748b;
        text-transform: uppercase; letter-spacing: .04em; margin-bottom: .375rem;
    }
    .campo {
        width: 100%; background: #fff; border: 2px solid #e2e8f0; border-radius: 1rem;
        padding: .7rem 1rem; font-size: .75rem; font-weight: 700; color: #1e293b;
        outline: none; transition: all .15s;
    }
    .campo::placeholder { font-weight: 500; color: #cbd5e1; }
    .campo:focus { border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20, 184, 166, .12); }

    /* Calendario acorde a la paleta del sistema */
    #calendar {
        --fc-border-color: #e2e8f0;
        --fc-today-bg-color: #f0fdfa;
        --fc-button-bg-color: #0f766e;
        --fc-button-border-color: #0f766e;
        --fc-button-hover-bg-color: #115e59;
        --fc-button-hover-border-color: #115e59;
        --fc-button-active-bg-color: #134e4a;
        --fc-button-active-border-color: #134e4a;
        --fc-event-bg-color: #0d9488;
        --fc-event-border-color: #0d9488;
        --fc-now-indicator-color: #f43f5e;
        font-size: .8rem;
    }
    #calendar .fc-toolbar-title { font-size: 1.1rem; font-weight: 900; color: #1e293b; text-transform: capitalize; }
    #calendar .fc-button { font-size: .7rem; font-weight: 800; border-radius: .75rem; text-transform: capitalize; box-shadow: none !important; }
    #calendar .fc-col-header-cell-cushion { font-size: .7rem; font-weight: 800; color: #64748b; text-transform: uppercase; padding: .6rem 0; }
    #calendar .fc-daygrid-day-number { font-size: .7rem; font-weight: 800; color: #475569; }
    #calendar .fc-event { border-radius: .5rem; padding: 1px 4px; font-weight: 700; cursor: pointer; transition: transform .12s; }
    #calendar .fc-event:hover { transform: translateY(-1px); }
    #calendar .fc-theme-standard td, #calendar .fc-theme-standard th, #calendar .fc-theme-standard .fc-scrollgrid { border-color: #e2e8f0; }
    @media (max-width: 640px) {
        #calendar .fc-toolbar { flex-direction: column; gap: .5rem; }
    }
</style>

<script>
    let calendarInstance = null;

    // Notificación flotante (esquina superior derecha)
    function notificar(icon, title) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    function moduloConsultasDoctor() {
        return {
            openModalDetalle: false,
            openModalConsulta: false,
            cargando: true,
            guardando: false,
            citasVista: 0,

            // Datos de la cita seleccionada (solo valores simples, no el objeto de FullCalendar)
            citaActual: { cita_id: '', paciente_id: '', paciente_nombre: '', fecha: '', hora: '', motivo: '' },

            // Campos de exploración por sistemas
            sistemas: [
                { key: 'cabeza_cuello', label: 'Cabeza y cuello', icono: 'bi-person-fill' },
                { key: 'torax',         label: 'Tórax',           icono: 'bi-heart-pulse' },
                { key: 'abdomen',       label: 'Abdomen',         icono: 'bi-circle' },
                { key: 'extremidades',  label: 'Extremidades',    icono: 'bi-hand-index' },
                { key: 'piel_faneras',  label: 'Piel y faneras',  icono: 'bi-droplet' },
                { key: 'neurologico',   label: 'Neurológico',     icono: 'bi-lightning-charge' }
            ],

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

            iniciales(nombre) {
                return (nombre || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
            },

            initCalendar() {
                let self = this;
                let calendarEl = document.getElementById('calendar');
                if (!calendarEl) return;

                calendarInstance = new FullCalendar.Calendar(calendarEl, {
                    locale: 'es',
                    initialView: window.innerWidth < 768 ? 'timeGridDay' : 'dayGridMonth',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                    height: 'auto',
                    nowIndicator: true,
                    navLinks: true,
                    dayMaxEvents: 3,
                    allDaySlot: false,
                    slotMinTime: '07:00:00',
                    slotMaxTime: '21:00:00',
                    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

                    // Endpoint filtrado por doctor
                    events: {
                        url: '{{ route("consultas.citas") }}',
                        failure: () => notificar('error', 'No se pudieron cargar las citas')
                    },

                    loading: (estaCargando) => { self.cargando = estaCargando; },
                    eventsSet: (eventos) => { self.citasVista = eventos.length; },

                    eventClick: function(arg) {
                        self.verDetalle(arg.event);
                    }
                });
                calendarInstance.render();
            },

            verDetalle(ev) {
                let p = ev.extendedProps;
                this.citaActual = {
                    cita_id: p.cita_id,
                    paciente_id: p.paciente_id,
                    paciente_nombre: p.paciente_nombre || ev.title,
                    fecha: ev.start
                        ? ev.start.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
                        : '',
                    hora: ev.start && !ev.allDay
                        ? ev.start.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
                        : 'Todo el día',
                    motivo: p.motivo || 'Sin motivo registrado'
                };
                this.openModalDetalle = true;
            },

            abrirAtencion() {
                this.form = {
                    cita_id: this.citaActual.cita_id,
                    paciente_id: this.citaActual.paciente_id,
                    paciente_nombre: this.citaActual.paciente_nombre,
                    exploracion_fisica: '',
                    cabeza_cuello: '',
                    torax: '',
                    abdomen: '',
                    extremidades: '',
                    piel_faneras: '',
                    neurologico: '',
                    notas_generales: ''
                };
                this.openModalDetalle = false;
                this.openModalConsulta = true;
            },

            async guardarAtencion() {
                if (this.guardando) return;
                this.guardando = true;

                try {
                    const response = await fetch('{{ route("consultas.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.form)
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || data.status !== 'success') {
                        let mensaje = data.message || 'No se pudo guardar la consulta.';
                        if (data.errors) {
                            mensaje = data.errors[Object.keys(data.errors)[0]][0];
                        }
                        notificar('warning', mensaje);
                        this.guardando = false;
                        return;
                    }

                    this.openModalConsulta = false;
                    notificar('success', 'Consulta guardada. Abriendo receta...');

                    if (data.redireccion_receta) {
                        // Redirección automática al módulo de recetas conectando la consulta
                        setTimeout(() => { window.location.href = data.redireccion_receta; }, 1200);
                    } else {
                        this.guardando = false;
                        calendarInstance.refetchEvents();
                    }
                } catch (error) {
                    console.error('Error de red:', error);
                    notificar('error', 'No se pudo comunicar con el servidor');
                    this.guardando = false;
                }
            }
        }
    }
</script>
@endsection