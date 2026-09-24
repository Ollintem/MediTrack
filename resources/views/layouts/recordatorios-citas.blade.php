{{-- resources/views/layouts/recordatorios-citas.blade.php

     Avisa al médico cuando una cita está por comenzar (15 min, 5 min y a la hora de la cita).
     Puedes incluirlo UNA vez en tu layout (layouts/admin.blade.php, antes de </body>) para que funcione en
     cualquier pantalla del sistema:   @include('layouts.recordatorios-citas')
     @once evita que se dibuje dos veces si además está incluido en la vista de consultas. --}}
@once
@if (\Illuminate\Support\Facades\Route::has('consultas.agenda'))
<div x-data="recordatoriosCitas()" x-init="iniciar()">
    <template x-teleport="body">
        <div class="fixed bottom-5 right-5 z-[100002] flex flex-col gap-3 w-[calc(100vw-2.5rem)] max-w-sm pointer-events-none">
            <template x-for="a in avisos" :key="a.clave">
                <div x-show="a.visible"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-6"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-6"
                     class="pointer-events-auto relative overflow-hidden bg-white rounded-2xl shadow-2xl shadow-slate-900/20 border border-slate-100">

                    <div class="h-1.5" :class="a.nivel === 'ahora' ? 'bg-rose-500' : (a.nivel === '5' ? 'bg-amber-400' : 'bg-teal-500')"></div>

                    <div class="p-4 pr-10 flex gap-3">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-lg"
                              :class="a.nivel === 'ahora' ? 'bg-rose-50 text-rose-500' : (a.nivel === '5' ? 'bg-amber-50 text-amber-500' : 'bg-teal-50 text-teal-600')">
                            <i class="bi bi-bell-fill" :class="a.nivel === 'ahora' && 'animate-bounce'"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-wide"
                               :class="a.nivel === 'ahora' ? 'text-rose-500' : (a.nivel === '5' ? 'text-amber-600' : 'text-teal-600')"
                               x-text="a.titulo"></p>
                            <p class="text-sm font-black text-slate-800 truncate" x-text="a.paciente"></p>
                            <p class="text-[11px] font-semibold text-slate-500 mt-0.5 leading-snug" x-text="a.detalle"></p>

                            <div class="flex gap-2 mt-3">
                                <button type="button" @click="atender(a)"
                                        class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-xl text-[11px] shadow-md shadow-teal-600/20 transition-all cursor-pointer">
                                    Atender ahora
                                </button>
                                <button type="button" @click="cerrar(a.clave)"
                                        class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-500 font-extrabold rounded-xl text-[11px] transition-all cursor-pointer">
                                    Más tarde
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="cerrar(a.clave)" title="Cerrar"
                            class="absolute right-3 top-4 w-6 h-6 rounded-lg text-slate-300 hover:text-slate-500 hover:bg-slate-50 flex items-center justify-center transition-all cursor-pointer">
                        <i class="bi bi-x-lg text-[10px]"></i>
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
    function recordatoriosCitas() {
        return {
            avisos: [],
            intervalo: null,
            deshabilitado: false,
            URL_AGENDA: '{{ route("consultas.agenda") }}?recordatorios=1',
            URL_CONSULTAS: '{{ \Illuminate\Support\Facades\Route::has("consultas.index") ? route("consultas.index") : "" }}',

            iniciar() {
                this.revisar();
                this.intervalo = setInterval(() => this.revisar(), 30000);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) this.revisar(); });
            },

            async revisar() {
                if (this.deshabilitado) return;
                try {
                    const r = await fetch(this.URL_AGENDA, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    // Sin sesión, sin permiso o sin ruta: se deja de consultar
                    if ([401, 403, 404].includes(r.status)) {
                        this.deshabilitado = true;
                        clearInterval(this.intervalo);
                        return;
                    }
                    if (!r.ok) return;
                    const d = await r.json();
                    this.procesar(d.proximas || [], !!d.es_admin);
                } catch (e) { /* se reintenta en el siguiente ciclo */ }
            },

            procesar(citas, esAdmin) {
                const pendientes = citas.filter(c => ['Pendiente', 'Confirmada'].includes(c.estado));
                const idsPendientes = new Set(pendientes.map(c => c.cita_id));

                pendientes.forEach(c => {
                    const f = c.faltan_min;
                    let nivel = null;
                    if (f <= 0 && f > -30) nivel = 'ahora';
                    else if (f > 0 && f <= 5) nivel = '5';
                    else if (f > 5 && f <= 15) nivel = '15';
                    if (!nivel) return;

                    const clave = c.cita_id + ':' + nivel;
                    if (this.yaAvisado(clave)) return;
                    this.marcarAvisado(clave);
                    this.mostrar(c, nivel, clave, esAdmin);
                });

                // Quita los avisos de citas que ya se atendieron, se cancelaron o se reprogramaron
                this.avisos.filter(a => !idsPendientes.has(a.cita_id)).forEach(a => this.cerrar(a.clave));
            },

            mostrar(c, nivel, clave, esAdmin) {
                const f = c.faltan_min;
                let titulo;
                if (nivel === 'ahora') titulo = f === 0 ? 'Es hora de tu cita' : 'Cita en espera · hace ' + (-f) + ' min';
                else if (nivel === '5') titulo = '¡Ya casi! · en ' + Math.max(f, 1) + ' min';
                else titulo = 'Cita próxima · en ' + f + ' min';

                const detalle = c.hora + ' – ' + c.hora_fin + ' · ' + (c.consultorio_nombre || 'Sin consultorio')
                    + (esAdmin ? ' · Dr(a). ' + c.personal_nombre : '');

                // Un aviso nuevo de la misma cita reemplaza al anterior
                this.avisos.filter(a => a.cita_id === c.cita_id).forEach(a => this.cerrar(a.clave));

                this.avisos.push({ clave, cita_id: c.cita_id, nivel, titulo, paciente: c.paciente_nombre, detalle, visible: false });
                const aviso = this.avisos.find(a => a.clave === clave);
                setTimeout(() => { aviso.visible = true; }, 30);

                // Los avisos previos a la hora se ocultan solos; el de "ahora" espera al médico
                if (nivel !== 'ahora') setTimeout(() => this.cerrar(clave), 60000);

                // Aviso del navegador (solo si el médico dio permiso)
                try {
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification(titulo, { body: c.paciente_nombre + ' · ' + detalle, tag: 'cita-' + c.cita_id });
                    }
                } catch (e) {}
            },

            cerrar(clave) {
                const a = this.avisos.find(x => x.clave === clave);
                if (!a) return;
                a.visible = false;
                setTimeout(() => { this.avisos = this.avisos.filter(x => x.clave !== clave); }, 300);
            },

            atender(a) {
                this.cerrar(a.clave);
                if (!this.URL_CONSULTAS) return;

                const ruta = new URL(this.URL_CONSULTAS, location.origin).pathname.replace(/\/$/, '');
                if (location.pathname.replace(/\/$/, '') === ruta) {
                    // Ya estamos en la pantalla de consultas: se abre la cita directamente
                    window.dispatchEvent(new CustomEvent('atender-cita', { detail: { id: a.cita_id } }));
                } else {
                    location.href = this.URL_CONSULTAS + '?atender=' + a.cita_id;
                }
            },

            // Para no repetir el mismo aviso (aunque se recargue la página o se cambie de pantalla)
            yaAvisado(clave) {
                try { return localStorage.getItem('recordatorio_cita_' + clave) !== null; } catch (e) { return false; }
            },
            marcarAvisado(clave) {
                try { localStorage.setItem('recordatorio_cita_' + clave, String(Date.now())); } catch (e) {}
            }
        };
    }
</script>
@endif
@endonce