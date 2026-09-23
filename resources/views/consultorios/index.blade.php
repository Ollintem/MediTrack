@extends('layouts.admin')

@section('content')
<div class="py-6 space-y-6" x-data="moduloConsultorios()">
    
    <!-- BANNER INSTITUCIONAL -->
    <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
        <div class="space-y-2 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                <i class="bi bi-hospital-fill"></i> Infraestructura Médica
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Directorio de Consultorios</h1>
            <p class="text-xs text-slate-200 font-medium max-w-xl leading-relaxed">
                Administra las salas, consultorios físicos y pisos disponibles para la asignación estructurada de citas en el sistema.
            </p>
        </div>
        <button type="button" @click="abrirModalCrear()" class="bg-white text-teal-900 hover:bg-emerald-50 px-6 py-3 rounded-2xl text-xs font-black shadow-lg transition-all flex items-center gap-2 cursor-pointer z-10">
            <i class="bi bi-plus-lg text-sm text-teal-700"></i> Registrar Consultorio
        </button>
    </div>

    <!-- TABLA DE CONSULTORIOS -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-base font-black text-slate-800">Consultorios Registrados</h3>
            <span class="text-xs font-bold px-3 py-1 bg-teal-50 text-teal-700 rounded-full">{{ count($consultorios ?? []) }} en total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Clínica ID</th>
                        <th class="py-4 px-6">Nombre del Consultorio</th>
                        <th class="py-4 px-6">Piso</th>
                        <th class="py-4 px-6">Estado</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-bold text-slate-700">
                    @forelse($consultorios ?? [] as $c)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 text-slate-400">#{{ $c->id }}</td>
                            <td class="py-4 px-6">Clínica #{{ $c->clinica_id }}</td>
                            <td class="py-4 px-6 text-slate-900 font-black flex items-center gap-2">
                                <i class="bi bi-door-open text-teal-600 text-sm"></i> {{ $c->nombre }}
                            </td>
                            <td class="py-4 px-6">Piso {{ $c->piso }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black {{ $c->estado == 'Activo' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }}">
                                    {{ $c->estado }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <button type="button" @click='abrirModalEditar(@json($c))' class="px-3 py-1.5 bg-sky-50 text-sky-600 hover:bg-sky-100 rounded-xl transition-all font-bold cursor-pointer">Editar</button>
                                <button type="button" @click="eliminarConsultorio({{ $c->id }})" class="px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl transition-all font-bold cursor-pointer">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 font-medium">
                                No hay consultorios registrados todavía. ¡Crea el primero arriba!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- INCLUIMOS EL MODAL DESDE ARCHIVO APARTE -->
    @include('consultorios.modal')

</div>

<script>
    function moduloConsultorios() {
    return {
        openModal: false,
        modoEdicion: false,
        idSeleccionado: null,
        form: { clinica_id: 1, nombre: '', piso: '1', estado: '1' }, // <--- Debe estar en 'A'

        abrirModalCrear() {
            this.modoEdicion = false;
            this.form = { clinica_id: 1, nombre: '', piso: '1', estado: '1' }; // <--- Y aquí también
            this.openModal = true;
        },
        // ... resto del código

            abrirModalEditar(item) {
                this.modoEdicion = true;
                this.idSeleccionado = item.id;
                this.form = { clinica_id: item.clinica_id, nombre: item.nombre, piso: item.piso, estado: item.estado };
                this.openModal = true;
            },

            guardar() {
                let url = this.modoEdicion ? `/consultorios/${this.idSeleccionado}` : '/consultorios';
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
                    let res = await response.json();
                    
                    if (!response.ok) {
                        let mensaje = res.message || 'Revisa que todos los campos requeridos estén llenos.';
                        if (res.errors) {
                            let primerCampo = Object.keys(res.errors)[0];
                            mensaje = res.errors[primerCampo][0];
                        }
                        Swal.fire({
                            title: 'Atención',
                            text: mensaje,
                            icon: 'warning',
                            confirmButtonColor: '#0f766e'
                        });
                        return;
                    }

                    this.openModal = false;
                    Swal.fire({ 
                        title: '¡Éxito!', 
                        text: res.message, 
                        icon: 'success', 
                        timer: 1200, 
                        showConfirmButton: false 
                    }).then(() => location.reload());
                })
                .catch(error => {
                    console.error('Error de red:', error);
                    Swal.fire('Error', 'No se pudo comunicar con el servidor', 'error');
                });
            },

            eliminarConsultorio(id) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Se eliminará este consultorio del sistema',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f43f5e',
                    confirmButtonText: 'Sí, eliminar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/consultorios/${id}`, {
                            method: 'DELETE',
                            headers: { 
                                'Content-Type': 'application/json', 
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(res => {
                            Swal.fire({ 
                                title: 'Eliminado', 
                                text: res.message, 
                                icon: 'success', 
                                timer: 1000, 
                                showConfirmButton: false 
                            }).then(() => location.reload());
                        });
                    }
                });
            }
        }
    }
</script>
@endsection