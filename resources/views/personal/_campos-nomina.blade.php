{{--
    Datos de nómina del empleado (salario diario y tipo de pago).
    Se usa en create y edit de Personal:
        @include('personal._campos-nomina')                          ← alta
        @include('personal._campos-nomina', ['empleado' => $personal]) ← edición
--}}
@php
    $empleadoNomina = $empleado ?? null;
    $salarioInicial = old('salario_diario', data_get($empleadoNomina, 'salario_diario', ''));
    $tipoInicial    = old('tipo_pago', data_get($empleadoNomina, 'tipo_pago') ?: 'QUINCENAL');
@endphp

<div x-data="{
        salario: @js($salarioInicial === null ? '' : (string) $salarioInicial),
        tipo: @js((string) $tipoInicial),
        tipos: [
            { valor: 'SEMANAL',   texto: 'Semanal',   dias: 7,  icono: 'bi-calendar-week' },
            { valor: 'QUINCENAL', texto: 'Quincenal', dias: 15, icono: 'bi-calendar2-range' },
            { valor: 'MENSUAL',   texto: 'Mensual',   dias: 30, icono: 'bi-calendar3' },
        ],
        get diasTipo() { return (this.tipos.find(t => t.valor === this.tipo) || this.tipos[1]).dias; },
        get porPeriodo() { return (Number(this.salario) || 0) * this.diasTipo; },
        get mensual() { return (Number(this.salario) || 0) * 30; },
        dinero(v) { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0); }
     }"
     class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 space-y-4">

    <div class="flex items-center gap-2.5">
        <span class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center"><i class="bi bi-cash-coin"></i></span>
        <div>
            <p class="text-xs font-black text-emerald-900">Datos de nómina</p>
            <p class="text-[10px] font-semibold text-emerald-700/70">Se usan para generar sus recibos de pago automáticamente</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- Salario diario --}}
        <div>
            <label for="salario_diario" class="block text-[11px] font-black text-slate-600 uppercase mb-1.5">Salario diario</label>
            <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400">$</span>
                <input type="number" id="salario_diario" name="salario_diario" x-model="salario"
                       step="0.01" min="0" max="999999" placeholder="0.00" inputmode="decimal"
                       class="w-full pl-8 pr-14 py-2.5 rounded-2xl border border-slate-200 bg-white text-sm font-black text-slate-800 tabular-nums outline-none transition-all focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 @error('salario_diario') border-rose-400 @enderror">
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">MXN</span>
            </div>
            @error('salario_diario')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>

        {{-- Tipo de pago --}}
        <div>
            <span class="block text-[11px] font-black text-slate-600 uppercase mb-1.5">Se le paga</span>
            <input type="hidden" name="tipo_pago" :value="tipo">
            <div class="grid grid-cols-3 gap-1.5">
                <template x-for="t in tipos" :key="t.valor">
                    <button type="button" @click="tipo = t.valor"
                            class="py-2 rounded-xl border-2 text-center transition-all"
                            :class="tipo === t.valor ? 'border-emerald-500 bg-white text-emerald-800 shadow-sm' : 'border-transparent bg-white/60 text-slate-500 hover:border-emerald-200'">
                        <i class="bi text-sm" :class="t.icono"></i>
                        <p class="text-[10px] font-black" x-text="t.texto"></p>
                    </button>
                </template>
            </div>
            @error('tipo_pago')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Cálculo rápido --}}
    <div class="grid grid-cols-2 gap-2 text-center">
        <div class="rounded-xl bg-white border border-emerald-100 py-2">
            <p class="text-[9px] font-black uppercase text-slate-400" x-text="'Por pago ' + tipo.toLowerCase()"></p>
            <p class="text-sm font-black text-emerald-700 tabular-nums" x-text="dinero(porPeriodo)"></p>
        </div>
        <div class="rounded-xl bg-white border border-emerald-100 py-2">
            <p class="text-[9px] font-black uppercase text-slate-400">Aprox. al mes</p>
            <p class="text-sm font-black text-slate-700 tabular-nums" x-text="dinero(mensual)"></p>
        </div>
    </div>
    <p x-show="!Number(salario)" class="text-[10px] font-bold text-amber-700"><i class="bi bi-info-circle"></i> Puedes dejarlo en blanco y capturarlo después desde la nómina.</p>
</div>