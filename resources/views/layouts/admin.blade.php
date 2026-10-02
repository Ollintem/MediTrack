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

    <!-- Estado global del layout -->
    <script>
        function layoutMeditrack() {
            return {
                sidebarOpen: (() => { try { return localStorage.getItem('sidebarOpen') !== 'false'; } catch (e) { return true; } })(),
                mobileOpen: false,
                ancho: window.innerWidth,
                ahora: new Date(),
                headerVisible: true,
                tip: { texto: '', top: 0, left: 0 },
                headerHover: false,
                ultimoScroll: 0,

                get saludo() {
                    const h = this.ahora.getHours();
                    if (h < 12) return 'Buenos días';
                    if (h < 19) return 'Buenas tardes';
                    return 'Buenas noches';
                },
                get hora() {
                    return this.ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
                },
                get fecha() {
                    return this.ahora.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
                },

                init() {
                    this.$watch('sidebarOpen', v => { try { localStorage.setItem('sidebarOpen', v); } catch (e) {} });
                    window.addEventListener('resize', () => {
                        this.ancho = window.innerWidth;
                        if (this.ancho >= 1024) this.mobileOpen = false;
                    });
                    setInterval(() => { this.ahora = new Date(); }, 15000);
                },

                alScrollear(el) {
                    const y = el.scrollTop;
                    const delta = y - this.ultimoScroll;
                    if (y < 80) this.headerVisible = true;
                    else if (delta > 6 && !this.headerHover) this.headerVisible = false;
                    else if (delta < -6) this.headerVisible = true;
                    this.ultimoScroll = y;
                },

                alMoverCursor(e, el) {
                    if (this.headerVisible) return;
                    if (e.clientY - el.getBoundingClientRect().top < 40) this.headerVisible = true;
                },

                mostrarTip(el, texto) {
                    if (this.sidebarOpen || this.ancho < 1024) return;
                    const r = el.getBoundingClientRect();
                    this.tip = { texto, top: r.top + r.height / 2, left: r.right + 14 };
                },
                ocultarTip() { this.tip.texto = ''; },

                alternarMenu() {
                    if (this.ancho < 1024) this.mobileOpen = !this.mobileOpen;
                    else { this.sidebarOpen = !this.sidebarOpen; this.ocultarTip(); }
                }
            };
        }
    </script>

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
        .page-fade-in { animation: fadeInPage 220ms ease-out forwards; }
        @keyframes fadeInPage {
            from { opacity: 0.85; transform: translateY(4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* SIDEBAR MEDITRACK */
        .mt-sidebar {
            background: #ffffff;
            width: 18rem;
            z-index: 40;
            transition: width .3s cubic-bezier(.2,.8,.2,1), transform .3s cubic-bezier(.2,.8,.2,1);
        }
        .mt-header { transition: transform .35s cubic-bezier(.2,.8,.2,1), opacity .25s ease; will-change: transform; }
        .mt-header.header-oculto { transform: translateY(-110%); opacity: 0; pointer-events: none; }
        @media (prefers-reduced-motion: reduce) { .mt-header { transition: none; } }

        @media (max-width: 1023.98px) {
            .mt-sidebar { position: fixed; top: 0; bottom: 0; left: 0; transform: translateX(-100%); }
            .mt-sidebar.movil-abierto { transform: translateX(0); box-shadow: 0 25px 50px -12px rgba(15,23,42,.25); }
        }
        .solo-rail { display: none !important; }

        @media (min-width: 1024px) {
            .mt-sidebar { position: relative; }
            .mt-sidebar.rail { width: 5.25rem; }
            .mt-sidebar.rail .solo-expandido { display: none !important; }
            .mt-sidebar.rail .solo-rail { display: flex !important; }
            .mt-sidebar.rail .marca-fila { justify-content: center; }
            .mt-sidebar.rail .nav-link { justify-content: center; padding: .5rem; }
        }

        .nav-scroll::-webkit-scrollbar { width: 4px; }
        .nav-scroll::-webkit-scrollbar-thumb { background: rgba(15,23,42,0.12); border-radius: 999px; }
        .nav-scroll::-webkit-scrollbar-track { background: transparent; }
        .nav-scroll { scrollbar-width: thin; scrollbar-color: rgba(15,23,42,0.12) transparent; }

        .latido { display: inline-block; animation: latido 1.4s ease-in-out infinite; transform-origin: center; }
        @keyframes latido {
            0%, 100% { transform: scale(1); }
            14%      { transform: scale(1.18); }
            28%      { transform: scale(1); }
            42%      { transform: scale(1.12); }
            70%      { transform: scale(1); }
        }

        .ecg-trazo {
            stroke-dasharray: 160;
            stroke-dashoffset: 160;
            animation: ecg 2.8s linear infinite;
        }
        @keyframes ecg {
            0%   { stroke-dashoffset: 160; opacity: 1; }
            70%  { stroke-dashoffset: 0;   opacity: 1; }
            100% { stroke-dashoffset: 0;   opacity: 0; }
        }

        .pulso-online::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 999px;
            border: 2px solid #34d399;
            animation: ondaOnline 2s ease-out infinite;
        }
        @keyframes ondaOnline {
            from { opacity: .8; transform: scale(.6); }
            to   { opacity: 0;  transform: scale(1.6); }
        }

        .nav-item { animation: navEntrada 380ms cubic-bezier(.2,.8,.2,1) both; }
        @keyframes navEntrada {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .latido, .ecg-trazo, .pulso-online::after, .nav-item { animation: none !important; }
            .ecg-trazo { stroke-dashoffset: 0; }
        }

        /* FIX DE SWEETALERT2 */
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

        /* ESTILOS DE FLATPICKR */
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
        .flatpickr-calendar::after { display: none !important; }
        .flatpickr-months { align-items: center !important; margin-bottom: 0.5rem !important; padding: 0 0.25rem !important; }
        .flatpickr-months .flatpickr-month { background: transparent !important; color: #0f766e !important; height: 38px !important; }
        .flatpickr-current-month { font-size: 0.9rem !important; font-weight: 800 !important; color: #0f766e !important; padding-top: 4px !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 800 !important; border-radius: 0.75rem !important; padding: 2px 6px !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months:hover { background: #f0fdf4 !important; }
        .flatpickr-current-month input.cur-year { font-weight: 800 !important; color: #0f766e !important; }
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            fill: #0d9488 !important;
            padding: 6px !important;
            border-radius: 0.75rem !important;
            transition: all 0.2s ease !important;
            top: 0.6rem !important;
        }
        .flatpickr-months .flatpickr-prev-month:hover,
        .flatpickr-months .flatpickr-next-month:hover { background-color: #ccfbf1 !important; color: #0d9488 !important; }
        .flatpickr-innerContainer { display: block !important; }
        .flatpickr-rContainer { display: block !important; width: 100% !important; }
        .flatpickr-weekdays { display: flex !important; justify-content: space-between !important; width: 100% !important; background: transparent !important; text-align: center !important; }
        span.flatpickr-weekday {
            color: #94a3b8 !important;
            font-weight: 800 !important;
            font-size: 0.68rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            flex: 1 !important;
            text-align: center !important;
        }
        .flatpickr-days { width: 100% !important; display: block !important; }
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
            width: 14.28% !important;
            max-width: 14.28% !important;
            flex-basis: 14.28% !important;
            margin: 0 !important;
            border: 1px solid transparent !important;
            transition: all 0.15s ease-in-out !important;
        }
        .flatpickr-day:hover { background: #f0fdf4 !important; color: #0d9488 !important; border-color: #99f6e4 !important; }
        .flatpickr-day.today { border-color: #0d9488 !important; color: #0d9488 !important; background: #f0fdf4 !important; }
        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: linear-gradient(135deg, #0d9488 0%, #059669 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35) !important;
            border: none !important;
        }
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay { color: #cbd5e1 !important; }
    </style>
</head>
<body class="bg-slate-100/70 flex h-screen overflow-hidden font-sans text-gray-800"
      x-data="layoutMeditrack()"
      @keydown.escape.window="mobileOpen = false">

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

        // ===== MENÚ CONFIGURADO CON PROVEEDORES, POS, CAJA Y NÓMINA =====
       // ===== MENÚ CONFIGURADO DIRECTO =====
        $menu = [
            ['titulo' => null, 'items' => [
                ['texto' => 'Dashboard',        'ruta' => 'home',               'activo' => 'home',           'icono' => 'bi-grid-1x2-fill',          'permiso' => null],
            ]],
            ['titulo' => 'Gestión Médica', 'items' => [
                ['texto' => 'Pacientes',        'ruta' => 'pacientes.index',     'activo' => 'pacientes.*',    'icono' => 'bi-people-fill',            'permiso' => null],
                ['texto' => 'Citas',            'ruta' => 'citas.index',         'activo' => 'citas.*',        'icono' => 'bi-calendar2-week-fill',    'permiso' => null],
                ['texto' => 'Consultas',        'ruta' => 'consultas.index',     'activo' => 'consultas.*',    'icono' => 'bi-clipboard2-pulse-fill',  'permiso' => null],
                ['texto' => 'Consultorios',     'ruta' => 'consultorios.index',  'activo' => 'consultorios.*', 'icono' => 'bi-hospital-fill',          'permiso' => null],
            ]],
            ['titulo' => 'Farmacia y Finanzas', 'items' => [
                ['texto' => 'Terminal POS',     'ruta' => 'pos.index',           'activo' => 'pos.*',          'icono' => 'bi-shop',           'permiso' => null],
                ['texto' => 'Caja Central',     'ruta' => 'caja.index',          'activo' => 'caja.*',         'icono' => 'bi-cash-coin',      'permiso' => null],
                ['texto' => 'Despacho Farmacia','ruta' => 'farmacia.despacho',   'activo' => 'farmacia.*',     'icono' => 'bi-bag-check-fill', 'permiso' => null],
                ['texto' => 'Inventario',       'ruta' => 'inventario.index',    'activo' => 'inventario.*',   'icono' => 'bi-box-seam-fill',  'permiso' => null],
                ['texto' => 'Proveedores',      'ruta' => 'proveedores.index',   'activo' => 'proveedores.*',  'icono' => 'bi-truck',                  'permiso' => null],
                ['texto' => 'Recetas',          'ruta' => 'recetas.index',       'activo' => 'recetas.*',      'icono' => 'bi-file-earmark-medical-fill','permiso' => null],
                ['texto' => 'Facturación',      'ruta' => null,                  'activo' => 'facturacion.*',  'icono' => 'bi-credit-card-2-front-fill','permiso' => null],
            ]],
            ['titulo' => 'Administración', 'items' => [
                ['texto' => 'Personal',         'ruta' => 'personal.index',      'activo' => 'personal.*',     'icono' => 'bi-person-badge-fill', 'permiso' => null],
                ['texto' => 'Roles y Permisos', 'ruta' => 'roles.index',         'activo' => 'roles.*',        'icono' => 'bi-shield-lock-fill',       'permiso' => null],
                ['texto' => 'Reportes',         'ruta' => null,                  'activo' => 'reportes.*',     'icono' => 'bi-bar-chart-line-fill',    'permiso' => null],
                ['texto' => 'Configuración',    'ruta' => 'configuracion.index', 'activo' => 'configuracion.*','icono' => 'bi-gear-fill',              'permiso' => null],
                ['texto' => 'Bitácora',         'ruta' => 'bitacora.index',      'activo' => 'bitacora.*',     'icono' => 'bi-journal-text',           'permiso' => null],
            ]],
        ];

        $seccionActual = null;
        $paginaActual  = 'Dashboard';
        foreach ($menu as $i => $seccion) {
            foreach ($menu[$i]['items'] as $item) {
                if (request()->routeIs($item['activo'])) {
                    $seccionActual = $seccion['titulo'];
                    $paginaActual  = $item['texto'];
                }
            }
        }

        $nombreUsuario = $user->nombre ?? 'Usuario';
        $primerNombre  = \Illuminate\Support\Str::of($nombreUsuario)->trim()->explode(' ')->first() ?: 'Usuario';
        $iniciales     = collect(preg_split('/\s+/', trim($nombreUsuario)))
                            ->filter()->take(2)
                            ->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                            ->implode('') ?: 'U';
        $rolUsuario    = optional(optional($user)->rol)->nombre ?? ($isSuperAdmin ? 'Super Admin' : 'Personal');

        $totalNotificaciones = (int) ($totalNotificaciones ?? 0);
        $contadorPaso = 0;
    @endphp

    <!-- FONDO OSCURO EN MÓVIL -->
    <div x-show="mobileOpen" x-cloak
         x-transition.opacity.duration.200ms
         @click="mobileOpen = false"
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-30 lg:hidden"></div>

    <!-- BARRA LATERAL (SIDEBAR) -->
    <aside x-cloak
           class="mt-sidebar h-full flex flex-col flex-shrink-0 text-slate-700 border-r border-slate-200"
           :class="{ 'movil-abierto': mobileOpen, 'rail': !sidebarOpen }">

        <!-- MARCA -->
        <div class="relative flex-shrink-0 px-4 pt-5 pb-4">
            <div class="marca-fila flex items-center gap-3">
                <a href="{{ route('home') }}" class="relative flex-shrink-0 w-11 h-11 rounded-2xl bg-teal-600 flex items-center justify-center shadow-md shadow-teal-600/25">
                    <i class="bi bi-heart-pulse-fill text-xl text-white latido"></i>
                </a>

                <div class="solo-expandido min-w-0 flex-1">
                    <p class="text-lg font-black tracking-tight leading-none text-slate-800">
                        Medi<span class="text-teal-600">Track</span>
                    </p>
                    <svg class="mt-1.5 w-28 h-3.5" viewBox="0 0 120 16" fill="none" aria-hidden="true">
                        <path d="M0 8 H38 L44 2 L50 14 L56 4 L60 8 H78 L82 5 L86 8 H120" stroke="rgba(13,148,136,.15)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path class="ecg-trazo" d="M0 8 H38 L44 2 L50 14 L56 4 L60 8 H78 L82 5 L86 8 H120" stroke="#14b8a6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <button x-show="ancho < 1024" @click="mobileOpen = false"
                        class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="Cerrar menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- BUSCADOR GLOBAL -->
        <div x-data="buscadorGlobal()"
             @keydown.window.ctrl.k.prevent="enfocar()"
             @keydown.window.meta.k.prevent="enfocar()"
             class="relative flex-shrink-0 px-4 mb-2 z-[60]">

            <button type="button" @click="enfocar()"
                    @mouseenter="mostrarTip($el, 'Buscar módulo (Ctrl+K)')" @mouseleave="ocultarTip()"
                    class="solo-rail w-full h-11 rounded-2xl bg-slate-100 hover:bg-teal-50 text-slate-500 hover:text-teal-700 items-center justify-center transition">
                <i class="bi bi-search"></i>
            </button>

            <div class="solo-expandido relative flex items-center">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="bi bi-search text-xs"></i>
                </span>

                <input type="text"
                       x-ref="buscador"
                       x-model="query"
                       @input.debounce.200ms="realizarBusqueda()"
                       @focus="open = true"
                       @click.away="open = false"
                       @keydown.escape="open = false; $el.blur()"
                       placeholder="Ir a un módulo…"
                       class="w-full pl-9 pr-14 py-2.5 bg-slate-100 hover:bg-slate-200/60 focus:bg-white border border-transparent focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 rounded-2xl text-xs text-slate-700 placeholder-slate-400 outline-none transition-all">

                <span class="absolute right-2.5 flex items-center">
                    <i x-show="cargando" class="bi bi-arrow-repeat animate-spin text-teal-600 text-xs"></i>
                    <kbd x-show="!cargando" class="px-1.5 py-0.5 rounded-md bg-white border border-slate-200 text-[9px] font-bold text-slate-400 font-sans">Ctrl K</kbd>
                </span>
            </div>

            <div x-show="open && (resultados.length > 0 || (query.length >= 2 && !cargando))"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="absolute left-4 right-4 mt-2 bg-white rounded-2xl shadow-2xl shadow-slate-900/10 ring-1 ring-black/5 z-[99999] overflow-hidden max-h-80 overflow-y-auto text-gray-800"
                 x-cloak>

                <template x-for="(item, index) in resultados" :key="index">
                    <a :href="item.url" class="flex items-center gap-2.5 p-2.5 hover:bg-teal-50/70 transition-colors border-b border-gray-50 last:border-none">
                        <div class="p-2 bg-teal-50 text-teal-600 rounded-xl flex-shrink-0">
                            <i :class="'bi ' + item.icono + ' text-sm'"></i>
                        </div>
                        <div class="overflow-hidden flex-1">
                            <span class="text-[9px] font-bold uppercase text-teal-700 px-1.5 py-0.5 bg-teal-50 rounded-md" x-text="item.categoria"></span>
                            <p class="text-xs font-black text-gray-800 truncate mt-0.5" x-text="item.titulo"></p>
                        </div>
                        <i class="bi bi-arrow-return-left text-gray-300 text-xs"></i>
                    </a>
                </template>

                <template x-if="resultados.length === 0 && query.length >= 2 && !cargando">
                    <div class="p-4 text-center text-xs text-gray-400">
                        <i class="bi bi-door-closed text-base block mb-1"></i>
                        <span>Módulo no encontrado o sin acceso.</span>
                    </div>
                </template>
            </div>
        </div>

        <!-- NAVEGACIÓN -->
        <nav class="nav-scroll relative flex-1 min-h-0 overflow-y-auto overflow-x-hidden px-4 py-2 space-y-5">
            @foreach($menu as $seccion)
                @continue(count($seccion['items']) === 0)

                <div>
                    @if($seccion['titulo'])
                        <p class="solo-expandido flex items-center gap-2 px-3 mb-2 text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">
                            <span>{{ $seccion['titulo'] }}</span>
                            <span class="flex-1 h-px bg-slate-200"></span>
                        </p>
                        <div class="solo-rail justify-center mb-2"><span class="h-px w-8 bg-slate-200"></span></div>
                    @endif

                    <div class="space-y-1">
                        @foreach($seccion['items'] as $item)
                            @php
                                $esActivo  = request()->routeIs($item['activo']);
                                $pendiente = empty($item['ruta']) || !\Illuminate\Support\Facades\Route::has($item['ruta']);
                                $href      = $pendiente ? '#' : route($item['ruta']);
                                $contadorPaso++;
                            @endphp

                            <a href="{{ $href }}"
                               @if($pendiente) aria-disabled="true" @endif
                               @if($esActivo) aria-current="page" @endif
                               @mouseenter="mostrarTip($el, @js($item['texto'] . ($pendiente ? ' · Pronto' : '')))"
                               @mouseleave="ocultarTip()"
                               style="animation-delay: {{ $contadorPaso * 35 }}ms"
                               class="nav-item nav-link group relative flex items-center gap-3 px-2.5 py-2 rounded-2xl text-[13px] font-bold transition-all duration-200
                                      {{ $esActivo
                                          ? 'bg-teal-50 text-teal-800'
                                          : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}
                                      {{ $pendiente ? 'cursor-default' : '' }}">

                                @if($esActivo)
                                    <span class="absolute -left-4 top-1/2 -translate-y-1/2 h-7 w-1.5 rounded-r-full bg-teal-600"></span>
                                @endif

                                <span class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200
                                             {{ $esActivo
                                                 ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/30'
                                                 : 'bg-slate-100 text-slate-500 group-hover:bg-teal-100 group-hover:text-teal-700 group-hover:scale-105' }}">
                                    <i class="bi {{ $item['icono'] }} text-[15px]"></i>
                                </span>

                                <span class="solo-expandido truncate">{{ $item['texto'] }}</span>

                                @if($pendiente)
                                    <span class="solo-expandido ml-auto text-[9px] font-extrabold uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-400 border border-slate-200">Pronto</span>
                                @elseif($esActivo)
                                    <i class="solo-expandido bi bi-chevron-right ml-auto text-[10px] text-teal-500"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <!-- PIE DEL SIDEBAR -->
        <div class="relative flex-shrink-0 p-4">
            <div class="solo-rail justify-center py-2"
                 @mouseenter="mostrarTip($el, 'Sistema en línea')" @mouseleave="ocultarTip()">
                <span class="pulso-online relative w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            </div>
            <div class="solo-expandido rounded-2xl bg-slate-50 border border-slate-200 p-3">
                <div class="flex items-center gap-2.5">
                    <span class="relative flex w-2 h-2">
                        <span class="pulso-online relative w-2 h-2 rounded-full bg-emerald-400"></span>
                    </span>
                    <p class="text-[11px] font-bold text-slate-600">Sistema en línea</p>
                    <span class="ml-auto text-[10px] font-semibold text-slate-400" x-text="hora"></span>
                </div>
                <p class="mt-1 pl-[18px] text-[10px] text-slate-400 capitalize" x-text="fecha"></p>
            </div>
        </div>
    </aside>

    <!-- ETIQUETA FLOTANTE DE ÍCONOS -->
    <div x-show="tip.texto && !sidebarOpen && ancho >= 1024" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         :style="`top:${tip.top}px; left:${tip.left}px`"
         class="fixed z-[70] -translate-y-1/2 pointer-events-none">
        <div class="relative px-3 py-1.5 rounded-xl bg-slate-800 text-white text-xs font-bold shadow-xl whitespace-nowrap">
            <span class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 rotate-45 bg-slate-800"></span>
            <span x-text="tip.texto"></span>
        </div>
    </div>

    <!-- TRANSICIÓN LÍQUIDA -->
    <div x-data="{
            activo: true,
            reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            init() {
                requestAnimationFrame(() => requestAnimationFrame(() => { this.activo = false; }));
            },
            navegar(url) {
                this.activo = true;
                if (this.reducedMotion) { window.location.href = url; return; }

                let yaNavego = false;
                const ir = () => { if (!yaNavego) { yaNavego = true; window.location.href = url; } };
                this.$refs.panel.addEventListener('transitionend', ir, { once: true });
                setTimeout(ir, 700);
            }
        }"
        x-init="init()"
        @disparar-liquido.window="navegar($event.detail)"
        role="status"
        aria-live="polite"
        :aria-hidden="(!activo).toString()"
        class="pointer-events-none fixed inset-0 z-[999999] overflow-hidden flex items-center justify-center"
        :class="activo && 'pointer-events-auto'">

        <div x-ref="panel"
             class="absolute inset-x-0 -top-[3%] h-[106%] bg-gradient-to-br from-teal-950 via-teal-900 to-emerald-950 will-change-transform"
             :class="reducedMotion ? '' : 'transition-transform duration-[600ms] ease-[cubic-bezier(0.83,0,0.17,1)]'"
             :style="activo ? 'transform: translateY(0%);' : 'transform: translateY(-100%);'">

            <svg class="absolute -bottom-px left-0 w-[200%] h-10 text-emerald-400/70"
                 :class="activo && !reducedMotion && 'animate-ola'"
                 viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0,30 C150,55 150,5 300,30 C450,55 450,5 600,30 C750,55 750,5 900,30 C1050,55 1050,5 1200,30 L1200,60 L0,60 Z" fill="currentColor" opacity="0.55"/>
            </svg>
            <div class="absolute bottom-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-emerald-400 to-transparent shadow-[0_0_18px_#34d399]"></div>
        </div>

        <div class="relative z-20 flex items-center justify-center pointer-events-none">
            <div :class="reducedMotion ? '' : 'transition-all duration-500 ease-out'"
                 :style="activo ? 'opacity:1; transform: translateY(0) scale(1);' : 'opacity:0; transform: translateY(-8px) scale(0.94);'"
                 class="text-center space-y-3">

                  <div class="relative w-16 h-16 mx-auto">
                      <div class="absolute inset-0 rounded-2xl bg-emerald-400/25 blur-xl" :class="activo && !reducedMotion && 'animate-resplandor'"></div>
                      <div class="relative w-16 h-16 rounded-2xl bg-teal-900/90 border border-emerald-500/40 shadow-[0_0_30px_rgba(52,211,153,0.35)] flex items-center justify-center">
                          <i class="bi bi-heart-pulse-fill text-emerald-400 text-2xl" aria-hidden="true"></i>
                      </div>
                  </div>

                  <div class="space-y-1">
                      <h2 class="text-white font-black tracking-[0.4em] text-sm uppercase drop-shadow-md">MediTrack</h2>
                      <p class="text-[10px] text-emerald-300/70 tracking-widest uppercase">Sistema médico</p>
                  </div>

                  <div class="w-24 h-[3px] mx-auto rounded-full bg-white/10 overflow-hidden mt-1">
                      <div class="h-full bg-emerald-400 rounded-full" :class="activo && !reducedMotion && 'animate-progreso'"></div>
                  </div>
             </div>
             <span class="sr-only">Cargando…</span>
        </div>
    </div>

    <style>
        @keyframes ola { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .animate-ola { animation: ola 3.4s linear infinite; }
        @keyframes resplandor { 0%, 100% { opacity: .55; transform: scale(1); } 50% { opacity: .15; transform: scale(1.35); } }
        .animate-resplandor { animation: resplandor 1.8s ease-in-out infinite; }
        @keyframes progreso { from { width: 0%; } to { width: 100%; } }
        .animate-progreso { animation: progreso 600ms ease-out forwards; }
        @media (prefers-reduced-motion: reduce) { .animate-ola, .animate-resplandor, .animate-progreso { animation: none !important; } }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('a').forEach((enlace) => {
                enlace.addEventListener('click', (e) => {
                    const href = enlace.getAttribute('href');
                    const esExterno = enlace.hostname && enlace.hostname !== window.location.hostname;

                    if (href === '#' || enlace.getAttribute('aria-disabled') === 'true') {
                        e.preventDefault();
                        return;
                    }

                    if (
                        href &&
                        !href.startsWith('#') &&
                        !href.startsWith('javascript:') &&
                        !href.startsWith('mailto:') &&
                        !href.startsWith('tel:') &&
                        enlace.target !== '_blank' &&
                        !href.includes('logout') &&
                        !enlace.hasAttribute('download') &&
                        !esExterno &&
                        !e.ctrlKey && !e.metaKey && !e.shiftKey
                    ) {
                        e.preventDefault();
                        window.dispatchEvent(new CustomEvent('disparar-liquido', { detail: href }));
                    }
                });
            });
        });
    </script>

    <!-- ÁREA PRINCIPAL -->
    <div class="flex-1 min-w-0 flex flex-col overflow-y-auto"
         @scroll.passive="alScrollear($el)"
         @mousemove.throttle.100ms="alMoverCursor($event,$el)">

        <header class="mt-header sticky top-0 z-20 w-full px-4 sm:px-6 lg:px-8 pt-4 pb-3 bg-slate-100/70 backdrop-blur-xl"
                :class="{ 'header-oculto': !headerVisible }"
                @mouseenter="headerHover = true"
                @mouseleave="headerHover = false">
            <div class="flex items-center gap-3 sm:gap-4 rounded-3xl bg-white/90 border border-white shadow-[0_8px_30px_-12px_rgba(15,118,110,0.18)] ring-1 ring-slate-200/60 px-3 sm:px-4 py-2.5">

                <button @click="alternarMenu()"
                        class="group flex-shrink-0 w-10 h-10 rounded-2xl flex items-center justify-center border transition"
                        :class="(ancho < 1024 ? mobileOpen : sidebarOpen)
                                    ? 'text-slate-500 hover:text-teal-700 bg-slate-50 hover:bg-teal-50 border-slate-200/70'
                                    : 'text-white bg-teal-600 hover:bg-teal-700 border-teal-600 shadow-md shadow-teal-600/25'"
                        :title="(ancho < 1024 ? mobileOpen : sidebarOpen) ? 'Ocultar menú' : 'Mostrar menú'">
                    <i class="bi text-lg transition-transform duration-200"
                       :class="(ancho < 1024 ? mobileOpen : sidebarOpen) ? 'bi-list' : 'bi-arrow-bar-right group-hover:translate-x-0.5'"></i>
                </button>

                <div class="min-w-0 flex-1">
                    <nav class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-400" aria-label="Ruta">
                        <i class="bi bi-house-door-fill text-teal-500"></i>
                        @if($seccionActual)
                            <i class="bi bi-chevron-right text-[8px]"></i>
                            <span class="hidden sm:inline">{{ $seccionActual }}</span>
                            <i class="bi bi-chevron-right text-[8px] hidden sm:inline"></i>
                        @else
                            <i class="bi bi-chevron-right text-[8px]"></i>
                        @endif
                        <span class="text-teal-700 font-bold truncate">{{ $paginaActual }}</span>
                    </nav>
                    <p class="text-sm sm:text-base font-black text-slate-800 truncate leading-tight mt-0.5">
                        <span x-text="saludo">Hola</span>, {{ $primerNombre }}
                    </p>
                </div>

                <div class="hidden md:flex items-center gap-2.5 px-3.5 py-2 rounded-2xl bg-teal-50 border border-teal-100">
                    <i class="bi bi-clock-history text-teal-600"></i>
                    <div class="leading-tight">
                        <p class="text-sm font-black text-teal-900 tabular-nums" x-text="hora"></p>
                        <p class="text-[10px] font-semibold text-teal-700/70 capitalize" x-text="fecha"></p>
                    </div>
                </div>

                <!-- NOTIFICACIONES -->
                <div x-data="{ abierto: false }" class="relative">
                    <button @click="abierto = !abierto" @click.outside="abierto = false"
                            class="relative w-10 h-10 rounded-2xl flex items-center justify-center text-slate-500 hover:text-teal-700 bg-slate-50 hover:bg-teal-50 border border-slate-200/70 transition"
                            title="Notificaciones">
                        <i class="bi bi-bell-fill"></i>
                        @if($totalNotificaciones > 0)
                            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center ring-2 ring-white">
                                {{ $totalNotificaciones > 9 ? '9+' :$totalNotificaciones }}
                            </span>
                            <span class="absolute -top-1 -right-1 w-[18px] h-[18px] rounded-full bg-rose-500 animate-ping opacity-40"></span>
                        @endif
                    </button>

                    <div x-show="abierto" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-3 w-80 origin-top-right rounded-3xl bg-white shadow-2xl shadow-slate-900/15 ring-1 ring-slate-200 overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                            <p class="text-sm font-black text-slate-800">Notificaciones</p>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-teal-50 text-teal-700">{{ $totalNotificaciones }} nuevas</span>
                        </div>
                        <div class="px-5 py-8 text-center">
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-teal-50 text-teal-500 flex items-center justify-center mb-3">
                                <i class="bi bi-bell-slash text-xl"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-600">Todo al día</p>
                            <p class="text-[11px] text-slate-400 mt-1">Aquí aparecerán alertas de citas y stock.</p>
                        </div>
                    </div>
                </div>

                <!-- USUARIO -->
                <div x-data="{ abierto: false }" class="relative">
                    <button @click="abierto = !abierto" @click.outside="abierto = false"
                            class="flex items-center gap-2.5 pl-1 pr-2 sm:pr-3 py-1 rounded-2xl hover:bg-slate-50 border border-transparent hover:border-slate-200/70 transition"
                            :class="abierto && 'bg-slate-50 border-slate-200/70'">
                        <span class="relative flex-shrink-0">
                            <span class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-black text-sm shadow-md shadow-teal-600/25">
                                {{ $iniciales }}
                            </span>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 ring-2 ring-white"></span>
                        </span>
                        <span class="hidden sm:block text-left leading-tight">
                            <span class="block text-xs font-black text-slate-800 max-w-[140px] truncate">{{ $nombreUsuario }}</span>
                            <span class="block text-[10px] font-bold text-teal-600 uppercase tracking-wider">{{ $rolUsuario }}</span>
                        </span>
                        <i class="bi bi-chevron-down text-[10px] text-slate-400 hidden sm:block transition-transform duration-200" :class="abierto && 'rotate-180'"></i>
                    </button>

                    <div x-show="abierto" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-3 w-72 origin-top-right rounded-3xl bg-white shadow-2xl shadow-slate-900/15 ring-1 ring-slate-200 overflow-hidden">

                        <div class="relative px-5 pt-5 pb-4 bg-teal-50 text-slate-800 border-b border-teal-100 overflow-hidden">
                            <div class="relative flex items-center gap-3">
                                <span class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-black text-lg">{{ $iniciales }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-black truncate">{{ $nombreUsuario }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ $user->email ?? '' }}</p>
                                </div>
                            </div>
                            <span class="relative inline-flex items-center gap-1 mt-3 px-2 py-0.5 rounded-full bg-white ring-1 ring-teal-200 text-[10px] font-extrabold uppercase tracking-wider text-teal-700">
                                <i class="bi bi-shield-check"></i> {{ $rolUsuario }}
                            </span>
                        </div>

                        <div class="p-2">
                            <a href="{{ route('configuracion.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-2xl text-xs font-bold text-slate-600 hover:bg-teal-50 hover:text-teal-700 transition">
                                <span class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center"><i class="bi bi-gear-fill"></i></span>
                                Configuración
                            </a>

                            <div class="my-1.5 mx-3 h-px bg-slate-100"></div>

                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition">
                                    <span class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center"><i class="bi bi-box-arrow-right"></i></span>
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENIDO DINÁMICO -->
        <main id="main-content" class="px-4 sm:px-6 lg:px-8 pt-3 pb-8 space-y-6 w-full min-w-0 flex-1 block page-fade-in">
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

    <!-- NOTIFICACIONES TOAST -->
    <script>
        window.notificar = function(mensaje, tipo = 'success') {
            const colores = {
                success: { border: 'border-teal-200', icon: '#0d9488' },
                error:   { border: 'border-rose-200', icon: '#f43f5e' },
                warning: { border: 'border-amber-200', icon: '#f59e0b' },
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

    @if (session('success') || session('error') || session('warning') || session('info') || session('status'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success') || session('status'))
                window.notificar(@js(session('success') ?? session('status')), 'success');
            @endif

            @if (session('error'))
                window.notificar(@js(session('error')), 'error');
            @endif

            @if (session('warning'))
                window.notificar(@js(session('warning')), 'warning');
            @endif

            @if (session('info'))
                window.notificar(@js(session('info')), 'info');
            @endif
        });
    </script>
    @endif

   <!-- BUSCADOR GLOBAL DINÁMICO DEL SIDEBAR -->
    <script>
        // Extraemos automáticamente los módulos registrados en el menú de Laravel
        const modulosRegistrados = @js(
            collect($menu)->flatMap(function($seccion) {
                return collect($seccion['items'])->map(function($item) use ($seccion) {
                    return [
                        'titulo'    => $item['texto'],
                        'categoria' => $seccion['titulo'] ?? 'General',
                        'icono'     => $item['icono'],
                        'url'       => (!empty($item['ruta']) && \Illuminate\Support\Facades\Route::has($item['ruta'])) ? route($item['ruta']) : '#',
                        'pendiente' => empty($item['ruta']) || !\Illuminate\Support\Facades\Route::has($item['ruta'])
                    ];
                });
            })->values()->all()
        );

        function buscadorGlobal() {
            return {
                query: '',
                resultados: [],
                cargando: false,
                open: false,

                enfocar() {
                    if (window.innerWidth < 1024) this.mobileOpen = true;
                    else this.sidebarOpen = true;
                    setTimeout(() => this.$refs.buscador?.focus(), 320);
                },

                realizarBusqueda() {
                    const q = this.query.trim().toLowerCase();

                    if (q.length < 2) {
                        this.resultados = [];
                        return;
                    }

                    this.cargando = true;
                    this.open = true;

                    // Filtra dinámicamente sobre los módulos registrados en el menú
                    this.resultados = modulosRegistrados.filter(m => 
                        !m.pendiente && (
                            m.titulo.toLowerCase().includes(q) || 
                            m.categoria.toLowerCase().includes(q)
                        )
                    );

                    this.cargando = false;
                }
            }
        }
    </script>
</body>
</html>