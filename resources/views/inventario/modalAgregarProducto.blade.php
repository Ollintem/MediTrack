<!-- MODAL REGISTRAR NUEVO MEDICAMENTO / PRODUCTO -->
<template x-teleport="body">
    <div x-show="openCreateModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="openCreateModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-xs" 
                 @click="openCreateModal = false"></div>

            <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100">
                
                <!-- Encabezado Modal -->
                <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-teal-700 to-emerald-600 text-white">
                    <div class="flex items-center gap-2 font-black text-sm uppercase">
                        <i class="bi bi-capsule text-lg"></i>
                        <span>Registrar Nuevo Medicamento</span>
                    </div>
                    <button type="button" @click="openCreateModal = false" class="text-white/80 hover:text-white text-lg font-bold">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Formulario -->
                <form action="{{ route('inventario.store') }}" 
                      method="POST" 
                      x-data="{
                          codigo: '{{ old('codigo') }}',
                          nombre: '{{ old('nombre') }}',
                          precio_compra: '{{ old('precio_compra') }}',
                          precio_venta: '{{ old('precio_venta') }}',
                          stock_actual: '{{ old('stock_actual', 0) }}',
                          stock_minimo: '{{ old('stock_minimo', 5) }}',
                          
                          validarFormulario(e) {
                              if (!this.codigo || this.codigo.length !== 13 || !this.nombre || !this.precio_compra || !this.precio_venta || this.stock_actual === '' || this.stock_minimo === '') {
                                  e.preventDefault();
                                  if (typeof Swal !== 'undefined') {
                                      Swal.fire({
                                          title: '¡APARTADOS INCOMPLETOS!',
                                          text: 'Por favor llena los apartados vacíos o verifica el código de barras (13 dígitos) antes de continuar.',
                                          icon: 'warning',
                                          confirmButtonColor: '#0d9488',
                                          confirmButtonText: 'ENTENDIDO',
                                          customClass: { container: 'z-[10050]' }
                                      });
                                  }
                              }
                          }
                      }"
                      @submit="validarFormulario($event)"
                      class="p-6 space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Código de Barras EAN-13 (13 Dígitos) -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-[11px] font-bold text-gray-700 uppercase">CÓDIGO DE BARRAS (EAN-13) *</label>
                                <span class="text-[10px] text-gray-400 font-bold">13 DÍGITOS</span>
                            </div>
                            <input type="text" 
                                   name="codigo" 
                                   x-model="codigo"
                                   required 
                                   maxlength="13" 
                                   minlength="13"
                                   pattern="[0-9]{13}"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 13);"
                                   title="El código de barras debe contener exactamente 13 dígitos numéricos."
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none tracking-wider" 
                                   placeholder="7501058618210">
                        </div>

                        <!-- Categoría con Dropdown Personalizado -->
                        <div class="relative" x-data="{ openCatModal: false, catNombre: '', catId: '' }">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-[11px] font-bold text-gray-700 uppercase">CATEGORÍA *</label>
                                <button type="button" @click="openCategoriaModal = true" class="text-[11px] font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1 focus:outline-none transition-colors">
                                    <i class="bi bi-gear-fill"></i> Gestionar Categorías
                                </button>
                            </div>
                            <input type="hidden" name="categoria_id" :value="catId" required>

                            <button type="button" 
                                    @click="openCatModal = !openCatModal" 
                                    @click.outside="openCatModal = false"
                                    class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold bg-white text-left flex justify-between items-center focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                <span x-text="catNombre || 'SELECCIONE CATEGORÍA...'" :class="!catNombre ? 'text-gray-400 font-normal' : 'text-gray-800 font-bold uppercase'"></span>
                                <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
                            </button>

                            <div x-show="openCatModal" class="absolute z-50 mt-1 w-full bg-white rounded-2xl border border-gray-100 shadow-xl py-1.5 text-xs font-bold uppercase overflow-hidden" x-cloak>
                                @foreach($categorias as $cat)
                                @php $cNom = strtoupper($cat->nombre); @endphp
                                <button type="button" 
                                        @click="catId = '{{ $cat->id }}'; catNombre = '{{ $cNom }}'; openCatModal = false" 
                                        class="w-full text-left px-4 py-2 hover:bg-teal-50 hover:text-teal-700 transition-colors">
                                    {{ $cNom }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Nombre Comercial -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">NOMBRE DE MEDICAMENTO Y PRESENTACIÓN *</label>
                        <input type="text" 
                               name="nombre" 
                               x-model="nombre"
                               required 
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none" 
                               placeholder="EJ. PARACETAMOL 500MG CAJA C/30 TABLETAS">
                    </div>

                    <!-- Precios y Stock Inicial -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. COMPRA ($) *</label>
                            <input type="number" 
                                   step="0.01" 
                                   name="precio_compra" 
                                   x-model="precio_compra"
                                   required 
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none" 
                                   placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">P. VENTA ($) *</label>
                            <input type="number" 
                                   step="0.01" 
                                   name="precio_venta" 
                                   x-model="precio_venta"
                                   required 
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none" 
                                   placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">STOCK INICIAL *</label>
                            <input type="number" 
                                   name="stock_actual" 
                                   x-model="stock_actual"
                                   required 
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">STOCK MÍNIMO *</label>
                            <input type="number" 
                                   name="stock_minimo" 
                                   x-model="stock_minimo"
                                   required 
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Descripción Opcional -->
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">DESCRIPCIÓN / SUSTANCIA ACTIVA (OPCIONAL)</label>
                        <textarea name="descripcion" 
                                  rows="2" 
                                  class="w-full px-3.5 py-2.5 rounded-2xl border border-gray-200 text-xs font-bold uppercase focus:ring-2 focus:ring-teal-500 focus:outline-none" 
                                  placeholder="EJ. PARACETAMOL ANALGÉSICO Y ANTIPIRÉTICO"></textarea>
                    </div>

                    <!-- Footer Modal -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="openCreateModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-bold uppercase">CANCELAR</button>
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-xs font-bold shadow-lg shadow-teal-600/20 uppercase active:scale-95 transition-all">GUARDAR MEDICAMENTO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>