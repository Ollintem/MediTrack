@extends('layouts.guest')

@section('content')
<!-- Animación de Entrada / Splash Screen -->
<div id="splash-screen" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-teal-800 text-white transition-opacity duration-700 opacity-100 pointer-events-none">
    <div class="flex items-center gap-3 animate-pulse">
        <div class="p-3 bg-white/10 rounded-2xl backdrop-blur-md border border-white/20">
            <i class="bi bi-hospital text-4xl text-teal-300"></i>
        </div>
        <h1 class="text-4xl font-extrabold tracking-wider">MediTrack</h1>
    </div>
    <p class="text-xs text-teal-200 mt-2 tracking-widest uppercase">Sistema de Gestión Médica</p>
</div>

<!-- Contenedor Principal de Login -->
<div class="min-h-screen flex items-center justify-center bg-slate-50 p-4 sm:p-6">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden transform transition-all duration-500 scale-95 opacity-0" id="login-card">
        
        <!-- Header con Identidad de Marca -->
        <div class="bg-gradient-to-br from-teal-700 to-teal-900 p-8 text-center text-white relative">
            <div class="inline-flex p-3 bg-white/10 rounded-2xl backdrop-blur-md border border-white/20 mb-3 shadow-inner">
                <i class="bi bi-hospital text-3xl text-teal-300"></i>
            </div>
            <h2 class="text-2xl font-bold">Bienvenido a MediTrack</h2>
            <p class="text-xs text-teal-100 mt-1">Ingresa tus credenciales para acceder al sistema</p>
        </div>

        <!-- Formulario de Acceso -->
        <form method="POST" action="{{ route('login') }}" class="p-8 space-y-5">
            @csrf

            <!-- Manejo Seguro de Errores de Validacion -->
            @if (isset($errors) && $errors->any())
                <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-red-600 text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <p class="flex items-center gap-1.5 font-medium">
                            <i class="bi bi-exclamation-circle-fill"></i> {{ $error }}
                        </p>
                    @endforeach
                </div>
            @endif

            <!-- Campo Correo Electrónico -->
            <div>
                <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Correo Electrónico</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus placeholder="usuario@meditrack.com"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition outline-none">
                </div>
            </div>

            <!-- Campo Contraseña (8 Caracteres) -->
            <div>
                <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Contraseña</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password" id="password" required maxlength="8" placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition outline-none">
                </div>
            </div>

            <!-- Recordar Sesión -->
            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 w-4 h-4">
                    <span>Recordar sesión</span>
                </label>
            </div>

            <!-- Botón de Inicio de Sesión -->
            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-semibold py-3 rounded-xl shadow-md shadow-teal-600/20 transition duration-200 flex items-center justify-center gap-2 text-sm">
                <span>Iniciar Sesión</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">MediTrack &copy; {{ date('Y') }} — Todos los derechos reservados</p>
        </div>
    </div>
</div>

<!-- Script para la Animación de Entrada -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const splash = document.getElementById("splash-screen");
        const card = document.getElementById("login-card");

        setTimeout(() => {
            if (splash) {
                splash.classList.add("opacity-0");
                setTimeout(() => {
                    splash.remove();
                    if (card) {
                        card.classList.remove("scale-95", "opacity-0");
                        card.classList.add("scale-100", "opacity-100");
                    }
                }, 300);
            }
        }, 800);
    });
</script>
@endsection