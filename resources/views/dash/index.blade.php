@extends('layouts.admin')

@section('content')
<!-- TARJETAS DE MÉTRICAS (KPIs) -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex justify-between items-start">
        <div>
            <p class="text-sm font-medium text-gray-500">Pacientes de Hoy</p>
            <h3 class="text-3xl font-bold text-gray-800 mt-2">47</h3>
            <span class="text-xs text-emerald-600 font-semibold mt-1 inline-block"><i class="bi bi-graph-up-arrow"></i> +12% vs mes anterior</span>
        </div>
        <div class="bg-teal-50 p-3 rounded-lg text-teal-600"><i class="bi bi-person text-xl"></i></div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex justify-between items-start">
        <div>
            <p class="text-sm font-medium text-gray-500">Citas Pendientes</p>
            <h3 class="text-3xl font-bold text-gray-800 mt-2">12</h3>
            <span class="text-xs text-rose-500 font-semibold mt-1 inline-block"><i class="bi bi-graph-down-arrow"></i> -5% vs mes anterior</span>
        </div>
        <div class="bg-teal-50 p-3 rounded-lg text-teal-600"><i class="bi bi-calendar-check text-xl"></i></div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex justify-between items-start">
        <div>
            <p class="text-sm font-medium text-gray-500">Ingresos del Mes</p>
            <h3 class="text-3xl font-bold text-gray-800 mt-2">$000000</h3>
            <span class="text-xs text-emerald-600 font-semibold mt-1 inline-block"><i class="bi bi-graph-up-arrow"></i> +18.4% vs mes anterior</span>
        </div>
        <div class="bg-teal-50 p-3 rounded-lg text-teal-600"><i class="bi bi-wallet2 text-xl"></i></div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex justify-between items-start">
        <div>
            <p class="text-sm font-medium text-gray-500">Ocupación Consultorios</p>
            <h3 class="text-3xl font-bold text-gray-800 mt-2">0000%</h3>
            <span class="text-xs text-emerald-600 font-semibold mt-1 inline-block"><i class="bi bi-graph-up-arrow"></i> +2.1% vs mes anterior</span>
        </div>
        <div class="bg-teal-50 p-3 rounded-lg text-teal-600"><i class="bi bi-door-open text-xl"></i></div>
    </div>
</div>

<!-- SECCIÓN INFERIOR (GRÁFICA + AGENDA + ACCIONES) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- COLUMNA IZQUIERDA -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Gráfica de Ingresos -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-800">Ingresos Mensuales</h2>
                <span class="text-xs text-teal-600 font-semibold cursor-pointer">Historial de 6 Meses</span>
            </div>
            <div class="h-44 flex items-end justify-between px-6 pt-6 border-b pb-2">
                <div class="w-8 bg-teal-600 rounded-t h-28"></div>
                <div class="w-8 bg-teal-600 rounded-t h-32"></div>
                <div class="w-8 bg-teal-600 rounded-t h-36"></div>
                <div class="w-8 bg-teal-600 rounded-t h-30"></div>
                <div class="w-8 bg-teal-600 rounded-t h-40"></div>
                <div class="w-8 bg-teal-600 rounded-t h-44"></div>
            </div>
            <div class="flex justify-between px-4 text-xs text-gray-400 mt-2 font-medium">
                <span>Ene</span><span>Feb</span><span>Mar</span><span>Abr</span><span>May</span><span>Jun</span>
            </div>
        </div>

        <!-- Agenda del Día -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-800">Agenda del Día</h2>
                <span class="text-xs text-gray-400 font-medium">6 Próximas citas</span>
            </div>
            <div class="divide-y text-sm">
                <div class="py-3 flex items-center justify-between">
                    <span class="font-bold text-teal-600 w-16">08:30</span>
                    <span class="bg-amber-100 text-amber-700 text-xs px-2 py-0.5 rounded font-medium">Pediatría</span>
                    <span class="font-semibold text-gray-800 w-40">Sofía Rodríguez</span>
                    <span class="text-gray-500 text-xs w-36">Dra. Elena Gómez</span>
                    <span class="bg-amber-50 text-amber-600 text-xs px-2.5 py-1 rounded-full font-semibold">En espera</span>
                </div>
                <div class="py-3 flex items-center justify-between">
                    <span class="font-bold text-teal-600 w-16">09:15</span>
                    <span class="bg-pink-100 text-pink-700 text-xs px-2 py-0.5 rounded font-medium">Cardiología</span>
                    <span class="font-semibold text-gray-800 w-40">Alejandro Silva</span>
                    <span class="text-gray-500 text-xs w-36">Dr. Marcos Ruiz</span>
                    <span class="bg-emerald-50 text-emerald-600 text-xs px-2.5 py-1 rounded-full font-semibold">En consulta</span>
                </div>
                <div class="py-3 flex items-center justify-between">
                    <span class="font-bold text-teal-600 w-16">10:00</span>
                    <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 rounded font-medium">Medicina General</span>
                    <span class="font-semibold text-gray-800 w-40">Camila Vergara</span>
                    <span class="text-gray-500 text-xs w-36">Dr. Carlos Mendoza</span>
                    <span class="bg-teal-50 text-teal-600 text-xs px-2.5 py-1 rounded-full font-semibold">Confirmado</span>
                </div>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA -->
    <div class="space-y-6">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm space-y-3">
            <h2 class="font-bold text-gray-800 mb-2">Acciones Rápidas</h2>
            <button class="w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2.5 rounded-lg flex items-center justify-center gap-2 transition">
                <i class="bi bi-calendar-plus"></i> Nueva Cita
            </button>
            <button class="w-full bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium py-2.5 rounded-lg flex items-center justify-center gap-2 border transition">
                <i class="bi bi-person-plus"></i> Registrar Paciente
            </button>
            <button class="w-full bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium py-2.5 rounded-lg flex items-center justify-center gap-2 border transition">
                <i class="bi bi-file-earmark-text"></i> Generar Factura
            </button>
            <button class="w-full bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium py-2.5 rounded-lg flex items-center justify-center gap-2 border transition">
                <i class="bi bi-box"></i> Ver Inventario
            </button>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-800">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="bi bi-exclamation-triangle-fill text-amber-500"></i> Alerta de Stock
            </div>
            <p class="text-xs leading-relaxed text-amber-700">
                Insulina Glargina y Jeringas 5ml están por debajo del nivel mínimo establecido.
            </p>
        </div>
    </div>

</div>
@endsection