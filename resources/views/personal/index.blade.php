@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Directorio de Personal Médico</h2>
            <p class="text-sm text-gray-500">Gestiona la información del personal y sus credenciales de acceso.</p>
        </div>
        <a href="{{ route('personal.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition flex items-center gap-2">
            <i class="bi bi-person-plus"></i> Registrar Personal
        </a>
    </div>

    <!-- Alerta de éxito -->
    @if (session('success'))
        <div class="p-4 mb-4 text-sm text-teal-800 bg-teal-100 rounded-lg border border-teal-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-semibold">ID</th>
                    <th class="px-6 py-3 font-semibold">Nombre Completo</th>
                    <th class="px-6 py-3 font-semibold">Correo</th>
                    <th class="px-6 py-3 font-semibold">Rol Profesional</th>
                    <th class="px-6 py-3 font-semibold">Turno</th>
                    <th class="px-6 py-3 font-semibold">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                @forelse ($personal as $persona)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-mono text-gray-400">#{{ $persona->id }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $persona->nombre_completo }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $persona->correo }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $persona->rol_profesional ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-1 rounded-md">
                                Turno {{ $persona->turno }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($persona->estado === 'Activo')
                                <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-emerald-200">
                                    Activo
                                </span>
                            @else
                                <span class="bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-red-200">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No hay personal registrado en el sistema.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection