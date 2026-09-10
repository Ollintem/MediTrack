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

@php
    $emailActual = $personal->usuario->email ?? '';
    $usernameActual = Str::before($emailActual, '@');
    $tieneUsuario = !empty($personal->user_id) || !empty($personal->usuario);
@endphp

<!-- Contenedor con Alpine.js -->
<div class="space-y-8 max-w-5xl mx-auto pb-12" x-data="{ 
    openModal: false,
    requiereAcceso: {{ old('requiere_acceso', $tieneUsuario ? 'true' : 'false') }}
}">
    
    <!-- BANNER HERO PREMIUM -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger" style="animation-delay: 0ms;">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-pencil-square text-amber-300"></i> Edición de Perfil
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Editar Información de Personal</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Modifica las credenciales y la información laboral del usuario.
                </p>
            </div>

            <a href="{{ route('personal.index') }}" class="bg-white/10 hover:bg-white/20 text-white font-bold px-5 py-3 rounded-2xl text-sm border border-white/20 backdrop-blur-md shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                <i class="bi bi-arrow-left"></i>
                <span>Volver al directorio</span>
            </a>
        </div>
    </div>

    <!-- ERRORES DE VALIDACIÓN -->
    @if ($errors->any())
        <div class="p-5 bg-rose-50 rounded-3xl border border-rose-200 text-rose-800 space-y-2 animate-stagger" style="animation-delay: 100ms;">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="bi bi-exclamation-octagon-fill text-rose-500 text-lg"></i>
                <span>Por favor corrige los siguientes errores:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-1 text-rose-700 font-medium pl-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- FORMULARIO DE EDICIÓN -->
    <form action="{{ route('personal.update', $personal->id) }}" method="POST" class="space-y-8 animate-stagger" style="animation-delay: 150ms;">
        @csrf
        @method('PUT')

        <!-- SECCIÓN 1: DATOS DE ACCESO -->
        <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-slate-50 via-teal-50/20 to-emerald-50/10 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-teal-600/20">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-gray-800 text-base">Datos de Acceso</h3>
                        <p class="text-xs text-gray-500">Credenciales del sistema e identificación de usuario</p>
                    </div>
                </div>

                <!-- SWITCH REQUIERE ACCESO AL SISTEMA -->
                <div class="flex items-center gap-3 bg-white/80 border border-gray-200 px-4 py-2 rounded-2xl shadow-2xs">
                    <input type="hidden" name="requiere_acceso" :value="requiereAcceso ? 1 : 0">
                    <label for="switchAccesoEdit" class="flex items-center cursor-pointer gap-3 select-none">
                        <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                            <i class="bi bi-shield-lock-fill" :class="requiereAcceso ? 'text-teal-600' : 'text-gray-400'"></i>
                            Requiere acceder a sistema
                        </span>
                        <div class="relative">
                            <input type="checkbox" id="switchAccesoEdit" x-model="requiereAcceso" class="sr-only">
                            <div class="block w-11 h-6 rounded-full transition-colors duration-300" :class="requiereAcceso ? 'bg-teal-600' : 'bg-gray-300'"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300" :class="requiereAcceso ? 'transform translate-x-5' : ''"></div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="p-8 space-y-6">
                <!-- Nombre Completo -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        Nombre Completo *
                    </label>
                    <div class="flex rounded-2xl border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-teal-500 focus-within:border-teal-500 transition-all">
                        <span class="inline-flex items-center px-4 bg-gray-50 text-gray-400 border-r border-gray-200">
                            <i class="bi bi-person text-base"></i>
                        </span>
                        <input type="text" name="nombre_completo" value="{{ old('nombre_completo', $personal->nombre_completo) }}" required class="w-full p-3.5 text-sm focus:outline-none border-0 font-semibold text-gray-800">
                    </div>
                </div>

                <!-- BLOQUE CREDENCIALES DE ACCESO (DESPLEGABLE SEGÚN SWITCH) -->
                <div x-show="requiereAcceso" x-collapse x-cloak class="space-y-6 pt-4 border-t border-gray-100">
                    <!-- Usuario de Correo -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Usuario de Acceso (Correo) *
                        </label>
                        <div class="flex rounded-2xl border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-teal-500 focus-within:border-teal-500 transition-all">
                            <span class="inline-flex items-center px-4 bg-gray-50 text-gray-400 border-r border-gray-200">
                                <i class="bi bi-envelope text-base"></i>
                            </span>
                            <input type="text" name="username" value="{{ old('username', $usernameActual) }}" :required="requiereAcceso" :disabled="!requiereAcceso" class="w-full p-3.5 text-sm focus:outline-none border-0">
                            <span class="inline-flex items-center px-4 bg-gray-100 text-gray-600 font-semibold text-xs border-l border-gray-200">
                                @meditrack.com
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5 font-medium flex items-center gap-1">
                            <i class="bi bi-info-circle"></i> Modifica el usuario solo si es estrictamente necesario.
                        </p>
                    </div>

                    <!-- Contraseñas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                Nueva Contraseña <span class="text-gray-400 font-normal lowercase">(Opcional)</span>
                            </label>
                            <input type="password" name="password" :disabled="!requiereAcceso" maxlength="8" minlength="8" placeholder="Exactamente 8 caracteres" class="w-full border border-gray-300 rounded-2xl p-3.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
                            <p class="text-[11px] text-gray-400 mt-1.5 font-medium">Déjalo en blanco si no deseas cambiarla. Debe ser de 8 caracteres.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                Confirmar Nueva Contraseña
                            </label>
                            <input type="password" name="password_confirmation" :disabled="!requiereAcceso" maxlength="8" minlength="8" placeholder="Repite la contraseña" class="w-full border border-gray-300 rounded-2xl p-3.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
                        </div>
                    </div>
                </div>

                <!-- NOTA CUANDO NO REQUIERE ACCESO -->
                <div x-show="!requiereAcceso" x-cloak class="p-4 bg-amber-50/60 border border-amber-200/80 rounded-2xl text-amber-800 text-xs flex items-center gap-3">
                    <i class="bi bi-info-circle-fill text-amber-600 text-lg flex-shrink-0"></i>
                    <span>Este personal está registrado sin cuenta activa. <strong>No puede iniciar sesión</strong> a menos que actives esta opción y asignes credenciales.</span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: INFORMACIÓN PROFESIONAL -->
        <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-slate-50 via-teal-50/20 to-emerald-50/10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-teal-600/20">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-gray-800 text-base">Información Profesional</h3>
                    <p class="text-xs text-gray-500">Asignación de rol de seguridad y teléfono</p>
                </div>
            </div>

            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cargo / Rol Profesional -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Cargo / Rol Profesional *</label>
                            @if(auth()->user()->tienePermiso('Roles', 'crear'))
                                <button type="button" @click="openModal = true" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1 focus:outline-none transition-colors">
                                    <i class="bi bi-gear-fill"></i> Gestionar Roles
                                </button>
                            @endif
                        </div>
                        
                        @php
                            $rolActualId = old('rol_id');
                            if (!$rolActualId) {
                                if (isset($personal->usuario->rol_id)) {
                                    $rolActualId = $personal->usuario->rol_id;
                                } elseif (isset($roles)) {
                                    $rolMatch = $roles->firstWhere('nombre', $personal->especialidad_principal);
                                    $rolActualId = $rolMatch ? $rolMatch->id : '';
                                }
                            }
                        @endphp
                        <select name="rol_id" id="selectRolId" required class="w-full border border-gray-300 rounded-2xl p-3.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all bg-white">
                            <option value="" disabled {{ empty($rolActualId) ? 'selected' : '' }}>Selecciona un rol...</option>
                            @if(isset($roles))
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->id }}" {{ $rolActualId == $rol->id ? 'selected' : '' }}>
                                        {{ $rol->nombre }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $personal->telefono) }}" placeholder="Ej. 5553209464" class="w-full border border-gray-300 rounded-2xl p-3.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTONES DE ACCIÓN (FOOTER) -->
        <div class="flex items-center justify-end gap-4 pt-4">
            <a href="{{ route('personal.index') }}" class="px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-sm font-semibold transition-all">
                Cancelar
            </a>
            <button type="submit" class="px-8 py-3.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2 transform active:scale-95">
                <i class="bi bi-pencil-square"></i>
                <span>Actualizar Personal</span>
            </button>
        </div>
    </form>

    <!-- Modal de Roles -->
    @include('personal.modalRoles')
</div>
@endsection