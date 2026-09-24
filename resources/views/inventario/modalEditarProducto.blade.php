<!-- MODAL EDITAR MEDICAMENTO / PRODUCTO -->
<template x-teleport="body">
    <div x-show="openEditModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openEditModal" class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" @click="openEditModal = false"></div>

            <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100">
                
                <div class="flex items-center justify-between px-6 py-4 bg-amber-500 text-white">
                    <div class="flex items-center gap-2 font-black text-sm uppercase">
                        <i class="bi bi-pencil-square text-lg"></i>
                        <span>Editar Medicamento</span>
                    </div>
                    <button type="button" @click="openEditModal = false" class="text-white/80 hover:text-white text-lg font-bold">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form :action="`/inventario/${productoEditar.id}`" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CÓDIGO DE BARRAS / SKU *</label>
                            <input type="text" name="codigo" x-model="productoEditar.codigo" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>

                        <div class="relative" x-data="{ openEditCat: false }">
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">CATEGORÍA *</label>
                            <input type="hidden" name="categoria_id" :value="productoEditar.categoria_id">

                            <button type="button" 
                                    @click="openEditCat = !openEditCat" 
                                    @click.outside="openEditCat = false"
                                    class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold bg-white text-left flex justify-between items-center focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <span x-text="productoEditar.categoria?.nombre ? productoEditar.categoria.nombre.toUpperCase() : 'SELECCIONE...'" class="text-gray-800 font-bold uppercase"></span>
                                <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
                            </button>

                            <div x-show="openEditCat" class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-gray-100 shadow-xl py-1.5 text-xs font-bold uppercase overflow-hidden" x-cloak>
                                @foreach($categorias as $cat)
                                @php $cNomEdit = strtoupper($cat->nombre); @endphp
                                <button type="button" 
                                        @click="productoEditar.categoria_id = '{{ $cat->id }}'; productoEditar.categoria = { nombre: '{{ $cNomEdit }}' }; openEditCat = false" 
                                        class="w-full text-left px-4 py-2 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                                    {{ $cNomEdit }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">NOMBRE DE MEDICAMENTO Y PRESENTACIÓN *</label>
                        <input type="text" name="nombre" x-model="productoEditar.nombre" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. COMPRA ($) *</label>
                            <input type="number" step="0.01" name="precio_compra" x-model="productoEditar.precio_compra" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. VENTA ($) *</label>
                            <input type="number" step="0.01" name="precio_venta" x-model="productoEditar.precio_venta" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">STOCK MÍNIMO *</label>
                            <input type="number" name="stock_minimo" x-model="productoEditar.stock_minimo" required class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">DESCRIPCIÓN / SUSTANCIA ACTIVA</label>
                        <textarea name="descripcion" x-model="productoEditar.descripcion" rows="2" class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="openEditModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-2xl text-xs font-bold uppercase">CANCELAR</button>
                        <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl text-xs font-bold shadow-lg shadow-amber-500/20 uppercase active:scale-95 transition-all">ACTUALIZAR MEDICAMENTO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>