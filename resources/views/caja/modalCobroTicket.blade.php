<!-- MODAL DE COBRO / RECIBO OFICIAL PAGADO -->
<template x-teleport="body">
    <div x-show="openCobroModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="openCobroModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" 
                 @click="openCobroModal = false"></div>

            <div x-show="openCobroModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 scale-95" 
                 x-transition:enter-end="opacity-100 scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 scale-100" 
                 x-transition:leave-end="opacity-0 scale-95" 
                 class="inline-block w-full max-w-sm my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100 p-6 space-y-4">
                
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 print:hidden">
                    <div class="flex items-center gap-2 font-black text-xs text-teal-800 uppercase">
                        <i class="bi bi-receipt text-base"></i>
                        <span>RECIBO OFICIAL DE PAGO</span>
                    </div>
                    <button type="button" @click="openCobroModal = false" class="text-gray-400 hover:text-gray-600 font-bold focus:outline-none cursor-pointer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- CONTENIDO DEL COMPROBANTE TÉRMICO -->
                <div id="seccion-recibo-imprimible" class="p-4 border border-dashed border-teal-300 bg-teal-50/40 rounded-2xl space-y-3 font-mono text-xs text-gray-800">
                    
                    <div class="text-center">
                        <h2 class="font-black text-teal-950 text-sm tracking-wider uppercase">MEDITRACK CLÍNICA</h2>
                        <p class="text-[9px] font-bold text-teal-700 uppercase">COMPROBANTE OFICIAL DE PAGO</p>
                    </div>

                    <div class="space-y-1 text-[11px] pt-1">
                        <div class="flex justify-between">
                            <span class="font-bold text-teal-900">FOLIO:</span>
                            <span class="font-black text-teal-800" x-text="ticketSeleccionado?.codigo_ticket"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold text-teal-900">PACIENTE:</span>
                            <span class="font-extrabold uppercase text-gray-700 truncate max-w-[170px]" 
                                  x-text="ticketSeleccionado?.paciente ? (ticketSeleccionado.paciente.nombre_completo || ticketSeleccionado.paciente.primer_nombre) : 'PÚBLICO GENERAL'"></span>
                        </div>
                    </div>

                    <!-- SELLO DE ESTADO PAGADO -->
                    <div class="bg-emerald-100 border border-emerald-200 p-2 rounded-xl text-center">
                        <span class="block text-xs font-black text-emerald-900 uppercase">*** PAGADO ***</span>
                        <span class="text-[9px] font-bold text-emerald-700 uppercase" x-text="`MÉTODO: ${metodoPago}`"></span>
                    </div>

                    <!-- DESGLOSE DE PRODUCTOS -->
                    <table class="w-full text-left text-[11px] border-y border-dashed border-teal-200 py-2">
                        <thead>
                            <tr class="font-bold text-teal-900 border-b border-teal-200">
                                <th class="pb-1">CANT</th>
                                <th class="pb-1">CONCEPTO</th>
                                <th class="pb-1 text-right">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-teal-100">
                            <template x-for="det in ticketSeleccionado?.detalles" :key="det.id">
                                <tr>
                                    <td class="py-1 font-bold" x-text="`${det.cantidad}x`"></td>
                                    <td class="py-1 uppercase truncate max-w-[130px]" x-text="det.producto ? det.producto.nombre : 'MEDICAMENTO'"></td>
                                    <td class="py-1 text-right font-bold text-gray-900" x-text="`$${parseFloat(det.subtotal).toFixed(2)}`"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="flex justify-between items-baseline pt-1">
                        <span class="font-black text-teal-950 text-xs">TOTAL PAGADO:</span>
                        <span class="text-base font-black text-teal-800" x-text="`$${parseFloat(ticketSeleccionado?.monto_total || 0).toFixed(2)}`"></span>
                    </div>

                    <div class="pt-2 text-center">
                        <p class="text-[9px] font-extrabold text-teal-800 uppercase">
                            PRESENTAR EN VENTANILLA DE FARMACIA PARA RECOGER SU MEDICAMENTO
                        </p>
                    </div>
                </div>

                <!-- BOTONES DE ACCIÓN -->
                <div class="flex gap-2 pt-2 border-t border-gray-100 print:hidden">
                    <template x-if="ticketSeleccionado?.status === 'pendiente'">
                        <button type="button" 
                                @click="procesarCobroDirecto(ticketSeleccionado.id, metodoPago)" 
                                :disabled="procesandoPago"
                                class="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white font-black text-xs uppercase rounded-2xl shadow-md active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <i class="bi bi-wallet2"></i>
                            <span x-text="procesandoPago ? 'PROCESANDO PAGO...' : 'CONFIRMAR COBRO'"></span>
                        </button>
                    </template>

                    <template x-if="ticketSeleccionado?.status !== 'pendiente'">
                        <div class="flex gap-2 w-full">
                            <button type="button" @click="openCobroModal = false" 
                                    class="w-1/2 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-2xl text-xs uppercase transition-all cursor-pointer">
                                CERRAR
                            </button>
                            <button type="button" @click="imprimirComprobante()" 
                                    class="w-1/2 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-2xl text-xs uppercase shadow-md shadow-teal-600/20 flex items-center justify-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                                <i class="bi bi-printer-fill"></i> IMPRIMIR
                            </button>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #seccion-recibo-imprimible, #seccion-recibo-imprimible * { visibility: visible; }
    #seccion-recibo-imprimible { 
        position: absolute; 
        left: 0; 
        top: 0; 
        width: 100%; 
        border: none !important;
        background: transparent !important;
    }
}
</style>