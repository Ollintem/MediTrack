@extends('layouts.admin')

{{--
    DASHBOARD · MediTrack
    El pulso del día: avance de la agenda, cinta de citas por hora, siguiente paciente,
    agenda de hoy, consultorios en este momento, caja e inventario, y la tendencia de seis meses.
    Datos de HomeController (se actualizan solos cada minuto vía home.datos).
    Lo que se ve depende de los permisos de quien entra.
--}}

@section('content')
@php
    $datosDash = array_merge($datos ?? [], [
        'urlDatos' => \Illuminate\Support\Facades\Route::has('home.datos') ? route('home.datos') : null,
    ]);
@endphp

<div class="db" x-data="modDash()" x-cloak>

    <!-- HOY: fecha, avance del día y accesos -->
    <section class="db-hoy db-aparece">
        <div class="db-g">
            <b class="t" x-text="Number(d.hoy.slice(8))"></b>
            <span>
                <em x-text="diaSemana(d.hoy)"></em>
                <span x-text="mesAnio(d.hoy)"></span>
            </span>
            <svg class="db-ecg" viewBox="0 0 160 44" aria-hidden="true">
                <path class="base" d="M0 26 H38 L44 22 L50 26 H58 L64 34 L72 4 L80 40 L86 26 H100 L108 18 L118 26 H160"/>
                <path class="vivo" d="M0 26 H38 L44 22 L50 26 H58 L64 34 L72 4 L80 40 L86 26 H100 L108 18 L118 26 H160"/>
            </svg>
        </div>

        <div class="db-avance">
            <p class="db-frase">
                <template x-if="r.total === 0"><span>Hoy no hay citas agendadas.</span></template>
                <template x-if="r.total > 0">
                    <span><b class="t" x-text="r.total - r.canceladas"></b> <span x-text="(r.total - r.canceladas) === 1 ? 'cita hoy' : 'citas hoy'"></span>
                        · <b class="t" x-text="r.finalizadas"></b> atendidas
                        · <b class="t" x-text="r.porAtender"></b> por atender</span>
                </template>
            </p>
            <div class="db-barra" x-show="r.total > 0" role="img" :aria-label="r.finalizadas + ' atendidas, ' + r.enCurso + ' en consulta, ' + r.porAtender + ' por atender, ' + r.canceladas + ' canceladas'">
                <i class="e-finalizada" :style="'flex-grow:' + r.finalizadas"></i>
                <i class="e-en-curso" :style="'flex-grow:' + r.enCurso"></i>
                <i class="e-pendiente" :style="'flex-grow:' + r.porAtender"></i>
                <i class="e-cancelada" :style="'flex-grow:' + r.canceladas"></i>
            </div>
            <ul class="db-ley" x-show="r.total > 0">
                <li class="e-finalizada">Atendidas</li><li class="e-en-curso">En consulta</li><li class="e-pendiente">Por atender</li><li class="e-cancelada" x-show="r.canceladas">Canceladas</li>
            </ul>
            <ul class="db-mini">
                <li><i class="ic-cred"><i class="bi bi-clipboard2-check"></i></i><b class="t" x-text="r.finalizadas"></b><span x-text="r.finalizadas === 1 ? 'consulta finalizada' : 'consultas finalizadas'"></span></li>
                <li><i class="ic-teal"><i class="bi bi-file-earmark-medical"></i></i><b class="t" x-text="d.actividad.recetas"></b><span x-text="d.actividad.recetas === 1 ? 'receta emitida' : 'recetas emitidas'"></span></li>
                <li><i class="ic-ambar"><i class="bi bi-person-plus"></i></i><b class="t" x-text="d.actividad.pacientesNuevos"></b><span x-text="d.actividad.pacientesNuevos === 1 ? 'paciente nuevo' : 'pacientes nuevos'"></span></li>
            </ul>
        </div>

        <div class="db-acc">
            <a class="db-dark" x-show="d.puede.citas && d.urls.citas" :href="d.urls.citas"><i class="bi bi-calendar-plus"></i> Agendar cita</a>
            <a class="db-btn" x-show="d.puede.pacientes && d.urls.pacientes" :href="d.urls.pacientes + '?nuevo=1'"><i class="bi bi-person-plus"></i> Registrar paciente</a>
            <a class="db-btn" x-show="d.puede.consultas && d.urls.consultas" :href="d.urls.consultas"><i class="bi bi-clipboard2-pulse"></i> Ir a consultas</a>
            <button type="button" class="db-sync" @click="refrescar(true)" :disabled="cargando || !d.urlDatos" :title="error ? 'Sin conexión: toca para reintentar' : 'Actualizar ahora'">
                <span :class="error ? 'mal' : ''"></span><span class="t" x-text="error ? 'Sin conexión' : 'Actualizado ' + d.generado"></span>
                <i class="bi bi-arrow-clockwise" :class="{ 'gira': cargando }"></i>
            </button>
        </div>
    </section>

    <!-- INDICADORES -->
    <section class="db-ind">
        <article class="db-k db-aparece" style="animation-delay: 30ms">
            <header><span>Pacientes hoy</span><i class="db-k-ic ic-cred"><i class="bi bi-people-fill"></i></i></header>
            <div class="db-k-v"><b class="t" x-text="Math.round(anim.pacientes)"></b></div>
            <div class="db-barras7" role="img" :aria-label="'Últimos 7 días: ' + k.pacientesHoy.serie.map(p => p.dia + ' ' + p.valor).join(', ')">
                <template x-for="(p, i) in k.pacientesHoy.serie" :key="'s' + i">
                    <span :class="i === k.pacientesHoy.serie.length - 1 ? 'hoy' : ''" :title="p.dia + ': ' + p.valor">
                        <i :style="'height:' + (montado ? altoSpark(p.valor) : 0) + '%'"></i><small x-text="p.dia.slice(0, 1)"></small>
                    </span>
                </template>
            </div>
            <small class="db-k-pie" x-text="textoDelta(k.pacientesHoy.delta, 'vs. la semana pasada')" :class="claseDelta(k.pacientesHoy.delta)"></small>
        </article>

        <article class="db-k db-aparece" style="animation-delay: 55ms">
            <header><span>Por atender</span><i class="db-k-ic ic-ambar"><i class="bi bi-hourglass-split"></i></i></header>
            <div class="db-k-v"><b class="t" x-text="Math.round(anim.pendientes)"></b><em x-text="k.pendientes.hoy + ' hoy'"></em></div>
            <div class="db-k-seg" role="img" :aria-label="k.pendientes.confirmadas + ' confirmadas y ' + (k.pendientes.valor - k.pendientes.confirmadas) + ' sin confirmar'">
                <i class="conf" :style="'flex-grow:' + (montado ? k.pendientes.confirmadas : 0)"></i>
                <i class="pend" :style="'flex-grow:' + (montado ? Math.max(0, k.pendientes.valor - k.pendientes.confirmadas) : 0)"></i>
            </div>
            <ul class="db-ley db-ley-s">
                <li class="l-conf"><span x-text="k.pendientes.confirmadas + ' confirmadas'"></span></li>
                <li class="l-pend"><span x-text="Math.max(0, k.pendientes.valor - k.pendientes.confirmadas) + ' sin confirmar'"></span></li>
            </ul>
        </article>

        <article class="db-k db-aparece" style="animation-delay: 80ms">
            <header><span>Consultas del mes</span><i class="db-k-ic ic-teal"><i class="bi bi-clipboard2-check-fill"></i></i></header>
            <div class="db-k-v"><b class="t" x-text="Math.round(anim.consultas)"></b><em class="db-k-em2" x-text="'atendidas'"></em></div>
            <svg class="db-area" viewBox="0 0 200 44" preserveAspectRatio="none" role="img" :aria-label="'Consultas atendidas por día de este mes'">
                <defs><linearGradient id="dbAreaG" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0e7c72" stop-opacity=".28"/><stop offset="1" stop-color="#0e7c72" stop-opacity="0"/></linearGradient></defs>
                <path class="relleno" :d="area.relleno" fill="url(#dbAreaG)"/>
                <path class="linea" :d="area.linea" vector-effect="non-scaling-stroke"/>
            </svg>
            <small class="db-k-pie" x-text="textoDelta(k.consultasMes.delta, 'vs. el mes anterior')" :class="claseDelta(k.consultasMes.delta)"></small>
        </article>

        <article class="db-k db-k-anillo db-aparece" style="animation-delay: 105ms">
            <header><span>Ocupación de consultorios</span><i class="db-k-ic ic-velo"><i class="bi bi-hospital-fill"></i></i></header>
            <div class="db-anillo-w">
                <svg class="db-anillo" viewBox="0 0 64 64" role="img" :aria-label="k.ocupacion.porcentaje + '% de ocupación'">
                    <circle cx="32" cy="32" r="26" class="pista"/>
                    <circle cx="32" cy="32" r="26" class="valor" :style="'stroke-dashoffset:' + (163.4 - 163.4 * (montado ? k.ocupacion.porcentaje : 0) / 100)"/>
                </svg>
                <b class="t" x-text="Math.round(anim.ocupacion) + '%'"></b>
                <dl>
                    <div class="o-ocu"><dt>Ocupados</dt><dd class="t" x-text="k.ocupacion.ocupados"></dd></div>
                    <div class="o-lib"><dt>Libres</dt><dd class="t" x-text="k.ocupacion.disponibles"></dd></div>
                    <div class="o-man"><dt>Mantenimiento</dt><dd class="t" x-text="k.ocupacion.mantenimiento"></dd></div>
                </dl>
            </div>
        </article>
    </section>

    <!-- CINTA DEL DÍA -->
    <section class="db-pulso db-aparece" style="animation-delay: 60ms" x-show="d.agenda.length" aria-label="Citas de hoy por hora">
        <header>
            <h2>El día por hora</h2>
            <p>Toca una cita para verla en la ficha.</p>
        </header>
        <div class="db-cinta" :style="'--carriles:' + cinta.carriles">
            <template x-for="h in cinta.horas" :key="'h' + h">
                <span class="db-hora t" :style="'left:' + pos(h * 60) + '%'" x-text="String(h).padStart(2, '0')"></span>
            </template>
            <template x-for="b in cinta.bloques" :key="'b' + b.c.id">
                <button type="button" class="db-bloque" :class="['e-' + slug(b.c.estado), sel && sel.id === b.c.id ? 'sel' : ''].join(' ')"
                        :style="'left:' + pos(b.ini) + '%; width:' + ancho(b) + '%; top:' + (b.carril * 30 + 24) + 'px'"
                        @click="elegir(b.c)" :aria-label="b.c.hora + ' ' + b.c.paciente + ', ' + etiquetaEstado(b.c.estado)">
                    <span x-text="b.c.paciente"></span>
                    <em class="db-tip"><b class="t" x-text="b.c.hora + '–' + b.c.fin"></b> <span x-text="b.c.paciente"></span><small x-text="b.c.doctor + (b.c.consultorio ? ' · ' + b.c.consultorio : '')"></small></em>
                </button>
            </template>
            <i class="db-ahora" x-show="minAhora >= cinta.desde && minAhora <= cinta.hasta" :style="'left:' + pos(minAhora) + '%'"><b class="t" x-text="horaActual"></b></i>
        </div>
    </section>

    <!-- ESCENARIO -->
    <div class="db-esc">
        <div class="db-centro">

            <!-- Ficha: siguiente paciente o cita elegida -->
            <article class="db-ficha db-aparece" style="animation-delay: 90ms" :class="ficha ? 'e-' + slug(ficha.estado) : 'vacia'">
                <span class="db-gl" x-html="ficha ? guilloche(ficha.paciente + ficha.id) : guilloche('MEDITRACK')"></span>
                <template x-if="ficha">
                    <div class="db-ficha-in">
                        <p class="db-ficha-k">
                            <span x-text="tituloFicha"></span>
                            <button type="button" x-show="sel" @click="sel = null" aria-label="Volver al siguiente paciente"><i class="bi bi-x-lg"></i></button>
                        </p>
                        <div class="db-ficha-p">
                            <span class="db-ros" x-html="roseta(ficha.paciente, 58)"></span>
                            <div>
                                <h2 x-text="ficha.paciente"></h2>
                                <p x-text="ficha.motivo || 'Sin motivo registrado'"></p>
                            </div>
                        </div>
                        <p class="db-ficha-alg" x-show="ficha.alergias.length"><i class="bi bi-exclamation-triangle-fill"></i><span x-text="'Alergias: ' + ficha.alergias.join(', ')"></span></p>
                        <dl class="db-ficha-d">
                            <div><dt>Hora</dt><dd class="t" x-text="ficha.hora + '–' + ficha.fin"></dd></div>
                            <div><dt>Médico</dt><dd x-text="ficha.doctor"></dd></div>
                            <div><dt>Consultorio</dt><dd x-text="ficha.consultorio || 'Sin asignar'"></dd></div>
                            <div><dt>Tipo</dt><dd x-text="ficha.tipo || '—'"></dd></div>
                        </dl>
                        <div class="db-ficha-f">
                            <span class="db-cuenta t" x-text="cuentaFicha"></span>
                            <a class="db-foil" x-show="d.puede.consultas && d.urls.consultas" :href="d.urls.consultas">Abrir consultas</a>
                            <a class="db-claro" x-show="d.puede.pacientes && d.urls.pacientes && ficha.paciente_id" :href="d.urls.pacientes + '?p=' + ficha.paciente_id">Expediente</a>
                        </div>
                    </div>
                </template>
                <template x-if="!ficha">
                    <div class="db-ficha-in db-ficha-vacia">
                        <svg viewBox="0 0 44 44" aria-hidden="true"><rect x="15" y="2" width="14" height="40" rx="4"/><rect x="2" y="15" width="40" height="14" rx="4"/></svg>
                        <b x-text="d.agenda.length ? 'No quedan pacientes por atender hoy' : 'Sin citas para hoy'"></b>
                        <p x-text="d.agenda.length ? 'Todas las citas del día ya se atendieron o se cancelaron.' : 'Cuando se agende una cita para hoy aparecerá aquí.'"></p>
                    </div>
                </template>
            </article>

            <!-- Agenda de hoy -->
            <section class="db-panel db-agenda db-aparece" style="animation-delay: 120ms">
                <header>
                    <h2>Agenda de hoy</h2>
                    <a class="db-link" x-show="d.puede.citas && d.urls.citas" :href="d.urls.citas">Ver citas <i class="bi bi-arrow-right"></i></a>
                </header>
                <div class="db-seg" role="group" aria-label="Filtrar agenda" x-show="d.agenda.length">
                    <template x-for="f in filtros" :key="'f' + f.k">
                        <button type="button" :aria-pressed="(filtro === f.k).toString()" @click="filtro = f.k" x-show="f.k !== 'mias' || hayMias">
                            <span x-text="f.t"></span><b class="t" x-text="contar(f.k)"></b>
                        </button>
                    </template>
                </div>
                <ol class="db-lista">
                    <template x-for="(c, i) in agendaFiltrada" :key="'a' + c.id">
                        <li>
                            <p class="db-ya" x-show="i === indiceAhora"><b class="t" x-text="horaActual"></b></p>
                            <button type="button" :class="['e-' + slug(c.estado), sel && sel.id === c.id ? 'sel' : ''].join(' ')" @click="elegir(c)">
                                <span class="db-l-h t"><b x-text="c.hora"></b><small x-text="c.fin"></small></span>
                                <span class="db-l-n">
                                    <b x-text="c.paciente"></b>
                                    <small x-text="c.doctor + (c.consultorio ? ' · ' + c.consultorio : '')"></small>
                                </span>
                                <i class="bi bi-exclamation-triangle-fill db-l-alg" x-show="c.alergias.length" :title="'Alergias: ' + c.alergias.join(', ')"></i>
                                <span class="db-chip" x-text="etiquetaEstado(c.estado)"></span>
                            </button>
                        </li>
                    </template>
                    <li x-show="agendaFiltrada.length && indiceAhora === agendaFiltrada.length"><p class="db-ya"><b class="t" x-text="horaActual"></b></p></li>
                </ol>
                <p class="db-vacio" x-show="!agendaFiltrada.length" x-text="d.agenda.length ? 'Ninguna cita con este filtro.' : (d.proximas.length ? 'No hay citas para hoy. Estas son las siguientes:' : 'No hay citas para hoy ni próximas.')"></p>
                <ol class="db-lista db-lista-libre" x-show="!d.agenda.length && d.proximas.length">
                    <template x-for="c in d.proximas" :key="'pa' + c.id">
                        <li>
                            <div class="db-px" :class="'e-' + slug(c.estado)">
                                <span class="db-l-h t"><b x-text="c.hora"></b><small x-text="fechaMini(c.fecha)"></small></span>
                                <span class="db-l-n"><b x-text="c.paciente"></b><small x-text="c.doctor + (c.consultorio ? ' · ' + c.consultorio : '')"></small></span>
                                <span class="db-chip" x-text="etiquetaEstado(c.estado)"></span>
                            </div>
                        </li>
                    </template>
                </ol>
            </section>


            <!-- Médicos de hoy -->
            <section class="db-panel db-meds db-aparece" style="animation-delay: 150ms" x-show="d.medicosHoy.length">
                <header>
                    <h2>Médicos de hoy</h2>
                    <ul class="db-ley">
                        <li class="m-at">Atendidas</li><li class="m-cu">En consulta</li><li class="m-pa">Por atender</li>
                    </ul>
                </header>
                <ul class="db-med-l">
                    <template x-for="m in d.medicosHoy" :key="'md' + m.nombre">
                        <li>
                            <span class="db-med-ros" x-html="roseta(m.nombre, 34)"></span>
                            <div>
                                <p><b x-text="m.nombre"></b><span class="t" x-text="m.atendidas + ' de ' + m.total"></span></p>
                                <div class="db-med-b" :style="'width:' + (m.total / maxMedico * 100) + '%'" role="img" :aria-label="m.atendidas + ' atendidas, ' + m.enCurso + ' en consulta, ' + m.porAtender + ' por atender'">
                                    <i class="m-at" :style="'flex-grow:' + (montado ? m.atendidas : 0) + (m.atendidas ? '' : '; display: none')"></i>
                                    <i class="m-cu" :style="'flex-grow:' + (montado ? m.enCurso : 0) + (m.enCurso ? '' : '; display: none')"></i>
                                    <i class="m-pa" :style="'flex-grow:' + (montado ? m.porAtender : 0) + (m.porAtender ? '' : '; display: none')"></i>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </section>
            <!-- Citas por mes -->
            <section class="db-panel db-graf db-estira db-aparece" style="animation-delay: 150ms">
                <header>
                    <div>
                        <h2>Citas por mes</h2>
                        <p>Últimos seis meses</p>
                    </div>
                    <ul class="db-ley">
                        <li class="g-at">Atendidas</li><li class="g-pr">Programadas</li><li class="g-ca">Canceladas</li>
                    </ul>
                </header>

                <div class="db-plot" @mouseleave="barra = null">
                    <template x-for="n in [4, 3, 2, 1, 0]" :key="'r' + n">
                        <div class="db-reg" :class="n === 0 ? 'base' : ''" :style="'bottom:' + n * 25 + '%'"><span class="t" x-text="Math.round(escala * n / 4)"></span></div>
                    </template>
                    <div class="db-barras">
                        <template x-for="(m, i) in d.grafica.meses" :key="'m' + m.clave">
                            <div class="db-col" @mouseenter="barra = i" @focus="barra = i" tabindex="0" :class="barra !== null && barra !== i ? 'tenue' : ''"
                                 :aria-label="m.etiqueta + ' ' + m.anio + ': ' + m.finalizadas + ' atendidas, ' + m.activas + ' programadas, ' + m.canceladas + ' canceladas'">
                                <b class="db-tot t" :style="'bottom: calc(' + alto(m) + '% + 6px)'" x-show="valor(m) > 0" x-text="m.total"></b>
                                <div class="db-pila" :style="'height:' + (montado ? alto(m) : 0) + '%'">
                                        <div class="db-pila-in">
                                            <i class="g-at" :style="'flex-grow:' + m.finalizadas + (m.finalizadas ? '' : '; display: none')"></i>
                                            <i class="g-pr" :style="'flex-grow:' + m.activas + (m.activas ? '' : '; display: none')"></i>
                                            <i class="g-ca" :style="'flex-grow:' + m.canceladas + (m.canceladas ? '' : '; display: none')"></i>
                                        </div>
                                </div>
                                <div class="db-tt" x-show="barra === i" x-cloak :class="i > d.grafica.meses.length - 3 ? 'izq' : ''">
                                    <p x-text="m.etiqueta + ' ' + m.anio"></p>
                                        <div>
                                            <span class="g-at">Atendidas <b class="t" x-text="m.finalizadas"></b></span>
                                            <span class="g-pr">Programadas <b class="t" x-text="m.activas"></b></span>
                                            <span class="g-ca">Canceladas <b class="t" x-text="m.canceladas"></b></span>
                                        </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="db-sin" x-show="escalaReal === 0">Aún no hay registros en estos meses.</p>
                </div>
                <div class="db-meses">
                    <template x-for="(m, i) in d.grafica.meses" :key="'e' + m.clave"><span :class="i === d.grafica.meses.length - 1 ? 'hoy' : ''" x-text="m.etiqueta"></span></template>
                </div>

                <table class="db-sr">
                    <caption>Citas por mes</caption>
                    <thead><tr><th>Mes</th><th>Atendidas</th><th>Programadas</th><th>Canceladas</th></tr></thead>
                    <tbody>
                        <template x-for="m in d.grafica.meses" :key="'t' + m.clave">
                            <tr><td x-text="m.etiqueta + ' ' + m.anio"></td><td x-text="m.finalizadas"></td><td x-text="m.activas"></td><td x-text="m.canceladas"></td></tr>
                        </template>
                    </tbody>
                </table>
            </section>
        </div>

        <aside class="db-lado">
            <!-- Consultorios ahora -->
            <section class="db-panel db-aparece" style="animation-delay: 100ms" x-show="d.consultorios.length">
                <header>
                    <h2>Consultorios ahora</h2>
                    <a class="db-link" x-show="d.puede.consultorios && d.urls.consultorios" :href="d.urls.consultorios">Plano <i class="bi bi-arrow-right"></i></a>
                </header>
                <div class="db-salas">
                    <template x-for="s in salasActivas" :key="'c' + s.id">
                        <div class="db-sala" :class="'s-' + s.estado">
                            <p><b x-text="s.nombre"></b><small class="t" x-show="s.piso" x-text="'P' + s.piso"></small></p>
                            <span x-text="s.estado === 'ocupado' ? (s.medico || 'Ocupado') : (s.estado === 'mantenimiento' ? 'Mantenimiento' : 'Libre')"></span>
                            <small x-show="s.estado === 'ocupado' && s.paciente" x-text="'con ' + s.paciente + (s.desde ? ' · ' + s.desde : '')"></small>
                        </div>
                    </template>
                </div>
                <p class="db-mant" x-show="salasMant.length"><b class="t" x-text="salasMant.length"></b> en mantenimiento: <span x-text="salasMant.map(s => s.nombre).join(', ')"></span></p>
            </section>

            <!-- Inventario -->
            <section class="db-panel db-stock db-aparece" style="animation-delay: 160ms" x-show="d.stock.disponible && (d.puede.inventario || d.stock.total)">
                <header>
                    <h2>Inventario</h2>
                    <a class="db-link" x-show="d.puede.inventario && d.urls.inventario" :href="d.urls.inventario">Surtir <i class="bi bi-arrow-right"></i></a>
                </header>
                <p class="db-stock-r" x-show="d.stock.total">
                    <b class="t" x-text="d.stock.total"></b>
                    <span x-text="d.stock.total === 1 ? 'producto por surtir' : 'productos por surtir'"></span>
                    <em x-show="d.stock.agotados" x-text="d.stock.agotados + (d.stock.agotados === 1 ? ' agotado' : ' agotados')"></em>
                </p>
                <p class="db-ok" x-show="!d.stock.total"><i class="bi bi-check2-circle"></i> Todo por encima del mínimo.</p>

                <!-- Galería 3D: las cajas del almacén en carrusel -->
                <div class="db-gal" x-show="(d.stock.galeria || []).length" x-cloak>
                    <div class="db-gal-tabs" role="group" aria-label="Qué mostrar" x-show="d.stock.total">
                        <button type="button" @click="galCambiar('surtir')" :aria-pressed="(galVista === 'surtir').toString()">Por surtir <b class="t" x-text="d.stock.total"></b></button>
                        <button type="button" @click="galCambiar('todo')" :aria-pressed="(galVista === 'todo').toString()">Todo <b class="t" x-text="(d.stock.galeria || []).length"></b></button>
                    </div>

                    <div class="db-gal-esc" x-ref="galEsc" tabindex="0" role="group" aria-roledescription="carrusel" :aria-label="galActual ? 'Producto ' + (galI + 1) + ' de ' + galeria.length + ': ' + titulo(galActual.nombre) : 'Galería'"
                         @pointerdown="galAgarrar($event)" @keydown.arrow-left.prevent="galMover(-1)" @keydown.arrow-right.prevent="galMover(1)">
                        <span class="db-gal-luz" aria-hidden="true"></span>
                        <span class="db-gal-piso" aria-hidden="true"></span>
                        <template x-for="(p, i) in galeria" :key="'g' + galVista + p.id">
                            <div class="db-gal-it" :class="i === galI && 'centro'" :style="galPos(i)" @click="galClic(i)" :data-i="i">
                                <span class="iv-caja" :class="'t-' + tipoClase(p)" :style="'--k:' + colorCaja(p) + ';' + (i === galI ? '' : LUZ_LADO) + (i === galI ? '' : ';transform: rotateX(-12deg) rotateY(-32deg)')" x-html="cajaDe(p)"></span>
                            </div>
                        </template>
                        <button type="button" class="db-gal-fl izq" @click.stop="galMover(-1)" @pointerdown.stop :disabled="galI <= 0" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" class="db-gal-fl der" @click.stop="galMover(1)" @pointerdown.stop :disabled="galI >= galeria.length - 1" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
                        <span class="db-gal-n t" aria-hidden="true" x-text="(galI + 1) + ' / ' + galeria.length"></span>
                    </div>

                    <template x-if="galActual">
                        <div class="db-gal-info" :class="'s-' + galActual.estado">
                            <p class="db-gal-cat"><i :style="'background:' + colorCaja(galActual)"></i><span x-text="galActual.categoria"></span><em x-text="esInsumo(galActual) ? 'Insumo' : 'Medicamento'"></em></p>
                            <b class="db-gal-nom" x-text="titulo(galActual.nombre)"></b>
                            <div class="db-st-b" role="img" :aria-label="galActual.disponible + ' en existencia, mínimo ' + galActual.minimo">
                                <i :style="'width:' + anchoGal(galActual) + '%'"></i>
                                <s x-show="galActual.minimo > 0" :style="'left:' + marcaGal(galActual) + '%'" title="Mínimo"></s>
                            </div>
                            <p class="db-gal-dato">
                                <span class="t"><b x-text="galActual.disponible"></b> en existencia · mínimo <span x-text="galActual.minimo"></span></span>
                                <span class="db-gal-tag" x-text="{ agotado: 'Agotado', bajo: 'Por surtir', ok: 'Suficiente' }[galActual.estado]"></span>
                            </p>
                            <div class="db-gal-pie">
                                <span class="db-gal-puntos" aria-hidden="true"><template x-for="(p, i) in galeria" :key="'d' + galVista + p.id"><i :class="i === galI && 'on'" @click="galIr(i)"></i></template></span>
                                <a class="db-link" x-show="d.puede.inventario && d.urls.inventario" :href="d.urls.inventario + '?p=' + galActual.id">Ver en inventario <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Tipo de consulta -->
            <section class="db-panel db-tipos db-aparece" style="animation-delay: 180ms" :class="{ 'db-estira': !(d.agenda.length && d.proximas.length) }">
                <header>
                    <div><h2>Tipo de consulta</h2><p>Citas de este mes</p></div>
                </header>
                <div class="db-dona-w">
                    <svg class="db-dona" viewBox="0 0 120 120" role="img" :aria-label="TIPOS.map(t => t.t + ' ' + (d.tiposMes[t.k] || 0)).join(', ')">
                        <circle cx="60" cy="60" r="46" class="pista"/>
                        <circle cx="60" cy="60" r="46" class="arco t-seg" :style="arco(0)"/>
                        <circle cx="60" cy="60" r="46" class="arco t-pri" :style="arco(1)"/>
                        <circle cx="60" cy="60" r="46" class="arco t-urg" :style="arco(2)"/>
                    </svg>
                    <p class="db-dona-c"><b class="t" x-text="totalTipos"></b><span x-text="totalTipos === 1 ? 'cita' : 'citas'"></span></p>
                </div>
                <ul class="db-tipos-l">
                    <template x-for="t in TIPOS" :key="'tp' + t.k">
                        <li :class="t.c"><span x-text="t.t"></span><b class="t" x-text="d.tiposMes[t.k] || 0"></b><em class="t" x-text="pctTipo(t.k) + '%'"></em></li>
                    </template>
                </ul>
            </section>

            <!-- Próximas citas (en el lateral cuando hay agenda hoy) -->
            <section class="db-panel db-prox db-aparece" style="animation-delay: 140ms" x-show="d.agenda.length && d.proximas.length" :class="{ 'db-estira': d.agenda.length && d.proximas.length }">
                <header>
                    <h2>Próximas citas</h2>
                    <a class="db-link" x-show="d.puede.citas && d.urls.citas" :href="d.urls.citas">Agenda <i class="bi bi-arrow-right"></i></a>
                </header>
                <ol class="db-lista">
                    <template x-for="c in d.proximas" :key="'px' + c.id">
                        <li>
                            <div class="db-px" :class="'e-' + slug(c.estado)">
                                <span class="db-l-h t"><b x-text="c.hora"></b><small x-text="fechaMini(c.fecha)"></small></span>
                                <span class="db-l-n"><b x-text="c.paciente"></b><small x-text="c.doctor + (c.consultorio ? ' · ' + c.consultorio : '')"></small></span>
                                
                            </div>
                        </li>
                    </template>
                </ol>
            </section>
        </aside>
    </div>

</div>
</div>

<script type="application/json" id="db-datos">{!! json_encode($datosDash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@verbatim
<style>
.db {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --g-at: #00897b; --g-pr: #c98a1e; --g-ca: #d0465c;
    --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.db * { box-sizing: border-box; }
.db :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.db button { cursor: pointer; }
.db button:disabled { cursor: default; }
.db .t { font-variant-numeric: tabular-nums; }
.db h2 { margin: 0; font: 700 17px/1.2 var(--disp); color: var(--tinta); }
.db p, .db dl, .db dd, .db ul, .db ol { margin: 0; }
.db ul, .db ol { list-style: none; padding: 0; }
.db-sr { position: absolute; left: 0; top: 0; width: 1px; height: 1px; border: 0; padding: 0; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
[x-cloak] { display: none !important; }

/* Estados de la cita (color fijo por significado) */
.db .e-pendiente, .db .e-confirmada { --c: var(--ambar); --cv: var(--ambarv); }
.db .e-confirmada { --c: var(--verde); --cv: var(--verdev); }
.db .e-en-curso { --c: var(--cred); --cv: var(--velo); }
.db .e-finalizada { --c: var(--media); --cv: var(--niebla); }
.db .e-cancelada { --c: var(--rosa); --cv: var(--rosav); }

/* ---------- Hoy ---------- */
.db-hoy { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: end; gap: 16px 32px; }
.db-g { display: flex; align-items: flex-end; gap: 12px; }
.db-g > b { font: 800 84px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); }
.db-g > span { display: flex; flex-direction: column; padding-bottom: 2px; font-size: 15px; font-weight: 500; line-height: 1.2; }
.db-g em { font: 700 20px/1.1 var(--disp); font-style: normal; color: var(--tinta); text-transform: capitalize; }
.db-avance { min-width: 0; padding-bottom: 2px; }
.db-frase { font-size: 15px; color: var(--texto); }
.db-frase b { font: 700 17px var(--disp); color: var(--tinta); }
.db-barra { display: flex; gap: 2px; height: 8px; margin-top: 10px; border-radius: 99px; overflow: hidden; background: var(--linea2); }
.db-barra i { flex-basis: 0; min-width: 0; background: var(--c); transition: flex-grow .45s var(--e); }
.db-barra .e-finalizada { background: var(--cred); }
.db-barra .e-pendiente { background: #d9b06a; }
.db-barra .e-en-curso { background: #3fbfae; }
.db-barra .e-cancelada { background: #e8a3ae; }
.db-ley { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 8px; font-size: 12.5px; color: var(--media); }
.db-ley li { display: flex; align-items: center; gap: 6px; }
.db-ley li:before { content: ''; width: 9px; height: 9px; border-radius: 3px; background: var(--c); }
.db-avance .db-ley .e-finalizada:before { background: var(--cred); }
.db-avance .db-ley .e-pendiente:before { background: #d9b06a; }
.db-avance .db-ley .e-en-curso:before { background: #3fbfae; }
.db-avance .db-ley .e-cancelada:before { background: #e8a3ae; }
.db-acc { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.db-dark, .db-btn { height: 40px; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px; border-radius: 11px; font: 600 14px var(--ui); text-decoration: none; white-space: nowrap; transition: background .15s, border-color .15s; }
.db-dark { background: var(--tinta); color: #fff; }
.db-dark:hover { background: var(--cred2); color: #fff; }
.db-btn { background: var(--papel); color: var(--tinta); border: 1px solid var(--linea); }
.db-btn:hover { border-color: #b9cbc6; color: var(--tinta); }
.db-btn .bi { color: var(--cred); }
.db-sync { margin-left: auto; height: 34px; display: inline-flex; align-items: center; gap: 8px; padding: 0 12px; border-radius: 99px; border: 1px solid var(--linea); background: var(--papel); font: 500 12.5px var(--ui); color: var(--media); }
.db-sync > span:first-child { width: 7px; height: 7px; border-radius: 50%; background: var(--verde); }
.db-sync > span.mal { background: var(--rosa); }
.db-sync .bi { font-size: 13px; color: var(--cred); }
.db-sync .gira { animation: dbGira .8s linear infinite; }
@keyframes dbGira { to { transform: rotate(360deg); } }

/* ECG */
.db-ecg { width: 150px; height: 44px; margin: 0 0 4px 8px; flex: none; }
.db-ecg path { fill: none; stroke-linecap: round; stroke-linejoin: round; }
.db-ecg .base { stroke: var(--linea); stroke-width: 2; }
.db-ecg .vivo { stroke: var(--rosa); stroke-width: 2.4; stroke-dasharray: 60 400; stroke-dashoffset: 60; animation: dbEcg 2.4s linear infinite; }
@keyframes dbEcg { to { stroke-dashoffset: -400; } }

/* ---------- Indicadores ---------- */
.db-ind { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.db-k { position: relative; min-width: 0; display: flex; flex-direction: column; gap: 8px; padding: 14px 16px; background: var(--papel); border: 1px solid var(--linea); border-radius: 16px; transition: transform .2s var(--e), box-shadow .2s, border-color .2s; }
.db-k:hover { transform: translateY(-2px); border-color: #c5d8d3; box-shadow: 0 16px 30px -22px rgba(11, 30, 28, .45); }
.db-k header { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.db-k header > span { font-size: 13px; font-weight: 600; color: var(--texto); }
.db-k-ic { flex: none; width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; font-style: normal; font-size: 16px; }
.ic-cred { background: var(--cred); color: var(--foil); }
.ic-ambar { background: var(--ambarv); color: var(--ambar); }
.ic-teal { background: #dff3ef; color: var(--teal); }
.ic-velo { background: var(--velo); color: var(--cred); }
.db-k-v { display: flex; align-items: baseline; gap: 8px; }
.db-k-v b { font: 750 36px/1 var(--disp); letter-spacing: -.02em; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-k-v em { font-style: normal; padding: 1px 8px; border-radius: 99px; background: var(--ambarv); color: var(--ambar); font-size: 12px; font-weight: 600; white-space: nowrap; }
.db-k-pie { font-size: 12.5px; color: var(--media); margin-top: auto; }
.db-k-pie.sube { color: var(--verde); }
.db-k-pie.baja { color: var(--rosa); }
.db-barras7 { display: flex; align-items: stretch; gap: 5px; height: 48px; }
.db-barras7 span { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 3px; }
.db-barras7 i { width: 100%; max-width: 16px; min-height: 3px; border-radius: 4px 4px 2px 2px; background: #b8d8d2; transition: height .45s var(--e); }
.db-barras7 small { font: 500 10px var(--mono); color: var(--media); line-height: 1; }
.db-barras7 span.hoy i { background: var(--cred); }
.db-barras7 span.hoy small { color: var(--cred); font-weight: 700; }
.db-k-seg { display: flex; gap: 2px; height: 10px; border-radius: 99px; overflow: hidden; background: var(--linea2); margin-top: 6px; }
.db-k-seg i { flex-basis: 0; transition: flex-grow .45s var(--e); }
.db-k-seg .conf { background: var(--verde); }
.db-k-seg .pend { background: #d9b06a; }
.db-ley-s { margin-top: 2px; font-size: 12px; }
.db .db-ley .l-conf:before { background: var(--verde); }
.db .db-ley .l-pend:before { background: #d9b06a; }
.db-area { width: 100%; height: 44px; display: block; }
.db-area .linea { fill: none; stroke: var(--teal); stroke-width: 2; stroke-linejoin: round; }
.db-anillo-w { position: relative; display: grid; grid-template-columns: 76px minmax(0, 1fr); align-items: center; gap: 14px; flex: 1; }
.db-anillo { width: 76px; height: 76px; transform: rotate(-90deg); }
.db-anillo circle { fill: none; stroke-width: 8; }
.db-anillo .pista { stroke: var(--linea2); }
.db-anillo .valor { stroke: var(--cred); stroke-linecap: round; stroke-dasharray: 163.4; transition: stroke-dashoffset .6s var(--e); }
.db-anillo-w > b { position: absolute; left: 0; width: 76px; text-align: center; font: 750 18px var(--disp); color: var(--tinta); }
.db-anillo-w dl div { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 2px 0; font-size: 12.5px; }
.db-anillo-w dt { display: flex; align-items: center; gap: 6px; color: var(--media); }
.db-anillo-w dt:before { content: ''; width: 8px; height: 8px; border-radius: 3px; background: var(--oc); }
.db-anillo-w dd { font: 600 13px var(--mono); color: var(--tinta); }
.db-anillo-w .o-ocu { --oc: var(--cred); } .db-anillo-w .o-lib { --oc: #9fd3c9; } .db-anillo-w .o-man { --oc: #d9b06a; }

/* ---------- Cinta del día ---------- */
.db-pulso { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; padding: 16px 18px 14px; box-shadow: 0 18px 40px -34px rgba(11, 30, 28, .5); }
.db-pulso header { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 4px; }
.db-pulso header p { font-size: 12.5px; color: var(--media); }
@container (max-width: 559px) { .db-pulso header p { display: none; } }
.db-cinta { position: relative; height: calc(var(--carriles) * 30px + 30px); margin: 0 6px; }
.db-cinta:before { content: ''; position: absolute; left: 0; right: 0; top: 18px; border-top: 1px solid var(--linea2); }
.db-hora { position: absolute; top: 0; transform: translateX(-50%); font: 500 11px var(--mono); color: var(--media); }
.db-hora:after { content: ''; position: absolute; left: 50%; top: 18px; height: calc(var(--carriles) * 30px + 8px); border-left: 1px dashed var(--linea2); }
.db-bloque { position: absolute; height: 26px; min-width: 8px; padding: 0 8px; border-radius: 7px; border: 0; background: var(--cv); box-shadow: inset 3px 0 0 var(--c); text-align: left; overflow: visible; transition: transform .15s var(--e), box-shadow .15s; }
.db-bloque > span { display: block; font-size: 12px; font-weight: 600; color: var(--tinta); line-height: 26px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-bloque.e-cancelada > span { text-decoration: line-through; color: var(--media); }
.db-bloque.e-finalizada > span { color: var(--media); }
.db-bloque.e-en-curso { background: var(--cred); box-shadow: none; }
.db-bloque.e-en-curso > span { color: #fff; }
.db-bloque:hover, .db-bloque.sel { transform: translateY(-1px); box-shadow: inset 3px 0 0 var(--c), 0 0 0 2px var(--c); z-index: 2; }
.db-tip { display: none; position: absolute; left: 0; bottom: calc(100% + 6px); z-index: 5; min-width: 180px; padding: 8px 10px; border-radius: 10px; background: var(--tinta); color: #fff; font-style: normal; font-size: 12.5px; white-space: nowrap; box-shadow: 0 14px 28px -16px rgba(11, 30, 28, .7); pointer-events: none; }
.db-tip b { font: 600 12.5px var(--mono); color: var(--foil); }
.db-tip small { display: block; color: #bfd3cf; font-size: 12px; }
.db-bloque:hover .db-tip { display: block; }
.db-ahora { position: absolute; top: 14px; bottom: -4px; border-left: 2px solid var(--rosa); z-index: 3; pointer-events: none; }
.db-ahora b { position: absolute; top: -14px; left: 50%; transform: translateX(-50%); padding: 0 4px; border-radius: 4px; background: var(--rosa); color: #fff; font: 600 10.5px/14px var(--mono); }

/* ---------- Escenario ---------- */
.db-esc { display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px; align-items: start; }
@container (min-width: 1000px) { .db-esc { grid-template-columns: minmax(0, 1fr) 340px; } }
.db-centro, .db-lado { min-width: 0; display: flex; flex-direction: column; gap: 18px; }
.db-centro > *, .db-lado > * { flex: none; }
@container (min-width: 1000px) {
    .db-esc { align-items: stretch; }
    .db-estira { flex: 1 0 auto !important; }
    .db-prox.db-estira .db-lista { max-height: none; }
}
@container (min-width: 640px) and (max-width: 999px) { .db-lado { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; } }

.db-panel { position: relative; background: var(--papel); border: 1px solid var(--linea); border-radius: 16px; padding: 14px 16px 16px; }
.db-panel > header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
.db-link { display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: var(--cred); text-decoration: none; white-space: nowrap; }
.db-link:hover { color: var(--cred2); text-decoration: underline; }
.db-vacio { padding: 10px 2px 2px; font-size: 13px; color: var(--media); }

/* Ficha del paciente: credencial verde */
.db-ficha { position: relative; overflow: hidden; border-radius: 20px; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #0f6c64 0%, var(--cred) 45%, var(--cred2) 100%); box-shadow: 0 24px 50px -30px rgba(7, 63, 59, .8); }
.db-gl { position: absolute; inset: 0; opacity: .45; pointer-events: none; }
.db-gl svg { width: 100%; height: 100%; }
.db-ficha-in { position: relative; padding: 18px 20px 18px; display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; }
.db-ficha-k { display: flex; align-items: center; justify-content: space-between; font: 600 11.5px var(--mono); letter-spacing: .08em; text-transform: uppercase; color: var(--foil); }
.db-ficha-k button { width: 28px; height: 28px; border-radius: 8px; border: 1px solid rgba(166, 239, 226, .3); background: transparent; color: var(--foil); display: grid; place-items: center; }
.db-ficha-k button:hover { background: rgba(166, 239, 226, .12); }
.db-ficha-p { display: flex; align-items: center; gap: 14px; min-width: 0; }
.db-ros { flex: none; width: 58px; height: 58px; display: grid; place-items: center; }
.db-ros svg { width: 58px; height: 58px; }
.db-ficha-p > div { min-width: 0; }
.db-ficha-p h2 { font: 800 28px/1.02 var(--disp); font-stretch: 85%; letter-spacing: -.01em; color: #fff; text-transform: uppercase; overflow-wrap: anywhere; }
.db-ficha-p p { margin-top: 4px; font-size: 14px; color: #cfe5e1; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.db-ficha-alg { display: inline-flex; align-items: center; gap: 8px; justify-self: start; padding: 5px 10px; border-radius: 99px; background: #fff; color: var(--rosa); font-size: 13px; font-weight: 600; }
.db-ficha-d { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px 16px; padding-top: 12px; border-top: 1px solid rgba(166, 239, 226, .2); }
.db-ficha-d dt { font-size: 11.5px; color: #9fc4bd; }
.db-ficha-d dd { font-size: 14px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-ficha-d dd.t { font-family: var(--mono); font-weight: 500; }
.db-ficha-f { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.db-cuenta { margin-right: auto; font: 600 14px var(--mono); color: var(--foil); }
.db-foil, .db-claro { height: 38px; display: inline-flex; align-items: center; padding: 0 14px; border-radius: 10px; font: 600 13.5px var(--ui); text-decoration: none; }
.db-foil { background: var(--foil); color: var(--cred2); }
.db-foil:hover { background: #c4f6ec; color: var(--cred2); }
.db-claro { border: 1px solid rgba(166, 239, 226, .35); color: #fff; }
.db-claro:hover { background: rgba(166, 239, 226, .12); color: #fff; }
.db-ficha.e-en-curso .db-ficha-k span:before { content: ''; display: inline-block; width: 7px; height: 7px; margin-right: 8px; border-radius: 50%; background: var(--foil); animation: dbLate 1.6s ease-in-out infinite; }
@keyframes dbLate { 50% { opacity: .3; } }
.db-ficha-vacia { justify-items: start; gap: 6px; padding: 24px 22px; }
.db-ficha-vacia svg { width: 34px; height: 34px; fill: var(--foil); opacity: .9; margin-bottom: 6px; }
.db-ficha-vacia b { font: 700 19px var(--disp); }
.db-ficha-vacia p { font-size: 14px; color: #cfe5e1; }

/* Agenda */
.db-seg { display: inline-flex; flex-wrap: wrap; gap: 2px; padding: 3px; border-radius: 11px; background: var(--niebla); margin-bottom: 8px; }
.db-seg button { height: 30px; display: inline-flex; align-items: center; gap: 6px; padding: 0 11px; border: 0; border-radius: 8px; background: none; font: 600 13px var(--ui); color: var(--media); }
.db-seg button b { font: 500 12px var(--mono); color: var(--media); }
.db-seg button[aria-pressed=true] { background: var(--papel); color: var(--tinta); box-shadow: 0 1px 3px rgba(11, 30, 28, .12); }
.db-seg button[aria-pressed=true] b { color: var(--cred); }
.db-lista { max-height: 460px; overflow-y: auto; margin: 0 -6px; padding: 0 6px; scrollbar-width: thin; }
.db-lista button { width: 100%; display: flex; align-items: center; gap: 12px; padding: 8px; border: 0; border-radius: 11px; background: none; text-align: left; }
.db-lista button:hover, .db-lista button.sel { background: var(--niebla); }
.db-lista button.sel { box-shadow: inset 3px 0 0 var(--cred); }
.db-l-h { flex: none; width: 58px; display: flex; flex-direction: column; align-items: center; padding: 4px 0; border-radius: 8px; background: var(--cv); }
.db-l-h b { font: 600 14px var(--mono); color: var(--c); }
.db-l-h small { font: 500 11px var(--mono); color: var(--media); }
.db-l-n { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.db-l-n b { font-size: 14px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-l-n small { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.e-cancelada .db-l-n b { text-decoration: line-through; color: var(--media); }
.db-l-alg { flex: none; color: var(--rosa); font-size: 13px; }
.db-chip { flex: none; padding: 2px 9px; border-radius: 99px; background: var(--cv); color: var(--c); font-size: 12px; font-weight: 600; white-space: nowrap; }
.db-ya { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
.db-ya b { padding: 0 5px; border-radius: 4px; background: var(--rosa); color: #fff; font: 600 11px/16px var(--mono); }
.db-ya:after { content: ''; flex: 1; border-top: 2px solid #f1b8c1; }

/* Consultorios */
.db-salas { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
.db-sala { min-width: 0; padding: 9px 10px 10px; border-radius: 11px; background: var(--niebla); box-shadow: inset 3px 0 0 var(--sc, var(--verde)); }
.db-sala p { display: flex; justify-content: space-between; gap: 6px; }
.db-sala p b { font-size: 13.5px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-sala p small { font: 500 11px var(--mono); color: var(--media); }
.db-sala > span { display: block; font-size: 12.5px; font-weight: 600; color: var(--sc); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-sala > small { display: block; font-size: 12px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-sala.s-disponible { --sc: var(--verde); }
.db-sala.s-ocupado { --sc: var(--cred); background: var(--velo); }
.db-sala.s-mantenimiento { --sc: var(--ambar); background: var(--ambarv); }
.db-mant { margin-top: 10px; font-size: 12.5px; color: var(--media); }
.db-mant b { color: var(--ambar); font-weight: 700; }

/* Caja */
.db-caja-v { font: 750 34px/1 var(--disp); letter-spacing: -.02em; color: var(--tinta); }
.db-caja-d { display: flex; flex-wrap: wrap; gap: 4px 12px; margin-top: 6px; font-size: 13px; color: var(--media); }
.db-pend { color: var(--ambar); font-weight: 600; }

/* Inventario */
.db-stock { min-width: 0; }
.db-ok { display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: var(--verde); }
.db-stock-r { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 6px; margin-bottom: 12px; font-size: 13.5px; color: var(--texto); }
.db-stock-r b { font: 750 22px/1 var(--disp); color: var(--ambar); }
.db-stock-r em { padding: 1px 8px; border-radius: 99px; background: var(--rosav); color: var(--rosa); font-style: normal; font-size: 12px; font-weight: 600; }
.db-stock ul { display: grid; grid-template-columns: minmax(0, 1fr); gap: 0; }
.db-stock li { min-width: 0; padding: 10px 0; border-top: 1px solid var(--linea2); --sc: #c98a1e; --sv: var(--ambarv); }
.db-stock li:first-child { border-top: 0; padding-top: 2px; }
.db-stock li.s-agotado { --sc: var(--rosa); --sv: var(--rosav); }
.db-stock li.s-minimo { --sc: #d9a24a; }
.db-st-n { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; min-width: 0; }
.db-st-n b { flex: 1; min-width: 0; font-size: 13px; font-weight: 600; line-height: 1.3; color: var(--tinta); text-transform: uppercase; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow-wrap: anywhere; }
.db-st-n span { flex: none; padding: 1px 8px; border-radius: 99px; background: var(--sv); color: var(--sc); font-size: 11.5px; font-weight: 650; white-space: nowrap; }
.db-stock li.s-minimo .db-st-n span { color: var(--ambar); }
.db-st-b { position: relative; height: 6px; margin: 8px 0 5px; border-radius: 99px; background: var(--linea2); }
.db-st-b i { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 99px; background: var(--sc); transition: width .45s var(--e); }
.db-st-b s { position: absolute; top: -3px; bottom: -3px; width: 2px; margin-left: -1px; border-radius: 2px; background: var(--tinta); opacity: .55; text-decoration: none; }
.db-stock li small { display: block; font-size: 12px; color: var(--media); }
.db-stock li small b { font-weight: 600; color: var(--tinta); }
.db-stock-mas { margin-top: 6px; font-size: 12.5px; color: var(--media); }

/* Galería 3D del inventario */
.db-gal { display: grid; gap: 10px; }
.db-gal-tabs { display: flex; gap: 2px; padding: 3px; border-radius: 11px; background: var(--niebla); justify-self: start; }
.db-gal-tabs button { height: 28px; padding: 0 11px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; font: 600 12.5px var(--ui); color: var(--media); background: none; }
.db-gal-tabs button b { font-weight: 650; font-size: 11.5px; color: var(--media); }
.db-gal-tabs button[aria-pressed=true] { background: var(--papel); color: var(--tinta); box-shadow: 0 1px 3px rgba(11, 30, 28, .14); }
.db-gal-esc { position: relative; height: 210px; border-radius: 14px; overflow: hidden; perspective: 760px; perspective-origin: 50% 40%; touch-action: pan-y; cursor: grab; user-select: none;
    background: radial-gradient(85% 75% at 50% 28%, #134a45 0%, #0a2826 50%, #040d0c 100%); outline-offset: -3px; }
.db-gal-esc.agarrando { cursor: grabbing; }
.db-gal-luz { position: absolute; left: 50%; top: -20px; width: 300px; height: 210px; margin-left: -150px; pointer-events: none;
    background: linear-gradient(180deg, rgba(196, 255, 244, .3), rgba(166, 239, 226, .04) 78%, transparent); clip-path: polygon(40% 0, 60% 0, 100% 100%, 0 100%); filter: blur(14px); }
.db-gal-piso { position: absolute; left: 0; right: 0; top: 70%; bottom: 0; pointer-events: none; background: linear-gradient(180deg, rgba(166, 239, 226, .07), rgba(0, 0, 0, .5)); border-top: 1px solid rgba(166, 239, 226, .12); }
.db-gal-it { position: absolute; left: 50%; top: 46%; width: 0; height: 0; transform-style: preserve-3d; transition: transform .45s var(--e), opacity .45s var(--e); cursor: pointer; }
.db-gal-it.centro { cursor: grab; }
.db-gal-it .iv-caja { position: absolute; left: 0; top: 0; margin: calc(var(--h) / -2) 0 0 calc(var(--w) / -2); }
.db-gal-it:not(.centro) .iv-cara:after { opacity: calc(var(--s, 0) + .12); }
.db-gal-fl { position: absolute; top: 50%; z-index: 20; width: 32px; height: 32px; margin-top: -16px; border-radius: 50%; display: grid; place-items: center; background: rgba(4, 22, 20, .55); color: #eafaf6; backdrop-filter: blur(6px); transition: background .15s, opacity .15s; }
.db-gal-fl:hover:not(:disabled) { background: rgba(255, 255, 255, .18); }
.db-gal-fl:disabled { opacity: .25; cursor: default; }
.db-gal-fl.izq { left: 8px; } .db-gal-fl.der { right: 8px; }
.db-gal-n { position: absolute; right: 12px; bottom: 9px; z-index: 20; font: 600 11px var(--mono); letter-spacing: .08em; color: rgba(166, 239, 226, .75); }
.db-gal-info { --sc: var(--verde); --sv: var(--verdev); min-width: 0; }
.db-gal-info.s-bajo { --sc: #c98a1e; --sv: var(--ambarv); }
.db-gal-info.s-agotado { --sc: var(--rosa); --sv: var(--rosav); }
.db-gal-cat { display: flex; align-items: center; gap: 7px; font: 600 10.5px var(--mono); letter-spacing: .1em; text-transform: uppercase; color: var(--media); }
.db-gal-cat i { width: 8px; height: 8px; border-radius: 2px; }
.db-gal-cat em { margin-left: auto; font-style: normal; padding: 1px 7px; border-radius: 99px; background: var(--niebla); color: var(--texto); }
.db-gal-nom { display: block; margin-top: 3px; font: 700 16px/1.2 var(--disp); color: var(--tinta); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.db-gal-info .db-st-b { margin: 8px 0 6px; }
.db-gal-dato { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 12px; color: var(--media); }
.db-gal-dato b { font-weight: 600; color: var(--tinta); }
.db-gal-tag { flex: none; padding: 1px 8px; border-radius: 99px; background: var(--sv); color: var(--sc); font-size: 11.5px; font-weight: 650; }
.db-gal-info.s-bajo .db-gal-tag { color: var(--ambar); }
.db-gal-pie { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px; }
.db-gal-puntos { display: flex; flex-wrap: wrap; gap: 4px; min-width: 0; }
.db-gal-puntos i { width: 6px; height: 6px; border-radius: 50%; background: var(--linea); cursor: pointer; transition: background .15s, width .2s; }
.db-gal-puntos i.on { width: 16px; border-radius: 3px; background: var(--teal); }

/* Médicos de hoy */
.db-meds > header { flex-wrap: wrap; }
.db-meds .db-ley { font-size: 12px; }
.db .db-ley .m-at:before, .db-med-b .m-at { background: var(--cred); }
.db .db-ley .m-cu:before, .db-med-b .m-cu { background: #3fbfae; }
.db .db-ley .m-pa:before, .db-med-b .m-pa { background: #d9b06a; }
.db-med-l { display: grid; gap: 12px; }
.db-med-l li { display: flex; align-items: center; gap: 12px; min-width: 0; }
.db-med-ros { flex: none; width: 34px; height: 34px; }
.db-med-ros svg { width: 34px; height: 34px; }
.db-med-l li > div { flex: 1; min-width: 0; }
.db-med-l p { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 5px; }
.db-med-l p b { font-size: 13.5px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-med-l p span { flex: none; font: 500 12px var(--mono); color: var(--media); }
.db-med-b { display: flex; gap: 2px; height: 10px; min-width: 12px; border-radius: 4px; overflow: hidden; }
.db-med-b i { flex-basis: 0; min-width: 4px; transition: flex-grow .45s var(--e); }

/* Hoy en la clínica (fichas del encabezado) */
.db-mini { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--linea); }
.db-mini li { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--media); }
.db-mini li > i { width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; font-style: normal; font-size: 13px; }
.db-mini b { font: 700 17px var(--disp); color: var(--tinta); }

/* ---------- Gráficas que llenan la columna ---------- */
.db-graf { display: flex; flex-direction: column; }
.db-graf .db-plot { flex: 1 0 230px; height: auto; min-height: 230px; }
.db-tipos { display: flex; flex-direction: column; }
.db-tipos .db-dona-w { margin-top: auto; }
.db-tipos .db-tipos-l { margin-bottom: auto; }
.db-lista-libre { max-height: none; }
.db-px { display: flex; align-items: center; gap: 12px; padding: 8px; border-radius: 11px; }
.db-k-v em.db-k-em2 { background: #dff3ef; color: var(--teal); }
.db-tipos > header p { font-size: 12.5px; color: var(--media); }
.db-dona-w { position: relative; width: 170px; height: 170px; margin: 6px auto 14px; }
.db-dona { width: 100%; height: 100%; transform: rotate(-90deg); }
.db-dona circle { fill: none; stroke-width: 16; }
.db-dona .pista { stroke: var(--linea2); }
.db-dona .arco { stroke-dasharray: 0 289.03; transition: stroke-dasharray .55s var(--e), stroke-dashoffset .55s var(--e); }
.db-dona .t-seg { stroke: #00897b; } .db-dona .t-pri { stroke: #7a5cc7; } .db-dona .t-urg { stroke: #e0653a; }
.db-dona-c { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.db-dona-c b { font: 750 34px/1 var(--disp); color: var(--tinta); }
.db-dona-c span { font-size: 12.5px; color: var(--media); }
.db-tipos-l li { display: grid; grid-template-columns: 1fr auto 46px; align-items: center; gap: 10px; padding: 8px 0; border-top: 1px solid var(--linea2); font-size: 13.5px; }
.db-tipos-l li span { display: flex; align-items: center; gap: 8px; color: var(--texto); }
.db-tipos-l li span:before { content: ''; width: 10px; height: 10px; border-radius: 3px; background: var(--tc); }
.db-tipos-l li b { font: 700 16px var(--disp); color: var(--tinta); }
.db-tipos-l li em { font: 500 12px var(--mono); font-style: normal; color: var(--media); text-align: right; }
.db-tipos-l .t-seg { --tc: #00897b; } .db-tipos-l .t-pri { --tc: #7a5cc7; } .db-tipos-l .t-urg { --tc: #e0653a; }

/* ---------- Gráfica ---------- */
.db-graf > header { flex-wrap: wrap; align-items: flex-start; margin-bottom: 18px; }
.db-graf > header p { font-size: 12.5px; color: var(--media); }
.db-graf > header .db-ley { margin: 4px 0 0 auto; }
.db-graf .db-seg { margin: 0; }
.db-ley .g-at:before { background: var(--g-at); }
.db-ley .g-pr:before { background: var(--g-pr); }
.db-ley .g-ca:before { background: var(--g-ca); }
.db-plot { position: relative; height: 230px; margin-left: 44px; }
.db-reg { position: absolute; left: 0; right: 0; border-top: 1px dashed var(--linea2); }
.db-reg.base { border-top: 1px solid var(--linea); }
.db-reg span { position: absolute; right: calc(100% + 8px); top: -8px; font: 500 11px var(--mono); color: var(--media); white-space: nowrap; }
.db-barras { position: absolute; inset: 0; display: flex; justify-content: space-around; gap: 10px; }
.db-col { position: relative; flex: 1; max-width: 64px; height: 100%; display: flex; align-items: flex-end; justify-content: center; outline-offset: 4px; transition: opacity .2s; }
.db-col.tenue { opacity: .45; }
.db-pila { width: 100%; max-width: 44px; transition: height .5s var(--e); }
.db-pila-in { height: 100%; display: flex; flex-direction: column-reverse; gap: 2px; border-radius: 4px 4px 0 0; overflow: hidden; }
.db-pila-in i { flex-basis: 0; min-height: 2px; }
.db-pila-in .g-at { background: var(--g-at); }
.db-pila-in .g-pr { background: var(--g-pr); }
.db-pila-in .g-ca { background: var(--g-ca); }
.db-tot { position: absolute; font: 600 12px var(--mono); color: var(--texto); transition: bottom .5s var(--e); }
.db-tt { position: absolute; bottom: calc(100% - 40px); left: calc(50% + 28px); z-index: 4; min-width: 150px; padding: 9px 11px; border-radius: 10px; background: var(--tinta); color: #fff; font-size: 12.5px; box-shadow: 0 14px 28px -16px rgba(11, 30, 28, .7); pointer-events: none; }
.db-tt.izq { left: auto; right: calc(50% + 28px); }
.db-tt p { font-weight: 700; margin-bottom: 4px; }
.db-tt span { display: flex; align-items: center; gap: 6px; color: #d4e4e1; }
.db-tt span:before { content: ''; width: 8px; height: 8px; border-radius: 2px; background: var(--g); }
.db-tt span b { margin-left: auto; padding-left: 14px; color: #fff; font: 600 12.5px var(--mono); }
.db-tt .g-at { --g: #3fbfae; } .db-tt .g-pr { --g: var(--g-pr); } .db-tt .g-ca { --g: #ec7d8f; }
.db-sin { position: absolute; inset: 0; display: grid; place-items: center; font-size: 13px; color: var(--media); }
.db-meses { display: flex; justify-content: space-around; gap: 10px; margin: 8px 0 0 44px; }
.db-meses span { flex: 1; max-width: 64px; text-align: center; font-size: 12.5px; color: var(--media); }
.db-meses span.hoy { color: var(--cred); font-weight: 700; }

/* ---------- Aparición ---------- */
.db-aparece { animation: dbAparece .3s var(--e) both; }
@keyframes dbAparece { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

/* ---------- Pantallas angostas ---------- */
@container (max-width: 1099px) {
    .db-ind { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@container (max-width: 699px) {
    .db-hoy { grid-template-columns: minmax(0, 1fr); }
    .db-g > b { font-size: 64px; }
    .db-sync { margin-left: 0; }
    .db-ficha-d { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .db-ficha-p h2 { font-size: 23px; }
    .db-pulso { overflow-x: auto; }
    .db-cinta { min-width: 640px; }
    .db-plot, .db-meses { margin-left: 38px; }
    .db-barras, .db-meses { gap: 6px; }
}
@container (max-width: 459px) {
    .db-ind { grid-template-columns: minmax(0, 1fr); }
    .db-ecg { display: none; }
    .db-chip { display: none; }
    .db-acc a { flex: 1 1 auto; justify-content: center; }
    .db-salas { grid-template-columns: minmax(0, 1fr); }
}
@media (prefers-reduced-motion: reduce) {
    .db-aparece, .db-sync .gira, .db-ficha.e-en-curso .db-ficha-k span:before, .db-ecg .vivo { animation: none !important; }
    .db-anillo .valor, .db-dona .arco, .db-k-seg i, .db-med-b i, .db-barras7 i { transition: none; }
    .db-pila, .db-tot, .db-barra i, .db-stock li > i > i { transition: none; }
}
</style>

<script>
function modDash() {
    const vacio = {
        generado: '--:--', hoy: new Date().toISOString().slice(0, 10), miPersonalId: null, urlDatos: null,
        puede: {}, urls: {},
        kpis: {
            pacientesHoy: { valor: 0, anterior: 0, delta: null, serie: [] },
            pendientes: { valor: 0, hoy: 0, confirmadas: 0 },
            consultasMes: { valor: 0, anterior: 0, delta: null },
            ocupacion: { total: 0, ocupados: 0, disponibles: 0, mantenimiento: 0, porcentaje: 0 },
        },
        agenda: [],
        resumenHoy: { total: 0, finalizadas: 0, enCurso: 0, canceladas: 0, porAtender: 0 },
        actividad: { recetas: 0, pacientesNuevos: 0 },
        proximas: [],
        consultorios: [],
        diario: [],
        medicosHoy: [],
        tiposMes: { 'Seguimiento': 0, 'Primera Vez': 0, 'Urgencia': 0 },
        grafica: { meses: [], hayIngresos: false },
        stock: { disponible: false, total: 0, agotados: 0, items: [], galeria: [], productos: 0 },
    };
    const fusionar = (extra) => {
        const x = extra || {};
        const r = Object.assign({}, vacio, x);
        r.kpis = Object.assign({}, vacio.kpis, x.kpis || {});
        r.resumenHoy = Object.assign({}, vacio.resumenHoy, x.resumenHoy || {});
        r.actividad = Object.assign({}, vacio.actividad, x.actividad || {});
        r.stock = Object.assign({}, vacio.stock, x.stock || {});
        r.grafica = Object.assign({}, vacio.grafica, x.grafica || {});
        r.puede = x.puede || {}; r.urls = x.urls || {};
        r.tiposMes = Object.assign({}, vacio.tiposMes, x.tiposMes || {});
        if (!r.urlDatos && vacio.urlDatos) r.urlDatos = vacio.urlDatos;
        return r;
    };
    const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const ETIQUETAS = { 'Pendiente': 'Pendiente', 'Confirmada': 'Confirmada', 'En curso': 'En consulta', 'Finalizada': 'Atendida', 'Cancelada': 'Cancelada' };
    const aMin = (h) => { const p = String(h || '').split(':'); return p.length > 1 ? Number(p[0]) * 60 + Number(p[1]) : null; };

    let inicial = {};
    try { inicial = JSON.parse(document.getElementById('db-datos').textContent); } catch (e) {}
    vacio.urlDatos = inicial.urlDatos || null;

    return {
        d: fusionar(inicial),
        cargando: false,
        error: false,
        montado: false,
        sel: null,
        filtro: 'todas',
        barra: null,
        ahora: new Date(),
        anim: { pacientes: 0, pendientes: 0, consultas: 0, ocupacion: 0 },
        galVista: 'surtir',
        galI: 0,
        LUZ_LADO: window.MTCaja ? MTCaja.luces(-12, -32) : '',
        TIPOS: [
            { k: 'Seguimiento', t: 'Seguimiento', c: 't-seg' },
            { k: 'Primera Vez', t: 'Primera vez', c: 't-pri' },
            { k: 'Urgencia', t: 'Urgencia', c: 't-urg' },
        ],
        filtros: [
            { k: 'todas', t: 'Todas' },
            { k: 'porAtender', t: 'Por atender' },
            { k: 'En curso', t: 'En consulta' },
            { k: 'Finalizada', t: 'Atendidas' },
            { k: 'mias', t: 'Mías' },
        ],

        get k() { return this.d.kpis; },
        get r() { return this.d.resumenHoy; },

        init() {
            requestAnimationFrame(() => requestAnimationFrame(() => { this.montado = true; }));
            if (!this.d.stock.total) this.galVista = 'todo';
            this.galTornamesa();
            this.contar0();
            setInterval(() => { this.ahora = new Date(); }, 30000);
            if (this.d.urlDatos) {
                setInterval(() => { if (!document.hidden) this.refrescar(false); }, 60000);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) this.refrescar(false); });
            }
        },

        async refrescar(manual) {
            if (!this.d.urlDatos || this.cargando) return;
            this.cargando = true;
            try {
                const res = await fetch(this.d.urlDatos, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const antes = this.galActual ? this.galActual.id : null;
                this.d = fusionar(await res.json());
                if (antes) { const i = this.galeria.findIndex(p => p.id === antes); this.galI = i >= 0 ? i : Math.min(this.galI, Math.max(0, this.galeria.length - 1)); }
                this.contar0();
                if (this.sel) this.sel = this.d.agenda.find(c => c.id === this.sel.id) || null;
                this.error = false;
                if (manual && window.notificar) window.notificar('Panel actualizado', 'success');
            } catch (e) {
                this.error = true;
                if (manual && window.notificar) window.notificar('No se pudo actualizar el panel', 'error');
            } finally {
                this.cargando = false;
            }
        },

        // ----- Números que suben al cargar -----
        contar0() {
            const destino = { pacientes: this.k.pacientesHoy.valor, pendientes: this.k.pendientes.valor, consultas: this.k.consultasMes.valor, ocupacion: this.k.ocupacion.porcentaje };
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.anim = destino; return; }
            const inicio = Object.assign({}, this.anim), t0 = performance.now(), dur = 600;
            const paso = (t) => {
                const p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3), n = {};
                for (const c in destino) n[c] = inicio[c] + (destino[c] - inicio[c]) * e;
                this.anim = n;
                if (p < 1) requestAnimationFrame(paso);
            };
            requestAnimationFrame(paso);
        },

        // ----- Mini gráficas -----
        get area() {
            const s = this.d.diario.map(p => Number(p.valor) || 0);
            if (s.length < 2) s.unshift(0);
            const max = Math.max(1, ...s), w = 200, h = 44, n = s.length - 1;
            const pts = s.map((v, i) => [(i / n * w).toFixed(1), (h - 3 - v / max * (h - 8)).toFixed(1)]);
            const linea = 'M' + pts.map(p => p.join(',')).join(' L');
            return { linea, relleno: linea + ' L' + w + ',' + h + ' L0,' + h + ' Z' };
        },
        get maxMedico() { return Math.max(1, ...this.d.medicosHoy.map(m => m.total)); },
        get totalTipos() { return this.TIPOS.reduce((a, t) => a + (Number(this.d.tiposMes[t.k]) || 0), 0); },
        pctTipo(k) { return this.totalTipos ? Math.round((this.d.tiposMes[k] || 0) / this.totalTipos * 100) : 0; },
        arco(i) {
            const C = 289.03, tot = this.totalTipos, gap = 3;
            if (!tot || !this.montado) return 'stroke-dasharray: 0 ' + C;
            let antes = 0;
            for (let j = 0; j < i; j++) antes += Number(this.d.tiposMes[this.TIPOS[j].k]) || 0;
            const v = Number(this.d.tiposMes[this.TIPOS[i].k]) || 0;
            const largo = v / tot * C, visible = Math.max(0, largo - (v < tot ? gap : 0));
            return 'stroke-dasharray: ' + visible.toFixed(2) + ' ' + C + '; stroke-dashoffset: ' + (-(antes / tot * C)).toFixed(2);
        },

        // ----- Fecha y hora -----
        diaSemana(f) { return DIAS[new Date(f + 'T12:00:00').getDay()]; },
        mesAnio(f) { const m = MESES[Number(f.slice(5, 7)) - 1] || ''; return m.charAt(0).toUpperCase() + m.slice(1) + ' ' + f.slice(0, 4); },
        get horaActual() { return String(this.ahora.getHours()).padStart(2, '0') + ':' + String(this.ahora.getMinutes()).padStart(2, '0'); },
        get minAhora() { return this.ahora.getHours() * 60 + this.ahora.getMinutes(); },

        // ----- Indicadores -----
        altoSpark(v) { const max = Math.max(1, ...this.k.pacientesHoy.serie.map(p => p.valor)); return Math.max(8, v / max * 74); },
        textoDelta(delta, contra) {
            if (delta === null || delta === undefined) return 'Sin datos para comparar';
            if (delta === 0) return 'Igual ' + contra.replace('vs. ', 'que ');
            return (delta > 0 ? '▲ ' : '▼ ') + Math.abs(delta) + '% ' + contra;
        },
        claseDelta(delta) { return delta > 0 ? 'sube' : (delta < 0 ? 'baja' : ''); },

        // ----- Cinta del día -----
        get cinta() {
            const citas = this.d.agenda.filter(c => aMin(c.hora) !== null);
            let desde = 8 * 60, hasta = 20 * 60;
            citas.forEach(c => { const i = aMin(c.hora); desde = Math.min(desde, Math.floor(i / 60) * 60); hasta = Math.max(hasta, Math.ceil((i + (c.duracion || 30)) / 60) * 60); });
            const fines = [];
            const bloques = citas.map(c => ({ c, ini: aMin(c.hora), fin: aMin(c.hora) + (c.duracion || 30) }))
                .sort((a, b) => a.ini - b.ini)
                .map(b => {
                    let carril = fines.findIndex(f => f <= b.ini);
                    if (carril === -1) { carril = fines.length; fines.push(0); }
                    fines[carril] = b.fin;
                    return Object.assign(b, { carril });
                });
            const horas = [];
            for (let h = desde / 60; h <= hasta / 60; h++) horas.push(h);
            return { desde, hasta, bloques, horas, carriles: Math.max(1, fines.length) };
        },
        pos(min) { const c = this.cinta; return (min - c.desde) / (c.hasta - c.desde) * 100; },
        ancho(b) { const c = this.cinta; return Math.max(0.6, (b.fin - b.ini) / (c.hasta - c.desde) * 100 - 0.25); },

        // ----- Ficha -----
        get siguiente() {
            const por = this.d.agenda.filter(c => c.estado === 'Pendiente' || c.estado === 'Confirmada');
            return por.find(c => aMin(c.fin) >= this.minAhora) || por[0] || this.d.agenda.find(c => c.estado === 'En curso') || null;
        },
        get ficha() { return this.sel || this.siguiente; },
        get tituloFicha() {
            const f = this.ficha;
            if (!f) return '';
            if (f.estado === 'En curso') return 'En consulta ahora';
            if (this.sel) return 'Cita de las ' + f.hora;
            return 'Siguiente paciente';
        },
        get cuentaFicha() {
            const f = this.ficha;
            if (!f) return '';
            const ini = aMin(f.hora), fin = aMin(f.fin);
            if (f.estado === 'Finalizada') return 'Atendida';
            if (f.estado === 'Cancelada') return 'Cancelada';
            if (f.estado === 'En curso') return 'Desde las ' + f.hora;
            const falta = ini - this.minAhora;
            if (falta > 0) return 'En ' + (falta >= 60 ? Math.floor(falta / 60) + ' h ' + (falta % 60 ? falta % 60 + ' min' : '') : falta + ' min');
            if (this.minAhora <= fin) return 'Es su hora';
            return 'Retraso de ' + (-falta) + ' min';
        },
        elegir(c) { this.sel = this.sel && this.sel.id === c.id ? null : c; window.mtRefresca && mtRefresca('.db-ficha-in'); },

        // ----- Inventario: la barra llega a 150% del mínimo y una marca indica el mínimo -----
        // ----- Galería 3D del inventario (la caja la dibuja MTCaja, en el layout) -----
        get galeria() {
            const g = this.d.stock.galeria || [];
            return this.galVista === 'surtir' && this.d.stock.total ? g.filter(p => p.estado !== 'ok') : g;
        },
        get galActual() { return this.galeria[Math.min(this.galI, this.galeria.length - 1)] || null; },
        cajaDe(p) { return window.MTCaja ? MTCaja.cajaHTML(p) : ''; },
        tipoClase(p) { return window.MTCaja ? MTCaja.tipoDe(p).clase : 'medicamento'; },
        esInsumo(p) { return window.MTCaja ? MTCaja.tipoDe(p).insumo : false; },
        colorCaja(p) { return window.MTCaja ? MTCaja.colorDe(p) : '#0b5a54'; },
        titulo(t) { return window.MTCaja ? MTCaja.titulo(t) : t; },
        anchoGal(p) { const tope = Math.max(1, p.minimo * 3, p.disponible); return this.montado ? (p.disponible <= 0 ? 0 : Math.max(3, Math.min(100, p.disponible / tope * 100))) : 0; },
        marcaGal(p) { return Math.min(100, p.minimo / Math.max(1, p.minimo * 3, p.disponible) * 100); },
        galPos(i) {
            const k = i - this.galI, a = Math.abs(k);
            if (a > 2) return 'opacity:0;pointer-events:none;transform:translateX(' + (k > 0 ? 260 : -260) + 'px) translateZ(-320px) scale(.4)';
            const x = k * 104, z = -a * 150, ry = -k * 32, sc = a ? .46 : .6;
            return 'z-index:' + (10 - a) + ';opacity:' + (a === 2 ? .45 : 1) + ';transform:translateX(' + x + 'px) translateZ(' + z + 'px) rotateY(' + ry + 'deg) scale(' + sc + ')';
        },
        galIr(i) {
            const n = this.galeria.length;
            if (!n) return;
            this.galI = Math.max(0, Math.min(n - 1, i));
            this._galRy = -32;
            this._galDesde = -32 - 160;  // la caja del centro llega girando
        },
        galMover(paso) { this.galIr(this.galI + paso); },
        galCambiar(v) { this.galVista = v; this.galI = 0; this._galRy = -32; },
        galClic(i) { if (this._galArrastre) return; if (i !== this.galI) this.galIr(i); },
        galAgarrar(e) {
            if (e.button !== 0) return;
            const el = this.$refs.galEsc, x0 = e.clientX;
            let rx0 = this._galRy || -32, mov = 0;
            this._galArrastre = false;
            el.classList.add('agarrando');
            const mover = ev => {
                mov = ev.clientX - x0;
                if (Math.abs(mov) > 6) this._galArrastre = true;
                // Arrastrar sobre la caja del centro la gira; al soltar decide si cambia de producto
                this._galRy = rx0 + mov * .6;
                this._galMano = true;
            };
            const soltar = () => {
                el.removeEventListener('pointermove', mover); el.removeEventListener('pointerup', soltar); el.removeEventListener('pointercancel', soltar);
                el.classList.remove('agarrando');
                this._galMano = false;
                if (Math.abs(mov) > 70) { this._galRy = rx0; this.galMover(mov < 0 ? 1 : -1); }
                setTimeout(() => { this._galArrastre = false; }, 0);
            };
            el.addEventListener('pointermove', mover); el.addEventListener('pointerup', soltar); el.addEventListener('pointercancel', soltar);
        },
        // La caja del centro gira despacio; se acomoda suave cuando llega una nueva
        galTornamesa() {
            const quieto = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            this._galRy = -32;
            let actual = -32, visible = true, ultimo = 0;
            this.$nextTick(() => {
                const esc = this.$refs.galEsc;
                if (esc && 'IntersectionObserver' in window) new IntersectionObserver(e => { visible = e[0].isIntersecting; }).observe(esc);
            });
            const paso = t => {
                const dt = Math.min(48, t - (ultimo || t)); ultimo = t;
                const esc = this.$refs.galEsc;
                if (esc && visible && !document.hidden && window.MTCaja) {
                    if (this._galDesde !== undefined && this._galDesde !== null) { actual = quieto ? this._galRy : this._galDesde; this._galDesde = null; }
                    if (!quieto && !this._galMano) this._galRy += dt * .018;
                    actual += (this._galRy - actual) * (quieto ? 1 : .1);
                    const caja = esc.querySelector('.db-gal-it.centro .iv-caja');
                    if (caja) MTCaja.girar(caja, -14, actual);
                }
                requestAnimationFrame(paso);
            };
            requestAnimationFrame(paso);
        },
        nivelStock(p) { return p.disponible <= 0 ? 'agotado' : (p.disponible < p.minimo ? 'bajo' : 'minimo'); },
        anchoStock(p) { const tope = Math.max(1, p.minimo * 1.5); return this.montado ? Math.max(p.disponible > 0 ? 3 : 0, Math.min(100, p.disponible / tope * 100)) : 0; },
        marcaStock(p) { return Math.min(100, p.minimo / Math.max(1, p.minimo * 1.5) * 100); },

        // ----- Consultorios -----
        get salasActivas() { return this.d.consultorios.filter(s => s.estado !== 'mantenimiento'); },
        get salasMant() { return this.d.consultorios.filter(s => s.estado === 'mantenimiento'); },

        // ----- Agenda -----
        get hayMias() { return !!this.d.miPersonalId && this.d.agenda.some(c => String(c.personal_id) === String(this.d.miPersonalId)); },
        coincide(c, f) {
            if (f === 'todas') return true;
            if (f === 'porAtender') return c.estado === 'Pendiente' || c.estado === 'Confirmada';
            if (f === 'mias') return String(c.personal_id) === String(this.d.miPersonalId);
            return c.estado === f;
        },
        contar(f) { return this.d.agenda.filter(c => this.coincide(c, f)).length; },
        get agendaFiltrada() { return this.d.agenda.filter(c => this.coincide(c, this.filtro)); },
        get indiceAhora() { const i = this.agendaFiltrada.findIndex(c => c.hora > this.horaActual); return i === -1 ? this.agendaFiltrada.length : i; },
        etiquetaEstado(e) { return ETIQUETAS[e] || e; },
        slug(e) { return String(e || '').toLowerCase().replace(/\s+/g, '-'); },

        // ----- Gráfica -----
        valor(m) { return Number(m.total || 0); },
        get escalaReal() { return Math.max(0, ...this.d.grafica.meses.map(m => this.valor(m))); },
        get escala() {
            const v = this.escalaReal;
            if (v <= 0) return 4;
            const bruto = v / 4, pot = Math.pow(10, Math.floor(Math.log10(bruto))), n = bruto / pot;
            const paso = (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pot;
            return Math.max(4, Math.ceil(paso) * 4);
        },
        alto(m) { return this.valor(m) / this.escala * 100; },

        // ----- Formatos y dibujos -----
        fechaMini(f) { const d = new Date(f + 'T12:00:00'); return d.getDate() + ' ' + ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][d.getMonth()]; },
        fechaCorta(f) {
            const d = new Date(f + 'T12:00:00'), m = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
            return ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'][d.getDay()] + ' ' + d.getDate() + ' ' + m[d.getMonth()];
        },
        roseta(seed, size) { return typeof mtRoseta === 'function' ? mtRoseta(seed, size) : ''; },
        guilloche(seed) { return typeof mtGuilloche === 'function' ? mtGuilloche(seed) : ''; },
    };
}
</script>
@endverbatim
@endsection
