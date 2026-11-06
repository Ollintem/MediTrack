@extends('layouts.admin')

@section('content')
{{--
    Bitácora: historial de movimientos registrados con Bitacora::registrar().
    Solo lectura, con filtros por módulo, acción, usuario y fechas (BitacoraController@index).
--}}
@php
    $colorAccion = fn ($accion) => match (mb_strtolower((string) $accion)) {
        'crear'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'editar'   => 'bg-sky-50 text-sky-700 ring-sky-200',
        'eliminar' => 'bg-rose-50 text-rose-700 ring-rose-200',
        default    => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
    $hayFiltros = collect($filtros)->filter()->isNotEmpty();
@endphp

<div class="space-y-6 w-full block">

    {{-- Encabezado --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="relative z-10 space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold">
                <i class="bi bi-journal-text text-emerald-300"></i> Administración
            </div>
            <h2 class="text-3xl font-extrabold tracking-tight">Bitácora</h2>
            <p class="text-sm text-teal-100/90 max-w-xl">
                Quién hizo qué y cuándo: altas, cambios y bajas registradas en el sistema.
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('bitacora.index') }}"
          class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
        <label class="block text-xs font-semibold text-slate-600">
            Módulo
            <select name="modulo" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach ($modulos as $m)
                    <option value="{{ $m }}" @selected(($filtros['modulo'] ?? '') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            Acción
            <select name="accion" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                <option value="">Todas</option>
                @foreach ($acciones as $a)
                    <option value="{{ $a }}" @selected(($filtros['accion'] ?? '') === $a)>{{ $a }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            Usuario
            <input type="text" name="usuario" value="{{ $filtros['usuario'] ?? '' }}" placeholder="Nombre"
                   class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            Desde
            <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"
                   class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            Hasta
            <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"
                   class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
        </label>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-bold px-4 py-2 rounded-xl text-sm">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            @if ($hayFiltros)
                <a href="{{ route('bitacora.index') }}" class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200" title="Quitar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>

    {{-- Registros --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 text-xs font-semibold text-slate-500">
            {{ number_format($registros->total()) }} {{ $registros->total() === 1 ? 'registro' : 'registros' }}
        </div>

        @forelse ($registros as $r)
            <div class="px-5 py-4 border-b border-gray-50 last:border-0 flex flex-col md:flex-row md:items-start gap-3">
                <div class="md:w-40 shrink-0 text-xs text-slate-500">
                    <div class="font-semibold text-slate-700">{{ $r->created_at?->format('d/m/Y') }}</div>
                    <div>{{ $r->created_at?->format('H:i:s') }}</div>
                </div>
                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold ring-1 {{ $colorAccion($r->accion) }}">{{ $r->accion }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-700 ring-1 ring-teal-200">{{ $r->modulo }}</span>
                        <span class="text-xs text-slate-500"><i class="bi bi-person"></i> {{ $r->usuario_nombre ?: 'Sistema' }}</span>
                        @if ($r->ip_address)
                            <span class="text-[11px] text-slate-400">IP {{ $r->ip_address }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-800 break-words">{{ $r->descripcion }}</p>
                    @if (!empty($r->detalles))
                        <details class="text-xs">
                            <summary class="cursor-pointer text-teal-700 font-semibold">Ver detalles</summary>
                            <pre class="mt-2 p-3 rounded-xl bg-slate-50 text-slate-700 overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode($r->detalles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-5 py-12 text-center text-sm text-slate-500">
                <i class="bi bi-journal-x text-3xl block mb-2 text-slate-300"></i>
                {{ $hayFiltros ? 'No hay registros con esos filtros.' : 'Todavía no hay movimientos registrados.' }}
            </div>
        @endforelse
    </div>

    @if ($registros->hasPages())
        <div>{{ $registros->links() }}</div>
    @endif
</div>
@endsection
