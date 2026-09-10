@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .animate-stagger {
        animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
</style>

<div class="space-y-8">
  
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger" style="animation-delay: 0ms;">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-people-fill text-emerald-300"></i> Gestión Institucional
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Directorio de Personal</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Administra las cuentas de usuario, asignación de cargos, datos profesionales y credenciales de acceso a MediTrack.
                </p>
            </div>

          
            <div class="flex items-center gap-3 flex-wrap">
                @if(auth()->user()->tienePermiso('Roles', 'ver'))
                    <a href="{{ route('roles.index') }}" class="bg-white/10 hover:bg-white/20 text-white font-bold px-4 py-3 rounded-2xl text-sm border border-white/20 backdrop-blur-md shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2 transform hover:-translate-y-0.5">
                        <i class="bi bi-shield-lock-fill text-emerald-300"></i>
                        <span>Gestionar Roles</span>
                    </a>
                @endif

                @if(auth()->user()->tienePermiso('Personal', 'crear'))
                    <a href="{{ route('personal.create') }}" class="group bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2 transform hover:-translate-y-0.5">
                        <i class="bi bi-person-plus-fill text-teal-600 group-hover:scale-110 transition-transform duration-300 text-base"></i>
                        <span>Registrar Personal</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-stagger" style="animation-delay: 100ms;">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold border border-teal-100">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total de Personal</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $personal->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold border border-sky-100">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Cuentas Activas</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $personal->where('estado', 'Activo')->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold border border-emerald-100">
                <i class="bi bi-building-check"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado del Sistema</p>
                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 mt-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Sincronizado
                </span>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 text-sm text-teal-900 bg-teal-50/90 rounded-2xl border border-teal-200 shadow-sm flex items-center gap-3 animate-stagger" style="animation-delay: 150ms;">
            <i class="bi bi-check-circle-fill text-teal-600 text-xl"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- TABLA DE DIRECTORIO CON TARJETA DE ESTILO PREMIUM -->
    <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden animate-stagger" style="animation-delay: 200ms;">
        <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-slate-50 via-teal-50/20 to-emerald-50/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-teal-600/20">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-base">Miembros Registrados</h3>
                    <p class="text-xs text-gray-500">Listado general de usuarios de la clínica</p>
                </div>
            </div>
            <span class="text-xs font-bold text-teal-700 bg-teal-100/80 px-3 py-1 rounded-full border border-teal-200/50">
                {{ $personal->count() }} Registros
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50/80 text-gray-500 uppercase font-bold border-b border-gray-100">
                    <tr>
                        <th class="py-4 px-6">Nombre Completo</th>
                        <th class="py-4 px-6">Correo Electrónico</th>
                        <th class="py-4 px-6">Cargo / Especialidad</th>
                        <th class="py-4 px-6">Teléfono</th>
                        <th class="py-4 px-6 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($personal as $persona)
                        <tr class="hover:bg-teal-50/30 transition-colors duration-150">
                            <td class="py-4 px-6 font-extrabold text-gray-800 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs uppercase">
                                        {{ substr($persona->nombre_completo, 0, 2) }}
                                    </div>
                                    <span>{{ $persona->nombre_completo }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 font-medium text-gray-600">
                                <div class="flex items-center gap-1.5">
                                    <i class="bi bi-envelope text-teal-600"></i>
                                    {{ $persona->usuario->email ?? 'Sin correo' }}
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200/60 shadow-2xs">
                                    <i class="bi bi-shield-check text-teal-600"></i>
                                    {{ $persona->especialidad_principal ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-semibold text-gray-600">
                                @if($persona->telefono)
                                    <div class="flex items-center gap-1.5">
                                        <i class="bi bi-telephone text-gray-400"></i>
                                        {{ $persona->telefono }}
                                    </div>
                                @else
                                    <span class="text-gray-400 italic">N/A</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Botón Editar -->
                                    @if(auth()->user()->tienePermiso('Personal', 'editar'))
                                        <a href="{{ route('personal.edit', $persona->id) }}" class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-xl text-xs font-semibold shadow-2xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center gap-1 px-3" title="Editar Registro">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </a>
                                    @endif

                                    <!-- Botón Eliminar -->
                                    @if(auth()->user()->tienePermiso('Personal', 'eliminar'))
                                        <button type="button" 
                                                onclick="eliminarPersonal({{ $persona->id }}, {{ json_encode($persona->nombre_completo) }})" 
                                                class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-2xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center gap-1 px-3" 
                                                title="Eliminar Personal">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    @endif

                                    <!-- Si no tiene permisos de edición ni eliminación -->
                                    @if(!auth()->user()->tienePermiso('Personal', 'editar') && !auth()->user()->tienePermiso('Personal', 'eliminar'))
                                        <span class="text-xs text-gray-400 italic font-medium">Sin acciones</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-500">
                                <i class="bi bi-person-x text-4xl text-gray-300 block mb-2"></i>
                                <p class="font-semibold text-gray-700">No hay registros de personal en el sistema.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function eliminarPersonal(id, nombre) {
    Swal.fire({
        title: '¿Eliminar personal?',
        text: `Estás a punto de borrar a "${nombre}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/personal/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Eliminado', data.message || 'El registro fue eliminado.', 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'No se pudo eliminar el registro.', 'error');
                }
            })
            .catch(err => Swal.fire('Error', err.message, 'error'));
        }
    });
}
</script>
@endsection