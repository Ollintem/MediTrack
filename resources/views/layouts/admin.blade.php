<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Sistema Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- CSS y JS de SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
        
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* BARRA DE PROGRESO SUPERIOR */
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

        /* ANIMACIÓN DE ENTRADA DE PÁGINA */
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

        /* FIX DE SWEETALERT2: FORZAR TAMAÑO Y POSICIÓN COMPACTA DEL TOAST */
        .swal2-container.swal2-top-end, 
        .swal2-container.swal2-top-right {
            top: 1.25rem !important;
            right: 1.25rem !important;
            left: auto !important;
            bottom: auto !important;
            width: auto !important;
            max-width: 380px !important;
            background: transparent !important;
            pointer-events: none !important;
        }

        .swal2-popup.swal2-toast {
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
            max-width: 360px !important;
            background: rgba(255, 255, 255, 0.98) !important;
            border-radius: 1rem !important;
            padding: 0.75rem 1rem !important;
            pointer-events: auto !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
        }

        .swal2-toast .swal2-title {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            color: #1e293b !important;
            margin: 0 0 0 0.5rem !important;
        }

        /* =========================================================
           ESTILOS CUSTOM ULTRA PREMIUM PARA FLATPICKR (MEDITRACK)
           ========================================================= */
        .flatpickr-calendar {
            background: #ffffff !important;
            border-radius: 1.5rem !important;
            border: 1px solid rgba(226, 232, 240, 0.9) !important;
            box-shadow: 0 25px 50px -12px rgba(13, 148, 136, 0.22), 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            padding: 1rem !important;
            width: 330px !important;
            z-index: 99999 !important;
            backdrop-filter: blur(8px) !important;
            margin-top: -8px !important;
        }

        .flatpickr-calendar::before, 
        .flatpickr-calendar::after {
            display: none !important;
        }

        .flatpickr-months {
            align-items: center !important;
            margin-bottom: 0.5rem !important;
            padding: 0 0.25rem !important;
        }
        .flatpickr-months .flatpickr-month {
            background: transparent !important;
            color: #0f766e !important;
            height: 38px !important;
        }
        .flatpickr-current-month {
            font-size: 0.9rem !important;
            font-weight: 800 !important;
            color: #0f766e !important;
            padding-top: 4px !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            font-weight: 800 !important;
            border-radius: 0.75rem !important;
            padding: 2px 6px !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months:hover {
            background: #f0fdf4 !important;
        }
        .flatpickr-current-month input.cur-year {
            font-weight: 800 !important;
            color: #0f766e !important;
        }

        .flatpickr-months .flatpickr-prev-month, 
        .flatpickr-months .flatpickr-next-month {
            fill: #0d9488 !important;
            padding: 6px !important;
            border-radius: 0.75rem !important;
            transition: all 0.2s ease !important;
            top: 0.6rem !important;
        }
        .flatpickr-months .flatpickr-prev-month:hover, 
        .flatpickr-months .flatpickr-next-month:hover {
            background-color: #ccfbf1 !important;
            color: #0d9488 !important;
        }

        /* CORRECCIÓN DE LA CUADRÍCULA DE DÍAS */
        .flatpickr-innerContainer {
            display: block !important;
        }
        .flatpickr-rContainer {
            display: block !important;
            width: 100% !important;
        }
        .flatpickr-weekdays {
            display: flex !important;
            justify-content: space-between !important;
            width: 100% !important;
            background: transparent !important;
            text-align: center !important;
        }
        span.flatpickr-weekday {
            color: #94a3b8 !important;
            font-weight: 800 !important;
            font-size: 0.68rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            flex: 1 !important;
            text-align: center !important;
        }
        .flatpickr-days {
            width: 100% !important;
            display: block !important;
        }
        .dayContainer {
            display: flex !important;
            flex-wrap: wrap !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 100% !important;
            justify-content: flex-start !important;
        }
        .flatpickr-day {
            border-radius: 0.75rem !important;
            color: #334155 !important;
            font-weight: 700 !important;
            font-size: 0.78rem !important;
            height: 35px !important;
            line-height: 35px !important;
            width: 14.28% !important; /* Exactamente 1/7 parte para formar columnas de 7 días */
            max-width: 14.28% !important;
            flex-basis: 14.28% !important;
            margin: 0 !important;
            border: 1px solid transparent !important;
            transition: all 0.15s ease-in-out !important;
        }
        .flatpickr-day:hover {
            background: #f0fdf4 !important;
            color: #0d9488 !important;
            border-color: #99f6e4 !important;
        }
        .flatpickr-day.today {
            border-color: #0d9488 !important;
            color: #0d9488 !important;
            background: #f0fdf4 !important;
        }
        .flatpickr-day.selected, 
        .flatpickr-day.selected:hover {
            background: linear-gradient(135deg, #0d9488 0%, #059669 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35) !important;
            border: none !important;
        }
        .flatpickr-day.flatpickr-disabled, 
        .flatpickr-day.prevMonthDay, 
        .flatpickr-day.nextMonthDay {
            color: #cbd5e1 !important;
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
        $isSuperAdmin = $user ? ($user->id === 1 ? true : ($user->rol ? in_array(strtolower($user->rol->nombre), ['super admin', 'administrador']) : false)) : false;

        $tienePermiso = function($nombreModulo) use ($user, $isSuperAdmin) {
            if ($isSuperAdmin) {
                return true;
            }
            if (!$user) {
                return false;
            }
            if (!$user->rol_id) {
                return false;
            }

            return \App\Models\Permiso::where('rol_id', $user->rol_id)
                ->whereHas('modulo', function($query) use ($nombreModulo) {
                    $query->whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower(trim($nombreModulo))]);
                })
                ->where('puede_ver', 1)
                ->exists();
        };
    @endphp

    <!-- BARRA LATERAL (SIDEBAR) -->
    <aside 
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="-translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="-translate-x-full opacity-0"
        class="w-64 h-full bg-white/95 backdrop-blur-md border-r border-gray-200/80 flex flex-col justify-between p-4 flex-shrink-0 z-30 shadow-sm" 
        x-cloak>

        <!-- 1. BLOQUE SUPERIOR -->
        <div class="flex-shrink-0">
            <div class="flex items-center justify-between text-teal-600 px-2 py-2 mb-4">
                <div class="flex items-center gap-2.5 font-bold text-xl tracking-tight">
                    <div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-md shadow-teal-600/20">
                        <i class="bi bi-heart-pulse-fill text-xl"></i>
                    </div>
                    <span class="bg-gradient-to-r from-teal-700 to-emerald-600 bg-clip-text text-transparent">MediTrack</span>
                </div>
                <button @click="sidebarOpen = false" class="p-1.5 text-gray-400 hover:text-teal-700 hover:bg-teal-50 rounded-xl transition-colors" title="Ocultar menú">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>
        </div>

       <!-- BUSCADOR INTEGRADO EN EL SIDEBAR CON AUTOCOMPLETADO Y PERMISOS -->
<div x-data="buscadorGlobal()" class="relative px-2 mb-3">
    
    <!-- Input con icono y spinner de carga -->
    <div class="relative flex items-center">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
            <i class="bi bi-search text-xs"></i>
        </span>
        
        <input type="text" 
               x-model="query" 
               @input.debounce.300ms="realizarBusqueda()"
               @focus="open = true"
               @click.away="open = false"
               placeholder="Buscar paciente, cita, factura..." 
               class="w-full pl-8 pr-8 py-2 bg-slate-100/90 border border-transparent focus:border-teal-300 rounded-xl text-xs text-gray-700 outline-none transition-all">
        
        <!-- Spinner de carga dinámico -->
        <template x-if="cargando">
            <span class="absolute right-3 flex items-center">
                <i class="bi bi-arrow-repeat animate-spin text-teal-600 text-xs"></i>
            </span>
        </template>
    </div>

    <!-- MENÚ DESPLEGABLE DE RESULTADOS -->
    <div x-show="open && (resultados.length > 0 || (query.length >= 2 && !cargando))" 
         x-transition
         class="absolute left-2 right-2 mt-2 bg-white rounded-2xl shadow-xl border border-gray-100 z-[9999] overflow-hidden max-h-80 overflow-y-auto"
         x-cloak>
        
        <!-- Lista de Resultados según Permisos -->
        <template x-for="(item, index) in resultados" :key="index">
            <a :href="item.url" class="flex items-start gap-2.5 p-2.5 hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-none">
                <div class="p-1.5 bg-teal-50 text-teal-600 rounded-lg flex-shrink-0">
                    <i :class="'bi ' + item.icono + ' text-xs'"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="flex items-center gap-1">
                        <span class="text-[9px] font-bold uppercase text-teal-700 px-1 py-0.2 bg-teal-50 rounded" x-text="item.categoria"></span>
                    </div>
                    <p class="text-xs font-bold text-gray-800 truncate mt-0.5" x-text="item.titulo"></p>
                    <p class="text-[10px] text-gray-400 truncate" x-text="item.subtitulo"></p>
                </div>
            </a>
        </template>

        <!-- Sin Coincidencias o Sin Permisos -->
        <template x-if="resultados.length === 0 && query.length >= 2 && !cargando">
            <div class="p-3 text-center text-xs text-gray-400">
                <i class="bi bi-search text-sm block mb-1"></i>
                <span>Sin coincidencias o permisos.</span>
            </div>
        </template>
    </div>
</div>

<script>
    function buscadorGlobal() {
        return {
            query: '',
            resultados: [],
            cargando: false,
            open: false,

            async realizarBusqueda() {
                if (this.query.length < 2) {
                    this.resultados = [];
                    return;
                }

                this.cargando = true;
                this.open = true;

                try {
                    const response = await fetch(`{{ route('buscar.global') }}?q=${encodeURIComponent(this.query)}`);
                    this.resultados = await response.json();
                } catch (error) {
                    console.error('Error en la búsqueda:', error);
                } finally {
                    this.cargando = false;
                }
            }
        }
    }
</script>

        <!-- 2. BLOQUE MEDIO (MENÚ) -->
       <nav class="space-y-4 flex-1 min-h-0 overflow-y-auto pr-1 my-2">

    <!-- DASHBOARD PRINCIPAL -->
    <a href="{{ route('home') }}" 
       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('home') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
        <i class="bi bi-grid-fill text-base"></i>
        <span>Dashboard</span>
    </a>

    <!-- CATEGORÍA 1: GESTIÓN MÉDICA -->
    <div class="pt-2">
        <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-teal-700/80 border-b border-gray-100 pb-1 mb-2">
            Gestión Médica
        </p>

        <a href="{{ route('pacientes.index') }}" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('pacientes.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-people text-base"></i>
            <span>Pacientes</span>
        </a>

        @if($tienePermiso('Citas'))
<a href="{{ route('citas.index') }}" 
   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('citas.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
    <i class="bi bi-calendar-event text-base"></i>
    <span>Citas</span>
</a>
@endif

        <a href="{{ route('consultas.index') }}" 
   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('consultas.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
    <i class="bi bi-file-earmark-medical text-base"></i>
    <span>Consultas</span>
</a>

        <a href="{{ route('consultorios.index') }}" class="flex items-center gap-3 ...">
    <i class="bi bi-hospital"></i>
    <span>Consultorios</span>
</a>
    </div>

    <!-- CATEGORÍA 2: FARMACIA Y FINANZAS -->
    <div class="pt-2">
        <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-teal-700/80 border-b border-gray-100 pb-1 mb-2">
            Farmacia y Finanzas
        </p>

        <a href="#" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('facturacion.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-credit-card text-base"></i>
            <span>Facturación</span>
        </a>

        <a href="{{ route('recetas.index') }}" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('recetas.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-file-earmark-text text-base"></i>
            <span>Recetas</span>
        </a>

        <a href="#" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('inventario.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-box-seam text-base"></i>
            <span>Inventario</span>
        </a>
    </div>

    <!-- CATEGORÍA 3: ADMINISTRACIÓN DEL SISTEMA -->
    <div class="pt-2">
        <p class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-teal-700/80 border-b border-gray-100 pb-1 mb-2">
            Administración
        </p>

        <a href="{{ route('personal.index') }}" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('personal.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-person-badge text-base"></i>
            <span>Personal</span>
        </a>

        <a href="{{ route('roles.index') }}" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('roles.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-shield-lock text-base"></i>
            <span>Roles y Permisos</span>
        </a>

        <a href="#" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('reportes.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-graph-up text-base"></i>
            <span>Reportes</span>
        </a>

        <a href="{{ route('configuracion.index') }}" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('configuracion.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-gear text-base"></i>
            <span>Configuración</span>
        </a>

        <a href="#" 
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('bitacora.*') ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-gray-600 hover:bg-slate-100/80 hover:text-gray-900' }}">
            <i class="bi bi-journal-text text-base"></i>
            <span>Bitácora</span>
        </a>
    </div>

</nav>
    </aside>

    <!-- ÁREA PRINCIPAL -->
    <div class="flex-1 min-w-0 flex flex-col overflow-y-auto">
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200/80 px-6 py-3.5 flex items-center justify-between sticky top-0 z-10 w-full shadow-2xs">
            <div class="flex items-center gap-3">
                <button x-show="!sidebarOpen" @click="sidebarOpen = true" x-cloak class="p-2 text-gray-600 hover:text-teal-700 hover:bg-teal-50 rounded-xl transition-all focus:outline-none" title="Mostrar menú">
                    <i class="bi bi-list text-2xl"></i>
                </button>

                <div x-show="!sidebarOpen" x-cloak class="flex items-center gap-2 font-extrabold text-lg text-teal-600">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center shadow-sm">
                        <i class="bi bi-heart-pulse-fill text-lg"></i>
                    </div>
                    <span class="bg-gradient-to-r from-teal-700 to-emerald-600 bg-clip-text text-transparent">MediTrack</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                
                <button class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-slate-100 rounded-xl transition">
                    <i class="bi bi-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 bg-rose-500 text-white text-[10px] px-1.5 py-0.2 rounded-full font-extrabold">3</span>
                </button>

                <div class="flex items-center gap-3 pl-2 border-l border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-teal-700 to-emerald-500 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                            {{ substr($user->nombre ?? 'A', 0, 1) }}
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-extrabold text-gray-800 leading-tight">
                                {{ $user->nombre ?? 'Usuario' }}
                            </p>
                            <p class="text-[11px] text-gray-400 font-semibold">
                                {{ $user->rol->nombre ?? ($isSuperAdmin ? 'Super Admin' : 'Personal') }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
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

    <!-- CONTROL DE BARRA PROGRESIVA SUPERIOR -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const progressBar = document.getElementById('top-progress-bar');

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

        window.addEventListener('beforeunload', () => {
            const progressBar = document.getElementById('top-progress-bar');
            if (progressBar) {
                progressBar.style.opacity = '1';
                progressBar.style.width = '100%';
            }
        });
    </script>

    <!-- NOTIFICACIONES TOAST FLOTANTES COMPACTAS -->
    <script>
        window.notificar = function(mensaje, tipo = 'success') {
            const colores = {
                success: { border: 'border-teal-200', icon: '#0d9488' },
                error:   { border: 'border-rose-200', icon: '#f43f5e' },
                info:    { border: 'border-sky-200', icon: '#0284c7' }
            };

            const c = colores[tipo] || colores.success;

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: tipo,
                iconColor: c.icon,
                title: mensaje,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: {
                    popup: `border ${c.border} font-sans`
                },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        };
    </script>

    @if (session('success') || session('error') || session('info') || session('status'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success') || session('status'))
                window.notificar("{{ session('success') ?? session('status') }}", 'success');
            @endif

            @if (session('error'))
                window.notificar("{{ session('error') }}", 'error');
            @endif

            @if (session('info'))
                window.notificar("{{ session('info') }}", 'info');
            @endif
        });
    </script>
    @endif
</body>
</html>