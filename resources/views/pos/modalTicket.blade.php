{{-- COMPROBANTE DEL TICKET: papel térmico con folio, vigencia en vivo y código de barras para caja central --}}
{{-- Usa de pos/index: openTicketModal, ticketGenerado, CLINICA, folio(), nombrePaciente(), lineas(), restante(), avanceVigencia(), estadoDe(), barrasDe(), imprimirTicketModal() --}}
<template x-teleport="body">
    <div class="psm-velo" x-show="openTicketModal" x-cloak @keydown.escape.window="openTicketModal && (openTicketModal = false)"
         x-transition:enter="psm-t" x-transition:enter-start="psm-0" x-transition:enter-end="psm-1"
         x-transition:leave="psm-t-s" x-transition:leave-start="psm-1" x-transition:leave-end="psm-0">
        <div class="pst" role="dialog" aria-modal="true" aria-label="Comprobante del ticket" @click.outside="openTicketModal = false">
            <div id="seccion-ticket-imprimible" class="pst-papel">
                <p class="pst-marca" x-text="CLINICA"></p>
                <p class="pst-sub">Pedido de farmacia · pagar en caja</p>

                <p class="pst-folio t" x-text="folio(ticketGenerado)"></p>
                <span class="pst-bar" x-html="barrasDe(ticketGenerado)"></span>

                <div class="pst-vig" :class="ticketGenerado ? estadoDe(ticketGenerado) : ''">
                    <template x-if="ticketGenerado && estadoDe(ticketGenerado) === 'pendiente'">
                        <div>
                            <p><span>Aparta las piezas</span><b class="t" x-text="restante(ticketGenerado)"></b></p>
                            <i class="pst-barra"><i :style="'width:' + avanceVigencia(ticketGenerado) + '%'"></i></i>
                            <small x-text="'Vence a las ' + hora(ticketGenerado.expires_at)"></small>
                        </div>
                    </template>
                    <p x-show="ticketGenerado && estadoDe(ticketGenerado) !== 'pendiente'" class="pst-sello" x-text="ticketGenerado ? textoEstado(estadoDe(ticketGenerado)) : ''"></p>
                </div>

                <dl class="pst-datos">
                    <div><dt>Paciente</dt><dd x-text="nombrePaciente(ticketGenerado)"></dd></div>
                    <div><dt>Fecha</dt><dd x-text="ticketGenerado ? fecha(ticketGenerado.created_at) + ' ' + hora(ticketGenerado.created_at) : ''"></dd></div>
                </dl>
                <table class="pst-tabla">
                    <thead><tr><th>Cant.</th><th>Producto</th><th>Importe</th></tr></thead>
                    <tbody>
                        <template x-for="l in lineas(ticketGenerado)" :key="'pt' + l.id">
                            <tr><td class="t" x-text="l.cantidad"></td><td x-text="l.nombre"></td><td class="t" x-text="dinero(l.subtotal)"></td></tr>
                        </template>
                    </tbody>
                </table>
                <p class="pst-tot"><span>Total a pagar</span><b class="t" x-text="dinero(ticketGenerado ? ticketGenerado.monto_total : 0)"></b></p>
                <p class="pst-pie">Presenta este código en caja central. Después de pagar, recoge tus productos en el mostrador.</p>
            </div>
            <div class="pst-acc">
                <button type="button" @click="openTicketModal = false">Cerrar</button>
                <button type="button" class="prim" @click="imprimirTicketModal()"><i class="bi bi-printer"></i>Imprimir</button>
            </div>
        </div>
    </div>
</template>

@verbatim
<style>
.pst { --tinta: #0b1e1c; --media: #56625f; --cred: #0b5a54; --verde: #08734f; --ambar: #9a5800; --foil: #a6efe2;
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    width: min(360px, 100%); display: grid; gap: 12px; font-family: var(--ui); animation: psm-in .2s cubic-bezier(.2, .8, .2, 1); }
.pst p, .pst dl { margin: 0; }
.pst .t { font-variant-numeric: tabular-nums; }
.pst-papel { position: relative; max-height: calc(100vh - 110px); overflow-y: auto; padding: 22px 22px 18px; background: #fffef9; color: var(--tinta); border-radius: 6px;
    box-shadow: 0 30px 60px -25px rgba(0, 0, 0, .6); font: 500 12px/1.45 var(--mono); }
.pst-marca { text-align: center; font: 800 22px/1.1 var(--disp); color: var(--cred); }
.pst-sub { text-align: center; font-size: 10.5px; letter-spacing: .14em; text-transform: uppercase; color: var(--media); }
.pst-folio { margin-top: 14px; text-align: center; font: 700 24px var(--mono); letter-spacing: .06em; }
.pst-bar { display: block; margin: 6px 0 2px; }
.pst-bar svg { display: block; width: 100%; height: 54px; }
.pst-vig { margin-top: 10px; padding: 10px 12px; border-radius: 10px; background: #fdf3e1; color: var(--ambar); }
.pst-vig p { display: flex; align-items: baseline; justify-content: space-between; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; }
.pst-vig b { font: 800 26px/1 var(--disp); letter-spacing: -.01em; }
.pst-vig small { display: block; margin-top: 4px; font-size: 11px; }
.pst-barra { display: block; height: 5px; margin-top: 6px; border-radius: 9px; background: rgba(154, 88, 0, .16); overflow: hidden; }
.pst-barra i { display: block; height: 100%; border-radius: inherit; background: var(--ambar); transition: width 1s linear; }
.pst-vig.pagado, .pst-vig.entregado { background: #e5f4ee; color: var(--verde); }
.pst-vig.expirado, .pst-vig.cancelado { background: #eef3f1; color: var(--media); }
.pst-sello { justify-content: center !important; font: 800 15px var(--disp) !important; letter-spacing: .14em !important; }
.pst-datos { margin-top: 12px; padding: 10px 0; border-top: 1px dashed #d8d4c6; display: grid; gap: 3px; }
.pst-datos div { display: flex; justify-content: space-between; gap: 10px; }
.pst-datos dt { color: var(--media); }
.pst-datos dd { margin: 0; text-align: right; font-weight: 600; }
.pst-tabla { width: 100%; border-collapse: collapse; border-top: 1px dashed #d8d4c6; border-bottom: 1px dashed #d8d4c6; }
.pst-tabla th { padding: 6px 0 4px; text-align: left; font-weight: 600; font-size: 10.5px; color: var(--media); text-transform: uppercase; letter-spacing: .08em; }
.pst-tabla td { padding: 3px 0; vertical-align: top; }
.pst-tabla th:last-child, .pst-tabla td:last-child { text-align: right; white-space: nowrap; }
.pst-tabla td:first-child { width: 38px; }
.pst-tot { display: flex; justify-content: space-between; align-items: baseline; margin-top: 10px !important; }
.pst-tot b { font: 800 26px var(--disp); }
.pst-pie { margin-top: 10px !important; text-align: center; font: 500 11px/1.4 var(--ui); color: var(--media); }
.pst-acc { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.pst-acc button { height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #fff; border: 1px solid #dbe4e1; font: 600 14px var(--ui); color: var(--tinta); cursor: pointer; }
.pst-acc button.prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.pst-acc button.prim .bi { color: var(--foil); }
@media print {
    body * { visibility: hidden; }
    #seccion-ticket-imprimible, #seccion-ticket-imprimible * { visibility: visible; }
    #seccion-ticket-imprimible { position: absolute; left: 0; top: 0; width: 80mm; max-height: none; overflow: visible; box-shadow: none; border-radius: 0; background: #fff; }
    .pst-vig { background: #fff !important; border: 1px solid #000; color: #000 !important; }
    .pst-barra { display: none; }
}
</style>
@endverbatim