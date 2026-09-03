<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Sistema Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- SIDEBAR DE MEDITRACK -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between p-4">
        <div>
            <div class="flex items-center gap-2 text-teal-600 font-bold text-xl px-3 py-2 mb-6">
                <i class="bi bi-heart-pulse-fill text-2xl"></i>
                <span>MediTrack</span>
            </div>

            <nav class="space-y-1">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-teal-50 text-teal-700 font-medium">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-people"></i> Pacientes
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-calendar-event"></i> Citas
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-credit-card"></i> Facturación
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-box-seam"></i> Inventario
                </a>
                <a href="{{ route('personal.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-person-badge"></i> Personal
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="bi bi-gear"></i> Configuración
                </a>
            </nav>
        </div>

        <div class="border-t pt-4 flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold">
                    {{ substr(Auth::user()->nombre ?? 'A', 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 leading-tight">
                        {{ Auth::user()->nombre ?? 'Usuario' }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ Auth::user()->id === 1 ? 'Super Admin' : 'Personal' }}
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-red-500">
                    <i class="bi bi-box-arrow-right text-lg"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ÁREA PRINCIPAL -->
    <div class="flex-1 flex flex-col overflow-y-auto">
        <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between sticky top-0 z-10">
            <h1 class="text-xl font-bold text-gray-800">Dashboard Principal</h1>
            <div class="flex items-center gap-4">
                <div class="relative">
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

        <!-- AQUÍ SE INYECTAN LAS VISTAS COMO DASH/INDEX -->
        <main class="p-8 space-y-6">
            @yield('content')
        </main>
    </div>

</body>
</html>