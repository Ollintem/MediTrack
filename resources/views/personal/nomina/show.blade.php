@extends('layouts.admin')

@section('content')
@php
    $usuario     = auth()->user();
    $puede       = fn ($accion) => !$usuario || !method_exists($usuario, 'tienePermiso') || $usuario->tienePermiso('Personal', $accion);
    $puedeEditar   = $puede('editar');
    $puedeEliminar = $puede('eliminar');

    $etiquetas = ['SEMANAL' => 'semanal', 'QUINCENAL' => 'quincenal', 'MENSUAL' => 'mensual', 'ESPECIAL' => 'especial'];
    $rango = $periodo->fecha_inicio->equalTo($periodo->fecha_fin)
        ? $periodo->fecha_inicio->format('d/m/Y')
        : $periodo->fecha_inicio->format('d/m/Y') . ' al ' . $periodo->fecha_fin->format('d/m/Y');
@endphp

<div class="space-y-6" x-data="periodoNomina()">

    @include('personal._pestanas', ['activa' => 'nomina'])

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="nom-aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="relative p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="min-w-0 space-y-2">
                <a href="{{ route('personal.nomina.index') }}" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-200 hover:text-white transition">
                    <i class="bi bi-arrow-left"></i> Volver a nómina
                </a>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight flex flex-wrap items-center gap-3">
                    {{ $periodo->tipo === 'ESPECIAL' ? 'Pago especial' : 'Nómina ' . ($etiquetas[$periodo->tipo] ?? strtolower($periodo->tipo)) }}
                    <i class="bi bi-heart-pulse-fill text-emerald-300 text-2xl nom-latido"></i>
                </h1>
                <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                    <span class="px-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20"><i class="bi bi-calendar-range"></i> {{ $rango }}</span>
                    @if ($periodo->notas)
                        <span class="px-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20 max-w-xs truncate" title="{{ $periodo->notas }}"><i class="bi bi-sticky"></i> {{ $periodo->notas }}</span>
                    @endif
                    <span class="px-3 py-1 rounded-full ring-1" :class="{ 'bg-amber-400/20 text-amber-100 ring-amber-300/40': estado === 'Abierto', 'bg-white/15 ring-white/30': estado === 'Cerrado', 'bg-emerald-400/25 text-emerald-100 ring-emerald-300/40': estado === 'Pagado' }">
                        <i class="bi" :class="{ 'bi-unlock-fill': estado === 'Abierto', 'bi-lock-fill': estado === 'Cerrado', 'bi-check-circle-fill': estado === 'Pagado' }"></i>
                        <span x-text="estado"></span>
                    </span>
                </div>
                {{-- Avance de pagos --}}
                <div class="flex items-center gap-3 pt-1 max-w-sm">
                    <div class="flex-1 h-2 rounded-full bg-white/15 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-300 transition-all duration-700" :style="'width:' + avance + '%'"></div>
                    </div>
                    <span class="text-[11px] font-black text-teal-100 tabular-nums" x-text="pagados.length + ' de ' + recibos.length + ' pagados'"></span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
            @if ($puedeEditar)
                <div class="flex flex-wrap gap-2" x-show="estado !== 'Pagado'">
                    <form method="POST" action="{{ route('personal.nomina.estado', $periodo) }}" x-ref="formEstado">
                        @csrf
                        <input type="hidden" name="estado" :value="estado === 'Abierto' ? 'Cerrado' : 'Abierto'">
                        <button type="button" @click="confirmarEstado()"
                                class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 ring-1 ring-white/20 text-xs font-bold flex items-center gap-2 transition">
                            <i class="bi" :class="estado === 'Abierto' ? 'bi-lock' : 'bi-unlock'"></i>
                            <span x-text="estado === 'Abierto' ? 'Cerrar edición' : 'Reabrir'"></span>
                        </button>
                    </form>
                    <button type="button" x-show="pendientes.length" @click="abrirPago(pendientes)"
                            class="px-4 py-2.5 rounded-2xl bg-white text-teal-900 hover:bg-emerald-50 text-xs font-black shadow-lg shadow-teal-950/25 flex items-center gap-2 transition">
                        <i class="bi bi-cash-coin"></i> <span x-text="'Pagar a todos (' + pendientes.length + ')'"></span>
                    </button>
                </div>
            @endif
            @if ($puedeEliminar)
                <button type="button" @click="eliminarPeriodo()" title="Eliminar este periodo"
                        class="px-4 py-2.5 rounded-2xl bg-rose-500/90 hover:bg-rose-500 text-white text-xs font-black flex items-center gap-2 transition">
                    <i class="bi bi-trash-fill"></i> Eliminar periodo
                </button>
            @endif
            </div>
        </div>
    </div>

    {{-- ===================== TOTALES ===================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <template x-for="(t, i) in tarjetas" :key="t.titulo">
            <div class="nom-aparece bg-white rounded-2xl border border-slate-200 p-4" :style="'animation-delay:' + (60 + i * 60) + 'ms'">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold text-slate-500" x-text="t.titulo"></p>
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center" :class="t.color"><i class="bi" :class="t.icono"></i></span>
                </div>
                <p class="text-2xl font-black mt-1 tabular-nums truncate text-slate-800" x-text="t.valor"></p>
            </div>
        </template>
    </div>

    {{-- ===================== RECIBOS ===================== --}}
    <div class="nom-aparece bg-white rounded-3xl border border-slate-200 overflow-hidden" style="animation-delay:280ms">
        <div class="p-5 sm:p-6 border-b border-slate-100 space-y-3">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-receipt"></i></span>
                    <div>
                        <h3 class="text-base font-black text-slate-800">Recibos por empleado</h3>
                        <p class="text-[11px] font-semibold text-slate-400">Cada empleado se paga por separado</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex p-1 rounded-2xl bg-slate-100 text-[11px] font-bold">
                        <template x-for="f in [['Todos','Todos'], ['Pendiente','Pendientes'], ['Pagado','Pagados']]" :key="f[0]">
                            <button type="button" @click="filtro = f[0]" :class="filtro === f[0] ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500'"
                                    class="px-3 py-1.5 rounded-xl transition" x-text="f[1]"></button>
                        </template>
                    </div>
                    <div class="relative w-full sm:w-56">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="search" x-model.debounce.150ms="busqueda" placeholder="Buscar empleado…"
                               class="w-full pl-9 pr-3 py-2 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-semibold focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 outline-none transition-all">
                    </div>
                    <button type="button" @click="exportarCsv()" title="Descargar en Excel (CSV)"
                            class="h-9 px-3 rounded-2xl border border-slate-200 text-slate-600 hover:border-teal-300 hover:text-teal-700 text-xs font-bold flex items-center gap-1.5 transition">
                        <i class="bi bi-file-earmark-spreadsheet"></i><span class="hidden sm:inline">Exportar</span>
                    </button>
                </div>
            </div>

            {{-- Barra de acciones --}}
            @if ($puedeEditar)
                <div class="flex flex-wrap items-center gap-2">
                    <div x-show="marcados.length" x-cloak x-transition.opacity class="flex items-center gap-2 px-3 py-1.5 rounded-2xl bg-teal-600 text-white text-xs font-bold">
                        <span x-text="marcados.length + ' seleccionado(s) · ' + dinero(sumaNeto(recibosMarcados))"></span>
                        <button type="button" @click="abrirPago(recibosMarcados)" class="px-3 py-1 rounded-xl bg-white text-teal-800 font-black hover:bg-emerald-50"><i class="bi bi-cash-coin"></i> Pagar</button>
                        <button type="button" @click="marcados = []" class="px-2 py-1 rounded-xl hover:bg-white/15" title="Quitar selección"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <form x-show="estado === 'Abierto' && disponibles.length" method="POST" action="{{ route('personal.nomina.agregar', $periodo) }}" class="ml-auto flex items-center gap-2">
                        @csrf
                        <select name="personal_id" required class="h-9 pl-3 pr-8 rounded-2xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:border-teal-500 outline-none">
                            <option value="">Agregar empleado…</option>
                            <template x-for="d in disponibles" :key="d.id">
                                <option :value="d.id" x-text="d.nombre + ' · ' + etiquetaTipo(d.tipo_pago)"></option>
                            </template>
                        </select>
                        <button class="h-9 px-3 rounded-2xl bg-teal-50 hover:bg-teal-100 text-teal-800 text-xs font-black flex items-center gap-1"><i class="bi bi-person-plus"></i> Agregar</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto" x-show="filtrados.length">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 text-[10px] text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        @if ($puedeEditar)
                            <th class="py-3.5 pl-5 w-8">
                                <input type="checkbox" :checked="todosMarcados" @change="alternarTodos()" :disabled="!pendientesFiltrados.length"
                                       class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" title="Seleccionar pendientes">
                            </th>
                        @endif
                        <th class="py-3.5 px-4">Empleado</th>
                        <th class="py-3.5 px-4 text-center hidden md:table-cell">Días</th>
                        <th class="py-3.5 px-4 text-right hidden lg:table-cell">Percepciones</th>
                        <th class="py-3.5 px-4 text-right hidden lg:table-cell">Deducciones</th>
                        <th class="py-3.5 px-4 text-right">Neto</th>
                        <th class="py-3.5 px-4">Pago</th>
                        <th class="py-3.5 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                    <template x-for="(r, i) in filtrados" :key="r.id">
                        <tr class="nom-fila transition-colors" :style="'animation-delay:' + Math.min(i * 25, 350) + 'ms'"
                            :class="[r._destello && 'nom-destello', r._saliendo && 'nom-sale', marcados.includes(r.id) ? 'bg-teal-50/60' : 'hover:bg-slate-50/60']">
                            @if ($puedeEditar)
                                <td class="py-3.5 pl-5">
                                    <input type="checkbox" x-show="r.estado !== 'Pagado'" :value="r.id" x-model.number="marcados"
                                           class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                    <i x-show="r.estado === 'Pagado'" class="bi bi-check-circle-fill text-emerald-500"></i>
                                </td>
                            @endif
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 rounded-2xl flex items-center justify-center text-[11px] font-black shrink-0"
                                          :class="r.estado === 'Pagado' ? 'bg-emerald-50 text-emerald-700' : 'bg-teal-50 text-teal-700'" x-text="iniciales(r.empleado)"></span>
                                    <div class="min-w-0">
                                        <a :href="r.ficha" class="block font-black text-slate-900 hover:text-teal-700 truncate max-w-[200px]" x-text="r.empleado" title="Ver su historial de nómina"></a>
                                        <p class="text-[10px] font-bold truncate" :class="r.salario_diario > 0 ? 'text-slate-400' : 'text-amber-600'"
                                           x-text="r.salario_diario > 0 ? dinero(r.salario_diario) + '/día · ' + etiquetaTipo(r.tipo_pago) : 'Sin salario registrado'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center tabular-nums hidden md:table-cell" x-text="r.dias_trabajados"></td>
                            <td class="py-3.5 px-4 text-right tabular-nums hidden lg:table-cell" x-text="dinero(r.total_percepciones)"></td>
                            <td class="py-3.5 px-4 text-right tabular-nums text-rose-600 hidden lg:table-cell" x-text="'-' + dinero(r.total_deducciones)"></td>
                            <td class="py-3.5 px-4 text-right tabular-nums font-black text-slate-900" x-text="dinero(r.neto)"></td>
                            <td class="py-3.5 px-4">
                                <template x-if="r.estado === 'Pagado'">
                                    <div>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700"><i class="bi bi-check2"></i> Pagado</span>
                                        <p class="text-[10px] font-semibold text-slate-400 mt-0.5" x-text="fechaCorta(r.fecha_pago) + ' · ' + metodoTexto(r.metodo_pago)"></p>
                                    </div>
                                </template>
                                <template x-if="r.estado !== 'Pagado'">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-50 text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Pendiente</span>
                                </template>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    @if ($puedeEditar)
                                        <button type="button" x-show="r.estado !== 'Pagado'" @click="abrirPago([r])" title="Registrar pago"
                                                class="h-8 px-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black flex items-center gap-1 transition">
                                            <i class="bi bi-cash"></i><span class="hidden xl:inline">Pagar</span>
                                        </button>
                                    @endif
                                    <button type="button" @click="editar(r)" :title="editable(r) ? 'Editar recibo' : 'Ver detalle'"
                                            class="w-8 h-8 rounded-xl flex items-center justify-center transition"
                                            :class="editable(r) ? 'bg-sky-50 text-sky-600 hover:bg-sky-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">
                                        <i class="bi text-[11px]" :class="editable(r) ? 'bi-pencil-fill' : 'bi-eye-fill'"></i>
                                    </button>
                                    <a :href="URL_RECIBOS + '/' + r.id + '/pdf'" target="_blank" title="Recibo en PDF"
                                       class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition">
                                        <i class="bi bi-file-earmark-pdf-fill text-[12px]"></i>
                                    </a>
                                    @if ($puedeEditar)
                                        <button type="button" x-show="r.estado === 'Pagado'" @click="revertir(r)" title="Revertir pago"
                                                class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition">
                                            <i class="bi bi-arrow-counterclockwise text-[12px]"></i>
                                        </button>
                                    @endif
                                    @if ($puedeEditar || $puedeEliminar)
                                        <button type="button" x-show="puedeBorrar(r)" @click="quitar(r)" title="Eliminar recibo"
                                                class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center transition">
                                            <i class="bi bi-trash-fill text-[11px]"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-slate-50 border-t-2 border-slate-200 text-xs font-black text-slate-800">
                    <tr>
                        @if ($puedeEditar)<td></td>@endif
                        <td class="py-3.5 px-4">Totales</td>
                        <td class="hidden md:table-cell"></td>
                        <td class="py-3.5 px-4 text-right tabular-nums hidden lg:table-cell" x-text="dinero(totales.percepciones)"></td>
                        <td class="py-3.5 px-4 text-right tabular-nums text-rose-600 hidden lg:table-cell" x-text="'-' + dinero(totales.deducciones)"></td>
                        <td class="py-3.5 px-4 text-right tabular-nums text-teal-700" x-text="dinero(totales.neto)"></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div x-show="!filtrados.length" x-cloak class="py-16 text-center">
            <span class="inline-flex w-16 h-16 rounded-3xl bg-slate-100 text-slate-400 items-center justify-center text-3xl mb-3"><i class="bi bi-people"></i></span>
            <p class="text-sm font-black text-slate-600" x-text="recibos.length ? 'Ningún recibo coincide' : 'Este periodo no tiene empleados'"></p>
        </div>
    </div>

    {{-- ===================== MODAL: REGISTRAR PAGO ===================== --}}
    <template x-teleport="body">
        <div x-show="modalPago" x-cloak @keydown.escape.window="modalPago = false"
             class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4" x-transition.opacity>
            <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-lg max-h-[94vh] flex flex-col overflow-hidden" @click.outside="modalPago = false" x-show="modalPago"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3 shrink-0">
                    <span class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center"><i class="bi bi-cash-coin text-lg"></i></span>
                    <div class="flex-1">
                        <h3 class="text-base font-black text-slate-800" x-text="pago.recibos.length === 1 ? 'Pagar a ' + pago.recibos[0].empleado : 'Pagar a ' + pago.recibos.length + ' empleados'"></h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Se registrará como pagado cada recibo</p>
                    </div>
                    <button type="button" @click="modalPago = false" class="w-8 h-8 rounded-xl hover:bg-slate-100 text-slate-400"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="p-6 overflow-y-auto space-y-5 text-xs">
                    <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 max-h-48 overflow-y-auto">
                        <template x-for="r in pago.recibos" :key="'p' + r.id">
                            <div class="flex items-center justify-between px-3.5 py-2">
                                <span class="font-bold text-slate-700 truncate pr-2" x-text="r.empleado"></span>
                                <span class="font-black tabular-nums" :class="r.neto < 0 ? 'text-rose-600' : 'text-slate-800'" x-text="dinero(r.neto)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block"><span class="nom-label">Fecha de pago *</span><input type="date" x-model="pago.fecha" class="nom-input"></label>
                        <label class="block"><span class="nom-label">Referencia</span><input type="text" x-model="pago.referencia" maxlength="100" class="nom-input" placeholder="Folio, núm. de transferencia…"></label>
                    </div>

                    <div>
                        <span class="nom-label">Forma de pago *</span>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="m in [['EFECTIVO','bi-cash-stack'],['TRANSFERENCIA','bi-bank'],['CHEQUE','bi-journal-check']]" :key="m[0]">
                                <button type="button" @click="pago.metodo = m[0]"
                                        class="p-3 rounded-2xl border-2 text-center transition"
                                        :class="pago.metodo === m[0] ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-slate-200 text-slate-500 hover:border-emerald-200'">
                                    <i class="bi text-lg" :class="m[1]"></i>
                                    <p class="text-[10px] font-black mt-1" x-text="metodoTexto(m[0])"></p>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-900 text-white p-4 flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-300 uppercase">Total a pagar</span>
                        <span class="text-xl font-black tabular-nums" x-text="dinero(sumaNeto(pago.recibos))"></span>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2 shrink-0">
                    <button type="button" @click="modalPago = false" class="px-5 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-100">Cancelar</button>
                    <button type="button" @click="confirmarPago()" :disabled="guardando || !pago.fecha || !pago.metodo"
                            class="px-5 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-black shadow-lg shadow-emerald-600/20 flex items-center gap-2">
                        <span x-show="guardando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                        <i x-show="!guardando" class="bi bi-check2-circle"></i> Registrar pago
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ===================== MODAL: RECIBO ===================== --}}
    <template x-teleport="body">
        <div x-show="modal" x-cloak @keydown.escape.window="modal = false"
             class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4" x-transition.opacity>
            <div class="bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-2xl max-h-[94vh] flex flex-col overflow-hidden"
                 @click.outside="modal = false" x-show="modal"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3 shrink-0">
                    <span class="w-11 h-11 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-black text-sm" x-text="iniciales(form.empleado)"></span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-black text-slate-800 truncate" x-text="form.empleado"></h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Recibo · {{ $rango }}</p>
                    </div>
                    <span x-show="form.estado === 'Pagado'" class="text-[10px] font-black px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700"><i class="bi bi-check2"></i> Pagado</span>
                    <button type="button" @click="modal = false" class="w-8 h-8 rounded-xl hover:bg-slate-100 text-slate-400"><i class="bi bi-x-lg"></i></button>
                </div>

                <fieldset class="p-6 overflow-y-auto space-y-5 text-xs" :disabled="!editable(form)">
                    <p x-show="!editable(form)" class="flex items-center gap-2 p-3 rounded-2xl bg-slate-100 text-slate-600 font-bold">
                        <i class="bi bi-lock-fill"></i>
                        <span x-text="form.estado === 'Pagado' ? 'Recibo pagado el ' + fechaCorta(form.fecha_pago) + ' (' + metodoTexto(form.metodo_pago) + (form.referencia ? ', ref. ' + form.referencia : '') + '). Revierte el pago para editarlo.' : 'El periodo está cerrado; reábrelo para editar.'"></span>
                    </p>

                    <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100 space-y-3">
                        <h4 class="text-[11px] font-black uppercase tracking-wider text-emerald-800 flex items-center gap-1.5"><i class="bi bi-plus-circle-fill"></i> Percepciones</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label class="block"><span class="nom-label">Salario diario</span><input type="number" step="0.01" min="0" x-model.number="form.salario_diario" class="nom-input"></label>
                            <label class="block"><span class="nom-label">Días</span><input type="number" step="0.5" min="0" max="31" x-model.number="form.dias_trabajados" class="nom-input"></label>
                            <label class="block"><span class="nom-label">Horas extra ($)</span><input type="number" step="0.01" min="0" x-model.number="form.horas_extra" class="nom-input"></label>
                            <label class="block"><span class="nom-label">Bonos ($)</span><input type="number" step="0.01" min="0" x-model.number="form.bonos" class="nom-input"></label>
                        </div>
                        <label class="flex items-center gap-2 text-[11px] font-bold text-emerald-800 cursor-pointer" x-show="editable(form)">
                            <input type="checkbox" x-model="form.guardar_salario" class="rounded border-emerald-300 text-teal-600 focus:ring-teal-500">
                            Guardar como salario del empleado para próximos pagos
                        </label>
                    </div>

                    <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-100 space-y-3">
                        <h4 class="text-[11px] font-black uppercase tracking-wider text-rose-800 flex items-center gap-1.5"><i class="bi bi-dash-circle-fill"></i> Deducciones</h4>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="block"><span class="nom-label">ISR ($)</span><input type="number" step="0.01" min="0" x-model.number="form.isr" class="nom-input"></label>
                            <label class="block"><span class="nom-label">IMSS ($)</span><input type="number" step="0.01" min="0" x-model.number="form.imss" class="nom-input"></label>
                            <label class="block"><span class="nom-label">Otras ($)</span><input type="number" step="0.01" min="0" x-model.number="form.otras_deducciones" class="nom-input"></label>
                        </div>
                        <p class="text-[10px] text-rose-700/80 font-semibold"><i class="bi bi-info-circle"></i> Captura los montos calculados por tu contador o con las tablas vigentes del SAT/IMSS.</p>
                    </div>

                    <label class="block"><span class="nom-label">Notas</span><textarea x-model="form.notas" rows="2" class="nom-input resize-none" placeholder="Opcional"></textarea></label>

                    <div class="rounded-2xl bg-slate-900 text-white p-4 grid grid-cols-3 gap-3 text-center">
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase">Percepciones</p><p class="text-sm font-black tabular-nums text-emerald-300" x-text="dinero(calc.percepciones)"></p></div>
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase">Deducciones</p><p class="text-sm font-black tabular-nums text-rose-300" x-text="'-' + dinero(calc.deducciones)"></p></div>
                        <div><p class="text-[10px] font-bold text-slate-400 uppercase">Neto a pagar</p><p class="text-lg font-black tabular-nums" :class="calc.neto < 0 ? 'text-rose-400' : 'text-white'" x-text="dinero(calc.neto)"></p></div>
                    </div>
                </fieldset>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-between gap-2 shrink-0">
                    <a :href="URL_RECIBOS + '/' + form.id + '/pdf'" target="_blank" class="px-4 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-100 flex items-center gap-2">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <div class="flex gap-2">
                        <button type="button" @click="modal = false" class="px-5 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-100">Cerrar</button>
                        <button type="button" x-show="editable(form)" @click="guardar()" :disabled="guardando || calc.neto < 0"
                                class="px-5 py-2.5 rounded-2xl bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white text-xs font-black shadow-lg shadow-teal-600/20 flex items-center gap-2">
                            <span x-show="guardando" class="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                            <i x-show="!guardando" class="bi bi-check2-circle"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
    .nom-input { width: 100%; padding: .55rem .8rem; border-radius: .9rem; border: 1px solid rgb(226 232 240); font-size: .75rem; font-weight: 700; outline: none; transition: all .15s; background: #fff; }
    .nom-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20,184,166,.12); }
    .nom-input:disabled { background: rgb(248 250 252); color: rgb(100 116 139); }
    .nom-label { display: block; font-size: .65rem; font-weight: 800; text-transform: uppercase; color: rgb(100 116 139); margin-bottom: .25rem; }
    .nom-aparece { animation: nomAparece .5s cubic-bezier(.16,1,.3,1) both; }
    .nom-fila    { animation: nomAparece .4s cubic-bezier(.16,1,.3,1) both; }
    .nom-sale    { animation: nomSale .3s ease-in forwards !important; }
    .nom-destello { animation: nomDestello 1.2s ease-out; }
    .nom-latido  { display: inline-block; animation: nomLatido 1.6s ease-in-out infinite; }
    @keyframes nomAparece  { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    @keyframes nomSale     { to { opacity: 0; transform: translateX(30px); } }
    @keyframes nomDestello { from { background-color: rgb(209 250 229); } to { background-color: transparent; } }
    @keyframes nomLatido   { 0%,100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @media (prefers-reduced-motion: reduce) { .nom-aparece, .nom-fila, .nom-latido, .nom-destello { animation: none !important; } }
</style>

<script>
    const URL_RECIBOS  = @js(url('personal/nomina/recibo'));
    const URL_PAGAR    = @js(route('personal.nomina.pagar', $periodo));
    const URL_PERIODO  = @js(url('personal/nomina/' . $periodo->id));
    const CSRF_NOMINA  = @js(csrf_token());
    const PERIODO_NOM  = @js(['tipo' => $periodo->tipo, 'inicio' => $periodo->fecha_inicio->format('Y-m-d'), 'pago' => optional($periodo->fecha_pago)->format('Y-m-d')]);

    function avisoNomina(mensaje, icono = 'success') {
        if (typeof window.notificar === 'function') return window.notificar(mensaje, icono);
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 3000, showConfirmButton: false, icon: icono, title: mensaje });
    }

    function periodoNomina() {
        const n = v => Number(v) || 0;
        const hoyIso = () => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };

        async function peticion(url, metodo, cuerpo) {
            const res = await fetch(url, {
                method: metodo,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_NOMINA },
                body: cuerpo ? JSON.stringify(cuerpo) : undefined,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                const primerError = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (primerError || data.message || 'No se pudo completar la acción.'));
            }
            return data;
        }

        return {
            recibos: @json($recibos),
            disponibles: @json($disponibles ?? []),
            estado: @js($periodo->estado),
            puedeEditar: @js($puedeEditar),
            puedeEliminar: @js($puedeEliminar),
            busqueda: '',
            filtro: 'Todos',
            marcados: [],
            modal: false,
            modalPago: false,
            guardando: false,
            form: {},
            pago: { recibos: [], fecha: '', metodo: 'TRANSFERENCIA', referencia: '' },

            init() {
                @if (session('success')) setTimeout(() => avisoNomina(@js(session('success'))), 300); @endif
                @if (session('error')) setTimeout(() => avisoNomina(@js(session('error')), 'error'), 300); @endif
            },

            // ----- Listas -----
            get filtrados() {
                const q = this.normalizar(this.busqueda);
                return this.recibos.filter(r =>
                    (this.filtro === 'Todos' || (this.filtro === 'Pagado' ? r.estado === 'Pagado' : r.estado !== 'Pagado')) &&
                    (!q || this.normalizar(r.empleado + ' ' + r.puesto).includes(q)));
            },
            get pagados()    { return this.recibos.filter(r => r.estado === 'Pagado'); },
            get pendientes() { return this.recibos.filter(r => r.estado !== 'Pagado'); },
            get pendientesFiltrados() { return this.filtrados.filter(r => r.estado !== 'Pagado'); },
            get recibosMarcados() { return this.recibos.filter(r => this.marcados.includes(r.id)); },
            get todosMarcados() { return this.pendientesFiltrados.length > 0 && this.pendientesFiltrados.every(r => this.marcados.includes(r.id)); },
            alternarTodos() {
                const ids = this.pendientesFiltrados.map(r => r.id);
                this.marcados = this.todosMarcados ? this.marcados.filter(id => !ids.includes(id)) : [...new Set([...this.marcados, ...ids])];
            },
            get avance() { return this.recibos.length ? Math.round(this.pagados.length / this.recibos.length * 100) : 0; },

            get totales() {
                return this.recibos.reduce((t, r) => ({
                    percepciones: t.percepciones + n(r.total_percepciones),
                    deducciones:  t.deducciones + n(r.total_deducciones),
                    neto:         t.neto + n(r.neto),
                }), { percepciones: 0, deducciones: 0, neto: 0 });
            },
            get tarjetas() {
                return [
                    { titulo: 'Total del periodo', valor: this.dinero(this.totales.neto),           icono: 'bi-cash-stack',      color: 'bg-teal-600 text-white' },
                    { titulo: 'Ya pagado',         valor: this.dinero(this.sumaNeto(this.pagados)),  icono: 'bi-check2-circle',   color: 'bg-emerald-500 text-white' },
                    { titulo: 'Por pagar',         valor: this.dinero(this.sumaNeto(this.pendientes)), icono: 'bi-hourglass-split', color: 'bg-amber-500 text-white' },
                    { titulo: 'Empleados',         valor: this.recibos.length,                       icono: 'bi-people-fill',     color: 'bg-sky-500 text-white' },
                ];
            },
            sumaNeto(lista) { return lista.reduce((s, r) => s + n(r.neto), 0); },

            // ----- Edición del recibo -----
            editable(r) { return this.puedeEditar && this.estado === 'Abierto' && r.estado !== 'Pagado'; },
            get calc() {
                const f = this.form;
                const base = n(f.salario_diario) * n(f.dias_trabajados);
                const percepciones = base + n(f.horas_extra) + n(f.bonos);
                const deducciones = n(f.isr) + n(f.imss) + n(f.otras_deducciones);
                return { base, percepciones, deducciones, neto: percepciones - deducciones };
            },
            editar(r) { this.form = { ...r, guardar_salario: r.salario_diario <= 0 }; this.modal = true; },

            async guardar() {
                this.guardando = true;
                try {
                    const f = this.form;
                    const data = await peticion(`${URL_RECIBOS}/${f.id}`, 'PUT', {
                        salario_diario: n(f.salario_diario), dias_trabajados: n(f.dias_trabajados),
                        horas_extra: n(f.horas_extra), bonos: n(f.bonos),
                        isr: n(f.isr), imss: n(f.imss), otras_deducciones: n(f.otras_deducciones),
                        notas: f.notas || null, guardar_salario: !!f.guardar_salario,
                    });
                    this.reemplazar(data.recibo);
                    this.modal = false;
                    avisoNomina(data.message || 'Recibo actualizado.');
                } catch (e) {
                    avisoNomina(e.message, 'error');
                } finally {
                    this.guardando = false;
                }
            },

            // ----- Pagos -----
            abrirPago(lista) {
                if (!lista.length) return;
                this.pago = { recibos: lista, fecha: PERIODO_NOM.pago && PERIODO_NOM.pago <= hoyIso() ? PERIODO_NOM.pago : hoyIso(), metodo: this.pago.metodo || 'TRANSFERENCIA', referencia: '' };
                this.modalPago = true;
            },
            async confirmarPago() {
                this.guardando = true;
                try {
                    const data = await peticion(URL_PAGAR, 'POST', {
                        recibo_ids: this.pago.recibos.map(r => r.id),
                        fecha_pago: this.pago.fecha, metodo_pago: this.pago.metodo, referencia: this.pago.referencia || null,
                    });
                    const pagadosAhora = this.pago.recibos.map(r => r.id);
                    data.recibos.forEach(r => this.reemplazar(r, pagadosAhora.includes(r.id)));
                    this.estado = data.estado;
                    this.marcados = this.marcados.filter(id => !pagadosAhora.includes(id));
                    this.modalPago = false;
                    avisoNomina(data.estado === 'Pagado' ? '¡Periodo pagado por completo!' : data.message);
                } catch (e) {
                    avisoNomina(e.message, 'error');
                } finally {
                    this.guardando = false;
                }
            },
            revertir(r) {
                Swal.fire({
                    title: '¿Revertir el pago?', html: `El recibo de <b>${this.escapar(r.empleado)}</b> volverá a quedar pendiente.`,
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#f59e0b', cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, revertir', cancelButtonText: 'Cancelar', customClass: { popup: 'rounded-3xl' },
                }).then(async res => {
                    if (!res.isConfirmed) return;
                    try {
                        const data = await peticion(`${URL_RECIBOS}/${r.id}/revertir`, 'POST');
                        this.reemplazar(data.recibo, true);
                        this.estado = data.estado;
                        avisoNomina(data.message);
                    } catch (e) { avisoNomina(e.message, 'error'); }
                });
            },
            // Pendientes de un periodo abierto: basta con "editar"; pagados o de periodo cerrado: requiere "eliminar"
            puedeBorrar(r) { return this.puedeEliminar || (this.puedeEditar && r.estado !== 'Pagado' && this.estado === 'Abierto'); },
            eliminarPeriodo() {
                const pagados = this.pagados.length;
                Swal.fire({
                    title: '¿Eliminar todo el periodo?',
                    html: `Se borrarán los <b>${this.recibos.length} recibo(s)</b> de este periodo.`
                        + (pagados ? `<br><span style="font-size:12px;color:#b45309"><b>${pagados}</b> ya estaban pagados; se perderá su registro de pago.</span>` : '')
                        + '<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>',
                    icon: 'warning', showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                    showLoaderOnConfirm: true, customClass: { popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try { return await peticion(URL_PERIODO, 'DELETE'); }
                        catch (e) { Swal.showValidationMessage(e.message); }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(res => {
                    if (!res.isConfirmed || !res.value) return;
                    avisoNomina(res.value.message);
                    setTimeout(() => { window.location.href = res.value.redirect; }, 600);
                });
            },
            quitar(r) {
                Swal.fire({
                    title: r.estado === 'Pagado' ? '¿Eliminar recibo pagado?' : '¿Eliminar este recibo?',
                    html: `Se eliminará el recibo de <b>${this.escapar(r.empleado)}</b> en este periodo.`
                        + (r.estado === 'Pagado' ? `<br><span style="font-size:12px;color:#b45309">Ya estaba pagado (${this.dinero(r.neto)}); se perderá su registro de pago.</span>` : '')
                        + (this.recibos.length === 1 ? '<br><span style="font-size:12px;color:#f43f5e">Es el único recibo: el periodo también se eliminará.</span>' : ''),
                    icon: 'warning', showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', customClass: { popup: 'rounded-3xl' },
                }).then(async res => {
                    if (!res.isConfirmed) return;
                    try {
                        const data = await peticion(`${URL_RECIBOS}/${r.id}`, 'DELETE');
                        if (data.periodo_eliminado) {
                            avisoNomina(data.message);
                            setTimeout(() => { window.location.href = data.redirect; }, 700);
                            return;
                        }
                        r._saliendo = true;
                        setTimeout(() => {
                            this.recibos = this.recibos.filter(x => x.id !== r.id);
                            this.marcados = this.marcados.filter(id => id !== r.id);
                            this.disponibles.push({ id: r.personal_id, nombre: r.empleado, tipo_pago: r.tipo_pago, salario: r.salario_diario });
                        }, 300);
                        this.estado = data.estado || this.estado;
                        avisoNomina(data.message);
                    } catch (e) { avisoNomina(e.message, 'error'); }
                });
            },
            reemplazar(nuevo, destello = true) {
                const i = this.recibos.findIndex(x => x.id === nuevo.id);
                if (i === -1) return;
                this.recibos[i] = { ...nuevo, _destello: destello };
                if (destello) setTimeout(() => { if (this.recibos[i]) this.recibos[i]._destello = false; }, 1300);
            },

            confirmarEstado() {
                const cerrar = this.estado === 'Abierto';
                Swal.fire({
                    title: cerrar ? '¿Cerrar la edición?' : '¿Reabrir el periodo?',
                    text: cerrar ? 'Ya no se podrán modificar los recibos pendientes, pero sí registrar sus pagos.' : 'Podrás volver a editar los recibos pendientes.',
                    icon: 'question', showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#0d9488', cancelButtonColor: '#64748b',
                    confirmButtonText: cerrar ? 'Sí, cerrar' : 'Sí, reabrir', cancelButtonText: 'Cancelar', customClass: { popup: 'rounded-3xl' },
                }).then(r => { if (r.isConfirmed) this.$refs.formEstado.submit(); });
            },

            exportarCsv() {
                const enc = ['Empleado', 'Puesto', 'Tipo de pago', 'Salario diario', 'Días', 'Sueldo base', 'Horas extra', 'Bonos', 'Percepciones', 'ISR', 'IMSS', 'Otras deducciones', 'Deducciones', 'Neto', 'Estado', 'Fecha de pago', 'Forma de pago', 'Referencia'];
                const filas = this.recibos.map(r => [r.empleado, r.puesto, this.etiquetaTipo(r.tipo_pago), r.salario_diario, r.dias_trabajados, r.sueldo_base, r.horas_extra, r.bonos, r.total_percepciones, r.isr, r.imss, r.otras_deducciones, r.total_deducciones, r.neto, r.estado, r.fecha_pago || '', r.metodo_pago ? this.metodoTexto(r.metodo_pago) : '', r.referencia]);
                const csv = [enc, ...filas].map(f => f.map(v => '"' + String(v ?? '').replace(/"/g, '""') + '"').join(',')).join('\r\n');
                const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = `nomina-${PERIODO_NOM.tipo.toLowerCase()}-${PERIODO_NOM.inicio}.csv`;
                a.click();
                URL.revokeObjectURL(a.href);
            },

            // ----- Formatos -----
            normalizar(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
            iniciales(t) { return (t || '?').trim().split(/\s+/).slice(0, 2).map(x => x[0]).join('').toUpperCase(); },
            dinero(v) { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0); },
            fechaCorta(f) { return f ? new Date(f + 'T00:00:00').toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
            metodoTexto(m) { return { EFECTIVO: 'Efectivo', TRANSFERENCIA: 'Transferencia', CHEQUE: 'Cheque' }[m] || m || ''; },
            etiquetaTipo(t) { return { SEMANAL: 'Semanal', QUINCENAL: 'Quincenal', MENSUAL: 'Mensual', ESPECIAL: 'Especial' }[t] || t; },
            escapar(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; },
        };
    }
</script>
@endsection