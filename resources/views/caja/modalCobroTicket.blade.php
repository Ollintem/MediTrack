<!-- MODAL 1: SELECCIÓN Y CONFIRMACIÓN DEL MÉTODO DE PAGO -->
<div x-show="openModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div @click.outside="openModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5">
        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
            <h3 class="text-sm font-black text-gray-800 uppercase">PROCESAR COBRO: <span class="text-teal-600" x-text="ticketSeleccionado.codigo_ticket"></span></h3>
            <button @click="openModal = false" class="text-gray-400 hover:text-gray-600"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="space-y-3">
            <div class="p-4 bg-slate-50 rounded-2xl space-y-2">
                <p class="text-xs font-extrabold text-gray-700 uppercase" x-text="`PACIENTE: ${ticketSeleccionado.paciente ? ticketSeleccionado.paciente.nombre_completo : 'PÚBLICO GENERAL'}`"></p>
                <div class="flex justify-between items-baseline pt-2 border-t border-gray-200">
                    <span class="text-xs font-bold text-gray-400 uppercase">TOTAL A COBRAR:</span>
                    <span class="text-2xl font-black text-teal-700" x-text="`$${parseFloat(ticketSeleccionado.monto_total || 0).toFixed(2)}`"></span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1">MÉTODO DE PAGO *</label>
                <select x-model="metodoPago" class="w-full bg-slate-50 border border-gray-200 text-xs font-bold rounded-2xl px-3 py-3 uppercase focus:outline-none focus:border-teal-500">
                    <option value="Efectivo">EFECTIVO</option>
                    <option value="Tarjeta">TARJETA DE DÉBITO / CRÉDITO</option>
                    <option value="Transferencia">TRANSFERENCIA BANCARIA</option>
                </select>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button @click="openModal = false" class="w-1/2 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-2xl text-xs uppercase">
                CANCELAR
            </button>
            <button @click="confirmarPago()" :disabled="cargando" class="w-1/2 py-3 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-2xl text-xs uppercase shadow-lg shadow-teal-600/20">
                <span x-text="cargando ? 'PROCESANDO...' : 'CONFIRMAR PAGO'"></span>
            </button>
        </div>
    </div>
</div>

<!-- MODAL 2: COMPROBANTE OFICIAL DE PAGO DEFINITIVO -->
<div x-show="openTicketPagadoModal" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4 text-center">
        
        <div id="comprobante-oficial-pago" class="font-mono text-xs text-gray-800 space-y-2 text-left">
            <div class="text-center border-b border-dashed border-gray-300 pb-2">
                <h2 class="text-sm font-black uppercase text-teal-800">CLÍNICA MEDITRACK</h2>
                <p class="text-[9px] font-bold text-gray-400">COMPROBANTE OFICIAL DE PAGO</p>
                <span class="inline-block mt-1 px-3 py-0.5 bg-emerald-100 text-emerald-800 font-black text-xs rounded-lg border border-emerald-300" x-text="`PAGADO: ${ticketCobrado?.codigo_ticket}`"></span>
            </div>

            <div class="text-[10px] space-y-0.5">
                <p><strong>PACIENTE:</strong> <span x-text="ticketCobrado?.paciente ? ticketCobrado.paciente.nombre_completo : 'PÚBLICO GENERAL'"></span></p>
                <p><strong>FECHA DE PAGO:</strong> <span x-text="new Date().toLocaleString('es-MX')"></span></p>
                <p><strong>MÉTODO DE PAGO:</strong> <span class="uppercase font-bold text-teal-700" x-text="metodoPago"></span></p>
            </div>

            <!-- DESGLOSE CONCEPTO/PRODUCTOS -->
            <table class="w-full text-left text-[10px] border-y border-dashed border-gray-300 py-1.5 my-2">
                <thead>
                    <tr class="font-bold border-b border-gray-200">
                        <th>CANT</th>
                        <th>CONCEPTO</th>
                        <th class="text-right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="det in ticketCobrado?.detalles" :key="det.id">
                        <tr>
                            <td class="font-bold" x-text="det.cantidad"></td>
                            <td class="uppercase truncate max-w-[100px]" x-text="det.producto ? det.producto.nombre : 'MEDICAMENTO'"></td>
                            <td class="text-right font-bold" x-text="`$${parseFloat(det.subtotal).toFixed(2)}`"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div class="flex justify-between items-baseline pt-1">
                <span class="text-xs font-bold">MONTO LIQUIDADO:</span>
                <span class="text-base font-black text-emerald-700" x-text="`$${parseFloat(ticketCobrado?.monto_total || 0).toFixed(2)}`"></span>
            </div>

            <p class="text-[9px] text-center text-gray-500 font-sans uppercase pt-2">PRESENTA ESTE TICKET EN FARMACIA PARA EL SURTIDO DE TU MEDICAMENTO.</p>
        </div>

        <div class="flex gap-2 pt-3 border-t border-gray-100 print:hidden">
            <button type="button" @click="openTicketPagadoModal = false; location.reload();" 
                    class="w-1/2 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-xl text-xs uppercase">
                CERRAR
            </button>
            <button type="button" @click="imprimirComprobante()" 
                    class="w-1/2 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-xl text-xs uppercase shadow-md shadow-teal-600/20 flex items-center justify-center gap-1">
                <i class="bi bi-printer-fill"></i> IMPRIMIR
            </button>
        </div>

    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #comprobante-oficial-pago, #comprobante-oficial-pago * { visibility: visible; }
    #comprobante-oficial-pago { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>