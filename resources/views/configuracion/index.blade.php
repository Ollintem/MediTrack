@extends('layouts.admin')

@section('content')
<div x-data="{ tab: 'general' }" class="p-6 max-w-7xl mx-auto space-y-6">

    <!-- BANNER DE ENCABEZADO -->
    <div class="relative overflow-hidden bg-gradient-to-r from-teal-800 to-emerald-700 rounded-3xl p-6 text-white shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <span class="bg-teal-600/50 backdrop-blur-md text-teal-100 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                    Administración del Sistema
                </span>
                <h1 class="text-3xl font-black mt-2">Configuración General</h1>
                <p class="text-teal-100/80 text-sm mt-1">
                    Gestiona los parámetros de la clínica, tiempos de consulta y preferencias globales.
                </p>
            </div>
            <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl">
                <i class="bi bi-gear-fill text-4xl text-teal-200"></i>
            </div>
        </div>
    </div>

    <!-- ALERTAS -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl flex items-center gap-3">
        <i class="bi bi-check-circle-fill text-xl"></i>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    <!-- NAVEGACIÓN POR PESTAÑAS (ALPINE.JS) -->
    <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
        <button @click="tab = 'general'" 
                :class="tab === 'general' ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-500 hover:text-teal-600 hover:bg-teal-50'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm transition-all">
            <i class="bi bi-hospital-fill"></i>
            <span>Perfil de la Clínica</span>
        </button>

        <button @click="tab = 'citas'" 
                :class="tab === 'citas' ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-500 hover:text-teal-600 hover:bg-teal-50'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm transition-all">
            <i class="bi bi-clock-fill"></i>
            <span>Citas y Consultas</span>
        </button>

        <button @click="tab = 'seguridad'" 
                :class="tab === 'seguridad' ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-500 hover:text-teal-600 hover:bg-teal-50'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm transition-all">
            <i class="bi bi-shield-lock-fill"></i>
            <span>Preferencias y Sistema</span>
        </button>
    </div>

    <!-- FORMULARIO -->
    <form action="{{ route('configuracion.update') }}" method="POST" class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
        @csrf
        @method('PUT')

        <!-- PESTAÑA 1: GENERAL -->
        <div x-show="tab === 'general'" class="space-y-6">
            <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 flex items-center gap-2">
                <i class="bi bi-building text-teal-600"></i> Datos de la Institución
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Nombre de la Clínica / Empresa</label>
                    <input type="text" name="nombre_clinica" value="{{ old('nombre_clinica', $config['nombre_clinica']) }}" required
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Correo Electrónico de Contacto</label>
                    <input type="email" name="email" value="{{ old('email', $config['email']) }}" required
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Teléfono Principal</label>
                    <input type="text" name="telefono" value="{{ old('telefono', $config['telefono']) }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Dirección Física</label>
                    <input type="text" name="direccion" value="{{ old('direccion', $config['direccion']) }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: CITAS -->
        <div x-show="tab === 'citas'" class="space-y-6" x-cloak>
            <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 flex items-center gap-2">
                <i class="bi bi-calendar-check text-teal-600"></i> Parámetros de Atención Médica
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Duración predeterminada por Consulta</label>
                    <select name="duracion_cita" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                        <option value="15" {{ $config['duracion_cita'] == '15' ? 'selected' : '' }}>15 Minutos</option>
                        <option value="30" {{ $config['duracion_cita'] == '30' ? 'selected' : '' }}>30 Minutos</option>
                        <option value="45" {{ $config['duracion_cita'] == '45' ? 'selected' : '' }}>45 Minutos</option>
                        <option value="60" {{ $config['duracion_cita'] == '60' ? 'selected' : '' }}>60 Minutos (1 Hora)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Moneda del Sistema</label>
                    <input type="text" value="{{ $config['moneda'] }}" disabled
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-500 text-sm cursor-not-allowed">
                </div>
            </div>
        </div>

        <!-- PESTAÑA 3: SEGURIDAD -->
        <div x-show="tab === 'seguridad'" class="space-y-6" x-cloak>
            <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 flex items-center gap-2">
                <i class="bi bi-bell text-teal-600"></i> Notificaciones e Historial
            </h2>

            <div class="space-y-4">
                <label class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100 cursor-pointer">
                    <input type="checkbox" checked class="w-5 h-5 text-teal-600 rounded focus:ring-teal-500 border-gray-300">
                    <div>
                        <p class="text-sm font-bold text-gray-800">Alertas de Bitácora del Sistema</p>
                        <p class="text-xs text-gray-500">Registrar eventos críticos y modificaciones de roles en la bitácora.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- BOTÓN DE GUARDAR -->
        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
            <button type="submit" class="flex items-center gap-2 px-6 py-3 rounded-xl bg-teal-600 text-white font-bold text-sm shadow-lg shadow-teal-600/30 hover:bg-teal-700 transition-all">
                <i class="bi bi-floppy-fill"></i>
                <span>Guardar Cambios</span>
            </button>
        </div>
    </form>
</div>
@endsection