@extends('layouts.admin')

{{--
    MÓDULO DE PERSONAL · MediTrack
    Plantilla agrupada por cargo y, al lado, el gafete de la persona elegida.
    El gafete se voltea para dar de alta o editar (a lo ancho, como la credencial de pacientes)
    y se guarda por AJAX: PersonalController responde JSON. ?nuevo=1 abre el alta; ?p=ID elige a alguien.
--}}

@section('content')
<div class="pe" x-data="modPersonal()" x-cloak :class="{ 'editando': editando }" @keydown.window="tecla($event)">

    <!-- PESTAÑAS, BÚSQUEDA Y ALTA -->
    <header class="pe-top">
        @include('personal._pestanas', ['activa' => 'plantilla'])
        <label class="pe-cmd">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="pe-sr">Buscar personal</span>
            <input type="search" x-ref="buscar" x-model.debounce.150ms="q" autocomplete="off" placeholder="Busca por nombre, cargo, especialidad, correo o teléfono">
            <kbd aria-hidden="true">/</kbd>
        </label>
        <a class="pe-btn" x-show="PUEDE.roles && R.roles" :href="R.roles"><i class="bi bi-shield-lock"></i> Roles y permisos</a>
        <button type="button" class="pe-dark" x-show="PUEDE.crear" @click="nueva()">Registrar personal <kbd>N</kbd></button>
    </header>

    <!-- CABECERA: cifra e indicadores -->
    <section class="pe-cab">
        <div class="pe-g">
            <b class="t" x-text="P.length"></b>
            <span><em x-text="P.length === 1 ? 'persona en el equipo' : 'personas en el equipo'"></em><span x-text="CLINICA"></span></span>
        </div>
        <div class="pe-ind">
            <template x-for="i in INDICADORES" :key="i.k">
                <button type="button" :class="'i-' + i.k" @click="filtro = filtro === i.k ? '' : i.k" :aria-pressed="(filtro === i.k).toString()">
                    <span x-text="i.t"></span><b class="t" x-text="contar(i.k)"></b>
                </button>
            </template>
        </div>
        <div class="pe-turnos" role="group" aria-label="Turno">
            <button type="button" :aria-pressed="(!turno).toString()" @click="turno = ''">Todos</button>
            <template x-for="t in TURNOS" :key="'ft' + t.v">
                <button type="button" :aria-pressed="(turno === t.v).toString()" @click="turno = turno === t.v ? '' : t.v"><i class="bi" :class="t.i"></i><span x-text="t.v"></span></button>
            </template>
        </div>
    </section>

    <section class="pe-esc">
        <!-- PLANTILLA POR CARGO -->
        <div class="pe-lista" aria-label="Plantilla">
            <template x-for="g in grupos" :key="'g' + g.rol">
                <div class="pe-grupo">
                    <h3><span x-text="g.rol"></span><small class="t" x-text="g.items.length"></small></h3>
                    <template x-for="p in g.items" :key="'p' + p.id">
                        <button type="button" class="pe-fila" :class="{ 'sel': sel && sel.id === p.id, 'inactivo': !p.activo }" @click="seleccionar(p)">
                            <span class="pe-av"><span x-html="roseta(p.nombre, 44)"></span><b x-text="iniciales(p)"></b><i :class="p.activo ? 'on' : ''"></i></span>
                            <span class="pe-fila-n">
                                <b><span x-text="p.nombre"></span><em x-show="p.soyYo">Tú</em></b>
                                <small x-text="[p.especialidad || p.rol, 'Turno ' + p.turno.toLowerCase()].filter(Boolean).join(' · ')"></small>
                            </span>
                            <span class="pe-fila-d">
                                <span class="pe-hoy t" x-show="p.citasHoy" x-text="p.citasHoy + (p.citasHoy === 1 ? ' cita hoy' : ' citas hoy')"></span>
                                <i class="bi" :class="p.acceso ? 'bi-shield-check pe-acc-si' : 'bi-shield-x pe-acc-no'" :title="p.acceso ? 'Con acceso: ' + p.email : 'Sin acceso al sistema'"></i>
                            </span>
                        </button>
                    </template>
                </div>
            </template>
            <div class="pe-vacio" x-show="!grupos.length">
                <b x-text="P.length ? 'Nadie coincide con la búsqueda' : 'Aún no hay personal registrado'"></b>
                <button type="button" x-show="P.length" @click="limpiar()">Quitar filtros</button>
                <button type="button" x-show="!P.length && PUEDE.crear" @click="nueva()">Registrar a la primera persona</button>
            </div>
        </div>

        <!-- GAFETE (frente) Y SU FORMULARIO (reverso) -->
        <aside class="pe-lado">
            <div class="pe-gafete" :class="{ 'volteado': editando }">
                <div class="pe-gafete-in">

                    <!-- FRENTE: gafete del personal -->
                    <article class="pe-cara pe-frente" :class="sel && !sel.activo ? 'inactivo' : ''" :aria-hidden="editando.toString()">
                        <span class="pe-ranura" aria-hidden="true"></span>
                        <template x-if="sel">
                            <div class="pe-fr">
                                <header class="pe-fr-h">
                                    <span class="pe-gl" x-html="guilloche(sel.nombre + sel.folio)"></span>
                                    <p><b x-text="CLINICA"></b><span>Personal</span></p>
                                    <span class="pe-folio t" x-text="sel.folio"></span>
                                </header>
                                <div class="pe-fr-foto">
                                    <span class="pe-ros" x-html="roseta(sel.nombre, 120)"></span>
                                    <b x-text="iniciales(sel)"></b>
                                </div>
                                <h2 x-text="sel.nombre"></h2>
                                <p class="pe-cargo"><span x-text="sel.rol || 'Sin cargo'"></span></p>
                                <p class="pe-esp" x-show="sel.especialidad" x-text="sel.especialidad"></p>
                                <dl class="pe-fr-d">
                                    <div><dt>Cédula</dt><dd class="t" x-text="sel.cedula || '—'"></dd></div>
                                    <div><dt>Turno</dt><dd><i class="bi" :class="turnoDe(sel.turno).i"></i> <span x-text="sel.turno"></span></dd></div>
                                    <div><dt>Teléfono</dt><dd class="t" x-text="sel.telefono ? formatoTel(sel.telefono) : '—'"></dd></div>
                                    <div><dt>Desde</dt><dd x-text="sel.desde || '—'"></dd></div>
                                    <div class="ancho" x-show="sel.direccion"><dt>Domicilio</dt><dd x-text="sel.direccion"></dd></div>
                                </dl>
                                <p class="pe-act t" x-show="sel.citasHoy || sel.atendidasMes" x-text="'Hoy ' + sel.citasHoy + (sel.citasHoy === 1 ? ' cita' : ' citas') + ' · ' + sel.atendidasMes + ' atendidas este mes'"></p>
                                <figure class="pe-codigo" :title="'Código 128 · ' + sel.folio">
                                    <span class="pe-barras" x-html="barras(sel.folio)"></span>
                                    <figcaption class="t" x-text="sel.folio"></figcaption>
                                </figure>
                                <footer class="pe-franja">
                                    <i></i>
                                    <span x-text="(sel.activo ? 'Activo' : 'Inactivo') + ' · ' + (sel.acceso ? sel.email : 'Sin acceso al sistema')"></span>
                                </footer>
                            </div>
                        </template>
                        <template x-if="!sel">
                            <div class="pe-fr pe-fr-vacio">
                                <header class="pe-fr-h"><span class="pe-gl" x-html="guilloche('PERSONAL')"></span><p><b x-text="CLINICA"></b><span>Personal</span></p></header>
                                <h2>Elige a alguien</h2>
                                <p>Toca un nombre de la plantilla para ver su gafete.</p>
                            </div>
                        </template>
                    </article>

                    <!-- REVERSO: alta o edición -->
                    <form class="pe-cara pe-reverso" @submit.prevent="guardar()" novalidate :aria-hidden="(!editando).toString()">
                        <p class="pe-rv-k" x-text="form.id ? 'Editando · ' + (sel ? sel.folio : '') : 'Alta de personal'"></p>

                        <div class="pe-rv-c">
                            <!-- Columna A: identidad y puesto -->
                            <div class="pe-rv-a">
                                <h4>Identidad</h4>
                                <div class="pe-dos">
                                    <label class="pe-campo" :class="{ 'mal': errores.nombre }">
                                        <span>Nombre(s)</span>
                                        <input type="text" x-ref="campoNombre" x-model="form.nombre" maxlength="80" autocomplete="off" class="may" placeholder="LAURA" @input="errores.nombre = ''" @blur="form.nombre = mayus(form.nombre)" :tabindex="editando ? 0 : -1">
                                        <small x-show="errores.nombre" x-text="errores.nombre"></small>
                                    </label>
                                    <label class="pe-campo">
                                        <span>Apellidos</span>
                                        <input type="text" x-model="form.apellido" maxlength="80" autocomplete="off" class="may" placeholder="MÉNDEZ RÍOS" @blur="form.apellido = mayus(form.apellido)" :tabindex="editando ? 0 : -1">
                                    </label>
                                </div>
                                <div class="pe-dos">
                                    <label class="pe-campo" :class="{ 'mal': errores.telefono }">
                                        <span>Teléfono <em>(opcional)</em></span>
                                        <input type="tel" x-model="form.telefono" inputmode="numeric" maxlength="10" autocomplete="off" class="t" placeholder="10 dígitos" @input="form.telefono = form.telefono.replace(/\D/g, '').slice(0, 10); errores.telefono = ''" :tabindex="editando ? 0 : -1">
                                        <small x-show="errores.telefono" x-text="errores.telefono"></small>
                                    </label>
                                    <label class="pe-campo" :class="{ 'mal': errores.rut }">
                                        <span>Cédula profesional <em>(opcional)</em></span>
                                        <input type="text" x-model="form.rut" maxlength="20" autocomplete="off" class="t may" placeholder="12345678" @input="errores.rut = ''" @blur="form.rut = form.rut.toUpperCase().replace(/\s/g, '')" :tabindex="editando ? 0 : -1">
                                        <small x-show="errores.rut" x-text="errores.rut"></small>
                                    </label>
                                </div>
                                <label class="pe-campo">
                                    <span>Domicilio <em>(opcional)</em></span>
                                    <input type="text" x-model="form.direccion" maxlength="255" autocomplete="off" class="may" placeholder="Calle y número, colonia, municipio, CP" @blur="form.direccion = mayus(form.direccion)" :tabindex="editando ? 0 : -1">
                                </label>

                                <h4>Puesto</h4>
                                <div class="pe-campo" :class="{ 'mal': errores.rol_id }">
                                    <span>Cargo o rol</span>
                                    <div class="pe-chips">
                                        <template x-for="r in ROLES" :key="'r' + r.id">
                                            <button type="button" class="pe-chip" :class="{ 'on': form.rol_id === r.id }" @click="form.rol_id = r.id; errores.rol_id = ''" :tabindex="editando ? 0 : -1">
                                                <i class="bi" :class="iconoRol(r.nombre)"></i><span x-text="r.nombre"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <p class="pe-ayuda" x-show="!ROLES.length">No hay roles registrados. Crea uno en Roles y permisos.</p>
                                    <small x-show="errores.rol_id" x-text="errores.rol_id"></small>
                                </div>
                                <label class="pe-campo" x-show="esMedico">
                                    <span>Especialidad <em>(se muestra en citas y recetas)</em></span>
                                    <input type="text" x-model="form.especialidad" maxlength="100" autocomplete="off" class="may" placeholder="MEDICINA GENERAL, PEDIATRÍA…" @blur="form.especialidad = mayus(form.especialidad)" :tabindex="editando ? 0 : -1">
                                </label>
                                <div class="pe-campo">
                                    <span>Turno</span>
                                    <div class="pe-seg pe-seg-4">
                                        <template x-for="t in TURNOS" :key="'t' + t.v">
                                            <button type="button" :aria-pressed="(form.turno === t.v).toString()" @click="form.turno = t.v" :tabindex="editando ? 0 : -1"><i class="bi" :class="t.i"></i><span x-text="t.v"></span></button>
                                        </template>
                                    </div>
                                </div>
                                <div class="pe-campo">
                                    <span>Estatus</span>
                                    <div class="pe-seg pe-seg-2">
                                        <button type="button" class="s-act" :aria-pressed="(form.estado === 'Activo').toString()" @click="form.estado = 'Activo'" :tabindex="editando ? 0 : -1"><i></i>Activo</button>
                                        <button type="button" class="s-ina" :aria-pressed="(form.estado === 'Inactivo').toString()" @click="form.estado = 'Inactivo'" :tabindex="editando ? 0 : -1"><i></i>Inactivo o con permiso</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna B: acceso y nómina -->
                            <div class="pe-rv-b">
                                <div class="pe-h4">
                                    <h4>Acceso al sistema</h4>
                                    <button type="button" class="pe-switch" role="switch" :aria-checked="form.acceso.toString()" @click="alternarAcceso()" :tabindex="editando ? 0 : -1">
                                        <span x-text="form.acceso ? 'Tendrá cuenta' : 'Sin cuenta'"></span><i></i>
                                    </button>
                                </div>
                                <template x-if="form.acceso">
                                    <div class="pe-acceso">
                                        <div class="pe-campo" :class="{ 'mal': errores.username }">
                                            <span>Usuario</span>
                                            <div class="pe-usuario">
                                                <input type="text" x-model="form.username" maxlength="40" autocomplete="off" placeholder="lmendez" @input="form.username = form.username.toLowerCase().replace(/[^a-z0-9_-]/g, ''); usuarioManual = true; errores.username = ''" :tabindex="editando ? 0 : -1">
                                                <span x-text="'@' + DOMINIO"></span>
                                            </div>
                                            <div class="pe-sug" x-show="!form.username || !usuarioManual">
                                                <template x-for="s in sugerencias" :key="'su' + s">
                                                    <button type="button" class="t" :class="{ 'on': form.username === s }" @click="form.username = s; usuarioManual = true" x-text="s" :tabindex="editando ? 0 : -1"></button>
                                                </template>
                                            </div>
                                            <small x-show="errores.username" x-text="errores.username"></small>
                                        </div>
                                        <div class="pe-campo" :class="{ 'mal': errores.password }">
                                            <span x-text="tieneCuenta ? 'Nueva contraseña (déjala vacía para conservarla)' : 'Contraseña de 8 caracteres'"></span>
                                            <div class="pe-pass">
                                                <input :type="verPass ? 'text' : 'password'" x-model="form.password" maxlength="8" autocomplete="new-password" class="t" placeholder="••••••••" @input="errores.password = ''" :tabindex="editando ? 0 : -1">
                                                <button type="button" @click="copiarPass()" x-show="form.password" aria-label="Copiar contraseña" :tabindex="editando ? 0 : -1"><i class="bi bi-clipboard"></i></button>
                                                <button type="button" @click="verPass = !verPass" :aria-label="verPass ? 'Ocultar' : 'Mostrar'" :tabindex="editando ? 0 : -1"><i class="bi" :class="verPass ? 'bi-eye-slash' : 'bi-eye'"></i></button>
                                                <button type="button" class="pe-gen" @click="generarPass()" :tabindex="editando ? 0 : -1"><i class="bi bi-magic"></i> Generar</button>
                                            </div>
                                            <div class="pe-fuerza" x-show="form.password"><template x-for="n in 4" :key="'fz' + n"><i :class="n <= fuerza.nivel ? fuerza.clase : ''"></i></template><span x-text="form.password.length !== 8 ? 'Lleva ' + form.password.length + ' de 8' : fuerza.texto"></span></div>
                                            <small x-show="errores.password" x-text="errores.password"></small>
                                        </div>
                                        <label class="pe-campo" x-show="form.password" :class="{ 'mal': form.password2 && form.password2 !== form.password }">
                                            <span>Confirmar contraseña</span>
                                            <input :type="verPass ? 'text' : 'password'" x-model="form.password2" maxlength="8" autocomplete="new-password" class="t" placeholder="••••••••" :tabindex="editando ? 0 : -1">
                                            <small x-show="form.password2 && form.password2 !== form.password">Las contraseñas no coinciden.</small>
                                        </label>
                                        <p class="pe-nota" x-show="passGenerada"><i class="bi bi-info-circle"></i> Copia la contraseña y entrégala: después de guardar ya no se puede ver.</p>
                                    </div>
                                </template>
                                <p class="pe-nota" x-show="!form.acceso">
                                    <i class="bi bi-person-lock"></i>
                                    <span x-text="tieneCuenta ? 'Al guardar se eliminará su cuenta y ya no podrá entrar al sistema. Su ficha y sus citas se conservan.' : 'Quedará solo en la plantilla: se le pueden asignar citas, pero no podrá iniciar sesión. Puedes darle acceso después.'"></span>
                                </p>

                                <h4>Datos de nómina</h4>
                                <div class="pe-dos">
                                    <label class="pe-campo" :class="{ 'mal': errores.salario_diario }">
                                        <span>Salario diario <em>(opcional)</em></span>
                                        <div class="pe-dinero"><b>$</b><input type="text" inputmode="decimal" x-model="form.salario_diario" maxlength="10" class="t" placeholder="0.00" @input="form.salario_diario = form.salario_diario.replace(/[^\d.]/g, ''); errores.salario_diario = ''" :tabindex="editando ? 0 : -1"><span>MXN</span></div>
                                        <small x-show="errores.salario_diario" x-text="errores.salario_diario"></small>
                                    </label>
                                    <div class="pe-campo">
                                        <span>Se le paga</span>
                                        <div class="pe-seg pe-seg-3">
                                            <template x-for="t in PAGOS" :key="'tp' + t.v">
                                                <button type="button" :aria-pressed="(form.tipo_pago === t.v).toString()" @click="form.tipo_pago = t.v" x-text="t.t" :tabindex="editando ? 0 : -1"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <p class="pe-ayuda" x-text="Number(form.salario_diario) ? 'Por pago ' + form.tipo_pago.toLowerCase() + ': ' + dinero(porPeriodo) + ' · aprox. ' + dinero(Number(form.salario_diario) * 30) + ' al mes' : 'Puedes dejarlo vacío y capturarlo después desde Nómina.'"></p>
                            </div>
                        </div>

                        <footer class="pe-rv-f">
                            <span class="pe-msg" :class="{ 'mal': mensajeMal }" x-text="mensajeForm"></span>
                            <button type="button" class="pe-btn" @click="cancelar()" :tabindex="editando ? 0 : -1">Cancelar</button>
                            <button type="submit" class="pe-btn pe-prim" :disabled="guardando" :tabindex="editando ? 0 : -1" x-text="guardando ? 'Guardando…' : (form.id ? 'Guardar cambios' : 'Dar de alta')"></button>
                        </footer>
                    </form>
                </div>
            </div>

            <!-- Acciones del gafete -->
            <div class="pe-acc" x-show="!editando && sel">
                <button type="button" class="pe-btn pe-prim" x-show="PUEDE.editar" @click="editar(sel)"><i class="bi bi-pencil"></i> Editar <kbd>E</kbd></button>
                <a class="pe-btn" x-show="sel && sel.nomina" :href="sel ? sel.nomina : '#'"><i class="bi bi-receipt"></i> Nómina</a>
                <button type="button" class="pe-btn pe-ico" x-show="sel && sel.email" @click="copiar(sel.email)" aria-label="Copiar correo" title="Copiar correo"><i class="bi bi-envelope"></i></button>
                <a class="pe-btn pe-ico" x-show="sel && sel.telefono" :href="sel && sel.telefono ? 'tel:' + sel.telefono : '#'" aria-label="Llamar" title="Llamar"><i class="bi bi-telephone"></i></a>
                <button type="button" class="pe-btn pe-ico pe-borrar" x-show="PUEDE.eliminar" @click="eliminar(sel)" :disabled="sel && sel.soyYo" :title="sel && sel.soyYo ? 'No puedes eliminar tu propia cuenta' : 'Eliminar'" aria-label="Eliminar"><i class="bi bi-trash3"></i></button>
            </div>
        </aside>
    </section>
</div>

<script type="application/json" id="pe-datos">{!! json_encode(array_merge($datosPersonal ?? [], ['csrf' => csrf_token(), 'exito' => session('success'), 'error' => session('error')]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@verbatim
<style>
.pe {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1); --flip: cubic-bezier(.3, .7, .1, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.pe * { box-sizing: border-box; }
.pe :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.pe button { cursor: pointer; }
.pe button:disabled { cursor: default; }
.pe .t { font-variant-numeric: tabular-nums; }
.pe h2, .pe h3, .pe h4, .pe p, .pe dl, .pe dd { margin: 0; }
.pe-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.pe kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); background: transparent; }
[x-cloak] { display: none !important; }

/* ---------- Arriba ---------- */
.pe-top { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 12px; }
.pe-cmd { flex: 1 1 320px; min-width: 0; height: 46px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 12px 30px -24px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 16px; }
.pe-cmd > .bi { color: var(--cred); }
.pe-cmd input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 450 15px var(--ui); color: var(--tinta); }
.pe-cmd input::placeholder { color: var(--media); }
.pe-dark { height: 44px; border-radius: 12px; border: 0; background: var(--tinta); color: #fff; font: 600 14px var(--ui); display: flex; align-items: center; gap: 10px; padding: 0 12px 0 16px; white-space: nowrap; }
.pe-dark:hover { background: var(--cred2); }
.pe-dark kbd { border-color: rgba(255, 255, 255, .25); color: #cfe; }

/* ---------- Cabecera ---------- */
.pe-cab { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px 26px; }
.pe-g { display: flex; align-items: flex-end; gap: 12px; }
.pe-g > b { font: 800 84px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); }
.pe-g > span { display: flex; flex-direction: column; padding-bottom: 2px; font-size: 14px; color: var(--media); line-height: 1.25; }
.pe-g em { font: 700 19px/1.15 var(--disp); font-style: normal; color: var(--tinta); }
.pe-ind { flex: 1 1 480px; min-width: 300px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0 14px; }
.pe-ind button { display: flex; justify-content: space-between; align-items: baseline; gap: 6px; padding: 8px 0 6px; text-align: left; background: none; border: 0; border-top: 2px solid var(--linea); font: 500 13px var(--ui); color: var(--texto); white-space: nowrap; }
.pe-ind button span { overflow: hidden; text-overflow: ellipsis; }
.pe-ind button b { font: 750 22px/1 var(--disp); color: var(--c); }
.pe-ind button[aria-pressed=true] { border-top-color: var(--c); }
.pe-ind button[aria-pressed=true] span { color: var(--tinta); font-weight: 650; }
.pe-ind .i-activos { --c: var(--verde); } .pe-ind .i-inactivos { --c: var(--media); } .pe-ind .i-acceso { --c: var(--cred); } .pe-ind .i-hoy { --c: var(--ambar); }
.pe-turnos { display: inline-flex; flex-wrap: wrap; gap: 2px; padding: 3px; border-radius: 11px; background: var(--papel); border: 1px solid var(--linea); }
.pe-turnos button { height: 32px; display: inline-flex; align-items: center; gap: 6px; padding: 0 11px; border: 0; border-radius: 8px; background: none; font: 600 13px var(--ui); color: var(--media); }
.pe-turnos button[aria-pressed=true] { background: var(--tinta); color: #fff; }
@container (max-width: 759px) { .pe-g > b { font-size: 64px; } .pe-ind { min-width: 100%; grid-template-columns: repeat(2, minmax(0, 1fr)); } }

/* ---------- Escenario ---------- */
.pe-esc { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
@container (min-width: 1000px) { .pe-esc { grid-template-columns: minmax(0, 1fr) 380px; } .pe-lado { position: sticky; top: 96px; } }
.pe-lado { display: grid; gap: 12px; min-width: 0; }

/* Plantilla */
.pe-lista { display: grid; gap: 16px; min-width: 0; transition: opacity .3s, filter .3s; }
.pe-grupo { background: var(--papel); border: 1px solid var(--linea); border-radius: 16px; padding: 10px 8px 8px; }
.pe-grupo h3 { display: flex; align-items: baseline; gap: 8px; padding: 2px 10px 8px; font: 700 16px var(--disp); color: var(--tinta); }
.pe-grupo h3 small { font: 500 12.5px var(--mono); color: var(--media); }
.pe-fila { width: 100%; display: flex; align-items: center; gap: 12px; padding: 8px 10px; border: 0; border-radius: 12px; background: none; text-align: left; transition: background .15s; }
.pe-fila:hover { background: var(--niebla); }
.pe-fila.sel { background: var(--velo); box-shadow: inset 3px 0 0 var(--cred); }
.pe-fila.inactivo .pe-fila-n b { color: var(--media); }
.pe-av { position: relative; flex: none; width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; color: #8fbfb6; background: var(--niebla); }
.pe-av > span, .pe-av svg { position: absolute; inset: 0; width: 100%; height: 100%; }
.pe-av b { position: relative; font: 700 14px var(--disp); color: var(--cred); }
.pe-av > i { position: absolute; right: 0; bottom: 0; width: 11px; height: 11px; border-radius: 50%; background: #b8c4c1; box-shadow: 0 0 0 2px var(--papel); }
.pe-av > i.on { background: var(--verde); }
.pe-fila-n { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.pe-fila-n b { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: var(--tinta); min-width: 0; }
.pe-fila-n b span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pe-fila-n em { flex: none; font-style: normal; font-size: 11px; font-weight: 650; color: var(--cred); background: var(--velo); border-radius: 99px; padding: 0 7px; }
.pe-fila-n small { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pe-fila-d { flex: none; display: flex; align-items: center; gap: 10px; }
.pe-hoy { font: 500 12px var(--mono); color: var(--ambar); background: var(--ambarv); border-radius: 99px; padding: 2px 8px; white-space: nowrap; }
.pe-acc-si { color: var(--cred); font-size: 16px; }
.pe-acc-no { color: #b8c4c1; font-size: 16px; }
.pe-vacio { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px 16px; border: 1.5px dashed var(--linea); border-radius: 16px; text-align: center; }
.pe-vacio b { font: 700 17px var(--disp); color: var(--tinta); }
.pe-vacio button { border: 0; background: none; font: 600 13.5px var(--ui); color: var(--cred); }
@container (max-width: 559px) { .pe-hoy { display: none; } }

/* ---------- Gafete ---------- */
.pe-gafete { perspective: 1500px; }
.pe-gafete-in { position: relative; min-height: 500px; transform-style: preserve-3d; transition: transform .45s var(--flip), height .3s var(--e); }
.pe-gafete.volteado .pe-gafete-in { transform: rotateY(180deg); height: 1180px; }
.pe-cara { position: absolute; inset: 0; border-radius: 22px; overflow: hidden; backface-visibility: hidden; -webkit-backface-visibility: hidden; }
.pe-frente { position: relative; inset: auto; min-height: 500px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 30px 50px -34px rgba(11, 30, 28, .6); }
.pe-ranura { position: absolute; z-index: 3; top: 14px; left: 50%; width: 62px; height: 12px; margin-left: -31px; border-radius: 99px; background: var(--niebla); box-shadow: inset 0 2px 3px rgba(11, 30, 28, .25); }
.pe-fr { position: relative; min-height: 500px; display: flex; flex-direction: column; align-items: center; text-align: center; }
.pe-fr-h { position: relative; width: 100%; height: 132px; padding: 38px 20px 0; overflow: hidden; color: #eafaf6; background: linear-gradient(160deg, #0d6a63, var(--cred) 50%, var(--cred2)); display: flex; justify-content: space-between; align-items: flex-start; text-align: left; }
.pe-gl, .pe-gl svg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.pe-fr-h p { position: relative; display: flex; flex-direction: column; min-width: 0; }
.pe-fr-h p b { font: 600 12px var(--mono); letter-spacing: .06em; text-transform: uppercase; color: var(--foil); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
.pe-fr-h p span { font: 700 20px var(--disp); color: #fff; }
.pe-folio { position: relative; font: 600 13px var(--mono); color: #fff; padding: 2px 8px; border: 1px solid rgba(166, 239, 226, .4); border-radius: 7px; }
.pe-fr-foto { position: relative; z-index: 2; width: 124px; height: 124px; margin-top: -58px; border-radius: 50%; display: grid; place-items: center; background: var(--papel); color: #7fbcb1; box-shadow: 0 0 0 5px var(--papel), 0 14px 26px -14px rgba(7, 63, 59, .7); }
.pe-ros, .pe-ros svg { position: absolute; inset: 2px; width: calc(100% - 4px); height: calc(100% - 4px); }
.pe-fr-foto b { position: relative; font: 800 40px/1 var(--disp); font-stretch: 85%; color: var(--cred); }
.pe-fr h2 { margin-top: 14px; padding: 0 22px; font: 780 25px/1.04 var(--disp); font-stretch: 80%; color: var(--tinta); text-transform: uppercase; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pe-cargo { margin-top: 8px; }
.pe-cargo span { display: inline-block; padding: 3px 12px; border-radius: 99px; background: var(--cred); color: var(--foil); font-size: 12.5px; font-weight: 650; letter-spacing: .02em; }
.pe-esp { margin-top: 6px; font-size: 13px; font-weight: 600; color: var(--teal); text-transform: uppercase; }
.pe-fr-d { width: 100%; margin-top: 16px; padding: 14px 22px 0; border-top: 1px solid var(--linea2); display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; text-align: left; }
.pe-fr-d dt { font-size: 11.5px; color: var(--media); }
.pe-fr-d dd { font-size: 14px; font-weight: 600; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pe-fr-d dd.t { font-family: var(--mono); font-weight: 500; }
.pe-fr-d .ancho { grid-column: 1 / -1; }
.pe-fr-d .ancho dd { font-size: 13px; font-weight: 500; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.pe-fr-d dd .bi { color: var(--teal); }
.pe-act { margin-top: 12px; font: 500 12.5px var(--mono); color: var(--ambar); }
.pe-codigo { flex: none; margin: auto 0 10px; padding-top: 16px; display: flex; flex-direction: column; align-items: center; gap: 2px; }
.pe-barras { display: block; height: 40px; background: #fff; }
.pe-barras svg { display: block; max-width: 100%; height: 40px; }
.pe-codigo figcaption { font: 500 11.5px var(--mono); letter-spacing: .3em; color: var(--media); }
.pe-franja { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 10px 18px; font-size: 13px; font-weight: 600; color: #fff; background: var(--verde); }
.pe-franja i { flex: none; width: 8px; height: 8px; border-radius: 50%; background: #fff; }
.pe-franja span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pe-frente.inactivo .pe-franja { background: #5d6a67; }
.pe-frente.inactivo .pe-fr-foto, .pe-frente.inactivo h2 { filter: grayscale(1); opacity: .75; }
.pe-fr-vacio h2 { margin-top: 70px; text-transform: none; font-size: 26px; }
.pe-fr-vacio > p { margin-top: 8px; padding: 0 30px; color: var(--media); }

/* Reverso: formulario */
.pe-reverso { transform: rotateY(180deg); background: #fbfdfc; border: 1px solid var(--linea); box-shadow: 0 24px 44px -30px rgba(11, 30, 28, .55); display: flex; flex-direction: column; }
.pe-rv-k { padding: 16px 18px 6px; font: 500 12px var(--mono); letter-spacing: .04em; text-transform: uppercase; color: var(--cred); }
.pe-rv-c { flex: 1; min-height: 0; overflow-y: auto; padding: 4px 18px 16px; display: flex; flex-direction: column; gap: 12px; scrollbar-width: thin; }
.pe-rv-a, .pe-rv-b { display: contents; }
.pe-rv-c h4 { flex-shrink: 0; margin-top: 6px; font: 700 15px var(--disp); color: var(--tinta); }
.pe-h4 { flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 6px; }
.pe-h4 h4 { margin: 0 !important; }
.pe-rv-c > *, .pe-rv-a > *, .pe-rv-b > * { flex-shrink: 0; }
.pe-dos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.pe-campo { position: relative; display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.pe-campo > span { font-size: 13px; font-weight: 600; color: var(--tinta); }
.pe-campo > span em { font-style: normal; font-weight: 400; color: var(--media); }
.pe-campo input { width: 100%; height: 40px; border: 1px solid var(--linea); border-radius: 10px; padding: 0 10px; font: 500 14px var(--ui); color: var(--tinta); background: var(--papel); outline: 0; transition: border-color .15s, box-shadow .15s; }
.pe-campo input.t { font-family: var(--mono); }
.pe-campo input.may { text-transform: uppercase; }
.pe-campo input.may::placeholder { text-transform: none; }
.pe-campo input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.pe-campo.mal input { border-color: var(--rosa); }
.pe-campo > small { font-size: 12px; font-weight: 500; color: var(--rosa); }
.pe-ayuda { font-size: 12.5px; color: var(--media); }
.pe-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.pe-chip { height: 36px; display: inline-flex; align-items: center; gap: 7px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--texto); }
.pe-chip .bi { color: var(--media); }
.pe-chip.on { border-color: var(--cred); background: var(--velo); color: var(--cred); }
.pe-chip.on .bi { color: var(--cred); }
.pe-campo.mal .pe-chip { border-color: #f1a7b4; }
.pe-seg { display: grid; gap: 5px; }
.pe-seg-2 { grid-template-columns: 1fr 1.6fr; }
.pe-seg-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.pe-seg-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.pe-seg button { height: 38px; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 0 6px; border-radius: 9px; border: 1px solid var(--linea); background: var(--papel); font: 600 12.5px var(--ui); color: var(--texto); white-space: nowrap; overflow: hidden; }
.pe-seg button .bi { color: var(--media); }
.pe-seg button[aria-pressed=true] { border-color: var(--cred); background: var(--velo); color: var(--cred); }
.pe-seg button[aria-pressed=true] .bi { color: var(--cred); }
.pe-seg button > i:not(.bi) { width: 8px; height: 8px; border-radius: 50%; background: var(--media); }
.pe-seg .s-act > i:not(.bi) { background: var(--verde); }
.pe-seg .s-act[aria-pressed=true] { border-color: var(--verde); background: var(--verdev); color: var(--verde); }
.pe-seg .s-ina[aria-pressed=true] { border-color: var(--media); background: var(--niebla); color: var(--texto); }
.pe-switch { display: inline-flex; align-items: center; gap: 10px; height: 34px; padding: 0 6px 0 12px; border-radius: 99px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--media); }
.pe-switch i { position: relative; width: 36px; height: 20px; border-radius: 99px; background: #c5d0cd; transition: background .2s; }
.pe-switch i:after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .25); transition: transform .2s var(--e); }
.pe-switch[aria-checked=true] { color: var(--cred); border-color: #b9d6d0; }
.pe-switch[aria-checked=true] i { background: var(--cred); }
.pe-switch[aria-checked=true] i:after { transform: translateX(16px); }
.pe-acceso { display: flex; flex-direction: column; gap: 12px; }
.pe-usuario { display: flex; height: 40px; border: 1px solid var(--linea); border-radius: 10px; overflow: hidden; background: var(--papel); transition: border-color .15s, box-shadow .15s; }
.pe-usuario:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.pe-campo.mal .pe-usuario { border-color: var(--rosa); }
.pe-usuario input:focus, .pe-dinero input:focus { outline: 0 !important; box-shadow: none !important; }
.pe-usuario input { flex: 1; min-width: 0; height: 100%; border: 0 !important; box-shadow: none !important; border-radius: 0; font-family: var(--mono); }
.pe-usuario span { display: flex; align-items: center; padding: 0 10px; background: var(--niebla); border-left: 1px solid var(--linea); font: 500 12.5px var(--mono); color: var(--media); }
.pe-sug { display: flex; flex-wrap: wrap; gap: 5px; }
.pe-sug button { height: 26px; padding: 0 9px; border-radius: 99px; border: 1px solid var(--linea); background: var(--papel); font: 500 12px var(--mono); color: var(--texto); }
.pe-sug button.on, .pe-sug button:hover { border-color: var(--cred); color: var(--cred); background: var(--velo); }
.pe-pass { position: relative; display: flex; gap: 6px; }
.pe-pass input { flex: 1; min-width: 0; padding-right: 72px; letter-spacing: .08em; }
.pe-pass > button:not(.pe-gen) { position: absolute; top: 5px; width: 30px; height: 30px; border: 0; border-radius: 8px; background: none; color: var(--media); display: grid; place-items: center; }
.pe-pass > button:nth-of-type(1) { right: 142px; }
.pe-pass > button:nth-of-type(2) { right: 110px; }
.pe-pass > button:not(.pe-gen):hover { background: var(--niebla); color: var(--cred); }
.pe-gen { flex: none; height: 40px; display: inline-flex; align-items: center; gap: 6px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--linea); background: var(--papel); font: 600 13px var(--ui); color: var(--cred); }
.pe-gen:hover { background: var(--velo); }
.pe-fuerza { display: flex; align-items: center; gap: 4px; }
.pe-fuerza i { flex: 1; max-width: 46px; height: 5px; border-radius: 99px; background: var(--linea2); }
.pe-fuerza i.f1 { background: var(--rosa); } .pe-fuerza i.f2 { background: #d9a24a; } .pe-fuerza i.f3 { background: var(--teal); } .pe-fuerza i.f4 { background: var(--verde); }
.pe-fuerza span { margin-left: 6px; font-size: 12px; color: var(--media); }
.pe-nota { display: flex; gap: 8px; padding: 10px 12px; border-radius: 10px; background: var(--niebla); font-size: 12.5px; color: var(--texto); }
.pe-nota .bi { color: var(--cred); }
.pe-dinero { display: flex; align-items: center; height: 40px; border: 1px solid var(--linea); border-radius: 10px; background: var(--papel); overflow: hidden; transition: border-color .15s, box-shadow .15s; }
.pe-dinero:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.pe-dinero b { padding-left: 10px; font: 600 14px var(--mono); color: var(--media); }
.pe-dinero input { flex: 1; min-width: 0; height: 100%; border: 0 !important; box-shadow: none !important; padding: 0 6px; }
.pe-dinero span { padding: 0 10px; font: 500 11.5px var(--mono); color: var(--media); }
.pe-rv-f { display: flex; align-items: center; gap: 8px; padding: 12px 18px; border-top: 1px solid var(--linea2); background: var(--papel); }
.pe-msg { flex: 1; min-width: 0; font-size: 12.5px; color: var(--media); line-height: 1.3; }
.pe-msg.mal { color: var(--rosa); font-weight: 600; }

/* Botones */
.pe-btn { height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border-radius: 11px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); text-decoration: none; white-space: nowrap; transition: background .15s, border-color .15s; }
.pe-btn:hover { background: #f7faf9; color: var(--tinta); }
.pe-btn .bi { color: var(--cred); }
.pe-prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.pe-prim .bi { color: var(--foil); }
.pe-prim:hover { background: var(--cred2); color: #fff; }
.pe-prim:disabled { opacity: .6; }
.pe-prim kbd { border-color: rgba(255, 255, 255, .3); color: #cfe; }
.pe-ico { width: 40px; padding: 0; }
.pe-borrar .bi { color: var(--rosa); }
.pe-borrar { border-color: #f3c6cf; }
.pe-borrar:hover { background: var(--rosav); }
.pe-borrar:disabled { opacity: .35; cursor: not-allowed; }
.pe-acc { display: flex; gap: 8px; }
.pe-acc .pe-prim { flex: 1; }

/* Captura: lo demás se atenúa */
.pe.editando .pe-lista, .pe.editando .pe-cab { opacity: .35; filter: saturate(.6); pointer-events: none; }

/* Captura a lo ancho: como al crear un paciente, el gafete ocupa toda la vista */
@container (min-width: 640px) {
    .pe.editando .pe-esc { grid-template-columns: minmax(0, 1fr); }
    .pe.editando .pe-lista, .pe.editando .pe-acc { display: none; }
    .pe.editando .pe-cab { display: none; }
    .pe.editando .pe-lado { position: static; }
    .pe.editando .pe-cmd { opacity: .35; pointer-events: none; }
    .pe-gafete.volteado .pe-gafete-in { height: 1020px; }
    .pe.editando .pe-rv-k { padding: 20px 28px 8px; font-size: 13px; }
    .pe.editando .pe-rv-c { display: grid; grid-template-columns: minmax(0, 1fr); gap: 14px; padding: 4px 28px 18px; align-content: start; }
    .pe.editando .pe-rv-a, .pe.editando .pe-rv-b { display: flex; flex-direction: column; gap: 12px; min-width: 0; }
    .pe.editando .pe-rv-f { padding: 14px 28px; }
}
@container (min-width: 1000px) {
    .pe-gafete.volteado .pe-gafete-in { height: 790px; }
    .pe.editando .pe-rv-c { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 0 32px; }
    .pe.editando .pe-rv-b { padding-left: 32px; margin-left: -1px; border-left: 1px solid var(--linea2); }
}
@container (max-width: 459px) {
    .pe-dos { grid-template-columns: minmax(0, 1fr); }
    .pe-seg-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pe-dark kbd, .pe-cmd > kbd { display: none; }
}

@media (prefers-reduced-motion: reduce) {
    .pe-gafete-in { transition: none; }
    .pe-gafete.volteado .pe-gafete-in { transform: none; }
    .pe-gafete.volteado .pe-frente { visibility: hidden; }
    .pe-reverso { transform: none; visibility: hidden; }
    .pe-gafete.volteado .pe-reverso { visibility: visible; }
}
</style>

<script>
function modPersonal() {
    const D = JSON.parse(document.getElementById('pe-datos').textContent || '{}');
    const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const aviso = (m, i) => { if (window.notificar) window.notificar(m, i || 'success'); };
    const vacio = () => ({ id: null, nombre: '', apellido: '', telefono: '', rut: '', direccion: '', rol_id: '', especialidad: '', turno: 'Completo', estado: 'Activo', acceso: false, username: '', password: '', password2: '', salario_diario: '', tipo_pago: 'QUINCENAL' });

    return {
        P: D.personal || [],
        ROLES: D.roles || [],
        PUEDE: D.puede || {},
        R: D.rutas || {},
        DOMINIO: D.dominio || 'meditrack.com',
        CLINICA: D.clinica || 'MediTrack',
        TURNOS: [
            { v: 'Mañana', i: 'bi-sunrise' }, { v: 'Tarde', i: 'bi-sunset' },
            { v: 'Noche', i: 'bi-moon-stars' }, { v: 'Completo', i: 'bi-clock' },
        ],
        PAGOS: [{ v: 'SEMANAL', t: 'Semanal', d: 7 }, { v: 'QUINCENAL', t: 'Quincenal', d: 15 }, { v: 'MENSUAL', t: 'Mensual', d: 30 }],
        INDICADORES: [
            { k: 'activos', t: 'Activos' }, { k: 'inactivos', t: 'Inactivos' },
            { k: 'acceso', t: 'Con acceso' }, { k: 'hoy', t: 'Atienden hoy' },
        ],

        sel: null, editando: false, guardando: false,
        q: '', filtro: '', turno: '',
        form: vacio(), errores: {}, mensajeForm: '', mensajeMal: false,
        verPass: false, passGenerada: false, usuarioManual: false,

        init() {
            this.$watch('sel', () => window.mtRefresca && mtRefresca('.pe-frente .pe-fr'));
            const u = new URLSearchParams(location.search);
            this.sel = this.P.find(p => String(p.id) === u.get('p')) || this.P.find(p => p.soyYo) || this.P[0] || null;
            if (u.get('nuevo') && this.PUEDE.crear) this.$nextTick(() => this.nueva());
            else if (u.get('editar') && this.sel && this.PUEDE.editar) this.$nextTick(() => this.editar(this.sel));
            if (u.has('nuevo') || u.has('editar')) history.replaceState(null, '', location.pathname + (this.sel ? '?p=' + this.sel.id : ''));
            if (D.exito) setTimeout(() => aviso(D.exito, 'success'), 300);
            if (D.error) setTimeout(() => aviso(D.error, 'error'), 300);
            this.$watch('form.nombre', () => this.sugerirUsuario());
            this.$watch('form.apellido', () => this.sugerirUsuario());
        },

        // ----- Lista -----
        cumple(p, k) {
            if (k === 'activos') return p.activo;
            if (k === 'inactivos') return !p.activo;
            if (k === 'acceso') return p.acceso;
            if (k === 'hoy') return p.citasHoy > 0;
            return true;
        },
        contar(k) { return this.P.filter(p => this.cumple(p, k)).length; },
        get filtrados() {
            const q = norm(this.q);
            return this.P.filter(p => (!this.filtro || this.cumple(p, this.filtro)) && (!this.turno || p.turno === this.turno)
                && (!q || norm([p.nombre, p.rol, p.especialidad, p.email, p.telefono, p.cedula, p.folio].join(' ')).includes(q)));
        },
        get grupos() {
            const m = {};
            this.filtrados.forEach(p => { const k = p.rol || 'Sin cargo'; (m[k] = m[k] || []).push(p); });
            return Object.keys(m).sort((a, b) => (a === 'Sin cargo') - (b === 'Sin cargo') || m[b].length - m[a].length || a.localeCompare(b))
                .map(rol => ({ rol, items: m[rol] }));
        },
        limpiar() { this.q = ''; this.filtro = ''; this.turno = ''; },
        seleccionar(p) {
            if (this.editando) return;
            this.sel = p;
            history.replaceState(null, '', location.pathname + '?p=' + p.id);
            if (window.innerWidth < 1100) this.$nextTick(() => { const g = document.querySelector('.pe-gafete'); if (g) g.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' }); });
        },

        // ----- Formulario -----
        nueva() {
            if (!this.PUEDE.crear || this.editando) return;
            this.form = vacio();
            if (this.ROLES.length === 1) this.form.rol_id = this.ROLES[0].id;
            this.abrir(false);
        },
        editar(p) {
            if (!p || !this.PUEDE.editar || this.editando) return;
            this.form = Object.assign(vacio(), {
                id: p.id, nombre: p.pila || p.nombre, apellido: p.apellido || '', telefono: p.telefono || '', rut: p.cedula || '',
                direccion: p.direccion || '', rol_id: p.rol_id || '', especialidad: p.especialidad || '', turno: p.turno || 'Completo',
                estado: p.estado || 'Activo', acceso: !!p.acceso, username: p.username || '',
                salario_diario: p.salario ? String(p.salario) : '', tipo_pago: p.tipo_pago || 'QUINCENAL',
            });
            this.abrir(true);
        },
        abrir(manual) {
            this.errores = {}; this.mensajeForm = this.form.id ? 'Ctrl + Enter guarda los cambios' : 'Ctrl + Enter da de alta'; this.mensajeMal = false;
            this.verPass = false; this.passGenerada = false; this.usuarioManual = manual;
            this.editando = true;
            this.$nextTick(() => {
                const m = document.querySelector('.pe').closest('.overflow-y-auto');
                if (m) m.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
                setTimeout(() => this.$refs.campoNombre && this.$refs.campoNombre.focus({ preventScroll: true }), reduce ? 0 : 500);
            });
        },
        cancelar() { this.editando = false; this.guardando = false; },
        get tieneCuenta() { const p = this.form.id ? this.P.find(x => x.id === this.form.id) : null; return !!(p && p.acceso); },
        get nombreRol() { const r = this.ROLES.find(r => r.id === this.form.rol_id); return r ? r.nombre : ''; },
        get esMedico() { return /medic|doctor|dr\b|dra\b/.test(norm(this.nombreRol)) || !!this.form.especialidad; },
        alternarAcceso() { this.form.acceso = !this.form.acceso; if (this.form.acceso && !this.form.username) this.sugerirUsuario(true); },
        get sugerencias() {
            const nom = norm(this.form.nombre).replace(/\b(dr|dra|enf|lic)\.?\s+/g, '').replace(/[^a-z ]/g, '').split(/\s+/).filter(Boolean);
            const ape = norm(this.form.apellido).replace(/[^a-z ]/g, '').split(/\s+/).filter(Boolean);
            if (!nom.length || !ape.length) return [];
            const n = nom[0], a = ape[0], a2 = ape[1] || '';
            return [...new Set([n[0] + a, n + '_' + a, n[0] + a + (a2 ? a2[0] : ''), n + a[0]])].filter(s => s.length >= 3).slice(0, 3);
        },
        sugerirUsuario(forzar) { if ((forzar || !this.usuarioManual) && this.form.acceso && !this.tieneCuenta) this.form.username = this.sugerencias[0] || this.form.username; },
        get fuerza() {
            const p = this.form.password; let n = 0;
            if (p.length >= 8) n++; if (/[a-z]/.test(p) && /[A-Z]/.test(p)) n++; if (/\d/.test(p)) n++; if (/[^A-Za-z0-9]/.test(p)) n++;
            const t = ['Muy débil', 'Débil: agrega mayúsculas, números o símbolos', 'Aceptable', 'Buena', 'Muy buena'];
            return { nivel: Math.max(p ? 1 : 0, n), texto: t[n], clase: 'f' + Math.max(1, n) };
        },
        generarPass() {
            const g = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnpqrstuvwxyz', '23456789', '#$%&*@!?'];
            const azar = s => s[crypto.getRandomValues(new Uint32Array(1))[0] % s.length];
            const c = g.map(azar), todos = g.join('');
            while (c.length < 8) c.push(azar(todos));
            for (let i = c.length - 1; i > 0; i--) { const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1); [c[i], c[j]] = [c[j], c[i]]; }
            this.form.password = this.form.password2 = c.join('');
            this.verPass = true; this.passGenerada = true; this.errores.password = '';
        },
        async copiarPass() { try { await navigator.clipboard.writeText(this.form.password); aviso('Contraseña copiada'); } catch (e) { aviso('No se pudo copiar', 'warning'); } },
        get porPeriodo() { const t = this.PAGOS.find(t => t.v === this.form.tipo_pago) || this.PAGOS[1]; return (Number(this.form.salario_diario) || 0) * t.d; },

        validar() {
            const f = this.form, e = {};
            if (!f.nombre.trim()) e.nombre = 'Escribe el nombre.';
            if (!f.rol_id) e.rol_id = 'Elige el cargo o rol.';
            if (f.telefono && f.telefono.length !== 10) e.telefono = 'El teléfono debe tener 10 dígitos.';
            if (f.salario_diario && isNaN(Number(f.salario_diario))) e.salario_diario = 'Escribe un número.';
            if (f.acceso) {
                if (!/^[a-z0-9_-]{3,40}$/.test(f.username)) e.username = 'Usa al menos 3 letras minúsculas, números, guion o guion bajo.';
                if (!this.tieneCuenta || f.password) {
                    if (f.password.length !== 8) e.password = 'La contraseña debe tener exactamente 8 caracteres.';
                    else if (f.password !== f.password2) e.password = 'Las contraseñas no coinciden.';
                }
            }
            this.errores = e;
            return Object.keys(e).length === 0;
        },
        async guardar() {
            if (this.guardando) return;
            if (!this.validar()) { this.mensajeForm = 'Revisa los campos marcados.'; this.mensajeMal = true; window.mtSacudir && mtSacudir('.pe-rv-c'); return; }
            const f = this.form;
            const datos = {
                nombre: this.mayus(f.nombre), apellido: this.mayus(f.apellido), telefono: f.telefono, rut: f.rut, direccion: f.direccion,
                rol_id: f.rol_id, especialidad: this.esMedico ? f.especialidad : '', turno: f.turno, estado: f.estado,
                requiere_acceso: f.acceso ? 1 : 0, username: f.username, salario_diario: f.salario_diario || 0, tipo_pago: f.tipo_pago,
            };
            if (f.acceso && f.password) { datos.password = f.password; datos.password_confirmation = f.password2; }
            this.guardando = true; this.mensajeMal = false; this.mensajeForm = 'Guardando…';
            try {
                const res = await fetch(f.id ? this.R.base + '/' + f.id : this.R.base, {
                    method: f.id ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(datos),
                });
                const r = await res.json().catch(() => ({}));
                if (res.status === 422) {
                    const e = {}, mapa = { email: 'username', password_confirmation: 'password' };
                    Object.entries(r.errors || {}).forEach(([k, v]) => { e[mapa[k] || k] = v[0]; });
                    this.errores = e;
                    this.mensajeForm = Object.values(e)[0] || 'Revisa los datos.'; this.mensajeMal = true;
                    window.mtSacudir && mtSacudir('.pe-rv-c');
                    return;
                }
                if (!res.ok) throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (r.message || 'No se pudo guardar.'));
                const p = r.personal;
                const i = this.P.findIndex(x => x.id === p.id);
                if (i >= 0) this.P.splice(i, 1, Object.assign({}, this.P[i], p, { citasHoy: this.P[i].citasHoy, atendidasMes: this.P[i].atendidasMes }));
                else this.P.push(p);
                this.sel = this.P.find(x => x.id === p.id);
                this.editando = false;
                history.replaceState(null, '', location.pathname + '?p=' + p.id);
                aviso(r.mensaje || 'Guardado', 'success');
                this.$nextTick(() => window.mtDestello && mtDestello('.pe-fila.sel'));
            } catch (err) {
                this.mensajeForm = err.message || 'Error de conexión.'; this.mensajeMal = true;
            } finally {
                this.guardando = false;
            }
        },

        // ----- Acciones del gafete -----
        async copiar(t) { try { await navigator.clipboard.writeText(t); aviso('Correo copiado: ' + t); } catch (e) { aviso('No se pudo copiar', 'warning'); } },
        eliminar(p) {
            if (!p || p.soyYo || !window.Swal) return;
            Swal.fire({
                title: '¿Eliminar a ' + p.nombre + '?',
                text: p.acceso ? 'También se eliminará su cuenta (' + p.email + '). Esta acción no se puede deshacer.' : 'Esta acción no se puede deshacer.',
                icon: 'warning', showCancelButton: true, reverseButtons: true, focusCancel: true,
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#c8102e', cancelButtonColor: '#56625f',
                showLoaderOnConfirm: true, allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        const res = await fetch(this.R.base + '/' + p.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': D.csrf, 'Accept': 'application/json' } });
                        const r = await res.json().catch(() => ({}));
                        if (!res.ok || r.success === false) throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (r.message || 'No se pudo eliminar.'));
                        return r;
                    } catch (e) { Swal.showValidationMessage(e.message || 'Error de conexión'); }
                },
            }).then(res => {
                if (!res.isConfirmed) return;
                const quitar = () => {
                    this.P = this.P.filter(x => x.id !== p.id);
                    this.sel = this.filtrados[0] || this.P[0] || null;
                };
                window.mtQuitar ? mtQuitar('.pe-fila.sel', quitar) : quitar();
                aviso((res.value && res.value.message) || 'Registro eliminado.', 'success');
            });
        },

        // ----- Teclado -----
        tecla(e) {
            const t = e.target.tagName, escribiendo = ['INPUT', 'TEXTAREA', 'SELECT'].includes(t) || e.target.isContentEditable;
            if (document.querySelector('.swal2-container')) return;
            if (!this.editando && this.lector(e, escribiendo)) return;
            if (this.editando) {
                if (e.key === 'Escape') { e.preventDefault(); this.cancelar(); }
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.guardar(); }
                return;
            }
            if (escribiendo || e.ctrlKey || e.metaKey || e.altKey) return;
            if (e.key === '/') { e.preventDefault(); this.$refs.buscar.focus(); }
            else if (e.key === 'n' || e.key === 'N') { e.preventDefault(); this.nueva(); }
            else if ((e.key === 'e' || e.key === 'E') && this.sel) { e.preventDefault(); this.editar(this.sel); }
            else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                const l = this.grupos.flatMap(g => g.items); if (!l.length) return;
                e.preventDefault();
                const i = l.findIndex(p => this.sel && p.id === this.sel.id);
                this.seleccionar(l[Math.max(0, Math.min(l.length - 1, i + (e.key === 'ArrowDown' ? 1 : -1)))]);
            }
        },

        // Lector de códigos: teclea el folio muy rápido y termina con Enter (también si cae en el buscador)
        _lec: '', _lecT: 0,
        lector(e, escribiendo) {
            const ahora = performance.now();
            if (e.key === 'Enter') {
                const leido = (escribiendo && e.target === this.$refs.buscar ? this.q : this._lec).trim().toUpperCase();
                this._lec = '';
                const m = leido.match(/^MT-?(\d{1,6})$/);
                if (!m) return false;
                const p = this.P.find(x => x.folio === 'MT-' + m[1].padStart(4, '0'));
                e.preventDefault();
                if (!p) { if (window.notificar) window.notificar('Ningún gafete con el folio ' + leido, 'warning'); return true; }
                this.limpiar(); this.seleccionar(p);
                if (escribiendo) e.target.blur();
                if (window.notificar) window.notificar('Gafete leído: ' + p.nombre, 'success');
                return true;
            }
            if (escribiendo || e.key.length !== 1 || e.ctrlKey || e.metaKey || e.altKey) return false;
            this._lec = (ahora - this._lecT > 80 ? '' : this._lec) + e.key;
            this._lecT = ahora;
            // Mientras llegan teclas de lector (rápidas), no se disparan los atajos N, E ni /
            return this._lec.length > 1;
        },

        // ----- Formatos y dibujos -----
        mayus(s) { return String(s || '').trim().replace(/\s+/g, ' ').toUpperCase(); },
        iniciales(p) {
            const n = String((p && (p.pila || p.nombre)) || '').replace(/\b(DR|DRA|ENF|LIC|ING)\.?\s+/gi, '').trim();
            const a = String((p && p.apellido) || '').trim();
            return ((n[0] || '') + (a[0] || (n.split(/\s+/)[1] || '')[0] || '')).toUpperCase() || '?';
        },
        turnoDe(v) { return this.TURNOS.find(t => t.v === v) || this.TURNOS[3]; },
        formatoTel(t) { return t && t.length === 10 ? t.slice(0, 2) + ' ' + t.slice(2, 6) + ' ' + t.slice(6) : t; },
        dinero(v) { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0); },
        iconoRol(n) {
            const s = norm(n);
            if (/medic|doctor/.test(s)) return 'bi-heart-pulse';
            if (/enferm/.test(s)) return 'bi-bandaid';
            if (/recep/.test(s)) return 'bi-person-workspace';
            if (/admin/.test(s)) return 'bi-shield-lock';
            if (/farmac/.test(s)) return 'bi-capsule';
            if (/caj|contab|factur/.test(s)) return 'bi-cash-coin';
            return 'bi-person-badge';
        },
        roseta(seed, size) { return typeof mtRoseta === 'function' ? mtRoseta(seed, size) : ''; },
        guilloche(seed) { return typeof mtGuilloche === 'function' ? mtGuilloche(seed) : ''; },
        // Código de barras Code 128 (juego B) con el folio: lo lee cualquier lector o app de códigos.
        // Un lector USB que «teclea» el folio y Enter en esta pantalla selecciona a la persona.
        barras(texto) {
            const P = ['212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'];
            const t = String(texto || '').replace(/[^\x20-\x7E]/g, '');
            if (!t) return '';
            const vals = [104], q = 10;
            for (const ch of t) vals.push(ch.charCodeAt(0) - 32);
            let suma = 104;
            for (let i = 1; i < vals.length; i++) suma += vals[i] * i;
            vals.push(suma % 103, 106);
            let x = q, d = '';
            vals.forEach(v => {
                const w = P[v];
                for (let i = 0; i < w.length; i++) {
                    const n = Number(w[i]);
                    if (i % 2 === 0) d += 'M' + x + ' 0h' + n + 'v40h-' + n + 'z';
                    x += n;
                }
            });
            const ancho = x + q;
            return '<svg viewBox="0 0 ' + ancho + ' 40" width="' + ancho * 2 + '" height="40" preserveAspectRatio="none" shape-rendering="crispEdges" role="img" aria-label="Código de barras ' + t + '"><path d="' + d + '" fill="#0b1e1c"/></svg>';
        },
    };
}
</script>
@endverbatim
@endsection
