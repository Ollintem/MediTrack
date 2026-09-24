<!-- MODAL GESTIONAR CATEGORÍAS DE INVENTARIO -->
<template x-teleport="body">
    <div x-show="openCategoriaModal" class="fixed inset-0 z-[9999] overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openCategoriaModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" 
                 @click="openCategoriaModal = false"></div>

            <div class="inline-block w-full max-w-lg my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100">
                
                <!-- Header Fijo -->
                <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-teal-800 to-emerald-700 text-white">
                    <div class="flex items-center gap-2 font-black text-sm uppercase">
                        <i class="bi bi-tags-fill text-lg text-emerald-300"></i>
                        <span>Gestionar Categorías</span>
                    </div>
                    <button type="button" @click="openCategoriaModal = false" class="text-white/80 hover:text-white text-lg font-bold">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Formulario Rápido Crear Categoría -->
                    <form id="formNuevaCategoria" onsubmit="guardarCategoriaModal(event)" class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-gray-100">
                        @csrf
                        <label class="block text-[11px] font-bold text-gray-700 uppercase">NUEVA CATEGORÍA *</label>
                        <div class="flex gap-2">
                            <input type="text" id="modal_cat_nombre" name="nombre" required placeholder="EJ. ANALGÉSICOS, ANTIBIÓTICOS" class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none">
                            <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold uppercase shadow-md shadow-teal-600/20 flex-shrink-0 active:scale-95 transition-all">
                                Guardar
                            </button>
                        </div>
                        <span id="error_cat_nombre" class="text-[10px] text-rose-600 font-bold hidden block"></span>
                    </form>

                    <!-- Lista de Categorías Existentes -->                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">CATEGORÍAS REGISTRADAS</label>
                        <div class="border border-gray-100 rounded-2xl overflow-hidden max-h-56 overflow-y-auto divide-y divide-gray-100" id="listaCategoriasModal">
                            @if(count($categorias) > 0)
                                @foreach($categorias as $cat)
                                    <div class="flex items-center justify-between p-3 hover:bg-slate-50 transition-colors" id="cat-row-{{ $cat->id }}">
                                        <span class="text-xs font-bold text-gray-800 uppercase">{{ $cat->nombre }}</span>
                                        <button type="button" 
                                                onclick="eliminarCategoriaModal({{ $cat->id }}, '{{ addslashes($cat->nombre) }}')" 
                                                class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white flex items-center justify-center text-xs transition-all" 
                                                title="Eliminar Categoría">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @else
                                <div class="p-4 text-center text-gray-400 text-xs font-bold" id="sinCategoriasMsg">
                                    No hay categorías registradas.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="flex justify-end px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    <button type="button" @click="openCategoriaModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-bold uppercase transition-all">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
function guardarCategoriaModal(e) {
    e.preventDefault();
    const nombreInput = document.getElementById('modal_cat_nombre');
    const errorSpan = document.getElementById('error_cat_nombre');
    if (errorSpan) errorSpan.classList.add('hidden');

    fetch("{{ route('categorias-inventario.store') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ nombre: nombreInput.value })
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Error al guardar la categoría.');
        return data;
    })
    .then(data => {
        if (data.success || data.categoria) {
            const cat = data.categoria || data;
            const cNom = cat.nombre.toUpperCase();

            // Insertar en la lista del modal
            const lista = document.getElementById('listaCategoriasModal');
            const msgSinCat = document.getElementById('sinCategoriasMsg');
            if (msgSinCat) msgSinCat.remove();

            const newRow = document.createElement('div');
            newRow.className = "flex items-center justify-between p-3 hover:bg-slate-50 transition-colors";
            newRow.id = `cat-row-${cat.id}`;
            newRow.innerHTML = `
                <span class="text-xs font-bold text-gray-800 uppercase">${cNom}</span>
                <button type="button" onclick="eliminarCategoriaModal(${cat.id}, '${cNom}')" class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white flex items-center justify-center text-xs transition-all" title="Eliminar Categoría">
                    <i class="bi bi-trash-fill"></i>
                </button>
            `;
            lista.prepend(newRow);

            // Actualizar el dropdown activo en el modal de producto
            if (window.Alpine) {
                const el = document.querySelector('[x-data]');
                if (el && Alpine.$data(el)) {
                    Alpine.$data(el).catId = cat.id;
                    Alpine.$data(el).catNombre = cNom;
                }
            }

            nombreInput.value = '';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Categoría agregada',
                    showConfirmButton: false,
                    timer: 2000
                });
            }
        }
    })
    .catch(err => {
        if (errorSpan) {
            errorSpan.textContent = err.message;
            errorSpan.classList.remove('hidden');
        }
    });
}

function eliminarCategoriaModal(id, nombre) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿ELIMINAR CATEGORÍA?',
            text: `Se eliminará "${nombre}". Los productos asociados perderán esta categoría.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'SÍ, ELIMINAR',
            cancelButtonText: 'CANCELAR',
            customClass: {
                container: 'z-[10050]',
                popup: 'rounded-3xl p-6 border border-gray-100 shadow-2xl bg-white font-sans',
                title: 'text-xl font-extrabold text-gray-800 uppercase',
                confirmButton: 'px-6 py-2.5 rounded-xl font-bold text-xs uppercase shadow-md',
                cancelButton: 'px-6 py-2.5 rounded-xl font-bold text-xs uppercase shadow-sm'
            }
        }).then(result => {
            if (result.isConfirmed) {
                ejecutarEliminacionCategoria(id);
            }
        });
    } else if (confirm(`¿Eliminar la categoría ${nombre}?`)) {
        ejecutarEliminacionCategoria(id);
    }
}

function ejecutarEliminacionCategoria(id) {
    fetch(`/categorias-inventario/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`cat-row-${id}`);
            if (row) row.remove();
            if (typeof window.notificar === 'function') window.notificar('Categoría eliminada', 'success');
        } else {
            alert(data.message || 'Error al eliminar la categoría.');
        }
    });
}
</script>