{{-- Pestañas del módulo de Personal: Plantilla | Nómina --}}
@php
    $activa = $activa ?? 'plantilla';
    $pestanasPersonal = [
        ['clave' => 'plantilla', 'texto' => 'Plantilla', 'sub' => 'Empleados y accesos',  'icono' => 'bi-people-fill', 'ruta' => 'personal.index'],
        ['clave' => 'nomina',    'texto' => 'Nómina',    'sub' => 'Periodos y recibos',   'icono' => 'bi-cash-coin',   'ruta' => 'personal.nomina.index'],
    ];
@endphp

<nav class="flex items-center gap-1 p-1.5 rounded-2xl bg-white border border-slate-200 w-full sm:w-fit" aria-label="Secciones de Personal">
    @foreach ($pestanasPersonal as $t)
        @continue(!\Illuminate\Support\Facades\Route::has($t['ruta']))
        @php $esActiva = $activa === $t['clave']; @endphp
        <a href="{{ route($t['ruta']) }}"
           @if ($esActiva) aria-current="page" @endif
           class="flex-1 sm:flex-none flex items-center gap-2.5 px-4 py-2 rounded-xl transition-all
                  {{ $esActiva ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center {{ $esActiva ? 'bg-white/20' : 'bg-slate-100' }}">
                <i class="bi {{ $t['icono'] }}"></i>
            </span>
            <span class="text-left leading-tight">
                <span class="block text-xs font-black">{{ $t['texto'] }}</span>
                <span class="hidden sm:block text-[10px] font-semibold {{ $esActiva ? 'text-teal-100' : 'text-slate-400' }}">{{ $t['sub'] }}</span>
            </span>
        </a>
    @endforeach
</nav>
