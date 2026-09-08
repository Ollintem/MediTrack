@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Envoltorio Alpine.js para abrir/cerrar el modal -->
<div class="space-y-6" x-data="{ openModal: false }">
    <!-- Encabezado de la Sección -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestión de Roles y Permisos</h2>
        </div>
        
        <!-- Botón para abrir el Modal Compartido -->
        <button @click="openModal = true" type="button" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Nuevo Rol
        </button>
    </div>

    @if (session('success'))
        <div class="p-4 mb-4 text-sm text-teal-800 bg-teal-100 rounded-lg border border-teal-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Banner Informativo -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3 text-blue-800 text-sm font-medium">
            <i class="bi bi-info-circle-fill text-blue-600 text-lg"></i>
            <span>Define y visualiza las acciones puede realizar cada cargo dentro de los módulos del sistema.
</span>
        </div>
    </div>

    <!-- Tabla Principal de Roles -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-xs uppercase text-white font-bold tracking-wider">
                        <th class="px-6 py-4 bg-slate-800">ID / Rol</th>
                        <th class="px-6 py-4 bg-slate-800">Módulo</th>
                        <th class="px-4 py-4 bg-slate-800 text-center">Mostrar</th>
                        <th class="px-4 py-4 bg-slate-900 text-center">Detalle</th>
                        <th class="px-4 py-4 bg-emerald-950 text-center">Alta</th>
                        <th class="px-4 py-4 bg-amber-950 text-center">Editar</th>
                        <th class="px-4 py-4 bg-rose-950 text-center">Eliminar</th>
                        <th class="px-6 py-4 bg-slate-800 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    @forelse ($roles as $rol)
                        @if (isset($modulos) && $modulos->count() > 0)
                            @foreach ($modulos as $index => $modulo)
    @php
        // Buscar el permiso del rol actual para el módulo específico
        $permiso = $rol->permisos->firstWhere('modulo_id', $modulo->id);
    @endphp
    <tr class="hover:bg-gray-50 transition border-b border-gray-100">
        @if ($index === 0)
            <td rowspan="{{ $modulos->count() }}" class="px-6 py-4 font-bold text-gray-900 align-top border-r border-gray-200 bg-gray-50/50">
                <div class="flex flex-col gap-1 sticky top-20">
                    <span class="text-xs font-mono text-gray-400">#{{ $rol->id }}</span>
                    <span class="text-base text-gray-800">{{ $rol->nombre }}</span>
                </div>
            </td>
        @endif

        <td class="px-6 py-3 font-semibold text-gray-800 bg-white">
            {{ $modulo->nombre }}
        </td>

        <!-- MOSTRAR / VER -->
        <td class="px-4 py-3 text-center">
            <input type="checkbox" disabled {{ ($permiso && $permiso->puede_ver) ? 'checked' : '' }} class="w-5 h-5 text-teal-600 rounded cursor-not-allowed">
        </td>

        <!-- DETALLE (Misma variable de lectura 'puede_ver') -->
        <td class="px-4 py-3 text-center">
            <input type="checkbox" disabled {{ ($permiso && $permiso->puede_ver) ? 'checked' : '' }} class="w-5 h-5 text-teal-600 rounded cursor-not-allowed">
        </td>

        <!-- ALTA / CREAR -->
        <td class="px-4 py-3 text-center">
            <input type="checkbox" disabled {{ ($permiso && $permiso->puede_crear) ? 'checked' : '' }} class="w-5 h-5 text-teal-600 rounded cursor-not-allowed">
        </td>

        <!-- EDITAR -->
        <td class="px-4 py-3 text-center">
            <input type="checkbox" disabled {{ ($permiso && $permiso->puede_editar) ? 'checked' : '' }} class="w-5 h-5 text-teal-600 rounded cursor-not-allowed">
        </td>

        <!-- ELIMINAR -->
        <td class="px-4 py-3 text-center">
            <input type="checkbox" disabled {{ ($permiso && $permiso->puede_eliminar) ? 'checked' : '' }} class="w-5 h-5 text-teal-600 rounded cursor-not-allowed">
        </td>

        @if ($index === 0)
            <td rowspan="{{ $modulos->count() }}" class="px-6 py-4 text-center align-middle border-l border-gray-200 bg-gray-50/50">
                <button type="button" onclick="confirmarEliminacion({{ $rol->id }}, '{{ $rol->nombre }}')" class="inline-flex items-center gap-1 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 font-medium px-3 py-1.5 rounded-lg text-xs transition">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </td>
        @endif
    </tr>
@endforeach
                        @else
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-bold text-gray-900">#{{ $rol->id }} - {{ $rol->nombre }}</td>
                                <td colspan="6" class="px-6 py-4 text-gray-400 italic">No hay módulos registrados en la base de datos.</td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" onclick="confirmarEliminacion({{ $rol->id }}, '{{ $rol->nombre }}')" class="inline-flex items-center gap-1 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 font-medium px-3 py-1.5 rounded-lg text-xs transition">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">No hay roles registrados en el sistema.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!--INCLUIR  MODAL COMPARTIDO DE LA MISMA CARPETA -->
    @include('personal.modalRoles')
</div>

<script>
function confirmarEliminacion(id, nombre) {
    Swal.fire({
        title: '¿Eliminar rol?',
        text: `Estás a punto de borrar el rol "${nombre}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/roles/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Eliminado', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', err.message, 'error'));
        }
    });
}
</script>
@endsection