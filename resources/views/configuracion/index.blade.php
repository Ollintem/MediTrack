@extends('layouts.admin')

@section('content')
<div class="p-6 space-y-6" x-data="{ tab: 'preferencias' }">

    <!-- HEADER DE CONFIGURACIÓN -->
    <div class="bg-gradient-to-r from-teal-800 to-emerald-700 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="px-3 py-1 bg-white/10 text-white rounded-full text-[10px] font-bold tracking-wider uppercase backdrop-blur-md">
                    Administración del Sistema
                </span>
                <h1 class="text-3xl font-black tracking-tight mt-2">Configuración General</h1>
                <p class="text-teal-100 text-xs mt-1">Gestiona los parámetros de la clínica, tiempos de consulta y preferencias globales.</p>
            </div>
            <div class="p-4 bg-white/10 rounded-2xl backdrop-blur-md text-white/90">
                <i class="bi bi-gear-fill text-4xl"></i>
            </div>
        </div>
    </div>

    <!-- PESTAÑAS DE NAVEGACIÓN -->
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 pb-2">
        <button type="button" 
                @click="tab = 'perfil'" 
                :class="tab === 'perfil' ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer">
            <i class="bi bi-hospital-fill"></i>
            <span>Perfil de la Clínica</span>
        </button>

        <button type="button" 
                @click="tab = 'citas'" 
                :class="tab === 'citas' ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer">
            <i class="bi bi-clock-fill"></i>
            <span>Citas y Consultas</span>
        </button>

        <button type="button" 
                @click="tab = 'preferencias'" 
                :class="tab === 'preferencias' ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer">
            <i class="bi bi-shield-fill-check"></i>
            <span>Preferencias y Sistema</span>
        </button>
    </div>

    <!-- MENSAJES DE ALERTA -->
    @if(session('success'))
        <div class="p-4 bg-teal-50 border border-teal-200 text-teal-800 rounded-2xl text-xs font-medium flex items-center gap-2">
            <i class="bi bi-check-circle-fill text-teal-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- FORMULARIO -->
    <form action="{{ route('configuracion.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- PESTAÑA 1: PERFIL -->
        <div x-show="tab === 'perfil'" class="space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                        <i class="bi bi-building text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase">Información General</h3>
                        <p class="text-[11px] text-gray-400">Datos visibles en el sistema y recetas.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nombre de la Clínica</label>
                        <input type="text" name="nombre_clinica" 
                               value="{{ old('nombre_clinica', $config['nombre_clinica'] ?? '') }}"
                               class="uppercase w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Teléfono Principal</label>
                        <input type="text" name="telefono" 
                               value="{{ old('telefono', $config['telefono'] ?? '') }}"
                               class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Dirección</label>
                        <input type="text" name="direccion" 
                               value="{{ old('direccion', $config['direccion'] ?? '') }}"
                               class="uppercase w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: CITAS -->
        <div x-show="tab === 'citas'" class="space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                        <i class="bi bi-calendar-range text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase">Parámetros de Atención</h3>
                        <p class="text-[11px] text-gray-400">Tiempos estándar para consultas.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Duración Cita (Minutos)</label>
                    <input type="number" name="duracion_cita" 
                           value="{{ old('duracion_cita', $config['duracion_cita'] ?? '30') }}"
                           class="w-full max-w-xs px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20">
                </div>
            </div>
        </div>

        <!-- PESTAÑA 3: PREFERENCIAS Y SISTEMA -->
        <div x-show="tab === 'preferencias'" class="space-y-6">
            
            <!-- TARJETA NOTIFICACIONES -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                        <i class="bi bi-bell text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase">Notificaciones e Historial</h3>
                        <p class="text-[11px] text-gray-400">Ajustes del registro de eventos.</p>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-gray-100 flex items-center gap-3">
                    <input type="checkbox" id="alertas_bitacora" name="alertas_bitacora" value="1" 
                           {{ !empty($config['alertas_bitacora']) ? 'checked' : '' }}
                           class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-gray-300">
                    <div>
                        <label for="alertas_bitacora" class="text-xs font-bold text-gray-800 block cursor-pointer">
                            Alertas de Bitácoira del Sistema
                        </label>
                        <p class="text-[11px] text-gray-400">Registrar eventos críticos en la bitácora.</p>
                    </div>
                </div>
            </div>

            <!-- TARJETA AVISO DE PRIVACIDAD -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                        <i class="bi bi-shield-lock-fill text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase">Aviso de Privacidad y Legales</h3>
                        <p class="text-[11px] text-gray-400">Texto que visualizarán los pacientes al registrarse.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Contenido del Aviso de Privacidad</label>
                    <textarea name="aviso_privacidad" rows="6" 
                              class="uppercase w-full px-4 py-3 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20"
                              placeholder="REDACTA AQUÍ EL AVISO DE PRIVACIDAD..."
                              oninput="this.value = this.value.toUpperCase()">{{ old('aviso_privacidad', $config['aviso_privacidad'] ?? '') }}</textarea>
                    <p class="text-[10px] text-gray-400 mt-1">Si dejas este campo vacío, el sistema mostrará el texto legal predeterminado.</p>
                </div>
            </div>

        </div>

        <!-- BOTÓN GUARDAR -->
        <div class="mt-6 flex justify-end">
            <button type="submit" 
                    class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-teal-600/20 transition-all flex items-center gap-2 cursor-pointer">
                <i class="bi bi-floppy-fill text-sm"></i>
                <span>Guardar Cambios</span>
            </button>
        </div>

    </form>
</div>
@endsection