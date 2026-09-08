@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Directorio de Personal</h2>
            <p class="text-sm text-gray-500">Gestión de información del personal.</p>
        </div>
        
        <!-- Agrupación de botones -->
        <div class="flex items-center gap-3">
            <!-- Botón Gestionar Roles -->
    <a href="{{ route('roles.index') }}" class="bg-white hover:bg-gray-50 text-teal-700 font-semibold px-4 py-2 rounded-lg text-sm border border-teal-200 shadow-sm transition flex items-center gap-2">
        <i class="bi bi-gear text-teal-600"></i> Gestionar Roles
    </a>

            <!-- Botón Registrar Personal -->
            <a href="{{ route('personal.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition flex items-center gap-2">
                <i class="bi bi-person-plus"></i> Registrar Personal
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 mb-4 text-sm text-teal-800 bg-teal-100 rounded-lg border border-teal-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-semibold">ID</th>
                    <th class="px-6 py-3 font-semibold">Nombre Completo</th>
                    <th class="px-6 py-3 font-semibold">Correo</th>
                    <th class="px-6 py-3 font-semibold">Cargo / Especialidad</th>
                    <th class="px-6 py-3 font-semibold">Teléfono</th>
                    <th class="px-6 py-3 font-semibold text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                @forelse ($personal as $persona)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-mono text-gray-400">#{{ $persona->id }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $persona->nombre_completo }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $persona->usuario->email ?? 'Sin correo' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-md border border-blue-100">
                                {{ $persona->especialidad_principal ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $persona->telefono ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
    <!-- Botón Editar -->
    <a href="{{ route('personal.edit', $persona->id) }}" class="inline-flex items-center gap-1 bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-100 font-medium px-3 py-1.5 rounded-lg text-xs transition">
        <i class="bi bi-pencil-square"></i> Editar
    </a>

    <!-- Formulario con clase "contents" -->
    <form id="form-delete-{{ $persona->id }}" action="{{ route('personal.destroy', $persona->id) }}" method="POST" class="contents">
        @csrf
        @method('DELETE')
        <button type="button" 
            onclick="eliminarPersonal({{ $persona->id }}, {{ json_encode($persona->nombre_completo) }})" 
            class="inline-flex items-center gap-1 bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 font-medium px-3 py-1.5 rounded-lg text-xs transition" 
            title="Eliminar personal">
        <i class="bi bi-trash"></i> Eliminar
</button>
    </form>
</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay personal registrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

<!-- boton SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function eliminarPersonal(id, nombre) {
    Swal.fire({
        title: '¿Eliminar personal?',
        text: `Estás a punto de borrar a "${nombre}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d9488', // 
        cancelButtonColor: '#ef4444', //
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