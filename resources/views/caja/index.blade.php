@extends('layouts.admin')

{{--
    CAJA CENTRAL · MediTrack
    Si no hay sesión de caja abierta, presenta la pantalla de Apertura de Caja con sugerencia del turno anterior.
    Si la caja está abierta, muestra el panel de cobro, cifra del día y botón con modal de cierre/corte de caja.
--}}

@section('content')

@if(!isset($cajaAbierta) || !$cajaAbierta)
    {{-- ===================== APERTURA DEL TURNO ===================== --}}
    @php
        $datosApertura = [
            'anterior' => (float) ($montoAnterior ?? 0),
            'inicial'  => old('monto_inicial'),
            'ahora'    => now()->toIso8601String(),
            // Abrir la caja = registrar un movimiento (crear). La ruta lo vuelve a revisar.
            'puede'    => auth()->user()->tienePermiso('Caja Central', 'crear'),
        ];
    @endphp
    <script type="application/json" id="cj-apertura">{!! json_encode($datosApertura, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <div class="cj" x-data="aperturaCaja()" x-cloak @keydown.window="tecla($event)">
        <section class="cj-ab">
            <!-- Credencial del turno -->
            <article class="cj-turno" aria-label="Turno de caja">
                <header>
                    <span>Caja central</span>
                    <i class="cj-turno-edo">Cerrada</i>
                </header>
                <p class="cj-turno-dia" x-text="diaLargo"></p>
                <p class="cj-turno-hora t" x-text="reloj"></p>
                <dl>
                    <div><dt>Cajero</dt><dd>{{ \Illuminate\Support\Str::title(Auth::user()->name ?? 'Usuario') }}</dd></div>
                    <div><dt>Último corte</dt><dd class="t" x-text="D.anterior > 0 ? dinero(D.anterior) : 'Sin registro'"></dd></div>
                </dl>
                <button type="button" class="cj-turno-usar" x-show="D.anterior > 0" @click="usarAnterior()">
                    <i class="bi bi-arrow-repeat"></i><span x-text="'Empezar con ' + dinero(D.anterior)"></span>
                </button>
                <footer><i class="bi bi-lock"></i> Se abre registrando el fondo de cambio</footer>
            </article>

            <!-- Conteo del fondo -->
            <form action="{{ route('caja.aperturar') }}" method="POST" class="cj-ab-form" x-ref="form" @submit="if (!D.puede) { $event.preventDefault(); return; } enviando = true">
                @csrf
                <div class="cj-ab-cab">
                    <h1>Abrir caja</h1>
                    <p>Cuenta el efectivo con el que empiezas el turno. Puedes contarlo por billete o escribir el total.</p>
                </div>

                <div class="cj-conteo">
                    <template x-for="b in BILLETES" :key="'b' + b.v">
                        <div class="cj-den">
                            <span class="cj-billete" :style="'--b:' + b.c"><b x-text="'$' + b.v"></b></span>
                            <div class="cj-paso">
                                <button type="button" @click="sumar(b.v, -1)" :disabled="!conteo[b.v]" :aria-label="'Un billete de $' + b.v + ' menos'"><i class="bi bi-dash"></i></button>
                                <input type="number" min="0" step="1" class="t" x-model.number="conteo[b.v]" @input="manual = null" :aria-label="'Billetes de $' + b.v">
                                <button type="button" @click="sumar(b.v, 1)" :aria-label="'Un billete de $' + b.v + ' más'"><i class="bi bi-plus"></i></button>
                            </div>
                            <span class="cj-den-sub t" x-text="conteo[b.v] ? dinero(b.v * conteo[b.v], 0) : '—'"></span>
                        </div>
                    </template>
                    <div class="cj-den">
                        <span class="cj-billete moneda"><b>Monedas</b></span>
                        <div class="cj-in chico"><span>$</span><input type="number" min="0" step="0.5" class="t" x-model="monedas" @input="manual = null" placeholder="0" aria-label="Total en monedas"></div>
                        <span class="cj-den-sub t" x-text="Number(monedas) ? dinero(monedas) : '—'"></span>
                    </div>
                </div>

                <div class="cj-ab-total">
                    <label for="monto_inicial">Fondo inicial</label>
                    <div class="cj-in grande" :class="error && 'mal'">
                        <span>$</span>
                        <input type="number" step="0.01" min="0" name="monto_inicial" id="monto_inicial" required class="t"
                               :value="fondoTexto" @input="manual = $event.target.value; error = ''" placeholder="0.00">
                        <em>MXN</em>
                    </div>
                    <p class="cj-ab-err" x-show="error" x-text="error"></p>
                    <p class="cj-ab-nota" x-text="manual !== null ? 'Monto escrito a mano; el conteo por billete se ignora.' : (suma > 0 ? 'Suma del conteo.' : 'Si no hay fondo, deja $0.00.')"></p>
                </div>

                <div class="cj-ab-pie">
                    <a href="{{ route('home') }}" class="cj-btn">Cancelar</a>
                    <span class="cj-btn" x-show="!D.puede">Tu rol no puede abrir la caja</span>
                    <button type="submit" class="cj-cobrar" x-show="D.puede" :disabled="enviando">
                        <i class="bi" :class="enviando ? 'bi-hourglass-split' : 'bi-unlock'"></i>
                        <span x-text="enviando ? 'Abriendo…' : 'Abrir caja con ' + dinero(fondo)"></span>
                        <kbd x-show="!enviando">Ctrl ↵</kbd>
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
    function aperturaCaja() {
        const D = JSON.parse(document.getElementById('cj-apertura').textContent);
        const desfase = new Date(D.ahora).getTime() - Date.now();
        const BILLETES = [
            { v: 1000, c: '#6b5b86' }, { v: 500, c: '#2f5f9e' }, { v: 200, c: '#4d7f2c' },
            { v: 100, c: '#b2392f' }, { v: 50, c: '#9b3572' }, { v: 20, c: '#2f7f74' },
        ];
        const conteo = {}; BILLETES.forEach(b => { conteo[b.v] = 0; });
        return {
            D, BILLETES, conteo, monedas: '', manual: D.inicial !== null && D.inicial !== undefined && D.inicial !== '' ? String(D.inicial) : null,
            error: @json($errors->first('monto_inicial')), enviando: false, ahora: Date.now() + desfase,
            init() {
                setInterval(() => { this.ahora = Date.now() + desfase; }, 1000);
                this.$nextTick(() => { const i = document.getElementById('monto_inicial'); if (i && this.manual === null) i.focus(); });
            },
            get suma() { return BILLETES.reduce((s, b) => s + b.v * (Number(this.conteo[b.v]) || 0), 0) + (Number(this.monedas) || 0); },
            get fondo() { return this.manual !== null ? Math.max(0, Number(this.manual) || 0) : this.suma; },
            get fondoTexto() { return this.manual !== null ? this.manual : (this.suma ? this.suma.toFixed(2) : ''); },
            get diaLargo() { const t = new Date(this.ahora).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }); return t.charAt(0).toUpperCase() + t.slice(1); },
            get reloj() { return new Date(this.ahora).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); },
            sumar(v, d) { this.conteo[v] = Math.max(0, (Number(this.conteo[v]) || 0) + d); this.manual = null; },
            usarAnterior() { BILLETES.forEach(b => { this.conteo[b.v] = 0; }); this.monedas = ''; this.manual = this.D.anterior.toFixed(2); },
            dinero(n, dec = 2) { return window.MTCaja.dinero(n, dec); },
            tecla(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); if (!this.enviando && this.D.puede) { this.enviando = true; this.$refs.form.submit(); } }
            },
        };
    }
    </script>

@else
    {{-- ========================================================= --}}
    {{-- PANEL DE COBRO Y OPERACIÓN DE CAJA CENTRAL                --}}
    {{-- ========================================================= --}}
    @php
        $nombrePaciente = fn ($p) => $p ? (trim((string) ($p->nombre ?? '')) ?: 'Paciente') : null;

        $datosCaja = [
            'ahora'      => now()->toIso8601String(),
            'csrf'       => csrf_token(),
            'urlBuscar'  => route('caja.buscar-ticket', '__FOLIO__'),
            'urlPagar'   => route('caja.procesar-pago', '__ID__'),
            // Cobrar y hacer el corte = registrar movimientos de caja (crear). La ruta lo vuelve a revisar.
            'puedeCobrar' => auth()->user()->tienePermiso('Caja Central', 'crear'),
            'urlPos'     => \Illuminate\Support\Facades\Route::has('pos.index') ? route('pos.index') : null,
            'clinica'    => data_get($clinica ?? null, 'nombre') ?: 'MediTrack',
            'turno'      => [
                'desde'  => optional($cajaAbierta->created_at)->toIso8601String(),
                'cajero' => \Illuminate\Support\Str::title((string) (optional(\App\Models\User::find($cajaAbierta->user_id))->name ?? '')),
                'fondo'  => (float) ($totales['fondo_inicial'] ?? 0),
            ],
            'errorCierre' => session('error') && preg_match('/CONTRASE|AUTORIZACI/u', (string) session('error')) ? true : false,
            'tickets'    => $ticketsPendientes->map(fn ($t) => [
                'id'       => $t->id,
                'codigo'   => (string) $t->codigo_ticket,
                'paciente' => $nombrePaciente($t->paciente),
                'total'    => (float) $t->monto_total,
                'creado'   => optional($t->created_at)->toIso8601String(),
                'vence'    => optional($t->expires_at)->toIso8601String(),
                'detalles' => $t->detalles->map(fn ($d) => [
                    'id'       => $d->id,
                    'cantidad' => (int) $d->cantidad,
                    'nombre'   => (string) (optional($d->producto)->nombre ?: 'Producto'),
                    'precio'   => (float) $d->precio_unitario,
                    'subtotal' => (float) $d->subtotal,
                ])->values(),
            ])->values(),
            'cobros'     => $cobrosHoy->map(fn ($m) => [
                'id'       => $m->id,
                'folio'    => optional($m->ticket)->codigo_ticket,
                'paciente' => $nombrePaciente(optional($m->ticket)->paciente),
                'monto'    => (float) $m->monto,
                'metodo'   => (string) ($m->metodo_pago ?: 'Efectivo'),
                'tipo'     => (string) ($m->tipo ?: 'Ingreso'),
                'concepto' => (string) $m->concepto,
                'hora'     => optional($m->created_at)->format('H:i'),
            ])->values(),
        ];
    @endphp

    <div class="cj" x-data="cajaApp()" @keydown.window="tecla($event)">

        <!-- BARRA SUPERIOR CON LECTOR Y BOTÓN DE CIERRE -->
        <header class="cj-top flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="flex-1 flex items-center gap-2">
                <label class="cj-scan flex-1" :class="{ 'busca': buscandoTicket || cargando, 'mal': errorEscaneo }">
                    <i class="bi bi-upc-scan" aria-hidden="true"></i>
                    <span class="cj-sr">Escanear comprobante</span>
                    <input type="text" x-ref="scan" x-model="codigoEscaneado" @keydown.enter.prevent="escanearTicket()" :disabled="buscandoTicket"
                           autocomplete="off" placeholder="Escanea el comprobante o escribe el folio (TCK-…)">
                    <span class="cj-scan-est" x-show="buscandoTicket || cargando" x-cloak><i class="cj-gira"></i>Buscando…</span>
                    <kbd x-show="!buscandoTicket && !cargando" aria-hidden="true">Enter</kbd>
                </label>
                <button type="button" class="cj-cam" :class="camaraActiva && 'on'" @click="camaraActiva ? detenerCamara() : iniciarCamara()">
                    <i class="bi" :class="camaraActiva ? 'bi-camera-video-off' : 'bi-camera'"></i><span x-text="camaraActiva ? 'Detener cámara' : 'Cámara'"></span>
                </button>
            </div>

            <!-- Turno abierto y corte -->
            <div class="cj-turno-chip">
                <span class="cj-tc-info">
                    <i></i>
                    <span><b x-text="'Turno desde ' + hora(D.turno.desde)"></b><small class="t" x-text="'En caja ' + dinero(enCaja)"></small></span>
                </span>
                <button type="button" x-show="D.puedeCobrar" @click="abrirCorte()"><i class="bi bi-lock"></i>Corte de caja</button>
            </div>
        </header>

        <div class="cj-opc">
            <button type="button" class="cj-sw" role="switch" :aria-checked="cobroAutomatico.toString()" @click="cobroAutomatico = !cobroAutomatico">
                <i></i><span>Cobrar al escanear</span>
            </button>
            <div class="cj-metodos" role="radiogroup" aria-label="Método de pago">
                <template x-for="m in metodos" :key="m.k">
                    <button type="button" role="radio" :aria-checked="(metodoPago === m.k).toString()" @click="metodoPago = m.k"><i class="bi" :class="m.i"></i><span x-text="m.t"></span></button>
                </template>
            </div>
            <p class="cj-aviso mal" x-show="errorEscaneo" x-cloak><i class="bi bi-exclamation-triangle"></i><span x-text="errorEscaneo"></span></p>
            <p class="cj-aviso bien" x-show="ultimoCobro && !errorEscaneo" x-cloak><i class="bi bi-check2-circle"></i><span x-text="ultimoCobro"></span><button type="button" x-show="ticketCobrado" @click="openTicketPagadoModal = true">Ver comprobante</button></p>
        </div>

        <div class="cj-camara" x-show="camaraActiva" x-cloak><div id="lector-camara"></div></div>

        <!-- CIFRA DEL DÍA -->
        <section class="cj-cifra" aria-label="Resumen del día">
            <div class="cj-n">
                <b class="t" x-text="dinero(totalHoy, 0)">$0</b>
                <span>cobrado en el turno<br><span class="t" x-text="cobros.length"></span> <span x-text="cobros.length === 1 ? 'cobro' : 'cobros'"></span></span>
            </div>
            <div class="cj-ind">
                <div class="e-pend"><span>Por cobrar</span><b class="t" x-text="vigentes.length"></b><small class="t" x-text="dinero(porCobrar)"></small><i :style="'--p:' + (vigentes.length ? 100 : 0) + '%'"></i></div>
                <template x-for="m in metodos" :key="'i' + m.k">
                    <div :class="'e-' + m.k.toLowerCase()"><span x-text="m.t"></span><b class="t" x-text="dinero(porMetodo(m.k), 0)"></b><small x-text="cuentaMetodo(m.k) + (cuentaMetodo(m.k) === 1 ? ' cobro' : ' cobros')"></small><i :style="'--p:' + (totalHoy ? porMetodo(m.k) / totalHoy * 100 : 0) + '%'"></i></div>
                </template>
            </div>
        </section>

        <div class="cj-escenario">
            <!-- TICKET ELEGIDO -->
            <aside class="cj-mostrador" aria-label="Ticket elegido">
                <div class="cj-papel" :class="{ 'pagado': hecho, 'vencido': sel && !hecho && vencido(sel) }" x-show="sel" x-cloak>
                    <i class="cj-sello" x-show="hecho">Pagado</i>
                    <i class="cj-sello vence" x-show="sel && !hecho && vencido(sel)">Vencido</i>
                    <p class="cj-p-marca"><i class="bi bi-plus-square-fill"></i>MediTrack · Caja central</p>
                    <div class="cj-p-folio">
                        <b class="mono" x-text="sel ? sel.codigo : ''"></b>
                        <span x-text="sel ? (hecho ? 'Pagado ' + horaPago + ' · ' + metodoUsado : (vencido(sel) ? 'Venció ' + hora(sel.vence) : 'Vence en ' + restante(sel))) : ''" :class="sel && !hecho && urgente(sel) && 'urge'"></span>
                    </div>
                    <p class="cj-p-pac"><span>Paciente</span><b x-text="sel ? (sel.paciente || 'Público general') : ''"></b></p>
                    <ul class="cj-p-items">
                        <template x-for="dt in (sel ? sel.detalles : [])" :key="'d' + dt.id">
                            <li><span class="t" x-text="dt.cantidad + ' ×'"></span><b x-text="titulo(dt.nombre)"></b><span class="t" x-text="dinero(dt.subtotal)"></span></li>
                        </template>
                        <li class="vacio" x-show="sel && !sel.detalles.length">Sin desglose de productos</li>
                    </ul>
                    <p class="cj-p-tot"><span>Total</span><b class="t" x-text="sel ? dinero(sel.total) : ''"></b></p>
                    <span class="cj-p-bar" x-show="hecho" x-html="sel && hecho ? barras(sel.codigo) : ''" aria-hidden="true"></span>
                    <i class="cj-p-corte" aria-hidden="true"></i>
                </div>

                <!-- Cobro -->
                <div class="cj-cobro" x-show="sel && !hecho" x-cloak>
                    <div class="cj-metodos grande" role="radiogroup" aria-label="Método de pago">
                        <template x-for="m in metodos" :key="'c' + m.k">
                            <button type="button" role="radio" :aria-checked="(metodoPago === m.k).toString()" @click="metodoPago = m.k"><i class="bi" :class="m.i"></i><span x-text="m.t"></span></button>
                        </template>
                    </div>
                    <div class="cj-cambio" x-show="metodoPago === 'Efectivo'">
                        <label for="cj-recibe">Paga con</label>
                        <div class="cj-in"><span>$</span><input id="cj-recibe" type="number" min="0" step="0.5" x-model="recibido" placeholder="0.00" class="t" @keydown.enter.prevent="confirmarPago()"></div>
                        <div class="cj-billetes">
                            <button type="button" @click="recibido = String(sel.total)">Exacto</button>
                            <template x-for="b in billetes" :key="b"><button type="button" @click="recibido = String(b)" x-text="'$' + b"></button></template>
                        </div>
                        <p class="cj-cambio-r" :class="falta > 0 && 'mal'" x-show="recibido !== ''">
                            <span x-text="falta > 0 ? 'Faltan' : 'Cambio'"></span><b class="t" x-text="dinero(falta > 0 ? falta : cambio)"></b>
                        </p>
                    </div>
                    <button type="button" class="cj-cobrar" x-show="D.puedeCobrar" @click="confirmarPago()" :disabled="cargando || vencido(sel) || (metodoPago === 'Efectivo' && recibido !== '' && falta > 0)">
                        <span x-show="cargando" class="cj-gira"></span>
                        <span x-text="cargando ? 'Cobrando…' : (vencido(sel) ? 'Ticket vencido' : 'Cobrar ' + dinero(sel ? sel.total : 0))"></span>
                        <kbd x-show="!cargando && !vencido(sel)">Ctrl ↵</kbd>
                    </button>
                    <p class="cj-nota" x-show="vencido(sel)">Pide al paciente que lo revalide en la Terminal POS.</p>
                </div>

                <div class="cj-cobro" x-show="hecho" x-cloak>
                    <button type="button" class="cj-cobrar" @click="siguiente()"><span x-text="vigentes.length ? 'Siguiente ticket' : 'Listo'"></span><i class="bi bi-arrow-right"></i></button>
                    <button type="button" class="cj-btn" @click="openTicketPagadoModal = true"><i class="bi bi-printer"></i>Imprimir comprobante</button>
                </div>

                <div class="cj-vacio" x-show="!sel">
                    <i class="bi bi-receipt"></i>
                    <p><b x-text="vigentes.length ? 'Escanea un comprobante o elige un ticket' : 'No hay tickets por cobrar'"></b>
                       <span x-text="vigentes.length ? 'El ticket aparecerá aquí listo para cobrar.' : 'Los tickets se generan en la Terminal POS.'"></span></p>
                    <a x-show="D.urlPos" :href="D.urlPos" class="cj-btn"><i class="bi bi-shop"></i>Ir a la Terminal POS</a>
                </div>
            </aside>

            <div class="cj-col">
                <!-- POR COBRAR -->
                <section class="cj-lista" aria-labelledby="cjPorCobrar">
                    <div class="cj-lh">
                        <h2 id="cjPorCobrar">Por cobrar <small class="t" x-text="vigentes.length"></small></h2>
                        <label class="cj-busca"><i class="bi bi-search"></i><input type="search" x-model="searchQuery" placeholder="Folio o paciente" autocomplete="off"></label>
                    </div>
                    <div class="cj-tks">
                        <template x-for="t in filtrados" :key="t.id">
                            <button type="button" class="cj-tk" :class="{ 'sel': sel && sel.id === t.id && !hecho, 'urge': urgente(t), 'vencido': vencido(t) }" @click="elegir(t)">
                                <span class="cj-tk-reloj" :style="'--p:' + avance(t)"><b class="t" x-text="vencido(t) ? '—' : minutos(t)"></b><small x-text="vencido(t) ? 'vencido' : 'min'"></small></span>
                                <span class="cj-tk-n">
                                    <b class="mono" x-text="t.codigo"></b>
                                    <span x-text="(t.paciente || 'Público general') + ' · ' + piezas(t) + (piezas(t) === 1 ? ' pieza' : ' piezas')"></span>
                                </span>
                                <span class="cj-tk-tot t" x-text="dinero(t.total)"></span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>
                        </template>
                        <div class="cj-sin" x-show="!filtrados.length">
                            <i class="bi" :class="tickets.length ? 'bi-search' : 'bi-check2-circle'"></i>
                            <p x-text="tickets.length && searchQuery ? 'Ningún ticket coincide' : 'No hay cobros pendientes'"></p>
                        </div>
                    </div>
                </section>

                <!-- COBROS DE HOY -->
                <section class="cj-lista" aria-labelledby="cjHoy">
                    <div class="cj-lh"><h2 id="cjHoy">Cobros del turno <small class="t" x-text="cobros.length"></small></h2></div>
                    <ol class="cj-hoy">
                        <template x-for="c in cobros" :key="'m' + c.id">
                            <li :class="[c.nuevo ? 'mt-destello' : '', 'e-' + c.metodo.toLowerCase()]">
                                <span class="cj-hoy-h t" x-text="c.hora"></span>
                                <span class="cj-hoy-ic"><i class="bi" :class="icono(c.metodo)"></i></span>
                                <span class="cj-hoy-n"><b class="mono" x-text="c.folio || 'Movimiento'"></b><small x-text="(c.paciente || 'Público general') + ' · ' + c.metodo"></small></span>
                                <b class="cj-hoy-m t" :class="c.tipo === 'Egreso' && 'egreso'" x-text="(c.tipo === 'Egreso' ? '−' : '') + dinero(c.monto)"></b>
                            </li>
                        </template>
                        <li class="cj-sin" x-show="!cobros.length"><i class="bi bi-cash-coin"></i><p>Aún no hay cobros en este turno</p></li>
                    </ol>
                </section>
            </div>
        </div>

        <!-- CORTE DE CAJA -->
        <template x-teleport="body">
            <div class="cjc-velo" x-show="openCierreModal" x-cloak @keydown.escape.window="openCierreModal && (openCierreModal = false)"
                 x-transition:enter="cjm-t" x-transition:enter-start="cjm-0" x-transition:enter-end="cjm-1"
                 x-transition:leave="cjm-t-s" x-transition:leave-start="cjm-1" x-transition:leave-end="cjm-0">
                <form method="POST" action="{{ route('caja.cerrar') }}" class="cjc" role="dialog" aria-modal="true" aria-label="Corte de caja"
                      @click.outside="openCierreModal = false" @submit="cerrando = true">
                    @csrf
                    <div class="cjc-cab">
                        <span class="ic"><i class="bi bi-lock"></i></span>
                        <div><h3>Corte de caja</h3><p x-text="'Turno desde ' + hora(D.turno.desde) + (D.turno.cajero ? ' · ' + D.turno.cajero : '')"></p></div>
                        <button type="button" class="cerrar" @click="openCierreModal = false" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="cjc-cuerpo">
                        <div class="cjc-hoja">
                            <dl>
                                <div><dt>Fondo inicial</dt><dd class="t" x-text="dinero(D.turno.fondo)"></dd></div>
                                <template x-for="m in metodos" :key="'cc' + m.k">
                                    <div :class="'e-' + m.k.toLowerCase()"><dt><i class="bi" :class="m.i"></i><span x-text="m.t"></span><small x-text="'· ' + cuentaMetodo(m.k)"></small></dt><dd class="t" x-text="dinero(porMetodo(m.k))"></dd></div>
                                </template>
                                <div class="suma"><dt>Total cobrado</dt><dd class="t" x-text="dinero(totalHoy)"></dd></div>
                            </dl>
                            <p class="cjc-esperado"><span>Efectivo que debe haber en caja</span><b class="t" x-text="dinero(enCaja)"></b><small>Fondo + cobros en efectivo</small></p>
                        </div>

                        <div class="cjc-contado">
                            <label for="cjc-contado">Efectivo contado <small>(opcional, para revisar)</small></label>
                            <div class="cj-in"><span>$</span><input id="cjc-contado" type="number" min="0" step="0.5" x-model="contado" class="t" placeholder="0.00"></div>
                            <p class="cjc-dif" x-show="contado !== ''" :class="Math.abs(diferencia) < 0.005 ? 'ok' : (diferencia > 0 ? 'sobra' : 'falta')">
                                <i class="bi" :class="Math.abs(diferencia) < 0.005 ? 'bi-check2-circle' : 'bi-exclamation-triangle'"></i>
                                <span x-text="Math.abs(diferencia) < 0.005 ? 'La caja cuadra' : (diferencia > 0 ? 'Sobran ' + dinero(diferencia) : 'Faltan ' + dinero(-diferencia))"></span>
                            </p>
                        </div>

                        <p class="cjc-aviso" x-show="vigentes.length"><i class="bi bi-hourglass-split"></i><span x-text="(vigentes.length === 1 ? 'Queda 1 ticket por cobrar' : 'Quedan ' + vigentes.length + ' tickets por cobrar') + '. Se podrán cobrar al abrir el siguiente turno, mientras no venzan.'"></span></p>

                        <div>
                            <label class="cjc-lab" for="admin_password">Tu contraseña para autorizar</label>
                            <div class="cj-in" :class="D.errorCierre && 'mal'">
                                <i class="bi bi-key"></i>
                                <input :type="verPass ? 'text' : 'password'" name="admin_password" id="admin_password" x-ref="pass" required autocomplete="current-password" placeholder="Contraseña">
                                <button type="button" class="cjc-ojo" @click="verPass = !verPass" :aria-label="verPass ? 'Ocultar contraseña' : 'Mostrar contraseña'"><i class="bi" :class="verPass ? 'bi-eye-slash' : 'bi-eye'"></i></button>
                            </div>
                            <p class="cjc-err" x-show="D.errorCierre">La contraseña no coincide; inténtalo de nuevo.</p>
                        </div>
                    </div>
                    <div class="cjc-pie">
                        <button type="button" class="cj-btn" @click="openCierreModal = false">Cancelar</button>
                        <button type="submit" class="cj-cobrar" :disabled="cerrando"><i class="bi" :class="cerrando ? 'bi-hourglass-split' : 'bi-lock'"></i><span x-text="cerrando ? 'Cerrando…' : 'Cerrar caja'"></span></button>
                    </div>
                </form>
            </div>
        </template>

        @include('caja.modalCobroTicket')
    </div>

    <script type="application/json" id="cj-datos">{!! json_encode($datosCaja, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

    <!-- LECTOR DE CÓDIGOS CON LA CÁMARA (CODE128) -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>


    <script>
    function cajaApp() {
        const D = JSON.parse(document.getElementById('cj-datos').textContent);
        const desfase = new Date(D.ahora).getTime() - Date.now();
        const quitarAcentos = s => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        
        const deTicket = raw => ({
            id: raw.id,
            codigo: String(raw.codigo_ticket || raw.codigo || ''),
            paciente: raw.paciente ? (raw.paciente.nombre || 'Paciente') : (typeof raw.paciente === 'string' ? raw.paciente : null),
            total: Number(raw.monto_total ?? raw.total ?? 0),
            creado: raw.created_at || raw.creado || null,
            vence: raw.expires_at || raw.vence || null,
            detalles: (raw.detalles || []).map(d => ({ id: d.id, cantidad: Number(d.cantidad) || 0, nombre: d.nombre || (d.producto && d.producto.nombre) || 'Producto', precio: Number(d.precio_unitario ?? d.precio ?? 0), subtotal: Number(d.subtotal) || 0 })),
        });

        return {
            D,
            tickets: D.tickets,
            cobros: D.cobros,
            metodos: [
                { k: 'Efectivo', t: 'Efectivo', i: 'bi-cash-stack' },
                { k: 'Tarjeta', t: 'Tarjeta', i: 'bi-credit-card' },
                { k: 'Transferencia', t: 'Transferencia', i: 'bi-bank' },
            ],
            billetes: [50, 100, 200, 500, 1000],
            searchQuery: '',
            sel: null,
            hecho: false,
            metodoUsado: '',
            horaPago: '',
            recibido: '',
            ahora: Date.now() + desfase,

            openModal: false,
            openTicketPagadoModal: false,
            openCierreModal: !!D.errorCierre,
            contado: '',
            verPass: false,
            cerrando: false,
            ticketSeleccionado: {},
            ticketCobrado: null,
            metodoPago: 'Efectivo',
            cargando: false,

            codigoEscaneado: '',
            buscandoTicket: false,
            errorEscaneo: '',
            ultimoCobro: '',
            cobroAutomatico: true,
            camaraActiva: false,
            lectorCamara: null,
            ultimoFolioLeido: '',
            ultimaLecturaMs: 0,

            init() {
                try { const m = localStorage.getItem('caja-metodo'); if (m) this.metodoPago = m; const a = localStorage.getItem('caja-auto'); if (a !== null) this.cobroAutomatico = a === '1'; } catch (e) {}
                this.$watch('metodoPago', v => { try { localStorage.setItem('caja-metodo', v); } catch (e) {} });
                this.$watch('cobroAutomatico', v => { try { localStorage.setItem('caja-auto', v ? '1' : '0'); } catch (e) {} });
                setInterval(() => { this.ahora = Date.now() + desfase; }, 1000);
                this.enfocarLector();
                this.$watch('openTicketPagadoModal', abierto => { if (!abierto) this.enfocarLector(); });
                this.$watch('openCierreModal', abierto => { if (!abierto) this.enfocarLector(); });
                if (D.errorCierre) this.$nextTick(() => setTimeout(() => this.$refs.pass && this.$refs.pass.focus(), 120));
                this.$watch('sel', () => { this.recibido = ''; });
            },

            dinero(n, dec = 2) { return window.MTCaja.dinero(n, dec); },
            titulo(t) { return window.MTCaja ? MTCaja.titulo(t) : String(t || ''); },
            barras(t) { return window.MTCaja ? MTCaja.barras(t) : ''; },
            hora(iso) { if (!iso) return ''; const d = new Date(iso); return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0'); },
            icono(m) { return (this.metodos.find(x => x.k === m) || {}).i || 'bi-cash'; },
            piezas(t) { return t.detalles.reduce((s, d) => s + d.cantidad, 0) || t.detalles.length; },

            msRestantes(t) { return t.vence ? new Date(t.vence).getTime() - this.ahora : Infinity; },
            vencido(t) { return !!t && this.msRestantes(t) <= 0; },
            urgente(t) { const ms = this.msRestantes(t); return ms > 0 && ms < 300000; },
            minutos(t) { const ms = this.msRestantes(t); return ms === Infinity ? '∞' : Math.max(0, Math.ceil(ms / 60000)); },
            restante(t) {
                const ms = this.msRestantes(t);
                if (ms === Infinity) return 'sin límite';
                const s = Math.max(0, Math.floor(ms / 1000));
                return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0') + ' min';
            },
            avance(t) {
                if (!t.vence || !t.creado) return 100;
                const total = new Date(t.vence) - new Date(t.creado);
                return total > 0 ? Math.max(0, Math.min(100, (this.msRestantes(t) / total) * 100)).toFixed(1) : 0;
            },

            get vigentes() { return this.tickets.filter(t => !this.vencido(t)); },
            get filtrados() {
                const q = quitarAcentos(this.searchQuery).trim().toUpperCase();
                return this.tickets.filter(t => !q || t.codigo.toUpperCase().includes(q) || quitarAcentos(t.paciente || 'Público general').toUpperCase().includes(q))
                    .sort((a, b) => (this.vencido(a) - this.vencido(b)) || (this.msRestantes(a) - this.msRestantes(b)));
            },
            get porCobrar() { return this.vigentes.reduce((s, t) => s + t.total, 0); },
            get totalHoy() { return this.cobros.filter(c => c.tipo !== 'Egreso').reduce((s, c) => s + c.monto, 0); },
            porMetodo(m) { return this.cobros.filter(c => c.metodo === m && c.tipo !== 'Egreso').reduce((s, c) => s + c.monto, 0); },
            cuentaMetodo(m) { return this.cobros.filter(c => c.metodo === m && c.tipo !== 'Egreso').length; },
            get enCaja() { return (Number(D.turno.fondo) || 0) + this.porMetodo('Efectivo'); },
            get diferencia() { return (Number(this.contado) || 0) - this.enCaja; },
            abrirCorte() { if (!this.D.puedeCobrar) return; this.openCierreModal = true; this.contado = ''; this.verPass = false; this.$nextTick(() => setTimeout(() => this.$refs.pass && this.$refs.pass.focus(), 60)); },
            get cambio() { return Math.max(0, (Number(this.recibido) || 0) - (this.sel ? this.sel.total : 0)); },
            get falta() { return Math.max(0, (this.sel ? this.sel.total : 0) - (Number(this.recibido) || 0)); },

            elegir(t) {
                this.sel = t;
                this.hecho = false;
                this.ticketSeleccionado = t;
                this.errorEscaneo = '';
            },
            siguiente() {
                const sig = this.filtrados.find(t => !this.vencido(t));
                this.hecho = false;
                this.sel = sig || null;
                if (sig) this.ticketSeleccionado = sig;
                this.enfocarLector();
            },

            enfocarLector() {
                if (this.camaraActiva) return;
                this.$nextTick(() => this.$refs.scan && this.$refs.scan.focus());
            },
            normalizarFolio(valor) {
                return (valor || '').trim().toUpperCase().replace(/'/g, '-');
            },
            escanearTicket() {
                const folio = this.normalizarFolio(this.codigoEscaneado);
                this.codigoEscaneado = '';
                this.procesarFolio(folio);
            },
            async procesarFolio(folio) {
                if (!folio || this.buscandoTicket || this.cargando) return;
                this.errorEscaneo = '';
                this.ultimoCobro = '';
                this.buscandoTicket = true;
                try {
                    const res = await fetch(D.urlBuscar.replace('__FOLIO__', encodeURIComponent(folio)), { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.status !== 'success') { this.mostrarError(data.message || 'No se pudo leer el ticket.'); return; }
                    const t = deTicket(data.ticket);
                    const enLista = this.tickets.find(x => x.id === t.id);
                    if (!enLista) this.tickets.unshift(t);
                    this.elegir(enLista || t);
                    if (this.cobroAutomatico) {
                        this.buscandoTicket = false;
                        await this.confirmarPago();
                    }
                } catch (e) {
                    console.error(e);
                    this.mostrarError('Error de conexión al buscar el ticket.');
                } finally {
                    this.buscandoTicket = false;
                }
            },
            mostrarError(mensaje) {
                const t = String(mensaje || ''), m = t === t.toUpperCase() ? (t.charAt(0) + t.slice(1).toLowerCase()).replace(/\b(tck|pos)\b/gi, x => x.toUpperCase()).replace(/tck-[a-z0-9]+/gi, x => x.toUpperCase()) : t;
                this.errorEscaneo = m;
                if (window.notificar) window.notificar(m, 'error');
                if (window.mtSacudir) mtSacudir('.cj-scan');
                if (navigator.vibrate) navigator.vibrate([100, 60, 100]);
                this.enfocarLector();
            },

            async iniciarCamara() {
                this.errorEscaneo = '';
                if (!window.isSecureContext) { this.mostrarError('La cámara solo funciona con https.'); return; }
                if (typeof Html5Qrcode === 'undefined') { this.mostrarError('No se cargó la librería del lector.'); return; }
                this.camaraActiva = true;
                await this.$nextTick();
                try {
                    this.lectorCamara = new Html5Qrcode('lector-camara', { formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128], verbose: false });
                    await this.lectorCamara.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: (w, h) => ({ width: Math.floor(w * 0.9), height: Math.floor(Math.min(h, w) * 0.35) }) },
                        texto => this.alLeerConCamara(texto),
                        () => {}
                    );
                } catch (e) {
                    console.error(e);
                    this.camaraActiva = false;
                    this.lectorCamara = null;
                    this.mostrarError('No se pudo abrir la cámara.');
                }
            },
            async detenerCamara() {
                if (this.lectorCamara) { try { await this.lectorCamara.stop(); this.lectorCamara.clear(); } catch (e) {} }
                this.lectorCamara = null;
                this.camaraActiva = false;
                this.enfocarLector();
            },
            alLeerConCamara(texto) {
                const folio = this.normalizarFolio(texto), ahora = Date.now();
                if (folio === this.ultimoFolioLeido && (ahora - this.ultimaLecturaMs) < 4000) return;
                this.ultimoFolioLeido = folio;
                this.ultimaLecturaMs = ahora;
                if (navigator.vibrate) navigator.vibrate(80);
                this.procesarFolio(folio);
            },

            async confirmarPago() {
                const t = this.sel;
                if (!t || !t.id || this.cargando || this.hecho || !this.D.puedeCobrar) return;
                if (this.vencido(t)) { this.mostrarError('El ticket ha expirado. Revalídalo en la Terminal POS.'); return; }
                if (this.metodoPago === 'Efectivo' && this.recibido !== '' && this.falta > 0) return;
                this.cargando = true;
                const metodo = this.metodoPago;
                try {
                    const res = await fetch(D.urlPagar.replace('__ID__', t.id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.csrf },
                        body: JSON.stringify({ metodo_pago: metodo })
                    });
                    const data = await res.json().catch(() => ({}));
                    if (data.status === 'success') {
                        const pagado = deTicket(data.ticket || t);
                        this.tickets = this.tickets.filter(x => x.id !== t.id);
                        const ahora = new Date();
                        this.horaPago = String(ahora.getHours()).padStart(2, '0') + ':' + String(ahora.getMinutes()).padStart(2, '0');
                        this.metodoUsado = metodo;
                        const mov = { id: 'n' + t.id, folio: pagado.codigo, paciente: pagado.paciente, monto: pagado.total, metodo, tipo: 'Ingreso', concepto: 'Pago de ticket ' + pagado.codigo, hora: this.horaPago, nuevo: true };
                        this.cobros.unshift(mov);
                        this.sel = Object.assign(pagado, { cambio: metodo === 'Efectivo' && this.recibido !== '' ? this.cambio : null, recibido: this.recibido });
                        this.hecho = true;
                        this.ticketCobrado = Object.assign({}, this.sel, { metodo, hora: this.horaPago });
                        this.ultimoCobro = pagado.codigo + ' cobrado · ' + this.dinero(pagado.total) + ' · ' + metodo;
                        if (window.notificar) window.notificar('Cobro registrado. El ticket pasó a despacho.', 'success');
                        if (navigator.vibrate) navigator.vibrate(200);
                        this.enfocarLector();
                    } else {
                        this.mostrarError(data.message || 'No se pudo registrar el cobro.');
                    }
                } catch (e) {
                    console.error(e);
                    this.mostrarError('Error de conexión al procesar el pago.');
                } finally {
                    this.cargando = false;
                }
            },

            tecla(e) {
                if (this.openTicketPagadoModal || this.openCierreModal) return;
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); if (this.hecho) this.siguiente(); else this.confirmarPago(); return; }
                const t = e.target, escribiendo = t && ['INPUT', 'TEXTAREA', 'SELECT'].includes(t.tagName);
                if (escribiendo || e.ctrlKey || e.metaKey || e.altKey) return;
                if (e.key === '/') { e.preventDefault(); this.$refs.scan.focus(); }
                if (e.key === 'c' || e.key === 'C') { e.preventDefault(); this.abrirCorte(); return; }
                if (e.key === '1' || e.key === '2' || e.key === '3') this.metodoPago = this.metodos[Number(e.key) - 1].k;
            },

            imprimirComprobante() { window.print(); }
        };
    }
    </script>
@endif

    @verbatim
    <style>
    .cj {
        --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
        --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
        --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
        --azul: #1d5fa8; --azulv: #e8f0fb; --violeta: #5b3fa0; --violetav: #efeafb;
        --e: cubic-bezier(.2, .8, .2, 1);
        --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
        container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
        display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px;
    }
    .cj * { box-sizing: border-box; }
    .cj :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
    .cj button { cursor: pointer; }
    .cj .t { font-variant-numeric: tabular-nums; }
    .cj .mono { font-family: var(--mono); letter-spacing: .04em; }
    .cj-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    .cj kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); }
    [x-cloak] { display: none !important; }
    .cj .e-efectivo { --c: var(--verde); --cv: var(--verdev); }
    .cj .e-tarjeta { --c: var(--azul); --cv: var(--azulv); }
    .cj .e-transferencia { --c: var(--violeta); --cv: var(--violetav); }
    .cj .e-pend { --c: var(--ambar); --cv: var(--ambarv); }
    .cj-gira { display: inline-block; width: 14px; height: 14px; border-radius: 50%; border: 2px solid currentColor; border-top-color: transparent; animation: cjGira .7s linear infinite; }
    @keyframes cjGira { to { rotate: 360deg; } }

    /* ---------- Lector ---------- */
    .cj-top { display: flex; gap: 10px; }
    .cj-scan { flex: 1; min-width: 0; height: 56px; border-radius: 14px; background: var(--papel); border: 1.5px solid var(--linea); box-shadow: 0 12px 30px -22px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 18px; transition: border-color .15s, box-shadow .15s; }
    .cj-scan:focus-within { border-color: var(--teal); box-shadow: 0 0 0 4px rgba(14, 124, 114, .12); }
    .cj-scan.mal { border-color: #e9919f; }
    .cj-scan > .bi { font-size: 20px; color: var(--cred); }
    .cj-scan input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 500 16px var(--mono); letter-spacing: .04em; color: var(--tinta); text-transform: uppercase; }
    .cj-scan input::placeholder { font-family: var(--ui); letter-spacing: 0; text-transform: none; color: var(--media); }
    .cj-scan-est { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: var(--teal); font-weight: 600; }
    .cj-cam { height: 56px; padding: 0 16px; border-radius: 14px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); }
    .cj-cam .bi { color: var(--cred); font-size: 16px; }
    .cj-cam.on { background: var(--rosav); border-color: #f0b3bd; color: var(--rosa); }
    .cj-cam.on .bi { color: var(--rosa); }
    .cj-opc { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; margin-top: -4px; }
    .cj-sw { display: inline-flex; align-items: center; gap: 9px; background: none; font: 600 13.5px var(--ui); color: var(--tinta); }
    .cj-sw i { position: relative; width: 36px; height: 20px; border-radius: 999px; background: var(--linea); transition: background .18s; }
    .cj-sw i:after { content: ""; position: absolute; left: 2px; top: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, .25); transition: translate .18s var(--e); }
    .cj-sw[aria-checked=true] i { background: var(--teal); }
    .cj-sw[aria-checked=true] i:after { translate: 16px 0; }
    .cj-metodos { display: flex; gap: 2px; padding: 3px; border-radius: 11px; background: var(--niebla); }
    .cj-metodos button { height: 30px; padding: 0 11px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; font: 600 12.5px var(--ui); color: var(--media); background: none; }
    .cj-metodos button[aria-checked=true] { background: var(--papel); color: var(--tinta); box-shadow: 0 1px 3px rgba(11, 30, 28, .14); }
    .cj-metodos button[aria-checked=true] .bi { color: var(--cred); }
    .cj-aviso { display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px; border-radius: 999px; font-size: 13px; font-weight: 550; }
    .cj-aviso.mal { background: var(--rosav); color: var(--rosa); }
    .cj-aviso.bien { background: var(--verdev); color: var(--verde); }
    .cj-aviso button { font: 600 12.5px var(--ui); color: var(--cred); background: none; text-decoration: underline; text-underline-offset: 2px; }
    .cj-camara { border-radius: 16px; overflow: hidden; background: #000; max-width: 560px; }

    /* ---------- Cifra ---------- */
    .cj-cifra { display: flex; align-items: flex-end; gap: 28px; flex-wrap: wrap; }
    .cj-n { display: flex; align-items: flex-end; gap: 12px; }
    .cj-n > b { font: 800 72px/.82 var(--disp); font-stretch: 80%; letter-spacing: -.03em; color: var(--tinta); }
    .cj-n > span { font-size: 15px; font-weight: 500; line-height: 1.25; padding-bottom: 2px; }
    .cj-ind { flex: 1; min-width: 300px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0 18px; }
    .cj-ind > div { display: grid; grid-template-columns: 1fr auto; align-items: end; padding-top: 6px; border-top: 1px solid var(--linea); }
    .cj-ind span { font-size: 13.5px; padding-bottom: 2px; }
    .cj-ind b { font: 750 22px/1 var(--disp); color: var(--c); }
    .cj-ind small { grid-column: 1 / -1; font-size: 12px; color: var(--media); }
    .cj-ind i { grid-column: 1 / -1; height: 4px; border-radius: 2px; background: var(--linea); margin-top: 6px; position: relative; overflow: hidden; }
    .cj-ind i:after { content: ""; position: absolute; inset: 0; width: var(--p); background: var(--c); border-radius: 2px; transition: width .5s var(--e); }
    @container (max-width: 759px) { .cj-n > b { font-size: 54px; } .cj-ind { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 16px; min-width: 100%; } .cj-cam span { display: none; } }

    /* ---------- Escenario ---------- */
    .cj-escenario { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
    .cj-col { display: grid; gap: 16px; min-width: 0; }
    @container (min-width: 1000px) {
        .cj-escenario { grid-template-columns: minmax(0, 1fr) 380px; }
        .cj-mostrador { grid-column: 2; grid-row: 1; position: sticky; top: 16px; }
        .cj-col { grid-column: 1; grid-row: 1; }
    }

    /* Ticket impreso */
    .cj-mostrador { display: grid; gap: 10px; min-width: 0; }
    @container (min-width: 1000px) { .cj-mostrador { max-height: calc(100vh - 32px); overflow-y: auto; overscroll-behavior: contain; padding-bottom: 10px; scrollbar-width: thin; } }
    .cj-papel { position: relative; padding: 16px 20px 20px; background: #fffef9; border-radius: 6px 6px 0 0; color: var(--tinta);
        box-shadow: 0 1px 0 #ece9df, 0 24px 40px -26px rgba(11, 30, 28, .5); animation: cjSale .4s var(--e); }
    @keyframes cjSale { from { clip-path: inset(0 0 100% 0); translate: 0 -10px; } to { clip-path: inset(0 0 0 0); translate: 0 0; } }
    .cj-p-corte { position: absolute; left: 0; right: 0; bottom: -9px; height: 10px; background: linear-gradient(-45deg, transparent 6px, #fffef9 0) 0 0 / 12px 10px, linear-gradient(45deg, transparent 6px, #fffef9 0) 0 0 / 12px 10px; filter: drop-shadow(0 2px 1px rgba(0, 0, 0, .06)); }
    .cj-p-marca { display: flex; align-items: center; justify-content: center; gap: 7px; font: 600 11px var(--mono); letter-spacing: .14em; text-transform: uppercase; color: var(--cred); padding-bottom: 12px; border-bottom: 1px dashed #d8d4c6; }
    .cj-p-folio { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-top: 12px; }
    .cj-p-folio b { font: 600 20px var(--mono); letter-spacing: .06em; }
    .cj-p-folio span { font-size: 12.5px; color: var(--media); text-align: right; }
    .cj-p-folio span.urge { color: var(--ambar); font-weight: 600; }
    .cj-p-pac { display: grid; margin-top: 8px; }
    .cj-p-pac span { font: 600 10.5px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--media); }
    .cj-p-pac b { font-weight: 600; }
    .cj-p-items { margin-top: 12px; padding: 10px 0; border-top: 1px dashed #d8d4c6; border-bottom: 1px dashed #d8d4c6; display: grid; gap: 6px; max-height: 168px; overflow-y: auto; }
    .cj-p-items li { display: grid; grid-template-columns: 34px minmax(0, 1fr) auto; gap: 8px; align-items: baseline; font-size: 13px; }
    .cj-p-items li > span:first-child { font-family: var(--mono); color: var(--media); }
    .cj-p-items li b { font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cj-p-items li > span:last-child { font-family: var(--mono); }
    .cj-p-items li.vacio { display: block; color: var(--media); font-size: 12.5px; }
    .cj-p-tot { display: flex; align-items: baseline; justify-content: space-between; margin-top: 12px; }
    .cj-p-tot span { font: 600 12px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--media); }
    .cj-p-tot b { font: 800 34px/1 var(--disp); }
    .cj-p-bar { display: block; margin-top: 14px; }
    .cj-p-bar svg { display: block; width: 100%; height: 44px; }
    .cj-sello { position: absolute; left: 50%; top: 52%; translate: -50% -50%; z-index: 2; padding: 4px 12px; border: 3px solid var(--verde); border-radius: 8px; color: var(--verde); font: 800 22px var(--disp); font-style: normal; letter-spacing: .1em; text-transform: uppercase; rotate: -12deg; background: rgba(255, 254, 249, .8); animation: cjSello .45s cubic-bezier(.3, 1.6, .5, 1); }
    .cj-sello.vence { border-color: var(--rosa); color: var(--rosa); animation: none; }
    @keyframes cjSello { from { scale: 2.2; opacity: 0; } to { scale: 1; opacity: 1; } }
    .cj-papel.vencido .cj-p-items, .cj-papel.vencido .cj-p-tot { opacity: .5; }

    /* Cobro */
    .cj-cobro { display: grid; gap: 10px; }
    .cj-metodos.grande { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); padding: 4px; }
    .cj-metodos.grande button { height: 40px; justify-content: center; font-size: 13.5px; }
    .cj-cambio { padding: 10px 12px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); display: grid; gap: 8px; }
    .cj-cambio label { font-size: 13px; font-weight: 600; color: var(--tinta); }
    .cj-in { display: flex; align-items: center; gap: 8px; height: 44px; padding: 0 12px; border-radius: 11px; border: 1px solid var(--linea); }
    .cj-in:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .14); }
    .cj-in span { font: 600 16px var(--ui); color: var(--media); }
    .cj-in input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 700 20px var(--disp); color: var(--tinta); }
    .cj-in input::-webkit-inner-spin-button, .cj-in input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .cj-billetes { display: flex; flex-wrap: wrap; gap: 5px; }
    .cj-billetes button { height: 28px; padding: 0 9px; border-radius: 8px; border: 1px solid var(--linea); background: var(--papel); font: 600 12.5px var(--ui); color: var(--texto); }
    .cj-billetes button:hover { border-color: var(--teal); color: var(--teal); }
    .cj-cambio-r { display: flex; align-items: baseline; justify-content: space-between; padding: 8px 12px; border-radius: 10px; background: var(--verdev); color: var(--verde); font-weight: 600; }
    .cj-cambio-r b { font: 800 24px var(--disp); }
    .cj-cambio-r.mal { background: var(--rosav); color: var(--rosa); }
    .cj-cobrar { height: 56px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; gap: 10px; background: var(--tinta); color: #fff; font: 650 17px var(--ui); transition: background .15s; }
    .cj-cobrar:hover:not(:disabled) { background: #163532; }
    .cj-cobrar:disabled { opacity: .55; cursor: not-allowed; }
    .cj-cobrar kbd { border-color: rgba(255, 255, 255, .25); color: var(--foil); }
    .cj-btn { height: 42px; padding: 0 14px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); text-decoration: none; }
    .cj-btn:hover { border-color: #b9cbc6; }
    .cj-btn .bi { color: var(--cred); }
    .cj-nota { font-size: 12.5px; color: var(--media); text-align: center; }
    .cj-vacio { padding: 26px 22px; border-radius: 18px; border: 1px dashed #c3d2ce; background: var(--papel); display: grid; justify-items: center; gap: 10px; text-align: center; }
    .cj-vacio > .bi { font-size: 34px; color: var(--teal); }
    .cj-vacio b { display: block; color: var(--tinta); font-weight: 600; }
    .cj-vacio span { font-size: 13px; color: var(--media); }

    /* Listas */
    .cj-lista { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; }
    .cj-lh { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 16px 14px 18px; border-bottom: 1px solid var(--linea2); }
    .cj-lh h2 { margin-right: auto; font: 750 20px var(--disp); color: var(--tinta); }
    .cj-lh h2 small { font: 500 13px var(--ui); color: var(--media); margin-left: 6px; }
    .cj-busca { display: flex; align-items: center; gap: 8px; height: 36px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--linea); min-width: 220px; }
    .cj-busca .bi { color: var(--media); font-size: 13px; }
    .cj-busca input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 500 13.5px var(--ui); color: var(--tinta); }
    .cj-tks { display: grid; max-height: 520px; overflow-y: auto; }
    .cj-tk { display: grid; grid-template-columns: 50px minmax(0, 1fr) auto 14px; align-items: center; gap: 14px; padding: 11px 18px; text-align: left; background: none; border-bottom: 1px solid var(--linea2); --c: var(--teal); }
    .cj-tk:last-child { border-bottom: 0; }
    .cj-tk:hover { background: rgba(11, 90, 84, .03); }
    .cj-tk.sel { background: linear-gradient(90deg, rgba(14, 124, 114, .08), transparent 60%); box-shadow: inset 3px 0 0 var(--teal); }
    .cj-tk.urge { --c: #c98a1e; }
    .cj-tk.vencido { --c: var(--rosa); opacity: .7; }
    .cj-tk > .bi { color: var(--media); font-size: 12px; }
    .cj-tk-reloj { position: relative; width: 50px; height: 50px; border-radius: 50%; display: grid; place-content: center; text-align: center;
        background: radial-gradient(closest-side, var(--papel) 78%, transparent 80% 100%), conic-gradient(var(--c) calc(var(--p) * 1%), var(--linea2) 0); }
    .cj-tk-reloj b { font: 700 16px/1 var(--disp); color: var(--tinta); }
    .cj-tk-reloj small { font-size: 9.5px; color: var(--media); }
    .cj-tk-n { min-width: 0; display: grid; }
    .cj-tk-n b { font-size: 15px; font-weight: 600; color: var(--tinta); }
    .cj-tk-n span { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cj-tk-tot { font: 700 18px var(--disp); color: var(--tinta); }
    .cj-hoy { display: grid; max-height: 420px; overflow-y: auto; }
    .cj-hoy li { display: grid; grid-template-columns: 44px 32px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 10px 18px; border-bottom: 1px solid var(--linea2); }
    .cj-hoy li:last-child { border-bottom: 0; }
    .cj-hoy-h { font: 500 12.5px var(--mono); color: var(--media); }
    .cj-hoy-ic { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center; background: var(--cv, var(--niebla)); color: var(--c, var(--cred)); }
    .cj-hoy-n { min-width: 0; display: grid; }
    .cj-hoy-n b { font-size: 13.5px; font-weight: 600; color: var(--tinta); }
    .cj-hoy-n small { font-size: 12px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cj-hoy-m { font: 700 15px var(--disp); color: var(--tinta); }
    .cj-hoy-m.egreso { color: var(--rosa); }
    .cj-sin { padding: 30px 18px; display: grid; justify-items: center; gap: 6px; text-align: center; color: var(--media); grid-template-columns: 1fr !important; }
    .cj-sin .bi { font-size: 26px; color: var(--teal); }
    @container (max-width: 640px) { .cj-tk { grid-template-columns: 44px minmax(0, 1fr) auto; } .cj-tk > .bi { display: none; } .cj-busca { min-width: 0; flex: 1; } .cj-scan kbd { display: none; } }
    @media (prefers-reduced-motion: reduce) { .cj-papel, .cj-sello { animation: none; } }

    /* ---------- Turno abierto (barra superior) ---------- */
    .cj-turno-chip { flex: none; height: 56px; display: flex; align-items: center; gap: 4px; padding: 4px 4px 4px 14px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); }
    .cj-tc-info { display: flex; align-items: center; gap: 10px; padding-right: 8px; }
    .cj-tc-info > i { width: 9px; height: 9px; border-radius: 50%; background: var(--verde); box-shadow: 0 0 0 4px var(--verdev); }
    .cj-tc-info > span { display: flex; flex-direction: column; line-height: 1.2; }
    .cj-tc-info b { font-size: 13px; font-weight: 600; color: var(--tinta); white-space: nowrap; }
    .cj-tc-info small { font: 500 12px var(--mono); color: var(--media); white-space: nowrap; }
    .cj-turno-chip > button { height: 46px; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; border-radius: 11px; border: 0; background: var(--tinta); color: #fff; font: 600 14px var(--ui); white-space: nowrap; }
    .cj-turno-chip > button:hover { background: var(--cred2); }
    .cj-turno-chip > button .bi { color: var(--foil); }
    @container (max-width: 640px) { .cj-turno-chip { width: 100%; } .cj-tc-info { flex: 1; } }

    /* ---------- Apertura ---------- */
    .cj-ab { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; align-items: start; max-width: 1080px; width: 100%; margin: 0 auto; }
    @container (min-width: 860px) { .cj-ab { grid-template-columns: 340px minmax(0, 1fr); } .cj-turno { position: sticky; top: 16px; } }
    .cj-turno { position: relative; overflow: hidden; display: grid; gap: 4px; padding: 20px 22px 0; border-radius: 22px; color: #eafaf6; align-content: start;
        background: radial-gradient(120% 90% at 100% 0, rgba(166, 239, 226, .22), transparent 55%), repeating-radial-gradient(circle at 18% 120%, transparent 0 13px, rgba(166, 239, 226, .07) 13px 14px), linear-gradient(160deg, var(--cred), var(--cred2));
        box-shadow: 0 30px 50px -30px rgba(7, 63, 59, .8); }
    .cj-turno header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .cj-turno header span { font: 600 11px var(--mono); letter-spacing: .16em; text-transform: uppercase; color: var(--foil); }
    .cj-turno-edo { font: 600 11.5px var(--ui); font-style: normal; padding: 3px 10px; border-radius: 99px; background: rgba(255, 255, 255, .12); color: #fff; }
    .cj-turno-dia { margin: 0; font: 700 26px/1.1 var(--disp); color: #fff; }
    .cj-turno-hora { margin: 0; font: 500 15px var(--mono); color: var(--foil); }
    .cj-turno dl { margin: 18px 0 0; display: grid; gap: 10px; }
    .cj-turno dl div { display: flex; justify-content: space-between; gap: 10px; padding-bottom: 8px; border-bottom: 1px dashed rgba(166, 239, 226, .25); }
    .cj-turno dt { font-size: 13px; color: #b9d8d2; }
    .cj-turno dd { margin: 0; font-weight: 600; color: #fff; text-align: right; }
    .cj-turno-usar { justify-self: start; margin-top: 12px; height: 38px; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; border-radius: 11px; border: 0; background: var(--foil); color: var(--cred2); font: 650 13.5px var(--ui); }
    .cj-turno footer { margin: 18px -22px 0; padding: 12px 22px; display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #fff; background: rgba(0, 0, 0, .18); }
    .cj-ab-form { min-width: 0; display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; padding: 22px; border-radius: 22px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 18px 40px -34px rgba(11, 30, 28, .5); }
    .cj-ab-cab h1 { margin: 0; font: 800 34px/1 var(--disp); font-stretch: 85%; letter-spacing: -.02em; color: var(--tinta); }
    .cj-ab-cab p { margin: 6px 0 0; font-size: 14px; color: var(--media); }
    .cj-conteo { display: grid; gap: 6px; }
    @container (min-width: 1040px) { .cj-conteo { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 18px; } }
    .cj-den { display: grid; grid-template-columns: 92px auto minmax(0, 1fr); align-items: center; gap: 12px; padding: 6px 0; border-bottom: 1px solid var(--linea2); }
    .cj-billete { position: relative; height: 40px; border-radius: 7px; display: grid; place-items: center; color: #fff; overflow: hidden;
        background: linear-gradient(135deg, color-mix(in srgb, var(--b) 85%, #fff), var(--b)); box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .25), 0 4px 10px -6px var(--b); }
    .cj-billete::before { content: ''; position: absolute; right: -14px; top: 50%; width: 30px; height: 30px; margin-top: -15px; border-radius: 50%; border: 2px solid rgba(255, 255, 255, .22); }
    .cj-billete b { position: relative; margin-right: 8px; font: 800 15px var(--disp); letter-spacing: -.01em; text-shadow: 0 1px 1px rgba(0, 0, 0, .2); }
    .cj-billete.moneda { background: linear-gradient(135deg, #e9e2c9, #c9b98a); color: #5b4a1e; box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .5); }
    .cj-billete.moneda b { font-size: 13.5px; }
    .cj-paso { display: inline-flex; align-items: center; gap: 2px; padding: 2px; border-radius: 10px; background: var(--niebla); }
    .cj-paso button { width: 32px; height: 32px; display: grid; place-items: center; border: 0; border-radius: 8px; background: none; color: var(--tinta); font-size: 16px; }
    .cj-paso button:hover:not(:disabled) { background: var(--papel); }
    .cj-paso button:disabled { opacity: .35; }
    .cj-paso input { width: 46px; height: 32px; border: 0; background: none; text-align: center; font: 700 15px var(--mono); color: var(--tinta); outline: 0; -moz-appearance: textfield; }
    .cj-paso input::-webkit-inner-spin-button, .cj-paso input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .cj-den-sub { justify-self: end; font: 600 14px var(--mono); color: var(--tinta); }
    .cj-in.chico { height: 36px; width: 110px; }
    .cj-in.chico input { font: 600 15px var(--mono); }
    .cj-in.grande { height: 64px; border-radius: 14px; }
    .cj-in.grande span { font: 700 26px var(--disp); }
    .cj-in.grande input { font: 800 38px var(--disp); font-stretch: 85%; letter-spacing: -.02em; }
    .cj-in.grande em { font: 600 12px var(--mono); font-style: normal; color: var(--media); }
    .cj-in.mal { border-color: #e9919f; }
    .cj-ab-total label { display: block; margin-bottom: 6px; font: 600 11px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--media); }
    .cj-ab-err { margin: 6px 0 0; font-size: 13px; font-weight: 600; color: var(--rosa); }
    .cj-ab-nota { margin: 6px 0 0; font-size: 12.5px; color: var(--media); }
    .cj-ab-pie { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; padding-top: 14px; border-top: 1px solid var(--linea2); }
    .cj-ab-pie .cj-cobrar { padding: 0 20px; }
    @container (max-width: 520px) { .cj-den { grid-template-columns: 74px auto minmax(0, 1fr); gap: 8px; } .cj-ab-pie .cj-cobrar { flex: 1; font-size: 15px; white-space: nowrap; padding: 0 14px; } .cj-cobrar kbd { display: none; } }

    /* ---------- Corte de caja (modal teletransportado) ---------- */
    .cjc-velo { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; padding: 16px; background: rgba(4, 18, 17, .62); backdrop-filter: blur(4px); }
    .cjc {
        --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
        --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
        --azul: #1d5fa8; --violeta: #5b3fa0; --e: cubic-bezier(.2, .8, .2, 1);
        --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
        width: min(500px, 100%); max-height: calc(100vh - 32px); display: flex; flex-direction: column; overflow: hidden; border-radius: 20px; background: var(--papel);
        box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .55); font: 450 14px/1.45 var(--ui); color: var(--texto); }
    .cjc * { box-sizing: border-box; }
    .cjc p, .cjc h3, .cjc dl { margin: 0; }
    .cjc .t { font-variant-numeric: tabular-nums; }
    .cjc-cab { flex: none; display: flex; align-items: center; gap: 12px; padding: 16px 18px; color: #fff; background: var(--cred2); background-image: radial-gradient(120% 140% at 100% 0, rgba(166, 239, 226, .2), transparent 60%); }
    .cjc-cab .ic { flex: none; width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: rgba(255, 255, 255, .1); color: var(--foil); font-size: 18px; }
    .cjc-cab > div { flex: 1; min-width: 0; }
    .cjc-cab h3 { font: 700 18px/1.2 var(--disp); color: #fff; }
    .cjc-cab p { font: 500 12px var(--mono); color: var(--foil); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cjc-cab .cerrar { flex: none; width: 34px; height: 34px; border-radius: 10px; border: 0; background: rgba(255, 255, 255, .08); color: #fff; cursor: pointer; }
    .cjc-cuerpo { flex: 1; min-height: 0; overflow-y: auto; padding: 18px; display: grid; gap: 16px; }
    .cjc-hoja { padding: 14px 16px 16px; border-radius: 6px; background: #fffef9; box-shadow: inset 0 0 0 1px #ece9df; font-family: var(--mono); font-size: 13px; color: var(--tinta); }
    .cjc-hoja dl { display: grid; gap: 6px; }
    .cjc-hoja dl div { display: flex; justify-content: space-between; gap: 10px; }
    .cjc-hoja dt { display: flex; align-items: center; gap: 6px; color: var(--media); }
    .cjc-hoja dt .bi { color: var(--c, var(--cred)); }
    .cjc-hoja dt small { font-size: 11.5px; }
    .cjc-hoja dd { margin: 0; font-weight: 600; }
    .cjc-hoja .e-efectivo { --c: var(--verde); } .cjc-hoja .e-tarjeta { --c: var(--azul); } .cjc-hoja .e-transferencia { --c: var(--violeta); }
    .cjc-hoja .suma { margin-top: 4px; padding-top: 8px; border-top: 1px dashed #d8d4c6; }
    .cjc-hoja .suma dt, .cjc-hoja .suma dd { color: var(--tinta); font-weight: 700; }
    .cjc-esperado { margin-top: 12px !important; padding-top: 12px; border-top: 1px dashed #d8d4c6; display: grid; justify-items: center; text-align: center; }
    .cjc-esperado span { font-size: 10.5px; letter-spacing: .12em; text-transform: uppercase; color: var(--media); }
    .cjc-esperado b { font: 800 40px/1 var(--disp); font-stretch: 85%; letter-spacing: -.02em; color: var(--tinta); margin-top: 4px; }
    .cjc-esperado small { font: 500 12px var(--ui); color: var(--media); margin-top: 2px; }
    .cjc-contado { display: grid; gap: 6px; }
    .cjc-contado label, .cjc-lab { display: block; font-size: 13px; font-weight: 600; color: var(--tinta); margin-bottom: 6px; }
    .cjc-contado label small { font-weight: 500; color: var(--media); }
    .cjc-dif { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 10px; font-weight: 600; }
    .cjc-dif.ok { background: var(--verdev); color: var(--verde); }
    .cjc-dif.sobra { background: var(--ambarv); color: var(--ambar); }
    .cjc-dif.falta { background: var(--rosav); color: var(--rosa); }
    .cjc-aviso { display: flex; gap: 8px; padding: 10px 12px; border-radius: 12px; background: var(--ambarv); color: var(--ambar); font-size: 13px; font-weight: 600; }
    .cjc .cj-in { display: flex; align-items: center; gap: 8px; height: 44px; padding: 0 6px 0 12px; border-radius: 11px; border: 1px solid var(--linea); }
    .cjc .cj-in:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .14); }
    .cjc .cj-in.mal { border-color: #e9919f; }
    .cjc .cj-in > span, .cjc .cj-in > .bi { color: var(--media); font-weight: 600; }
    .cjc .cj-in input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 600 16px var(--ui); color: var(--tinta); }
    .cjc-ojo { width: 34px; height: 34px; border: 0; border-radius: 9px; background: none; color: var(--media); cursor: pointer; }
    .cjc-ojo:hover { background: var(--niebla); }
    .cjc-err { margin-top: 6px !important; font-size: 13px; font-weight: 600; color: var(--rosa); }
    .cjc-pie { flex: none; display: flex; justify-content: flex-end; gap: 8px; padding: 12px 18px; border-top: 1px solid var(--linea2); background: #f9fbfa; }
    .cjc .cj-btn { height: 46px; padding: 0 16px; border-radius: 12px; display: inline-flex; align-items: center; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); cursor: pointer; }
    .cjc .cj-cobrar { height: 46px; padding: 0 18px; border: 0; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; background: var(--tinta); color: #fff; font: 650 15px var(--ui); cursor: pointer; }
    .cjc .cj-cobrar .bi { color: var(--foil); }
    .cjc .cj-cobrar:disabled { opacity: .6; }
    .cjm-t { transition: opacity .2s cubic-bezier(.2, .8, .2, 1); } .cjm-t-s { transition: opacity .13s ease-in; } .cjm-0 { opacity: 0; } .cjm-1 { opacity: 1; }
        </style>
    @endverbatim

@endsection