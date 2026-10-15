{{--
    HOJA DE RECETA · MediTrack
    Frente: la receta elegida tal como se imprime (clínica, paciente, diagnóstico, medicamentos y firma).
    Reverso: el formulario para emitir una receta nueva. Al emitir, la hoja se voltea y ocupa todo el ancho
    (dos columnas: paciente, médico, diagnóstico e indicaciones | medicamentos).
    Lo usa recetas/index.blade.php (comparte su componente Alpine).
--}}
<div class="rx-hoja" :class="{ 'volteada': editando }">
    <div class="rx-hoja-in">

        <!-- FRENTE: receta impresa -->
        <article class="rx-cara rx-papel" :aria-hidden="editando.toString()">
            <template x-if="sel">
                <div class="rx-doc">
                    <header class="rx-doc-h">
                        <div>
                            <b x-text="CLINICA.nombre"></b>
                            <small x-text="[CLINICA.direccion, CLINICA.telefono ? 'Tel. ' + CLINICA.telefono : ''].filter(Boolean).join(' · ')"></small>
                        </div>
                        <p class="t"><span>Receta</span><b x-text="'#' + sel.folio"></b><small x-text="fechaLarga(sel.fecha)"></small></p>
                    </header>
                    <div class="rx-doc-p">
                        <p><span>Paciente</span><b x-text="sel.paciente"></b><em x-show="sel.edad !== null" x-text="sel.edad + ' años'"></em></p>
                        <p class="rx-alg" x-show="sel.alergias.length"><i class="bi bi-exclamation-triangle-fill"></i><span x-text="'Alergias: ' + sel.alergias.join(', ')"></span></p>
                        <p><span>Diagnóstico</span><b class="rx-dx" x-text="sel.diagnostico"></b></p>
                    </div>
                    <div class="rx-doc-rx">
                        <span class="rx-simbolo" aria-hidden="true">℞</span>
                        <ol>
                            <template x-for="(d, i) in sel.detalles" :key="'d' + i">
                                <li>
                                    <b x-text="d.medicamento"></b>
                                    <span x-text="[d.dosis, d.frecuencia, d.duracion].filter(Boolean).join(' · ') || 'Sin indicación de dosis'"></span>
                                </li>
                            </template>
                        </ol>
                    </div>
                    <p class="rx-doc-ind" x-show="sel.indicaciones"><span>Indicaciones</span><span x-text="sel.indicaciones"></span></p>
                    <footer class="rx-doc-f">
                        <i></i>
                        <b x-text="sel.medico ? 'Dr(a). ' + sel.medico : 'Médico tratante'"></b>
                        <small x-text="sel.cedula ? 'Cédula ' + sel.cedula : 'Firma y sello'"></small>
                    </footer>
                </div>
            </template>
            <template x-if="!sel">
                <div class="rx-doc rx-doc-vacio">
                    <span class="rx-simbolo" aria-hidden="true">℞</span>
                    <b>Sin receta seleccionada</b>
                    <p>Elige una receta de la lista o emite una nueva.</p>
                </div>
            </template>
        </article>

        <!-- REVERSO: emitir receta -->
        <form class="rx-cara rx-form" @submit.prevent="emitir()" novalidate :aria-hidden="(!editando).toString()">
            <p class="rx-form-k" x-text="form.consulta_id ? 'Receta de la consulta · ' + (PREFILL ? PREFILL.fecha : '') : 'Receta nueva'"></p>

            <div class="rx-form-c">
                <!-- Columna A: paciente, médico, diagnóstico e indicaciones -->
                <div class="rx-form-a">
                    <!-- Resumen de la consulta (si viene de una) -->
                    <div class="rx-cons" x-show="form.consulta_id && PREFILL && PREFILL.resumen && PREFILL.resumen.length">
                        <button type="button" @click="verResumen = !verResumen">Exploración de la consulta <i class="bi bi-chevron-down" :class="{ 'gira': verResumen }"></i></button>
                        <div x-show="verResumen">
                            <template x-for="r in (PREFILL ? PREFILL.resumen : [])" :key="'r' + r.etiqueta"><p><b x-text="r.etiqueta + ':'"></b> <span x-text="r.texto"></span></p></template>
                        </div>
                    </div>

                    <!-- Paciente -->
                    <div class="rx-campo" :class="{ 'mal': errores.paciente_id }" @click.outside="bPac.abierto = false">
                        <span>Paciente</span>
                        <input type="text" x-ref="campoPaciente" x-model="bPac.q" autocomplete="off" :disabled="!!form.consulta_id"
                               :placeholder="form.paciente_id ? nombrePaciente(form.paciente_id) : 'Busca por nombre o CURP'" :class="{ 'elegido': form.paciente_id && !bPac.q }"
                               @focus="bPac.abierto = true" @input="bPac.abierto = true; errores.paciente_id = ''"
                               @keydown.arrow-down.prevent="bPac.i = Math.min(bPac.i + 1, pacientesFiltrados.length - 1)" @keydown.arrow-up.prevent="bPac.i = Math.max(bPac.i - 1, 0)"
                               @keydown.enter.prevent="pacientesFiltrados[bPac.i] && elegirPaciente(pacientesFiltrados[bPac.i])" :tabindex="editando ? 0 : -1">
                        <div class="rx-lista" x-show="bPac.abierto && pacientesFiltrados.length" x-cloak>
                            <template x-for="(p, i) in pacientesFiltrados" :key="'p' + p.id">
                                <button type="button" :class="{ 'on': i === bPac.i }" @mousemove="bPac.i = i" @click="elegirPaciente(p)">
                                    <b x-text="p.nombre"></b><small class="t" x-text="p.curp || 'Sin CURP'"></small>
                                    <em x-show="p.alergias.length" x-text="p.alergias[0]"></em>
                                </button>
                            </template>
                        </div>
                        <p class="rx-alg-f" x-show="alergiasForm.length"><i class="bi bi-exclamation-triangle-fill"></i><span x-text="'Alérgico a: ' + alergiasForm.join(', ')"></span></p>
                        <small x-show="errores.paciente_id" x-text="errores.paciente_id"></small>
                    </div>

                    <!-- Médico -->
                    <div class="rx-campo" :class="{ 'mal': errores.personal_id }" @click.outside="popMed = false">
                        <span>Médico</span>
                        <button type="button" class="rx-pick" :class="{ 'abierto': popMed }" @click="popMed = !popMed" :tabindex="editando ? 0 : -1">
                            <span x-text="form.personal_id ? nombreMedico(form.personal_id) : 'Elige al médico'"></span><i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="rx-lista" x-show="popMed" x-cloak>
                            <template x-for="m in MED" :key="'m' + m.id">
                                <button type="button" :class="{ 'on': String(m.id) === String(form.personal_id) }" @click="form.personal_id = String(m.id); popMed = false; errores.personal_id = ''">
                                    <b x-text="m.nombre"></b><small x-text="m.especialidad || 'Médico'"></small>
                                </button>
                            </template>
                        </div>
                        <small x-show="errores.personal_id" x-text="errores.personal_id"></small>
                    </div>

                    <!-- Diagnóstico -->
                    <label class="rx-campo" :class="{ 'mal': errores.diagnostico }">
                        <span>Diagnóstico</span>
                        <textarea rows="2" maxlength="2000" class="may" x-model="form.diagnostico" @input="errores.diagnostico = ''" placeholder="Ej. Faringoamigdalitis aguda" :tabindex="editando ? 0 : -1"></textarea>
                        <small x-show="errores.diagnostico" x-text="errores.diagnostico"></small>
                    </label>

                    <!-- Indicaciones -->
                    <label class="rx-campo">
                        <span>Indicaciones generales <em>(opcional)</em></span>
                        <textarea rows="2" maxlength="3000" class="may" x-model="form.indicaciones_generales" placeholder="Dieta, reposo, cuidados, próxima cita…" :tabindex="editando ? 0 : -1"></textarea>
                    </label>
                </div>

                <!-- Columna B: medicamentos -->
                <div class="rx-form-b">
                    <!-- Medicamentos -->
                    <div class="rx-campo" :class="{ 'mal': errores.medicamentos }">
                        <span>Medicamentos</span>
                        <template x-for="(m, i) in form.medicamentos" :key="m.uid">
                            <div class="rx-med" :class="{ 'alerta': alergiaDe(m.nombre) }">
                                <span class="rx-med-n t" x-text="i + 1"></span>
                                <div class="rx-med-c" @click.outside="m.sug = false">
                                    <input type="text" class="may rx-med-nom" x-model="m.nombre" maxlength="255" placeholder="Medicamento y presentación" autocomplete="off"
                                           @focus="m.sug = true" @input="m.sug = true; errores.medicamentos = ''" @keydown.escape.stop="m.sug = false" :tabindex="editando ? 0 : -1">
                                    <div class="rx-lista rx-sug" x-show="m.sug && sugerencias(m.nombre).length" x-cloak>
                                        <template x-for="s in sugerencias(m.nombre)" :key="'s' + s.nombre">
                                            <button type="button" @click="m.nombre = s.nombre; m.sug = false"><b x-text="s.nombre"></b><small class="t" :class="{ 'agotado': s.stock <= 0 }" x-text="s.stock > 0 ? s.stock + ' en inventario' : 'Sin existencias'"></small></button>
                                        </template>
                                    </div>
                                    <p class="rx-med-alg" x-show="alergiaDe(m.nombre)"><i class="bi bi-exclamation-triangle-fill"></i><span x-text="'Coincide con la alergia a ' + alergiaDe(m.nombre)"></span></p>
                                    <div class="rx-med-d">
                                        <input type="text" class="may" x-model="m.dosis" maxlength="255" placeholder="Dosis (1 tableta)" :tabindex="editando ? 0 : -1">
                                        <input type="text" class="may" x-model="m.frecuencia" maxlength="255" placeholder="Frecuencia" :tabindex="editando ? 0 : -1">
                                        <input type="text" class="may" x-model="m.duracion" maxlength="255" placeholder="Duración" :tabindex="editando ? 0 : -1">
                                    </div>
                                    <div class="rx-rapidos">
                                        <template x-for="f in ['CADA 6 H', 'CADA 8 H', 'CADA 12 H', 'CADA 24 H']" :key="'f' + f">
                                            <button type="button" :class="{ 'on': m.frecuencia === f }" @click="m.frecuencia = f" x-text="f.replace('CADA ', 'c/').toLowerCase()" :tabindex="editando ? 0 : -1"></button>
                                        </template>
                                        <i></i>
                                        <template x-for="d in ['3 DÍAS', '5 DÍAS', '7 DÍAS', '10 DÍAS']" :key="'du' + d">
                                            <button type="button" :class="{ 'on': m.duracion === d }" @click="m.duracion = d" x-text="d.toLowerCase()" :tabindex="editando ? 0 : -1"></button>
                                        </template>
                                    </div>
                                </div>
                                <button type="button" class="rx-med-x" @click="quitarMed(i)" x-show="form.medicamentos.length > 1" aria-label="Quitar medicamento" :tabindex="editando ? 0 : -1"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </template>
                        <button type="button" class="rx-mas" @click="agregarMed()" :tabindex="editando ? 0 : -1"><i class="bi bi-plus-lg"></i> Agregar medicamento</button>
                        <small x-show="errores.medicamentos" x-text="errores.medicamentos"></small>
                    </div>
                </div>
            </div>

            <footer class="rx-form-f">
                <span class="rx-msg" :class="{ 'mal': hayAlertaAlergia }" x-text="hayAlertaAlergia ? 'Revisa: un medicamento coincide con una alergia' : mensajeForm"></span>
                <button type="button" class="rx-btn" @click="cancelar()" :tabindex="editando ? 0 : -1">Cancelar</button>
                <button type="submit" class="rx-btn rx-prim" :disabled="guardando" :tabindex="editando ? 0 : -1" x-text="guardando ? 'Emitiendo…' : 'Emitir receta'"></button>
            </footer>
        </form>
    </div>
</div>

<!-- Acciones de la receta elegida -->
<div class="rx-acc" x-show="!editando && sel">
    <a class="rx-btn rx-prim" :href="sel ? sel.pdf : '#'" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Imprimir / PDF <kbd>P</kbd></a>
    <button type="button" class="rx-btn" @click="duplicar(sel)"><i class="bi bi-copy"></i> Repetir</button>
    <button type="button" class="rx-btn rx-borrar" @click="eliminar(sel)" aria-label="Eliminar receta"><i class="bi bi-trash3"></i></button>
</div>