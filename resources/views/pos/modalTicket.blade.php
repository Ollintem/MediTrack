<!-- MODAL DE IMPRESIÓN / COMPROBANTE DE FILTRO CON CÓDIGO DE BARRAS -->
<template x-teleport="body">
    <div x-show="openTicketModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="openTicketModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                 @click="openTicketModal = false"></div>

            <div x-show="openTicketModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="inline-block w-full max-w-xs my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-gray-100 p-5 space-y-4">

                <!-- ENCABEZADO DEL MODAL -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-2 print:hidden">
                    <div class="flex items-center gap-2 font-black text-xs text-amber-900 uppercase">
                        <i class="bi bi-receipt-cutoff text-base"></i>
                        <span>COMPROBANTE DE FILTRO</span>
                    </div>
                    <button type="button" @click="openTicketModal = false" class="text-gray-400 hover:text-gray-600 font-bold focus:outline-none">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- CONTENIDO DEL COMPROBANTE -->
                <div id="seccion-ticket-imprimible" class="p-4 border border-dashed border-amber-300 bg-amber-50/60 rounded-2xl space-y-3 font-mono text-xs text-gray-800">

                    <div class="text-center">
                        <h2 class="font-black text-amber-950 text-sm tracking-wider uppercase">MEDITRACK CLÍNICA</h2>
                        <p class="text-[9px] font-bold text-amber-700 uppercase">COMPROBANTE DE PEDIDO / PAGAR EN CAJA</p>
                    </div>

                    <div class="space-y-1 text-[11px] pt-1">
                        <div class="flex justify-between">
                            <span class="font-bold text-amber-900">FOLIO:</span>
                            <span class="font-black text-teal-800" x-text="ticketGenerado?.codigo_ticket"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold text-amber-900">PACIENTE:</span>
                            <span class="font-extrabold uppercase text-gray-700 truncate max-w-[140px]" x-text="ticketGenerado?.paciente ? (ticketGenerado.paciente.nombre_completo || (ticketGenerado.paciente.primer_nombre + ' ' + (ticketGenerado.paciente.apellido_paterno || ''))) : 'PÚBLICO GENERAL'"></span>
                        </div>
                    </div>

                    <!-- ALERTA DE VIGENCIA -->
                    <div class="bg-amber-100/90 border border-amber-200/80 p-2 rounded-xl text-center space-y-0.5">
                        <div class="flex justify-center items-center gap-1.5 text-amber-900 font-extrabold text-[11px]">
                            <i class="bi bi-clock-history"></i>
                            <span>VIGENCIA: 25 MINUTOS</span>
                        </div>
                        <p class="text-[9px] font-bold text-amber-700 uppercase">
                            EXPIRA: <span x-text="formatearHoraExpiracion(ticketGenerado?.expires_at)"></span>
                        </p>
                    </div>

                    <!-- TOTAL A PAGAR -->
                    <div class="flex justify-between items-baseline pt-2 border-t border-dashed border-amber-200">
                        <span class="font-black text-amber-950 text-xs">TOTAL A PAGAR:</span>
                        <span class="text-lg font-black text-teal-800" x-text="`$${parseFloat(ticketGenerado?.monto_total || 0).toFixed(2)}`"></span>
                    </div>

                    <!-- CÓDIGO DE BARRAS (CODE128) CON EL FOLIO DEL TICKET -->
                    <!--
                        x-effect se vuelve a ejecutar cada vez que cambia openTicketModal o ticketGenerado,
                        así funciona igual al generar un ticket nuevo que al reimprimir desde el historial.
                        Se usa $el en lugar de buscar el componente con querySelector('[x-data]'),
                        que podía tomar otro componente del layout y no dibujar nada.
                    -->
                    <div class="pt-2 text-center bg-white p-2 rounded-xl border border-amber-200 flex flex-col items-center justify-center">
                        <svg id="codigo-barras-ticket"
                             class="max-w-full h-auto"
                             x-effect="
                                const folio = ticketGenerado?.codigo_ticket;
                                if (openTicketModal && folio) {
                                    $nextTick(() => {
                                        if (typeof JsBarcode === 'function') {
                                            JsBarcode($el, folio, {
                                                format: 'CODE128',
                                                lineColor: '#000000',
                                                background: '#ffffff',
                                                width: 2,
                                                height: 60,
                                                margin: 10,
                                                displayValue: true,
                                                fontSize: 12,
                                                fontOptions: 'bold',
                                                font: 'monospace'
                                            });
                                        }
                                    });
                                }
                             "></svg>
                        <p class="text-[8px] font-extrabold text-amber-800/80 uppercase mt-1">
                            PRESENTAR ESTE CÓDIGO EN CAJA CENTRAL
                        </p>
                    </div>
                </div>

                <!-- ACCIONES / BOTONES -->
                <div class="flex gap-2 pt-1 border-t border-gray-100 print:hidden">
                    <button type="button" @click="openTicketModal = false"
                            class="w-1/2 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold rounded-2xl text-xs uppercase transition-all">
                        CERRAR
                    </button>
                    <button type="button" @click="imprimirTicketModal()"
                            class="w-1/2 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-extrabold rounded-2xl text-xs uppercase shadow-md shadow-teal-600/20 flex items-center justify-center gap-1.5 transition-all active:scale-95">
                        <i class="bi bi-printer-fill"></i> IMPRIMIR
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<!-- LIBRERÍA JSBARCODE -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<style>
@media print {
    body * { visibility: hidden; }
    #seccion-ticket-imprimible, #seccion-ticket-imprimible * { visibility: visible; }
    #seccion-ticket-imprimible {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        background: transparent !important;
    }
    /* Barras negras sobre blanco puro: los lectores fallan con fondos de color */
    #codigo-barras-ticket { background: #ffffff !important; }
}
</style>