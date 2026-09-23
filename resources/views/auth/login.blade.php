@extends('layouts.guest')

@section('content')
<!-- Estilos y Animaciones Custom -->
<style>
    @keyframes kenBurns {
        0% { transform: scale(1); }
        50% { transform: scale(1.08); }
        100% { transform: scale(1); }
    }
    @keyframes floatIn {
        0% { opacity: 0; transform: translateY(30px) scale(0.96); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes itemCascade {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-8px); }
        40%, 80% { transform: translateX(8px); }
    }

    .animate-bg-kenburns {
        animation: kenBurns 22s ease-in-out infinite alternate;
    }
    .animate-float-in {
        animation: floatIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .animate-cascade {
        opacity: 0;
        animation: itemCascade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .animate-shake {
        animation: shakeError 0.4s ease-in-out;
    }

    .delay-1 { animation-delay: 100ms; }
    .delay-2 { animation-delay: 200ms; }
    .delay-3 { animation-delay: 300ms; }
    .delay-4 { animation-delay: 400ms; }
    .delay-5 { animation-delay: 550ms; }
</style>

<!-- Splash Screen -->
<div id="splash-screen" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-teal-800 text-white transition-opacity duration-700 opacity-100 pointer-events-none">
    <div class="flex items-center gap-3 animate-pulse">
        <div class="p-3 bg-white/10 rounded-2xl backdrop-blur-md border border-white/20 shadow-lg">
            <i class="bi bi-hospital text-4xl text-teal-300"></i>
        </div>
        <h1 class="text-3xl font-extrabold tracking-wider text-white">MediTrack</h1>
    </div>
    <p class="text-xs text-teal-200 mt-2 tracking-widest uppercase font-semibold">Sistema de Gestión Médica</p>
</div>

<!-- Contenedor Principal -->
<div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 lg:p-10 relative overflow-hidden font-sans">
    
    <!-- Fondo de Pantalla Completa -->
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat animate-bg-kenburns"
         style="background-image: url('https://images.unsplash.com/photo-1629909613654-28e377c37b09?q=80&w=1920&auto=format&fit=crop');">
    </div>
    
    <div class="absolute inset-0 bg-slate-900/10"></div>

    <!-- TARJETA PRINCIPAL SPLIT -->
    <div class="w-full max-w-5xl bg-white/45 backdrop-blur-md rounded-[32px] border border-white/60 shadow-2xl shadow-slate-900/20 overflow-hidden grid grid-cols-1 md:grid-cols-12 relative z-10 animate-float-in" id="login-card">
        
        <!-- COLUMNA IZQUIERDA: Fondo emparejado en blanco translúcido con Reloj y Calendario Libre -->
        <div class="md:col-span-6 min-h-[380px] md:min-h-[580px] relative overflow-hidden flex flex-col items-center justify-center p-6 bg-white/30 backdrop-blur-md border-b md:border-b-0 md:border-r border-white/40 group">
            
            <!-- Card de Calendario Completo y Reloj (Integrado armónicamente) -->
            <div class="relative z-10 bg-white/60 backdrop-blur-md border border-white/80 rounded-3xl p-6 shadow-xl w-full max-w-sm transform group-hover:scale-[1.01] transition duration-300">
                
                <!-- Reloj Digital Dinámico -->
                <div class="text-center pb-4 mb-4 border-b border-slate-900/10">
                    <div id="live-time" class="text-4xl font-black tracking-tight text-teal-700 drop-shadow-sm font-mono">00:00:00</div>
                    <span id="live-day-name" class="text-[11px] font-extrabold uppercase tracking-widest text-slate-700">Cargando...</span>
                </div>

                <!-- Encabezado del Mes y Año -->
                <div class="flex items-center justify-between pb-3 mb-2">
                    <div class="flex items-center gap-2 text-teal-800 font-extrabold text-sm uppercase tracking-wide">
                        <i class="bi bi-calendar3 text-base"></i>
                        <span id="cal-month-year">-- ----</span>
                    </div>
                </div>

                <!-- Cabecera de Días de la Semana -->
                <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black uppercase text-slate-700 mb-2">
                    <div>Dom</div><div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div>
                </div>

                <!-- Cuadrícula de Todos los Días del Mes (Generado con JS) -->
                <div id="cal-days-grid" class="grid grid-cols-7 gap-1 text-center text-xs font-bold text-slate-800">
                </div>
            </div>

            <!-- Badge Superior -->
            <div class="absolute top-4 left-4 z-10 inline-flex items-center gap-2 px-3 py-1 bg-white/60 backdrop-blur-md rounded-full border border-white/70 text-[11px] font-bold text-slate-900 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Hora y Calendario del Sistema</span>
            </div>
        </div>

        <!-- COLUMNA DERECHA: Formulario -->
        <div class="md:col-span-6 p-8 sm:p-12 lg:p-14 bg-white/30 backdrop-blur-md flex flex-col justify-between">
            
            <div class="text-center animate-cascade delay-1">
                <div class="inline-flex items-center justify-center p-3 bg-teal-600/15 rounded-2xl border border-teal-500/30 mb-3 text-teal-700 shadow-sm backdrop-blur-sm">
                    <i class="bi bi-hospital text-3xl"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight drop-shadow-sm">Bienvenido de nuevo</h2>
                <p class="text-xs text-slate-700 mt-1 font-semibold">Ingresa a tu cuenta de MediTrack</p>
            </div>

            @if (isset($errors) && $errors->any())
                <div class="my-4 p-3.5 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-900 text-xs space-y-1 animate-shake backdrop-blur-sm">
                    @foreach ($errors->all() as $error)
                        <p class="flex items-center gap-2 font-bold">
                            <i class="bi bi-exclamation-circle-fill text-sm shrink-0 text-red-700"></i>
                            <span>{{ $error }}</span>
                        </p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4 my-auto pt-4">
                @csrf

                <div class="animate-cascade delay-2">
                    <label for="email" class="block text-[11px] font-extrabold text-slate-800 uppercase tracking-wider mb-1.5 drop-shadow-sm">Correo Electrónico / Usuario</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 group-focus-within:text-teal-700 transition">
                            <i class="bi bi-envelope text-sm"></i>
                        </span>
                        <input type="text" name="email" id="email" value="{{ old('email') }}" required autofocus placeholder="admin@meditrack.com"
                            class="w-full pl-10 pr-4 py-3 bg-white/50 border border-white/80 rounded-xl text-sm text-slate-900 placeholder-slate-500 font-medium focus:bg-white/80 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition duration-200 outline-none shadow-sm backdrop-blur-sm">
                    </div>
                </div>

                <div class="animate-cascade delay-3">
                    <label for="password" class="block text-[11px] font-extrabold text-slate-800 uppercase tracking-wider mb-1.5 drop-shadow-sm">Contraseña</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 group-focus-within:text-teal-700 transition">
                            <i class="bi bi-lock text-sm"></i>
                        </span>
                        <input type="password" name="password" id="password" required maxlength="30" placeholder="••••••••"
                            class="w-full pl-10 pr-11 py-3 bg-white/50 border border-white/80 rounded-xl text-sm text-slate-900 placeholder-slate-500 font-medium focus:bg-white/80 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition duration-200 outline-none shadow-sm backdrop-blur-sm">
                        
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-800 transition focus:outline-none">
                            <i id="password-icon" class="bi bi-eye text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1 animate-cascade delay-4">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-800 font-bold hover:text-slate-950 transition drop-shadow-sm">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 w-4 h-4">
                        <span>Recordar sesión</span>
                    </label>
                </div>

                <div class="animate-cascade delay-5 pt-2">
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 active:scale-[0.98] text-white font-bold py-3.5 rounded-full shadow-lg shadow-teal-600/30 hover:shadow-teal-600/40 transition duration-200 flex items-center justify-center gap-2 text-sm group">
                        <span>Acceder al Sistema</span>
                        <i class="bi bi-arrow-right text-base group-hover:translate-x-1.5 transition-transform duration-200"></i>
                    </button>
                </div>
            </form>

            <div class="text-center pt-6 border-t border-slate-900/10">
                <p class="text-[11px] text-slate-700 font-bold drop-shadow-sm">MediTrack System &copy; {{ date('Y') }} — Todos los derechos reservados</p>
            </div>

        </div>

    </div>
</div>

<!-- Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Splash Screen
        const splash = document.getElementById("splash-screen");
        setTimeout(() => {
            if (splash) {
                splash.classList.add("opacity-0");
                setTimeout(() => splash.remove(), 300);
            }
        }, 600);

        // Lógica de Reloj en Tiempo Real y Calendario Completo
        const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        function updateClockAndCalendar() {
            const now = new Date();
            
            // 1. Reloj en tiempo real (HH:MM:SS)
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            document.getElementById('live-time').textContent = `${hours}:${minutes}:${seconds}`;
            document.getElementById('live-day-name').textContent = days[now.getDay()];

            // 2. Generación del Calendario Completo del Mes
            const year = now.getFullYear();
            const month = now.getMonth();
            const today = now.getDate();

            document.getElementById('cal-month-year').textContent = `${months[month]} ${year}`;

            const firstDayIndex = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();

            const grid = document.getElementById('cal-days-grid');
            grid.innerHTML = '';

            // Espacios vacíos
            for (let i = 0; i < firstDayIndex; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'py-1.5';
                grid.appendChild(emptyCell);
            }

            // Días del mes
            for (let day = 1; day <= totalDays; day++) {
                const dayCell = document.createElement('div');
                dayCell.textContent = day;
                
                if (day === today) {
                    dayCell.className = 'py-1.5 rounded-xl bg-teal-600 text-white font-black shadow-md shadow-teal-600/30 ring-2 ring-teal-500 scale-105';
                } else {
                    dayCell.className = 'py-1.5 rounded-lg hover:bg-white/60 transition text-slate-800';
                }

                grid.appendChild(dayCell);
            }
        }

        updateClockAndCalendar();
        setInterval(() => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('live-time').textContent = `${hours}:${minutes}:${seconds}`;
        }, 1000);
    });

    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('password-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordIcon.classList.remove('bi-eye');
            passwordIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            passwordIcon.classList.remove('bi-eye-slash');
            passwordIcon.classList.add('bi-eye');
        }
    }
</script>
@endsection