@extends('layouts.admin')

@section('content')
@php
    $usuario       = auth()->user();
    $puede         = fn ($accion) => !$usuario || !method_exists($usuario, 'tienePermiso') || $usuario->tienePermiso('Personal', $accion);
    $puedeCrear    = $puede('crear');
    $puedeEliminar = $puede('eliminar');

    $periodosJs = collect($periodos ?? [])->map(fn ($p) => [
        'id'        => $p->id,
        'tipo'      => $p->tipo,
        'inicio'    => $p->fecha_inicio->format('Y-m-d'),
        'fin'       => $p->fecha_fin->format('Y-m-d'),
        'estado'    => $p->estado,
        'empleados' => (int) $p->recibos_count,
        'pagados'   => (int) $p->pagados_count,
        'neto'      => (float) $p->total_neto,
        'pendiente' => (float) $p->neto_pendiente,
        'url'       => route('personal.nomina.show', $p),
    ])->values();

    $tarjetas = [
        ['Pendiente por pagar', 'pendiente',  'bi-hourglass-split', 'bg-amber-500 text-white',   true],
        ['Pagado este mes',     'pagadoMes',  'bi-cash-stack',      'bg-teal-600 text-white',    true],
        ['Pagado este año',     'pagadoAnio', 'bi-graph-up-arrow',  'bg-emerald-500 text-white', true],
        ['Empleados activos',   'empleados',  'bi-people-fill',     'bg-sky-500 text-white',     false],
    ];
@endphp

<div class="space-y-6" x-data="moduloNomina()">

    @include('personal._pestanas', ['activa' => 'nomina'])

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="nom-aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <i class="bi bi-cash-coin absolute right-14 top-6 text-[100px] leading-none text-white/10 pointer-events-none hidden md:block"></i>

        <div class="relative p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-[11px] font-bold uppercase tracking-wide text-emerald-300">
                    <i class="bi bi-person-badge-fill"></i> Personal
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex items-center gap-3">
                    Nómina <i class="bi bi-heart-pulse-fill text-emerald-300 text-2xl nom-latido"></i>
                </h1>
                <p class="text-xs text-teal-100/80 font-medium max-w-xl leading-relaxed">
                    Cada empleado tiene su propio calendario de pago. Genera recibos para quien corresponda y registra cada pago por separado.
                </p>
            </div>
            @if ($puedeCrear)
                <button type="button" @click="abrirNuevo()"
                        class="group shrink-0 bg-white text-teal-900 hover:bg-emerald-50 font-black px-5 py-3 rounded-2xl text-xs shadow-lg shadow-teal-950/25 transition-all hover:-translate-y-0.5 active:scale-95 flex items-center gap-2">
                    <i class="bi bi-plus-circle-fill text-sm text-teal-700 transition-transform group-hover:rotate-90"></i> Nuevo pago
                </button>
            @endif
        </div>
    </div>

    @if (($resumen['sinSalario'] ?? 0) > 0)
        <div class="nom-aparece flex items-start gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-base"></i>
            <p><b>{{ $resumen['sinSalario'] }} empleado(s)</b> no tienen salario diario registrado. Captúralo en <b>Plantilla → Editar</b> o dentro de su recibo.</p>
        </div>
    @endif

    {{-- ===================== TARJETAS ===================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ($tarjetas as $i => [$titulo, $clave, $icono, $color, $esDinero])
            <div class="nom-aparece bg-white rounded-2xl border border-slate-200 p-4 hover:-translate-y-0.5 hover:shadow-md transition-all" style="animation-delay: {{ 60 + $i * 60 }}ms">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold text-slate-500">{{ $titulo }}</p>
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center {{ $color }}"><i class="bi {{ $icono }}"></i></span>
                </div>
                <p class="text-2xl font-black text-slate-800 mt-1 tabular-nums truncate">
                    {{ $esDinero ? '$' . number_format($resumen[$clave] ?? 0, 2) : ($resumen[$clave] ?? 0) }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start">

        {{-- ===================== PRÓXIMOS PAGOS POR EMPLEADO ===================== --}}
        <section class="nom-aparece xl:col-span-2 bg-white rounded-3xl border border-slate-200 overflow-hidden" style="animation-delay:280ms">
            <div class="p-5 border-b border-slate-100 space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-alarm"></i></span>
                    <div class="flex-1">
                        <h3 class="text-base font-black text-slate-800">Próximos pagos</h3>
                        <p class="text-[11px] font-semibold text-slate-400">Sugerencias según el tipo de pago de cada empleado · no se crean hasta que das “Generar”</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="t in ['TODOS', 'SEMANAL', 'QUINCENAL', 'MENSUAL']" :key="t">
                        <button type="button" @click="filtroTipo = t"
                                class="px-3 py-1 rounded-full text-[11px] font-bold border transition"
                                :class="filtroTipo === t ? 'bg-teal-600 border-teal-600 text-white' : 'bg-white border-slate-200 text-slate-500 hover:border-teal-300'">
                            <span x-text="t === 'TODOS' ? 'Todos' : etiquetaTipo(t)"></span>
                            <span class="ml-0.5 opacity-70" x-text="t === 'TODOS' ? empleados.length : empleados.filter(e => e.tipo_pago === t).length"></span>
                        </button>
                    </template>
                </div>
                @if ($puedeCrear)
                    <button type="button" x-show="filtroTipo !== 'TODOS' && empleadosFiltrados.length" x-cloak @click="abrirNuevoTipo(filtroTipo)"
                            class="w-full py-2 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-800 text-[11px] font-black transition flex items-center justify-center gap-1.5">
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span x-text="'Generar pago ' + etiquetaTipo(filtroTipo).toLowerCase() + ' para ' + empleadosFiltrados.length + ' empleado(s)'"></span>
                    </button>
                @endif
            </div>

            <div class="divide-y divide-slate-100 max-h-[560px] overflow-y-auto">
                <template x-for="(e, i) in empleadosFiltrados" :key="e.id">
                    <div class="nom-fila p-4 flex items-center gap-3 hover:bg-slate-50/70 transition" :style="'animation-delay:' + Math.min(i * 30, 300) + 'ms'">
                        <span class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-[11px] font-black shrink-0" x-text="iniciales(e.nombre)"></span>
                        <div class="min-w-0 flex-1">
                            <a :href="e.ficha" class="block text-sm font-black text-slate-800 hover:text-teal-700 truncate" x-text="e.nombre"></a>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                <span class="text-[9px] font-black px-1.5 py-0.5 rounded-md" :class="colorTipo(e.tipo_pago)" x-text="etiquetaTipo(e.tipo_pago)"></span>
                                <span class="text-[10px] font-semibold text-slate-400" x-text="'Sugerido: ' + rango(e.prox_ini, e.prox_fin)"
                                      title="Aún no se ha creado; es solo una propuesta según su tipo de pago"></span>
                            </div>
                            <p class="text-[10px] font-semibold text-slate-400 mt-1"
                               x-text="e.ultimo ? 'Último periodo generado hasta el ' + fechaCorta(e.ultimo) : 'Aún no tiene pagos generados'"></p>
                            <p class="text-[10px] font-black" :class="vencimiento(e).clase" x-text="vencimiento(e).texto"></p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-black text-slate-700 tabular-nums" x-text="e.salario > 0 ? dinero(e.salario * diasEntre(e.prox_ini, e.prox_fin)) : 'Sin salario'"
                               :class="e.salario > 0 ? '' : 'text-amber-600'"></p>
                            @if ($puedeCrear)
                                <button type="button" @click="abrirNuevoEmpleado(e)"
                                        class="mt-1 h-7 px-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-[10px] font-black inline-flex items-center gap-1 transition">
                                    <i class="bi bi-plus-lg"></i> Generar
                                </button>
                            @endif
                        </div>
                    </div>
                </template>
                <p x-show="!empleadosFiltrados.length" class="p-8 text-center text-xs font-semibold text-slate-400">No hay empleados con este tipo de pago.</p>
            </div>
        </section>

        {{-- ===================== PERIODOS / PAGOS ===================== --}}
        <section class="nom-aparece xl:col-span-3 bg-white rounded-3xl border border-slate-200 overflow-hidden" style="animation-delay:340ms">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-calendar-range"></i></span>
                    <div>
                        <h3 class="text-base font-black text-slate-800">Periodos y pagos</h3>
                        <p class="text-[11px] font-semibold text-slate-400" x-text="periodos.length + ' registrados'"></p>
                    </div>
                </div>
                <div class="flex p-1 rounded-2xl bg-slate-100 text-[11px] font-bold self-start sm:self-auto">
                    <template x-for="f in [['Todos','Todos'], ['Pendientes','Con pendientes'], ['Pagado','Pagados']]" :key="f[0]">
                        <button type="button" @click="filtro = f[0]"
                                :class="filtro === f[0] ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'"
                                class="px-3 py-1.5 rounded-xl transition" x-text="f[1]"></button>
                    </template>
                </div>
            </div>

            <div class="divide-y divide-slate-100" x-show="periodosFiltrados.length">
                <template x-for="(p, i) in periodosFiltrados" :key="p.id">
                    <a :href="p.url" class="nom-fila group flex items-center gap-4 p-4 hover:bg-teal-50/30 transition" :class="p._saliendo && 'nom-sale'"
                       :style="'animation-delay:' + Math.min(i * 30, 300) + 'ms'">
                        <span class="w-11 h-11 rounded-2xl flex flex-col items-center justify-center shrink-0 text-[9px] font-black leading-tight" :class="colorTipo(p.tipo)">
                            <i class="bi text-sm" :class="p.tipo === 'ESPECIAL' ? 'bi-star-fill' : 'bi-calendar2-range'"></i>
                            <span x-text="etiquetaTipo(p.tipo).slice(0, 4)"></span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-black text-slate-800" x-text="rango(p.inicio, p.fin)"></p>
                                <span class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full" :class="chip(p.estado)">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="punto(p.estado)"></span><span x-text="p.estado"></span>
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-1.5">
                                <div class="flex-1 max-w-[180px] h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-700" :style="'width:' + (p.empleados ? p.pagados / p.empleados * 100 : 0) + '%'"></div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500" x-text="p.pagados + ' de ' + p.empleados + ' pagados'"></span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-black text-slate-900 tabular-nums" x-text="dinero(p.neto)"></p>
                            <p x-show="p.pendiente > 0" class="text-[10px] font-bold text-amber-600 tabular-nums" x-text="'Faltan ' + dinero(p.pendiente)"></p>
                        </div>
                        @if ($puedeEliminar)
                            <button type="button" @click.prevent.stop="eliminar(p)" title="Eliminar periodo"
                                    class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center shrink-0 transition">
                                <i class="bi bi-trash-fill text-[11px]"></i>
                            </button>
                        @endif
                        <i class="bi bi-chevron-right text-slate-300 group-hover:text-teal-600 transition"></i>
                    </a>
                </template>
            </div>

            <div x-show="!periodosFiltrados.length" x-cloak class="py-16 text-center px-6">
                <span class="inline-flex w-16 h-16 rounded-3xl bg-teal-50 text-teal-500 items-center justify-center text-3xl mb-3"><i class="bi bi-cash-coin"></i></span>
                <p class="text-sm font-black text-slate-600" x-text="periodos.length ? 'No hay periodos con este filtro' : 'Aún no hay pagos registrados'"></p>
                <p x-show="!periodos.length" class="text-xs text-slate-400 mt-1">Usa “Generar” junto a un empleado o “Nuevo pago”.</p>
            </div>
        </section>
    </div>

    {{-- ===================== MODAL: NUEVO PAGO ===================== --}}
    @if ($puedeCrear)
    <template x-teleport="body">
        <div x-show="modalNuevo" x-cloak @keydown.escape.window="modalNuevo = false"
             class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4" x-transition.opacity>
            <form method="POST" action="{{ route('personal.nomina.store') }}" @click.outside="modalNuevo = false" @submit="if (!seleccion.length) { $event.preventDefault(); avisoNomina('Elige al menos un empleado.', 'warning'); }"
                  class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-2xl max-h-[94vh] flex flex-col overflow-hidden"
                  x-show="modalNuevo" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                @csrf
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3 shrink-0">
                    <span class="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center"><i class="bi bi-calendar-plus"></i></span>
                    <div class="flex-1">
                        <h3 class="text-base font-black text-slate-800">Nuevo pago de nómina</h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Elige el periodo y a quién se le paga</p>
                    </div>
                    <button type="button" @click="modalNuevo = false" class="w-8 h-8 rounded-xl hover:bg-slate-100 text-slate-400"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="p-6 overflow-y-auto space-y-5 text-xs">
                    {{-- Tipo --}}
                    <div>
                        <label class="nom-label">Tipo de pago</label>
                        <input type="hidden" name="tipo" :value="nuevo.tipo">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="t in [['SEMANAL','bi-calendar-week','Cada semana'],['QUINCENAL','bi-calendar2-range','Cada 15 días'],['MENSUAL','bi-calendar3','Cada mes'],['ESPECIAL','bi-star-fill','Adelanto, bono…']]" :key="t[0]">
                                <button type="button" @click="elegirTipo(t[0], true)"
                                        class="p-3 rounded-2xl border-2 text-center transition-all"
                                        :class="nuevo.tipo === t[0] ? 'border-teal-500 bg-teal-50 text-teal-800' : 'border-slate-200 text-slate-500 hover:border-teal-200'">
                                    <i class="bi text-lg" :class="t[1]"></i>
                                    <p class="text-[11px] font-black mt-1" x-text="etiquetaTipo(t[0])"></p>
                                    <p class="text-[9px] font-semibold opacity-70" x-text="t[2]"></p>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Fechas --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div><label class="nom-label">Del *</label><input type="date" name="fecha_inicio" x-model="nuevo.inicio" required class="nom-input"></div>
                        <div><label class="nom-label">Al *</label><input type="date" name="fecha_fin" x-model="nuevo.fin" :min="nuevo.inicio" required class="nom-input"></div>
                        <div><label class="nom-label">Se paga el</label><input type="date" name="fecha_pago" x-model="nuevo.pago" :min="nuevo.inicio" class="nom-input"></div>
                    </div>

                    {{-- Empleados --}}
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <label class="nom-label !mb-0">Empleados incluidos <span class="text-teal-700" x-text="'(' + seleccion.length + ')'"></span></label>
                            <div class="flex gap-1.5">
                                <button type="button" x-show="nuevo.tipo !== 'ESPECIAL'" @click="seleccionarPorTipo(nuevo.tipo)" class="text-[10px] font-black px-2 py-1 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100" x-text="'Solo ' + etiquetaTipo(nuevo.tipo).toLowerCase() + 'es'"></button>
                                <button type="button" @click="seleccion = empleados.map(e => e.id)" class="text-[10px] font-black px-2 py-1 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200">Todos</button>
                                <button type="button" @click="seleccion = []" class="text-[10px] font-black px-2 py-1 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200">Ninguno</button>
                            </div>
                        </div>
                        <div class="relative mb-2">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px]"></i>
                            <input type="search" x-model="buscarEmpleado" placeholder="Buscar empleado…" class="nom-input !pl-8">
                        </div>
                        <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 max-h-60 overflow-y-auto">
                            <template x-for="e in empleadosModal" :key="e.id">
                                <label class="flex items-center gap-3 px-3.5 py-2.5 cursor-pointer hover:bg-slate-50 transition" :class="seleccion.includes(e.id) && 'bg-teal-50/60'">
                                    <input type="checkbox" :value="e.id" x-model.number="seleccion" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-xs font-black text-slate-800 truncate" x-text="e.nombre"></span>
                                        <span class="text-[10px] font-semibold text-slate-400" x-text="e.puesto || 'Personal'"></span>
                                    </span>
                                    <span class="text-[9px] font-black px-1.5 py-0.5 rounded-md shrink-0" :class="colorTipo(e.tipo_pago)" x-text="etiquetaTipo(e.tipo_pago)"></span>
                                    <span class="w-20 text-right text-[11px] font-black tabular-nums shrink-0" :class="e.salario > 0 ? 'text-slate-600' : 'text-amber-600'"
                                          x-text="e.salario > 0 ? dinero(e.salario) + '/d' : 'Sin salario'"></span>
                                </label>
                            </template>
                            <p x-show="!empleadosModal.length" class="p-4 text-center text-slate-400 font-semibold">Sin resultados</p>
                        </div>
                        <template x-for="id in seleccion" :key="'h' + id"><input type="hidden" name="personal_ids[]" :value="id"></template>
                        <p x-show="hayMezcla" x-cloak class="mt-2 text-[10px] font-bold text-amber-700"><i class="bi bi-info-circle"></i> Incluiste empleados con otro tipo de pago; se les pagará con estas fechas.</p>
                    </div>

                    {{-- Resumen --}}
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 grid grid-cols-3 gap-2 text-center">
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase">Días</p><p class="text-sm font-black text-slate-800" x-text="nuevo.tipo === 'ESPECIAL' ? '—' : diasNuevo"></p></div>
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase">Empleados</p><p class="text-sm font-black text-slate-800" x-text="seleccion.length"></p></div>
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase">Sueldo estimado</p><p class="text-sm font-black text-teal-700" x-text="nuevo.tipo === 'ESPECIAL' ? 'Se captura' : dinero(estimado)"></p></div>
                    </div>
                    <p x-show="nuevo.tipo === 'ESPECIAL'" class="text-[10px] font-bold text-slate-500"><i class="bi bi-star"></i> En un pago especial el recibo empieza en $0: captura el monto como bono u horas extra dentro del recibo.</p>

                    <div>
                        <label class="nom-label">Notas</label>
                        <textarea name="notas" rows="2" class="nom-input resize-none" placeholder="Opcional">{{ old('notas') }}</textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2 shrink-0">
                    <button type="button" @click="modalNuevo = false" class="px-5 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-100">Cancelar</button>
                    <button type="submit" :disabled="!nuevo.inicio || !nuevo.fin || !seleccion.length"
                            class="px-5 py-2.5 rounded-2xl bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white text-xs font-black shadow-lg shadow-teal-600/20 flex items-center gap-2">
                        <i class="bi bi-check2-circle"></i> <span x-text="seleccion.length === 1 ? 'Crear recibo' : 'Crear ' + seleccion.length + ' recibos'"></span>
                    </button>
                </div>
            </form>
        </div>
    </template>
    @endif
</div>

<style>
    .nom-input { width: 100%; padding: .6rem .85rem; border-radius: 1rem; border: 1px solid rgb(226 232 240); font-size: .75rem; font-weight: 600; outline: none; transition: all .15s; background: #fff; }
    .nom-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20,184,166,.12); }
    .nom-label { display: block; font-size: .65rem; font-weight: 800; text-transform: uppercase; color: rgb(71 85 105); margin-bottom: .3rem; }
    .nom-aparece { animation: nomAparece .5s cubic-bezier(.16,1,.3,1) both; }
    .nom-fila    { animation: nomAparece .4s cubic-bezier(.16,1,.3,1) both; }
    .nom-sale    { animation: nomSale .3s ease-in forwards !important; }
    .nom-latido  { display: inline-block; animation: nomLatido 1.6s ease-in-out infinite; }
    @keyframes nomAparece { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    @keyframes nomSale    { to { opacity: 0; transform: translateX(30px); } }
    @keyframes nomLatido  { 0%,100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @media (prefers-reduced-motion: reduce) { .nom-aparece, .nom-fila, .nom-latido { animation: none !important; } }
</style>

<script>
    const URL_NOMINA  = @js(url('personal/nomina'));
    const CSRF_NOMINA = @js(csrf_token());

    function avisoNomina(mensaje, icono = 'success') {
        if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
    }

    function moduloNomina() {
        const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const fecha = f => new Date(f + 'T00:00:00');

        return {
            periodos: @json($periodosJs),
            empleados: @json($empleados ?? []),
            errores: @json($errors->all()),
            filtro: 'Todos',
            filtroTipo: 'TODOS',
            modalNuevo: @js($errors->any()),
            buscarEmpleado: '',
            seleccion: @json(array_map('intval', (array) old('personal_ids', []))),
            nuevo: {
                tipo:   @js(old('tipo', 'QUINCENAL')),
                inicio: @js(old('fecha_inicio', '')),
                fin:    @js(old('fecha_fin', '')),
                pago:   @js(old('fecha_pago', '')),
            },

            init() {
                if (!this.nuevo.inicio) this.elegirTipo(this.nuevo.tipo, false);
                if (this.errores.length) setTimeout(() => avisoNomina(this.errores[0], 'error'), 300);
            },

            // ----- Listas -----
            get empleadosFiltrados() { return this.filtroTipo === 'TODOS' ? this.empleados : this.empleados.filter(e => e.tipo_pago === this.filtroTipo); },
            get periodosFiltrados() {
                if (this.filtro === 'Pendientes') return this.periodos.filter(p => p.pagados < p.empleados);
                if (this.filtro === 'Pagado') return this.periodos.filter(p => p.empleados && p.pagados === p.empleados);
                return this.periodos;
            },
            get empleadosModal() {
                const q = this.normalizar(this.buscarEmpleado);
                const lista = q ? this.empleados.filter(e => this.normalizar(e.nombre + ' ' + e.puesto).includes(q)) : this.empleados;
                // Primero los del tipo elegido
                return [...lista].sort((a, b) => (b.tipo_pago === this.nuevo.tipo) - (a.tipo_pago === this.nuevo.tipo) || a.nombre.localeCompare(b.nombre));
            },
            get hayMezcla() {
                return this.nuevo.tipo !== 'ESPECIAL' && this.empleados.some(e => this.seleccion.includes(e.id) && e.tipo_pago !== this.nuevo.tipo);
            },

            // ----- Abrir el modal -----
            abrirNuevo() { this.elegirTipo(this.nuevo.tipo || 'QUINCENAL', true); this.buscarEmpleado = ''; this.modalNuevo = true; },
            abrirNuevoTipo(tipo) { this.elegirTipo(tipo, true); this.buscarEmpleado = ''; this.modalNuevo = true; },
            abrirNuevoEmpleado(e) {
                this.nuevo.tipo = e.tipo_pago;
                this.nuevo.inicio = e.prox_ini;
                this.nuevo.fin = e.prox_fin;
                this.nuevo.pago = e.prox_fin;
                this.seleccion = [e.id];
                this.buscarEmpleado = '';
                this.modalNuevo = true;
            },

            // Propone fechas del periodo actual y selecciona a los empleados de ese tipo
            elegirTipo(tipo, seleccionar) {
                this.nuevo.tipo = tipo;
                const hoy = new Date();
                let ini, fin;
                if (tipo === 'SEMANAL') {
                    const dia = (hoy.getDay() + 6) % 7;
                    ini = new Date(hoy); ini.setDate(hoy.getDate() - dia);
                    fin = new Date(ini); fin.setDate(ini.getDate() + 6);
                } else if (tipo === 'QUINCENAL') {
                    const primera = hoy.getDate() <= 15;
                    ini = new Date(hoy.getFullYear(), hoy.getMonth(), primera ? 1 : 16);
                    fin = primera ? new Date(hoy.getFullYear(), hoy.getMonth(), 15) : new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
                } else if (tipo === 'MENSUAL') {
                    ini = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                    fin = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
                } else {
                    ini = fin = hoy;
                }
                this.nuevo.inicio = iso(ini);
                this.nuevo.fin = iso(fin);
                this.nuevo.pago = iso(fin);
                if (seleccionar) this.seleccionarPorTipo(tipo);
            },
            seleccionarPorTipo(tipo) { this.seleccion = tipo === 'ESPECIAL' ? [] : this.empleados.filter(e => e.tipo_pago === tipo).map(e => e.id); },

            get diasNuevo() { return this.diasEntre(this.nuevo.inicio, this.nuevo.fin); },
            get estimado() {
                return this.empleados.filter(e => this.seleccion.includes(e.id)).reduce((s, e) => s + e.salario * this.diasNuevo, 0);
            },

            // ----- Vencimientos -----
            vencimiento(e) {
                const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
                const d = Math.round((fecha(e.prox_fin) - hoy) / 86400000);
                if (d < 0)  return { texto: 'Atrasado ' + Math.abs(d) + ' día(s)', clase: 'text-rose-600' };
                if (d === 0) return { texto: 'Se paga hoy', clase: 'text-amber-600' };
                if (d <= 3) return { texto: 'Se paga en ' + d + ' día(s)', clase: 'text-amber-600' };
                return { texto: 'Se paga en ' + d + ' días', clase: 'text-slate-400' };
            },

            eliminar(p) {
                Swal.fire({
                    title: '¿Eliminar periodo?',
                    html: `Se borrará el periodo <b>${this.rango(p.inicio, p.fin)}</b> y sus ${p.empleados} recibo(s).`
                        + (p.pagados ? `<br><span style="font-size:12px;color:#b45309">Incluye <b>${p.pagados} recibo(s) ya pagado(s)</b>; se perderá su registro de pago.</span>` : '')
                        + '<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>',
                    icon: 'warning', showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                    showLoaderOnConfirm: true, customClass: { popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try {
                            const r = await fetch(`${URL_NOMINA}/${p.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_NOMINA, 'Accept': 'application/json' } });
                            const data = await r.json().catch(() => ({}));
                            if (!r.ok || data.success === false) throw new Error(data.message || 'No se pudo eliminar.');
                            return data;
                        } catch (e) { Swal.showValidationMessage(e.message); }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(res => {
                    if (!res.isConfirmed) return;
                    p._saliendo = true;
                    setTimeout(() => { this.periodos = this.periodos.filter(x => x.id !== p.id); }, 300);
                    avisoNomina('Periodo eliminado.');
                });
            },

            // ----- Formatos -----
            diasEntre(a, b) {
                if (!a || !b) return 0;
                return Math.max(0, Math.round((fecha(b) - fecha(a)) / 86400000) + 1);
            },
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
            iniciales(t) { return (t || '?').trim().split(/\s+/).slice(0, 2).map(x => x[0]).join('').toUpperCase(); },
            fechaCorta(f) { return f ? fecha(f).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
            dinero(v) { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0); },
            rango(a, b) {
                const o = { day: 'numeric', month: 'short' };
                if (a === b) return fecha(a).toLocaleDateString('es-MX', { ...o, year: 'numeric' });
                return fecha(a).toLocaleDateString('es-MX', o) + ' – ' + fecha(b).toLocaleDateString('es-MX', { ...o, year: 'numeric' });
            },
            etiquetaTipo(t) { return { SEMANAL: 'Semanal', QUINCENAL: 'Quincenal', MENSUAL: 'Mensual', ESPECIAL: 'Especial' }[t] || t; },
            colorTipo(t) { return { SEMANAL: 'bg-sky-50 text-sky-700', QUINCENAL: 'bg-teal-50 text-teal-700', MENSUAL: 'bg-violet-50 text-violet-700', ESPECIAL: 'bg-amber-50 text-amber-700' }[t] || 'bg-slate-100 text-slate-600'; },
            chip(e)  { return { Abierto: 'bg-amber-50 text-amber-700', Cerrado: 'bg-slate-100 text-slate-600', Pagado: 'bg-emerald-50 text-emerald-700' }[e] || 'bg-slate-100 text-slate-600'; },
            punto(e) { return { Abierto: 'bg-amber-400', Cerrado: 'bg-slate-400', Pagado: 'bg-emerald-500' }[e] || 'bg-slate-400'; },
        };
    }
</script>
@endsection