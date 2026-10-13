<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTrack - Sistema Médico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Tipografías del sistema: Bricolage Grotesque, Geist y Geist Mono -->
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
/* Patrones de seguridad */
function mtHash(s) { let h = 2166136261; for (const c of String(s)) { h ^= c.charCodeAt(0); h = Math.imul(h, 16777619); } return h >>> 0; }
function mtRoseta(seed, size) {
    const hv = mtHash(seed || 'MT'), n = 18 + hv % 9, c = size / 2; let d = '';
    for (let k = 0; k < 9; k++) {
        const A = size * .11 + k * size * .042, B = size * .03 + (k % 3) * size * .012;
        for (let i = 0; i <= 160; i++) {
            const t = i / 160 * 2 * Math.PI, r = A + B * Math.cos(n * t + k * .4);
            d += (i ? 'L' : 'M') + (c + r * Math.cos(t)).toFixed(1) + ',' + (c + r * Math.sin(t)).toFixed(1);
        }
        d += 'Z';
    }
    return `<svg viewBox="0 0 ${size} ${size}" aria-hidden="true"><path d="${d}" fill="none" stroke="currentColor" stroke-width=".6"/></svg>`;
}
function mtGuilloche(seed) {
    const hv = mtHash(seed || 'MEDITRACK'), w = 300, h = 110, n = 22 + hv % 10, cx = w * .86, cy = h * .5; let d = '';
    for (let k = 0; k < 10; k++) {
        const A = 34 + k * 6, B = 5 + (k % 4) * 1.6, ph = k * .22 + (hv % 100) / 50;
        for (let i = 0; i <= 220; i++) {
            const t = i / 220 * 2 * Math.PI, r = A + B * Math.cos(n * t + ph);
            d += (i ? 'L' : 'M') + (cx + r * Math.cos(t)).toFixed(1) + ',' + (cy + r * Math.sin(t) * .9).toFixed(1);
        }
        d += 'Z';
    }
    for (let k = 0; k < 4; k++) {
        const y0 = 16 + k * 26;
        for (let x = 0; x <= w; x += 5) d += (x ? 'L' : 'M') + x + ',' + (y0 + 5 * Math.sin(x / 23 + k * .9 + hv % 5) + 2 * Math.sin(x / 7 + k)).toFixed(1);
    }
    return `<svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="${d}" fill="none" stroke="#a6efe2" stroke-width=".5" opacity=".28"/></svg>`;
}
const mtNorm = s => String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

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
            this.$nextTick(() => { if (this.$refs.gafeteGl) this.$refs.gafeteGl.innerHTML = mtGuilloche(this.semilla); });
        },

        roseta(seed, size) { return mtRoseta(seed, size); },

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

        abrirPaleta() {
            if (this.paleta) { this.$refs.pq.select(); return; }
            this._foco = document.activeElement;
            this.mobileOpen = false;
            this.q = ''; this.remotos = []; this.sel = 0; this.paleta = true;
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
            const barra = document.getElementById('top-progress-bar');
            if (barra) { barra.style.opacity = '1'; barra.style.width = '70%'; }
            window.location.href = r.url;
        }
    };
}
</script>
@endverbatim

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

/* Barra de progreso */
#top-progress-bar { position: fixed; top: 0; left: 0; height: 3px; width: 0; background: var(--ly-teal); z-index: 9999; transition: width 200ms ease-out, opacity 150ms ease-in; pointer-events: none; opacity: 0; }

.page-fade-in { animation: mtPagina 220ms ease-out both; }
@keyframes mtPagina { from { opacity: .85; transform: translateY(4px); } }

@media (min-width: 1024px) { .ly-movil { display: none !important; } }
@media (max-width: 639.98px) { .ly-sm { display: none !important; } }
.ly-marca-tx, .ly-hd-tit { flex: 1; min-width: 0; }

.ly-ico { position: relative; flex: none; width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center; color: var(--ly-texto); background: var(--ly-papel); border: 1px solid var(--ly-linea); transition: background .15s, color .15s, border-color .15s; }
.ly-ico:hover { color: var(--ly-tinta); border-color: #c5d3cf; background: #f7faf9; }
.ly-ico .bi { font-size: 17px; }

/* MENÚ LATERAL */
.ly-sb { --ancho: 272px; width: var(--ancho); height: 100%; flex: none; display: flex; flex-direction: column; background: #f8fbfa; border-right: 1px solid var(--ly-linea); z-index: 40; color: var(--ly-texto); transition: width .3s var(--ly-e), transform .3s var(--ly-e); }
@media (max-width: 1023.98px) {
    .ly-sb { position: fixed; inset: 0 auto 0 0; transform: translateX(-100%); }
    .ly-sb.movil-abierto { transform: none; box-shadow: 0 30px 60px -20px rgba(11, 30, 28, .45); }
}
@media (min-width: 1024px) {
    .ly-sb { position: relative; }
    .ly-sb.rail { --ancho: 76px; }
    .ly-sb.rail .ly-oculta-rail { display: none !important; }
}

/* Marca */
.ly-marca { display: flex; align-items: center; gap: 12px; padding: 20px 16px 18px; }
.ly-sello { flex: none; display: block; width: 42px; height: 42px; filter: drop-shadow(0 6px 10px rgba(7, 63, 59, .28)); transition: transform .3s var(--ly-e); }
.ly-sello svg { width: 100%; height: 100%; display: block; }
.ly-sello:hover { transform: rotate(90deg); }
.ly-logo { font: 700 23px/1 var(--ly-disp); font-stretch: 85%; letter-spacing: -.02em; color: var(--ly-tinta); }
.ly-clinica { margin-top: 6px; font: 500 12px/1.2 var(--ly-mono); letter-spacing: .04em; text-transform: uppercase; color: var(--ly-media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-sb.rail .ly-marca { justify-content: center; padding-inline: 0; }

/* Buscador */
.ly-buscar-wrap { padding: 0 16px 8px; }
.ly-buscar { width: 100%; height: 40px; display: flex; align-items: center; gap: 10px; padding: 0 8px 0 12px; border-radius: 10px; background: var(--ly-papel); border: 1px solid var(--ly-linea); color: var(--ly-media); font-size: 13.5px; font-weight: 450; text-align: left; box-shadow: 0 6px 16px -14px rgba(11, 30, 28, .6); transition: border-color .15s, box-shadow .15s; }
.ly-buscar:hover { border-color: #b9cbc6; box-shadow: 0 8px 20px -14px rgba(11, 30, 28, .7); }
.ly-buscar .bi { font-size: 14px; color: var(--ly-cred); }
.ly-buscar span { flex: 1; }
.ly-sb.rail .ly-buscar-wrap { padding-inline: 16px; }
.ly-sb.rail .ly-buscar { justify-content: center; padding: 0; }

/* Navegación */
.ly-nav { flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; padding: 4px 12px 12px; scrollbar-width: thin; scrollbar-color: rgba(11, 30, 28, .14) transparent; }
.ly-grupo { margin-top: 14px; }
.ly-grupo:first-child { margin-top: 4px; }
.ly-grupo-t { display: flex; align-items: center; gap: 8px; padding: 0 10px 6px; font-size: 12px; font-weight: 600; color: var(--ly-media); }
.ly-grupo-t:before { content: ""; flex: none; width: 8px; height: 8px; background: linear-gradient(var(--ly-cred), var(--ly-cred)) center / 8px 2px no-repeat, linear-gradient(var(--ly-cred), var(--ly-cred)) center / 2px 8px no-repeat; opacity: .7; }
.ly-grupo-t:after { content: ""; flex: 1; height: 1px; background: var(--ly-linea); }
.ly-sb.rail .ly-grupo-t { padding: 0; justify-content: center; }
.ly-sb.rail .ly-grupo-t span, .ly-sb.rail .ly-grupo-t:before { display: none; }
.ly-sb.rail .ly-grupo-t:after { flex: 0 0 24px; }

.ly-link { position: relative; display: flex; align-items: center; gap: 10px; height: 40px; padding: 0 8px 0 6px; margin-top: 2px; border-radius: 11px; font-size: 14px; font-weight: 500; color: var(--ly-texto); transition: background .15s, color .15s; }
.ly-link .bi { flex: none; width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; font-size: 15px; color: var(--ly-media); transition: color .15s, background .15s, box-shadow .15s; }
.ly-link-t { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
a.ly-link:hover { background: rgba(11, 90, 84, .06); color: var(--ly-tinta); }
a.ly-link:hover .bi { color: var(--ly-cred); background: var(--ly-papel); box-shadow: 0 0 0 1px var(--ly-linea); }
.ly-link.es-activo { background: var(--ly-velo); color: var(--ly-tinta); font-weight: 650; }
.ly-link.es-activo .bi { color: #fff; background: var(--ly-cred); box-shadow: 0 6px 12px -6px rgba(7, 63, 59, .8); }
.ly-link.es-activo:after { content: ""; position: absolute; right: 10px; top: 50%; width: 6px; height: 6px; margin-top: -3px; border-radius: 50%; background: var(--ly-cred); }
.ly-link.es-pronto { cursor: default; color: #7d8986; }
.ly-link.es-pronto .bi { color: #9aa6a3; }
.ly-link em { font: 500 12px var(--ly-ui); font-style: normal; color: #7d8986; }
.ly-sb.rail .ly-link { justify-content: center; padding: 0; height: 44px; }
.ly-sb.rail .ly-link .bi { width: 34px; height: 34px; font-size: 17px; }
.ly-sb.rail .ly-link.es-activo { background: transparent; }
.ly-sb.rail .ly-link.es-activo:after { display: none; }

.ly-tip { position: fixed; z-index: 70; transform: translateY(-50%); pointer-events: none; padding: 6px 10px; border-radius: 8px; background: var(--ly-tinta); color: #fff; font-size: 13px; font-weight: 550; white-space: nowrap; box-shadow: 0 10px 24px -12px rgba(11, 30, 28, .7); }

/* GAFETE DE USUARIO */
.ly-pie { padding: 10px 12px 14px; border-top: 1px solid var(--ly-linea); }
.ly-gafete { perspective: 900px; }
.ly-gafete-in { position: relative; height: 84px; transform-style: preserve-3d; transition: transform .7s cubic-bezier(.3, .7, .1, 1); }
.ly-gafete.volteado .ly-gafete-in { transform: rotateY(180deg); }
.ly-gf { position: absolute; inset: 0; border-radius: 14px; backface-visibility: hidden; -webkit-backface-visibility: hidden; overflow: hidden; }

.ly-gf-frente { display: flex; align-items: center; gap: 12px; padding: 0 12px; text-align: left; color: #eafaf6; background: linear-gradient(150deg, #0d6a63, var(--ly-cred) 45%, var(--ly-cred2)); box-shadow: 0 14px 28px -18px rgba(7, 63, 59, .9), inset 0 0 0 1px rgba(166, 239, 226, .18); }
.ly-gf-frente:after { content: ""; position: absolute; inset: 0; background: linear-gradient(105deg, transparent 35%, rgba(255, 255, 255, .14) 50%, transparent 65%); transform: translateX(-100%); transition: transform .8s var(--ly-e); pointer-events: none; }
.ly-gf-frente:hover:after { transform: translateX(100%); }
.ly-gf-gl, .ly-gf-gl svg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
.ly-gf-datos { position: relative; flex: 1; min-width: 0; display: flex; flex-direction: column; }
.ly-gf-k { font: 500 12px/1.2 var(--ly-mono); color: var(--ly-foil); opacity: .85; }
.ly-gf-n { margin-top: 3px; font: 750 17px/1.1 var(--ly-disp); font-stretch: 80%; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-gf-r { margin-top: 2px; font-size: 12px; font-weight: 500; color: #cdeee7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-gf-giro { position: relative; font-size: 14px; color: var(--ly-foil); opacity: .7; transition: transform .3s var(--ly-e), opacity .15s; }
.ly-gf-frente:hover .ly-gf-giro { opacity: 1; transform: rotate(90deg); }

.ly-avatar { position: relative; flex: none; width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; color: var(--ly-foil); background: rgba(7, 63, 59, .55); box-shadow: inset 0 0 0 1px rgba(166, 239, 226, .35); }
.ly-avatar > span, .ly-avatar > span svg { position: absolute; inset: 0; width: 100%; height: 100%; opacity: .6; }
.ly-avatar b { position: relative; font: 800 16px/1 var(--ly-disp); font-stretch: 80%; color: #fff; }
.ly-avatar-hd { width: 40px; height: 40px; background: radial-gradient(circle at 30% 25%, #11766e, var(--ly-cred2)); }
.ly-avatar-hd b { font-size: 14px; }

.ly-gf-reverso { transform: rotateY(180deg); background: var(--ly-papel); border: 1px solid var(--ly-linea); box-shadow: 0 14px 28px -20px rgba(11, 30, 28, .6); padding: 10px 10px 10px 12px; display: flex; flex-direction: column; justify-content: space-between; }
.ly-gf-mail { font: 500 12px var(--ly-mono); color: var(--ly-media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 30px; }
.ly-gf-acc { display: flex; gap: 6px; }
.ly-gf-acc > a, .ly-gf-acc form { flex: 1; min-width: 0; }
.ly-gf-acc a, .ly-gf-acc button { width: 100%; height: 36px; display: flex; align-items: center; justify-content: center; gap: 6px; border-radius: 9px; font-size: 13px; font-weight: 600; white-space: nowrap; border: 1px solid var(--ly-linea); color: var(--ly-tinta); background: var(--ly-papel); transition: background .15s, border-color .15s; }
.ly-gf-acc a:hover { background: var(--ly-niebla); }
.ly-gf-acc .es-salir { color: var(--ly-rosa); border-color: #f3c6cf; }
.ly-gf-acc .es-salir:hover { background: var(--ly-rosav); }
.ly-gf-volver { position: absolute; top: 6px; right: 6px; width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; color: var(--ly-media); }
.ly-gf-volver:hover { background: var(--ly-niebla); color: var(--ly-tinta); }

.ly-sb.rail .ly-pie { padding-inline: 0; display: flex; justify-content: center; }
.ly-sb.rail .ly-gafete-in { width: 52px; height: 52px; }
.ly-sb.rail .ly-gf-frente { padding: 0; justify-content: center; border-radius: 50%; }
.ly-sb.rail .ly-gf-reverso { display: none; }

/* ENCABEZADO */
.ly-hd { position: sticky; top: 0; z-index: 20; padding: 14px 16px 8px; background: linear-gradient(var(--ly-niebla) 70%, rgba(238, 243, 241, 0)); transition: transform .35s var(--ly-e), opacity .25s ease; }
@media (min-width: 640px) { .ly-hd { padding-inline: 24px; } }
@media (min-width: 1024px) { .ly-hd { padding-inline: 32px; } }
.ly-hd.oculto { transform: translateY(-110%); opacity: 0; pointer-events: none; }
.ly-hd-in { display: flex; align-items: center; gap: 12px; }
.ly-ruta { font-size: 13px; font-weight: 500; color: var(--ly-media); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 10px; }
.ly-saludo { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
.ly-ruta-s { display: inline-flex; align-items: center; gap: 6px; padding: 1px 8px 1px 7px; border-radius: 6px; background: var(--ly-velo); color: var(--ly-cred); font: 500 12px/18px var(--ly-mono); letter-spacing: .04em; text-transform: uppercase; }
.ly-ruta-s:before { content: ""; width: 7px; height: 7px; background: linear-gradient(currentColor, currentColor) center / 7px 2px no-repeat, linear-gradient(currentColor, currentColor) center / 2px 7px no-repeat; }
.ly-pag { margin-top: 2px; font: 700 27px/1.05 var(--ly-disp); font-stretch: 85%; letter-spacing: -.02em; color: var(--ly-tinta); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ly-reloj { display: none; flex-direction: column; align-items: flex-end; line-height: 1.15; padding-right: 4px; }
.ly-reloj b { font: 600 16px var(--ly-mono); color: var(--ly-tinta); }
.ly-reloj span { font-size: 12px; font-weight: 500; color: var(--ly-media); }
@media (min-width: 768px) { .ly-reloj { display: flex; } }
.ly-badge { position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 9px; background: var(--ly-rosa); color: #fff; font: 700 11px/18px var(--ly-ui); text-align: center; box-shadow: 0 0 0 2px var(--ly-niebla); }

.ly-pop { position: absolute; right: 0; top: calc(100% + 10px); z-index: 30; background: var(--ly-papel); border: 1px solid var(--ly-linea); border-radius: 16px; box-shadow: 0 24px 48px -24px rgba(11, 30, 28, .5); overflow: hidden; }
.ly-notis { width: 320px; max-width: calc(100vw - 32px); }
.ly-pop-h { display: flex; align-items: center; justify-between; padding: 14px 16px; border-bottom: 1px solid var(--ly-linea2); }
.ly-pop-h p { font: 750 16px var(--ly-disp); color: var(--ly-tinta); }
.ly-pop-h span { font-size: 12px; font-weight: 600; color: var(--ly-media); }
.ly-vacio { display: flex; gap: 12px; padding: 18px 16px 20px; color: var(--ly-media); font-size: 13px; }
.ly-vacio .bi { font-size: 22px; color: #08734f; }
.ly-vacio b { display: block; color: var(--ly-tinta); font-weight: 600; margin-bottom: 2px; }

/* PALETA Ctrl+K */
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
    .ly-sb, .ly-hd, .ly-gafete-in, .ly-gf-frente:after, .page-fade-in, .ly-pal-in, .ly-pal-in .ly-pal, .ly-pal-out { transition: none !important; animation: none !important; }
    .ly-gafete.volteado .ly-gafete-in { transform: none; }
    .ly-gafete.volteado .ly-gf-frente { visibility: hidden; }
    .ly-gf-reverso { transform: none; visibility: hidden; }
    .ly-gafete.volteado .ly-gf-reverso { visibility: visible; }
}

/* SWEETALERT2 TOASTS */
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

    <!-- BARRA DE PROGRESO DE NAVEGACIÓN -->
    <div id="top-progress-bar"></div>

    @php
        $user = Auth::user();
        $puede = fn ($modulo, $accion = 'ver') => $user ? (bool) $user->tienePermiso($modulo, $accion) : false;

        // Menú lateral limpio (sin Despacho Farmacia, Proveedores, Facturación ni Reportes)
        $menu = [
            ['titulo' => null, 'items' => [
                ['texto' => 'Dashboard',         'ruta' => 'home',               'activo' => 'home',            'icono' => 'bi-grid-1x2',               'permiso' => null,            'claves' => 'inicio panel resumen estadisticas'],
            ]],
            ['titulo' => 'Gestión médica', 'items' => [
                ['texto' => 'Pacientes',         'ruta' => 'pacientes.index',     'activo' => 'pacientes.*',     'icono' => 'bi-people',                 'permiso' => 'Pacientes',     'claves' => 'expedientes credencial curp alergias'],
                ['texto' => 'Citas',             'ruta' => 'citas.index',         'activo' => 'citas.*',         'icono' => 'bi-calendar2-week',         'permiso' => 'Citas',         'claves' => 'agenda calendario turnos agendar'],
                ['texto' => 'Consultas',         'ruta' => 'consultas.index',     'activo' => 'consultas.*',     'icono' => 'bi-clipboard2-pulse',       'permiso' => 'Consultas',     'claves' => 'atencion diagnostico signos vitales'],
                ['texto' => 'Consultorios',      'ruta' => 'consultorios.index',  'activo' => 'consultorios.*',  'icono' => 'bi-hospital',               'permiso' => 'Consultorios',  'claves' => 'salas espacios'],
                ['texto' => 'Recetas',           'ruta' => 'recetas.index',       'activo' => 'recetas.*',       'icono' => 'bi-file-earmark-medical',   'permiso' => 'Recetas',       'claves' => 'prescripcion medicamentos'],
            ]],
            ['titulo' => 'Farmacia y finanzas', 'items' => [
                ['texto' => 'Terminal POS',      'ruta' => 'pos.index',           'activo' => 'pos.*',           'icono' => 'bi-shop',                   'permiso' => 'POS',           'claves' => 'venta vender cobrar punto de venta'],
                ['texto' => 'Caja central',      'ruta' => 'caja.index',          'activo' => 'caja.*',          'icono' => 'bi-cash-coin',              'permiso' => 'Caja',          'claves' => 'corte apertura dinero pagos'],
                ['texto' => 'Inventario',        'ruta' => 'inventario.index',    'activo' => 'inventario.*',    'icono' => 'bi-box-seam',               'permiso' => 'Inventario',    'claves' => 'stock productos existencias lotes'],
            ]],
            ['titulo' => 'Administración', 'items' => [
                ['texto' => 'Personal',          'ruta' => 'personal.index',      'activo' => 'personal.*',      'icono' => 'bi-person-badge',           'permiso' => 'Personal',      'claves' => 'medicos doctores empleados nomina usuarios'],
                ['texto' => 'Roles y permisos',  'ruta' => 'roles.index',         'activo' => 'roles.*',         'icono' => 'bi-shield-lock',            'permiso' => 'Roles',         'claves' => 'accesos seguridad'],
                ['texto' => 'Configuración',     'ruta' => 'configuracion.index', 'activo' => 'configuracion.*', 'icono' => 'bi-gear',                  'permiso' => null,            'claves' => 'ajustes clinica aviso de privacidad cuenta'],
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

        // Acciones rápidas de la paleta.
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

        // Datos del usuario en sesión.
        $nombreUsuario = trim(optional(optional($user)->personal)->nombre_completo ?: (optional($user)->name ?? '')) ?: 'Usuario';
        $palabras      = collect(preg_split('/\s+/', $nombreUsuario))->filter()->values();
        $primerNombre  = \Illuminate\Support\Str::title(mb_strtolower($palabras->first() ?? 'Usuario'));
        $iniciales     = $palabras->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'U';
        $rolUsuario    = optional(optional($user)->rol)->nombre ?? (optional($user)->id === 1 ? 'Super Admin' : 'Personal');
        $nombreClinica = optional($clinica ?? null)->nombre ?? ($config['nombre_clinica'] ?? null);
        $folioUsuario  = 'MT-' . str_pad((string) (optional($user)->id ?? 0), 4, '0', STR_PAD_LEFT);
        $puedeConfig   = \Illuminate\Support\Facades\Route::has('configuracion.index');

        $totalNotificaciones = (int) ($totalNotificaciones ?? 0);
    @endphp

    <!-- FONDO OSCURO EN MÓVIL -->
    <div x-show="mobileOpen" x-cloak
         x-transition.opacity.duration.200ms
         @click="mobileOpen = false"
         class="ly-velo-movil ly-movil"></div>

    <!-- SIDEBAR -->
    <aside class="ly-sb" :class="{ 'movil-abierto': mobileOpen, 'rail': !sidebarOpen && ancho >= 1024 }" aria-label="Menú principal">

        <!-- MARCA -->
        <div class="ly-marca">
            <a href="{{ route('home') }}" class="ly-sello" aria-label="Ir al inicio"
               @mouseenter="mostrarTip($el, 'Inicio')" @mouseleave="ocultarTip()">
                <svg viewBox="0 0 44 44" aria-hidden="true">
                    <rect x="15" y="2" width="14" height="40" rx="4" fill="#0b5a54"/>
                    <rect x="2" y="15" width="40" height="14" rx="4" fill="#0b5a54"/>
                    <rect x="15" y="15" width="14" height="14" fill="#073f3b"/>
                    <path d="M16.5 22h3.5l1.5-4 2.5 8 1.5-4h2.5" stroke="#a6efe2" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <div class="ly-oculta-rail ly-marca-tx">
                <p class="ly-logo">MediTrack</p>
                <p class="ly-clinica" title="{{ $nombreClinica }}">{{ $nombreClinica ?: 'Sistema clínico' }}</p>
            </div>
            <button type="button" class="ly-ico ly-movil" x-show="ancho < 1024" @click="mobileOpen = false" aria-label="Cerrar menú">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- BUSCADOR -->
        <div class="ly-buscar-wrap">
            <button type="button" class="ly-buscar" @click="abrirPaleta()"
                    @mouseenter="mostrarTip($el, 'Buscar (Ctrl K)')" @mouseleave="ocultarTip()"
                    aria-label="Buscar pacientes, módulos o acciones" aria-keyshortcuts="Control+K">
                <i class="bi bi-search"></i>
                <span class="ly-oculta-rail">Buscar o ir a…</span>
                <kbd class="ly-oculta-rail">Ctrl K</kbd>
            </button>
        </div>

        <!-- NAVEGACIÓN -->
        <nav class="ly-nav" aria-label="Módulos">
            @foreach ($menu as $seccion)
                @continue($seccion['items']->isEmpty())
                <div class="ly-grupo">
                    @if ($seccion['titulo'])
                        <p class="ly-grupo-t"><span>{{ $seccion['titulo'] }}</span></p>
                    @endif
                    @foreach ($seccion['items'] as $item)
                        @php $tipTexto = $item['texto'] . ($item['pendiente'] ? ' · Pronto' : ''); @endphp
                        @if ($item['pendiente'])
                            <span class="ly-link es-pronto" aria-disabled="true"
                                  @mouseenter="mostrarTip($el, @js($tipTexto))" @mouseleave="ocultarTip()">
                                <i class="bi {{ $item['icono'] }}"></i>
                                <span class="ly-oculta-rail ly-link-t">{{ $item['texto'] }}</span>
                                <em class="ly-oculta-rail">Pronto</em>
                            </span>
                        @else
                            <a href="{{ $item['href'] }}" class="ly-link {{ $item['esActivo'] ? 'es-activo' : '' }}"
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
        </nav>

        <!-- GAFETE DE USUARIO -->
        <div class="ly-pie">
            <div class="ly-gafete" :class="{ 'volteado': gafeteVuelta }" @click.outside="gafeteVuelta = false"
                 @keydown.escape.stop="gafeteVuelta = false; $refs.gafeteFrente.focus()">
                <div class="ly-gafete-in">
                    <!-- Frente -->
                    <button type="button" class="ly-gf ly-gf-frente" x-ref="gafeteFrente"
                            @click="voltearGafete()" :aria-expanded="gafeteVuelta.toString()" :tabindex="gafeteVuelta ? -1 : 0"
                            @mouseenter="mostrarTip($el, @js($nombreUsuario))" @mouseleave="ocultarTip()"
                            aria-label="Mi cuenta: {{ $nombreUsuario }}">
                        <span class="ly-gf-gl" x-ref="gafeteGl" aria-hidden="true"></span>
                        <span class="ly-avatar" aria-hidden="true">
                            <span x-html="roseta(semilla, 44)"></span>
                            <b>{{ $iniciales }}</b>
                        </span>
                        <span class="ly-oculta-rail ly-gf-datos">
                            <span class="ly-gf-k">Gafete · <span class="t">{{ $folioUsuario }}</span></span>
                            <span class="ly-gf-n">{{ $nombreUsuario }}</span>
                            <span class="ly-gf-r">{{ $rolUsuario }}</span>
                        </span>
                        <i class="ly-oculta-rail bi bi-arrow-repeat ly-gf-giro" aria-hidden="true"></i>
                    </button>

                    <!-- Reverso -->
                    <div class="ly-gf ly-gf-reverso" :aria-hidden="(!gafeteVuelta).toString()">
                        <p class="ly-gf-mail" title="{{ $user->email ?? '' }}">{{ $user->email ?? $nombreUsuario }}</p>
                        <div class="ly-gf-acc">
                            @if ($puedeConfig)
                                <a href="{{ route('configuracion.index') }}" :tabindex="gafeteVuelta ? 0 : -1">
                                    <i class="bi bi-gear"></i> Mi cuenta
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" id="ly-salir">
                                @csrf
                                <button type="submit" class="es-salir" :tabindex="gafeteVuelta ? 0 : -1">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                                </button>
                            </form>
                        </div>
                        <button type="button" class="ly-gf-volver" @click="gafeteVuelta = false; $refs.gafeteFrente.focus()"
                                :tabindex="gafeteVuelta ? 0 : -1" aria-label="Regresar al frente del gafete">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ETIQUETA FLOTANTE -->
    <div x-show="tip.texto && !sidebarOpen && ancho >= 1024" x-cloak
         :style="`top:${tip.top}px; left:${tip.left}px`"
         class="ly-tip" role="tooltip"><span x-text="tip.texto"></span></div>

    <!-- ÁREA PRINCIPAL -->
    <div class="flex-1 min-w-0 flex flex-col overflow-y-auto"
         @scroll.passive="alScrollear($el)"
         @mousemove.throttle.100ms="alMoverCursor($event,$el)">

        <header class="ly-hd" :class="{ 'oculto': !headerVisible }"
                @mouseenter="headerHover = true" @mouseleave="headerHover = false">
            <div class="ly-hd-in">
                <button type="button" class="ly-ico" @click="alternarMenu()"
                        :aria-label="(ancho < 1024 ? mobileOpen : sidebarOpen) ? 'Contraer menú' : 'Mostrar menú'"
                        :aria-expanded="(ancho < 1024 ? mobileOpen : sidebarOpen).toString()">
                    <i class="bi" :class="ancho < 1024 ? 'bi-list' : (sidebarOpen ? 'bi-layout-sidebar-inset' : 'bi-layout-sidebar')"></i>
                </button>

                <div class="ly-hd-tit">
                    <p class="ly-ruta">
                        <span class="ly-saludo"><span x-text="saludo">Hola</span>, {{ $primerNombre }}</span>
                        @if ($seccionActual)<span class="ly-ruta-s ly-sm">{{ $seccionActual }}</span>@endif
                    </p>
                    <p class="ly-pag">{{ $paginaActual }}</p>
                </div>

                <button type="button" class="ly-ico ly-movil" @click="abrirPaleta()" aria-label="Buscar">
                    <i class="bi bi-search"></i>
                </button>

                <div class="ly-reloj" aria-label="Hora actual">
                    <b class="t" x-text="hora"></b>
                    <span x-text="fecha"></span>
                </div>

                <div x-data="{ abierto: false }" class="relative" @keydown.escape.stop="abierto = false">
                    <button type="button" class="ly-ico" @click="abierto = !abierto" @click.outside="abierto = false"
                            :aria-expanded="abierto.toString()"
                            aria-label="Notificaciones{{ $totalNotificaciones ? ': ' . $totalNotificaciones . ' nuevas' : '' }}">
                        <i class="bi bi-bell"></i>
                        @if ($totalNotificaciones > 0)
                            <span class="ly-badge t">{{ $totalNotificaciones > 9 ? '9+' :$totalNotificaciones }}</span>
                        @endif
                    </button>

                    <div x-show="abierto" x-cloak x-transition.origin.top.right class="ly-pop ly-notis">
                        <div class="ly-pop-h">
                            <p>Notificaciones</p>
                            <span class="t">{{ $totalNotificaciones }} nuevas</span>
                        </div>
                        <div class="ly-vacio">
                            <i class="bi bi-check2-circle"></i>
                            <p><b>Todo al día</b>Aquí aparecerán los avisos de citas y de inventario.</p>
                        </div>
                    </div>
                </div>

                <button type="button" class="ly-avatar ly-avatar-hd ly-movil" @click="mobileOpen = true; gafeteVuelta = true"
                        aria-label="Mi cuenta: {{ $nombreUsuario }}">
                    <span x-html="roseta(semilla, 40)"></span>
                    <b>{{ $iniciales }}</b>
                </button>
            </div>
        </header>

        <main id="main-content" class="px-4 sm:px-6 lg:px-8 pt-3 pb-8 space-y-6 w-full min-w-0 flex-1 block page-fade-in">
            @yield('content')
        </main>
    </div>

    <!-- PALETA DE BÚSQUEDA GLOBAL (Ctrl+K) -->
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

    <script type="application/json" id="ly-nav">{!! json_encode(['modulos' => $navJs, 'acciones' =>$accionesJs, 'buscar' => \Illuminate\Support\Facades\Route::has('buscar.global') ? route('buscar.global') : null, 'semilla' => optional($user)->email ?? $nombreUsuario], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const progressBar = document.getElementById('top-progress-bar');
            document.body.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.href) {
                    const targetUrl = link.href;
                    const isExternal = link.target === '_blank';
                    const isAnchor = targetUrl.includes('#') && link.pathname === window.location.pathname;
                    const isSamePage = targetUrl === window.location.href;

                    if (!isExternal && !isAnchor && !isSamePage && !targetUrl.startsWith('javascript:')) {
                        progressBar.style.opacity = '1';
                        progressBar.style.width = '70%';
                    }
                }
            });
        });

        window.addEventListener('beforeunload', () => {
            const progressBar = document.getElementById('top-progress-bar');
            if (progressBar) {
                progressBar.style.opacity = '1';
                progressBar.style.width = '100%';
            }
        });
    </script>

    <!-- TOASTS -->
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