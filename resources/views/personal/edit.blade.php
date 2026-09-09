@extends('layouts.admin')

@section('content')
<!-- Se añade x-data al contenedor principal -->
<div class="max-w-3xl mx-auto space-y-6" x-data="{ openModal: false }">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Editar Información de Personal</h2>
            <p class="text-sm text-gray-500">Modifica las credenciales y la información laboral del usuario.</p>
        </div>
        <a href="{{ route('personal.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium flex items-center gap-1">
            <i class="bi bi-arrow-left"></i> Volver al directorio
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8">
        <form action="{{ route('personal.update', $personal->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                <i class="bi bi-key"></i> DATOS DE ACCESO
            </h3>

            <!-- Nombre Completo -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre Completo</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" name="nombre_completo" value="{{ old('nombre_completo', $personal->nombre_completo) }}" required class="w-full pl-10 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>

            <!-- Usuario de Correo -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Usuario de Acceso (Correo)</label>
                @php
                    $emailActual = $personal->usuario->email ?? '';
                    $usernameActual = Str::before($emailActual, '@');
                @endphp
                <div class="flex rounded-lg shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="text" name="username" value="{{ old('username', $usernameActual) }}" required class="flex-1 min-w-0 w-full border border-gray-300 p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 text-gray-600 font-medium text-sm">
                        @meditrack.com
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Modifica el usuario solo si es strictly necesario.</p>
            </div>

            <!-- Contraseñas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nueva Contraseña <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <input type="password" name="password" maxlength="8" minlength="8" placeholder="Exactamente 8 caracteres" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmation" maxlength="8" minlength="8" placeholder="Repite la contraseña" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>
            <p class="text-xs text-gray-400">Déjalo en blanco si no deseas cambiarla. Debe ser de 8 caracteres.</p>

            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider pt-4 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                <i class="bi bi-briefcase"></i> INFORMACIÓN PROFESIONAL
            </h3>

           <!-- Rol / Especialidad -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-semibold text-gray-700">Cargo / Rol Profesional</label>
                        <button type="button" @click="openModal = true" class="text-xs text-teal-600 hover:text-teal-700 font-semibold flex items-center gap-1 focus:outline-none">
                            <i class="bi bi-gear"></i> Gestionar Roles
                        </button>
                    </div>
                    <select name="rol_id" id="select_rol_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                        <option value="">Selecciona un rol...</option>
                        @if(isset($roles))
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}" 
                                    {{ old('rol_id', $personal->usuario->rol_id ?? '') == $rol->id ? 'selected' : '' }}>
                                    {{ $rol->nombre }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono', $personal->telefono) }}" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
    </div>
</div>

            <!-- Botones -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('personal.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50 font-medium">
                    Cancelar
                </a>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-6 py-2.5 rounded-lg text-sm shadow-sm transition flex items-center gap-2">
                    <i class="bi bi-pencil-square"></i> Actualizar Personal
                </button>
            </div>
        </form>
    </div>

    <!-- Incluir el modal dentro del ámbito de x-data -->
    @include('personal.modalRoles')
</div>
@endsection