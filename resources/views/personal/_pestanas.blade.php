{{-- Pestañas del módulo de Personal: Plantilla | Nómina (también las usan las vistas de nómina) --}}
@php
    $activa = $activa ?? 'plantilla';
    $pestanasPersonal = [
        ['clave' => 'plantilla', 'texto' => 'Plantilla', 'icono' => 'bi-person-badge', 'ruta' => 'personal.index'],
        ['clave' => 'nomina',    'texto' => 'Nómina',    'icono' => 'bi-receipt',      'ruta' => 'personal.nomina.index'],
    ];
@endphp

<nav class="pe-tabs" aria-label="Secciones de Personal">
    @foreach ($pestanasPersonal as $t)
        @continue(!\Illuminate\Support\Facades\Route::has($t['ruta']))
        <a href="{{ route($t['ruta']) }}" @if ($activa === $t['clave']) aria-current="page" @endif>
            <i class="bi {{ $t['icono'] }}"></i>{{ $t['texto'] }}
        </a>
    @endforeach
</nav>

@once
@verbatim
<style>
.pe-tabs { display: inline-flex; gap: 2px; padding: 3px; border-radius: 12px; background: #fff; border: 1px solid #dbe4e1; font-family: 'Geist', ui-sans-serif, system-ui, sans-serif; }
.pe-tabs a { height: 34px; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; border-radius: 9px; font-size: 13.5px; font-weight: 600; color: #56625f; text-decoration: none; transition: background .15s, color .15s; }
.pe-tabs a:hover { color: #0b1e1c; background: #eef3f1; }
.pe-tabs a .bi { font-size: 14px; }
.pe-tabs a[aria-current=page] { background: #0b1e1c; color: #fff; view-transition-name: pe-pestana; }
.pe-tabs a[aria-current=page] .bi { color: #a6efe2; }
</style>
@endverbatim
@endonce
