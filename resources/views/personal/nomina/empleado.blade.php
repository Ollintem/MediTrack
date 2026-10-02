@extends('layouts.admin')

@section('content')
@php
    $usuario     = auth()->user();
    $puede       = fn ($accion) => !$usuario || !method_exists($usuario, 'tienePermiso') || $usuario->tienePermiso('Personal', $accion);
    $puedeCrear  = $puede('crear');
    $puedeEditar = $puede('editar');
    $puedeEliminar = $puede('eliminar');
@endphp

<div class="space-y-6" x-data="fichaNomina()">

    @include('personal._pestanas', ['activa' => 'nomina'])

    {{-- ===================== ENCABEZADO ===================== --}}
    <div class="nom-aparece relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-700 text-white shadow-xl shadow-teal-900/20">
        <div class="absolute -right-16 -top-20 w-64 h-64 rounded-full bg-white/5"></div>
        <div class="relative p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-center gap-4 min-w-0">
                <span class="w-16 h-16 rounded-2xl bg-white/15 ring-1 ring-white/25 flex items-center justify-center text-xl font-black shrink-0" x-text="iniciales(e.nombre)"></span>
                <div class="min-w-0 space-y-1.5">
                    <a href="{{ route('personal.nomina.index') }}" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-200 hover:text-white transition">
                        <i class="bi bi-arrow-left"></i> Volver a nómina
                    </a>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight flex items-center gap-2 truncate">
                        <span class="truncate" x-text="e.nombre"></span>
                        <i class="bi bi-heart-pulse-fill text-emerald-300 text-lg nom-latido"></i>
                    </h1>
                    <div class="flex flex-wrap gap-2 text-[11px] font-bold">
                        <span x-show="e.puesto" class="px-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20" x-text="e.puesto"></span>
                        <span class="px-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20"><i class="bi bi-calendar2-range"></i> <span x-text="'Pago ' + etiquetaTipo(e.tipo_pago).toLowerCase()"></span></span>
                        <span class="px-3 py-1 rounded-full ring-1" :class="e.salario > 0 ? 'bg-white/10 ring-white/20' : 'bg-amber-400/20 ring-amber-300/40 text-amber-100'">
                            <i class="bi bi-cash"></i> <span x-text="e.salario > 0 ? dinero(e.salario) + ' por día' : 'Sin salario registrado'"></span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 shrink-0">
                @if ($puedeEditar && \Illuminate\Support\Facades\Route::has('personal.edit'))
                    <a href="{{ route('personal.edit', $personal->id) }}" class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 ring-1 ring-white/20 text-xs font-bold flex items-center gap-2 transition">
                        <i class="bi bi-pencil"></i> Editar salario
                    </a>
                @endif
                @if ($puedeCrear)
                    <button type="button" @click="abrir('normal')" class="px-4 py-2.5 rounded-2xl bg-white text-teal-900 hover:bg-emerald-50 text-xs font-black shadow-lg shadow-teal-950/25 flex items-center gap-2 transition">
                        <i class="bi bi-plus-circle-fill"></i> Generar su pago
                    </button>
                    <button type="button" @click="abrir('especial')" class="px-4 py-2.5 rounded-2xl bg-amber-400 text-amber-950 hover:bg-amber-300 text-xs font-black flex items-center gap-2 transition">
                        <i class="bi bi-star-fill"></i> Pago especial
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== RESUMEN ===================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="nom-aparece bg-white rounded-2xl border border-slate-200 p-4" style="animation-delay:60ms">
            <p class="text-[11px] font-bold text-slate-500">Próximo pago</p>
            <p class="text-lg font-black text-slate-800 mt-1" x-text="rango(e.prox_ini, e.prox_fin)"></p>
            <p class="text-[11px] font-black mt-0.5" :class="vencimiento.clase" x-text="vencimiento.texto"></p>
        </div>
        <div class="nom-aparece bg-white rounded-2xl border border-slate-200 p-4" style="animation-delay:120ms">
            <p class="text-[11px] font-bold text-slate-500">Pagado este año</p>
            <p class="text-2xl font-black text-emerald-700 mt-1 tabular-nums" x-text="dinero(resumen.pagadoAnio)"></p>
        </div>
        <div class="nom-aparece bg-white rounded-2xl border border-slate-200 p-4" style="animation-delay:180ms">
            <p class="text-[11px] font-bold text-slate-500">Pendiente de pagarle</p>
            <p class="text-2xl font-black mt-1 tabular-nums" :class="resumen.pendiente > 0 ? 'text-amber-600' : 'text-slate-800'" x-text="dinero(resumen.pendiente)"></p>
        </div>
        <div class="nom-aparece bg-white rounded-2xl border border-slate-200 p-4" style="animation-delay:240ms">
            <p class="text-[11px] font-bold text-slate-500">Último pago</p>
            <p class="text-lg font-black text-slate-800 mt-1" x-text="resumen.ultimoPago ? fechaCorta(resumen.ultimoPago) : 'Sin pagos aún'"></p>
            <p class="text-[11px] font-semibold text-slate-400" x-text="resumen.recibos + ' recibo(s) en total'"></p>
        </div>
    </div>

    {{-- ===================== HISTORIAL ===================== --}}
    <div class="nom-aparece bg-white rounded-3xl border border-slate-200 overflow-hidden" style="animation-delay:300ms">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center gap-3">
            <span class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-clock-history"></i></span>
            <div>
                <h3 class="text-base font-black text-slate-800">Historial de pagos</h3>
                <p class="text-[11px] font-semibold text-slate-400">Todos sus recibos, del más reciente al más antiguo</p>
            </div>
        </div>

        <div class="divide-y divide-slate-100" x-show="recibos.length">
            <template x-for="(r, i) in recibos" :key="r.id">
                <div class="nom-fila flex flex-wrap sm:flex-nowrap items-center gap-4 p-4 hover:bg-slate-50/60 transition" :class="r._saliendo && 'nom-sale'" :style="'animation-delay:' + Math.min(i * 30, 300) + 'ms'">
                    <span class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
                          :class="r.estado === 'Pagado' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                        <i class="bi text-lg" :class="r.estado === 'Pagado' ? 'bi-check-circle-fill' : 'bi-hourglass-split'"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a :href="r.url_periodo" class="text-sm font-black text-slate-800 hover:text-teal-700" x-text="rango(r.inicio, r.fin)"></a>
                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded-md" :class="colorTipo(r.periodo_tipo)" x-text="etiquetaTipo(r.periodo_tipo)"></span>
                        </div>
                        <p class="text-[11px] font-semibold text-slate-400 mt-0.5"
                           x-text="r.estado === 'Pagado' ? 'Pagado el ' + fechaCorta(r.fecha_pago) + ' · ' + metodoTexto(r.metodo_pago) + (r.referencia ? ' · Ref. ' + r.referencia : '') : 'Pendiente de pago'"></p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-black text-slate-900 tabular-nums" x-text="dinero(r.neto)"></p>
                        <p class="text-[10px] font-semibold text-slate-400 tabular-nums" x-text="dinero(r.total_percepciones) + ' − ' + dinero(r.total_deducciones)"></p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <a :href="r.url_periodo" title="Abrir el periodo" class="w-8 h-8 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 flex items-center justify-center transition"><i class="bi bi-box-arrow-up-right text-[11px]"></i></a>
                        <a :href="URL_RECIBOS + '/' + r.id + '/pdf'" target="_blank" title="Recibo en PDF" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 flex items-center justify-center transition"><i class="bi bi-file-earmark-pdf-fill text-[12px]"></i></a>
                        @if ($puedeEliminar)
                            <button type="button" @click="eliminar(r)" title="Eliminar recibo" class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center transition"><i class="bi bi-trash-fill text-[11px]"></i></button>
                        @endif
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!recibos.length" class="py-16 text-center px-6">
            <span class="inline-flex w-16 h-16 rounded-3xl bg-teal-50 text-teal-500 items-center justify-center text-3xl mb-3"><i class="bi bi-receipt"></i></span>
            <p class="text-sm font-black text-slate-600">Aún no tiene recibos de nómina</p>
            @if ($puedeCrear)
                <button type="button" @click="abrir('normal')" class="mt-2 text-xs font-black text-teal-700 hover:text-teal-900">+ Generar su primer pago</button>
            @endif
        </div>
    </div>

    {{-- ===================== MODAL: GENERAR PAGO DE ESTE EMPLEADO ===================== --}}
    @if ($puedeCrear)
    <template x-teleport="body">
        <div x-show="modal" x-cloak @keydown.escape.window="modal = false"
             class="fixed inset-0 z-[9999] bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4" x-transition.opacity>
            <form method="POST" action="{{ route('personal.nomina.store') }}" @click.outside="modal = false"
                  class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" x-show="modal"
                  x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-6 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100">
                @csrf
                <input type="hidden" name="personal_ids[]" :value="e.id">
                <input type="hidden" name="tipo" :value="nuevo.tipo">

                <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center text-white" :class="nuevo.tipo === 'ESPECIAL' ? 'bg-amber-500' : 'bg-teal-600'">
                        <i class="bi" :class="nuevo.tipo === 'ESPECIAL' ? 'bi-star-fill' : 'bi-calendar-plus'"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-black text-slate-800" x-text="nuevo.tipo === 'ESPECIAL' ? 'Pago especial' : 'Pago ' + etiquetaTipo(nuevo.tipo).toLowerCase()"></h3>
                        <p class="text-[11px] text-slate-400 font-semibold truncate" x-text="e.nombre"></p>
                    </div>
                    <button type="button" @click="modal = false" class="w-8 h-8 rounded-xl hover:bg-slate-100 text-slate-400"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block"><span class="nom-label">Del *</span><input type="date" name="fecha_inicio" x-model="nuevo.inicio" required class="nom-input"></label>
                        <label class="block"><span class="nom-label">Al *</span><input type="date" name="fecha_fin" x-model="nuevo.fin" :min="nuevo.inicio" required class="nom-input"></label>
                        <label class="block col-span-2"><span class="nom-label">Se paga el</span><input type="date" name="fecha_pago" x-model="nuevo.pago" :min="nuevo.inicio" class="nom-input"></label>
                    </div>
                    <label class="block"><span class="nom-label" x-text="nuevo.tipo === 'ESPECIAL' ? 'Motivo *' : 'Notas'"></span>
                        <input type="text" name="notas" x-model="nuevo.notas" :required="nuevo.tipo === 'ESPECIAL'" maxlength="200" class="nom-input"
                               :placeholder="nuevo.tipo === 'ESPECIAL' ? 'Adelanto, aguinaldo, bono, finiquito…' : 'Opcional'">
                    </label>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <span class="font-bold text-slate-500" x-text="nuevo.tipo === 'ESPECIAL' ? 'El monto se captura en el recibo' : diasNuevo + ' días × ' + dinero(e.salario)"></span>
                        <span class="text-base font-black text-teal-700 tabular-nums" x-show="nuevo.tipo !== 'ESPECIAL'" x-text="dinero(e.salario * diasNuevo)"></span>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="modal = false" class="px-5 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-100">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-black shadow-lg shadow-teal-600/20 flex items-center gap-2">
                        <i class="bi bi-check2-circle"></i> Crear recibo
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
    @keyframes nomSale { to { opacity: 0; transform: translateX(30px); } }
    .nom-latido  { display: inline-block; animation: nomLatido 1.6s ease-in-out infinite; }
    @keyframes nomAparece { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    @keyframes nomLatido  { 0%,100% { transform: scale(1); } 15% { transform: scale(1.2); } 30% { transform: scale(1); } 45% { transform: scale(1.12); } }
    @media (prefers-reduced-motion: reduce) { .nom-aparece, .nom-fila, .nom-latido { animation: none !important; } }
</style>

<script>
    const URL_RECIBOS = @js(url('personal/nomina/recibo'));
    const CSRF_NOMINA = @js(csrf_token());

    function fichaNomina() {
        const fecha = f => new Date(f + 'T00:00:00');
        const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

        return {
            e: @json($empleado),
            resumen: @json($resumen),
            recibos: @json($recibos),
            modal: @js($errors->any()),
            nuevo: { tipo: 'QUINCENAL', inicio: '', fin: '', pago: '', notas: '' },

            init() {
                @if ($errors->any())
                    this.abrir(@js(old('tipo') === 'ESPECIAL' ? 'especial' : 'normal'));
                    setTimeout(() => window.notificar && window.notificar(@js($errors->first()), 'error'), 300);
                @endif
            },

            abrir(modo) {
                if (modo === 'especial') {
                    const hoy = iso(new Date());
                    this.nuevo = { tipo: 'ESPECIAL', inicio: hoy, fin: hoy, pago: hoy, notas: '' };
                } else {
                    this.nuevo = { tipo: this.e.tipo_pago, inicio: this.e.prox_ini, fin: this.e.prox_fin, pago: this.e.prox_fin, notas: '' };
                }
                this.modal = true;
            },

            get diasNuevo() {
                if (!this.nuevo.inicio || !this.nuevo.fin) return 0;
                return Math.max(0, Math.round((fecha(this.nuevo.fin) - fecha(this.nuevo.inicio)) / 86400000) + 1);
            },
            get vencimiento() {
                const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
                const d = Math.round((fecha(this.e.prox_fin) - hoy) / 86400000);
                if (d < 0)  return { texto: 'Atrasado ' + Math.abs(d) + ' día(s)', clase: 'text-rose-600' };
                if (d === 0) return { texto: 'Se paga hoy', clase: 'text-amber-600' };
                return { texto: 'Faltan ' + d + ' día(s)', clase: d <= 3 ? 'text-amber-600' : 'text-slate-400' };
            },

            eliminar(r) {
                Swal.fire({
                    title: '¿Eliminar este recibo?',
                    html: `Recibo del <b>${this.rango(r.inicio, r.fin)}</b> por <b>${this.dinero(r.neto)}</b>.`
                        + (r.estado === 'Pagado' ? '<br><span style="font-size:12px;color:#b45309">Ya estaba pagado; se perderá su registro de pago.</span>' : '')
                        + '<br><span style="font-size:12px;color:#f43f5e">Esta acción no se puede deshacer.</span>',
                    icon: 'warning', showCancelButton: true, reverseButtons: true,
                    confirmButtonColor: '#f43f5e', cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                    showLoaderOnConfirm: true, customClass: { popup: 'rounded-3xl' },
                    preConfirm: async () => {
                        try {
                            const res = await fetch(`${URL_RECIBOS}/${r.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_NOMINA, 'Accept': 'application/json' } });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || data.success === false) throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (data.message || 'No se pudo eliminar.'));
                            return data;
                        } catch (e) { Swal.showValidationMessage(e.message); }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(res => {
                    if (!res.isConfirmed || !res.value) return;
                    r._saliendo = true;
                    setTimeout(() => {
                        this.recibos = this.recibos.filter(x => x.id !== r.id);
                        this.resumen.recibos = this.recibos.length;
                        this.resumen.pendiente = this.recibos.filter(x => x.estado !== 'Pagado').reduce((s, x) => s + Number(x.neto || 0), 0);
                        if (r.estado === 'Pagado' && r.fecha_pago && r.fecha_pago.slice(0, 4) === String(new Date().getFullYear())) {
                            this.resumen.pagadoAnio = Math.max(0, this.resumen.pagadoAnio - Number(r.neto || 0));
                        }
                    }, 300);
                    if (window.notificar) window.notificar(res.value.message, 'success');
                });
            },

            iniciales(t) { return (t || '?').trim().split(/\s+/).slice(0, 2).map(x => x[0]).join('').toUpperCase(); },
            dinero(v) { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0); },
            fechaCorta(f) { return f ? fecha(f).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; },
            rango(a, b) {
                if (!a) return '';
                const o = { day: 'numeric', month: 'short' };
                if (a === b) return fecha(a).toLocaleDateString('es-MX', { ...o, year: 'numeric' });
                return fecha(a).toLocaleDateString('es-MX', o) + ' – ' + fecha(b).toLocaleDateString('es-MX', { ...o, year: 'numeric' });
            },
            metodoTexto(m) { return { EFECTIVO: 'Efectivo', TRANSFERENCIA: 'Transferencia', CHEQUE: 'Cheque' }[m] || m || ''; },
            etiquetaTipo(t) { return { SEMANAL: 'Semanal', QUINCENAL: 'Quincenal', MENSUAL: 'Mensual', ESPECIAL: 'Especial' }[t] || t; },
            colorTipo(t) { return { SEMANAL: 'bg-sky-50 text-sky-700', QUINCENAL: 'bg-teal-50 text-teal-700', MENSUAL: 'bg-violet-50 text-violet-700', ESPECIAL: 'bg-amber-50 text-amber-700' }[t] || 'bg-slate-100 text-slate-600'; },
        };
    }
</script>
@endsection