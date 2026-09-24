@extends('layouts.admin')

@section('content')
@php
    $listaConsultorios = collect($consultorios ?? []);

    // Normaliza el estado: acepta 1/0, '1'/'0', true/false o 'Activo'/'Inactivo'.
    $esActivo = function ($c) {
        $v = is_string($c->estado) ? mb_strtolower(trim($c->estado)) : $c->estado;
        return in_array($v, [1, '1', true, 'true', 'activo', 'active', 'si', 'sí', 'habilitado'], true);
    };

    $totalConsultorios = $listaConsultorios->count();
    $totalActivos = $listaConsultorios->filter($esActivo)->count();
    $totalInactivos = $totalConsultorios - $totalActivos;
    $pisosDisponibles = $listaConsultorios->pluck('piso')->unique()->sort()->values();

    // Posición inicial (si aún no tienen pos_x / pos_y guardadas): cuadrícula automática.
    $columnasPlano = max(2, min(5, (int) ceil(sqrt(max($totalConsultorios, 1)))));
    $datosPlano = $listaConsultorios->values()->map(fn ($c, $i) => [
        'id'     => $c->id,
        'nombre' => $c->nombre,
        'piso'   => (int) $c->piso,
        'activo' => $esActivo($c),
        'x'      => $c->pos_x ?? ($i % $columnasPlano) * 100 + 20,
        'y'      => $c->pos_y ?? intdiv($i, $columnasPlano) * 100 + 20,
    ]);
@endphp

<div class="py-6 space-y-6" x-data="moduloConsultorios()">

    <!-- BANNER INSTITUCIONAL -->
    <div class="relative bg-gradient-to-r from-teal-900 via-teal-800 to-emerald-800 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 overflow-hidden">
        <div class="absolute -right-10 -top-14 w-44 h-44 rounded-full bg-white/5"></div>
        <div class="absolute right-16 bottom-[-3rem] w-24 h-24 rounded-full bg-white/5"></div>

        <div class="space-y-2 z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-[11px] font-bold tracking-wide uppercase text-emerald-300 border border-white/10">
                <i class="bi bi-hospital-fill"></i> Infraestructura médica
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Directorio de consultorios</h1>
            <p class="text-xs text-slate-200 font-medium max-w-xl leading-relaxed">
                Administra las salas, consultorios físicos y pisos disponibles para la asignación estructurada de citas en el sistema.
            </p>
        </div>
        <button type="button" @click="abrirModalCrear()" class="z-10 bg-white text-teal-900 hover:bg-emerald-50 px-6 py-3 rounded-2xl text-xs font-black shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center gap-2 cursor-pointer">
            <i class="bi bi-plus-lg text-sm text-teal-700"></i> Registrar consultorio
        </button>
    </div>

    <!-- RESUMEN -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4" style="animation-delay:0ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Total</p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $totalConsultorios }}</p>
        </div>
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4" style="animation-delay:60ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activos
            </p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $totalActivos }}</p>
        </div>
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4" style="animation-delay:120ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Inactivos
            </p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $totalInactivos }}</p>
        </div>
        <div class="tarjeta-stat bg-white rounded-2xl border border-slate-100 shadow-sm p-4" style="animation-delay:180ms">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Pisos en uso</p>
            <p class="text-xl font-black text-slate-800 mt-1">{{ $pisosDisponibles->count() }}</p>
        </div>
    </div>

    @if ($listaConsultorios->isNotEmpty())
    <!-- PLANO EDITABLE -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="flex flex-wrap items-center gap-2 px-6 py-4 border-b border-slate-100 bg-slate-50/60">
            <i class="bi bi-bounding-box-circles text-teal-600"></i>
            <p class="text-sm font-black text-slate-800">Plano del consultorio</p>
            <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                <i class="bi bi-arrows-move"></i> Arrastra cada bloque para acomodarlo como en el edificio real
            </span>
            <span class="ml-auto text-[10px] font-bold text-slate-400">{{ $totalConsultorios }} {{ $totalConsultorios === 1 ? 'espacio' : 'espacios' }}</span>
        </div>

        <div class="overflow-x-auto bg-gradient-to-br from-slate-50 to-white">
            <div class="relative mx-auto select-none" style="width:840px; height:620px;">
                <!-- Piso isométrico: 640 x 480 px de superficie -->
                <div class="absolute rounded-2xl border border-slate-200 bg-white"
                     style="left:100px; top:70px; width:640px; height:480px;
                            transform: rotateX(52deg) rotateZ(45deg); transform-style: preserve-3d;
                            background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px); background-size: 20px 20px;
                            box-shadow: 0 12px 0 rgba(15,23,42,0.06);">

                    <template x-for="c in plano" :key="c.id">
                        <div @pointerdown="iniciarArrastre($event, c)"
                             @pointermove="mover($event, c)"
                             @pointerup="soltar(c)"
                             @pointercancel="soltar(c)"
                             :title="c.nombre + ' · ' + (c.activo ? 'Activo' : 'Inactivo')"
                             class="absolute rounded-md flex items-center justify-center"
                             :class="arrastre && arrastre.id === c.id ? 'cursor-grabbing' : 'cursor-grab'"
                             :style="estiloTile(c)">
                            <!-- Etiqueta contra-rotada para que siempre se lea de frente -->
                            <div class="flex flex-col items-center gap-0.5 pointer-events-none text-center"
                                 style="transform: rotateZ(-45deg) rotateX(-52deg); max-width:70px;">
                                <i class="bi bi-door-open text-teal-700 text-base"></i>
                                <span class="text-[9px] font-black text-slate-600 leading-tight" x-text="c.nombre"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <p class="text-[10px] text-slate-400 font-semibold px-6 pb-4">
            Los cambios de posición se guardan automáticamente al soltar cada bloque.
        </p>
    </div>
    @endif

    <!-- TABLA DE CONSULTORIOS -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h3 class="text-base font-black text-slate-800">Consultorios registrados</h3>
                <span class="text-xs font-bold px-3 py-1 bg-teal-50 text-teal-700 rounded-full">{{ $totalConsultorios }} en total</span>
            </div>

            <!-- Filtros -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[11px]"></i>
                    <input type="text" x-model="busqueda" placeholder="Buscar por nombre..."
                           class="bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-2 text-xs font-medium outline-none focus:border-teal-500 focus:bg-white transition-all w-44">
                </div>
                <select x-model="filtroPiso" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                    <option value="todos">Todos los pisos</option>
                    @foreach ($pisosDisponibles as $p)
                        <option value="{{ $p }}">Piso {{ $p }}</option>
                    @endforeach
                </select>
                <select x-model="filtroEstado" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-600 outline-none focus:border-teal-500 cursor-pointer">
                    <option value="todos">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-[10px] font-black uppercase text-slate-400 tracking-wider">
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Nombre del consultorio</th>
                        <th class="py-4 px-6">Piso</th>
                        <th class="py-4 px-6">Estado</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-bold text-slate-700">
                    @forelse($listaConsultorios as $c)
                        @php $activo = $esActivo($c); @endphp
                        <tr x-show="coincide(@js($c->nombre), {{ (int) $c->piso }}, '{{ $activo ? 'activo' : 'inactivo' }}')"
                            x-transition
                            class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 text-slate-400">#{{ $c->id }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2 text-slate-900 font-black">
                                    <span class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                                        <i class="bi bi-door-open text-xs"></i>
                                    </span>
                                    {{ $c->nombre }}
                                </div>
                            </td>
                            <td class="py-4 px-6">Piso {{ $c->piso }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black {{ $activo ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }}">
                                    {{ $activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click='abrirModalEditar(@json($c))'
                                            class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 transition-all flex items-center justify-center cursor-pointer" title="Editar">
                                        <i class="bi bi-pencil-fill text-[11px]"></i>
                                    </button>
                                    <button type="button" @click="eliminarConsultorio({{ $c->id }})"
                                            class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all flex items-center justify-center cursor-pointer" title="Eliminar">
                                        <i class="bi bi-trash-fill text-[11px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 font-medium">
                                No hay consultorios registrados todavía. ¡Crea el primero arriba!
                            </td>
                        </tr>
                    @endforelse

                    @if ($listaConsultorios->isNotEmpty())
                        <tr x-show="visibles === 0" x-cloak>
                            <td colspan="5" class="py-12 text-center text-slate-400 font-medium">
                                Ningún consultorio coincide con los filtros.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- INCLUIMOS EL MODAL DESDE ARCHIVO APARTE -->
    @include('consultorios.modal')

</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .tarjeta-stat {
        opacity: 0;
        animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    [x-cloak] { display: none !important; }
    @media (prefers-reduced-motion: reduce) {
        .tarjeta-stat { animation: none !important; opacity: 1 !important; }
    }
</style>

<script>
    // Notificación flotante (esquina superior derecha)
    function toast(icon, title) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: 2800,
            timerProgressBar: true
        });
    }

    // Muestra el toast después de recargar la página
    function toastYRecargar(icon, title) {
        sessionStorage.setItem('toast_consultorios', JSON.stringify({ icon, title }));
        location.reload();
    }

    function moduloConsultorios() {
    return {
        openModal: false,
        modoEdicion: false,
        idSeleccionado: null,
        form: { clinica_id: 1, nombre: '', piso: '1', estado: '1' },

        // ----- Plano 3D arrastrable -----
        plano: @json($datosPlano),
        tam: 80,        // tamaño del bloque (px, en el plano)
        paso: 20,       // cuadrícula de imantado
        ancho: 640,     // superficie del piso
        alto: 480,
        anguloZ: 45,    // mismos ángulos que el CSS del piso
        anguloX: 52,
        arrastre: null,

        init() {
            const pendiente = sessionStorage.getItem('toast_consultorios');
            if (pendiente) {
                sessionStorage.removeItem('toast_consultorios');
                const t = JSON.parse(pendiente);
                this.$nextTick(() => toast(t.icon, t.title));
            }
        },

        estiloTile(c) {
            const alzado = this.arrastre && this.arrastre.id === c.id;
            const borde = c.activo ? '#0d9488' : '#fb7185';
            return `left:${c.x}px; top:${c.y}px; width:${this.tam}px; height:${this.tam}px;`
                 + `background:#f6f1e6; border:4px solid ${borde};`
                 + `box-shadow:${alzado ? '0 16px 0 rgba(15,23,42,0.18)' : '0 6px 0 rgba(15,23,42,0.10)'};`
                 + `transform:${alzado ? 'translateZ(26px)' : 'translateZ(0)'}; transform-style:preserve-3d;`
                 + `touch-action:none; z-index:${alzado ? 20 : 10};`
                 + `transition:${alzado ? 'box-shadow .15s' : 'left .15s, top .15s, box-shadow .15s, transform .15s'};`;
        },

        iniciarArrastre(e, item) {
            e.currentTarget.setPointerCapture(e.pointerId);
            this.arrastre = { id: item.id, cx: e.clientX, cy: e.clientY, x0: item.x, y0: item.y };
        },

        // Convierte el movimiento del puntero en pantalla al movimiento sobre el plano inclinado
        mover(e, item) {
            const a = this.arrastre;
            if (!a || a.id !== item.id) return;
            const t = this.anguloZ * Math.PI / 180;
            const f = this.anguloX * Math.PI / 180;
            const u = e.clientX - a.cx;
            const v = (e.clientY - a.cy) / Math.cos(f);
            const px = u * Math.cos(t) + v * Math.sin(t);
            const py = -u * Math.sin(t) + v * Math.cos(t);
            item.x = Math.max(0, Math.min(a.x0 + px, this.ancho - this.tam));
            item.y = Math.max(0, Math.min(a.y0 + py, this.alto - this.tam));
        },

        soltar(item) {
            if (!this.arrastre || this.arrastre.id !== item.id) return;
            const { x0, y0 } = this.arrastre;
            this.arrastre = null;

            const maxX = Math.floor((this.ancho - this.tam) / this.paso) * this.paso;
            const maxY = Math.floor((this.alto - this.tam) / this.paso) * this.paso;
            item.x = Math.min(maxX, Math.round(item.x / this.paso) * this.paso);
            item.y = Math.min(maxY, Math.round(item.y / this.paso) * this.paso);

            if (this.hayColision(item)) {
                item.x = x0;
                item.y = y0;
                toast('warning', 'Ese espacio ya está ocupado por otro consultorio');
                return;
            }
            if (item.x !== x0 || item.y !== y0) this.guardarPosicion(item, x0, y0);
        },

        hayColision(item) {
            return this.plano.some(o => o.id !== item.id &&
                Math.abs(o.x - item.x) < this.tam && Math.abs(o.y - item.y) < this.tam);
        },

        guardarPosicion(item, x0, y0) {
            fetch(`/consultorios/${item.id}/posicion`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ pos_x: item.x, pos_y: item.y })
            })
            .then(r => { if (!r.ok) throw new Error(); toast('success', 'Posición guardada'); })
            .catch(() => {
                item.x = x0;
                item.y = y0;
                toast('error', 'No se pudo guardar la posición');
            });
        },

        // ----- Filtros de la tabla -----
        busqueda: '',
        filtroPiso: 'todos',
        filtroEstado: 'todos',

        coincide(nombre, piso, estado) {
            const texto = this.busqueda.trim().toLowerCase();
            const pasaTexto = texto === '' || nombre.toLowerCase().includes(texto);
            const pasaPiso = this.filtroPiso === 'todos' || String(piso) === String(this.filtroPiso);
            const pasaEstado = this.filtroEstado === 'todos' || estado === this.filtroEstado;
            return pasaTexto && pasaPiso && pasaEstado;
        },

        get visibles() {
            return this.plano.filter(c =>
                this.coincide(c.nombre, c.piso, c.activo ? 'activo' : 'inactivo')
            ).length;
        },

        // ----- CRUD -----
        abrirModalCrear() {
            this.modoEdicion = false;
            this.form = { clinica_id: 1, nombre: '', piso: '1', estado: '1' };
            this.openModal = true;
        },

        abrirModalEditar(item) {
            this.modoEdicion = true;
            this.idSeleccionado = item.id;
            // El estado se toma ya normalizado del plano, para que el botón Activo/Inactivo quede bien marcado
            const enPlano = this.plano.find(c => c.id === item.id);
            this.form = {
                clinica_id: item.clinica_id,
                nombre: item.nombre,
                piso: item.piso,
                estado: enPlano && enPlano.activo ? '1' : '0'
            };
            this.openModal = true;
        },

        guardar() {
            let url = this.modoEdicion ? `/consultorios/${this.idSeleccionado}` : '/consultorios';
            let method = this.modoEdicion ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.form)
            })
            .then(async response => {
                let res = await response.json();

                if (!response.ok) {
                    let mensaje = res.message || 'Revisa que todos los campos requeridos estén llenos.';
                    if (res.errors) {
                        let primerCampo = Object.keys(res.errors)[0];
                        mensaje = res.errors[primerCampo][0];
                    }
                    toast('warning', mensaje);
                    return;
                }

                this.openModal = false;
                toastYRecargar('success', res.message || 'Consultorio guardado');
            })
            .catch(error => {
                console.error('Error de red:', error);
                toast('error', 'No se pudo comunicar con el servidor');
            });
        },

        eliminarConsultorio(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Se eliminará este consultorio del sistema',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f43f5e',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch(`/consultorios/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(async r => {
                    const res = await r.json();
                    if (!r.ok) throw new Error(res.message);
                    toastYRecargar('success', res.message || 'Consultorio eliminado');
                })
                .catch(err => toast('error', err.message || 'No se pudo eliminar'));
            });
        }
    }
    }
</script>
@endsection