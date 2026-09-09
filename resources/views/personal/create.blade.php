@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{ openModal: false }">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Registrar Nuevo Personal</h2>
            <p class="text-sm text-gray-500">Completa los datos para dar de alta un miembro del personal médico.</p>
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
        <form action="{{ route('personal.store') }}" method="POST" class="space-y-6">
            @csrf

            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                <i class="bi bi-person-badge"></i> Datos Personales y de Acceso
            </h3>

            <!-- Nombre y Apellido -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre(s)</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Ej. Andres" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Apellido(s)</label>
                    <input type="text" name="apellido" value="{{ old('apellido') }}" required placeholder="Ej. Morales" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>

            <!-- Usuario de Correo con sufijo @meditrack.com -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Usuario de Acceso (Correo)</label>
                <div class="flex rounded-lg shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="text" name="username" value="{{ old('username') }}" required placeholder="amorales" class="flex-1 min-w-0 w-full border border-gray-300 p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 text-gray-600 font-medium text-sm">
                        @meditrack.com
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-1">El correo se generará automáticamente como <strong>usuario@meditrack.com</strong>.</p>
            </div>

            <!-- Contraseña de 8 caracteres exactos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña (8 caracteres)</label>
                    <input type="password" name="password" required maxlength="8" minlength="8" placeholder="••••••••" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Debe contener exactamente 8 caracteres.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" required maxlength="8" minlength="8" placeholder="••••••••" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>

            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider pt-4 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                <i class="bi bi-briefcase"></i> Información Profesional y Turno
            </h3>

            <!-- Rol / Especialidad con Dropdown + Botón Modal con Alpine -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
    <div class="flex items-center justify-between mb-2">
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
            Cargo / Rol Profesional *
        </label>
        <a href="{{ route('roles.index') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1">
            <i class="bi bi-gear"></i> Gestionar Roles
        </a>
    </div>
    <select name="rol_id" required class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
        <option value="" disabled selected>Selecciona un rol...</option>
        @foreach($roles as $rol)
            <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
        @endforeach
    </select>
</div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Turno Asignado</label>
                    <select name="turno" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                        <option value="Mañana" {{ old('turno') == 'Mañana' ? 'selected' : '' }}>Mañana</option>
                        <option value="Tarde" {{ old('turno') == 'Tarde' ? 'selected' : '' }}>Tarde</option>
                        <option value="Noche" {{ old('turno') == 'Noche' ? 'selected' : '' }}>Noche</option>
                        <option value="Completo" {{ old('turno', 'Completo') == 'Completo' ? 'selected' : '' }}>Completo</option>
                    </select>
                </div>
            </div>

            <!-- Teléfono y RUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono de Contacto</label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}" placeholder="5512345678" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">RUT / Cédula <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <input type="text" name="rut" value="{{ old('rut') }}" placeholder="12345678-9" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('personal.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50 font-medium">
                    Cancelar
                </a>
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm shadow-sm transition flex items-center gap-2">
                    <i class="bi bi-person-check"></i> Registrar Personal
                </button>
            </div>
        </form>
    </div>

    <!-- Incluir el modal compartido con el diseño y la matriz requerida -->
    @include('personal.modalRoles')
    </div>
    @endsection