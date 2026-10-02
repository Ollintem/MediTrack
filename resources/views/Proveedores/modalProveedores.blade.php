<!-- MODAL ÚNICO PARA CREAR Y EDITAR PROVEEDOR -->
<template x-teleport="body">
    <div x-show="openModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" 
         x-cloak>
        
        <div @click.away="openModal = false" 
             class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full border border-teal-100 flex flex-col overflow-hidden max-h-[90vh]">
            
            <!-- ENCABEZADO DEL MODAL -->
            <div class="px-6 py-4 bg-gradient-to-r from-teal-800 to-emerald-700 text-white flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-emerald-300">
                        <i class="bi bi-truck text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-base" x-text="isEdit ? 'Editar Proveedor' : 'Nuevo Proveedor'"></h3>
                        <p class="text-xs text-teal-100/90 font-medium" x-text="isEdit ? 'Actualiza la información comercial y de contacto' : 'Registra un nuevo distribuidor o insumo'"></p>
                    </div>
                </div>
                <button type="button" @click="openModal = false" class="text-teal-200 hover:text-white text-xl font-bold p-1 cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- FORMULARIO -->
            <form :action="isEdit ? `/proveedores/${form.id}` : '{{ route('proveedores.store') }}'" method="POST" class="p-6 overflow-y-auto space-y-5 text-xs text-slate-700">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- SECCIÓN 1: DATOS COMERCIALES Y FISCALES -->
                <div class="space-y-3">
                    <h4 class="text-[10px] font-black uppercase tracking-wider text-teal-800 border-b border-slate-100 pb-1 flex items-center gap-1.5">
                        <i class="bi bi-building"></i> Datos Comerciales y Fiscales
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block font-bold text-slate-700 mb-1">Razón Social / Empresa <span class="text-rose-500">*</span></label>
                            <input type="text" name="razon_social" x-model="form.razon_social" required placeholder="EJ: FARMACÉUTICA DEL VALLE S.A." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nombre Comercial</label>
                            <input type="text" name="nombre_comercial" x-model="form.nombre_comercial" placeholder="EJ: DROGUERÍA DEL VALLE" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">RFC / RUT / Id Fiscal</label>
                            <input type="text" name="rut_rfc" x-model="form.rut_rfc" placeholder="EJ: DFV900101XXX" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Categoría Principal <span class="text-rose-500">*</span></label>
                            <select name="categoria" x-model="form.categoria" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none font-semibold uppercase">
                                <option value="">SELECCIONAR CATEGORÍA...</option>
                                <option value="Medicamentos">Medicamentos / Farmacia</option>
                                <option value="Insumos Médicos">Insumos y Material Quirúrgico</option>
                                <option value="Equipamiento">Equipamiento Médico</option>
                                <option value="Laboratorio">Reactivos de Laboratorio</option>
                                <option value="Servicios">Mantenimiento y Servicios</option>
                                <option value="Papelería">Papelería y Oficina</option>
                                <option value="Otros">Otros Proveedores</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: CONTACTO Y UBICACIÓN -->
                <div class="space-y-3">
                    <h4 class="text-[10px] font-black uppercase tracking-wider text-teal-800 border-b border-slate-100 pb-1 flex items-center gap-1.5">
                        <i class="bi bi-person-lines-fill"></i> Datos de Contacto
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Contacto / Vendedor</label>
                            <input type="text" name="contacto_nombre" x-model="form.contacto_nombre" placeholder="LIC. ROBERTO GÓMEZ" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Teléfono Directo</label>
                            <input type="text" name="telefono" x-model="form.telefono" placeholder="5512345678" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Correo Electrónico</label>
                            <input type="email" name="email" x-model="form.email" placeholder="ventas@proveedor.com" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none font-semibold">
                        </div>

                        <div class="col-span-3">
                            <label class="block font-bold text-slate-700 mb-1">Dirección Física / Almacén</label>
                            <input type="text" name="direccion" x-model="form.direccion" placeholder="AV. CENTRAL #123, COL. CENTRO" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase font-semibold">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: PAGO Y ESTADO -->
                <div class="space-y-3">
                    <h4 class="text-[10px] font-black uppercase tracking-wider text-teal-800 border-b border-slate-100 pb-1 flex items-center gap-1.5">
                        <i class="bi bi-credit-card"></i> Condiciones Comerciales y Banco
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Días Crédito</label>
                            <input type="number" min="0" name="dias_credito" x-model="form.dias_credito" placeholder="0" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Banco</label>
                            <input type="text" name="banco" x-model="form.banco" placeholder="BBVA / BANAMEX" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Cuenta CLABE / Nº</label>
                            <input type="text" name="cuenta_bancaria" x-model="form.cuenta_bancaria" placeholder="0121800..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estado <span class="text-rose-500">*</span></label>
                            <select name="estado" x-model="form.estado" required class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none font-semibold uppercase">
                                <option value="activo">ACTIVO</option>
                                <option value="inactivo">INACTIVO</option>
                            </select>
                        </div>

                        <div class="col-span-4">
                            <label class="block font-bold text-slate-700 mb-1">Notas Internas / Observaciones</label>
                            <textarea name="notas" x-model="form.notas" rows="2" placeholder="ENTREGAS SOLO DÍAS MARTES Y JUEVES DE 9:00 AM A 1:00 PM" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:outline-none uppercase font-semibold"></textarea>
                        </div>
                    </div>
                </div>

                <!-- PIE DE FORMULARIO -->
                <div class="pt-3 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="openModal = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-black shadow-md transition cursor-pointer flex items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i>
                        <span x-text="isEdit ? 'Guardar Cambios' : 'Registrar Proveedor'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>