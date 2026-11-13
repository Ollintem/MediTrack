<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack — Iniciar Sesión</title>

    {{-- Librerías locales (public/vendor y public/css): el login funciona sin internet --}}
    <!-- Bootstrap Icons y tipografías del sistema -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fonts/fuentes.css') }}">

    <!-- Tailwind compilado (antes cdn.tailwindcss.com); al final del <head>, como lo inyectaba el CDN -->
    <link rel="stylesheet" href="{{ asset('css/tailwind.css') }}?v={{ @filemtime(public_path('css/tailwind.css')) }}">
</head>
<body class="bg-slate-50 font-sans antialiased text-gray-900">
    
    @yield('content')

</body>
</html>