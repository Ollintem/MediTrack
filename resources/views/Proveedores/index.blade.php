@extends('layouts.admin')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    input[type="text"], 
    textarea {
        text-transform: uppercase !important;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-stagger {
        animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
</style>

<div class="space-y-8 w-full block" x-data="{ 
    searchQuery: '',
    selectedCategoria: '',
    viewMode: 'list',
    openModal: false,
    isEdit: false,
    form: {
        id: '',
        razon_social: '',
        nombre_comercial: '',
        rut_rfc: '',
        categoria: '',
        contacto_nombre: '',
        telefono: '',
        email: '',
        direccion: '',
        dias_credito: 0,
        banco: '',
        cuenta_bancaria: '',
        estado: 'activo',
        notas: ''
    },

    nuevoProveedor() {
        this.isEdit = false;
        this.form = {
            id: '',
            razon_social: '',
            nombre_comercial: '',
            rut_rfc: '',
            categoria: '',
            contacto_nombre: '',
            telefono: '',
            email: '',
            direccion: '',
            dias_credito: 0,
            banco: '',
            cuenta_bancaria: '',
            estado: 'activo',
            notas: ''
        };
        this.openModal = true;
    },

    editarProveedor(prov) {
        this.isEdit = true;
        this.form = { ...prov };
        this.openModal = true;
    }
}">

    <!-- BANNER HERO -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 via-teal-600 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10 animate-stagger" style="animation-delay: 0ms;">
        <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-teal-100 text-xs font-semibold backdrop-blur-md">
                    <i class="bi bi-truck text-emerald-300"></i> Módulo de Logística & Compras
                </div>
                <h2 class="text-3xl font-extrabold tracking-tight">Proveedores y Distribuidores</h2>
                <p class="text-sm text-teal-100/90 max-w-xl">
                    Administra el catálogo de suplidores de medicamentos, insumos médicos, equipos y servicios para la clínica.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="nuevoProveedor()" type="button" class="group bg-white text-teal-800 hover:bg-teal-50 font-bold px-5 py-3 rounded-2xl text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 transform hover:-translate-y-0.5 cursor-pointer">
                    <i class="bi bi-plus-circle-fill text-teal-600 group-hover:scale-110 transition-transform duration-300 text-base"></i>
                    <span>Nuevo Proveedor</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-stagger" style="animation-delay: 100ms;">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold border border-teal-100">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Registrados</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $proveedores->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold border border-emerald-100">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Proveedores Activos</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $proveedores->where('estado', 'activo')->count() }}</h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold border border-sky-100">
                <i class="bi bi-tags-fill"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Categorías Activas</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $proveedores->pluck('categoria')->unique()->count() }}</h3>
            </div>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL / TABLA -->
    <div class="bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden animate-stagger" style="animation-delay: 200ms;">
        
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-50 via-teal-50/20 to-transparent">
            <div>
                <h3 class="font-extrabold text-gray-800 text-lg">Directorio de Proveedores</h3>
                <p class="text-xs text-gray-500">Gestión de contactos, condiciones comerciales e información de facturación</p>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="relative w-full md:w-64">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" x-model="searchQuery" placeholder="Buscar por empresa, RFC o vendedor..." class="w-full pl-9 pr-4 py-2.5 rounded-2xl border border-gray-200 bg-white text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all uppercase">
                </div>

                <select x-model="selectedCategoria" class="py-2.5 px-3 rounded-2xl border border-gray-200 bg-white text-xs font-semibold focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all uppercase">
                    <option value="">TODAS LAS CATEGORÍAS</option>
                    <option value="Medicamentos">MEDICAMENTOS</option>
                    <option value="Insumos Médicos">INSUMOS MÉDICOS</option>
                    <option value="Equipamiento">EQUIPAMIENTO</option>
                    <option value="Laboratorio">LABORATORIO</option>
                    <option value="Servicios">SERVICIOS</option>
                </select>

                <div class="flex items-center bg-slate-100 p-1 rounded-2xl border border-gray-200 shrink-0">
                    <button @click="viewMode = 'list'" type="button" :class="viewMode === 'list' ? 'bg-white text-teal-700 shadow-xs' : 'text-gray-400 hover:text-gray-600'" class="p-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer" title="Vista de Tabla">
                        <i class="bi bi-list-task text-base"></i>
                    </button>
                    <button @click="viewMode = 'grid'" type="button" :class="viewMode === 'grid' ? 'bg-white text-teal-700 shadow-xs' : 'text-gray-400 hover:text-gray-600'" class="p-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer" title="Vista de Tarjetas">
                        <i class="bi bi-grid-fill text-base"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div x-show="viewMode === 'list'" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-gray-400 font-bold uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="p-4">PROVEEDOR / RAZÓN SOCIAL</th>
                        <th class="p-4">CATEGORÍA</th>
                        <th class="p-4">CONTACTO / VENDEDOR</th>
                        <th class="p-4">TELÉFONO / EMAIL</th>
                        <th class="p-4">CRÉDITO</th>
                        <th class="p-4">ESTADO</th>
                        <th class="p-4 text-center">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700 font-semibold">
                    @forelse ($proveedores as$prov)
                        @php
                            $razon = strtolower($prov->razon_social);
                            $comercial = strtolower($prov->nombre_comercial ?? '');
                            $contacto = strtolower($prov->contacto_nombre ?? '');
                            $rfc = strtolower($prov->rut_rfc ?? '');
                        @endphp
                        <tr class="hover:bg-teal-50/20 transition-colors" 
                            x-show="(!searchQuery || '{{ $razon }}'.includes(searchQuery.toLowerCase()) || '{{ $comercial }}'.includes(searchQuery.toLowerCase()) \vert{}\vert{} '{{$contacto }}'.includes(searchQuery.toLowerCase()) || '{{ $rfc }}'.includes(searchQuery.toLowerCase())) && (!selectedCategoria \vert{}\vert{} '{{$prov->categoria }}' === selectedCategoria)">
                            
                            <td class="p-4">
                                <p class="font-extrabold text-gray-800 uppercase">{{ $prov->razon_social }}</p>
                                @if($prov->nombre_comercial)
                                    <p class="text-[10px] text-teal-700 font-bold uppercase">{{ $prov->nombre_comercial }}</p>
                                @endif
                                @if($prov->rut_rfc)
                                    <p class="text-[10px] text-gray-400 uppercase">RFC: {{ $prov->rut_rfc }}</p>
                                @endif
                            </td>

                            <td class="p-4">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-bold border border-slate-200 text-[10px] uppercase">
                                    {{ $prov->categoria }}
                                </span>
                            </td>

                            <td class="p-4 uppercase">
                                <p class="font-bold text-gray-800">{{ $prov->contacto_nombre ?? 'N/D' }}</p>
                            </td>

                            <td class="p-4">
                                <p class="font-bold text-slate-700">{{ $prov->telefono ?? 'S/N' }}</p>
                                <p class="text-[10px] text-teal-600 font-semibold">{{ $prov->email ?? '' }}</p>
                            </td>

                            <td class="p-4">
                                @if($prov->dias_credito > 0)
                                    <span class="text-teal-800 font-extrabold">{{ $prov->dias_credito }} DÍAS</span>
                                @else
                                    <span class="text-slate-400 font-semibold">CONTADO</span>
                                @endif
                            </td>

                            <td class="p-4">
                                @if($prov->estado === 'activo')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> ACTIVO
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">
                                        INACTIVO
                                    </span>
                                @endif
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="editarProveedor({{ json_encode($prov) }})" class="bg-teal-600 hover:bg-teal-700 text-white p-2 rounded-xl text-xs font-bold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center cursor-pointer" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button type="button" onclick="confirmarEliminacionProveedor({{ $prov->id }}, '{{$prov->razon_social }}')" class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-xl text-xs font-semibold shadow-xs hover:shadow-md transition-all active:scale-95 flex items-center justify-center cursor-pointer" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-gray-400 italic">
                                <i class="bi bi-truck text-4xl mb-2 block text-gray-300"></i>
                                No hay proveedores registrados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- TARJETAS GRID -->
        <div x-show="viewMode === 'grid'" class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($proveedores as$prov)
                @php
                    $razon = strtolower($prov->razon_social);
                    $comercial = strtolower($prov->nombre_comercial ?? '');
                    $contacto = strtolower($prov->contacto_nombre ?? '');
                    $rfc = strtolower($prov->rut_rfc ?? '');
                    $inicial = strtoupper(substr($prov->razon_social, 0, 2));
                @endphp

                <div x-show="(!searchQuery || '{{ $razon }}'.includes(searchQuery.toLowerCase()) || '{{ $comercial }}'.includes(searchQuery.toLowerCase()) \vert{}\vert{} '{{$contacto }}'.includes(searchQuery.toLowerCase()) || '{{ $rfc }}'.includes(searchQuery.toLowerCase())) && (!selectedCategoria \vert{}\vert{} '{{$prov->categoria }}' === selectedCategoria)"
                     class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs hover:border-teal-500 hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <span class="text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200">
                                {{ $prov->categoria }}
                            </span>
                            @if($prov->estado === 'activo')
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">ACTIVO</span>
                            @else
                                <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full border border-slate-200">INACTIVO</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-800 font-black flex items-center justify-center text-xs shrink-0">
                                {{ $inicial }}
                            </div>
                            <div class="truncate">
                                <h4 class="font-bold text-gray-800 text-xs truncate uppercase">{{ $prov->razon_social }}</h4>
                                <p class="text-[11px] text-teal-700 font-extrabold truncate uppercase">{{ $prov->contacto_nombre ?? 'Sin contacto' }}</p>
                            </div>
                        </div>

                        <div class="text-xs space-y-1 text-slate-600 pt-2 border-t border-slate-100">
                            <p><i class="bi bi-telephone text-teal-600 mr-1"></i> {{ $prov->telefono ?? 'S/N' }}</p>
                            <p class="truncate"><i class="bi bi-envelope text-teal-600 mr-1"></i> {{ $prov->email ?? 'N/D' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button type="button" @click="editarProveedor({{ json_encode($prov) }})" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold shadow-xs flex items-center gap-1 transition cursor-pointer">
                            <i class="bi bi-pencil-square"></i> Editar
                        </button>
                        <button onclick="confirmarEliminacionProveedor({{ $prov->id }}, '{{$prov->razon_social }}')" type="button" class="bg-rose-500 hover:bg-rose-600 text-white p-1.5 rounded-xl text-xs font-semibold shadow-xs transition cursor-pointer" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center text-gray-400 italic">
                    No hay proveedores registrados.
                </div>
            @endforelse
        </div>
    </div>

    <!-- INCLUIR MODAL DE CREACIÓN / EDICIÓN -->
    @include('proveedores.modalProveedores')
</div>

<script>
function confirmarEliminacionProveedor(id, nombre) {
    Swal.fire({
        title: '¿Eliminar proveedor?',
        html: `Estás a punto de eliminar a <b class="text-teal-700">${nombre}</b>.<br><span class="text-xs text-rose-500">Esta acción no se puede deshacer.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="bi bi-trash-fill"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        target: 'body',
        customClass: {
            container: 'z-[10050]',
            popup: 'rounded-3xl p-6 border border-slate-100 shadow-2xl bg-white font-sans',
            title: 'text-xl font-black text-slate-800',
            confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95',
            cancelButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm transition-all active:scale-95'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/proveedores/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.notificar(data.message || 'El proveedor ha sido eliminado.', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    window.notificar(data.message || 'No se pudo eliminar el proveedor.', 'error');
                }
            })
            .catch(() => {
                window.notificar('Ocurrió un error al procesar la solicitud.', 'error');
            });
        }
    });
}
</script>
@endsection