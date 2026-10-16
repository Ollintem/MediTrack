{{-- COMPROBANTE DE PAGO: vista previa en papel térmico e impresión (solo se imprime #comprobante-oficial-pago) --}}
{{-- Usa de caja/index: openTicketPagadoModal, ticketCobrado, dinero(), titulo(), barras() e imprimirComprobante() --}}
<template x-teleport="body">
    <div x-show="openTicketPagadoModal" x-cloak class="cjm-velo" @keydown.escape.window="openTicketPagadoModal && (openTicketPagadoModal = false)"
         x-transition:enter="cjm-t" x-transition:enter-start="cjm-0" x-transition:enter-end="cjm-1"
         x-transition:leave="cjm-t-s" x-transition:leave-start="cjm-1" x-transition:leave-end="cjm-0">
        <div class="cjm" role="dialog" aria-modal="true" aria-label="Comprobante de pago" @click.outside="openTicketPagadoModal = false">
            <div id="comprobante-oficial-pago" class="cjm-papel">
                <p class="cjm-marca">MediTrack</p>
                <p class="cjm-sub">Comprobante de pago</p>
                <p class="cjm-sello">Pagado</p>
                <dl class="cjm-datos">
                    <div><dt>Folio</dt><dd x-text="ticketCobrado ? ticketCobrado.codigo : ''"></dd></div>
                    <div><dt>Fecha</dt><dd x-text="new Date().toLocaleDateString('es-MX') + ' ' + (ticketCobrado ? ticketCobrado.hora : '')"></dd></div>
                    <div><dt>Paciente</dt><dd x-text="ticketCobrado && ticketCobrado.paciente ? ticketCobrado.paciente : 'Público general'"></dd></div>
                    <div><dt>Método</dt><dd x-text="ticketCobrado ? ticketCobrado.metodo : ''"></dd></div>
                </dl>
                <table class="cjm-tabla">
                    <thead><tr><th>Cant.</th><th>Concepto</th><th>Importe</th></tr></thead>
                    <tbody>
                        <template x-for="det in (ticketCobrado ? ticketCobrado.detalles : [])" :key="'c' + det.id">
                            <tr><td x-text="det.cantidad"></td><td x-text="titulo(det.nombre)"></td><td x-text="dinero(det.subtotal)"></td></tr>
                        </template>
                    </tbody>
                </table>
                <p class="cjm-tot"><span>Total pagado</span><b x-text="dinero(ticketCobrado ? ticketCobrado.total : 0)"></b></p>
                <p class="cjm-cambio" x-show="ticketCobrado && ticketCobrado.cambio !== null && ticketCobrado.cambio !== undefined">
                    <span x-text="'Recibido ' + dinero(ticketCobrado ? ticketCobrado.recibido : 0)"></span><span x-text="'Cambio ' + dinero(ticketCobrado ? ticketCobrado.cambio : 0)"></span>
                </p>
                <span class="cjm-bar" x-html="ticketCobrado ? barras(ticketCobrado.codigo) : ''"></span>
                <p class="cjm-pie">Presenta este comprobante en farmacia para recibir tus productos.</p>
            </div>
            <div class="cjm-acc">
                <button type="button" @click="openTicketPagadoModal = false">Cerrar</button>
                <button type="button" class="prim" @click="imprimirComprobante()"><i class="bi bi-printer"></i>Imprimir</button>
            </div>
        </div>
    </div>
</template>

@verbatim
<style>
.cjm-velo { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; padding: 16px; background: rgba(4, 18, 17, .62); backdrop-filter: blur(4px); font-family: 'Geist', ui-sans-serif, system-ui, sans-serif; }
.cjm-t { transition: opacity .2s cubic-bezier(.2, .8, .2, 1); } .cjm-t-s { transition: opacity .13s ease-in; } .cjm-0 { opacity: 0; } .cjm-1 { opacity: 1; }
.cjm { width: min(360px, 100%); display: grid; gap: 12px; }
.cjm-papel { position: relative; padding: 22px 22px 18px; background: #fffef9; color: #0b1e1c; border-radius: 6px; box-shadow: 0 30px 60px -25px rgba(0, 0, 0, .6); font-family: 'Geist Mono', ui-monospace, monospace; font-size: 12px; max-height: 70vh; overflow-y: auto; }
.cjm-marca { text-align: center; font: 800 22px 'Bricolage Grotesque', ui-sans-serif, sans-serif; color: #0b5a54; }
.cjm-sub { text-align: center; font-size: 10.5px; letter-spacing: .14em; text-transform: uppercase; color: #56625f; }
.cjm-sello { width: max-content; margin: 10px auto 0; padding: 2px 12px; border: 2px solid #08734f; border-radius: 6px; color: #08734f; font: 800 15px 'Bricolage Grotesque', ui-sans-serif, sans-serif; letter-spacing: .12em; text-transform: uppercase; rotate: -4deg; }
.cjm-datos { margin-top: 12px; padding: 10px 0; border-top: 1px dashed #d8d4c6; display: grid; gap: 3px; }
.cjm-datos div { display: flex; justify-content: space-between; gap: 10px; }
.cjm-datos dt { color: #56625f; }
.cjm-datos dd { text-align: right; font-weight: 600; }
.cjm-tabla { width: 100%; border-collapse: collapse; border-top: 1px dashed #d8d4c6; border-bottom: 1px dashed #d8d4c6; }
.cjm-tabla th { padding: 6px 0 4px; text-align: left; font-weight: 600; font-size: 10.5px; color: #56625f; text-transform: uppercase; letter-spacing: .08em; }
.cjm-tabla td { padding: 3px 0; vertical-align: top; }
.cjm-tabla th:last-child, .cjm-tabla td:last-child { text-align: right; }
.cjm-tabla td:first-child { width: 38px; }
.cjm-tot { display: flex; justify-content: space-between; align-items: baseline; margin-top: 10px; }
.cjm-tot b { font: 800 24px 'Bricolage Grotesque', ui-sans-serif, sans-serif; }
.cjm-cambio { display: flex; justify-content: space-between; margin-top: 4px; color: #56625f; }
.cjm-bar { display: block; margin-top: 12px; }
.cjm-bar svg { display: block; width: 100%; height: 44px; }
.cjm-pie { margin-top: 10px; text-align: center; font-family: 'Geist', ui-sans-serif, sans-serif; font-size: 11px; color: #56625f; }
.cjm-acc { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.cjm-acc button { height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #fff; border: 1px solid #dbe4e1; font: 600 14px 'Geist', ui-sans-serif, sans-serif; color: #0b1e1c; cursor: pointer; }
.cjm-acc button.prim { background: #0b1e1c; border-color: #0b1e1c; color: #fff; }
@media print {
    body * { visibility: hidden; }
    #comprobante-oficial-pago, #comprobante-oficial-pago * { visibility: visible; }
    #comprobante-oficial-pago { position: absolute; left: 0; top: 0; width: 80mm; max-height: none; overflow: visible; box-shadow: none; border-radius: 0; }
}
</style>
@endverbatim
