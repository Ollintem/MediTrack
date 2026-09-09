<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Sistema Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans" x-data="{ sidebarOpen: true }">

    @php
    $user = Auth::user();
    // El usuario ID 1 o con rol 'Super Admin' / 'Administrador' siempre ve todo
    $isSuperAdmin = ($user->id === 1 || ($user->rol && in_array(strtolower($user->rol->nombre), ['super admin', 'administrador'])));

    // Helper simplificado para verificar permisos en la base de datos
    $tienePermiso = function($nombreModulo) use ($user, $isSuperAdmin) {
        if ($isSuperAdmin) return true;
        if (!$user || !$user->rol_id) return false;

        // Comprueba si existe un registro en permisos con puede_ver = 1 para este rol_id y este módulo
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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="-translate-x-full opacity-0"
        class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between p-4 flex-shrink-0 z-30"
    >
        <div>
            <!-- LOGO + BOTÓN HAMBURGUESA JUNTO A MEDITRACK -->
            <div class="flex items-center justify-between text-teal-600 px-2 py-2 mb-6">
                <div class="flex items-center gap-2 font-bold text-xl">
                    <i class="bi bi-heart-pulse-fill text-2xl"></i>
                    <span>MediTrack</span>
                </div>
                <!-- Botón Hamburguesa/Cierre al lado de MediTrack -->
                <button @click="sidebarOpen = false" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition focus:outline-none">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>

            <nav class="space-y-1">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('home') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="bi bi-grid-fill"></i> <span>Dashboard</span>
                </a>

                @if($tienePermiso('Pacientes'))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-people"></i> <span>Pacientes</span>
                </a>
                @endif

                @if($tienePermiso('Citas'))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-calendar-event"></i> <span>Citas</span>
                </a>
                @endif

                @if($tienePermiso('Facturación'))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-credit-card"></i> <span>Facturación</span>
                </a>
                @endif

                @if($tienePermiso('Inventario'))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-box-seam"></i> <span>Inventario</span>
                </a>
                @endif

                @if($tienePermiso('Personal'))
                <a href="{{ route('personal.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('personal.*') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="bi bi-person-badge"></i> <span>Personal</span>
                </a>
                @endif

                @if($tienePermiso('Roles'))
                <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('roles.*') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="bi bi-shield-lock"></i> <span>Roles y Permisos</span>
                </a>
                @endif

                @if($tienePermiso('Reportes'))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-graph-up"></i> <span>Reportes</span>
                </a>
                @endif

                @if($tienePermiso('Configuración'))
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-gear"></i> <span>Configuración</span>
                </a>
                @endif
            </nav>
        </div>

        <div class="border-t pt-4 flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold">
                    {{ substr($user->nombre ?? 'A', 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 leading-tight">
                        {{ $user->nombre ?? 'Usuario' }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $user->rol->nombre ?? ($isSuperAdmin ? 'Super Admin' : 'Personal') }}
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-red-500" title="Cerrar Sesión">
                    <i class="bi bi-box-arrow-right text-lg"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ÁREA PRINCIPAL -->
    <div class="flex-1 flex flex-col overflow-y-auto">
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-4">
                <!-- Botón Hamburguesa que se activa solo si el Sidebar se oculta -->
                <button x-show="!sidebarOpen" @click="sidebarOpen = true" x-cloak class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition focus:outline-none">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <h1 class="text-xl font-bold text-gray-800">Dashboard Principal</h1>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden sm:block">
                    <i class="bi bi-search absolute left-3 top-2.5 text-gray-400"></i>
                    <input type="text" placeholder="Buscar paciente, cita, factura..." class="bg-gray-100 text-sm rounded-lg pl-9 pr-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <button class="relative p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                    <i class="bi bi-bell text-lg"></i>
                    <span class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full font-bold">3</span>
                </button>
                <div class="flex items-center gap-2 bg-teal-50 text-teal-700 px-3 py-1.5 rounded-lg text-sm font-semibold border border-teal-200">
                    <i class="bi bi-hospital"></i> Clínica Principal
                </div>
            </div>
        </header>

        <!-- CONTENIDO DINÁMICO -->
        <main class="p-8 space-y-6">
            @yield('content')
        </main>
    </div>

</body>
</html>