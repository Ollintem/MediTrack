<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Sistema Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/teal.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
        
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* --- BARRA DE PROGRESO SUPERIOR ULTRA FLUIDA (ESTILO GITHUB/YOUTUBE) --- */
        #top-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, #0d9488 0%, #10b981 50%, #06b6d4 100%);
            box-shadow: 0 0 10px rgba(13, 148, 136, 0.7);
            z-index: 9999;
            transition: width 200ms ease-out, opacity 150ms ease-in;
            pointer-events: none;
            opacity: 0;
        }

        /* ANIMACIÓN DE ENTRADA SUAVE DEL CONTENIDO */
        .page-fade-in {
            animation: fadeInPage 220ms ease-out forwards;
        }

        @keyframes fadeInPage {
            from {
                opacity: 0.85;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-slate-50/50 flex h-screen overflow-hidden font-sans text-gray-800" 
      x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }"
      x-init="$watch('sidebarOpen', value => localStorage.setItem('sidebarOpen', value))">

    <!-- BARRA DE PROGRESO DE NAVEGACIÓN -->
    <div id="top-progress-bar"></div>

    @php
        $user = Auth::user();
        $isSuperAdmin = ($user->id === 1 || ($user->rol && in_array(strtolower($user->rol->nombre), ['super admin', 'administrador'])));

        $tienePermiso = function($nombreModulo) use ($user, $isSuperAdmin) {
            if ($isSuperAdmin) return true;
            if (!$user || !$user->rol_id) return false;

            return \App\Models\Permiso::where('rol_id', $user->rol_id)
                ->whereHas('modulo', function($query) use ($nombreModulo) {
                    $query->whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($nombreModulo))]);
                })
                ->where('puede_ver', 1)
                ->exists();
        };
    @endphp

    <!-- SIDEBAR DE MEDITRACK -->
    <aside 
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    class="w-64 h-full bg-white/95 backdrop-blur-md border-r border-gray-200/80 flex flex-col justify-between p-4 flex-shrink-0 z-30 shadow-sm overflow-hidden">

    <!-- 1. BLOQUE SUPERIOR (LOGO + HAMBURGUESA) -->
    <div class="flex-shrink-0">
        <div class="flex items-center justify-between text-teal-600 px-2 py-2 mb-4">
            <div class="flex items-center gap-2.5 font-bold text-xl tracking-tight">
                <div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-md shadow-teal-600/20">
                    <i class="bi bi-heart-pulse-fill text-xl"></i>
                </div>
                <span class="bg-gradient-to-r from-teal-700 to-emerald-600 bg-clip-text text-transparent">MediTrack</span>
            </div>
            <button @click="sidebarOpen = false" class="p-1.5 text-gray-400 hover:text-teal-700 hover:bg-teal-50 rounded-lg">
                <i class="bi bi-list text-2xl"></i>
            </button>
        </div>
    </div>

    <!-- 2. BLOQUE MEDIO (MENÚ CON SCROLL FORZADO) -->
    <nav class="space-y-1 flex-1 min-h-0 overflow-y-auto pr-1 my-2">
        <a href="{{ route('home') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('home') ? 'bg-teal-500 text-white shadow-md shadow-teal-500/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-grid-fill text-base"></i> <span>Dashboard</span>
        </a>

        @if($tienePermiso('Pacientes'))
        <a href="{{ route('pacientes.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('pacientes.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-people text-base"></i> <span>Pacientes</span>
        </a>
        @endif

        @if($tienePermiso('Citas'))
        <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-slate-100/80 hover:text-gray-900 transition-all">
            <i class="bi bi-calendar-event text-base"></i> <span>Citas</span>
        </a>
        @endif

        @if($tienePermiso('Consultas'))
        <a href="{{ route('consultas.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl transition-all font-semibold text-sm {{ request()->routeIs('consultas.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-teal-50 hover:text-teal-700' }}">
            <i class="bi bi-file-earmark-medical text-base"></i> <span>Consultas</span>
        </a>
        @endif

        @if($tienePermiso('Consultorios'))
        <a href="{{ route('consultorios.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl transition-all font-semibold text-sm {{ request()->routeIs('consultorios.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-teal-50 hover:text-teal-700' }}">
            <i class="bi bi-hospital text-base"></i> <span>Consultorios</span>
        </a>
        @endif

        @if($tienePermiso('Facturación'))
        <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-slate-100/80 hover:text-gray-900 transition-all">
            <i class="bi bi-credit-card text-base"></i> <span>Facturación</span>
        </a>
        @endif

        @if($tienePermiso('Recetas'))
        <a href="{{ route('recetas.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('recetas.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-file-earmark-medical text-base"></i> <span>Recetas</span>
        </a>
        @endif

        @if($tienePermiso('Inventario'))
        <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-slate-100/80 hover:text-gray-900 transition-all">
            <i class="bi bi-box-seam text-base"></i> <span>Inventario</span>
        </a>
        @endif

        @if($tienePermiso('Personal'))
        <a href="{{ route('personal.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('personal.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-person-badge text-base"></i> <span>Personal</span>
        </a>
        @endif

        @if($tienePermiso('Roles'))
        <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('roles.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-shield-lock text-base"></i> <span>Roles y Permisos</span>
        </a>
        @endif

        @if($tienePermiso('Reportes'))
        <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-slate-100/80 hover:text-gray-900 transition-all">
            <i class="bi bi-graph-up text-base"></i> <span>Reportes</span>
        </a>
        @endif

        @if($tienePermiso('Configuración'))
        <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold text-gray-600 hover:bg-slate-100/80 hover:text-gray-900 transition-all">
            <i class="bi bi-gear text-base"></i> <span>Configuración</span>
        </a>
        @endif

        @if($tienePermiso('Bitácora'))
        <a href="{{ route('bitacora.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl transition-all font-semibold text-sm {{ request()->routeIs('bitacora.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-teal-50 hover:text-teal-700' }}">
            <i class="bi bi-journal-text text-base"></i> <span>Bitácora</span>
        </a>
        @endif

        @if($tienePermiso('Configuración'))
        <a href="{{ route('configuracion.index') }}" 
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all {{ request()->routeIs('configuracion.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-teal-50 hover:text-teal-700' }}">
        <i class="bi bi-gear-fill text-lg"></i>
        <span>Configuración</span>
        </a>
        @endif
        </nav>
           
    
    </aside>

    <!-- ÁREA PRINCIPAL -->
    <div class="flex-1 min-w-0 flex flex-col overflow-y-auto">
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200/80 px-6 py-3.5 flex items-center justify-between sticky top-0 z-10 w-full shadow-2xs">
            <div class="flex items-center gap-3">
                <!-- Botón Hamburguesa -->
                <button x-show="!sidebarOpen" @click="sidebarOpen = true" x-cloak class="p-2 text-gray-600 hover:bg-slate-100 rounded-xl transition focus:outline-none">
                    <i class="bi bi-list text-2xl"></i>
                </button>

                <!-- Logo MediTrack -->
                <div x-show="!sidebarOpen" x-cloak class="flex items-center gap-2 font-extrabold text-lg text-teal-600">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center shadow-sm">
                        <i class="bi bi-heart-pulse-fill text-lg"></i>
                    </div>
                    <span class="bg-gradient-to-r from-teal-700 to-emerald-600 bg-clip-text text-transparent">MediTrack</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden sm:block">
                    <i class="bi bi-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                    <input type="text" placeholder="Buscar paciente, cita, factura..." class="bg-slate-100/90 border border-transparent focus:border-teal-300 text-xs rounded-xl pl-9 pr-4 py-2 w-64 focus:outline-none focus:bg-white transition-all">
                </div>
                <button class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-slate-100 rounded-xl transition">
                    <i class="bi bi-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 bg-rose-500 text-white text-[10px] px-1.5 py-0.2 rounded-full font-extrabold">3</span>
                </button>
                <!-- Usuario y logout -->
                <div class="border-t border-gray-100 pt-4 flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-teal-700 to-emerald-500 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    {{ substr($user->nombre ?? 'A', 0, 1) }}
                </div>
                <div>
                    <p class="text-xs font-extrabold text-gray-800 leading-tight">
                        {{ $user->nombre ?? 'Usuario' }}
                    </p>
                    <p class="text-[11px] text-gray-400 font-semibold">
                        {{ $user->rol->nombre ?? ($isSuperAdmin ? 'Super Admin' : 'Personal') }}
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition" title="Cerrar Sesión">
                    <i class="bi bi-box-arrow-right text-lg"></i>
                </button>
            </form>
        </div>
            </div>
        </header>

        <!-- CONTENIDO DINÁMICO -->
        <main id="main-content" class="p-8 space-y-6 w-full min-w-0 flex-1 block page-fade-in">
            @yield('content')
        </main>
    </div>

    <!-- CONTROL DE BARRA PROGRESIVA SUPERIOR SUAVE -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const progressBar = document.getElementById('top-progress-bar');

            // Simular avance inmediato de la barra al hacer clic en un enlace interno
            document.body.addEventListener('click', (e) => {
                const link = e.target.closest('a');

                if (link && link.href) {
                    const targetUrl = link.href;
                    const isExternal = link.target === '_blank';
                    const isAnchor = targetUrl.includes('#') && link.pathname === window.location.pathname;
                    const isSamePage = targetUrl === window.location.href;

                    if (!isExternal && !isAnchor && !isSamePage && !targetUrl.startsWith('javascript:')) {
                        progressBar.style.opacity = '1';
                        progressBar.style.width = '70%';
                    }
                }
            });
        });

        // Completar la barra cuando finalice el evento unload o inicio de carga
        window.addEventListener('beforeunload', () => {
            const progressBar = document.getElementById('top-progress-bar');
            if (progressBar) {
                progressBar.style.opacity = '1';
                progressBar.style.width = '100%';
            }
        });
    </script>
</body>
</html>