@extends('layouts.admin')

{{--
    ROLES Y PERMISOS · MediTrack
    A la izquierda los roles; a la derecha la matriz de permisos del rol elegido, agrupada como el menú.
    Editar y crear se hacen en la misma matriz y se guardan por AJAX (RolController responde JSON).
    La vista «Comparar» pone todos los roles lado a lado.
--}}

@section('content')
<div class="rl" x-data="modRoles()" x-cloak :class="{ 'editando': editando }" @keydown.window="tecla($event)">

    <!-- BÚSQUEDA Y ALTA -->
    <header class="rl-top">
        <label class="rl-cmd">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="rl-sr">Buscar</span>
            <input type="search" x-ref="buscar" x-model.debounce.150ms="q" autocomplete="off" placeholder="Busca un rol o un módulo">
            <kbd aria-hidden="true">/</kbd>
        </label>
        <a class="rl-btn" x-show="R.personal" :href="R.personal"><i class="bi bi-person-badge"></i> Ver personal</a>
        <button type="button" class="rl-dark" x-show="PUEDE.crear" @click="nuevo()">Nuevo rol <kbd>N</kbd></button>
    </header>

    <!-- CABECERA -->
    <section class="rl-cab">
        <div class="rl-g">
            <b class="t" x-text="roles.length"></b>
            <span><em x-text="roles.length === 1 ? 'rol en la clínica' : 'roles en la clínica'"></em><span x-text="MOD.length + ' módulos · ' + totalPersonas + (totalPersonas === 1 ? ' persona con rol' : ' personas con rol')"></span></span>
        </div>
        <ul class="rl-ley" aria-label="Acciones">
            <template x-for="a in ACC" :key="'l' + a.k"><li :class="'a-' + a.k"><i class="bi" :class="a.i"></i><span x-text="a.t"></span></li></template>
        </ul>
        <div class="rl-seg" role="group" aria-label="Vista">
            <button type="button" :aria-pressed="(vista === 'rol').toString()" @click="vista = 'rol'"><i class="bi bi-shield-lock"></i> Por rol</button>
            <button type="button" :aria-pressed="(vista === 'comparar').toString()" @click="if (!editando) vista = 'comparar'" :disabled="editando"><i class="bi bi-layout-three-columns"></i> Comparar</button>
        </div>
    </section>

    <!-- VISTA POR ROL -->
    <section class="rl-esc mt-aparece" x-show="vista === 'rol'">
        <!-- Lista de roles -->
        <aside class="rl-lista" aria-label="Roles">
            <template x-for="r in rolesFiltrados" :key="'r' + r.id">
                <button type="button" class="rl-rol" :class="{ 'sel': sel && sel.id === r.id }" @click="elegir(r)">
                    <svg class="rl-anillo" viewBox="0 0 40 40" aria-hidden="true">
                        <circle cx="20" cy="20" r="16" class="pista"/>
                        <circle cx="20" cy="20" r="16" class="valor" :style="'stroke-dashoffset:' + (100.5 - 100.5 * cobertura(r) / 100)"/>
                    </svg>
                    <span class="rl-rol-n">
                        <b x-text="r.nombre"></b>
                        <small x-text="(r.total ? 'Acceso total' : modulosCon(r) + ' de ' + MOD.length + ' módulos') + ' · ' + r.usuarios + (r.usuarios === 1 ? ' persona' : ' personas')"></small>
                    </span>
                    <em x-show="esMio(r)">Tu rol</em>
                </button>
            </template>
            <p class="rl-vacio" x-show="!rolesFiltrados.length" x-text="roles.length ? 'Ningún rol coincide.' : 'Aún no hay roles.'"></p>
        </aside>

        <!-- Rol elegido y su matriz -->
        <div class="rl-panel" x-show="sel || editando">
            <header class="rl-ph">
                <div class="rl-ph-a">
                    <p class="rl-k" x-text="editando ? (form.id ? 'Editando rol' : 'Rol nuevo') : 'Rol'"></p>
                    <template x-if="!editando">
                        <h2 x-text="sel ? sel.nombre : ''"></h2>
                    </template>
                    <template x-if="editando">
                        <label class="rl-nombre" :class="{ 'mal': errorNombre }">
                            <span class="rl-sr">Nombre del rol</span>
                            <input type="text" x-ref="campoNombre" x-model="form.nombre" maxlength="100" placeholder="Nombre del rol, por ejemplo Recepcionista" @input="errorNombre = ''">
                            <small x-show="errorNombre" x-text="errorNombre"></small>
                        </label>
                    </template>
                    <div class="rl-gente" x-show="!editando && sel">
                        <template x-for="p in (sel ? sel.personas.slice(0, 6) : [])" :key="'pp' + p.nombre">
                            <a :href="p.personal_id && R.personal ? R.personal + '?p=' + p.personal_id : null" :class="{ 'sin': !p.personal_id }"><span x-html="roseta(p.nombre, 22)"></span><span x-text="p.nombre"></span></a>
                        </template>
                        <span x-show="sel && sel.personas.length > 6" class="rl-mas t" x-text="'+' + (sel ? sel.personas.length - 6 : 0)"></span>
                        <span x-show="sel && !sel.personas.length" class="rl-nadie">Nadie tiene este rol todavía.</span>
                    </div>
                </div>
                <div class="rl-ph-b" x-show="!editando && sel">
                    <button type="button" class="rl-btn rl-prim" x-show="PUEDE.editar && sel && !sel.total" @click="editar(sel)"><i class="bi bi-pencil"></i> Editar permisos <kbd>E</kbd></button>
                    <button type="button" class="rl-btn" x-show="PUEDE.crear" @click="duplicar(sel)"><i class="bi bi-copy"></i> Duplicar</button>
                    <button type="button" class="rl-btn rl-ico" x-show="PUEDE.editar && sel && sel.total" @click="editar(sel)" title="Cambiar el nombre" aria-label="Cambiar el nombre"><i class="bi bi-pencil"></i></button>
                    <button type="button" class="rl-btn rl-ico rl-borrar" x-show="PUEDE.eliminar" @click="eliminar(sel)" :disabled="sel && esMio(sel)" :title="sel && esMio(sel) ? 'No puedes eliminar tu propio rol' : 'Eliminar rol'" aria-label="Eliminar rol"><i class="bi bi-trash3"></i></button>
                </div>
            </header>

            <!-- Medidor de permisos -->
            <div class="rl-medidor" x-show="!(sel && sel.total && !editando)">
                <div class="rl-barra"><i :style="'width:' + porcentajeActual + '%'"></i></div>
                <span class="t" x-text="permisosActuales + ' de ' + (MOD.length * 4) + ' permisos · ' + modulosActuales + ' de ' + MOD.length + ' módulos'"></span>
            </div>
            <p class="rl-total" x-show="sel && sel.total && !editando">
                <i class="bi bi-shield-fill-check"></i>
                <span><b>Acceso total automático.</b> El sistema deja entrar a este rol a todos los módulos sin revisar la tabla de permisos.</span>
            </p>

            <!-- Atajos al editar -->
            <div class="rl-atajos" x-show="editando">
                <span>Empezar desde</span>
                <button type="button" @click="plantilla('todo')"><i class="bi bi-check2-all"></i> Acceso total</button>
                <button type="button" @click="plantilla('lectura')"><i class="bi bi-eye"></i> Solo ver</button>
                <button type="button" @click="plantilla('nada')"><i class="bi bi-x-lg"></i> Nada</button>
                <div class="rl-copiar" @click.outside="popCopiar = false">
                    <button type="button" @click="popCopiar = !popCopiar" :aria-expanded="popCopiar.toString()"><i class="bi bi-copy"></i> Copiar de… <i class="bi bi-chevron-down"></i></button>
                    <div class="rl-pop" x-show="popCopiar" x-cloak>
                        <template x-for="r in roles.filter(x => x.id !== form.id)" :key="'cp' + r.id">
                            <button type="button" @click="copiarDe(r)"><b x-text="r.nombre"></b><small x-text="r.total ? 'Acceso total' : modulosCon(r) + ' módulos'"></small></button>
                        </template>
                    </div>
                </div>
            </div>
            <p class="rl-aviso" x-show="editando && seQuitaRoles"><i class="bi bi-exclamation-triangle-fill"></i> Es tu rol: sin «Ver» en Roles ya no podrás entrar a esta pantalla.</p>

            <!-- Matriz -->
            <div class="rl-matriz" :class="{ 'lectura': !editando }">
                <div class="rl-mh">
                    <span>Módulo</span>
                    <template x-for="a in ACC" :key="'h' + a.k">
                        <button type="button" :class="'a-' + a.k" @click="columna(a.k)" :disabled="!editando" :title="editando ? 'Marcar o quitar toda la columna' : ''" x-text="a.t"></button>
                    </template>
                </div>
                <template x-for="g in gruposVisibles" :key="'g' + g.nombre">
                    <div class="rl-grupo">
                        <button type="button" class="rl-gh" @click="grupo(g)" :disabled="!editando">
                            <span x-text="g.nombre"></span>
                            <small class="t" x-text="g.mods.filter(m => tieneAcceso(m.id)).length + ' de ' + g.mods.length"></small>
                        </button>
                        <template x-for="m in g.mods" :key="'m' + m.id">
                            <div class="rl-fila" :class="{ 'on': tieneAcceso(m.id) }">
                                <button type="button" class="rl-mod" @click="fila(m.id)" :disabled="!editando" :title="editando ? 'Marcar o quitar todo el módulo' : ''">
                                    <i class="bi" :class="m.icono"></i>
                                    <span><b x-text="m.nombre === 'POS' ? 'Terminal POS' : m.nombre"></b><small x-text="m.desc"></small></span>
                                </button>
                                <template x-for="a in ACC" :key="'c' + m.id + a.k">
                                    <button type="button" class="rl-p" :class="'a-' + a.k" :aria-pressed="valor(m.id, a.k).toString()" :disabled="!editando"
                                            @click="alternar(m.id, a.k)" :aria-label="a.t + ' en ' + m.nombre"><i class="bi" :class="a.i"></i></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
                <p class="rl-vacio" x-show="!gruposVisibles.length">Ningún módulo coincide con la búsqueda.</p>
            </div>

            <!-- Barra para guardar -->
            <footer class="rl-pie" x-show="editando">
                <span class="rl-msg" :class="{ 'mal': mensajeMal }" x-text="mensaje"></span>
                <button type="button" class="rl-btn" @click="cancelar()">Cancelar</button>
                <button type="button" class="rl-btn rl-prim" @click="guardar()" :disabled="guardando" x-text="guardando ? 'Guardando…' : (form.id ? 'Guardar permisos' : 'Crear rol')"></button>
            </footer>
        </div>

        <div class="rl-panel rl-panel-vacio" x-show="!sel && !editando">
            <b>Sin roles todavía</b>
            <p>Crea el primer rol para dar permisos al personal.</p>
        </div>
    </section>

    <!-- VISTA COMPARAR -->
    <section class="rl-comp mt-aparece" x-show="vista === 'comparar'" x-cloak>
        <div class="rl-comp-in">
            <table>
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <template x-for="r in rolesFiltrados" :key="'ch' + r.id">
                            <th><button type="button" @click="vista = 'rol'; elegir(r)" x-text="r.nombre"></button><small class="t" x-text="r.total ? 'total' : cobertura(r) + '%'"></small></th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="m in MOD" :key="'cm' + m.id">
                        <tr>
                            <th><i class="bi" :class="m.icono"></i> <span x-text="m.nombre === 'POS' ? 'Terminal POS' : m.nombre"></span></th>
                            <template x-for="r in rolesFiltrados" :key="'cc' + r.id + '-' + m.id">
                                <td>
                                    <span class="rl-mini">
                                        <template x-for="a in ACC" :key="'cd' + r.id + m.id + a.k">
                                            <i :class="['a-' + a.k, (r.total || (r.permisos[m.id] && r.permisos[m.id][a.k])) ? 'on' : ''].join(' ')" :title="a.t"></i>
                                        </template>
                                    </span>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script type="application/json" id="rl-datos">{!! json_encode(array_merge($datosRoles ?? [], ['csrf' => csrf_token(), 'exito' => session('success'), 'errores' => $errors->all()]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

@verbatim
<style>
.rl {
    --tinta: #0b1e1c; --texto: #2c3d3a; --media: #56625f; --linea: #dbe4e1; --linea2: #edf2f1; --niebla: #eef3f1; --papel: #fff;
    --cred: #0b5a54; --cred2: #073f3b; --foil: #a6efe2; --teal: #0e7c72; --velo: #e2efec;
    --rosa: #c8102e; --rosav: #fdecef; --ambar: #9a5800; --ambarv: #fdf3e1; --verde: #08734f; --verdev: #e5f4ee;
    --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    container-type: inline-size; font: 450 14px/1.45 var(--ui); color: var(--texto);
    display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
}
.rl * { box-sizing: border-box; }
.rl :focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
.rl button { cursor: pointer; font-family: var(--ui); }
.rl button:disabled { cursor: default; }
.rl .t { font-variant-numeric: tabular-nums; }
.rl h2, .rl p { margin: 0; }
.rl ul { margin: 0; padding: 0; list-style: none; }
.rl-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.rl kbd { font: 600 11px var(--mono); border: 1px solid var(--linea); border-radius: 6px; padding: 1px 6px; color: var(--media); background: transparent; }
[x-cloak] { display: none !important; }

/* Colores por acción */
.rl .a-ver { --c: var(--cred); --cv: var(--velo); }
.rl .a-crear { --c: var(--verde); --cv: var(--verdev); }
.rl .a-editar { --c: var(--ambar); --cv: var(--ambarv); }
.rl .a-eliminar { --c: var(--rosa); --cv: var(--rosav); }

/* ---------- Arriba ---------- */
.rl-top { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 12px; }
.rl-cmd { flex: 1 1 320px; min-width: 0; max-width: 760px; height: 50px; border-radius: 14px; background: var(--papel); border: 1px solid var(--linea); box-shadow: 0 12px 30px -24px rgba(11, 30, 28, .45); display: flex; align-items: center; gap: 12px; padding: 0 12px 0 16px; }
.rl-cmd > .bi { color: var(--cred); }
.rl-cmd input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 450 15px var(--ui); color: var(--tinta); }
.rl-cmd input::placeholder { color: var(--media); }
.rl-dark { height: 44px; border-radius: 12px; border: 0; background: var(--tinta); color: #fff; font: 600 14px var(--ui); display: flex; align-items: center; gap: 10px; padding: 0 12px 0 16px; white-space: nowrap; }
.rl-dark:hover { background: var(--cred2); }
.rl-dark kbd { border-color: rgba(255, 255, 255, .25); color: #cfe; }

/* ---------- Cabecera ---------- */
.rl-cab { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px 26px; }
.rl-g { display: flex; align-items: flex-end; gap: 12px; margin-right: auto; }
.rl-g > b { font: 800 84px/.8 var(--disp); font-stretch: 78%; letter-spacing: -.04em; color: var(--tinta); }
.rl-g > span { display: flex; flex-direction: column; padding-bottom: 2px; font-size: 14px; color: var(--media); line-height: 1.25; }
.rl-g em { font: 700 19px/1.15 var(--disp); font-style: normal; color: var(--tinta); }
.rl-ley { display: flex; flex-wrap: wrap; gap: 6px 14px; padding-bottom: 6px; }
.rl-ley li { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--texto); }
.rl-ley li .bi { width: 22px; height: 22px; border-radius: 6px; display: grid; place-items: center; font-size: 11px; color: var(--c); background: var(--cv); }
.rl-seg { display: inline-flex; padding: 3px; border-radius: 11px; background: var(--papel); border: 1px solid var(--linea); }
.rl-seg button { height: 34px; display: inline-flex; align-items: center; gap: 7px; padding: 0 12px; border: 0; border-radius: 8px; background: none; font: 600 13px var(--ui); color: var(--media); }
.rl-seg button[aria-pressed=true] { background: var(--tinta); color: #fff; }
.rl-seg button:disabled { opacity: .45; }
@container (max-width: 759px) { .rl-g > b { font-size: 64px; } .rl-ley { display: none; } }

/* ---------- Escenario ---------- */
.rl-esc { display: grid; grid-template-columns: minmax(0, 1fr); gap: 20px; align-items: start; }
@container (min-width: 900px) { .rl-esc { grid-template-columns: 290px minmax(0, 1fr); } .rl-lista { position: sticky; top: 96px; } }

/* Lista de roles */
.rl-lista { display: grid; gap: 4px; padding: 8px; background: var(--papel); border: 1px solid var(--linea); border-radius: 16px; transition: opacity .3s, filter .3s; }
.rl-rol { width: 100%; display: flex; align-items: center; gap: 12px; padding: 9px 10px; border: 0; border-radius: 12px; background: none; text-align: left; }
.rl-rol:hover { background: var(--niebla); }
.rl-rol.sel { background: var(--velo); box-shadow: inset 3px 0 0 var(--cred); }
.rl-anillo { flex: none; width: 40px; height: 40px; transform: rotate(-90deg); }
.rl-anillo circle { fill: none; stroke-width: 5; }
.rl-anillo .pista { stroke: var(--linea2); }
.rl-anillo .valor { stroke: var(--cred); stroke-linecap: round; stroke-dasharray: 100.5; transition: stroke-dashoffset .8s var(--e); }
.rl-rol-n { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.rl-rol-n b { font-size: 14.5px; font-weight: 650; color: var(--tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rl-rol-n small { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rl-rol em { flex: none; font-style: normal; font-size: 11px; font-weight: 650; color: var(--cred); background: var(--papel); border: 1px solid #b9d6d0; border-radius: 99px; padding: 0 7px; }
.rl-vacio { padding: 12px; font-size: 13px; color: var(--media); }
@container (min-width: 520px) and (max-width: 899px) { .rl-lista { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

/* Panel del rol */
.rl-panel { position: relative; min-width: 0; background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; box-shadow: 0 18px 40px -34px rgba(11, 30, 28, .5); }
.rl-panel-vacio { padding: 50px 20px; text-align: center; }
.rl-panel-vacio b { font: 700 19px var(--disp); color: var(--tinta); }
.rl-panel-vacio p { margin-top: 6px; color: var(--media); }
.rl-ph { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 14px 20px; padding: 20px 22px 14px; }
.rl-ph-a { flex: 1 1 320px; min-width: 0; }
.rl-k { font: 500 12px var(--mono); letter-spacing: .05em; text-transform: uppercase; color: var(--cred); }
.rl-ph h2 { margin-top: 4px; font: 780 34px/1.02 var(--disp); font-stretch: 85%; color: var(--tinta); overflow-wrap: anywhere; }
.rl-nombre { display: flex; flex-direction: column; gap: 4px; margin-top: 6px; }
.rl-nombre input { width: 100%; height: 50px; border: 1px solid var(--linea); border-radius: 12px; padding: 0 14px; font: 700 22px var(--disp); color: var(--tinta); background: var(--papel); outline: 0; transition: border-color .15s, box-shadow .15s; }
.rl-nombre input::placeholder { font: 450 16px var(--ui); color: #9aa7a4; }
.rl-nombre input:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(14, 124, 114, .15); }
.rl-nombre.mal input { border-color: var(--rosa); }
.rl-nombre small { font-size: 12.5px; font-weight: 500; color: var(--rosa); }
.rl-gente { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 10px; }
.rl-gente a { display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 10px 0 4px; border-radius: 99px; background: var(--niebla); font-size: 12.5px; font-weight: 600; color: var(--tinta); text-decoration: none; max-width: 220px; }
.rl-gente a:hover { background: var(--velo); color: var(--cred); }
.rl-gente a.sin { pointer-events: none; }
.rl-gente a > span:first-child { flex: none; width: 22px; height: 22px; border-radius: 50%; background: var(--papel); color: #7fbcb1; }
.rl-gente a > span:first-child svg { width: 22px; height: 22px; display: block; }
.rl-gente a > span:last-child { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rl-mas { font: 500 12px var(--mono); color: var(--media); }
.rl-nadie { font-size: 13px; color: var(--media); }
.rl-ph-b { display: flex; flex-wrap: wrap; gap: 8px; }
.rl-medidor { display: flex; align-items: center; gap: 12px; padding: 0 22px 14px; }
.rl-barra { flex: 1; max-width: 340px; height: 8px; border-radius: 99px; background: var(--linea2); overflow: hidden; }
.rl-barra i { display: block; height: 100%; border-radius: 99px; background: var(--cred); transition: width .5s var(--e); }
.rl-medidor span { font: 500 12.5px var(--mono); color: var(--media); }
.rl-total { display: flex; gap: 10px; margin: 0 22px 14px; padding: 12px 14px; border-radius: 12px; background: var(--velo); font-size: 13.5px; color: var(--texto); }
.rl-total .bi { color: var(--cred); font-size: 18px; }
.rl-total b { color: var(--tinta); }
.rl-atajos { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding: 0 22px 12px; }
.rl-atajos > span { margin-right: 4px; font-size: 13px; font-weight: 600; color: var(--media); }
.rl-atajos button { height: 32px; display: inline-flex; align-items: center; gap: 6px; padding: 0 11px; border-radius: 9px; border: 1px solid var(--linea); background: var(--papel); font-size: 13px; font-weight: 600; color: var(--texto); }
.rl-atajos button:hover { border-color: #b9d6d0; color: var(--cred); }
.rl-atajos button .bi { color: var(--cred); }
.rl-copiar { position: relative; }
.rl-pop { position: absolute; z-index: 20; top: calc(100% + 6px); left: 0; width: 240px; max-height: 260px; overflow-y: auto; padding: 4px; background: var(--papel); border: 1px solid var(--linea); border-radius: 12px; box-shadow: 0 16px 32px -20px rgba(11, 30, 28, .55); }
.rl-pop button { width: 100%; height: auto !important; display: flex !important; flex-direction: column; align-items: flex-start !important; gap: 0 !important; padding: 7px 10px !important; border: 0 !important; border-radius: 8px !important; text-align: left; }
.rl-pop button:hover { background: var(--niebla); }
.rl-pop b { font-size: 13.5px; color: var(--tinta); }
.rl-pop small { font-size: 12px; color: var(--media); }
.rl-aviso { display: flex; gap: 8px; margin: 0 22px 12px; padding: 10px 12px; border-radius: 10px; background: var(--rosav); color: var(--rosa); font-size: 13px; font-weight: 600; }

/* Matriz */
.rl-matriz { border-top: 1px solid var(--linea2); }
.rl-mh, .rl-fila { display: grid; grid-template-columns: minmax(0, 1fr) repeat(4, 64px); align-items: center; gap: 0 4px; padding: 0 18px 0 22px; }
.rl-mh { position: sticky; top: 0; z-index: 2; height: 40px; background: #fbfdfc; border-bottom: 1px solid var(--linea2); font-size: 12px; font-weight: 600; color: var(--media); }
.rl-mh button { height: 28px; border: 0; border-radius: 7px; background: none; font-size: 12.5px; font-weight: 650; color: var(--c); }
.rl-mh button:hover:not(:disabled) { background: var(--cv); }
.rl-grupo + .rl-grupo { border-top: 1px solid var(--linea2); }
.rl-gh { width: 100%; display: flex; align-items: baseline; gap: 8px; padding: 14px 22px 6px; border: 0; background: none; text-align: left; font: 700 15px var(--disp); color: var(--tinta); }
.rl-gh small { font: 500 12px var(--mono); color: var(--media); }
.rl-gh:hover:not(:disabled) { color: var(--cred); }
.rl-fila { min-height: 54px; border-radius: 0; transition: background .15s; }
.rl-fila:hover { background: #f8fbfa; }
.rl-mod { min-width: 0; display: flex; align-items: center; gap: 12px; padding: 6px 0; border: 0; background: none; text-align: left; }
.rl-mod > .bi { flex: none; width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; font-size: 15px; background: var(--niebla); color: var(--media); transition: background .2s, color .2s; }
.rl-fila.on .rl-mod > .bi { background: var(--cred); color: var(--foil); }
.rl-mod > span { min-width: 0; display: flex; flex-direction: column; }
.rl-mod b { font-size: 14px; font-weight: 600; color: var(--tinta); }
.rl-mod small { font-size: 12.5px; color: var(--media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rl-fila:not(.on) .rl-mod b { color: var(--media); }
.rl-p { justify-self: center; width: 40px; height: 36px; border-radius: 10px; border: 1px dashed #cfdad7; background: var(--papel); color: #c2ccc9; display: grid; place-items: center; font-size: 14px; transition: background .15s, color .15s, border-color .15s, transform .15s var(--e); }
.rl-p[aria-pressed=true] { border: 1px solid var(--c); background: var(--cv); color: var(--c); }
.rl-p:hover:not(:disabled) { border-color: var(--c); color: var(--c); }
.rl-p:active:not(:disabled) { transform: scale(.92); }
.rl-matriz.lectura .rl-p { border-style: solid; border-color: transparent; background: none; }
.rl-matriz.lectura .rl-p[aria-pressed=true] { background: var(--cv); border-color: transparent; }
.rl-matriz.lectura .rl-p:not([aria-pressed=true]) i { opacity: .35; }
.rl-matriz .rl-vacio { padding: 18px 22px; }
@container (max-width: 559px) {
    .rl-mh, .rl-fila { grid-template-columns: minmax(0, 1fr) repeat(4, 40px); padding: 0 10px 0 14px; }
    .rl-mod small { display: none; }
    .rl-mod > .bi { display: none; }
    .rl-p { width: 36px; }
    .rl-mh button { font-size: 10.5px; padding: 0; }
}

/* Barra de guardado */
.rl-pie { position: sticky; bottom: 0; z-index: 3; display: flex; align-items: center; gap: 8px; padding: 12px 18px 12px 22px; border-top: 1px solid var(--linea2); background: rgba(255, 255, 255, .96); backdrop-filter: blur(6px); border-radius: 0 0 18px 18px; }
.rl-msg { flex: 1; min-width: 0; font-size: 12.5px; color: var(--media); }
.rl-msg.mal { color: var(--rosa); font-weight: 600; }

/* Botones */
.rl-btn { height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 14px; border-radius: 11px; border: 1px solid var(--linea); background: var(--papel); font: 600 14px var(--ui); color: var(--tinta); text-decoration: none; white-space: nowrap; transition: background .15s, border-color .15s; }
.rl-btn:hover { background: #f7faf9; color: var(--tinta); }
.rl-btn .bi { color: var(--cred); }
.rl-prim { background: var(--cred); border-color: var(--cred); color: #fff; }
.rl-prim .bi { color: var(--foil); }
.rl-prim:hover { background: var(--cred2); color: #fff; }
.rl-prim:disabled { opacity: .6; }
.rl-prim kbd { border-color: rgba(255, 255, 255, .3); color: #cfe; }
.rl-ico { width: 40px; padding: 0; }
.rl-borrar { border-color: #f3c6cf; }
.rl-borrar .bi { color: var(--rosa); }
.rl-borrar:hover { background: var(--rosav); }
.rl-borrar:disabled { opacity: .35; cursor: not-allowed; }

/* Edición: la lista se atenúa */
.rl.editando .rl-lista, .rl.editando .rl-cab { opacity: .35; filter: saturate(.6); pointer-events: none; }
.rl.editando .rl-cmd input { pointer-events: auto; }

/* ---------- Comparar ---------- */
.rl-comp { background: var(--papel); border: 1px solid var(--linea); border-radius: 18px; overflow: hidden; }
.rl-comp-in { overflow-x: auto; }
.rl-comp table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.rl-comp thead th { position: sticky; top: 0; padding: 12px 10px; background: #fbfdfc; border-bottom: 1px solid var(--linea2); text-align: center; vertical-align: bottom; min-width: 110px; }
.rl-comp thead th:first-child { text-align: left; padding-left: 20px; font-size: 12px; font-weight: 600; color: var(--media); }
.rl-comp thead button { border: 0; background: none; font: 700 14px var(--disp); color: var(--tinta); }
.rl-comp thead button:hover { color: var(--cred); text-decoration: underline; }
.rl-comp thead small { display: block; font: 500 11.5px var(--mono); color: var(--media); }
.rl-comp tbody th { position: sticky; left: 0; z-index: 1; padding: 10px 14px 10px 20px; background: var(--papel); text-align: left; font-weight: 600; color: var(--tinta); white-space: nowrap; }
.rl-comp tbody th .bi { color: var(--teal); margin-right: 6px; }
.rl-comp tbody tr + tr > * { border-top: 1px solid var(--linea2); }
.rl-comp td { padding: 10px; text-align: center; }
.rl-mini { display: inline-flex; gap: 3px; }
.rl-mini i { width: 12px; height: 12px; border-radius: 3px; background: var(--linea2); }
.rl-mini i.on { background: var(--c); }

@media (prefers-reduced-motion: reduce) { .rl-anillo .valor, .rl-barra i { transition: none; } }
</style>

<script>
function modRoles() {
    const D = JSON.parse(document.getElementById('rl-datos').textContent || '{}');
    const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    const ACC = [
        { k: 'ver', t: 'Ver', i: 'bi-eye' }, { k: 'crear', t: 'Crear', i: 'bi-plus-lg' },
        { k: 'editar', t: 'Editar', i: 'bi-pencil' }, { k: 'eliminar', t: 'Eliminar', i: 'bi-trash3' },
    ];
    const K = ACC.map(a => a.k);
    const aviso = (m, i) => { if (window.notificar) window.notificar(m, i || 'success'); };
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    return {
        ACC,
        MOD: D.modulos || [],
        roles: D.roles || [],
        PUEDE: D.puede || {},
        R: D.rutas || {},
        miRolId: D.miRolId,
        sel: null, vista: 'rol', q: '',
        editando: false, guardando: false, popCopiar: false,
        form: { id: null, nombre: '', permisos: {} }, inicial: '',
        errorNombre: '', mensaje: '', mensajeMal: false,

        init() {
            this.$watch('sel', () => window.mtRefresca && mtRefresca('.rl-panel'));
            const u = new URLSearchParams(location.search);
            this.sel = this.roles.find(r => String(r.id) === u.get('r')) || this.roles.find(r => this.esMio(r)) || this.roles[0] || null;
            if (D.exito) setTimeout(() => aviso(D.exito), 300);
            if ((D.errores || []).length) setTimeout(() => aviso(D.errores[0], 'warning'), 300);
            if (u.get('nuevo') && this.PUEDE.crear) this.$nextTick(() => this.nuevo());
        },

        // ----- Datos -----
        get totalPersonas() { return this.roles.reduce((n, r) => n + (r.usuarios || 0), 0); },
        get rolesFiltrados() {
            const q = norm(this.q);
            if (!q || this.MOD.some(m => norm(m.nombre).includes(q))) return this.roles;
            return this.roles.filter(r => norm(r.nombre).includes(q));
        },
        get grupos() {
            const g = [];
            this.MOD.forEach(m => { let x = g.find(y => y.nombre === m.grupo); if (!x) g.push(x = { nombre: m.grupo, mods: [] }); x.mods.push(m); });
            return g;
        },
        get gruposVisibles() {
            const q = norm(this.q);
            if (!q || this.roles.some(r => norm(r.nombre).includes(q))) return this.grupos;
            return this.grupos.map(g => ({ nombre: g.nombre, mods: g.mods.filter(m => norm(m.nombre + ' ' + m.desc).includes(q)) })).filter(g => g.mods.length);
        },
        esMio(r) { return r && this.miRolId !== null && String(r.id) === String(this.miRolId); },
        permisosDe(r) { return r.total ? null : r.permisos; },
        contar(p) { return Object.values(p || {}).reduce((n, x) => n + K.filter(k => x && x[k]).length, 0); },
        modulosCon(r) { return r.total ? this.MOD.length : this.MOD.filter(m => { const x = r.permisos[m.id]; return x && K.some(k => x[k]); }).length; },
        cobertura(r) { return r.total ? 100 : (this.MOD.length ? Math.round(this.contar(r.permisos) / (this.MOD.length * 4) * 100) : 0); },

        // Lo que muestra la matriz: el formulario al editar, si no el rol elegido
        get actuales() { return this.editando ? this.form.permisos : (this.sel ? this.sel.permisos : {}); },
        valor(id, k) {
            if (!this.editando && this.sel && this.sel.total) return true;
            const p = this.actuales[id]; return !!(p && p[k]);
        },
        tieneAcceso(id) { return K.some(k => this.valor(id, k)); },
        get permisosActuales() { return this.contar(this.actuales); },
        get modulosActuales() { return this.MOD.filter(m => this.tieneAcceso(m.id)).length; },
        get porcentajeActual() { return this.MOD.length ? this.permisosActuales / (this.MOD.length * 4) * 100 : 0; },

        elegir(r) {
            if (this.editando) return;
            this.sel = r;
            history.replaceState(null, '', location.pathname + '?r=' + r.id);
            if (window.innerWidth < 1100) this.$nextTick(() => { const p = document.querySelector('.rl-panel'); if (p) p.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' }); });
        },

        // ----- Edición -----
        vacio() { const p = {}; this.MOD.forEach(m => { p[m.id] = { ver: false, crear: false, editar: false, eliminar: false }; }); return p; },
        copia(r) {
            const p = this.vacio();
            this.MOD.forEach(m => { const o = r && !r.total ? (r.permisos[m.id] || {}) : null; K.forEach(k => { p[m.id][k] = r && r.total ? true : !!(o && o[k]); }); });
            return p;
        },
        abrir(form) {
            this.form = form; this.inicial = JSON.stringify(form);
            this.errorNombre = ''; this.mensajeMal = false; this.popCopiar = false;
            this.mensaje = 'Toca un permiso para darlo o quitarlo. Ctrl + Enter guarda.';
            this.vista = 'rol'; this.editando = true;
            this.$nextTick(() => {
                const m = document.querySelector('.rl').closest('.overflow-y-auto');
                if (m) m.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
                if (this.$refs.campoNombre) this.$refs.campoNombre.focus({ preventScroll: true });
            });
        },
        nuevo() { if (this.PUEDE.crear && !this.editando) this.abrir({ id: null, nombre: '', permisos: this.vacio() }); },
        editar(r) { if (r && this.PUEDE.editar && !this.editando) this.abrir({ id: r.id, nombre: r.nombre, permisos: this.copia(r) }); },
        duplicar(r) { if (r && this.PUEDE.crear && !this.editando) this.abrir({ id: null, nombre: r.nombre + ' (copia)', permisos: this.copia(r) }); },
        copiarDe(r) { this.form.permisos = this.copia(r); this.popCopiar = false; },
        get hayCambios() { return this.editando && JSON.stringify(this.form) !== this.inicial; },
        cancelar() {
            if (!this.hayCambios || !window.Swal) { this.editando = false; return; }
            Swal.fire({
                title: '¿Descartar los cambios?', text: 'Los permisos que modificaste no se guardarán.', icon: 'question',
                showCancelButton: true, reverseButtons: true, confirmButtonText: 'Descartar', cancelButtonText: 'Seguir editando',
                confirmButtonColor: '#c8102e', cancelButtonColor: '#0b5a54',
            }).then(r => { if (r.isConfirmed) this.editando = false; });
        },
        // Crear, editar o eliminar necesitan ver; quitar ver quita lo demás
        alternar(id, k) {
            if (!this.editando) return;
            const p = this.form.permisos[id];
            p[k] = !p[k];
            if (k !== 'ver' && p[k]) p.ver = true;
            if (k === 'ver' && !p.ver) K.forEach(x => { p[x] = false; });
        },
        fila(id) { if (!this.editando) return; const p = this.form.permisos[id], todo = K.every(k => p[k]); K.forEach(k => { p[k] = !todo; }); },
        columna(k) {
            if (!this.editando) return;
            const todo = this.MOD.every(m => this.form.permisos[m.id][k]);
            this.MOD.forEach(m => { const p = this.form.permisos[m.id]; p[k] = !todo; if (k !== 'ver' && p[k]) p.ver = true; if (k === 'ver' && !p.ver) K.forEach(x => { p[x] = false; }); });
        },
        grupo(g) {
            if (!this.editando) return;
            const todo = g.mods.every(m => K.every(k => this.form.permisos[m.id][k]));
            g.mods.forEach(m => K.forEach(k => { this.form.permisos[m.id][k] = !todo; }));
        },
        plantilla(t) { this.MOD.forEach(m => { const p = this.form.permisos[m.id]; p.ver = t !== 'nada'; p.crear = p.editar = p.eliminar = t === 'todo'; }); },
        get seQuitaRoles() {
            if (!this.editando || !this.form.id || String(this.form.id) !== String(this.miRolId)) return false;
            const m = this.MOD.find(x => norm(x.nombre) === 'roles');
            return !!(m && !this.form.permisos[m.id].ver);
        },
        async guardar() {
            if (this.guardando) return;
            const nombre = this.form.nombre.trim().replace(/\s+/g, ' ');
            if (!nombre) { this.errorNombre = 'Escribe el nombre del rol.'; this.mensaje = 'Falta el nombre.'; this.mensajeMal = true; window.mtSacudir && mtSacudir('.rl-nombre'); return; }
            if (this.roles.some(r => r.id !== this.form.id && norm(r.nombre) === norm(nombre))) { this.errorNombre = 'Ya existe un rol con ese nombre.'; this.mensajeMal = true; this.mensaje = this.errorNombre; window.mtSacudir && mtSacudir('.rl-nombre'); return; }
            this.guardando = true; this.mensajeMal = false; this.mensaje = 'Guardando…';
            try {
                const id = this.form.id;
                const res = await fetch(id ? this.R.base + '/' + id : this.R.base, {
                    method: id ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ nombre, permisos: this.form.permisos }),
                });
                const r = await res.json().catch(() => ({}));
                if (res.status === 422) { this.errorNombre = (r.errors && r.errors.nombre && r.errors.nombre[0]) || r.message || 'Revisa el nombre.'; this.mensaje = this.errorNombre; this.mensajeMal = true; window.mtSacudir && mtSacudir('.rl-nombre'); return; }
                if (!res.ok) throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (r.message || 'No se pudo guardar.'));
                const i = this.roles.findIndex(x => x.id === r.rol.id);
                if (i >= 0) this.roles.splice(i, 1, r.rol); else this.roles.push(r.rol);
                this.roles.sort((a, b) => a.nombre.localeCompare(b.nombre));
                this.sel = this.roles.find(x => x.id === r.rol.id);
                this.editando = false;
                history.replaceState(null, '', location.pathname + '?r=' + r.rol.id);
                aviso(r.mensaje || 'Guardado');
                this.$nextTick(() => window.mtDestello && mtDestello('.rl-rol.sel'));
            } catch (e) {
                this.mensaje = e.message || 'Error de conexión.'; this.mensajeMal = true;
            } finally { this.guardando = false; }
        },

        // ----- Eliminar -----
        eliminar(r) {
            if (!r || this.esMio(r) || !window.Swal) return;
            Swal.fire({
                title: '¿Eliminar el rol ' + r.nombre + '?',
                text: (r.usuarios ? r.usuarios + (r.usuarios === 1 ? ' persona se quedará' : ' personas se quedarán') + ' sin rol y no podrán entrar a los módulos hasta que les asignes otro. ' : '') + 'Esta acción no se puede deshacer.',
                icon: 'warning', showCancelButton: true, reverseButtons: true, focusCancel: true,
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar', confirmButtonColor: '#c8102e', cancelButtonColor: '#56625f',
                showLoaderOnConfirm: true, allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        const res = await fetch(this.R.base + '/' + r.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': D.csrf, 'Accept': 'application/json' } });
                        const d = await res.json().catch(() => ({}));
                        if (!res.ok || d.success === false) throw new Error(res.status === 419 ? 'Tu sesión expiró. Recarga la página.' : (d.message || 'No se pudo eliminar.'));
                        return d;
                    } catch (e) { Swal.showValidationMessage(e.message || 'Error de conexión'); }
                },
            }).then(x => {
                if (!x.isConfirmed) return;
                const quitar = () => { this.roles = this.roles.filter(y => y.id !== r.id); this.sel = this.roles[0] || null; };
                window.mtQuitar ? mtQuitar('.rl-rol.sel', quitar) : quitar();
                aviso((x.value && x.value.message) || 'Rol eliminado.');
            });
        },

        // ----- Teclado -----
        tecla(e) {
            if (document.querySelector('.swal2-container')) return;
            const escribiendo = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);
            if (this.editando) {
                if (e.key === 'Escape') { e.preventDefault(); if (this.popCopiar) this.popCopiar = false; else this.cancelar(); }
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); this.guardar(); }
                return;
            }
            if (escribiendo || e.ctrlKey || e.metaKey || e.altKey) return;
            if (e.key === '/') { e.preventDefault(); this.$refs.buscar.focus(); }
            else if (e.key === 'n' || e.key === 'N') { e.preventDefault(); this.nuevo(); }
            else if ((e.key === 'e' || e.key === 'E') && this.sel) { e.preventDefault(); this.editar(this.sel); }
            else if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && this.vista === 'rol') {
                const l = this.rolesFiltrados; if (!l.length) return;
                e.preventDefault();
                const i = l.findIndex(r => this.sel && r.id === this.sel.id);
                this.elegir(l[Math.max(0, Math.min(l.length - 1, i + (e.key === 'ArrowDown' ? 1 : -1)))]);
            }
        },

        roseta(seed, size) { return typeof mtRoseta === 'function' ? mtRoseta(seed, size) : ''; },
    };
}
</script>
@endverbatim
@endsection
