{{-- COMPROBANTE DE PAGO: papel térmico de 80 mm con sello, folio y código de barras (solo se imprime #comprobante-oficial-pago) --}}
{{-- Usa de caja/index: openTicketPagadoModal, ticketCobrado, D (clínica y turno), dinero(), titulo(), barras(), siguiente() e imprimirComprobante() --}}
<template x-teleport="body">
    <div x-show="openTicketPagadoModal" x-cloak class="cjm-velo" @keydown.escape.window="openTicketPagadoModal && (openTicketPagadoModal = false)"
         @keydown.window="if (openTicketPagadoModal && (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'p') { event.preventDefault(); imprimirComprobante(); }"
         x-transition:enter="cjm-t" x-transition:enter-start="cjm-0" x-transition:enter-end="cjm-1"
         x-transition:leave="cjm-t-s" x-transition:leave-start="cjm-1" x-transition:leave-end="cjm-0">
        <div class="cjm" role="dialog" aria-modal="true" aria-label="Comprobante de pago" @click.outside="openTicketPagadoModal = false">
            <div id="comprobante-oficial-pago" class="cjm-papel">
                <p class="cjm-marca" x-text="D.clinica || 'MediTrack'"></p>
                <p class="cjm-sub">Comprobante de pago · Caja central</p>

                <i class="cjm-sello">Pagado</i>
                <div class="cjm-folio"><b x-text="ticketCobrado ? ticketCobrado.codigo : ''"></b></div>
                <span class="cjm-bar" x-html="ticketCobrado ? barras(ticketCobrado.codigo) : ''"></span>

                <dl class="cjm-datos">
                    <div><dt>Fecha</dt><dd x-text="new Date().toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }) + ' · ' + (ticketCobrado ? ticketCobrado.hora : '')"></dd></div>
                    <div><dt>Paciente</dt><dd x-text="ticketCobrado && ticketCobrado.paciente ? titulo(ticketCobrado.paciente) : 'Público general'"></dd></div>
                    <div><dt>Forma de pago</dt><dd x-text="ticketCobrado ? ticketCobrado.metodo : ''"></dd></div>
                    <div x-show="D.turno && D.turno.cajero"><dt>Cajero</dt><dd x-text="D.turno ? D.turno.cajero : ''"></dd></div>
                </dl>

                <table class="cjm-tabla">
                    <thead><tr><th>Cant.</th><th>Producto</th><th>Importe</th></tr></thead>
                    <tbody>
                        <template x-for="det in (ticketCobrado ? ticketCobrado.detalles : [])" :key="'c' + det.id">
                            <tr><td x-text="det.cantidad"></td><td x-text="titulo(det.nombre)"></td><td x-text="dinero(det.subtotal)"></td></tr>
                        </template>
                    </tbody>
                </table>

                <p class="cjm-tot"><span>Total pagado</span><b x-text="dinero(ticketCobrado ? ticketCobrado.total : 0)"></b></p>
                <div class="cjm-cambio" x-show="ticketCobrado && ticketCobrado.cambio !== null && ticketCobrado.cambio !== undefined">
                    <p><span>Recibido</span><b x-text="dinero(ticketCobrado ? ticketCobrado.recibido : 0)"></b></p>
                    <p><span>Cambio</span><b x-text="dinero(ticketCobrado ? ticketCobrado.cambio : 0)"></b></p>
                </div>

                <p class="cjm-pie"><b>Recoge tus productos en el mostrador de farmacia</b>con este comprobante. Gracias por tu visita.</p>
            </div>
            <div class="cjm-acc">
                <button type="button" @click="openTicketPagadoModal = false">Cerrar</button>
                <button type="button" class="prim" @click="imprimirComprobante()"><i class="bi bi-printer"></i>Imprimir <kbd>Ctrl P</kbd></button>
                <button type="button" class="sig" x-show="vigentes.length" @click="openTicketPagadoModal = false; siguiente()">Siguiente ticket por cobrar<i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>
</template>

@verbatim
<style>
.cjm-velo { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; padding: 16px; background: rgba(4, 18, 17, .62); backdrop-filter: blur(4px); font-family: 'Geist', ui-sans-serif, system-ui, sans-serif; }
.cjm-t { transition: opacity .2s cubic-bezier(.2, .8, .2, 1); } .cjm-t-s { transition: opacity .13s ease-in; } .cjm-0 { opacity: 0; } .cjm-1 { opacity: 1; }
.cjm { width: min(360px, 100%); display: grid; gap: 12px; animation: cjmEntra .22s cubic-bezier(.2, .8, .2, 1); }
@keyframes cjmEntra { from { transform: translateY(12px); } }
.cjm p, .cjm dl { margin: 0; }
.cjm-papel { position: relative; padding: 22px 22px 20px; background: #fffef9; color: #0b1e1c; border-radius: 6px; box-shadow: 0 30px 60px -25px rgba(0, 0, 0, .6);
    font: 500 12px/1.45 'Geist Mono', ui-monospace, monospace; max-height: calc(100vh - 130px); overflow-y: auto; font-variant-numeric: tabular-nums; }
.cjm-marca { text-align: center; font: 800 22px/1.1 'Bricolage Grotesque', ui-sans-serif, sans-serif; color: #0b5a54; }
.cjm-sub { text-align: center; font-size: 10.5px; letter-spacing: .14em; text-transform: uppercase; color: #56625f; }
.cjm-folio { margin-top: 8px; text-align: center; }
.cjm-folio b { font: 700 24px 'Geist Mono', ui-monospace, monospace; letter-spacing: .06em; }
.cjm-sello { display: block; width: max-content; margin: 10px auto 0; padding: 1px 12px; border: 2px solid #08734f; border-radius: 6px; color: #08734f; background: rgba(255, 254, 249, .85);
    font: 800 13px 'Bricolage Grotesque', ui-sans-serif, sans-serif; font-style: normal; letter-spacing: .14em; text-transform: uppercase; rotate: -4deg; animation: cjmSello .4s cubic-bezier(.3, 1.6, .5, 1) .1s backwards; }
@keyframes cjmSello { from { scale: 2; opacity: 0; } }
.cjm-bar { display: block; margin: 6px 0 2px; }
.cjm-bar svg { display: block; width: 100%; height: 50px; }
.cjm-datos { margin-top: 12px; padding: 10px 0; border-top: 1px dashed #d8d4c6; display: grid; gap: 3px; }
.cjm-datos div { display: flex; justify-content: space-between; gap: 10px; }
.cjm-datos dt { color: #56625f; }
.cjm-datos dd { margin: 0; text-align: right; font-weight: 600; }
.cjm-tabla { width: 100%; border-collapse: collapse; border-top: 1px dashed #d8d4c6; border-bottom: 1px dashed #d8d4c6; }
.cjm-tabla th { padding: 6px 0 4px; text-align: left; font-weight: 600; font-size: 10.5px; color: #56625f; text-transform: uppercase; letter-spacing: .08em; }
.cjm-tabla td { padding: 3px 0; vertical-align: top; }
.cjm-tabla th:last-child, .cjm-tabla td:last-child { text-align: right; white-space: nowrap; }
.cjm-tabla td:first-child { width: 38px; }
.cjm-tot { display: flex; justify-content: space-between; align-items: baseline; margin-top: 10px !important; }
.cjm-tot b { font: 800 26px 'Bricolage Grotesque', ui-sans-serif, sans-serif; }
.cjm-cambio { margin-top: 6px; padding: 8px 10px; border-radius: 8px; background: #e5f4ee; color: #08734f; display: grid; gap: 2px; }
.cjm-cambio p { display: flex; justify-content: space-between; }
.cjm-cambio p:last-child b { font-size: 14px; }
.cjm-pie { margin-top: 12px !important; text-align: center; font: 500 11px/1.45 'Geist', ui-sans-serif, sans-serif; color: #56625f; }
.cjm-pie b { display: block; color: #0b1e1c; font-weight: 600; }
.cjm-acc { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.cjm-acc button { height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #fff; border: 1px solid #dbe4e1; font: 600 14px 'Geist', ui-sans-serif, sans-serif; color: #0b1e1c; cursor: pointer; }
.cjm-acc button.prim { background: #0b1e1c; border-color: #0b1e1c; color: #fff; }
.cjm-acc button.prim .bi { color: #a6efe2; }
.cjm-acc button kbd { font: 600 10.5px 'Geist Mono', ui-monospace, monospace; border: 1px solid rgba(255, 255, 255, .25); border-radius: 5px; padding: 0 5px; color: #cfe; }
.cjm-acc button.sig { grid-column: 1 / -1; background: rgba(255, 255, 255, .1); border-color: rgba(255, 255, 255, .25); color: #fff; }
.cjm-acc button.sig:hover { background: rgba(255, 255, 255, .18); }
@media (max-width: 420px) { .cjm-acc button kbd { display: none; } }
@media (prefers-reduced-motion: reduce) { .cjm, .cjm-sello { animation: none; } }
@media print {
    body * { visibility: hidden; }
    #comprobante-oficial-pago, #comprobante-oficial-pago * { visibility: visible; }
    #comprobante-oficial-pago { position: absolute; left: 0; top: 0; width: 80mm; max-height: none; overflow: visible; box-shadow: none; border-radius: 0; background: #fff; }
    .cjm-cambio { background: #fff; border: 1px solid #000; color: #000; }
}
</style>
@endverbatim