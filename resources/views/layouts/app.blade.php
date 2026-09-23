<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack System</title>
    <!-- Estilos CSS e Iconos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased min-h-screen">
    
    <!-- Navbar Superior Stylized -->
    <nav class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-teal-600 text-white rounded-xl shadow-sm">
                        <i class="bi bi-hospital text-lg"></i>
                    </div>
                    <span class="font-extrabold text-slate-900 text-lg tracking-tight">MediTrack</span>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-800 font-bold flex items-center justify-center text-xs">
                            A
                        </div>
                        <span class="text-xs font-bold text-slate-700">Administrador Principal</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800 transition flex items-center gap-1">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido dinámico -->
    <main>
        @yield('content')
    </main>

</body>
</html>