{{--
    PASE DE LA CITA · MediTrack
    Frente: el pase de la cita seleccionada (hora, paciente, médico, consultorio, motivo y estado).
    Reverso: el formulario para agendarla o editarla. Al editar, el pase se voltea y ocupa todo el ancho
    (dos columnas: quién/dónde/qué y cuándo, con calendario y reloj a la vista).
    Lo usa citas/index.blade.php (comparte su componente Alpine).
--}}
<div class="ci-pase" :class="{ 'volteado': editando }">
    <div class="ci-pase-in">

        <!-- FRENTE -->
        <article class="ci-cara ci-frente" :class="sel ? 'e-' + slug(sel.estado) : 'e-vacio'" :aria-hidden="editando.toString()">
            <span class="ci-gl" x-html="guilloche(sel ? sel.paciente + sel.id : 'AGENDA')"></span>

            <template x-if="sel">
                <div class="ci-fr">
                    <header>
                        <span class="ci-k">Cita · <b class="t" x-text="'#' + String(sel.id).padStart(4, '0')"></b></span>
                        <span class="ci-tipo" x-text="sel.tipo_consulta"></span>
                    </header>
                    <p class="ci-hora t"><span x-text="sel.hora"></span><small x-text="'– ' + horaFin(sel)"></small></p>
                    <p class="ci-fecha" x-text="fechaLarga(sel.fecha) + ' · ' + sel.duracion_min + ' min'"></p>
                    <h2 x-text="sel.paciente"></h2>
                    <p class="ci-alg" x-show="sel.alergia"><i class="bi bi-exclamation-triangle-fill"></i><span x-text="'Alergia: ' + sel.alergia"></span></p>
                    <dl>
                        <div><dt>Médico</dt><dd x-text="nombreMedico(sel.personal_id)"></dd></div>
                        <div><dt>Consultorio</dt><dd x-text="nombreConsultorio(sel.consultorio_id)"></dd></div>
                    </dl>
                    <p class="ci-motivo" x-text="sel.motivo || 'Sin motivo registrado'"></p>
                    <footer class="ci-franja">
                        <i></i><span x-text="franja(sel)"></span>
                    </footer>
                </div>
            </template>

            <template x-if="!sel">
                <div class="ci-fr ci-fr-vacio">
                    <header><span class="ci-k">Agenda</span></header>
                    <h2>Elige una cita</h2>
                    <p>Toca una cita en el tablero para ver su pase, o un espacio libre para agendar ahí.</p>
                </div>
            </template>
        </article>

        <!-- REVERSO: formulario -->
        <form class="ci-cara ci-reverso" @submit.prevent="guardar()" novalidate :aria-hidden="(!editando).toString()">
            <p class="ci-rv-k" x-text="form.id ? 'Editando cita · #' + String(form.id).padStart(4, '0') : 'Agendando cita nueva'"></p>

            <div class="ci-rv-cuerpo">
                <!-- Columna A: quién, dónde y qué -->
                <div class="ci-rv-a">
                    <!-- Paciente (buscador) -->
                    <div class="ci-campo" :class="{ 'mal': errores.paciente_id }" @click.outside="bPac.abierto = false">
                        <span>Paciente</span>
                        <div class="ci-combo">
                            <input type="text" x-ref="campoPaciente" x-model="bPac.q" autocomplete="off"
                                   :placeholder="form.paciente_id ? nombrePaciente(form.paciente_id) : 'Busca por nombre o CURP'"
                                   :class="{ 'elegido': form.paciente_id && !bPac.q }"
                                   @focus="bPac.abierto = true" @input="bPac.abierto = true; errores.paciente_id = ''"
                                   @keydown.arrow-down.prevent="bPac.i = Math.min(bPac.i + 1, pacientesFiltrados.length - 1)"
                                   @keydown.arrow-up.prevent="bPac.i = Math.max(bPac.i - 1, 0)"
                                   @keydown.enter.prevent="pacientesFiltrados[bPac.i] && elegirPaciente(pacientesFiltrados[bPac.i])"
                                   :tabindex="editando ? 0 : -1">
                            <div class="ci-combo-lista" x-show="bPac.abierto && pacientesFiltrados.length" x-cloak>
                                <template x-for="(p, i) in pacientesFiltrados" :key="'pp' + p.id">
                                    <button type="button" :class="{ 'on': i === bPac.i }" @mousemove="bPac.i = i" @click="elegirPaciente(p)">
                                        <b x-text="p.nombre"></b>
                                        <small class="t" x-text="p.curp || 'Sin CURP'"></small>
                                        <em x-show="p.alergia" x-text="p.alergia"></em>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <small x-show="errores.paciente_id" x-text="errores.paciente_id"></small>
                    </div>

                    <!-- Médico (solo doctores) -->
                    <div class="ci-campo" :class="{ 'mal': errores.personal_id }">
                        <span>Médico</span>
                        <button type="button" class="ci-pick" :class="{ 'abierto': pop === 'med', 'vacio': !form.personal_id }" @click="abrirPop('med')" :tabindex="editando ? 0 : -1" :aria-expanded="(pop === 'med').toString()">
                            <span x-text="form.personal_id ? nombreMedico(form.personal_id) : 'Elige al médico'"></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="ci-pop" x-show="pop === 'med'" x-cloak>
                            <template x-for="m in doctores" :key="'pm' + m.id">
                                <button type="button" class="ci-op" :class="{ 'on': String(m.id) === String(form.personal_id) }" @click="form.personal_id = String(m.id); errores.personal_id = ''; pop = ''">
                                    <b x-text="m.nombre"></b>
                                    <small x-text="(m.especialidad || 'Médico') + ' · ' + citasDeMedico(m.id, form.fecha) + ' citas ese día'"></small>
                                </button>
                            </template>
                            <p class="ci-pop-vacio" x-show="!doctores.length">No hay médicos registrados.</p>
                        </div>
                        <small x-show="errores.personal_id" x-text="errores.personal_id"></small>
                    </div>

                    <!-- Consultorio -->
                    <div class="ci-campo" :class="{ 'mal': errores.consultorio_id }">
                        <span>Consultorio</span>
                        <button type="button" class="ci-pick" :class="{ 'abierto': pop === 'con', 'vacio': !form.consultorio_id }" @click="abrirPop('con')" :tabindex="editando ? 0 : -1" :aria-expanded="(pop === 'con').toString()">
                            <span x-text="form.consultorio_id ? nombreConsultorio(form.consultorio_id) : 'Elige el consultorio'"></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="ci-pop ci-pop-rej" x-show="pop === 'con'" x-cloak>
                            <template x-for="c in CON" :key="'pc' + c.id">
                                <button type="button" class="ci-op" :disabled="c.estado === 'Mantenimiento'" :class="{ 'on': String(c.id) === String(form.consultorio_id) }"
                                        @click="form.consultorio_id = String(c.id); errores.consultorio_id = ''; pop = ''">
                                    <b x-text="c.nombre"></b>
                                    <small x-text="(c.piso ? 'Piso ' + c.piso : 'Sin piso') + (c.estado === 'Mantenimiento' ? ' · mantenimiento' : '')"></small>
                                </button>
                            </template>
                        </div>
                        <small x-show="errores.consultorio_id" x-text="errores.consultorio_id"></small>
                    </div>

                    <div class="ci-campo">
                        <span>Duración</span>
                        <div class="ci-chips">
                            <template x-for="d in [15, 30, 45, 60, 90]" :key="'d' + d">
                                <button type="button" class="ci-chip" :class="{ 'on': Number(form.duracion_min) === d }" @click="form.duracion_min = d" x-text="d + ' min'" :tabindex="editando ? 0 : -1"></button>
                            </template>
                        </div>
                    </div>

                    <div class="ci-campo">
                        <span>Tipo de consulta</span>
                        <div class="ci-seg ci-seg-3">
                            <template x-for="t in ['Primera Vez', 'Seguimiento', 'Urgencia']" :key="'t' + t">
                                <button type="button" :aria-pressed="(form.tipo_consulta === t).toString()" @click="form.tipo_consulta = t" x-text="t" :tabindex="editando ? 0 : -1"></button>
                            </template>
                        </div>
                    </div>

                    <div class="ci-campo" x-show="form.id">
                        <span>Estado</span>
                        <div class="ci-seg ci-seg-5">
                            <template x-for="e in ESTADOS" :key="'fe' + e.valor">
                                <button type="button" :class="'e-' + e.slug" :aria-pressed="(form.estado === e.valor).toString()" @click="form.estado = e.valor" :tabindex="editando ? 0 : -1">
                                    <i></i><span x-text="e.corto"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <label class="ci-campo" :class="{ 'mal': errores.motivo }">
                        <span>Motivo de la consulta</span>
                        <input type="text" x-model="form.motivo" maxlength="255" placeholder="¿Qué se va a revisar?" @input="errores.motivo = ''" :tabindex="editando ? 0 : -1">
                        <small x-show="errores.motivo" x-text="errores.motivo"></small>
                    </label>
                </div>

                <!-- Columna B: cuándo -->
                <div class="ci-rv-b">
                    <div class="ci-dos">
                        <!-- Fecha -->
                        <div class="ci-campo" :class="{ 'mal': errores.fecha }">
                            <span>Fecha</span>
                            <button type="button" class="ci-pick" :class="{ 'abierto': pop === 'fecha', 'vacio': !form.fecha }" @click="abrirPop('fecha')" :tabindex="editando ? 0 : -1" :aria-expanded="(pop === 'fecha').toString()">
                                <span x-text="form.fecha ? fechaCorta(form.fecha) + ' · ' + diaSemana(form.fecha).slice(0, 3) : 'Elige'"></span>
                                <i class="bi bi-calendar3"></i>
                            </button>
                        </div>
                        <!-- Hora -->
                        <div class="ci-campo" :class="{ 'mal': errores.hora }">
                            <span>Hora</span>
                            <button type="button" class="ci-pick t" :class="{ 'abierto': pop === 'hora', 'vacio': !form.hora }" @click="abrirPop('hora')" :tabindex="editando ? 0 : -1" :aria-expanded="(pop === 'hora').toString()">
                                <span x-text="form.hora || 'Elige'"></span>
                                <i class="bi bi-clock"></i>
                            </button>
                        </div>
                    </div>
                    <small class="ci-err" x-show="errores.fecha || errores.hora" x-text="errores.fecha || errores.hora"></small>

                    <!-- Calendario para elegir la fecha -->
                    <div class="ci-pop ci-pop-cal" x-show="pop === 'fecha'" x-cloak>
                        <div class="ci-mini-h">
                            <button type="button" class="ci-ico ci-ico-s" @click="mesForm = sumarMes(mesForm, -1)" aria-label="Mes anterior"><i class="bi bi-chevron-left"></i></button>
                            <b x-text="nombreMes(mesForm) + ' ' + mesForm.slice(0, 4)"></b>
                            <button type="button" class="ci-ico ci-ico-s" @click="mesForm = sumarMes(mesForm, 1)" aria-label="Mes siguiente"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <div class="ci-mini-g">
                            <template x-for="d in ['L', 'M', 'M', 'J', 'V', 'S', 'D']"><span class="ci-mini-s" x-text="d"></span></template>
                            <template x-for="cel in celdasMes(mesForm)" :key="'f' + cel.fecha">
                                <button type="button" class="t" :class="{ 'fuera': !cel.dentro, 'hoy': cel.fecha === hoy, 'sel': cel.fecha === form.fecha }"
                                        :disabled="!form.id && cel.fecha < hoy"
                                        :data-n="form.personal_id ? Math.min(3, Math.ceil(citasDeMedico(form.personal_id, cel.fecha) / 2)) : 0"
                                        @click="form.fecha = cel.fecha; errores.fecha = ''; pop = 'hora'">
                                    <span x-text="Number(cel.fecha.slice(8))"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Reloj para elegir la hora (24 h) -->
                    <div class="ci-pop ci-pop-hora" x-show="pop === 'hora'" x-cloak>
                        <p class="ci-pop-t">Hora exacta</p>
                        <div class="ci-exacta t">
                            <input type="text" inputmode="numeric" maxlength="2" aria-label="Hora (0 a 23)" x-ref="horaH" x-model="exH" placeholder="HH"
                                   @focus="$event.target.select()"
                                   @input="aplicarExacta(); if (exH.length === 2) $refs.horaM.focus()"
                                   @blur="exH = form.hora ? form.hora.slice(0, 2) : exH"
                                   @keydown.arrow-up.prevent="pasoParte('h', 1)" @keydown.arrow-down.prevent="pasoParte('h', -1)">
                            <b>:</b>
                            <input type="text" inputmode="numeric" maxlength="2" aria-label="Minutos (0 a 59)" x-ref="horaM" x-model="exM" placeholder="MM"
                                   @focus="$event.target.select()"
                                   @input="aplicarExacta()"
                                   @blur="exM = form.hora ? form.hora.slice(3, 5) : exM"
                                   @keydown.arrow-up.prevent="pasoParte('m', 1)" @keydown.arrow-down.prevent="pasoParte('m', -1)"
                                   @keydown.enter.prevent="pop = ''">
                            <span>24 h · escribe cualquier minuto o usa ↑ ↓</span>
                        </div>
                        <p class="ci-pop-t">O elige rápido</p>
                        <div class="ci-horas t">
                            <template x-for="h in [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]" :key="'hh' + h">
                                <button type="button" :class="{ 'on': form.hora && Number(form.hora.slice(0, 2)) === h, 'lleno': !horaConEspacio(h) }"
                                        @click="elegirHora(h, form.hora ? Number(form.hora.slice(3, 5)) : 0)" x-text="String(h).padStart(2, '0')"></button>
                            </template>
                        </div>
                        <p class="ci-pop-t">Minutos frecuentes</p>
                        <div class="ci-mins t">
                            <template x-for="m in [0, 15, 30, 45]" :key="'mm' + m">
                                <button type="button" :class="{ 'on': form.hora && Number(form.hora.slice(3, 5)) === m }" :disabled="!form.hora"
                                        @click="elegirHora(Number(form.hora.slice(0, 2)), m); pop = ''" x-text="':' + String(m).padStart(2, '0')"></button>
                            </template>
                        </div>
                        <p class="ci-pop-nota">Las horas atenuadas ya no tienen espacio para este médico o consultorio.</p>
                    </div>

                    <!-- Horarios libres sugeridos para ese médico y consultorio -->
                    <div class="ci-campo" x-show="form.personal_id && form.fecha">
                        <span>Horarios libres <small class="ci-ayuda" x-text="libres.length ? '' : '· sin espacios este día'"></small></span>
                        <div class="ci-chips">
                            <template x-for="h in libres" :key="'lh' + h">
                                <button type="button" class="ci-chip t" :class="{ 'on': form.hora === h }" @click="form.hora = h; errores.hora = ''" x-text="h" :tabindex="editando ? 0 : -1"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="ci-rv-pie">
                <span class="ci-msg" :class="{ 'mal': choqueForm }" x-text="choqueForm || mensajeForm"></span>
                <button type="button" class="ci-btn" @click="cancelar()" :tabindex="editando ? 0 : -1">Cancelar</button>
                <button type="submit" class="ci-btn ci-prim" :disabled="guardando" :tabindex="editando ? 0 : -1">
                    <span x-text="guardando ? 'Guardando…' : (form.id ? 'Guardar' : 'Agendar')"></span>
                </button>
            </footer>
        </form>
    </div>
</div>

<!-- ACCIONES DEL PASE -->
<div class="ci-acc" x-show="!editando">
    <template x-if="sel">
        <div class="ci-acc-in">
            <button type="button" class="ci-btn ci-prim ci-ancho" x-show="siguientePaso(sel)" @click="cambiarEstado(sel, siguientePaso(sel).valor)" :disabled="guardando">
                <span x-text="siguientePaso(sel) ? siguientePaso(sel).accion : ''"></span>
            </button>
            <div class="ci-acc-fila">
                <button type="button" class="ci-btn" @click="editar(sel)">Editar <kbd>E</kbd></button>
                <button type="button" class="ci-btn" x-show="!['Cancelada', 'Finalizada'].includes(sel.estado)" @click="cambiarEstado(sel, 'Cancelada')" :disabled="guardando">Cancelar cita</button>
                <button type="button" class="ci-btn ci-borrar" @click="eliminar(sel)" aria-label="Eliminar cita"><i class="bi bi-trash3"></i></button>
            </div>
        </div>
    </template>
    <template x-if="!sel">
        <button type="button" class="ci-btn ci-prim ci-ancho" @click="nueva()">Agendar cita <kbd>N</kbd></button>
    </template>
</div>