<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Sistema Médico</title>
    <!-- Precarga la página al pasar el cursor por un enlace (Chrome/Edge): el cambio de módulo se siente inmediato -->
    <script type="speculationrules">
    {"prefetch": [{"where": {"and": [
        {"href_matches": "/*"},
        {"not": {"href_matches": "/logout"}},
        {"not": {"href_matches": "/*pdf*"}},
        {"not": {"href_matches": "/*ticket*"}},
        {"not": {"href_matches": "/*corte*"}},
        {"not": {"selector_matches": "[target=_blank], [download], [data-no-precarga]"}}
    ]}, "eagerness": "moderate"}]}
    </script>
    <script>
        // Cortina MediTrack: si venimos de otra pantalla del sistema, se muestra desde el primer cuadro
        (() => {
            try {
                const t = Number(sessionStorage.getItem('ly-cortina') || 0);
                sessionStorage.removeItem('ly-cortina');
                if (t && Date.now() - t < 8000) document.documentElement.classList.add('ly-cortina-on', 'ly-cortina-llega');
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Tipografías del sistema (DESIGN.md): Bricolage Grotesque, Geist y Geist Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,200..800&family=Geist:wght@100..900&family=Geist+Mono:wght@100..900&display=swap">

    <!-- CSS y JS de SweetAlert2 y Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    {{-- Estado global del layout (menú, gafete y paleta Ctrl+K). Va antes de Alpine. --}}
@verbatim
<script>
/* Patrones de seguridad (mismo algoritmo que la credencial de Pacientes) */
function mtHash(s) { let h = 2166136261; for (const c of String(s)) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); } return h >>> 0; }
function mtRoseta(seed, size) {
    const hv = mtHash(seed || 'MT'), n = 18 + hv % 9, c = size / 2; let d = '';
    for (let k = 0; k < 9; k++) {
        const A = size * .11 + k * size * .042, B = size * .03 + (k % 3) * size * .012;
        for (let i = 0; i <= 160; i++) { const t = i / 160 * 2 * Math.PI, r = A + B * Math.cos(n * t + k * .4); d += (i ? 'L' : 'M') + (c + r * Math.cos(t)).toFixed(1) + ',' + (c + r * Math.sin(t)).toFixed(1); }
        d += 'Z';
    }
    return `<svg viewBox="0 0 ${size} ${size}" aria-hidden="true"><path d="${d}" fill="none" stroke="currentColor" stroke-width=".6"/></svg>`;
}
function mtGuilloche(seed) {
    const hv = mtHash(seed || 'MEDITRACK'), w = 300, h = 110, n = 22 + hv % 10, cx = w * .86, cy = h * .5; let d = '';
    for (let k = 0; k < 10; k++) {
        const A = 34 + k * 6, B = 5 + (k % 4) * 1.6, ph = k * .22 + (hv % 100) / 50;
        for (let i = 0; i <= 220; i++) { const t = i / 220 * 2 * Math.PI, r = A + B * Math.cos(n * t + ph); d += (i ? 'L' : 'M') + (cx + r * Math.cos(t)).toFixed(1) + ',' + (cy + r * Math.sin(t) * .9).toFixed(1); }
        d += 'Z';
    }
    for (let k = 0; k < 4; k++) { const y0 = 16 + k * 26; for (let x = 0; x <= w; x += 5) d += (x ? 'L' : 'M') + x + ',' + (y0 + 5 * Math.sin(x / 23 + k * .9 + hv % 5) + 2 * Math.sin(x / 7 + k)).toFixed(1); }
    return `<svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="${d}" fill="none" stroke="#a6efe2" stroke-width=".5" opacity=".28"/></svg>`;
}
const mtNorm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();

function layoutMeditrack() {
    const lee = (k, d) => { try { const v = localStorage.getItem(k); return v === null ? d : v; } catch (e) { return d; } };
    const guarda = (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} };
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    return {
        sidebarOpen: lee('sidebarOpen', 'true') !== 'false',
        mobileOpen: false,
        ancho: window.innerWidth,
        ahora: new Date(),
        headerVisible: true,
        headerHover: false,
        ultimoScroll: 0,
        tip: { texto: '', top: 0, left: 0 },
        gafeteVuelta: false,
        buscarMenu: '',

        // Paleta
        paleta: false,
        q: '',
        sel: 0,
        remotos: [],
        buscando: false,
        nav: { modulos: [], acciones: [], buscar: null, semilla: 'MT' },
        semilla: 'MT',
        _timer: null,
        _peticion: 0,
        _foco: null,

        get saludo() {
            const h = this.ahora.getHours();
            return h < 12 ? 'Buenos días' : h < 19 ? 'Buenas tardes' : 'Buenas noches';
        },
        get hora() { return this.ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }); },
        get fecha() { const f = this.ahora.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }); return f.charAt(0).toUpperCase() + f.slice(1); },

        init() {
            try { this.nav = Object.assign(this.nav, JSON.parse(document.getElementById('ly-nav').textContent)); } catch (e) {}
            this.semilla = this.nav.semilla || 'MT';
            this.$watch('sidebarOpen', v => guarda('sidebarOpen', v));
            this.$watch('mobileOpen', v => { if (!v) this.gafeteVuelta = false; });
            this.$watch('q', () => this.buscarRemoto());
            window.addEventListener('resize', () => {
                this.ancho = window.innerWidth;
                if (this.ancho >= 1024) this.mobileOpen = false;
            });
            setInterval(() => { this.ahora = new Date(); }, 15000);
            this.$nextTick(() => { if (this.$refs.sbGl) this.$refs.sbGl.innerHTML = mtGuilloche('MEDITRACK').replace('opacity=".28"', 'opacity=".12"'); });
        },

        roseta(seed, size) { return mtRoseta(seed, size); },
        guilloche(seed) { return mtGuilloche(seed); },

        /* ---------- Buscador del menú ---------- */
        coincideMenu(texto) {
            const q = mtNorm(this.buscarMenu); if (!q) return true;
            const h = mtNorm(texto); return q.split(/\s+/).every(p => h.includes(p));
        },
        algunoCoincide(textos) { return textos.some(t => this.coincideMenu(t)); },
        irPrimerModulo() {
            const a = [...document.querySelectorAll('.ly-nav a.ly-link')].find(el => el.offsetParent !== null);
            if (a) { this.recordar(a.dataset.modulo); window.location.href = a.href; }
            else if (this.buscarMenu.trim().length >= 2) this.abrirPaleta(this.buscarMenu);
        },
        enfocarBuscador() {
            if (this.ancho < 1024) this.mobileOpen = true; else this.sidebarOpen = true;
            setTimeout(() => this.$refs.inputBuscadorSidebar && this.$refs.inputBuscadorSidebar.focus(), 250);
        },

        /* ---------- Encabezado que se oculta al bajar ---------- */
        alScrollear(el) {
            const y = el.scrollTop, delta = y - this.ultimoScroll;
            if (y < 80) this.headerVisible = true;
            else if (delta > 6 && !this.headerHover) this.headerVisible = false;
            else if (delta < -6) this.headerVisible = true;
            this.ultimoScroll = y;
        },
        alMoverCursor(e, el) {
            if (!this.headerVisible && (e.clientY - el.getBoundingClientRect().top) < 40) this.headerVisible = true;
        },

        /* ---------- Menú lateral ---------- */
        mostrarTip(el, texto) {
            if (this.sidebarOpen || this.ancho < 1024) return;
            const r = el.getBoundingClientRect();
            this.tip = { texto, top: r.top + r.height / 2, left: r.right + 12 };
        },
        ocultarTip() { this.tip.texto = ''; },
        alternarMenu() {
            if (this.ancho < 1024) this.mobileOpen = !this.mobileOpen;
            else { this.sidebarOpen = !this.sidebarOpen; this.gafeteVuelta = false; this.ocultarTip(); }
        },
        voltearGafete() {
            this.ocultarTip();
            const enfocar = () => setTimeout(() => {
                const el = document.querySelector('.ly-gf-reverso a, .ly-gf-reverso button');
                el && el.focus({ preventScroll: true });
            }, reduce ? 0 : 420);
            if (this.ancho >= 1024 && !this.sidebarOpen) {
                this.sidebarOpen = true;
                setTimeout(() => { this.gafeteVuelta = true; enfocar(); }, reduce ? 0 : 320);
                return;
            }
            this.gafeteVuelta = !this.gafeteVuelta;
            if (this.gafeteVuelta) enfocar();
        },

        /* ---------- Paleta Ctrl+K ---------- */
        abrirPaleta(texto) {
            if (this.paleta) { this.$refs.pq.select(); return; }
            this._foco = document.activeElement;
            this.mobileOpen = false;
            this.q = typeof texto === 'string' ? texto.trim() : ''; this.remotos = []; this.sel = 0; this.paleta = true; this.buscarMenu = '';
            this.$nextTick(() => this.$refs.pq.focus());
        },
        cerrarPaleta() {
            this.paleta = false; this.buscando = false;
            if (this._foco && this._foco.focus) this._foco.focus({ preventScroll: true });
        },
        recordar(nombre) {
            let l = []; try { l = JSON.parse(lee('ly-recientes', '[]')) || []; } catch (e) {}
            l = [nombre, ...l.filter(x => x !== nombre)].slice(0, 4);
            guarda('ly-recientes', JSON.stringify(l));
        },
        coincide(texto, q) {
            const h = mtNorm(texto);
            return mtNorm(q).split(/\s+/).filter(Boolean).every(p => h.includes(p));
        },
        get resultados() {
            const q = this.q.trim(), salida = [];
            const mod = (m, grupo) => ({ clave: grupo + m.t, grupo, tipo: 'modulo', titulo: m.t, sub: m.s, icono: m.i, url: m.u, nombre: m.t });
            const acc = a => ({ clave: 'a' + a.t, grupo: 'Acciones', tipo: 'accion', titulo: a.t, sub: a.d, icono: a.i, url: a.u, accion: a.accion });
            const acciones = [...this.nav.acciones, { t: 'Cerrar sesión', d: 'Salir de MediTrack', i: 'bi-box-arrow-right', k: 'salir logout', accion: 'salir' }];

            if (!q) {
                let rec = []; try { rec = JSON.parse(lee('ly-recientes', '[]')) || []; } catch (e) {}
                rec.map(n => this.nav.modulos.find(m => m.t === n)).filter(Boolean).forEach(m => salida.push(mod(m, 'Recientes')));
                this.nav.acciones.forEach(a => salida.push(acc(a)));
                this.nav.modulos.filter(m => !rec.includes(m.t)).forEach(m => salida.push(mod(m, 'Ir a')));
                return salida;
            }
            this.nav.modulos.filter(m => this.coincide(m.t + ' ' + m.s + ' ' + m.k, q)).forEach(m => salida.push(mod(m, 'Ir a')));
            acciones.filter(a => this.coincide(a.t + ' ' + a.k, q)).forEach(a => salida.push(acc(a)));
            this.remotos.forEach((p, i) => salida.push({ clave: 'p' + i + p.url, grupo: 'Pacientes', tipo: 'paciente', titulo: p.titulo, sub: p.subtitulo, url: p.url, semilla: p.semilla, alergia: p.alergia }));
            return salida;
        },
        mover(d) {
            const n = this.resultados.length; if (!n) return;
            this.sel = (this.sel + d + n) % n;
            this.$nextTick(() => { const el = document.getElementById('ly-r-' + this.sel); el && el.scrollIntoView({ block: 'nearest' }); });
        },
        buscarRemoto() {
            this.sel = 0;
            clearTimeout(this._timer);
            const q = this.q.trim();
            if (q.length < 2 || !this.nav.buscar) { this.remotos = []; this.buscando = false; return; }
            this.buscando = true;
            const id = ++this._peticion;
            this._timer = setTimeout(async () => {
                try {
                    const r = await fetch(this.nav.buscar + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const datos = r.ok ? await r.json() : [];
                    if (id !== this._peticion) return;
                    this.remotos = (Array.isArray(datos) ? datos : []).filter(x => x.categoria === 'Pacientes');
                } catch (e) {
                    if (id === this._peticion) this.remotos = [];
                } finally {
                    if (id === this._peticion) this.buscando = false;
                }
            }, 180);
        },
        abrirResultado(r) {
            if (!r) return;
            if (r.tipo === 'modulo') this.recordar(r.nombre);
            if (r.accion === 'salir') { document.getElementById('ly-salir')?.submit(); return; }
            if (!r.url) return;
            this.paleta = false;
            if (window.mtCortina) mtCortina();
            window.location.href = r.url;
        }
    };
}
</script>
@endverbatim

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Estilos de la carcasa (prefijo ly-; el chatbot ya usa mt-). Reglas en DESIGN.md. --}}

@verbatim
<style>
[x-cloak] { display: none !important; }

:root {
    --ly-tinta: #0b1e1c; --ly-texto: #2c3d3a; --ly-media: #56625f;
    --ly-linea: #dbe4e1; --ly-linea2: #edf2f1; --ly-niebla: #eef3f1; --ly-papel: #fff;
    --ly-cred: #0b5a54; --ly-cred2: #073f3b; --ly-foil: #a6efe2; --ly-teal: #0e7c72; --ly-velo: #e2efec;
    --ly-rosa: #c8102e; --ly-rosav: #fdecef;
    --ly-disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
    --ly-ui: 'Geist', ui-sans-serif, system-ui, sans-serif;
    --ly-mono: 'Geist Mono', ui-monospace, monospace;
    --ly-e: cubic-bezier(.2, .8, .2, 1);
}
.ly-body { background: var(--ly-niebla); }
.ly-velo-movil { position: fixed; inset: 0; z-index: 30; background: rgba(11, 30, 28, .5); }
.ly-sb, .ly-hd, .ly-paleta, .ly-tip { font-family: var(--ly-ui); }
.ly-sb :focus-visible, .ly-hd :focus-visible, .ly-paleta :focus-visible { outline: 2px solid var(--ly-teal); outline-offset: 2px; }
.ly-sb .t, .ly-hd .t, .ly-paleta .t { font-variant-numeric: tabular-nums; }
.ly-sb kbd, .ly-paleta kbd { font: 600 11px var(--ly-mono); border: 1px solid var(--ly-linea); border-radius: 6px; padding: 1px 6px; color: var(--ly-media); background: var(--ly-papel); }

/* Barra de progreso de navegación */
#top-progress-bar { position: fixed; top: 0; left: 0; height: 3px; width: 0; background: var(--ly-teal); z-index: 9999; transition: width 200ms ease-out, opacity 150ms ease-in; pointer-events: none; opacity: 0; }

/* =========================================================
   MOVIMIENTO (rápido y discreto: nada que haga esperar)
   rápido 120ms · base 180ms · suave 260ms · salida acelerada, llegada suave
   ========================================================= */
:root { --mt-rapido: 120ms; --mt-base: 180ms; --mt-suave: 260ms; --mt-e: cubic-bezier(.2, .8, .2, 1); --mt-sale: cubic-bezier(.4, 0, 1, 1); }

/* Entrada de página cuando el navegador no hace la transición entre páginas */
.page-fade-in { animation: mtPagina var(--mt-suave) var(--mt-e) both; }
.ly-cortina-llega .page-fade-in { animation: none; }
@keyframes mtPagina { from { opacity: 0; transform: translateY(6px); } }

@keyframes mtEntra { from { opacity: 0; transform: translateY(6px); } }

/* ---------- Respuesta al tocar ----------
   Se usan las propiedades individuales scale/translate para no pisar los transform de cada módulo.
   Botones compactos se hunden (96%); filas y botones grandes bajan 1px. */
:where(#main-content, .ly-hd, .ly-sb, .ly-paleta) :where(button, a, [role=button], summary) { transition-property: background-color, border-color, color, box-shadow, opacity, scale, translate, filter; transition-duration: var(--mt-rapido); transition-timing-function: var(--mt-e); }
#main-content :is(button, a, [role=button]):not(:disabled):active { translate: 0 1px; }
#main-content :is(button, a):is([class*="-btn"], [class*="-dark"], [class*="-prim"], [class*="-ico"], [class*="-chip"], [class*="-hoy"], [class*="-foil"], [class*="-claro"], [class*="-gen"], [class*="-mas"], [class*="-op"], .rounded-xl, .rounded-2xl, .rounded-lg, .rounded-full):not(:disabled):active,
.ly-ico:active, .ly-user:active, .ly-sb-x:active { translate: none; scale: .96; transition-duration: 60ms; }
.ly-link:not(.es-pronto):active { scale: .98; transition-duration: 60ms; }
#main-content :is(input, textarea, select) { transition: border-color var(--mt-rapido) var(--mt-e), box-shadow var(--mt-base) var(--mt-e), background-color var(--mt-rapido); }

/* ---------- Al elegir: chips, segmentados, permisos, filtros ---------- */
#main-content button[aria-pressed="true"], #main-content :is([class*="-chip"], [class*="-op"], .pe-sug button).on { animation: mtElige var(--mt-base) var(--mt-e); }
@keyframes mtElige { 0% { scale: .94; } 60% { scale: 1.02; } 100% { scale: 1; } }

/* ---------- Menús desplegables, listas de sugerencias y selectores propios ---------- */
:is(.ci-pop, .ci-combo-lista, .cs-pop, .rx-lista, .rl-pop, .pe-sug) { transform-origin: top center; animation: mtPop var(--mt-base) var(--mt-e); }
@keyframes mtPop { from { opacity: 0; scale: .97; translate: 0 -4px; } }

/* ---------- Entradas y salidas de contenido ---------- */
.mt-aparece { animation: mtEntra var(--mt-base) var(--mt-e) both; }
/* Filas que se agregan a una lista (al cargar, filtrar o guardar): llegan en cascada corta */
:where(.pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala, .pe-grupo, .rx-grupo) { animation: mtFila var(--mt-base) var(--mt-e) both; }
:where(.pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala):nth-child(2) { animation-delay: 15ms; }
:where(.pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala):nth-child(3) { animation-delay: 30ms; }
:where(.pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala):nth-child(4) { animation-delay: 45ms; }
:where(.pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala):nth-child(5) { animation-delay: 60ms; }
:where(.pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala):nth-child(n+6) { animation-delay: 75ms; }
@keyframes mtFila { from { opacity: 0; translate: 0 4px; } }
/* Cambio de selección: el contenido de la tarjeta se renueva sin parpadeo */
.mt-cambia { animation: mtCambia 200ms var(--mt-e); }
@keyframes mtCambia { from { opacity: .35; translate: 0 3px; } }
/* Guardado: la fila recién guardada se ilumina un instante */
.mt-destello { animation: mtDestello 900ms ease-out; }
:where(.pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala).mt-destello { animation: mtFila var(--mt-base) var(--mt-e) both, mtDestello 900ms ease-out; }
@keyframes mtDestello { 0%, 25% { box-shadow: inset 0 0 0 2px #0e7c72, 0 0 0 4px rgba(14, 124, 114, .18); } 100% { box-shadow: inset 0 0 0 2px transparent, 0 0 0 4px transparent; } }
/* Error de validación: sacudida breve del formulario */
.mt-sacude { animation: mtSacude 280ms cubic-bezier(.36, .07, .19, .97); }
@keyframes mtSacude { 20%, 60% { translate: -5px 0; } 40%, 80% { translate: 5px 0; } }
/* Eliminado: la fila se encoge y se va antes de quitarla */
.mt-sale { animation: mtQuita 180ms var(--mt-sale) forwards; pointer-events: none; }
@keyframes mtQuita { to { opacity: 0; translate: 12px 0; scale: .98; } }
/* ---------- Pantallas anteriores (inventario, nómina, POS, caja…) ----------
   Se conservan sus animaciones pero con los mismos tiempos cortos del resto del sistema */
:is(.aparece, .nom-aparece, .gc-seccion, .me-seccion, .mp-seccion, .sl-seccion, .ss-seccion) { animation-duration: .3s !important; animation-delay: calc(var(--d, 0ms) * .35) !important; }
.tarjeta-stat { animation-duration: .3s !important; }
:is(.fila-in, .nom-fila, .gc-fila, .sl-renglon) { animation-duration: .22s !important; animation-delay: calc(var(--d, 0ms) * .3) !important; }
:is(.pop, .gc-pop, .me-pop, .mp-pop, .sl-pop, .ss-pop) { animation-duration: .28s !important; }
:is(.fila-sale, .nom-sale, .gc-sale) { animation-duration: .18s !important; }
:is(.barra-stock) { animation-duration: .5s !important; }
:is(.gc-nueva, .sl-nuevo, .nom-destello) { animation-duration: .9s !important; }

/* Número que cambia */
.mt-num { animation: mtNum 220ms var(--mt-e); }
@keyframes mtNum { from { opacity: .3; translate: 0 -3px; } }

/* Utilidades propias (no dependen de Tailwind) */
@media (min-width: 1024px) { .ly-movil { display: none !important; } }
@media (max-width: 639.98px) { .ly-sm { display: none !important; } }
.ly-marca-tx, .ly-hd-tit { flex: 1; min-width: 0; }

/* Botón cuadrado de ícono */
.ly-ico { position: relative; flex: none; width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center; color: var(--ly-texto); background: var(--ly-papel); border: 1px solid var(--ly-linea); transition: background .15s, color .15s, border-color .15s; }
.ly-ico:hover { color: var(--ly-tinta); border-color: #c5d3cf; background: #f7faf9; }
.ly-ico .bi { font-size: 17px; }

/* =========================================================
   MENÚ LATERAL (verde credencial; el módulo activo es una pestaña que se une a la página)
   ========================================================= */
.ly-sb { --ancho: 264px; --sbf: #0a4743; position: relative; width: var(--ancho); height: 100%; flex: none; display: flex; flex-direction: column; z-index: 40; color: #d7ece7; background: linear-gradient(180deg, #0b524c 0%, var(--sbf) 45%, #062f2c 100%); transition: width .3s var(--ly-e), transform .3s var(--ly-e); }
.ly-sb-gl, .ly-sb-gl svg { position: absolute; left: 0; right: 0; bottom: 0; height: 260px; width: 100%; pointer-events: none; }
@media (max-width: 1023.98px) {
    .ly-sb { position: fixed; inset: 0 auto 0 0; transform: translateX(-100%); }
    .ly-sb.movil-abierto { transform: none; box-shadow: 0 30px 60px -20px rgba(11, 30, 28, .6); }
}
@media (min-width: 1024px) { .ly-sb.rail { --ancho: 78px; } .ly-sb.rail .ly-oculta-rail { display: none !important; } }

.ly-marca { position: relative; display: flex; align-items: center; gap: 12px; padding: 22px 18px 18px; }
.ly-sello { flex: none; display: block; width: 38px; height: 38px; transition: transform .3s var(--ly-e); }
.ly-sello svg { width: 100%; height: 100%; display: block; }
.ly-sello:hover { transform: rotate(90deg); }
.ly-logo { font: 700 21px/1 var(--ly-disp); font-stretch: 85%; letter-spacing: -.01em; color: #fff; }
.ly-clinica { margin-top: 5px; font: 500 11.5px/1.2 var(--ly-mono); letter-spacing: .05em; text-transform: uppercase; color: var(--ly-foil); opacity: .85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-sb-x { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; color: #d7ece7; background: rgba(255, 255, 255, .08); }
.ly-sb.rail .ly-marca { justify-content: center; padding-inline: 0; }

.ly-buscar-wrap { position: relative; padding: 0 14px 10px; }
.ly-buscar { display: flex; align-items: center; gap: 9px; height: 40px; padding: 0 8px 0 12px; border-radius: 11px; background: rgba(255, 255, 255, .07); border: 1px solid rgba(166, 239, 226, .14); transition: background .15s, border-color .15s; }
.ly-buscar:focus-within { background: rgba(255, 255, 255, .12); border-color: rgba(166, 239, 226, .45); }
.ly-buscar > .bi { font-size: 13px; color: var(--ly-foil); }
.ly-buscar input { flex: 1; min-width: 0; border: 0; outline: 0 !important; box-shadow: none !important; background: none; font: 500 13.5px var(--ly-ui); color: #fff; }
.ly-buscar input::placeholder { color: #9cc5bd; }
.ly-buscar button { color: #9cc5bd; font-size: 12px; }
.ly-sb kbd { font: 600 10.5px var(--ly-mono); border: 1px solid rgba(166, 239, 226, .25); border-radius: 6px; padding: 1px 5px; color: #9cc5bd; background: none; }
.ly-buscar-rail { display: none; width: 46px; height: 40px; margin: 0 auto; border-radius: 11px; place-items: center; color: var(--ly-foil); background: rgba(255, 255, 255, .07); }
.ly-sb.rail .ly-buscar-rail { display: grid; }

.ly-nav { position: relative; flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; padding: 2px 0 12px 14px; scrollbar-width: thin; scrollbar-color: rgba(166, 239, 226, .2) transparent; }
.ly-grupo { margin-top: 14px; }
.ly-grupo:first-child { margin-top: 2px; }
.ly-grupo-t { padding: 0 10px 6px; font-size: 12px; font-weight: 600; color: #8fbfb6; }
.ly-grupo-r { display: none; }
.ly-sb.rail .ly-grupo-r { display: block; width: 22px; height: 1px; margin: 0 auto 8px; background: rgba(166, 239, 226, .2); transform: translateX(-7px); }

.ly-link { position: relative; display: flex; align-items: center; gap: 12px; height: 40px; padding: 0 12px; margin: 1px 14px 1px 0; border-radius: 11px; font-size: 14px; font-weight: 500; color: #d7ece7; transition: background .15s, color .15s; }
.ly-link .bi { flex: none; width: 20px; text-align: center; font-size: 17px; color: #a9d3ca; transition: color .15s; }
.ly-link-t { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
a.ly-link:hover { background: rgba(255, 255, 255, .07); color: #fff; }
a.ly-link:hover .bi { color: var(--ly-foil); }
/* Pestaña activa: toma el color de la página y se une a ella con curvas invertidas */
.ly-link.es-activo { margin-right: 0; border-radius: 12px 0 0 12px; background: var(--ly-niebla); color: var(--ly-tinta); font-weight: 650; }
.ly-link.es-activo .bi { color: var(--ly-cred); }
.ly-link.es-activo:before, .ly-link.es-activo:after { content: ""; position: absolute; right: 0; width: 14px; height: 14px; background: transparent; pointer-events: none; }
.ly-link.es-activo:before { top: -14px; border-bottom-right-radius: 12px; box-shadow: 6px 6px 0 6px var(--ly-niebla); }
.ly-link.es-activo:after { bottom: -14px; border-top-right-radius: 12px; box-shadow: 6px -6px 0 6px var(--ly-niebla); }
.ly-link.es-pronto { cursor: default; opacity: .5; }
.ly-link em { font-style: normal; font: 500 11px var(--ly-mono); color: #9cc5bd; border: 1px solid rgba(166, 239, 226, .25); border-radius: 6px; padding: 0 5px; }
.ly-sb.rail .ly-nav { padding-left: 12px; }
.ly-sb.rail .ly-link { justify-content: center; padding: 0; height: 44px; margin-right: 12px; }
.ly-sb.rail .ly-link.es-activo { margin-right: 0; }
.ly-buscar-pac { width: calc(100% - 14px); display: flex; align-items: center; gap: 10px; margin-top: 12px; padding: 10px 12px; border-radius: 11px; border: 1px dashed rgba(166, 239, 226, .3); background: none; text-align: left; font-size: 13px; color: #d7ece7; }
.ly-buscar-pac .bi { color: var(--ly-foil); }
.ly-buscar-pac b { color: #fff; }

.ly-pie { position: relative; display: flex; align-items: center; gap: 9px; padding: 14px 18px 16px; font-size: 12.5px; color: #9cc5bd; border-top: 1px solid rgba(166, 239, 226, .12); }
.ly-pie b { margin-left: auto; font: 500 12.5px var(--ly-mono); color: #d7ece7; }
.ly-pie-p { display: grid; place-items: center; }
.ly-pie-p i { width: 8px; height: 8px; border-radius: 50%; background: #3ddc97; box-shadow: 0 0 0 3px rgba(61, 220, 151, .18); }
.ly-sb.rail .ly-pie { justify-content: center; padding-inline: 0; }

/* Etiqueta flotante del menú contraído */
.ly-tip { position: fixed; z-index: 70; transform: translateY(-50%); pointer-events: none; padding: 6px 10px; border-radius: 8px; background: var(--ly-tinta); color: #fff; font-size: 13px; font-weight: 550; white-space: nowrap; box-shadow: 0 10px 24px -12px rgba(11, 30, 28, .7); }

/* =========================================================
   ENCABEZADO (barra flotante)
   ========================================================= */
.ly-hd { position: sticky; top: 0; z-index: 20; padding: 14px 16px 10px; background: linear-gradient(var(--ly-niebla) 72%, rgba(238, 243, 241, 0)); transition: transform .24s var(--ly-e), opacity .18s ease; }
@media (min-width: 640px) { .ly-hd { padding-inline: 24px; } }
@media (min-width: 1024px) { .ly-hd { padding-inline: 32px; } }
.ly-hd.oculto { transform: translateY(-110%); opacity: 0; pointer-events: none; }
.ly-hd-in { display: flex; align-items: center; gap: 10px; padding: 8px 8px 8px 10px; border-radius: 18px; background: var(--ly-papel); border: 1px solid var(--ly-linea); box-shadow: 0 14px 30px -26px rgba(11, 30, 28, .55); }
.ly-rel { position: relative; }
.ly-ruta { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--ly-media); white-space: nowrap; overflow: hidden; }
.ly-ruta a { color: var(--ly-cred); }
.ly-ruta .bi-chevron-right { font-size: 9px; color: #a3b0ad; }
.ly-ruta b { font-weight: 600; color: var(--ly-cred); overflow: hidden; text-overflow: ellipsis; }
.ly-saludo { margin-top: 1px; font: 700 19px/1.15 var(--ly-disp); font-stretch: 88%; color: var(--ly-tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-ico { border-color: var(--ly-linea2); background: #f7faf9; }
.ly-reloj { display: none; align-items: center; gap: 10px; height: 44px; padding: 0 14px; border-radius: 12px; background: var(--ly-velo); color: var(--ly-cred); }
.ly-reloj > .bi { font-size: 16px; }
.ly-reloj span { display: flex; flex-direction: column; line-height: 1.1; }
.ly-reloj b { font: 600 15px var(--ly-mono); color: var(--ly-tinta); }
.ly-reloj small { font-size: 11.5px; font-weight: 500; color: var(--ly-cred); }
@media (min-width: 900px) { .ly-reloj { display: flex; } }
.ly-badge { position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 9px; background: var(--ly-rosa); color: #fff; font: 700 11px/18px var(--ly-ui); text-align: center; box-shadow: 0 0 0 2px var(--ly-papel); }

.ly-user { display: flex; align-items: center; gap: 10px; height: 44px; padding: 0 10px 0 3px; border-radius: 12px; border: 1px solid transparent; transition: background .15s, border-color .15s; }
.ly-user:hover, .ly-user.abierto { background: #f7faf9; border-color: var(--ly-linea2); }
.ly-user-tx { display: flex; flex-direction: column; text-align: left; line-height: 1.15; max-width: 170px; }
.ly-user-tx b { font-size: 13.5px; font-weight: 650; color: var(--ly-tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-user-tx small { font-size: 12px; color: var(--ly-cred); }
.ly-user > .bi { font-size: 11px; color: var(--ly-media); transition: transform .2s; }
.ly-user.abierto > .bi { transform: rotate(180deg); }
.ly-avatar { position: relative; flex: none; width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; color: var(--ly-foil); background: radial-gradient(circle at 30% 25%, #11766e, var(--ly-cred2)); }
.ly-avatar > span, .ly-avatar > span svg { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .55; }
.ly-avatar b { position: relative; font: 800 14px/1 var(--ly-disp); font-stretch: 80%; color: #fff; }

.ly-pop { position: absolute; right: 0; top: calc(100% + 10px); z-index: 30; background: var(--ly-papel); border: 1px solid var(--ly-linea); border-radius: 16px; box-shadow: 0 24px 48px -24px rgba(11, 30, 28, .5); overflow: hidden; }
.ly-notis { width: 320px; max-width: calc(100vw - 32px); }
.ly-pop-h { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid var(--ly-linea2); }
.ly-pop-h p { font: 750 16px var(--ly-disp); color: var(--ly-tinta); }
.ly-pop-h span { font-size: 12px; font-weight: 600; color: var(--ly-media); }
.ly-vacio { display: flex; gap: 12px; padding: 18px 16px 20px; color: var(--ly-media); font-size: 13px; }
.ly-vacio .bi { font-size: 22px; color: #08734f; }
.ly-vacio b { display: block; color: var(--ly-tinta); font-weight: 600; margin-bottom: 2px; }
.ly-menu-u { width: 300px; max-width: calc(100vw - 32px); padding: 8px; }
.ly-gafete { position: relative; overflow: hidden; display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 12px; color: #eafaf6; background: linear-gradient(150deg, #0d6a63, var(--ly-cred) 45%, var(--ly-cred2)); }
.ly-gafete-gl, .ly-gafete-gl svg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.ly-gafete > div { position: relative; min-width: 0; display: flex; flex-direction: column; }
.ly-gafete small { font: 500 11.5px var(--ly-mono); color: var(--ly-foil); }
.ly-gafete b { font: 750 17px/1.15 var(--ly-disp); font-stretch: 80%; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-gafete span { font-size: 12px; color: #cdeee7; }
.ly-avatar-g { width: 44px; height: 44px; background: rgba(7, 63, 59, .5); box-shadow: inset 0 0 0 1px rgba(166, 239, 226, .35); }
.ly-menu-mail { padding: 10px 8px 6px; font: 500 12px var(--ly-mono); color: var(--ly-media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-menu-l { display: grid; gap: 2px; }
.ly-menu-l a, .ly-menu-l button { width: 100%; display: flex; align-items: center; gap: 10px; height: 40px; padding: 0 10px; border-radius: 10px; font-size: 13.5px; font-weight: 550; color: var(--ly-tinta); text-align: left; }
.ly-menu-l a:hover, .ly-menu-l button:hover { background: var(--ly-niebla); }
.ly-menu-l .bi { width: 18px; color: var(--ly-cred); }
.ly-menu-l kbd { margin-left: auto; font: 600 10.5px var(--ly-mono); border: 1px solid var(--ly-linea); border-radius: 6px; padding: 1px 5px; color: var(--ly-media); }
.ly-menu-l .es-salir { color: var(--ly-rosa); }
.ly-menu-l .es-salir .bi { color: var(--ly-rosa); }
.ly-menu-l .es-salir:hover { background: var(--ly-rosav); }

/* =========================================================
   PALETA Ctrl+K
   ========================================================= */
.ly-paleta { position: fixed; inset: 0; z-index: 10050; display: flex; justify-content: center; align-items: flex-start; padding: 12vh 16px 16px; }
.ly-pal-velo { position: absolute; inset: 0; background: rgba(11, 30, 28, .42); backdrop-filter: blur(2px); }
.ly-pal { position: relative; width: 100%; max-width: 640px; max-height: 72vh; display: flex; flex-direction: column; background: var(--ly-papel); border-radius: 18px; border: 1px solid var(--ly-linea); box-shadow: 0 40px 80px -30px rgba(11, 30, 28, .65); overflow: hidden; }
.ly-pal-in { animation: mtPalIn .22s var(--ly-e); }
.ly-pal-out { animation: mtPalIn .12s ease-in reverse; }
@keyframes mtPalIn { from { opacity: 0; } }
.ly-pal-in .ly-pal { animation: mtPalSube .26s var(--ly-e); }
@keyframes mtPalSube { from { opacity: 0; transform: translateY(-8px) scale(.985); } }

.ly-pal-q { display: flex; align-items: center; gap: 12px; height: 58px; padding: 0 14px 0 18px; border-bottom: 1px solid var(--ly-linea2); }
.ly-pal-q > .bi { font-size: 17px; color: var(--ly-cred); }
.ly-pal-q input, .ly-pal-q input:focus-visible { outline: 0 !important; box-shadow: none !important; }
.ly-pal-q input { flex: 1; min-width: 0; border: 0; outline: 0; background: none; font: 450 16px var(--ly-ui); color: var(--ly-tinta); }
.ly-pal-q input::placeholder { color: var(--ly-media); }
.ly-pal-cargando { width: 16px; height: 16px; border-radius: 50%; border: 2px solid var(--ly-linea); border-top-color: var(--ly-teal); animation: mtGira .7s linear infinite; }
@keyframes mtGira { to { transform: rotate(360deg); } }

.ly-pal-lista { flex: 1; min-height: 0; overflow-y: auto; padding: 6px 8px 8px; scrollbar-width: thin; }
.ly-pal-g { padding: 10px 10px 4px; font-size: 12px; font-weight: 600; color: var(--ly-media); }
.ly-pal-r { position: relative; width: 100%; display: flex; align-items: center; gap: 12px; padding: 7px 10px; border-radius: 10px; text-align: left; color: var(--ly-texto); }
.ly-pal-r.on { background: var(--ly-velo); }
.ly-pal-r.on:before { content: ""; position: absolute; left: 0; top: 10px; bottom: 10px; width: 3px; border-radius: 0 3px 3px 0; background: var(--ly-cred); }
.ly-pal-i { flex: none; width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; background: var(--ly-niebla); color: var(--ly-cred); font-size: 16px; }
.ly-pal-i.es-pac { position: relative; border-radius: 50%; background: radial-gradient(circle at 30% 25%, #11766e, var(--ly-cred2)); color: var(--ly-foil); }
.ly-pal-ros, .ly-pal-ros svg { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .75; }
.ly-pal-tx { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.ly-pal-tx b { font-size: 14px; font-weight: 600; color: var(--ly-tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-pal-tx small { font-size: 12px; color: var(--ly-media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-pal-tx small.mono { font-family: var(--ly-mono); letter-spacing: .02em; }
.ly-pal-alg { flex: none; max-width: 40%; font-style: normal; font-size: 12px; font-weight: 600; color: var(--ly-rosa); background: var(--ly-rosav); padding: 2px 8px; border-radius: 999px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-pal-enter { font-size: 13px; color: var(--ly-media); opacity: 0; }
.ly-pal-r.on .ly-pal-enter { opacity: 1; }
.ly-pal-vacio { padding: 26px 18px 30px; text-align: center; font-size: 13px; color: var(--ly-media); }
.ly-pal-vacio b { display: block; font: 750 17px var(--ly-disp); color: var(--ly-tinta); margin-bottom: 4px; }
.ly-pal-pie { display: flex; align-items: center; gap: 14px; padding: 10px 16px; border-top: 1px solid var(--ly-linea2); font-size: 12px; color: var(--ly-media); background: #fafcfb; }
.ly-pal-pie kbd { margin-right: 3px; }
.ly-pal-ayuda { margin-left: auto; }
@media (max-width: 639.98px) { .ly-pal-ayuda { display: none; } }

@media (prefers-reduced-motion: reduce) {
    #ly-cortina, #ly-cortina * { animation: none !important; transition-duration: 1ms !important; }
    .mt-aparece, .mt-cambia, .mt-destello, .mt-sacude, .mt-sale, .mt-num, [class*="-pop"], .rx-lista, .ci-combo-lista, .pe-sug,
    .pe-fila, .rl-rol, .rx-fila, .cs-fi, .ci-prox button, .co-fila, .db-lista li, .db-sala, .pe-grupo, .rx-grupo,
    #main-content button[aria-pressed="true"], .swal2-popup, .swal2-container { animation: none !important; }
    .ly-sb, .ly-hd, .ly-gafete-in, .ly-gf-frente:after, .page-fade-in, .ly-pal-in, .ly-pal-in .ly-pal, .ly-pal-out { transition: none !important; animation: none !important; }
    .ly-gafete.volteado .ly-gafete-in { transform: none; }
    .ly-gafete.volteado .ly-gf-frente { visibility: hidden; }
    .ly-gf-reverso { transform: none; visibility: hidden; }
    .ly-gafete.volteado .ly-gf-reverso { visibility: visible; }
}

/* =========================================================
   CORTINA MEDITRACK (entre pantallas)
   ========================================================= */
#ly-cortina { position: fixed; inset: 0; z-index: 10050; display: grid; place-items: center; overflow: hidden; color: #eafaf6;
    background: radial-gradient(120% 90% at 50% 40%, #0f6c64 0%, #0b5a54 45%, #062f2c 100%);
    opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 200ms var(--mt-e), visibility 0s linear 200ms; }
.ly-cortina-on #ly-cortina { opacity: 1; visibility: visible; pointer-events: auto; transition: opacity 90ms var(--mt-e), visibility 0s; }
.ly-cortina-llega #ly-cortina { transition: none; }
.ly-cortina-sale #ly-cortina { opacity: 0; visibility: hidden; transition: opacity 160ms var(--mt-e), visibility 0s linear 160ms; }
.ly-cortina-gl, .ly-cortina-gl svg { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .3; pointer-events: none; }
.ly-cortina-in { position: relative; display: flex; flex-direction: column; align-items: center; gap: 14px; transition: scale 160ms var(--mt-e), opacity 120ms; }
.ly-cortina-on:not(.ly-cortina-llega) .ly-cortina-in { animation: lyCortinaIn 140ms var(--mt-e) both; }
.ly-cortina-sale .ly-cortina-in { scale: 1.04; opacity: 0; }
@keyframes lyCortinaIn { from { scale: .92; opacity: 0; } }
.ly-cortina-cruz { width: 76px; height: 76px; filter: drop-shadow(0 18px 30px rgba(0, 0, 0, .3)); }
.ly-cortina-cruz .latido { stroke-dasharray: 40; stroke-dashoffset: 40; animation: lyLatido 900ms var(--mt-e) 80ms infinite; }
@keyframes lyLatido { 0% { stroke-dashoffset: 40; } 55%, 100% { stroke-dashoffset: 0; } }
.ly-cortina-in b { font: 700 34px/1 var(--ly-disp); letter-spacing: -.01em; color: #fff; }
.ly-cortina-in small { margin-top: -8px; font: 600 12px var(--ly-mono); letter-spacing: .14em; text-transform: uppercase; color: var(--ly-foil); }
.ly-cortina-barra { position: relative; width: 120px; height: 3px; border-radius: 99px; overflow: hidden; background: rgba(166, 239, 226, .22); }
.ly-cortina-barra:after { content: ''; position: absolute; top: 0; bottom: 0; left: -40%; width: 40%; border-radius: 99px; background: var(--ly-foil); animation: lyBarra 800ms ease-in-out infinite; }
@keyframes lyBarra { to { left: 100%; } }

/* =========================================================
   DESPLEGABLES PROPIOS (reemplazan la lista nativa de <select>)
   El <select> original sigue en el formulario, oculto; se sincroniza en ambos sentidos.
   ========================================================= */
.ly-sel-nativo { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: 0 !important; opacity: 0 !important; pointer-events: none !important; overflow: hidden !important; border: 0 !important; }
.ly-sel-btn { display: flex !important; align-items: center; justify-content: space-between; gap: 8px; text-align: left; cursor: pointer; min-width: 0; -webkit-appearance: none; appearance: none; background-image: none !important; }
.ly-sel-btn > span { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-sel-btn > span.vacia { opacity: .6; }
.ly-sel-btn > .bi { flex: none; font-size: 12px; opacity: .7; transition: rotate var(--mt-base) var(--mt-e); }
.ly-sel-btn[aria-expanded=true] > .bi { rotate: 180deg; }
.ly-sel-btn[aria-expanded=true] { border-color: #0e7c72 !important; box-shadow: 0 0 0 3px rgba(14, 124, 114, .15) !important; }
.ly-sel-btn:disabled { cursor: not-allowed; opacity: .55; }
.ly-sel-pop { position: fixed; z-index: 10060; min-width: 180px; max-width: min(420px, calc(100vw - 16px)); padding: 4px; background: #fff; border: 1px solid #dbe4e1; border-radius: 12px;
    box-shadow: 0 18px 38px -18px rgba(11, 30, 28, .45); font: 450 14px/1.35 var(--ly-ui); color: #0b1e1c; transform-origin: top center; animation: mtPop var(--mt-base) var(--mt-e); display: flex; flex-direction: column; }
.ly-sel-pop.arriba { transform-origin: bottom center; }
.ly-sel-buscar { flex: none; margin: 2px 2px 6px; display: flex; align-items: center; gap: 8px; height: 36px; padding: 0 10px; border: 1px solid #dbe4e1; border-radius: 9px; background: #f8fbfa; }
.ly-sel-buscar .bi { color: #0b5a54; font-size: 13px; }
.ly-sel-buscar input { flex: 1; min-width: 0; border: 0 !important; outline: 0 !important; box-shadow: none !important; background: none; font: 450 14px var(--ly-ui); color: #0b1e1c; padding: 0; }
.ly-sel-lista { overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; }
.ly-sel-op { width: 100%; display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 0; border-radius: 8px; background: none; text-align: left; font: inherit; color: inherit; cursor: pointer; }
.ly-sel-op span { flex: 1; min-width: 0; }
.ly-sel-op .bi { flex: none; color: #0b5a54; visibility: hidden; }
.ly-sel-op.act { background: #eef3f1; }
.ly-sel-op.on { font-weight: 600; color: #0b5a54; }
.ly-sel-op.on .bi { visibility: visible; }
.ly-sel-op:disabled { opacity: .45; cursor: not-allowed; }
.ly-sel-op.vacia { color: #56625f; }
.ly-sel-g { padding: 8px 10px 4px; font-size: 11.5px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #56625f; }
.ly-sel-nada { padding: 10px; font-size: 13px; color: #56625f; }

/* =========================================================
   CALENDARIO PROPIO (para <input type="date">)
   ========================================================= */
.ly-fec { position: relative; display: block; min-width: 0; }
.ly-fec-txt { font-variant-numeric: tabular-nums; }
.ly-fec-ico { position: absolute; right: 4px; top: 50%; translate: 0 -50%; width: 32px; height: 32px; border: 0; border-radius: 8px; background: none; color: #0b5a54; display: grid; place-items: center; cursor: pointer; }
.ly-fec-ico:hover { background: #eef3f1; }
.ly-cal { width: 292px; padding: 10px; }
.ly-cal-h { display: flex; align-items: center; justify-content: space-between; gap: 4px; margin-bottom: 8px; }
.ly-cal-h button { height: 32px; min-width: 32px; border: 0; border-radius: 8px; background: none; display: grid; place-items: center; color: #2c3d3a; cursor: pointer; }
.ly-cal-h button:hover { background: #eef3f1; }
.ly-cal-t { display: inline-flex !important; align-items: center; gap: 6px; padding: 0 10px; font: 700 15px var(--ly-disp); color: #0b1e1c !important; text-transform: capitalize; }
.ly-cal-t .bi { font-size: 11px; color: #56625f; }
.ly-cal-g { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 2px; }
.ly-cal-g span { padding: 4px 0 6px; text-align: center; font-size: 12px; font-weight: 600; color: #56625f; }
.ly-cal-g button { height: 34px; border: 0; border-radius: 8px; background: none; font: 500 13.5px var(--ly-mono); color: #0b1e1c; cursor: pointer; }
.ly-cal-g button:hover:not(:disabled) { background: #eef3f1; }
.ly-cal-g button.fuera { color: #a8b3b0; }
.ly-cal-g button.hoy { box-shadow: inset 0 0 0 1.5px #0e7c72; }
.ly-cal-g button.on { background: #0b5a54 !important; color: #fff; box-shadow: none; }
.ly-cal-g button:disabled { color: #cdd5d2; cursor: not-allowed; }
.ly-cal-a { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 4px; margin-bottom: 8px; }
.ly-cal-m { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 4px; padding-top: 8px; border-top: 1px solid #edf2f1; }
.ly-cal-a button, .ly-cal-m button { height: 34px; border: 1px solid #dbe4e1; border-radius: 8px; background: #fff; font: 500 13px var(--ly-mono); color: #0b1e1c; cursor: pointer; }
.ly-cal-m button { font-family: var(--ly-ui); text-transform: capitalize; }
.ly-cal-a button:hover:not(:disabled), .ly-cal-m button:hover { border-color: #0e7c72; }
.ly-cal-a button.hoy { box-shadow: inset 0 0 0 1.5px #0e7c72; }
.ly-cal-a button.on, .ly-cal-m button.on { background: #0b5a54; border-color: #0b5a54; color: #fff; }
.ly-cal-a button:disabled { opacity: .35; cursor: not-allowed; }
.ly-cal-f { display: flex; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px solid #edf2f1; }
.ly-cal-f button { height: 30px; padding: 0 10px; border: 0; border-radius: 8px; background: none; font: 600 13px var(--ly-ui); color: #0b5a54; cursor: pointer; }
.ly-cal-f button:hover { background: #eef3f1; }
.ly-cal-f [data-borrar] { color: #56625f; }

/* =========================================================
   AJUSTES DE SWEETALERT2 (toasts y confirmaciones)
   ========================================================= */
.swal2-popup.swal2-modal.swal2-show { animation: mtModal 200ms var(--mt-e) !important; }
.swal2-popup.swal2-modal.swal2-hide { animation: mtModalSale 130ms var(--mt-sale) forwards !important; }
.swal2-container.swal2-backdrop-show { animation: mtVelo 180ms ease-out !important; }
.swal2-container.swal2-backdrop-hide { animation: mtVeloSale 130ms ease-in forwards !important; }
.swal2-popup.swal2-toast.swal2-show { animation: mtToast 240ms var(--mt-e) !important; }
.swal2-popup.swal2-toast.swal2-hide { animation: mtToastSale 160ms var(--mt-sale) forwards !important; }
.swal2-popup.swal2-modal { border-radius: 20px !important; font-family: var(--ly-ui) !important; }
.swal2-popup.swal2-modal .swal2-title { font-family: var(--ly-disp) !important; color: var(--ly-tinta) !important; }
.swal2-popup .swal2-actions button { border-radius: 11px !important; font-family: var(--ly-ui) !important; font-weight: 600 !important; transition: scale 60ms, filter 120ms, box-shadow 120ms !important; }
.swal2-popup .swal2-actions button:hover { filter: brightness(1.08); }
.swal2-popup .swal2-actions button:active { scale: .96; }
.swal2-validation-message { animation: mtSacude 280ms cubic-bezier(.36, .07, .19, .97); }
@keyframes mtModal { from { opacity: 0; scale: .96; translate: 0 8px; } }
@keyframes mtModalSale { to { opacity: 0; scale: .97; } }
@keyframes mtVelo { from { background: rgba(0, 0, 0, 0); } }
@keyframes mtVeloSale { to { background: rgba(0, 0, 0, 0); } }
@keyframes mtToast { from { opacity: 0; translate: 24px 0; } }
@keyframes mtToastSale { to { opacity: 0; translate: 16px 0; } }
.swal2-container.swal2-top-end,
.swal2-container.swal2-top-right { top: 1.25rem !important; right: 1.25rem !important; left: auto !important; bottom: auto !important; width: auto !important; max-width: 380px !important; background: transparent !important; pointer-events: none !important; }
.swal2-popup.swal2-toast { display: flex !important; align-items: center !important; width: 100% !important; max-width: 360px !important; background: rgba(255, 255, 255, .98) !important; border-radius: 14px !important; padding: .75rem 1rem !important; pointer-events: auto !important; box-shadow: 0 20px 40px -20px rgba(11, 30, 28, .45) !important; font-family: var(--ly-ui) !important; }
.swal2-toast .swal2-title { font-size: .85rem !important; font-weight: 600 !important; color: var(--ly-tinta) !important; margin: 0 0 0 .5rem !important; }
</style>
@endverbatim
</head>
<body class="ly-body flex h-screen overflow-hidden font-sans text-gray-800"
      x-data="layoutMeditrack()"
      @keydown.escape.window="if (!paleta) mobileOpen = false"
      @keydown.window.ctrl.k.prevent="abrirPaleta()"
      @keydown.window.meta.k.prevent="abrirPaleta()">

    <!-- CORTINA MEDITRACK: cubre la pantalla al pasar de una pantalla a otra -->
    <div id="ly-cortina" aria-hidden="true">
        <span class="ly-cortina-gl" id="ly-cortina-gl"></span>
        <div class="ly-cortina-in">
            <svg class="ly-cortina-cruz" viewBox="0 0 44 44" aria-hidden="true">
                <rect x="15" y="2" width="14" height="40" rx="4" fill="#a6efe2"/>
                <rect x="2" y="15" width="40" height="14" rx="4" fill="#a6efe2"/>
                <path class="latido" d="M12 22h6l1.8-4.5 3 9 1.8-4.5h7.4" stroke="#0b5a54" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <b>MediTrack</b>
            <small>{{ optional($clinica ?? null)->nombre ?? ($config['nombre_clinica'] ?? 'Sistema médico') }}</small>
            <i class="ly-cortina-barra"></i>
        </div>
    </div>

    <!-- BARRA DE PROGRESO DE NAVEGACIÓN -->
    <div id="top-progress-bar"></div>

    @php
        $user = Auth::user();
        $puede = fn ($modulo, $accion = 'ver') => $user ? (bool) $user->tienePermiso($modulo, $accion) : false;

        // Menú lateral. 'claves' alimenta la búsqueda de la paleta (Ctrl+K).
        $menu = [
            ['titulo' => null, 'items' => [
                ['texto' => 'Dashboard',         'ruta' => 'home',                'activo' => 'home',            'icono' => 'bi-grid-1x2',               'permiso' => null,            'claves' => 'inicio panel resumen estadisticas'],
            ]],
            ['titulo' => 'Gestión médica', 'items' => [
                ['texto' => 'Pacientes',         'ruta' => 'pacientes.index',     'activo' => 'pacientes.*',     'icono' => 'bi-people',                 'permiso' => 'Pacientes',     'claves' => 'expedientes credencial curp alergias'],
                ['texto' => 'Citas',             'ruta' => 'citas.index',         'activo' => 'citas.*',         'icono' => 'bi-calendar2-week',         'permiso' => 'Citas',         'claves' => 'agenda calendario turnos agendar'],
                ['texto' => 'Consultas',         'ruta' => 'consultas.index',     'activo' => 'consultas.*',     'icono' => 'bi-clipboard2-pulse',       'permiso' => 'Consultas',     'claves' => 'atencion diagnostico signos vitales'],
                ['texto' => 'Consultorios',      'ruta' => 'consultorios.index',  'activo' => 'consultorios.*',  'icono' => 'bi-hospital',               'permiso' => 'Consultorios',  'claves' => 'salas espacios'],
            ]],
            ['titulo' => 'Farmacia y finanzas', 'items' => [
                ['texto' => 'Terminal POS',      'ruta' => 'pos.index',           'activo' => 'pos.*',           'icono' => 'bi-shop',                   'permiso' => 'POS',           'claves' => 'venta vender cobrar punto de venta'],
                ['texto' => 'Caja central',      'ruta' => 'caja.index',          'activo' => 'caja.*',          'icono' => 'bi-cash-coin',              'permiso' => 'Caja Central',  'claves' => 'corte apertura dinero pagos'],
                ['texto' => 'Despacho farmacia', 'ruta' => 'farmacia.despacho',   'activo' => 'farmacia.*',      'icono' => 'bi-bag-check',              'permiso' => 'Inventario',    'claves' => 'entregar medicamentos surtir'],
                ['texto' => 'Inventario',        'ruta' => 'inventario.index',    'activo' => 'inventario.*',    'icono' => 'bi-box-seam',               'permiso' => 'Inventario',    'claves' => 'stock productos existencias lotes'],
                ['texto' => 'Proveedores',       'ruta' => 'proveedores.index',   'activo' => 'proveedores.*',   'icono' => 'bi-truck',                  'permiso' => 'Proveedores',   'claves' => 'compras laboratorios'],
                ['texto' => 'Recetas',           'ruta' => 'recetas.index',       'activo' => 'recetas.*',       'icono' => 'bi-file-earmark-medical',   'permiso' => 'Recetas',       'claves' => 'prescripcion medicamentos'],
                ['texto' => 'Facturación',       'ruta' => null,                  'activo' => 'facturacion.*',   'icono' => 'bi-receipt',                'permiso' => 'Facturación',   'claves' => 'facturas cfdi'],
            ]],
            ['titulo' => 'Administración', 'items' => [
                ['texto' => 'Personal',          'ruta' => 'personal.index',      'activo' => 'personal.*',      'icono' => 'bi-person-badge',           'permiso' => 'Personal',      'claves' => 'medicos doctores empleados nomina usuarios'],
                ['texto' => 'Roles y permisos',  'ruta' => 'roles.index',         'activo' => 'roles.*',         'icono' => 'bi-shield-lock',            'permiso' => 'Roles',         'claves' => 'accesos seguridad'],
                ['texto' => 'Reportes',          'ruta' => null,                  'activo' => 'reportes.*',      'icono' => 'bi-bar-chart-line',         'permiso' => 'Reportes',      'claves' => 'estadisticas informes'],
                ['texto' => 'Configuración',     'ruta' => 'configuracion.index', 'activo' => 'configuracion.*', 'icono' => 'bi-gear',                   'permiso' => 'Configuración', 'claves' => 'ajustes clinica aviso de privacidad cuenta'],
                ['texto' => 'Bitácora',          'ruta' => 'bitacora.index',      'activo' => 'bitacora.*',      'icono' => 'bi-journal-text',           'permiso' => 'Bitácora',      'claves' => 'historial registros auditoria'],
            ]],
        ];

        // Filtra por permisos, resuelve rutas y detecta la página actual.
        $seccionActual = null;
        $paginaActual  = 'Dashboard';
        $navJs = [];
        foreach ($menu as $i => $seccion) {
            $menu[$i]['items'] = collect($seccion['items'])
                ->filter(fn ($item) => is_null($item['permiso']) || $puede($item['permiso']))
                ->map(function ($item) use ($seccion, &$seccionActual, &$paginaActual, &$navJs) {
                    $item['pendiente'] = empty($item['ruta']) || !\Illuminate\Support\Facades\Route::has($item['ruta']);
                    $item['href']      = $item['pendiente'] ? null : route($item['ruta']);
                    $item['esActivo']  = request()->routeIs($item['activo']);
                    if ($item['esActivo']) {
                        $seccionActual = $seccion['titulo'];
                        $paginaActual  = $item['texto'];
                    }
                    if (!$item['pendiente']) {
                        $navJs[] = ['t' => $item['texto'], 's' => $seccion['titulo'] ?? 'General', 'i' => $item['icono'], 'u' => $item['href'], 'k' => $item['claves']];
                    }
                    return $item;
                })->values();
        }

        // Acciones rápidas de la paleta (solo las que el usuario puede hacer).
        $accionesJs = [];
        if (\Illuminate\Support\Facades\Route::has('pacientes.index') && $puede('Pacientes', 'crear')) {
            $accionesJs[] = ['t' => 'Registrar paciente', 'd' => 'Emite una credencial nueva', 'i' => 'bi-person-plus', 'u' => route('pacientes.index', ['nuevo' => 1]), 'k' => 'nuevo alta curp'];
        }
        if (\Illuminate\Support\Facades\Route::has('citas.index') && $puede('Citas', 'crear')) {
            $accionesJs[] = ['t' => 'Agendar una cita', 'd' => 'Abre la agenda de citas', 'i' => 'bi-calendar-plus', 'u' => route('citas.index'), 'k' => 'nueva cita calendario'];
        }
        if (\Illuminate\Support\Facades\Route::has('pos.index') && $puede('POS')) {
            $accionesJs[] = ['t' => 'Cobrar una venta', 'd' => 'Abre la terminal POS', 'i' => 'bi-upc-scan', 'u' => route('pos.index'), 'k' => 'vender ticket'];
        }

        // Datos de quien inició sesión (para el gafete).
        $nombreUsuario = trim(optional(optional($user)->personal)->nombre_completo ?: (optional($user)->name ?? '')) ?: 'Usuario';
        $palabras      = collect(preg_split('/\s+/', $nombreUsuario))->filter()->values();
        $primerNombre  = \Illuminate\Support\Str::title(mb_strtolower($palabras->first() ?? 'Usuario'));
        $iniciales     = $palabras->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'U';
        $rolUsuario    = optional(optional($user)->rol)->nombre ?? (optional($user)->id === 1 ? 'Super Admin' : 'Personal');
        $nombreClinica = optional($clinica ?? null)->nombre ?? ($config['nombre_clinica'] ?? null);
        $folioUsuario  = 'MT-' . str_pad((string) (optional($user)->id ?? 0), 4, '0', STR_PAD_LEFT);
        $puedeConfig   = \Illuminate\Support\Facades\Route::has('configuracion.index') && $puede('Configuración');

        $totalNotificaciones = (int) ($totalNotificaciones ?? 0);
    @endphp

    <!-- FONDO OSCURO EN MÓVIL -->
    <div x-show="mobileOpen" x-cloak
         x-transition.opacity.duration.200ms
         @click="mobileOpen = false"
         class="ly-velo-movil ly-movil"></div>

    {{-- Barra lateral: marca, buscador, navegación y gafete del usuario --}}
<aside class="ly-sb" :class="{ 'movil-abierto': mobileOpen, 'rail': !sidebarOpen && ancho >= 1024 }" aria-label="Menú principal">
    <span class="ly-sb-gl" x-ref="sbGl" aria-hidden="true"></span>

    <!-- MARCA -->
    <div class="ly-marca">
        <a href="{{ route('home') }}" class="ly-sello" aria-label="Ir al inicio" @mouseenter="mostrarTip($el, 'Inicio')" @mouseleave="ocultarTip()">
            <svg viewBox="0 0 44 44" aria-hidden="true">
                <rect x="15" y="2" width="14" height="40" rx="4" fill="#a6efe2"/>
                <rect x="2" y="15" width="40" height="14" rx="4" fill="#a6efe2"/>
                <path d="M16.5 22h3.5l1.5-4 2.5 8 1.5-4h2.5" stroke="#0b5a54" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
        <div class="ly-oculta-rail ly-marca-tx">
            <p class="ly-logo">MediTrack</p>
            <p class="ly-clinica" title="{{ $nombreClinica }}">{{ $nombreClinica ?: 'Sistema clínico' }}</p>
        </div>
        <button type="button" class="ly-sb-x ly-movil" x-show="ancho < 1024" @click="mobileOpen = false" aria-label="Cerrar menú"><i class="bi bi-x-lg"></i></button>
    </div>

    <!-- BUSCADOR: filtra los módulos; Enter abre el primero -->
    <div class="ly-buscar-wrap">
        <button type="button" class="ly-buscar-rail" @click="enfocarBuscador()" @mouseenter="mostrarTip($el, 'Buscar (Ctrl K)')" @mouseleave="ocultarTip()" aria-label="Buscar módulo">
            <i class="bi bi-search"></i>
        </button>
        <label class="ly-buscar ly-oculta-rail">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="text" x-ref="inputBuscadorSidebar" x-model="buscarMenu" placeholder="Ir a un módulo…" autocomplete="off" aria-label="Ir a un módulo"
                   @keydown.enter.prevent="irPrimerModulo()" @keydown.escape="buscarMenu = ''; $event.target.blur()">
            <button type="button" x-show="buscarMenu" x-cloak @click="buscarMenu = ''" aria-label="Limpiar"><i class="bi bi-x-circle-fill"></i></button>
            <kbd x-show="!buscarMenu">Ctrl K</kbd>
        </label>
    </div>

    <!-- NAVEGACIÓN -->
    <nav class="ly-nav" aria-label="Módulos">
        @foreach ($menu as $seccion)
            @continue($seccion['items']->isEmpty())
            @php $textosSeccion = $seccion['items']->map(fn ($it) => $it['texto'] . ' ' . ($seccion['titulo'] ?? '') . ' ' . $it['claves'])->values(); @endphp
            <div class="ly-grupo" x-show="algunoCoincide(@js($textosSeccion))">
                @if ($seccion['titulo'])
                    <p class="ly-grupo-t ly-oculta-rail">{{ $seccion['titulo'] }}</p>
                    <span class="ly-grupo-r" aria-hidden="true"></span>
                @endif
                @foreach ($seccion['items'] as $item)
                    @php
                        $tipTexto = $item['texto'] . ($item['pendiente'] ? ' · Pronto' : '');
                        $textoBusqueda = $item['texto'] . ' ' . ($seccion['titulo'] ?? '') . ' ' . $item['claves'];
                    @endphp
                    @if ($item['pendiente'])
                        <span class="ly-link es-pronto" aria-disabled="true" x-show="coincideMenu(@js($textoBusqueda))"
                              @mouseenter="mostrarTip($el, @js($tipTexto))" @mouseleave="ocultarTip()">
                            <i class="bi {{ $item['icono'] }}"></i>
                            <span class="ly-oculta-rail ly-link-t">{{ $item['texto'] }}</span>
                            <em class="ly-oculta-rail">Pronto</em>
                        </span>
                    @else
                        <a href="{{ $item['href'] }}" class="ly-link {{ $item['esActivo'] ? 'es-activo' : '' }}" data-modulo="{{ $item['texto'] }}"
                           x-show="coincideMenu(@js($textoBusqueda))"
                           @if ($item['esActivo']) aria-current="page" @endif
                           @mouseenter="mostrarTip($el, @js($tipTexto))" @mouseleave="ocultarTip()"
                           @click="recordar(@js($item['texto']))">
                            <i class="bi {{ $item['icono'] }}{{ $item['esActivo'] ? '-fill' : '' }}"></i>
                            <span class="ly-oculta-rail ly-link-t">{{ $item['texto'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        @endforeach

        <!-- Sin módulo que coincida: buscar en pacientes -->
        <button type="button" class="ly-buscar-pac ly-oculta-rail" x-show="buscarMenu.trim().length >= 2" x-cloak @click="abrirPaleta(buscarMenu)">
            <i class="bi bi-person-vcard"></i>
            <span>Buscar «<b x-text="buscarMenu.trim()"></b>» en pacientes</span>
        </button>
    </nav>

    <!-- PIE: estado del sistema -->
    <div class="ly-pie">
        <span class="ly-pie-p" @mouseenter="mostrarTip($el, 'Sistema en línea')" @mouseleave="ocultarTip()"><i></i></span>
        <span class="ly-oculta-rail">Sistema en línea</span>
        <b class="ly-oculta-rail t" x-text="hora"></b>
    </div>
</aside>

    <!-- ETIQUETA FLOTANTE (menú contraído) -->
    <div x-show="tip.texto && !sidebarOpen && ancho >= 1024" x-cloak
         :style="`top:${tip.top}px; left:${tip.left}px`"
         class="ly-tip" role="tooltip"><span x-text="tip.texto"></span></div>

    <!-- ÁREA PRINCIPAL -->
    <div class="flex-1 min-w-0 flex flex-col overflow-y-auto"
         @scroll.passive="alScrollear($el)"
         @mousemove.throttle.100ms="alMoverCursor($event,$el)">

        {{-- Encabezado: título de la página, hora y notificaciones --}}
<header class="ly-hd" :class="{ 'oculto': !headerVisible }" @mouseenter="headerHover = true" @mouseleave="headerHover = false">
    <div class="ly-hd-in">
        <button type="button" class="ly-ico" @click="alternarMenu()"
                :aria-label="(ancho < 1024 ? mobileOpen : sidebarOpen) ? 'Contraer menú' : 'Mostrar menú'"
                :aria-expanded="(ancho < 1024 ? mobileOpen : sidebarOpen).toString()">
            <i class="bi" :class="ancho < 1024 ? 'bi-list' : (sidebarOpen ? 'bi-text-indent-right' : 'bi-text-indent-left')"></i>
        </button>

        <div class="ly-hd-tit">
            <nav class="ly-ruta" aria-label="Ruta">
                <a href="{{ route('home') }}" aria-label="Inicio"><i class="bi bi-house-door"></i></a>
                @if ($seccionActual)<i class="bi bi-chevron-right"></i><span class="ly-sm">{{ $seccionActual }}</span>@endif
                <i class="bi bi-chevron-right"></i><b>{{ $paginaActual }}</b>
            </nav>
            <p class="ly-saludo"><span x-text="saludo">Hola</span>, {{ $primerNombre }}</p>
        </div>

        <button type="button" class="ly-ico ly-movil" @click="abrirPaleta()" aria-label="Buscar"><i class="bi bi-search"></i></button>

        <div class="ly-reloj" aria-label="Hora actual">
            <i class="bi bi-clock"></i>
            <span><b class="t" x-text="hora"></b><small x-text="fecha"></small></span>
        </div>

        <!-- Notificaciones -->
        <div x-data="{ abierto: false }" class="ly-rel" @keydown.escape.stop="abierto = false">
            <button type="button" class="ly-ico" @click="abierto = !abierto" @click.outside="abierto = false" :aria-expanded="abierto.toString()"
                    aria-label="Notificaciones{{ $totalNotificaciones ? ': ' . $totalNotificaciones . ' nuevas' : '' }}">
                <i class="bi bi-bell"></i>
                @if ($totalNotificaciones > 0)<span class="ly-badge t">{{ $totalNotificaciones > 9 ? '9+' : $totalNotificaciones }}</span>@endif
            </button>
            <div x-show="abierto" x-cloak x-transition.origin.top.right class="ly-pop ly-notis">
                <div class="ly-pop-h"><p>Notificaciones</p><span class="t">{{ $totalNotificaciones }} nuevas</span></div>
                <div class="ly-vacio"><i class="bi bi-check2-circle"></i><p><b>Todo al día</b>Aquí aparecerán los avisos de citas y de inventario.</p></div>
            </div>
        </div>

        <!-- Usuario -->
        <div x-data="{ abierto: false }" class="ly-rel" @keydown.escape.stop="abierto = false">
            <button type="button" class="ly-user" :class="{ 'abierto': abierto }" @click="abierto = !abierto" @click.outside="abierto = false" :aria-expanded="abierto.toString()" aria-label="Mi cuenta">
                <span class="ly-avatar"><span x-html="roseta(semilla, 38)"></span><b>{{ $iniciales }}</b></span>
                <span class="ly-user-tx ly-sm"><b>{{ $nombreUsuario }}</b><small>{{ $rolUsuario }}</small></span>
                <i class="bi bi-chevron-down ly-sm"></i>
            </button>
            <div x-show="abierto" x-cloak x-transition.origin.top.right class="ly-pop ly-menu-u">
                <div class="ly-gafete">
                    <span class="ly-gafete-gl" x-html="guilloche(semilla)"></span>
                    <span class="ly-avatar ly-avatar-g"><span x-html="roseta(semilla, 44)"></span><b>{{ $iniciales }}</b></span>
                    <div>
                        <small class="t">Gafete · {{ $folioUsuario }}</small>
                        <b>{{ $nombreUsuario }}</b>
                        <span>{{ $rolUsuario }}</span>
                    </div>
                </div>
                <p class="ly-menu-mail" title="{{ $user->email ?? '' }}">{{ $user->email ?? '' }}</p>
                <div class="ly-menu-l">
                    @if ($puedeConfig)
                        <a href="{{ route('configuracion.index') }}"><i class="bi bi-gear"></i> Mi cuenta y configuración</a>
                    @endif
                    <button type="button" @click="abierto = false; abrirPaleta()"><i class="bi bi-search"></i> Buscar en el sistema <kbd>Ctrl K</kbd></button>
                    <form method="POST" action="{{ route('logout') }}" id="ly-salir">
                        @csrf
                        <button type="submit" class="es-salir"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

        <!-- CONTENIDO DINÁMICO DE LAS VISTAS DE LARAVEL -->
        <main id="main-content" class="px-4 sm:px-6 lg:px-8 pt-3 pb-8 space-y-6 w-full min-w-0 flex-1 block page-fade-in">
            @yield('content')
        </main>
    </div>

    {{-- Paleta de búsqueda global (Ctrl+K) --}}
<div class="ly-paleta" x-show="paleta" x-cloak
     x-transition:enter="ly-pal-in" x-transition:leave="ly-pal-out"
     @keydown.escape.prevent.stop="cerrarPaleta()">
    <div class="ly-pal-velo" @click="cerrarPaleta()"></div>

    <div class="ly-pal" role="dialog" aria-modal="true" aria-label="Buscar en MediTrack">
        <div class="ly-pal-q">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="text" x-ref="pq" x-model="q"
                   @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)"
                   @keydown.enter.prevent="abrirResultado(resultados[sel])"
                   placeholder="Busca un paciente, un módulo o una acción…" autocomplete="off" spellcheck="false"
                   role="combobox" aria-expanded="true" aria-controls="ly-pal-lista"
                   :aria-activedescendant="resultados.length ? 'ly-r-' + sel : null">
            <span class="ly-pal-cargando" x-show="buscando" aria-hidden="true"></span>
            <kbd>Esc</kbd>
        </div>

        <div class="ly-pal-lista" id="ly-pal-lista" role="listbox" x-ref="plista">
            <template x-for="(r, i) in resultados" :key="r.clave">
                <div>
                    <p class="ly-pal-g" x-show="i === 0 || resultados[i - 1].grupo !== r.grupo" x-text="r.grupo"></p>
                    <button type="button" class="ly-pal-r" role="option" :id="'ly-r-' + i"
                            :aria-selected="(i === sel).toString()" :class="{ 'on': i === sel }"
                            @mousemove="sel = i" @click="abrirResultado(r)">
                        <span class="ly-pal-i" :class="r.tipo === 'paciente' && 'es-pac'">
                            <template x-if="r.tipo === 'paciente'"><span class="ly-pal-ros" x-html="roseta(r.semilla, 32)"></span></template>
                            <template x-if="r.tipo !== 'paciente'"><i class="bi" :class="r.icono"></i></template>
                        </span>
                        <span class="ly-pal-tx">
                            <b x-text="r.titulo"></b>
                            <small :class="r.tipo === 'paciente' && 't mono'" x-text="r.sub"></small>
                        </span>
                        <em class="ly-pal-alg" x-show="r.alergia" x-text="r.alergia"></em>
                        <i class="bi bi-arrow-return-left ly-pal-enter" aria-hidden="true"></i>
                    </button>
                </div>
            </template>

            <div class="ly-pal-vacio" x-show="!resultados.length && !buscando">
                <template x-if="q.trim().length >= 2">
                    <p><b>Sin resultados para «<span x-text="q.trim()"></span>»</b>Prueba con el apellido, la CURP o el celular del paciente.</p>
                </template>
                <template x-if="q.trim().length < 2">
                    <p><b>Escribe para buscar</b>Pacientes, módulos y acciones rápidas.</p>
                </template>
            </div>
        </div>

        <div class="ly-pal-pie">
            <span><kbd>↑</kbd><kbd>↓</kbd> moverse</span>
            <span><kbd>↵</kbd> abrir</span>
            <span><kbd>Esc</kbd> cerrar</span>
            <span class="ly-pal-ayuda">Busca pacientes por nombre, CURP, código o celular</span>
        </div>
    </div>
</div>

    <script type="application/json" id="ly-nav">{!! json_encode(['modulos' => $navJs, 'acciones' => $accionesJs, 'buscar' => \Illuminate\Support\Facades\Route::has('buscar.global') ? route('buscar.global') : null, 'semilla' => optional($user)->email ?? $nombreUsuario], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

    <!-- BARRA DE PROGRESO: aparece solo si la página tarda más de 120 ms en llegar -->
    <script>
        (() => {
            const barra = document.getElementById('top-progress-bar');
            let espera = null;
            const iniciar = () => {
                clearTimeout(espera);
                espera = setTimeout(() => { barra.style.opacity = '1'; barra.style.width = '72%'; }, 120);
            };
            const limpiar = () => { clearTimeout(espera); barra.style.transition = 'none'; barra.style.opacity = '0'; barra.style.width = '0'; requestAnimationFrame(() => { barra.style.transition = ''; }); };
            document.addEventListener('click', e => {
                const a = e.target.closest('a[href]');
                if (!a || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank' || a.hasAttribute('download')) return;
                const url = new URL(a.href, location.href);
                if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search) || a.getAttribute('href').startsWith('#')) return;
                iniciar();
            });
            document.addEventListener('submit', e => { if (!e.defaultPrevented && !e.target.hasAttribute('data-ajax')) iniciar(); });
            window.addEventListener('beforeunload', () => { if (barra.style.opacity === '1') barra.style.width = '100%'; });
            window.addEventListener('pageshow', limpiar);
        })();
    </script>

    <!-- CORTINA, DESPLEGABLES Y CALENDARIOS -->
    <script>
        (() => {
            const raiz = document.documentElement;
            const quieto = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* ---------- Cortina MediTrack ---------- */
            let seguro = null;
            window.mtCortina = () => {
                try { sessionStorage.setItem('ly-cortina', String(Date.now())); } catch (e) {}
                raiz.classList.remove('ly-cortina-sale', 'ly-cortina-llega');
                raiz.classList.add('ly-cortina-on');
                clearTimeout(seguro);
                seguro = setTimeout(retirar, 8000); // por si la navegación se cancela (descarga, aviso del navegador)
            };
            function retirar() {
                if (!raiz.classList.contains('ly-cortina-on')) return;
                raiz.classList.add('ly-cortina-sale');
                setTimeout(() => raiz.classList.remove('ly-cortina-on', 'ly-cortina-sale', 'ly-cortina-llega'), 180);
            }
            const t0 = performance.now();
            const alListo = () => {
                const gl = document.getElementById('ly-cortina-gl');
                if (gl && typeof mtGuilloche === 'function') gl.innerHTML = mtGuilloche('MEDITRACK');
                if (raiz.classList.contains('ly-cortina-llega')) setTimeout(retirar, Math.max(0, 120 - (performance.now() - t0)));
            };
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', alListo); else alListo();
            window.addEventListener('pageshow', e => { if (e.persisted) { raiz.classList.remove('ly-cortina-on', 'ly-cortina-sale', 'ly-cortina-llega'); } });

            const esSalidaInterna = a => {
                if (!a || !a.href || a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-sin-cortina')) return false;
                const h = a.getAttribute('href') || '';
                if (h.startsWith('#') || h.startsWith('javascript:') || h.startsWith('mailto:') || h.startsWith('tel:')) return false;
                const u = new URL(a.href, location.href);
                if (u.origin !== location.origin) return false;
                if (u.pathname === location.pathname && u.search === location.search) return false;
                return !/pdf|ticket|corte|descargar|exportar/i.test(u.pathname);
            };
            document.addEventListener('click', e => {
                if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                const a = e.target.closest('a[href]');
                if (esSalidaInterna(a)) mtCortina();
            });
            document.addEventListener('submit', e => {
                const f = e.target;
                if (e.defaultPrevented || f.target === '_blank' || f.hasAttribute('data-sin-cortina')) return;
                if (/pdf|ticket|corte|descargar|exportar/i.test(f.action || '')) return;
                mtCortina();
            });

            /* ---------- Desplegables propios para <select> ---------- */
            const PROTO = HTMLSelectElement.prototype;
            const dValor = Object.getOwnPropertyDescriptor(PROTO, 'value');
            const dIndice = Object.getOwnPropertyDescriptor(PROTO, 'selectedIndex');
            const norm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            let abierto = null;

            // Copia el aspecto calculado del control original (bordes, relleno, letra, color, alto, ancho)
            const PROPS = ['borderTopWidth','borderRightWidth','borderBottomWidth','borderLeftWidth','borderTopStyle','borderRightStyle','borderBottomStyle','borderLeftStyle',
                'borderTopColor','borderRightColor','borderBottomColor','borderLeftColor','borderTopLeftRadius','borderTopRightRadius','borderBottomLeftRadius','borderBottomRightRadius',
                'backgroundColor','paddingTop','paddingRight','paddingBottom','paddingLeft','height','minHeight','fontSize','fontWeight','fontFamily','color','lineHeight','letterSpacing','textTransform','boxShadow'];
            function copiarAspecto(orig, nuevo) {
                const cs = getComputedStyle(orig);
                PROPS.forEach(k => { const v = cs[k]; if (v && v !== 'auto') nuevo.style[k] = v; });
                // Fondo gris del navegador o transparente: el campo se ve en blanco como los demás.
                if (/^rgb\(239, 239, 239\)$|^rgba\(0, 0, 0, 0\)$|^transparent$/.test(cs.backgroundColor)) nuevo.style.backgroundColor = '#fff';
                if (cs.height === 'auto' || cs.height === '0px') nuevo.style.removeProperty('height');
                const padre = orig.parentElement;
                const ancho = orig.offsetWidth, anchoPadre = padre ? padre.clientWidth : 0;
                const llena = /(^|\s)w-full(\s|$)/.test(orig.className) || (ancho && anchoPadre && ancho >= anchoPadre - 4) || cs.width === '100%';
                if (llena || !ancho) nuevo.style.width = '100%';
                else nuevo.style.minWidth = ancho + 'px';
                if (cs.display === 'none' && !orig.style.display) nuevo.style.width = '100%';
                nuevo.style.marginTop = cs.marginTop; nuevo.style.marginBottom = cs.marginBottom;
            }

            function mejorar(sel) {
                if (sel.dataset.lySel || sel.multiple || sel.size > 1 || sel.hasAttribute('data-nativo') || sel.closest('.flatpickr-calendar, .swal2-container') || sel.classList.contains('swal2-select')) return;
                sel.dataset.lySel = '1';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = (sel.className || '') + ' ly-sel-btn';
                btn.setAttribute('aria-haspopup', 'listbox');
                btn.setAttribute('aria-expanded', 'false');
                if (sel.id) { btn.id = sel.id + '-ly'; document.querySelectorAll('label[for="' + sel.id + '"]').forEach(l => l.setAttribute('for', btn.id)); }
                btn.innerHTML = '<span></span><i class="bi bi-chevron-down" aria-hidden="true"></i>';
                copiarAspecto(sel, btn);
                sel.insertAdjacentElement('afterend', btn);
                sel.classList.add('ly-sel-nativo');
                sel.tabIndex = -1;
                sel.setAttribute('aria-hidden', 'true');

                const pintar = () => {
                    const op = sel.options[sel.selectedIndex];
                    const sp = btn.firstElementChild;
                    sp.textContent = op ? op.textContent.trim() : (sel.getAttribute('placeholder') || 'Elegir');
                    sp.classList.toggle('vacia', !op || (op.value === '' && (op.disabled || op.hidden || /^\s*(selecc|elig|escoge|sin |ning|--|—|\.\.\.)/i.test(op.textContent))));
                    btn.disabled = sel.disabled;
                    const oculto = sel.style.display === 'none';
                    btn.style.display = oculto ? 'none' : '';
                    if (sel.required) btn.setAttribute('aria-required', 'true');
                };
                sel._lyPintar = pintar;
                Object.defineProperty(sel, 'value', { configurable: true, get() { return dValor.get.call(this); }, set(v) { dValor.set.call(this, v); pintar(); } });
                Object.defineProperty(sel, 'selectedIndex', { configurable: true, get() { return dIndice.get.call(this); }, set(v) { dIndice.set.call(this, v); pintar(); } });
                sel.addEventListener('change', pintar);
                new MutationObserver(() => { pintar(); if (abierto && abierto.sel === sel) dibujarLista(); })
                    .observe(sel, { childList: true, subtree: true, attributes: true, attributeFilter: ['selected', 'disabled', 'style', 'class', 'label'] });
                const form = sel.form; if (form) form.addEventListener('reset', () => setTimeout(pintar));
                btn.addEventListener('click', () => abierto && abierto.sel === sel ? cerrar() : abrir(sel, btn));
                btn.addEventListener('keydown', e => {
                    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key) && !(abierto && abierto.sel === sel)) { e.preventDefault(); abrir(sel, btn); }
                });
                pintar();
            }

            function opcionesDe(sel) {
                const out = [];
                [...sel.children].forEach(n => {
                    if (n.tagName === 'OPTGROUP') { out.push({ grupo: n.label }); [...n.children].forEach(o => out.push({ o })); }
                    else if (n.tagName === 'OPTION') out.push({ o: n });
                });
                return out.filter(x => x.grupo || !x.o.hidden);
            }

            function abrir(sel, btn) {
                cerrar();
                const pop = document.createElement('div');
                pop.className = 'ly-sel-pop';
                pop.setAttribute('role', 'listbox');
                const muchas = sel.options.length > 8;
                pop.innerHTML = (muchas ? '<label class="ly-sel-buscar"><i class="bi bi-search"></i><input type="text" placeholder="Buscar…" autocomplete="off" aria-label="Buscar opción"></label>' : '') + '<div class="ly-sel-lista"></div>';
                document.body.appendChild(pop);
                abierto = { sel, btn, pop, q: '', act: -1, letras: '', tLetras: 0 };
                btn.setAttribute('aria-expanded', 'true');
                dibujarLista(true);
                colocar();
                const buscar = pop.querySelector('input');
                if (buscar) {
                    buscar.addEventListener('input', () => { abierto.q = buscar.value; dibujarLista(true); });
                    buscar.addEventListener('keydown', teclado);
                    setTimeout(() => buscar.focus({ preventScroll: true }), 0);
                } else btn.focus({ preventScroll: true });
            }

            function dibujarLista(reiniciar) {
                if (!abierto) return;
                const { sel, pop } = abierto;
                const lista = pop.querySelector('.ly-sel-lista');
                const q = norm(abierto.q);
                const items = opcionesDe(sel).filter(x => x.grupo || !q || norm(x.o.textContent).includes(q));
                lista.innerHTML = '';
                let n = 0;
                items.forEach(x => {
                    if (x.grupo) { const g = document.createElement('div'); g.className = 'ly-sel-g'; g.textContent = x.grupo; lista.appendChild(g); return; }
                    const b = document.createElement('button');
                    b.type = 'button'; b.className = 'ly-sel-op'; b.setAttribute('role', 'option');
                    b.disabled = x.o.disabled || (x.o.parentNode.tagName === 'OPTGROUP' && x.o.parentNode.disabled);
                    const on = x.o.index === sel.selectedIndex;
                    b.classList.toggle('on', on); b.classList.toggle('vacia', x.o.value === '');
                    b.setAttribute('aria-selected', String(on));
                    b.dataset.i = x.o.index;
                    b.innerHTML = '<span></span><i class="bi bi-check2"></i>';
                    b.firstChild.textContent = x.o.textContent.trim();
                    b.addEventListener('click', () => elegir(x.o.index));
                    b.addEventListener('mousemove', () => marcar([...lista.querySelectorAll('.ly-sel-op')].indexOf(b), false));
                    lista.appendChild(b); n++;
                });
                if (!n) { const v = document.createElement('p'); v.className = 'ly-sel-nada'; v.textContent = 'Sin coincidencias'; lista.appendChild(v); }
                if (reiniciar) {
                    const ops = [...lista.querySelectorAll('.ly-sel-op')];
                    const i = ops.findIndex(b => Number(b.dataset.i) === sel.selectedIndex);
                    marcar(i >= 0 ? i : ops.findIndex(b => !b.disabled), true);
                }
            }

            function marcar(i, desplazar) {
                if (!abierto) return;
                const ops = [...abierto.pop.querySelectorAll('.ly-sel-op')];
                ops.forEach(b => b.classList.remove('act'));
                if (i < 0 || !ops[i]) { abierto.act = -1; return; }
                abierto.act = i; ops[i].classList.add('act');
                if (desplazar) ops[i].scrollIntoView({ block: 'nearest' });
            }

            function elegir(indice) {
                if (!abierto) return;
                const { sel, btn } = abierto;
                const cambio = sel.selectedIndex !== indice;
                dIndice.set.call(sel, indice);
                sel._lyPintar && sel._lyPintar();
                cerrar();
                btn.focus({ preventScroll: true });
                if (cambio) {
                    sel.dispatchEvent(new Event('input', { bubbles: true }));
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            function colocar() {
                if (!abierto) return;
                const { btn, pop } = abierto;
                const r = btn.getBoundingClientRect();
                const vh = window.innerHeight, vw = window.innerWidth;
                pop.style.minWidth = Math.max(180, r.width) + 'px';
                const abajo = vh - r.bottom - 8, arriba = r.top - 8;
                const haciaArriba = abajo < 220 && arriba > abajo;
                const alto = Math.min(320, Math.max(140, haciaArriba ? arriba : abajo));
                pop.querySelector('.ly-sel-lista').style.maxHeight = (alto - (pop.querySelector('.ly-sel-buscar') ? 50 : 8)) + 'px';
                pop.classList.toggle('arriba', haciaArriba);
                const ancho = pop.offsetWidth;
                pop.style.left = Math.max(8, Math.min(r.left, vw - ancho - 8)) + 'px';
                if (haciaArriba) { pop.style.top = ''; pop.style.bottom = (vh - r.top + 6) + 'px'; }
                else { pop.style.bottom = ''; pop.style.top = (r.bottom + 6) + 'px'; }
            }

            function cerrar() {
                if (!abierto) return;
                abierto.btn.setAttribute('aria-expanded', 'false');
                abierto.pop.remove();
                abierto = null;
            }

            function teclado(e) {
                if (!abierto) return;
                const ops = [...abierto.pop.querySelectorAll('.ly-sel-op')];
                const habil = i => ops[i] && !ops[i].disabled;
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    let i = abierto.act, d = e.key === 'ArrowDown' ? 1 : -1;
                    do { i += d; } while (i >= 0 && i < ops.length && !habil(i));
                    if (habil(i)) marcar(i, true);
                } else if (e.key === 'Home' || e.key === 'End') {
                    e.preventDefault(); const i = e.key === 'Home' ? ops.findIndex((b, k) => habil(k)) : ops.length - 1 - [...ops].reverse().findIndex(b => !b.disabled); marcar(i, true);
                } else if (e.key === 'Enter') {
                    e.preventDefault(); if (habil(abierto.act)) elegir(Number(ops[abierto.act].dataset.i));
                } else if (e.key === 'Escape') {
                    e.preventDefault(); e.stopPropagation(); const b = abierto.btn; cerrar(); b.focus();
                } else if (e.key === 'Tab') {
                    cerrar();
                } else if (e.key.length === 1 && e.target.tagName !== 'INPUT') {
                    // Escribir para saltar a la opción que empieza así
                    const ahora = performance.now();
                    abierto.letras = (ahora - abierto.tLetras > 700 ? '' : abierto.letras) + norm(e.key); abierto.tLetras = ahora;
                    const i = ops.findIndex(b => !b.disabled && norm(b.textContent).trim().startsWith(abierto.letras));
                    if (i >= 0) marcar(i, true);
                }
            }
            document.addEventListener('keydown', e => { if (abierto && e.target === abierto.btn) teclado(e); }, true);
            document.addEventListener('pointerdown', e => { if (abierto && !abierto.pop.contains(e.target) && !abierto.btn.contains(e.target)) cerrar(); }, true);
            window.addEventListener('resize', () => cerrar());
            document.addEventListener('scroll', e => { if (abierto && !abierto.pop.contains(e.target)) colocar(); }, true);

            /* ---------- Calendario propio para <input type="date"> ----------
               El campo original queda oculto (conserva name, x-model y el formato AAAA-MM-DD);
               se muestra un campo dd/mm/aaaa donde también se puede escribir. */
            const dFecha = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
            const MESES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            const pad = n => String(n).padStart(2, '0');
            const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            const deIso = v => { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null; };
            const aTexto = v => { const d = deIso(v); return d ? pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() : ''; };
            const deTexto = t => {
                const m = /^\s*(\d{1,2})[\/\-. ](\d{1,2})[\/\-. ](\d{2,4})\s*$/.exec(t || '');
                if (!m) return null;
                let y = +m[3]; if (y < 100) y += y > (new Date().getFullYear() % 100) + 1 ? 1900 : 2000;
                const d = new Date(y, +m[2] - 1, +m[1]);
                return d.getMonth() === +m[2] - 1 ? d : null;
            };
            let cal = null;

            function calendario(inp) {
                if (inp.dataset.lyFecha || inp.hasAttribute('data-nativo') || inp.closest('.swal2-container')) return;
                inp.dataset.lyFecha = '1';
                const caja = document.createElement('span');
                caja.className = 'ly-fec';
                const txt = document.createElement('input');
                txt.type = 'text'; txt.inputMode = 'numeric'; txt.autocomplete = 'off';
                txt.className = (inp.className || '') + ' ly-fec-txt';
                txt.placeholder = 'dd/mm/aaaa';
                if (inp.id) { txt.id = inp.id + '-ly'; document.querySelectorAll('label[for="' + inp.id + '"]').forEach(l => l.setAttribute('for', txt.id)); }
                const ico = document.createElement('button');
                ico.type = 'button'; ico.className = 'ly-fec-ico'; ico.tabIndex = -1; ico.setAttribute('aria-label', 'Abrir calendario');
                ico.innerHTML = '<i class="bi bi-calendar3"></i>';
                copiarAspecto(inp, txt);
                txt.style.width = '100%'; txt.style.paddingRight = '38px'; txt.style.marginTop = txt.style.marginBottom = '0';
                caja.style.marginTop = getComputedStyle(inp).marginTop; caja.style.marginBottom = getComputedStyle(inp).marginBottom;
                caja.append(txt, ico);
                inp.insertAdjacentElement('afterend', caja);
                inp.classList.add('ly-sel-nativo'); inp.tabIndex = -1; inp.setAttribute('aria-hidden', 'true');

                const pintar = () => { if (document.activeElement !== txt) txt.value = aTexto(dFecha.get.call(inp)); txt.disabled = inp.disabled; caja.style.display = inp.style.display === 'none' ? 'none' : ''; };
                const fijar = (v, avisarCambio) => {
                    const antes = dFecha.get.call(inp);
                    dFecha.set.call(inp, v || '');
                    txt.value = aTexto(v);
                    if (avisarCambio && antes !== (v || '')) { inp.dispatchEvent(new Event('input', { bubbles: true })); inp.dispatchEvent(new Event('change', { bubbles: true })); }
                };
                inp._lyFijar = fijar;
                Object.defineProperty(inp, 'value', { configurable: true, get() { return dFecha.get.call(this); }, set(v) { dFecha.set.call(this, v); pintar(); } });
                new MutationObserver(pintar).observe(inp, { attributes: true, attributeFilter: ['disabled', 'style', 'min', 'max'] });

                const enRango = d => { const mn = deIso(inp.min), mx = deIso(inp.max); return (!mn || d >= mn) && (!mx || d <= mx); };
                txt.maxLength = 10;
                txt.addEventListener('input', () => {
                    const n = txt.value.replace(/\D/g, '').slice(0, 8);
                    const t = n.length > 4 ? n.slice(0, 2) + '/' + n.slice(2, 4) + '/' + n.slice(4) : n.length > 2 ? n.slice(0, 2) + '/' + n.slice(2) : n;
                    if (t !== txt.value) txt.value = t;
                    const d = n.length === 8 ? deTexto(t) : null;
                    if (d && enRango(d)) { fijar(iso(d), true); if (cal && cal.inp === inp) { cal.vista = new Date(d.getFullYear(), d.getMonth(), 1); dibujarCal(); } }
                    else if (!t) fijar('', true);
                });
                txt.addEventListener('blur', () => setTimeout(() => {
                    if (cal && cal.inp === inp) return;
                    const d = txt.value ? deTexto(txt.value) : null;
                    if (d && enRango(d)) fijar(iso(d), true);
                    txt.value = aTexto(dFecha.get.call(inp));
                }, 120));
                txt.addEventListener('focus', () => abrirCal(inp, txt));
                txt.addEventListener('click', () => { if (!cal || cal.inp !== inp) abrirCal(inp, txt); });
                txt.addEventListener('keydown', e => {
                    if (e.key === 'Escape' && cal) { e.stopPropagation(); cerrarCal(); }
                    if (e.key === 'Enter' && cal) { e.preventDefault(); cerrarCal(); }
                    if (e.key === 'Tab') cerrarCal();
                });
                ico.addEventListener('click', () => { cal && cal.inp === inp ? cerrarCal() : (txt.focus(), abrirCal(inp, txt)); });
                pintar();
            }

            function abrirCal(inp, txt) {
                if (cal && cal.inp === inp) return;
                cerrarCal(); cerrar();
                const pop = document.createElement('div');
                pop.className = 'ly-sel-pop ly-cal';
                document.body.appendChild(pop);
                const d = deIso(dFecha.get.call(inp)) || deIso(inp.max) && new Date() > deIso(inp.max) && deIso(inp.max) || new Date();
                cal = { inp, txt, pop, vista: new Date(d.getFullYear(), d.getMonth(), 1), modo: 'dias' };
                pop.addEventListener('pointerdown', e => e.preventDefault()); // no quitar el foco del campo
                dibujarCal(); colocarCal();
            }
            function cerrarCal() { if (cal) { cal.pop.remove(); cal = null; } }

            function dibujarCal() {
                if (!cal) return;
                const { inp, pop } = cal;
                const sel = deIso(dFecha.get.call(inp)), hoy = new Date(); hoy.setHours(0, 0, 0, 0);
                const mn = deIso(inp.min), mx = deIso(inp.max);
                const y = cal.vista.getFullYear(), m = cal.vista.getMonth();
                let h = '<div class="ly-cal-h"><button type="button" data-n="-1" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>';
                if (cal.modo === 'dias') {
                    h += '<button type="button" class="ly-cal-t" data-modo="anios">' + MESES[m] + ' ' + y + ' <i class="bi bi-chevron-down"></i></button>';
                } else {
                    const base = Math.floor(y / 12) * 12;
                    h += '<button type="button" class="ly-cal-t" data-modo="dias">' + base + ' – ' + (base + 11) + '</button>';
                }
                h += '<button type="button" data-n="1" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button></div>';
                if (cal.modo === 'dias') {
                    h += '<div class="ly-cal-g">' + ['L', 'M', 'M', 'J', 'V', 'S', 'D'].map(x => '<span>' + x + '</span>').join('');
                    const inicio = (new Date(y, m, 1).getDay() + 6) % 7;
                    for (let i = 0; i < 42; i++) {
                        const f = new Date(y, m, 1 - inicio + i);
                        const fuera = f.getMonth() !== m, off = (mn && f < mn) || (mx && f > mx);
                        const cls = [fuera ? 'fuera' : '', +f === +hoy ? 'hoy' : '', sel && +f === +sel ? 'on' : ''].join(' ');
                        h += '<button type="button" class="' + cls + '" data-f="' + iso(f) + '"' + (off ? ' disabled' : '') + '>' + f.getDate() + '</button>';
                        if (i === 34 && f.getMonth() !== m && new Date(y, m, 1 - inicio + 35).getMonth() !== m) break;
                    }
                    h += '</div>';
                } else {
                    const base = Math.floor(y / 12) * 12;
                    h += '<div class="ly-cal-a">';
                    for (let a = base; a < base + 12; a++) {
                        const off = (mn && a < mn.getFullYear()) || (mx && a > mx.getFullYear());
                        h += '<button type="button" data-a="' + a + '" class="' + (a === y ? 'on' : '') + (a === hoy.getFullYear() ? ' hoy' : '') + '"' + (off ? ' disabled' : '') + '>' + a + '</button>';
                    }
                    h += '</div><div class="ly-cal-m">' + MESES.map((n, i) => '<button type="button" data-m="' + i + '" class="' + (i === m ? 'on' : '') + '">' + n.slice(0, 3) + '</button>').join('') + '</div>';
                }
                const hoyOk = (!mn || hoy >= mn) && (!mx || hoy <= mx);
                h += '<div class="ly-cal-f"><button type="button" data-borrar>Borrar</button>' + (hoyOk ? '<button type="button" data-hoy>Hoy</button>' : '') + '</div>';
                pop.innerHTML = h;
                pop.querySelectorAll('[data-n]').forEach(b => b.onclick = () => {
                    const n = +b.dataset.n;
                    cal.vista = cal.modo === 'dias' ? new Date(y, m + n, 1) : new Date(y + 12 * n, m, 1);
                    dibujarCal();
                });
                pop.querySelectorAll('[data-modo]').forEach(b => b.onclick = () => { cal.modo = b.dataset.modo; dibujarCal(); });
                pop.querySelectorAll('[data-a]').forEach(b => b.onclick = () => { cal.vista = new Date(+b.dataset.a, m, 1); dibujarCal(); });
                pop.querySelectorAll('[data-m]').forEach(b => b.onclick = () => { cal.vista = new Date(y, +b.dataset.m, 1); cal.modo = 'dias'; dibujarCal(); });
                pop.querySelectorAll('[data-f]').forEach(b => b.onclick = () => { cal.inp._lyFijar(b.dataset.f, true); cerrarCal(); });
                const hb = pop.querySelector('[data-hoy]'); if (hb) hb.onclick = () => { cal.inp._lyFijar(iso(hoy), true); cerrarCal(); };
                pop.querySelector('[data-borrar]').onclick = () => { cal.inp._lyFijar('', true); cerrarCal(); };
            }
            function colocarCal() {
                if (!cal) return;
                const r = cal.txt.getBoundingClientRect(), vh = window.innerHeight, vw = window.innerWidth, pop = cal.pop;
                const alto = pop.offsetHeight, ancho = pop.offsetWidth;
                const arriba = vh - r.bottom < alto + 12 && r.top > alto + 12;
                pop.classList.toggle('arriba', arriba);
                pop.style.left = Math.max(8, Math.min(r.left, vw - ancho - 8)) + 'px';
                if (arriba) { pop.style.top = ''; pop.style.bottom = (vh - r.top + 6) + 'px'; } else { pop.style.bottom = ''; pop.style.top = (r.bottom + 6) + 'px'; }
            }
            document.addEventListener('pointerdown', e => { if (cal && !cal.pop.contains(e.target) && !cal.txt.parentNode.contains(e.target)) cerrarCal(); }, true);
            document.addEventListener('scroll', e => { if (cal && !cal.pop.contains(e.target)) colocarCal(); }, true);
            window.addEventListener('resize', () => cerrarCal());

            /* ---------- Aplicar a lo que ya existe y a lo que aparezca después ---------- */
            const revisar = nodo => {
                if (!nodo || nodo.nodeType !== 1) return;
                if (nodo.matches && nodo.matches('select')) mejorar(nodo);
                if (nodo.matches && nodo.matches('input[type=date]')) calendario(nodo);
                nodo.querySelectorAll && nodo.querySelectorAll('select').forEach(mejorar);
                nodo.querySelectorAll && nodo.querySelectorAll('input[type=date]').forEach(calendario);
            };
            const iniciar = () => {
                revisar(document.body);
                new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(revisar))).observe(document.body, { childList: true, subtree: true });
            };
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
        })();
    </script>

    <!-- AYUDANTES DE MOVIMIENTO: los módulos los llaman tras guardar, borrar, cambiar o fallar -->
    <script>
        (() => {
            const quieto = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const elem = x => typeof x === 'string' ? document.querySelector(x) : x;
            const reinicia = (el, clase) => {
                el.classList.remove(clase); void el.offsetWidth; el.classList.add(clase);
                // Solo cuenta el fin de SU animación (las de los hijos también burbujean)
                const nombre = { 'mt-cambia': 'mtCambia', 'mt-destello': 'mtDestello', 'mt-sacude': 'mtSacude', 'mt-num': 'mtNum' }[clase];
                const fin = e => { if (e.target !== el || (nombre && e.animationName !== nombre)) return; el.classList.remove(clase); el.removeEventListener('animationend', fin); };
                el.addEventListener('animationend', fin);
            };
            // Renueva el contenido de una tarjeta cuando cambia lo seleccionado
            window.mtRefresca = sel => requestAnimationFrame(() => { const el = elem(sel); if (el && !quieto()) reinicia(el, 'mt-cambia'); });
            // Ilumina un instante lo recién guardado
            window.mtDestello = sel => setTimeout(() => { const el = elem(sel); if (el && !quieto()) reinicia(el, 'mt-destello'); }, 60);
            // Sacude un formulario con errores
            window.mtSacudir = sel => { const el = elem(sel); if (el && !quieto()) reinicia(el, 'mt-sacude'); };
            // Anima la salida de un elemento y luego ejecuta el borrado real
            window.mtQuitar = (sel, alTerminar) => {
                const el = elem(sel);
                if (!el || quieto()) { alTerminar && alTerminar(); return; }
                el.classList.add('mt-sale');
                setTimeout(() => alTerminar && alTerminar(), 170);
            };
            // Número que cambia
            window.mtNumero = sel => { const el = elem(sel); if (el && !quieto()) reinicia(el, 'mt-num'); };
        })();
    </script>

    <!-- NOTIFICACIONES TOAST -->
    <script>
        window.notificar = function(mensaje, tipo = 'success') {
            const colores = {
                success: { border: 'border-teal-200', icon: '#0b5a54' },
                error:   { border: 'border-rose-200', icon: '#c8102e' },
                warning: { border: 'border-amber-200', icon: '#9a5800' },
                info:    { border: 'border-sky-200', icon: '#0284c7' }
            };

            const c = colores[tipo] || colores.success;

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: tipo,
                iconColor: c.icon,
                title: mensaje,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: {
                    popup: `border ${c.border} font-sans`
                },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        };
    </script>

    @if (session('success') || session('error') || session('warning') || session('info') || session('status'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success') || session('status'))
                window.notificar(@js(session('success') ?? session('status')), 'success');
            @endif

            @if (session('error'))
                window.notificar(@js(session('error')), 'error');
            @endif

            @if (session('warning'))
                window.notificar(@js(session('warning')), 'warning');
            @endif

            @if (session('info'))
                window.notificar(@js(session('info')), 'info');
            @endif
        });
    </script>
    @endif
</body>
</html>
