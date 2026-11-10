@extends('layouts.admin')

@section('content')
{{--
    Reportes financieros (ReporteController@index / ReporteFinanciero).
    Ingresos = ventas cobradas en caja (+ otros ingresos de caja).
    Egresos  = compras de inventario + nómina pagada (+ otros egresos de caja).
    Colores validados (paleta categórica, ambos pasan CVD y contraste): ingresos azul, egresos naranja.
--}}
@php
    $dinero = fn ($v) => '$' . number_format((float) $v, 2);
    $r = $resumen;
    $ingresosCat = collect($r['categorias'])->where('tipo', 'ingreso')->values();
    $egresosCat  = collect($r['categorias'])->where('tipo', 'egreso')->values();
    $maxIngreso  = max(1, $ingresosCat->max('total'), collect($r['metodos'])->max('total'));
    $maxEgreso   = max(1, $egresosCat->max('total'));
    $consulta    = ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()];
    $esAtajo     = fn ($a) => $a['desde']->toDateString() === $consulta['desde'] && $a['hasta']->toDateString() === $consulta['hasta'];

    // Navegación día a día (cuando se ve un solo día)
    $porDia = $vista === 'dia';
    $diaAnterior  = $desde->copy()->subDay()->toDateString();
    $diaSiguiente = $desde->copy()->addDay()->toDateString();
    $haySiguiente = $desde->copy()->addDay()->lte(today());

    // ----- Geometría de la gráfica (SVG): por mes o por día -----
    $n = count($serie);
    $izq = 64; $der = 12; $arriba = 12; $abajo = 30; $H = 260;
    $W = $porDia ? max(720, $izq + $der + $n * 22) : 720;      // muchos días: la gráfica se alarga y se desplaza
    $anchoMinimo = (int) round($W * 0.83);                      // ancho en pantalla en el que los textos siguen legibles
    $plotW = $W - $izq - $der; $plotH = $H - $arriba - $abajo;
    $maxValor = max(1, collect($serie)->max(fn ($m) => max($m['ingresos'], $m['egresos'])));
    $paso = pow(10, floor(log10($maxValor)));
    foreach ([1, 2, 2.5, 5, 10] as $f) { if ($f * $paso * 4 >= $maxValor) { $paso *= $f; break; } }
    $tope = $paso * 4;
    $y = fn ($v) => $arriba + $plotH - ($v / $tope) * $plotH;
    $anchoGrupo = $plotW / $n;
    $anchoBarra = max(3, min(16, ($anchoGrupo - ($porDia ? 6 : 10)) / 2));
    $cadaCuanto = $porDia ? max(1, (int) ceil($n / 31)) : 1;    // con muchos días se rotula uno sí y otro no
    // ¿Esa barra pertenece al periodo elegido? (las de contexto se ven más tenues)
    $enPeriodo = fn ($m) => $m['hasta'] >= $consulta['desde'] && $m['desde'] <= $consulta['hasta'];
    $actual = fn ($m) => $porDia ? $m['clave'] === $consulta['hasta'] : $m['clave'] === $hasta->format('Y-m');
    // Abre ese día, o ese mes día por día (sin pasar de hoy)
    $enlace = fn ($m) => route('reportes.index', ['desde' => $m['desde'], 'hasta' => min($m['hasta'], today()->toDateString())] + ($porDia ? [] : ['vista' => 'dia']));
    // Barra con las esquinas superiores redondeadas (4px) y la base plana sobre el eje
    $barra = function ($x, $v) use ($y, $anchoBarra, $arriba, $plotH) {
        $base = $arriba + $plotH; $top = $y($v); $alto = $base - $top;
        if ($alto < 0.5) return '';
        $r = min(4, $alto, $anchoBarra / 2);
        return "M{$x},{$base} V" . ($top + $r) . " Q{$x},{$top} " . ($x + $r) . ",{$top} H" . ($x + $anchoBarra - $r)
             . " Q" . ($x + $anchoBarra) . ",{$top} " . ($x + $anchoBarra) . "," . ($top + $r) . " V{$base} Z";
    };
    $corto = fn ($v) => $v >= 1000000 ? '$' . round($v / 1000000, 1) . 'M' : ($v >= 1000 ? '$' . round($v / 1000, 1) . 'k' : '$' . round($v));
@endphp

<style>
    .rp { --ingreso: #2a78d6; --egreso: #eb6834; --rejilla: #e5e7eb; --tinta-2: #475569; --tinta-3: #64748b; }
    .rp-barra-h { height: 10px; border-radius: 0 4px 4px 0; }
    .rp-barra-h.con-valor { min-width: 3px; } /* un monto pequeño sigue visible; en $0 no hay barra */
    .rp-tabla th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--tinta-3); font-weight: 700; }
    .rp-tabla td, .rp-tabla th { padding: .55rem .75rem; text-align: left; }
    .rp-tabla .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .rp-hit { fill: transparent; cursor: pointer; }
    .rp-hit:focus { outline: none; }
</style>

<div class="rp space-y-6 w-full block">

    {{-- Encabezado --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold">
                    <i class="bi bi-bar-chart-line text-emerald-300"></i> Administración
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Reportes</h2>
                @if ($unDia)
                    <div class="flex items-center gap-2 text-sm">
                        <a href="{{ route('reportes.index', ['desde' => $diaAnterior, 'hasta' => $diaAnterior]) }}"
                           class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center" title="Día anterior" aria-label="Día anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <p class="text-teal-50 font-semibold first-letter:uppercase">
                            {{ $desde->locale('es')->translatedFormat('l j \d\e F \d\e Y') }}{{ $desde->isToday() ? ' · hoy' : '' }}
                        </p>
                        @if ($haySiguiente)
                            <a href="{{ route('reportes.index', ['desde' => $diaSiguiente, 'hasta' => $diaSiguiente]) }}"
                               class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center" title="Día siguiente" aria-label="Día siguiente">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-teal-100/90 max-w-xl">
                        Ingresos y egresos del {{ $desde->locale('es')->translatedFormat('j \d\e F Y') }} al {{ $hasta->locale('es')->translatedFormat('j \d\e F Y') }}
                        ({{ $dias }} días).
                    </p>
                @endif
            </div>
            <a href="{{ route('reportes.exportar', $consulta) }}"
               class="inline-flex items-center gap-2 bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg">
                <i class="bi bi-download"></i> Descargar detalle (CSV)
            </a>
        </div>
    </div>

    {{-- Periodo --}}
    <form method="GET" action="{{ route('reportes.index') }}"
          class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col lg:flex-row lg:items-end gap-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($atajos as $a)
                <a href="{{ route('reportes.index', ['desde' => $a['desde']->toDateString(), 'hasta' => $a['hasta']->toDateString()]) }}"
                   class="px-3 py-2 rounded-xl text-xs font-bold {{ $esAtajo($a) ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $a['texto'] }}
                </a>
            @endforeach
        </div>
        <div class="flex flex-wrap items-end gap-3 lg:ml-auto">
            <label class="block text-xs font-semibold text-slate-600">
                Desde
                <input type="date" name="desde" value="{{ $consulta['desde'] }}" class="mt-1 rounded-xl border border-gray-200 px-3 py-2 text-sm">
            </label>
            <label class="block text-xs font-semibold text-slate-600">
                Hasta
                <input type="date" name="hasta" value="{{ $consulta['hasta'] }}" class="mt-1 rounded-xl border border-gray-200 px-3 py-2 text-sm">
            </label>
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-bold px-4 py-2 rounded-xl text-sm">
                <i class="bi bi-funnel"></i> Ver periodo
            </button>
        </div>
    </form>

    {{-- Totales --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-xs font-bold text-slate-500 flex items-center gap-2"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background: var(--ingreso)"></span>Ingresos</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $dinero($r['ingresos']) }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $r['conteos']['cobros'] }} {{ $r['conteos']['cobros'] === 1 ? 'cobro' : 'cobros' }} en caja</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-xs font-bold text-slate-500 flex items-center gap-2"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background: var(--egreso)"></span>Egresos</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $dinero($r['egresos']) }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $r['conteos']['compras'] }} {{ $r['conteos']['compras'] === 1 ? 'compra' : 'compras' }} · {{ $r['conteos']['recibos'] }} {{ $r['conteos']['recibos'] === 1 ? 'recibo' : 'recibos' }} de nómina</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-xs font-bold text-slate-500">Balance (ingresos − egresos)</p>
            <p class="text-2xl font-extrabold mt-1 tabular-nums {{ $r['balance'] < 0 ? 'text-rose-700' : 'text-slate-900' }}">
                {{ $r['balance'] < 0 ? '−' : '' }}{{ $dinero(abs($r['balance'])) }}
            </p>
            <p class="text-xs mt-1 font-semibold {{ $r['balance'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                <i class="bi {{ $r['balance'] < 0 ? 'bi-arrow-down-right' : 'bi-arrow-up-right' }}"></i>
                {{ $r['balance'] < 0 ? 'Se gastó más de lo que entró' : 'Entró más de lo que se gastó' }}
            </p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-xs font-bold text-slate-500">Utilidad bruta de farmacia</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $dinero($r['farmacia']['utilidad']) }}</p>
            <p class="text-xs text-slate-500 mt-1">
                Ventas {{ $dinero($r['farmacia']['ventas']) }} − costo {{ $dinero($r['farmacia']['costo']) }}
                @if (!is_null($r['farmacia']['margen'])) · margen {{ $r['farmacia']['margen'] }}% @endif
            </p>
        </div>
    </div>

    {{-- Tendencia: por día o por mes --}}
    <section class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm"
             x-data="{ tip: null, tabla: false, M: @js($serie) }">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h3 class="font-extrabold text-slate-900">Ingresos y egresos {{ $porDia ? 'por día' : 'por mes' }}</h3>
                <p class="text-xs text-slate-500">
                    @if (!$porDia)
                        Últimos 12 meses hasta {{ $hasta->locale('es')->translatedFormat('F Y') }} · clic en un mes para verlo día por día
                    @elseif ($dias < 14)
                        {{ $unDia ? 'El día elegido' : 'Los días elegidos' }} y los previos para comparar · clic en una barra para ver ese día
                    @elseif ($recortada)
                        Últimos 92 días del periodo (para más, usa la vista por mes) · clic en una barra para ver ese día
                    @else
                        Cada día del periodo · clic en una barra para ver ese día
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600">
                <div class="inline-flex rounded-lg bg-slate-100 p-0.5" role="group" aria-label="Agrupar la gráfica">
                    <a href="{{ route('reportes.index', $consulta + ['vista' => 'dia']) }}"
                       class="px-2.5 py-1 rounded-md {{ $porDia ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900' }}"
                       @if ($porDia) aria-current="true" @endif>Por día</a>
                    <a href="{{ route('reportes.index', $consulta + ['vista' => 'mes']) }}"
                       class="px-2.5 py-1 rounded-md {{ !$porDia ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900' }}"
                       @if (!$porDia) aria-current="true" @endif>Por mes</a>
                </div>
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background: var(--ingreso)"></span>Ingresos</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-sm" style="background: var(--egreso)"></span>Egresos</span>
                <button type="button" @click="tabla = !tabla" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">
                    <i class="bi" :class="tabla ? 'bi-bar-chart' : 'bi-table'"></i> <span x-text="tabla ? 'Ver gráfica' : 'Ver tabla'"></span>
                </button>
            </div>
        </div>

        <div x-show="!tabla" class="overflow-x-auto -mx-1 px-1" x-init="$nextTick(() => $el.scrollLeft = $el.scrollWidth)">{{-- en pantallas angostas la gráfica se desplaza dentro de su recuadro --}}
        <div class="relative" style="min-width: {{ $anchoMinimo }}px" @mouseleave="tip = null">
            <svg viewBox="0 0 {{ $W }} {{ $H }}" class="w-full h-auto" role="img"
                 aria-label="Ingresos y egresos {{ $porDia ? 'por día' : 'de los últimos 12 meses' }}. Usa Ver tabla para leer los valores.">
                {{-- Rejilla y eje Y --}}
                @for ($i = 0; $i <= 4; $i++)
                    @php $vy = $y($paso * $i); @endphp
                    <line x1="{{ $izq }}" x2="{{ $W - $der }}" y1="{{ $vy }}" y2="{{ $vy }}" stroke="var(--rejilla)" stroke-width="1" />
                    <text x="{{ $izq - 8 }}" y="{{ $vy + 4 }}" text-anchor="end" font-size="11" fill="var(--tinta-3)">{{ $corto($paso * $i) }}</text>
                @endfor

                @foreach ($serie as $i => $m)
                    @php
                        $gx = $izq + $i * $anchoGrupo;
                        $x1 = $gx + ($anchoGrupo - (2 * $anchoBarra + 2)) / 2;   // 2px de separación entre barras
                        $x2 = $x1 + $anchoBarra + 2;
                        $tenue = !$enPeriodo($m);
                        $resaltar = $actual($m);
                        $rotular = $resaltar || $i % $cadaCuanto === 0;
                        // En la vista por día, el primer día de cada mes lleva el mes ("1 Dic")
                        $rotulo = $porDia && $m['etiqueta'] === '1' ? '1 ' . explode(' ', $m['titulo'])[2] : $m['etiqueta'];
                    @endphp
                    <rect class="rp-col" x="{{ $gx }}" y="{{ $arriba }}" width="{{ $anchoGrupo }}" height="{{ $plotH }}" fill="transparent" pointer-events="none"
                          :fill="tip === {{ $i }} ? 'rgba(15,23,42,.05)' : 'transparent'" />
                    <g opacity="{{ $tenue ? '0.35' : '1' }}" pointer-events="none">
                        <path d="{{ $barra($x1, $m['ingresos']) }}" fill="var(--ingreso)" />
                        <path d="{{ $barra($x2, $m['egresos']) }}" fill="var(--egreso)" />
                    </g>
                    @if ($rotular)
                        <text x="{{ $gx + $anchoGrupo / 2 }}" y="{{ $H - 10 }}" text-anchor="middle" font-size="11" pointer-events="none"
                              fill="{{ $resaltar ? '#0f172a' : 'var(--tinta-3)' }}" font-weight="{{ $resaltar ? '700' : '400' }}">{{ $rotulo }}</text>
                    @endif
                    {{-- Zona para pasar el mouse y hacer clic: abre ese día (o ese mes, día por día) --}}
                    <a href="{{ $enlace($m) }}" aria-label="{{ $m['titulo'] }}: ingresos {{ $dinero($m['ingresos']) }}, egresos {{ $dinero($m['egresos']) }}. Abrir.">
                        <rect class="rp-hit" x="{{ $gx }}" y="{{ $arriba }}" width="{{ $anchoGrupo }}" height="{{ $plotH + $abajo }}"
                              @mouseenter="tip = {{ $i }}" @focus="tip = {{ $i }}" />
                    </a>
                @endforeach
                <line x1="{{ $izq }}" x2="{{ $W - $der }}" y1="{{ $arriba + $plotH }}" y2="{{ $arriba + $plotH }}" stroke="#94a3b8" stroke-width="1" />
            </svg>

            {{-- Tooltip --}}
            <template x-if="tip !== null">
                <div class="absolute top-2 pointer-events-none bg-white border border-gray-200 shadow-lg rounded-xl px-3 py-2 text-xs w-48"
                     :style="'left: clamp(0px, calc(' + (({{ $izq }} + (tip + .5) * {{ $anchoGrupo }}) / {{ $W }} * 100) + '% - 96px), calc(100% - 192px))'">
                    <p class="font-bold text-slate-900 mb-1" x-text="M[tip].titulo"></p>
                    <p class="flex justify-between gap-2"><span class="flex items-center gap-1.5 text-slate-600"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background: var(--ingreso)"></span>Ingresos</span><b class="tabular-nums text-slate-900" x-text="dinero(M[tip].ingresos)"></b></p>
                    <p class="flex justify-between gap-2"><span class="flex items-center gap-1.5 text-slate-600"><span class="inline-block w-2.5 h-2.5 rounded-sm" style="background: var(--egreso)"></span>Egresos</span><b class="tabular-nums text-slate-900" x-text="dinero(M[tip].egresos)"></b></p>
                    <p class="flex justify-between gap-2 border-t border-gray-100 mt-1 pt-1"><span class="text-slate-600">Balance</span><b class="tabular-nums" :class="M[tip].balance < 0 ? 'text-rose-700' : 'text-slate-900'" x-text="dinero(M[tip].balance)"></b></p>
                    <p class="text-[11px] text-teal-700 font-semibold mt-1">{{ $porDia ? 'Clic para ver este día' : 'Clic para ver este mes día por día' }}</p>
                </div>
            </template>
        </div>
        </div>

        {{-- La misma información en tabla (accesible y para copiar) --}}
        <div x-show="tabla" x-cloak class="overflow-x-auto">
            <table class="rp-tabla w-full text-sm">
                <thead><tr class="border-b border-gray-100"><th>{{ $porDia ? 'Día' : 'Mes' }}</th><th class="num">Ingresos</th><th class="num">Egresos</th><th class="num">Balance</th></tr></thead>
                <tbody>
                    @foreach ($serie as $m)
                        <tr class="border-b border-gray-50 {{ $enPeriodo($m) ? '' : 'text-slate-400' }}">
                            <td class="font-semibold"><a href="{{ $enlace($m) }}" class="text-teal-700 hover:underline">{{ $m['titulo'] }}</a></td>
                            <td class="num">{{ $dinero($m['ingresos']) }}</td>
                            <td class="num">{{ $dinero($m['egresos']) }}</td>
                            <td class="num {{ $m['balance'] < 0 ? 'text-rose-700' : '' }}">{{ $dinero($m['balance']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Desglose --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <section class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-5">
            <div>
                <h3 class="font-extrabold text-slate-900 mb-3">Ingresos por concepto</h3>
                @foreach ($ingresosCat as $c)
                    <div class="mb-3">
                        <div class="flex justify-between text-sm"><span class="text-slate-700">{{ $c['nombre'] }}</span><b class="tabular-nums text-slate-900">{{ $dinero($c['total']) }}</b></div>
                        <div class="mt-1 bg-slate-100 rounded-r"><div class="rp-barra-h {{ $c['total'] > 0 ? 'con-valor' : '' }}" style="width: {{ $c['total'] / $maxIngreso * 100 }}%; background: var(--ingreso)"></div></div>
                    </div>
                @endforeach
            </div>
            <div>
                <h3 class="font-extrabold text-slate-900 mb-3">Ventas por método de pago</h3>
                @foreach ($r['metodos'] as $m)
                    <div class="mb-3">
                        <div class="flex justify-between text-sm"><span class="text-slate-700">{{ $m['metodo'] }} <span class="text-xs text-slate-500">· {{ $m['cobros'] }} {{ $m['cobros'] === 1 ? 'cobro' : 'cobros' }}</span></span><b class="tabular-nums text-slate-900">{{ $dinero($m['total']) }}</b></div>
                        <div class="mt-1 bg-slate-100 rounded-r"><div class="rp-barra-h {{ $m['total'] > 0 ? 'con-valor' : '' }}" style="width: {{ $m['total'] / $maxIngreso * 100 }}%; background: var(--ingreso)"></div></div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
            <h3 class="font-extrabold text-slate-900 mb-3">Egresos por concepto</h3>
            @foreach ($egresosCat as $c)
                <div class="mb-3">
                    <div class="flex justify-between text-sm"><span class="text-slate-700">{{ $c['nombre'] }}</span><b class="tabular-nums text-slate-900">{{ $dinero($c['total']) }}</b></div>
                    <div class="mt-1 bg-slate-100 rounded-r"><div class="rp-barra-h {{ $c['total'] > 0 ? 'con-valor' : '' }}" style="width: {{ $c['total'] / $maxEgreso * 100 }}%; background: var(--egreso)"></div></div>
                </div>
            @endforeach
            <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                <i class="bi bi-info-circle"></i>
                Las compras son las entradas de inventario (surtido y lotes) por su costo de compra; no incluyen el inventario inicial
                capturado al dar de alta un producto. La nómina es el neto pagado a cada empleado en su fecha de pago.
                El fondo de apertura y el corte de caja no cuentan como ingreso ni egreso.
            </p>
        </section>
    </div>

    {{-- Productos más vendidos --}}
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 pt-5 pb-3">
            <h3 class="font-extrabold text-slate-900">Productos más vendidos</h3>
            <p class="text-xs text-slate-500">Tickets cobrados en el periodo, con su costo y utilidad</p>
        </div>
        <div class="overflow-x-auto">
            <table class="rp-tabla w-full text-sm">
                <thead><tr class="border-y border-gray-100 bg-slate-50/60"><th>Producto</th><th class="num">Piezas</th><th class="num">Ventas</th><th class="num">Costo</th><th class="num">Utilidad</th></tr></thead>
                <tbody>
                    @forelse ($productos as $p)
                        <tr class="border-b border-gray-50">
                            <td><span class="font-semibold text-slate-800">{{ $p['nombre'] }}</span> <span class="text-xs text-slate-400">{{ $p['codigo'] }}</span></td>
                            <td class="num">{{ number_format($p['piezas']) }}</td>
                            <td class="num">{{ $dinero($p['ventas']) }}</td>
                            <td class="num text-slate-500">{{ $dinero($p['costo']) }}</td>
                            <td class="num font-semibold {{ $p['utilidad'] < 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $dinero($p['utilidad']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-slate-500 py-8">No hubo ventas cobradas en este periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Detalle --}}
    @php $limite = 100; @endphp
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 pt-5 pb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h3 class="font-extrabold text-slate-900">Detalle de movimientos</h3>
                <p class="text-xs text-slate-500">
                    {{ number_format($movimientos->count()) }} {{ $movimientos->count() === 1 ? 'movimiento' : 'movimientos' }}
                    @if ($movimientos->count() > $limite) · se muestran los {{ $limite }} más recientes; descarga el CSV para verlos todos @endif
                </p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="rp-tabla w-full text-sm">
                <thead><tr class="border-y border-gray-100 bg-slate-50/60"><th>Fecha</th><th>Categoría</th><th>Concepto</th><th>Método</th><th class="num">Ingreso</th><th class="num">Egreso</th></tr></thead>
                <tbody>
                    @forelse ($movimientos->take($limite) as $m)
                        <tr class="border-b border-gray-50">
                            <td class="whitespace-nowrap text-slate-600">{{ $m['fecha'] }}</td>
                            <td class="whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 text-slate-700">
                                    <span class="inline-block w-2 h-2 rounded-sm" style="background: var(--{{ $m['tipo'] }})"></span>{{ $m['categoria'] }}
                                </span>
                            </td>
                            <td class="text-slate-800">{{ $m['concepto'] }}</td>
                            <td class="text-slate-600">{{ $m['metodo'] }}</td>
                            <td class="num">{{ $m['ingreso'] ? $dinero($m['ingreso']) : '' }}</td>
                            <td class="num">{{ $m['egreso'] ? $dinero($m['egreso']) : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-slate-500 py-8">No hay ingresos ni egresos en este periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    // Formato de dinero para el tooltip de la gráfica
    function dinero(v) {
        const n = Number(v) || 0;
        return (n < 0 ? '−' : '') + '$' + Math.abs(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
</script>
@endsection
