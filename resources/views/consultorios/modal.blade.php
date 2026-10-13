{{--
    PLACA DEL CONSULTORIO · MediTrack
    Frente: la placa del consultorio seleccionado (estado, piso, médico en consulta).
    Reverso: el formulario para registrarlo o editarlo. Al editar, la placa se voltea.
    Lo usa consultorios/index.blade.php (comparte su componente Alpine).
--}}
<aside class="co-lado" aria-label="Consultorio seleccionado">
    <div class="co-placa" :class="{ 'volteada': editando }">
        <div class="co-placa-in">

            <!-- FRENTE -->
            <article class="co-cara co-frente" :class="'e-' + (sel ? sel.estado : 'vacio')" :aria-hidden="editando.toString()">
                <span class="co-gl" x-html="sel ? guilloche(sel.nombre + sel.id) : guilloche('MEDITRACK')"></span>

                <template x-if="sel">
                    <div class="co-fr">
                        <header>
                            <span class="co-k">Consultorio · <b class="t" x-text="'#' + String(sel.id).padStart(3, '0')"></b></span>
                            <svg class="co-cruz" viewBox="0 0 44 44" aria-hidden="true"><rect x="15" y="2" width="14" height="40" rx="4"/><rect x="2" y="15" width="40" height="14" rx="4"/></svg>
                        </header>
                        <h2 x-text="sel.nombre"></h2>
                        <dl>
                            <div><dt>Piso</dt><dd x-text="etiquetaPiso(sel.piso)"></dd></div>
                            <div><dt>Estado</dt><dd x-text="info(sel.estado).texto"></dd></div>
                            <div x-show="sel.creado"><dt>Alta</dt><dd x-text="sel.creado"></dd></div>
                        </dl>
                        <footer class="co-franja">
                            <i class="bi" :class="info(sel.estado).icono"></i>
                            <span x-text="franja(sel)"></span>
                        </footer>
                    </div>
                </template>

                <template x-if="!sel">
                    <div class="co-fr co-fr-vacio">
                        <header><span class="co-k">Consultorios</span></header>
                        <h2>Aún no hay consultorios</h2>
                        <p>Registra el primero para verlo en el plano y asignarle citas.</p>
                    </div>
                </template>
            </article>

            <!-- REVERSO: formulario -->
            <form class="co-cara co-reverso" @submit.prevent="guardar()" novalidate :aria-hidden="(!editando).toString()">
                <p class="co-rv-k" x-text="form.id ? 'Editando placa · #' + String(form.id).padStart(3, '0') : 'Emitiendo placa nueva'"></p>

                <label class="co-campo" :class="{ 'mal': errores.nombre }">
                    <span>Nombre del consultorio</span>
                    <input type="text" x-ref="campoNombre" x-model="form.nombre" maxlength="80" autocomplete="off"
                           placeholder="Ej. Consultorio 101, Pediatría" @input="errores.nombre = ''" :tabindex="editando ? 0 : -1">
                    <small x-show="errores.nombre" x-text="errores.nombre"></small>
                </label>

                <div class="co-campo" :class="{ 'mal': errores.piso }">
                    <span>Piso</span>
                    <div class="co-pisos-op">
                        <input type="text" x-model="form.piso" maxlength="20" placeholder="1, 2, PB…" aria-label="Piso"
                               @input="errores.piso = ''" :tabindex="editando ? 0 : -1">
                        <template x-for="p in pisos" :key="'op' + p">
                            <button type="button" class="co-chip" :class="{ 'on': form.piso === p }" @click="form.piso = p"
                                    x-text="p === '' ? 'Sin piso' : p" :tabindex="editando ? 0 : -1"></button>
                        </template>
                    </div>
                    <small x-show="errores.piso" x-text="errores.piso"></small>
                </div>

                <div class="co-campo">
                    <span>Estado</span>
                    <div class="co-seg" role="radiogroup" aria-label="Estado del consultorio">
                        <template x-for="op in opciones" :key="'f' + op.valor">
                            <button type="button" role="radio" :class="'e-' + op.slug" :aria-checked="(form.estado === op.valor).toString()"
                                    :disabled="!form.id && op.valor !== 'Disponible'" @click="form.estado = op.valor" :tabindex="editando ? 0 : -1">
                                <i></i><span x-text="op.corto"></span>
                            </button>
                        </template>
                    </div>
                    <small class="co-nota" x-show="!form.id">Un consultorio nuevo siempre inicia disponible.</small>
                </div>

                <footer class="co-rv-pie">
                    <span class="co-msg" x-text="mensajeForm"></span>
                    <button type="button" class="co-btn" @click="cancelar()" :tabindex="editando ? 0 : -1">Cancelar</button>
                    <button type="submit" class="co-btn co-prim" :disabled="guardando" :tabindex="editando ? 0 : -1">
                        <span x-text="guardando ? 'Guardando…' : (form.id ? 'Guardar' : 'Emitir placa')"></span>
                    </button>
                </footer>
            </form>
        </div>
    </div>

    <!-- ACCIONES (frente) -->
    <div class="co-acc" x-show="!editando">
        <template x-if="sel">
            <div class="co-acc-in">
                <p class="co-acc-t">Cambiar estado</p>
                <div class="co-seg" role="radiogroup" aria-label="Cambiar estado del consultorio">
                    <template x-for="op in opciones" :key="'a' + op.valor">
                        <button type="button" role="radio" :class="'e-' + op.slug" :aria-checked="(sel.estado === op.slug).toString()"
                                @click="cambiarEstado(op)" :disabled="guardando">
                            <i></i><span x-text="op.corto"></span>
                        </button>
                    </template>
                </div>
                <button type="button" class="co-btn co-prim co-ancho" @click="editar(sel)">Editar placa <kbd>E</kbd></button>
                <button type="button" class="co-btn co-borrar co-ancho" @click="eliminar(sel)">Eliminar consultorio</button>
            </div>
        </template>
        <template x-if="!sel">
            <button type="button" class="co-btn co-prim co-ancho" @click="nuevo()">Registrar el primer consultorio</button>
        </template>
    </div>
</aside>