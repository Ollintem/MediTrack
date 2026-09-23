@extends('layouts.admin')

@section('content')
<!-- Importar FullCalendar y Localización -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js'></script>

<style>
    :root {
        --fc-border-color: #f1f5f9;
        --fc-button-bg-color: #0f766e;
        --fc-button-border-color: #0f766e;
        --fc-button-hover-bg-color: #0d9488;
        --fc-button-hover-border-color: #0d9488;
        --fc-button-active-bg-color: #115e59;
        --fc-event-bg-color: #0d9488;
        --fc-event-border-color: #0f766e;
        --fc-today-bg-color: #f0fdf4;
        --fc-page-bg-color: #ffffff;
    }

    .fc-theme-standard .fc-scrollgrid {
        border: 1px solid #f1f5f9 !important;
        background: #fff;
        border-radius: 1.25rem;
        overflow: hidden;
    }

    .fc-header-toolbar {
        background: #ffffff;
        padding: 1.25rem;
        border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        margin-bottom: 1.5rem !important;
    }

    .fc-toolbar-title {
        font-size: 1.15rem !important;
        font-weight: 800 !important;
        color: #0f766e !important;
    }

    .fc-col-header-cell {
        background: #f8fafc;
        padding: 0.85rem 0 !important;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.7rem;
        border-bottom: 2px solid #f1f5f9 !important;
    }

    .fc .fc-button {
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        padding: 0.5rem 1rem !important;
        text-transform: capitalize;
        box-shadow: 0 2px 4px rgba(15, 118, 110, 0.1);
    }

    .fc-daygrid-day-number {
        font-size: 0.8rem;
        font-weight: 700;
        color: #334155;
        padding: 8px 10px !important;
    }

    .fc-event {
        border-radius: 0.5rem;
        padding: 3px 6px;
        font-weight: 700;
        font-size: 0.7rem;
        border: none !important;
        cursor: pointer;
    }
</style>

<!-- CONTENEDOR PRINCIPAL -->
<div class="py-6 space-y-6" x-data="moduloCitas()" x-init="initCalendar()">
    
    <!-- BANNER INSTITUCIONAL (Idéntico al de Personal y demás módulos) -->
    <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="space-y-2 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                <i class="bi bi-calendar-check-fill"></i> Gestión de Agenda
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Directorio y Control de Citas</h1>
            <p class="text-xs text-slate-200 font-medium max-w-xl leading-relaxed">
                Administra el calendario de atenciones médicas, programa nuevas consultas y supervisa el estado de las citas en tiempo real.
            </p>
        </div>

        <div class="flex items-center gap-3 z-10">
            <button type="button" @click="abrirModalCrear()" class="bg-white text-teal-900 hover:bg-emerald-50 px-6 py-3 rounded-2xl text-xs font-black shadow-lg transition-all flex items-center gap-2 cursor-pointer">
                <i class="bi bi-plus-lg text-sm text-teal-700"></i> Agendar Cita
            </button>
        </div>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS / RESUMEN RÁPIDO -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div>
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Vista General</span>
                <h3 class="text-sm font-black text-slate-800">Calendario Activo</h3>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Sincronización</span>
                <h3 class="text-sm font-black text-emerald-600 flex items-center gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> En Línea
                </h3>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <span class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Modo de Operación</span>
                <h3 class="text-sm font-black text-slate-800">Interactivo / Arrastrar</h3>
            </div>
        </div>
    </div>

    <!-- TARJETA CONTENEDORA DEL CALENDARIO PRINCIPAL -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
        <div id="calendar" class="w-full"></div>
    </div>

    <!-- MODAL INCLUIDO APARTE -->
    @include('citas.modal')

</div>

<script>
    let calendarInstance = null;

    function moduloCitas() {
        return {
            openCitaModal: false, 
            modoEdicion: false, 
            citaId: null,
            form: {
                paciente_id: '', 
                personal_id: '', 
                fecha: '', 
                hora: '09:00', 
                duracion_min: 30, 
                motivo: '', 
                tipo_consulta: 'Primera Vez', 
                estado: 'Pendiente'
            },
            
            initCalendar() {
                let self = this;
                let calendarEl = document.getElementById('calendar');
                if (!calendarEl) return;

                calendarInstance = new FullCalendar.Calendar(calendarEl, {
                    locale: 'es',
                    initialView: 'dayGridMonth',
                    headerToolbar: { 
                        left: 'prev,next today', 
                        center: 'title', 
                        right: 'dayGridMonth,timeGridWeek,timeGridDay' 
                    },
                    navLinks: true, 
                    editable: true, 
                    selectable: true,
                    events: '{{ route("citas.eventos") }}',

                    select: function(arg) {
                        self.abrirModalCrear(arg.startStr);
                        calendarInstance.unselect();
                    },

                    eventClick: function(arg) {
                        let ev = arg.event;
                        Swal.fire({
                            title: ev.title,
                            html: `<strong>Estado:</strong> ${ev.extendedProps.estado}<br><span class="text-xs text-gray-500 mt-1 block">¿Qué deseas hacer?</span>`,
                            icon: 'info',
                            showDenyButton: true, 
                            showCancelButton: true,
                            confirmButtonText: 'Editar',
                            denyButtonText: 'Eliminar',
                            cancelButtonText: 'Cerrar',
                            confirmButtonColor: '#0284c7',
                            denyButtonColor: '#f43f5e'
                        }).then((result) => {
                            if (result.isConfirmed) self.abrirModalEditar(ev);
                            else if (result.isDenied) self.eliminarCita(ev.id);
                        });
                    },

                    eventDrop: function(info) {
                        fetch(`/citas/${info.event.id}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ dragged: true, start: info.event.startStr })
                        }).then(r => r.json()).then(data => {
                            if(window.notificar) window.notificar('Cita reprogramada');
                        });
                    }
                });
                calendarInstance.render();
            },

            abrirModalCrear(fechaSeleccionada = '') {
                this.modoEdicion = false;
                this.citaId = null;
                this.form = {
                    paciente_id: '', 
                    personal_id: '', 
                    fecha: '', 
                    hora: '09:00', 
                    duracion_min: 30, 
                    motivo: '', 
                    tipo_consulta: 'Primera Vez', 
                    estado: 'Pendiente'
                };

                if (fechaSeleccionada) {
                    if(fechaSeleccionada.includes('T')) {
                        let parts = fechaSeleccionada.split('T');
                        this.form.fecha = parts[0];
                        this.form.hora = parts[1].substring(0, 5);
                    } else {
                        this.form.fecha = fechaSeleccionada;
                    }
                } else {
                    this.form.fecha = new Date().toISOString().split('T')[0];
                }

                this.openCitaModal = true;
            },
            
            abrirModalEditar(ev) {
                this.modoEdicion = true;
                this.citaId = ev.id;
                let props = ev.extendedProps;
                this.form = {
                    motivo: ev.title, 
                    paciente_id: props.paciente_id, 
                    personal_id: props.personal_id,
                    fecha: props.fecha, 
                    hora: props.hora, 
                    duracion_min: props.duracion_min,
                    tipo_consulta: props.tipo_consulta, 
                    estado: props.estado
                };
                this.openCitaModal = true;
            },

            guardarCita() {
    let url = this.modoEdicion ? `/citas/${this.citaId}` : '/citas';
    let method = this.modoEdicion ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(this.form)
    })
    .then(async response => {
        let data = await response.json();
        
        // Si Laravel devuelve un error de validación u otro error HTTP
        if (!response.ok) {
            let mensaje = data.message || 'Revisa que todos los campos requeridos estén llenos.';
            if (data.errors) {
                // Extrae el primer error de validación de Laravel
                let primerCampo = Object.keys(data.errors)[0];
                mensaje = data.errors[primerCampo][0];
            }
            Swal.fire({
                title: 'Atención',
                text: mensaje,
                icon: 'warning',
                confirmButtonColor: '#0f766e'
            });
            return;
        }

        // Si todo sale bien
        this.openCitaModal = false;
        Swal.fire({
            title: '¡Guardado!',
            text: 'La cita se ha registrado correctamente',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
        
        if(calendarInstance) {
            calendarInstance.refetchEvents();
        }
    })
    .catch(error => {
        console.error('Error de red:', error);
        Swal.fire('Error', 'No se pudo comunicar con el servidor', 'error');
    });
},

            eliminarCita(id) {
                fetch(`/citas/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                }).then(r => r.json()).then(data => {
                    if(window.notificar) window.notificar('Cita eliminada', 'success');
                    if(calendarInstance) calendarInstance.refetchEvents();
                });
            }
        }
    }
</script>
@endsection