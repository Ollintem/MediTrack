{{--
    HOJA DE CONSULTA · MediTrack
    Ficha del paciente, signos vitales con rangos, exploración por sistemas y diagnóstico.
    Se habilita al iniciar la consulta; guarda borrador sola y al finalizar ofrece emitir la receta.
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
            <!-- Credencial del paciente -->
            <header class="cs-pac" :class="'e-' + slug(sel.estado)">
                <span class="cs-gl" x-html="guilloche(sel.paciente_nombre + sel.cita_id)"></span>
                <div class="cs-pac-a">
                    <p class="cs-k">
                        <span x-text="'Cita · #' + String(sel.cita_id).padStart(4, '0')"></span>
                        <span class="t" x-text="sel.hora + '–' + sel.hora_fin"></span>
                        <span x-text="(sel.consultorio_nombre || 'Sin consultorio') + (sel.consultorio_piso ? ' · Piso ' + sel.consultorio_piso : '')"></span>
                    </p>
                    <h2 x-text="sel.paciente_nombre"></h2>
                    <p class="cs-pac-d">
                        <span x-show="pac.edad !== null && pac.edad !== undefined" x-text="pac.edad + ' años'"></span>
                        <span x-show="pac.sexo" x-text="pac.sexo"></span>
                        <span x-show="pac.sangre" x-text="'Sangre ' + pac.sangre"></span>
                        <span class="t" x-show="pac.curp" x-text="pac.curp"></span>
                        <span class="t" x-show="pac.telefono" x-text="pac.telefono"></span>
                    </p>
                    <div class="cs-alg">
                        <template x-for="a in (pac.alergias || [])" :key="'al' + a"><span><i class="bi bi-exclamation-triangle-fill"></i><span x-text="a"></span></span></template>
                        <em x-show="cargado && !(pac.alergias || []).length">Sin alergias registradas</em>
                    </div>
                </div>
                <div class="cs-pac-b">
                    <span class="cs-estado" x-text="etiquetaEstado(sel.estado)"></span>
                    <p class="cs-crono t" x-show="sel.estado === 'En curso'" x-text="cronometro"></p>
                    <p class="cs-motivo"><b x-text="sel.tipo_consulta"></b><span x-text="sel.motivo || 'Sin motivo registrado'"></span></p>
                </div>
            </header>

            <!-- Antes de iniciar -->
            <div class="cs-iniciar" x-show="['Pendiente', 'Confirmada'].includes(sel.estado)">
                <div>
                    <b x-text="sel.fecha === hoy ? (faltan(sel) > 0 ? 'Empieza en ' + duracionTexto(faltan(sel)) : 'Es su hora') : 'Cita del ' + fechaLarga(sel.fecha)"></b>
                    <p>Al iniciar, el consultorio figura como ocupado y la hoja se habilita.</p>
                </div>
                <button type="button" class="cs-btn cs-prim" @click="iniciar()" :disabled="ocupado || sel.fecha !== hoy" x-text="sel.fecha === hoy ? 'Iniciar consulta' : 'Solo el día de la cita'"></button>
            </div>
            <div class="cs-aviso" x-show="sel.estado === 'Cancelada'"><i class="bi bi-x-circle"></i> Esta cita se canceló; no tiene consulta.</div>
            <div class="cs-aviso cs-aviso-ok" x-show="sel.estado === 'Finalizada'"><i class="bi bi-check2-circle"></i> Consulta finalizada · solo lectura.</div>

            <div class="cs-grid" x-show="sel.estado !== 'Cancelada'">
                <div class="cs-form" :class="{ 'bloqueado': !editable && sel.estado !== 'Finalizada' }">
                    <!-- 1 · Signos vitales -->
                    <section class="cs-sec">
                        <h3><span class="t">1</span> Signos vitales <small x-show="signosPrevios" x-text="'Anteriores del ' + fechaCorta(signosPrevios ? signosPrevios.fecha : '')"></small></h3>
                        <div class="cs-signos">
                            <label class="cs-sv cs-sv-pa" :class="'r-' + rango('pa')">
                                <span>Presión arterial</span>
                                <div class="cs-sv-in t">
                                    <input type="text" inputmode="numeric" maxlength="3" x-model="signos.pa_sistolica" :disabled="!editable" :placeholder="editable ? '120' : '—'" aria-label="Presión sistólica" @input="marcar()">
                                    <b>/</b>
                                    <input type="text" inputmode="numeric" maxlength="3" x-model="signos.pa_diastolica" :disabled="!editable" :placeholder="editable ? '80' : '—'" aria-label="Presión diastólica" @input="marcar()">
                                    <em>mmHg</em>
                                </div>
                                <small x-text="leyenda('pa')"></small>
                            </label>
                            <template x-for="sv in SIGNOS" :key="sv.k">
                                <label class="cs-sv" :class="'r-' + rango(sv.k)">
                                    <span x-text="sv.t"></span>
                                    <div class="cs-sv-in t">
                                        <input type="text" :inputmode="sv.dec ? 'decimal' : 'numeric'" maxlength="5" x-model="signos[sv.k]" :disabled="!editable" :placeholder="editable ? sv.ej : '—'" :aria-label="sv.t" @input="marcar()">
                                        <em x-text="sv.u"></em>
                                    </div>
                                    <small x-text="leyenda(sv.k)"></small>
                                </label>
                            </template>
                            <div class="cs-sv cs-sv-imc" :class="'r-' + rango('imc')">
                                <span>IMC</span>
                                <div class="cs-sv-in t"><b class="cs-imc" x-text="imc || '—'"></b></div>
                                <small x-text="leyenda('imc')"></small>
                            </div>
                        </div>
                        <div class="cs-dolor">
                            <span>Dolor</span>
                            <div class="t">
                                <template x-for="n in [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]" :key="'d' + n">
                                    <button type="button" :disabled="!editable" :aria-pressed="(String(signos.nivel_dolor) === String(n)).toString()" :style="'--n:' + n"
                                            @click="signos.nivel_dolor = String(signos.nivel_dolor) === String(n) ? '' : n; marcar()" x-text="n"></button>
                                </template>
                            </div>
                        </div>
                    </section>

                    <!-- 2 · Exploración física -->
                    <section class="cs-sec">
                        <h3><span class="t">2</span> Exploración física <small x-text="sistemasLlenos + ' de ' + SISTEMAS.length + ' sistemas'"></small></h3>
                        <label class="cs-campo">
                            <span>General</span>
                            <textarea rows="2" maxlength="5000" x-model="form.exploracion_fisica" :disabled="!editable" @input="marcar()" :placeholder="editable ? 'Aspecto general, estado de conciencia, hidratación…' : 'Sin registro'"></textarea>
                        </label>
                        <div class="cs-sistemas">
                            <template x-for="s in SISTEMAS" :key="s.k">
                                <div class="cs-sis" :class="{ 'lleno': (form[s.k] || '').trim(), 'normal': (form[s.k] || '').trim() === 'Sin hallazgos' }">
                                    <span x-text="s.t"></span>
                                    <button type="button" :disabled="!editable" @click="form[s.k] = (form[s.k] || '').trim() === 'Sin hallazgos' ? '' : 'Sin hallazgos'; marcar()">Normal</button>
                                    <input type="text" maxlength="2000" x-model="form[s.k]" :disabled="!editable" @input="marcar()" :placeholder="editable ? 'Hallazgos…' : 'Sin registro'" :aria-label="s.t">
                                </div>
                            </template>
                        </div>
                    </section>

                    <!-- 3 · Diagnóstico -->
                    <section class="cs-sec">
                        <h3><span class="t">3</span> Diagnóstico e indicaciones <small class="cs-req" x-show="editable">obligatorio para finalizar</small></h3>
                        <label class="cs-campo" :class="{ 'mal': errorDx }">
                            <textarea rows="5" maxlength="5000" x-ref="dx" x-model="form.notas_generales" :disabled="!editable" @input="marcar(); errorDx = false" :placeholder="editable ? 'Diagnóstico clínico, plan de tratamiento e indicaciones…' : 'Sin registro'"></textarea>
                            <small class="t" x-text="(form.notas_generales || '').length + ' / 5000'"></small>
                        </label>
                    </section>
                </div>

                <!-- Antecedentes e historial -->
                <aside class="cs-rail">
                    <div class="cs-rail-b">
                        <h4>Condiciones</h4>
                        <p x-show="!(pac.condiciones || []).length">Ninguna registrada</p>
                        <ul><template x-for="c in (pac.condiciones || [])" :key="'co' + c"><li x-text="c"></li></template></ul>
                    </div>
                    <div class="cs-rail-b">
                        <h4>Medicamentos actuales</h4>
                        <p x-show="!(pac.medicamentos || []).length">Ninguno registrado</p>
                        <ul><template x-for="m in (pac.medicamentos || [])" :key="'me' + m"><li x-text="m"></li></template></ul>
                    </div>
                    <div class="cs-rail-b">
                        <h4>Consultas anteriores</h4>
                        <p x-show="!historial.length">Es su primera consulta registrada.</p>
                        <template x-for="h in historial" :key="'h' + h.id">
                            <div class="cs-hist">
                                <b class="t" x-text="h.fecha ? fechaCorta(h.fecha) + ' ' + h.fecha.slice(0, 4) : 'Sin fecha'"></b>
                                <small x-text="h.medico"></small>
                                <p x-text="h.resumen || 'Sin notas registradas'"></p>
                            </div>
                        </template>
                    </div>
                    <a class="cs-link" :href="urlPaciente" x-show="pac.id">Abrir su expediente <i class="bi bi-arrow-right"></i></a>
                </aside>
            </div>

            <!-- Barra de acciones -->
            <footer class="cs-pie" x-show="sel.estado === 'En curso' || (sel.estado === 'Finalizada' && recetaUrl)">
                <span class="cs-guardado" x-show="sel.estado === 'En curso'">
                    <i :class="sucio ? 'pend' : 'ok'"></i>
                    <span x-text="guardando ? 'Guardando…' : (sucio ? 'Cambios sin guardar' : (guardadoA ? 'Borrador guardado a las ' + guardadoA : 'Se guarda solo cada 30 s'))"></span>
                </span>
                <button type="button" class="cs-btn cs-amb" x-show="sel.estado === 'En curso'" @click="liberar()" :disabled="ocupado">Liberar consultorio</button>
                <button type="button" class="cs-btn" x-show="sel.estado === 'En curso'" @click="guardar(false)" :disabled="ocupado">Guardar borrador</button>
                <button type="button" class="cs-btn cs-prim" x-show="sel.estado === 'En curso'" @click="guardar(true)" :disabled="ocupado">Finalizar consulta <kbd>Ctrl ↵</kbd></button>
                <a class="cs-btn cs-prim" x-show="sel.estado === 'Finalizada' && recetaUrl" :href="recetaUrl">Ver o emitir receta</a>
            </footer>
        </div>
    </template>
</section>