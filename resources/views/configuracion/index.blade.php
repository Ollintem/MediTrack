@extends('layouts.admin')

@section('content')
<div class="p-6 space-y-6" x-data="{ tab: 'perfil' }">

    <!-- HEADER DE CONFIGURACIÓN -->
    <div class="bg-gradient-to-r from-teal-800 to-emerald-700 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="px-3 py-1 bg-white/10 text-white rounded-full text-[10px] font-bold tracking-wider uppercase backdrop-blur-md">
                    Administración del Sistema
                </span>
                <h1 class="text-3xl font-black tracking-tight mt-2">Configuración General</h1>
                <p class="text-teal-100 text-xs mt-1">Gestiona los parámetros de la clínica, preferencias globales y seguridad de tu cuenta.</p>
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
                @click="tab = 'preferencias'" 
                :class="tab === 'preferencias' ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer">
            <i class="bi bi-shield-fill-check"></i>
            <span>Preferencias y Sistema</span>
        </button>

        <button type="button" 
                @click="tab = 'cuenta'" 
                :class="tab === 'cuenta' ? 'bg-teal-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer">
            <i class="bi bi-person-fill-lock"></i>
            <span>Seguridad de la Cuenta</span>
        </button>
    </div>

    <!-- MENSAJES DE ALERTA -->
    @if(session('success'))
        <div class="p-4 bg-teal-50 border border-teal-200 text-teal-800 rounded-2xl text-xs font-medium flex items-center gap-2">
            <i class="bi bi-check-circle-fill text-teal-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-xs font-medium space-y-1">
            <div class="flex items-center gap-2 font-bold">
                <i class="bi bi-exclamation-triangle-fill text-red-600 text-base"></i>
                <span>Por favor corrige los siguientes errores:</span>
            </div>
            <ul class="list-disc pl-6 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- FORMULARIO GENERAL (PERFIL DE LA CLÍNICA Y PREFERENCIAS) -->
    <form action="{{ route('configuracion.update') }}" method="POST" x-show="tab === 'perfil' || tab === 'preferencias'">
        @csrf
        @method('PUT')

        <!-- PESTAÑA 1: PERFIL DE LA CLÍNICA -->
        <div x-show="tab === 'perfil'" class="space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                        <i class="bi bi-building text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase">Información General de la Clínica</h3>
                        <p class="text-[11px] text-gray-400">Datos registrados en la base de datos para comprobantes y recetas.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nombre de la Clínica -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nombre de la Clínica</label>
                        <input type="text" name="nombre" 
                               value="{{ old('nombre', $clinica->nombre ?? '') }}"
                               class="uppercase w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- RUT / Identificación Empresa -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">RUT / Identificación de la Empresa</label>
                        <input type="text" name="rut_empresa" 
                               value="{{ old('rut_empresa', $clinica->rut_empresa ?? '') }}"
                               class="uppercase w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <!-- Teléfono Principal -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Teléfono Principal (10 dígitos)</label>
                        <input type="text" name="telefono" 
                               value="{{ old('telefono', $clinica->telefono ?? '') }}"
                               maxlength="10"
                               class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20">
                    </div>

                    <!-- Correo Electrónico de la Clínica -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Correo Electrónico</label>
                        <input type="email" name="email" 
                               value="{{ old('email', $config['email'] ?? ($clinica->email ?? '')) }}"
                               class="w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20">
                    </div>

                    <!-- Dirección -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Dirección Física</label>
                        <input type="text" name="direccion" 
                               value="{{ old('direccion', $clinica->direccion ?? '') }}"
                               class="uppercase w-full px-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20"
                               oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: PREFERENCIAS Y SISTEMA -->
        <div x-show="tab === 'preferencias'" class="space-y-6">
            
            <!-- TARJETA NOTIFICACIONES Y FINANZAS -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                        <i class="bi bi-bell text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase">Notificaciones e Historial</h3>
                        <p class="text-[11px] text-gray-400">Ajustes del registro de eventos e historial de pagos.</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <!-- Checkbox Recordatorios de Pago por Email -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-gray-100 flex items-center gap-3">
                        <input type="hidden" name="recordatorio_pago_email_submitted" value="1">
                        <input type="checkbox" id="recordatorio_pago_email" name="recordatorio_pago_email" value="1" 
                               {{ old('recordatorio_pago_email', $clinica->recordatorio_pago_email ?? 0) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-gray-300">
                        <div>
                            <label for="recordatorio_pago_email" class="text-xs font-bold text-gray-800 block cursor-pointer uppercase">
                                Recordatorios de Pago por Correo Electrónico
                            </label>
                            <p class="text-[11px] text-gray-400">Enviar notificaciones de estado de cuenta e historial de facturación a pacientes.</p>
                        </div>
                    </div>

                    <!-- Checkbox Alertas de Bitácora -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-gray-100 flex items-center gap-3">
                        <input type="checkbox" id="alertas_bitacora" name="alertas_bitacora" value="1" 
                               {{ !empty($config['alertas_bitacora']) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-gray-300">
                        <div>
                            <label for="alertas_bitacora" class="text-xs font-bold text-gray-800 block cursor-pointer uppercase">
                                Alertas de Bitácora del Sistema
                            </label>
                            <p class="text-[11px] text-gray-400">Registrar eventos críticos y modificaciones de datos en la bitácora auditora.</p>
                        </div>
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

        <!-- BOTÓN GUARDAR CONFIGURACIÓN DE LA CLÍNICA -->
        <div class="mt-6 flex justify-end">
            <button type="submit" 
                    class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-teal-600/20 transition-all flex items-center gap-2 cursor-pointer">
                <i class="bi bi-floppy-fill text-sm"></i>
                <span>Guardar Cambios</span>
            </button>
        </div>
    </form>

    <!-- PESTAÑA 3: SEGURIDAD DE LA CUENTA (ADMINISTRADOR) -->
    <div x-show="tab === 'cuenta'" class="space-y-6" x-cloak>
        <form action="{{ route('configuracion.cuenta.update') }}" method="POST">
            @csrf
            
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-6">
                <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
                    <div class="p-2 bg-teal-50 text-teal-600 rounded-xl">
                        <i class="bi bi-shield-lock-fill text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase">Credenciales y Acceso de Usuario</h3>
                        <p class="text-[11px] text-gray-400">Puedes cambiar solo tu correo, solo tu contraseña o ambos.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Correo Electrónico de Acceso -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                            Correo Electrónico de Acceso <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="bi bi-envelope-fill absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="email" 
                                   name="email" 
                                   value="{{ old('email', auth()->user()->email) }}" 
                                   placeholder="admin@meditrack.com" 
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20 font-semibold">
                        </div>
                    </div>

                    <!-- Contraseña Actual (Confirmación opcional / requerida solo si hay cambios) -->
                    <div class="md:col-span-2 p-4 bg-teal-50/50 rounded-2xl border border-teal-100/80 space-y-1">
                        <label class="block text-xs font-extrabold text-teal-900 uppercase mb-1">
                            Contraseña Actual
                        </label>
                        <div class="relative">
                            <i class="bi bi-key-fill absolute left-3.5 top-1/2 -translate-y-1/2 text-teal-600 text-sm"></i>
                            <input type="password" 
                                   name="current_password" 
                                   placeholder="Ingresa tu contraseña actual solo para autorizar cambios" 
                                   class="w-full pl-10 pr-4 py-2.5 bg-white rounded-xl border border-teal-200 text-xs text-gray-800 outline-none focus:ring-2 focus:ring-teal-500 font-semibold">
                        </div>
                        <p class="text-[10px] text-teal-700/80 font-medium pt-0.5">
                            <i class="bi bi-info-circle-fill"></i> Se te solicitará para confirmar la actualización de credenciales.
                        </p>
                    </div>

                    <!-- Nueva Contraseña (Opcional) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                            Nueva Contraseña <span class="text-gray-400 font-normal">(Opcional)</span>
                        </label>
                        <div class="relative">
                            <i class="bi bi-lock-fill absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="password" 
                                   name="new_password" 
                                   placeholder="Dejar en blanco si no deseas cambiarla" 
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20 font-semibold">
                        </div>
                    </div>

                    <!-- Confirmar Nueva Contraseña -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">
                            Confirmar Nueva Contraseña
                        </label>
                        <div class="relative">
                            <i class="bi bi-shield-check absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="password" 
                                   name="new_password_confirmation" 
                                   placeholder="Repite la nueva contraseña" 
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 rounded-xl border border-gray-200 text-xs text-gray-700 outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20 font-semibold">
                        </div>
                    </div>
                </div>

                <!-- BOTÓN GUARDAR CAMBIOS DE CUENTA -->
                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-teal-600/20 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-check-circle-fill text-sm"></i>
                        <span>Actualizar Credenciales</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection