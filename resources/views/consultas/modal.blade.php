{{--
    HOJA DE CONSULTA · MediTrack
    Ficha del paciente y, al iniciar la consulta, tres pestañas: signos vitales, exploración y diagnóstico.
    Guarda borrador sola y al finalizar ofrece emitir la receta. Finalizada se muestra como resumen.
    Mientras se atiende entra en modo enfoque: se ocultan la fila, la semana y el resumen del día,
    y la hoja ocupa todo el ancho (ficha a la izquierda, pestañas a la derecha).
    Lo usa consultas/index.blade.php (comparte su componente Alpine).
--}}
<section class="cs-hoja" aria-label="Hoja de consulta">

    <!-- Sin paciente elegido -->
    <div class="cs-hoja-vacia" x-show="!sel" x-cloak>
        <svg viewBox="0 0 44 44" aria-hidden="true"><rect x="15" y="2" width="14" height="40" rx="4"/><rect x="2" y="15" width="40" height="14" rx="4"/></svg>
        <b>Elige un paciente de la fila</b>
        <p>Aquí aparecerá su ficha y la hoja de consulta.</p>
    </div>

    <template x-if="sel">
        <div class="cs-hoja-in">
            <!-- Barra del modo enfoque (solo mientras se atiende) -->
            <div class="cs-foco" x-show="sel.estado === 'En curso'">
                <p class="cs-foco-k">Consulta en curso <span class="t" x-text="'· ' + cronometro"></span></p>
                <button type="button" @click="alternarEnfoque()">
                    <i class="bi" :class="enfocado ? 'bi-layout-sidebar' : 'bi-arrows-fullscreen'"></i>
                    <span x-text="enfocado ? 'Ver la fila de pacientes' : 'Modo enfoque'"></span>
                </button>
            </div>

            <!-- Credencial del paciente -->
            <header class="cs-pac">
                <span class="cs-gl" x-html="guilloche(sel.paciente_nombre + sel.cita_id)"></span>
                <div class="cs-pac-a">
                    <p class="cs-k t" x-text="sel.hora + '–' + sel.hora_fin + ' · ' + (sel.consultorio_nombre || 'Sin consultorio') + ' · ' + sel.tipo_consulta"></p>
                    <h2 x-text="sel.paciente_nombre"></h2>
                    <p class="cs-pac-d" x-text="datosPaciente"></p>
                    <p class="cs-alg" x-show="(pac.alergias || []).length"><i class="bi bi-exclamation-triangle-fill"></i><span x-text="'Alergias: ' + (pac.alergias || []).join(', ')"></span></p>
                </div>
                <div class="cs-pac-b">
                    <span class="cs-estado" :class="'e-' + slug(sel.estado)" x-text="etiquetaEstado(sel.estado)"></span>
                    <p class="cs-crono t" x-show="sel.estado === 'En curso'" x-text="cronometro"></p>
                </div>
            </header>

            <!-- Motivo y antecedentes en una línea discreta -->
            <div class="cs-contexto">
                <p><span>Motivo</span><b x-text="sel.motivo || 'Sin motivo registrado'"></b></p>
                <button type="button" class="cs-ant-bt" @click="verAnt = !verAnt" :aria-expanded="verAnt.toString()">
                    Antecedentes<span class="t" x-text="' · ' + historial.length + (historial.length === 1 ? ' consulta previa' : ' consultas previas')"></span>
                    <i class="bi bi-chevron-down" :class="{ 'gira': verAnt }"></i>
                </button>
            </div>
            <div class="cs-ant" x-show="verAnt" x-cloak>
                <div><h4>Condiciones</h4><p x-text="(pac.condiciones || []).join(', ') || 'Ninguna registrada'"></p></div>
                <div><h4>Medicamentos</h4><p x-text="(pac.medicamentos || []).join(', ') || 'Ninguno registrado'"></p></div>
                <div class="cs-ant-h">
                    <h4>Consultas anteriores</h4>
                    <p x-show="!historial.length">Es su primera consulta registrada.</p>
                    <template x-for="h in historial.slice(0, 3)" :key="'h' + h.id">
                        <p><b class="t" x-text="h.fecha ? fechaCorta(h.fecha) + ' ' + h.fecha.slice(0, 4) : 'Sin fecha'"></b> <span x-text="h.resumen || 'Sin notas registradas'"></span></p>
                    </template>
                    <a class="cs-link" :href="urlPaciente" x-show="pac.id">Abrir su expediente <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <!-- Antes de iniciar: solo la llamada a la acción -->
            <div class="cs-iniciar" x-show="['Pendiente', 'Confirmada'].includes(sel.estado)">
                <div>
                    <b x-text="sel.fecha === hoy ? (faltan(sel) > 0 ? 'Empieza en ' + duracionTexto(faltan(sel)) : 'Es su hora') : 'Cita del ' + fechaLarga(sel.fecha)"></b>
                    <p>Al iniciar, el consultorio se marca ocupado y se abre la hoja.</p>
                </div>
                <button type="button" class="cs-btn cs-prim" @click="iniciar()" :disabled="ocupado || sel.fecha !== hoy" x-text="sel.fecha === hoy ? 'Iniciar consulta' : 'Solo el día de la cita'"></button>
            </div>
            <div class="cs-aviso" x-show="sel.estado === 'Cancelada'">Esta cita se canceló; no tiene consulta.</div>

            <!-- En consulta: hoja por pestañas -->
            <div class="cs-papel" x-show="sel.estado === 'En curso'">
                <nav class="cs-tabs" role="tablist">
                    <template x-for="t in PESTANAS" :key="t.k">
                        <button type="button" role="tab" :aria-selected="(tab === t.k).toString()" @click="tab = t.k">
                            <i :class="estadoTab(t.k)"></i><span x-text="t.t"></span>
                        </button>
                    </template>
                </nav>

                <!-- Signos vitales -->
                <div class="cs-panel mt-aparece" x-show="tab === 'sig'">
                    <div class="cs-signos">
                        <label class="cs-sv" :class="'r-' + rango('pa')">
                            <span>Presión</span>
                            <div class="cs-sv-in t">
                                <input type="text" inputmode="numeric" maxlength="3" x-model="signos.pa_sistolica" placeholder="—" aria-label="Presión sistólica" @input="marcar()">
                                <b>/</b>
                                <input type="text" inputmode="numeric" maxlength="3" x-model="signos.pa_diastolica" placeholder="—" aria-label="Presión diastólica" @input="marcar()">
                                <em>mmHg</em>
                            </div>
                            <small x-text="leyenda('pa')"></small>
                        </label>
                        <template x-for="sv in SIGNOS" :key="sv.k">
                            <label class="cs-sv" :class="'r-' + rango(sv.k)">
                                <span x-text="sv.t"></span>
                                <div class="cs-sv-in t">
                                    <input type="text" :inputmode="sv.dec ? 'decimal' : 'numeric'" maxlength="5" x-model="signos[sv.k]" placeholder="—" :aria-label="sv.t" @input="marcar()">
                                    <em x-text="sv.u"></em>
                                </div>
                                <small x-text="leyenda(sv.k)"></small>
                            </label>
                        </template>
                    </div>
                    <div class="cs-fila2">
                        <p class="cs-imc" x-show="imc">IMC <b class="t" x-text="imc"></b> <span x-text="leyenda('imc')"></span></p>
                        <div class="cs-dolor">
                            <span>Dolor</span>
                            <template x-for="n in [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]" :key="'d' + n">
                                <button type="button" class="t" :aria-pressed="(String(signos.nivel_dolor) === String(n)).toString()" :style="'--n:' + n"
                                        @click="signos.nivel_dolor = String(signos.nivel_dolor) === String(n) ? '' : n; marcar()" x-text="n"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Exploración -->
                <div class="cs-panel mt-aparece" x-show="tab === 'exp'" x-cloak>
                    <textarea class="cs-area" rows="2" maxlength="5000" x-model="form.exploracion_fisica" @input="marcar()" placeholder="Exploración general: aspecto, conciencia, hidratación…"></textarea>
                    <div class="cs-sistemas">
                        <template x-for="s in SISTEMAS" :key="s.k">
                            <div class="cs-sis" :class="{ 'normal': esNormal(s.k), 'hallazgo': conHallazgo(s.k) || abiertos.includes(s.k) }">
                                <span x-text="s.t"></span>
                                <div class="cs-sis-bt">
                                    <button type="button" :aria-pressed="esNormal(s.k).toString()" @click="ponerNormal(s.k)">Normal</button>
                                    <button type="button" :aria-pressed="(conHallazgo(s.k) || abiertos.includes(s.k)).toString()" @click="abrirHallazgo(s.k)">Hallazgo</button>
                                </div>
                                <input type="text" maxlength="2000" x-show="conHallazgo(s.k) || abiertos.includes(s.k)" x-model="form[s.k]" @input="marcar()" placeholder="Describe el hallazgo…" :aria-label="'Hallazgo en ' + s.t">
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Diagnóstico -->
                <div class="cs-panel mt-aparece" x-show="tab === 'dx'" x-cloak>
                    <textarea class="cs-area" :class="{ 'mal': errorDx }" rows="7" maxlength="5000" x-ref="dx" x-model="form.notas_generales" @input="marcar(); errorDx = false"
                              placeholder="Diagnóstico, plan de tratamiento e indicaciones (obligatorio para finalizar)"></textarea>
                    <small class="cs-cuenta t" x-text="(form.notas_generales || '').length + ' / 5000'"></small>
                </div>

                <footer class="cs-pie">
                    <span class="cs-guardado"><i :class="sucio ? 'pend' : 'ok'"></i>
                        <span x-text="guardando ? 'Guardando…' : (sucio ? 'Sin guardar' : (guardadoA ? 'Guardado ' + guardadoA : 'Se guarda solo'))"></span></span>
                    <button type="button" class="cs-txt" @click="liberar()" :disabled="ocupado">Liberar consultorio</button>
                    <button type="button" class="cs-btn" @click="guardar(false)" :disabled="ocupado">Guardar</button>
                    <button type="button" class="cs-btn cs-prim" @click="guardar(true)" :disabled="ocupado">Finalizar</button>
                </footer>
            </div>

            <!-- Finalizada: resumen limpio -->
            <div class="cs-resumen" x-show="sel.estado === 'Finalizada'" x-cloak>
                <div class="cs-res-sig" x-show="resumenSignos.length">
                    <template x-for="r in resumenSignos" :key="'rs' + r.t"><span :class="'r-' + r.r"><small x-text="r.t"></small><b class="t" x-text="r.v"></b></span></template>
                </div>
                <div class="cs-res-b" x-show="form.exploracion_fisica || SISTEMAS.some(s => form[s.k])">
                    <h4>Exploración</h4>
                    <p x-show="form.exploracion_fisica" x-text="form.exploracion_fisica"></p>
                    <p x-show="SISTEMAS.some(s => form[s.k])" x-text="SISTEMAS.filter(s => form[s.k]).map(s => s.t + ': ' + form[s.k]).join(' · ')"></p>
                </div>
                <div class="cs-res-b">
                    <h4>Diagnóstico e indicaciones</h4>
                    <p x-text="form.notas_generales || 'Sin registro'"></p>
                </div>
                <a class="cs-btn cs-prim" x-show="recetaUrl" :href="recetaUrl">Ver o emitir receta</a>
            </div>
        </div>
    </template>
</section>
