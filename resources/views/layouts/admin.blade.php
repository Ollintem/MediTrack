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

// ---------- Caja 3D de medicamentos e insumos (la usan Inventario y el Dashboard) ----------
window.MTCaja = (() => {
    const LUZ = (() => { const v = [-0.35, -0.7, 0.62], m = Math.hypot(...v); return v.map(x => x / m); })();
    const NORMALES = { fr: [0, 0, 1], re: [0, 0, -1], de: [1, 0, 0], iz: [-1, 0, 0], ar: [0, -1, 0], ab: [0, 1, 0] };
    const cachePartes = new Map(), cacheTipo = new Map();
    // Dibujos de insumos (planos, para la ventana del sobre y lo que sale al abrir)
    const ITEMS = {
        jeringa: '<svg viewBox="0 0 180 48" aria-hidden="true"><rect x="4" y="12" width="5" height="24" rx="1.5" fill="#9fb3b9"/><rect x="9" y="21" width="40" height="6" fill="#c9d6da"/><rect x="47" y="7" width="5" height="34" rx="1.5" fill="#9fb3b9"/><rect x="52" y="13" width="78" height="22" rx="3" fill="#f3f8f9" stroke="#8aa1a8" stroke-width="1.5"/><rect x="88" y="15" width="40" height="18" rx="2" fill="var(--k)" opacity=".38"/><rect x="62" y="15" width="7" height="18" fill="#3c4f54"/><path d="M74 13v6M82 13v4M90 13v6M98 13v4M106 13v6M114 13v4M122 13v6" stroke="#6f8a91" stroke-width="1.2"/><path d="M130 19h8v10h-8z" fill="#cfdadd"/><path d="M138 22.5h10v3h-10z" fill="var(--k)"/><path d="M148 24h28" stroke="#8c9da2" stroke-width="1.6" stroke-linecap="round"/></svg>',
        aguja: '<svg viewBox="0 0 180 48" aria-hidden="true"><rect x="20" y="14" width="40" height="20" rx="4" fill="var(--k)"/><rect x="24" y="18" width="32" height="2" fill="#fff" opacity=".4"/><path d="M60 19h14v10H60z" fill="#d8e2e4"/><path d="M74 24h92" stroke="#8c9da2" stroke-width="2" stroke-linecap="round"/><rect x="74" y="16" width="70" height="16" rx="8" fill="none" stroke="#b9c8cc" stroke-dasharray="3 3"/></svg>',
        gasa: '<svg viewBox="0 0 120 90" aria-hidden="true"><rect x="14" y="10" width="92" height="70" rx="3" fill="#fbfcfc" stroke="#d3dcdf"/><path d="M14 22h92M14 34h92M14 46h92M14 58h92M14 70h92M26 10v70M38 10v70M50 10v70M62 10v70M74 10v70M86 10v70M98 10v70" stroke="#e1e8ea" stroke-width="1.4"/><path d="M106 10l-14 14V10z" fill="#e7edef"/></svg>',
        venda: '<svg viewBox="0 0 150 90" aria-hidden="true"><path d="M58 28h80v34H58z" fill="#f6f2ea" stroke="#d9d1c2"/><path d="M68 28v34M80 28v34M92 28v34M104 28v34M116 28v34M128 28v34" stroke="#e8e1d3" stroke-width="1.5"/><circle cx="46" cy="45" r="32" fill="#f3eee4" stroke="#d6cdbd"/><circle cx="46" cy="45" r="20" fill="none" stroke="#e2d9c8" stroke-width="2"/><circle cx="46" cy="45" r="9" fill="#d9cfbd"/></svg>',
        curita: '<svg viewBox="0 0 160 60" aria-hidden="true"><rect x="8" y="14" width="144" height="32" rx="16" fill="#e8c9a8"/><rect x="58" y="16" width="44" height="28" rx="4" fill="#fbf3e9"/><g fill="#d6b08a"><circle cx="24" cy="24" r="1.6"/><circle cx="34" cy="30" r="1.6"/><circle cx="24" cy="36" r="1.6"/><circle cx="44" cy="24" r="1.6"/><circle cx="44" cy="36" r="1.6"/><circle cx="116" cy="24" r="1.6"/><circle cx="126" cy="30" r="1.6"/><circle cx="136" cy="24" r="1.6"/><circle cx="116" cy="36" r="1.6"/><circle cx="136" cy="36" r="1.6"/></g></svg>',
        sonda: '<svg viewBox="0 0 160 80" aria-hidden="true"><path d="M20 40c0-16 20-24 40-24s40 8 40 24-20 24-40 24-30-6-30-16 14-14 30-14 24 4 24 10" fill="none" stroke="#c9dde2" stroke-width="5" stroke-linecap="round"/><path d="M104 40h40" stroke="#c9dde2" stroke-width="5" stroke-linecap="round"/><rect x="136" y="30" width="18" height="20" rx="4" fill="var(--k)"/></svg>',
        termometro: '<svg viewBox="0 0 160 50" aria-hidden="true"><rect x="10" y="14" width="120" height="22" rx="11" fill="#f2f6f7" stroke="#9fb2b8" stroke-width="1.5"/><rect x="24" y="19" width="40" height="12" rx="2" fill="#20363a"/><text x="44" y="28.5" text-anchor="middle" font-family="monospace" font-size="9" fill="#9ff0d8">36.5</text><path d="M130 25h20" stroke="#8c9da2" stroke-width="5" stroke-linecap="round"/><circle cx="100" cy="25" r="5" fill="var(--k)"/></svg>',
        guante: '<svg viewBox="0 0 100 130" aria-hidden="true"><path d="M30 128V78c-6-5-14-14-18-24-2-6 4-9 8-4l10 12V24c0-5 7-5 7 0v34h3V12c0-5 7-5 7 0v46h3V16c0-5 7-5 7 0v42h3V26c0-5 7-5 7 0v52c0 10-4 18-10 22v28z" fill="var(--guante, #7fb4e8)" stroke="rgba(0,0,0,.18)" stroke-width="1.2"/><path d="M36 128v-20h30v20" fill="rgba(255,255,255,.25)"/></svg>',
        cubrebocas: '<svg viewBox="0 0 140 90" aria-hidden="true"><path d="M8 30c8-8 16-8 22-2M132 30c-8-8-16-8-22-2M8 60c8 8 16 8 22 2M132 60c-8 8-16 8-22 2" fill="none" stroke="#e7eef0" stroke-width="3"/><path d="M28 22c28-8 56-8 84 0v46c-28 8-56 8-84 0z" fill="#8fd0e6" stroke="#6bb6cf"/><path d="M28 36c28-6 56-6 84 0M28 48c28-6 56-6 84 0M28 60c28-6 56-6 84 0" stroke="#74bdd6" stroke-width="1.4" fill="none"/><path d="M50 18c14-3 26-3 40 0" stroke="#c9d6da" stroke-width="3" fill="none"/></svg>',
        toalla: '<svg viewBox="0 0 120 110" aria-hidden="true"><path d="M20 106V40c0-14 8-28 40-34 32 6 40 20 40 34v66z" fill="#ffffff" stroke="#d8e2e4"/><path d="M34 106V46M48 106V40M62 106V36M76 106V40M90 106V46" stroke="#eef3f4" stroke-width="2"/></svg>',
        generico: '<svg viewBox="0 0 120 90" aria-hidden="true"><rect x="22" y="12" width="76" height="66" rx="10" fill="#fbfcfc" stroke="#d3dcdf"/><path d="M54 28h12v12h12v12H66v12H54V52H42V40h12z" fill="var(--k)"/></svg>',
    };
    const COLORES = ['#0e7c72', '#1d5fa8', '#4b3fa0', '#8a2f6b', '#2f7a3a', '#0e6e8c', '#2f4b7c', '#6a4c93'];

    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const MINUS = new Set(['de', 'del', 'con', 'y', 'en', 'para', 'la', 'el', 'por', 'sin', 'a']);
    const titulo = t => String(t || '').toLowerCase().replace(/\s+/g, ' ').trim()
        .split(' ').map((w, i) => (i && MINUS.has(w)) || /^\d/.test(w) || /^(mg|mcg|g|ml|ui|µg|kg|l|cm|mm|m|x)$/.test(w) ? w : w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

    // ----- Códigos de barras -----
    function ean13(cod) {
        if (!/^\d{13}$/.test(cod)) return '';
        const L = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];
        const G = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];
        const R = L.map(s => s.replace(/./g, b => b === '1' ? '0' : '1'));
        const PAR = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];
        const d = cod.split('').map(Number), par = PAR[d[0]];
        // Dígito verificador: si no cuadra, se usa Code 128 para que el lector lea el texto tal cual
        const suma = d.slice(0, 12).reduce((s, n, i) => s + n * (i % 2 ? 3 : 1), 0);
        if ((10 - suma % 10) % 10 !== d[12]) return '';
        let bits = '101';
        for (let i = 1; i <= 6; i++) bits += (par[i - 1] === 'L' ? L : G)[d[i]];
        bits += '01010';
        for (let i = 7; i <= 12; i++) bits += R[d[i]];
        bits += '101';
        return trazar(bits, 7);
    }
    function code128(texto) {
        const P = ['212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'];
        const t = String(texto || '').replace(/[^\x20-\x7E]/g, '');
        if (!t) return '';
        const vals = [104];
        for (const ch of t) vals.push(ch.charCodeAt(0) - 32);
        let suma = 104;
        for (let i = 1; i < vals.length; i++) suma += vals[i] * i;
        vals.push(suma % 103, 106);
        let bits = '';
        vals.forEach(v => { const w = P[v]; for (let i = 0; i < w.length; i++) bits += (i % 2 ? '0' : '1').repeat(Number(w[i])); });
        return trazar(bits, 10);
    }
    function trazar(bits, q) {
        let d = '', x = q;
        for (let i = 0; i < bits.length;) {
            let n = 0; const b = bits[i];
            while (bits[i] === b) { n++; i++; }
            if (b === '1') d += 'M' + x + ' 0h' + n + 'v40h-' + n + 'z';
            x += n;
        }
        return '<svg viewBox="0 0 ' + (x + q) + ' 40" preserveAspectRatio="none" shape-rendering="crispEdges" aria-hidden="true"><path d="' + d + '" fill="#0b1e1c"/></svg>';
    }
    const barras = cod => ean13(cod) || code128(cod);
    const api = {
        esc, titulo, barras,
        normalizar(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
        dinero(n, dec = 2) { return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: dec, maximumFractionDigits: dec }); },
        textoEstado(p) { return { ok: 'Suficiente', bajo: 'Por surtir', agotado: 'Agotado' }[p.estado] || ''; },
        descripcionUtil(p) { const d = String(p.descripcion || '').trim(); return d.length > 2 ? titulo(d).replace(/^./, c => c.toUpperCase()) : ''; },
        colorDe(p) { let h = 0; for (const ch of String(p.categoria || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0; return COLORES[h % COLORES.length]; },
        iniciales(p) { return this.partes(p).principal.replace(/[^A-Za-zÁÉÍÓÚÑáéíóúñ]/g, '').slice(0, 2) || '·'; },
        itemSVG(item) { return ITEMS[item] || ITEMS.generico; },
        partes(p) {
            const n = p.nombre || '';
            if (cachePartes.has(n)) return cachePartes.get(n);
            const m = /((?:\d+(?:[.,]\d+)?\s*x\s*)?\d+(?:[.,]\d+)?\s*(?:mg|mcg|µg|g|ml|ui|%|cm|mm)(?:\s*\/\s*\d*(?:[.,]\d+)?\s*(?:ml|g|dosis))?)(?![a-z])/i.exec(n);
            let principal = m ? n.slice(0, m.index) : n, dosis = m ? m[1] : '', forma = m ? n.slice(m.index + m[1].length) : '';
            principal = titulo(principal.replace(/[\s,.-]+$/, '')) || titulo(n);
            dosis = dosis.toLowerCase().replace(/\s+/g, ' ').replace(/(\d)\s*(mg|mcg|µg|g|ml|ui|cm|mm)/g, '$1 $2').replace(/ui/, 'UI');
            forma = titulo(forma.replace(/^[\s,.-]+/, ''));
            const r = { principal, dosis, forma };
            cachePartes.set(n, r);
            return r;
        },
        tipoDe(p) {
            const clave = (p.nombre || '') + '|' + (p.categoria || '');
            if (cacheTipo.has(clave)) return cacheTipo.get(clave);
            const n = this.normalizar(p.nombre), c = this.normalizar(p.categoria), t = n + ' ' + c;
            const catInsumo = /insumo|material|curacion|desechable|equipo|quirurg|consumible/.test(c);
            let r = { clase: 'medicamento', item: '' };
            const hay = (re) => re.test(n) || (catInsumo && re.test(t));
            if (/guante|cubrebocas|mascarilla|tapabocas|toallita|panuelo|cofia|gorro/.test(n)) r = { clase: 'dispensador', item: /guante/.test(n) ? 'guante' : (/toallita|panuelo/.test(n) ? 'toalla' : 'cubrebocas') };
            else if (/jeringa/.test(n)) r = { clase: 'sobre', item: 'jeringa' };
            else if (/aguja|lanceta|punzocat|bisturi/.test(n)) r = { clase: 'sobre', item: 'aguja' };
            else if (/gasa|aposito|compresa|torunda|algodon/.test(n)) r = { clase: 'sobre', item: 'gasa' };
            else if (/venda|tela adhesiva|micropore|esparadrapo|cinta/.test(n)) r = { clase: 'sobre', item: 'venda' };
            else if (/curita|bandita|tirita/.test(n)) r = { clase: 'sobre', item: 'curita' };
            else if (/cateter|sonda|venoclisis|equipo de|canula|sutura|electrodo|tubo/.test(n)) r = { clase: 'sobre', item: 'sonda' };
            else if (/alcohol|oxigenada|antisep|isodine|yodopovidona|benzal|clorhexidina|jabon|gel antibac|sanitizante/.test(n)) r = { clase: 'frasco', item: '' };
            else if (catInsumo || /termometro|abatelengua|hisopo|tira reactiva|bolsa|campo|bata|cubreboca/.test(n)) r = { clase: 'sobre', item: /termometro/.test(n) ? 'termometro' : 'generico' };
            r.insumo = r.clase !== 'medicamento';
            cacheTipo.set(clave, r);
            return r;
        },
        contenidoHTML(p) {
            const tipo = this.tipoDe(p);
            if (tipo.insumo) {
                const svg = this.itemSVG(tipo.item);
                return '<div class="ca iv-it">' + svg + '</div><div class="cb iv-it">' + svg + '</div>';
            }
            const pt = this.partes(p), forma = this.normalizar(pt.forma + ' ' + p.nombre);
            const liquido = /jarabe|suspension|solucion|gotas|ampolleta|ampula|inyectable|crema|pomada|gel|unguento|spray|aerosol|frasco|ovulo|supositorio/.test(forma);
            const nom = esc(pt.principal + (pt.dosis ? ' ' + pt.dosis : ''));
            if (liquido) {
                return '<div class="ca iv-hoja"><b>' + nom + '</b>' + '<i></i>'.repeat(11) + '</div><div class="cb iv-hoja"><b>Instructivo</b>' + '<i></i>'.repeat(11) + '</div>';
            }
            const capsula = /capsula/.test(forma);
            const una = capsula ? '<i><span class="cap"></span></i>' : '<i><span class="tb"></span></i>';
            return '<div class="ca iv-bl' + (capsula ? '' : ' tab') + '">' + una.repeat(10) + '</div>'
                + '<div class="cb iv-blb">' + ('<span>' + nom + ' · ' + nom + ' · ' + nom + '</span>').repeat(5) + '</div>';
        },
        cajaHTML(p, completo) {
            const tipo = this.tipoDe(p);
            const pt = this.partes(p), nom = pt.principal, cod = p.codigo;
            const sello = p.estado === 'agotado' ? '<i class="sello">Agotado</i>' : (p.estado === 'bajo' ? '<i class="sello bajo">Por surtir</i>' : '');
            const desc = this.descripcionUtil(p) || pt.forma || p.categoria;
            const reverso = '<div class="iv-cara f-re"><i class="franja"></i>'
                +   '<div class="txt"><b>' + esc(nom + (pt.dosis ? ' ' + pt.dosis : '')) + '</b><span>' + esc(desc) + '</span>'
                +     '<dl><dt>Precio</dt><dd>' + esc(this.dinero(p.venta)) + '</dd><dt>Existencias</dt><dd>' + p.disponible + ' pzas</dd><dt>Mínimo</dt><dd>' + p.minimo + ' pzas</dd></dl></div>'
                +   '<div class="bar">' + barras(cod) + '<span>' + esc(cod) + '</span></div></div>';
            const lados = '<div class="iv-cara f-de"><span class="v">' + esc(pt.dosis || nom) + (pt.dosis ? '<small> · ' + esc(nom) + '</small>' : '') + '</span></div>'
                + '<div class="iv-cara f-iz"><span class="v">' + esc(nom) + (pt.dosis ? ' <i>' + esc(pt.dosis) + '</i>' : '') + '</span></div>'
                + '<div class="iv-cara f-ab"><span>' + esc(p.categoria) + '</span><span>' + esc(this.textoEstado(p)) + '</span></div>';
            const etq = /insumo/i.test(p.categoria) ? p.categoria : 'Insumo · ' + p.categoria;
            const arriba = '<i class="tapa"></i><b>' + esc(nom) + '</b><span>' + esc(cod) + '</span>';
            const interior = '<div class="iv-in in-fr"></div><div class="iv-in in-re"></div><div class="iv-in in-de"></div><div class="iv-in in-iz"></div><div class="iv-in in-ab"></div>';

            if (tipo.clase === 'sobre') {
                // Sobre de insumo: papel grado médico con ventana transparente y borde sellado
                const frente = '<div class="iv-cara f-fr s-fr' + (sello ? ' con-sello' : '') + '">' + sello
                    + '<span class="cat">' + esc(etq) + '</span>'
                    + '<b class="nom">' + esc(nom) + '</b>' + (pt.dosis ? '<span class="dosis">' + esc(pt.dosis) + '</span>' : '')
                    + '<span class="ventana">' + this.itemSVG(tipo.item) + '</span>'
                    + '<span class="pie">' + esc(cod) + '</span></div>';
                return frente + reverso + lados
                    + (completo ? '<div class="iv-cont">' + this.contenidoHTML(p) + '</div>'
                        + '<div class="iv-tapa"><div class="iv-cara f-ar"></div><div class="iv-cara f-ar-in"></div></div>'
                        : '<div class="iv-cara f-ar"></div>');
            }
            if (tipo.clase === 'dispensador') {
                // Caja dispensadora (guantes, cubrebocas): ranura arriba y la pieza asomándose
                const frente = '<div class="iv-cara f-fr d-fr' + (sello ? ' con-sello' : '') + '">' + sello
                    + '<span class="cat">' + esc(etq) + '</span>'
                    + '<b class="nom">' + esc(nom) + '</b>' + (pt.dosis ? '<span class="dosis">' + esc(pt.dosis) + '</span>' : '') + (pt.forma ? '<span class="forma">' + esc(pt.forma) + '</span>' : '')
                    + '<span class="dibujo">' + this.itemSVG(tipo.item) + '</span>'
                    + '<span class="pie">' + esc(cod) + '</span></div>';
                return frente + reverso + lados
                    + (completo ? '<div class="iv-cont asoma">' + this.contenidoHTML(p) + '</div>' : '')
                    + '<div class="iv-cara f-ar d-ar"><i class="ranura"></i></div>';
            }
            if (tipo.clase === 'frasco') {
                // Frasco rectangular con tapa de rosca
                const frente = '<div class="iv-cara f-fr fr-fr' + (sello ? ' con-sello' : '') + '">' + sello
                    + '<span class="cat">Insumo</span><b class="nom">' + esc(nom) + '</b>'
                    + (pt.dosis ? '<span class="dosis">' + esc(pt.dosis) + '</span>' : '')
                    + '<span class="gota"></span><span class="pie">' + esc(cod) + '</span></div>';
                const tapon = '<div class="iv-tapon"><i class="tp tp-fr"></i><i class="tp tp-re"></i><i class="tp tp-de"></i><i class="tp tp-iz"></i><i class="tp tp-ar"></i></div>';
                return frente + reverso + lados + '<div class="iv-cara f-ar fr-ar"></div>' + tapon;
            }

            // Medicamento: caja de cartón con tapa y blíster
            const cruz = '<svg class="cruz" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z" fill="currentColor"/></svg>';
            const frente = '<div class="iv-cara f-fr' + (sello ? ' con-sello' : '') + '"><i class="ola2"></i><i class="ola"></i>' + cruz + sello
                + '<span class="cat">' + esc(p.categoria) + '</span>'
                + '<b class="nom' + (nom.length > 16 ? ' largo' : '') + '">' + esc(nom) + '</b>'
                + (pt.dosis ? '<span class="dosis">' + esc(pt.dosis) + '</span>' : '')
                + (pt.forma ? '<span class="forma">' + esc(pt.forma) + '</span>' : '')
                + '<span class="pie">' + esc(cod) + '</span></div>';
            return frente + reverso + lados
                + (completo
                    ? interior + '<div class="iv-cont">' + this.contenidoHTML(p) + '</div>'
                      + '<div class="iv-tapa"><div class="iv-cara f-ar">' + arriba + '</div><div class="iv-cara f-ar-in"></div></div>'
                    : '<div class="iv-cara f-ar">' + arriba + '</div>');
        },
        // Sombra y brillo de cada cara según el giro (devuelve variables CSS)
        luces(rx, ry) {
            const a = rx * Math.PI / 180, b = ry * Math.PI / 180;
            const ca = Math.cos(a), sa = Math.sin(a), cb = Math.cos(b), sb = Math.sin(b);
            const H = [LUZ[0], LUZ[1], LUZ[2] + 1], hm = Math.hypot(...H);
            const vars = [];
            for (const k in NORMALES) {
                const [x, y, z] = NORMALES[k];
                const x1 = cb * x + sb * z, z1 = -sb * x + cb * z;
                const n = [x1, ca * y - sa * z1, sa * y + ca * z1];
                const d = n[0] * LUZ[0] + n[1] * LUZ[1] + n[2] * LUZ[2];
                const sp = Math.max(0, (n[0] * H[0] + n[1] * H[1] + n[2] * H[2]) / hm);
                const g = Math.min(.75, Math.pow(sp, 14) * .85 + Math.max(0, n[2]) * .06);
                const pos = 50 + Math.max(-60, Math.min(60, Math.atan2(n[0], n[2]) * 57.3)) * 1.3;
                vars.push('--s-' + k + ':' + Math.max(0, Math.min(.38, (.86 - d) * .36)).toFixed(3), '--g-' + k + ':' + g.toFixed(3), '--p-' + k + ':' + pos.toFixed(1) + '%');
            }
            return vars.join(';');
        },
        // Aplica giro y luces a un elemento .iv-caja (conserva sus otros estilos)
        girar(el, rx, ry, tz = 0) {
            if (!el) return;
            el.style.cssText = el.style.cssText.replace(/--[sgp]-(fr|re|de|iz|ar|ab):[^;]*;?/g, '').replace(/transform:[^;]*;?/g, '') + ';' + this.luces(rx, ry);
            el.style.transform = 'translateZ(' + tz.toFixed(1) + 'px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg)';
        },
    };
    return api;
})();

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
/* ---------- CAJA 3D (Inventario y Dashboard): geometría, caras, insumos, tapa y contenido ---------- */
/* La caja: --w ancho, --h alto, --d fondo. Las caras se colocan alrededor del centro. */
.iv-caja { --w: 236px; --h: 130px; --d: 58px; --k: #0b5a54; --tinta: #0b1e1c; --e: cubic-bezier(.2, .8, .2, 1);
    --disp: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif; --ui: 'Geist', ui-sans-serif, system-ui, sans-serif; --mono: 'Geist Mono', ui-monospace, monospace;
    position: relative; width: var(--w); height: var(--h); transform-style: preserve-3d; transform: rotateX(-16deg) rotateY(-32deg); will-change: transform; }
.iv-cara { position: absolute; overflow: hidden; backface-visibility: hidden; -webkit-backface-visibility: hidden; background: #fbfdfc; color: var(--tinta);
    font-family: var(--ui); line-height: 1.15; }
.iv-cara:before { content: ""; position: absolute; inset: 0; z-index: 3; pointer-events: none;
    background: linear-gradient(112deg, transparent 36%, rgba(255, 255, 255, .8) 50%, transparent 64%); background-size: 280% 100%; background-position: var(--p, 50%) 0; opacity: var(--g, 0); }
.iv-cara:after { content: ""; position: absolute; inset: 0; z-index: 4; background: #021614; opacity: var(--s, 0); pointer-events: none; box-shadow: inset 0 0 0 .6px rgba(255, 255, 255, .55); }
.f-fr, .f-re { width: var(--w); height: var(--h); left: 0; top: 0; }
.f-fr { transform: translateZ(calc(var(--d) / 2)); --s: var(--s-fr); --g: var(--g-fr); --p: var(--p-fr); }
.f-re { transform: rotateY(180deg) translateZ(calc(var(--d) / 2)); --s: var(--s-re); --g: var(--g-re); --p: var(--p-re); }
.f-de, .f-iz { width: var(--d); height: var(--h); left: calc((var(--w) - var(--d)) / 2); top: 0; }
.f-de { transform: rotateY(90deg) translateZ(calc(var(--w) / 2)); --s: var(--s-de); --g: var(--g-de); --p: var(--p-de); }
.f-iz { transform: rotateY(-90deg) translateZ(calc(var(--w) / 2)); --s: var(--s-iz); --g: var(--g-iz); --p: var(--p-iz); }
.f-ar, .f-ab { width: var(--w); height: var(--d); left: 0; top: calc((var(--h) - var(--d)) / 2); }
.f-ar { transform: rotateX(90deg) translateZ(calc(var(--h) / 2)); --s: var(--s-ar); --g: var(--g-ar); --p: var(--p-ar); }
.f-ab { transform: rotateX(-90deg) translateZ(calc(var(--h) / 2)); --s: var(--s-ab); }

/* Caja completa (visor): tapa con bisagra, paredes interiores y contenido que sale al abrir */
.iv-tapa { position: absolute; left: 0; top: 0; width: var(--w); height: var(--d); transform-origin: 50% 0; transform-style: preserve-3d;
    transform: translateZ(calc(var(--d) / -2)) rotateX(90deg); transition: transform .5s var(--e) .12s; }
.abierta .iv-tapa { transform: translateZ(calc(var(--d) / -2)) rotateX(212deg); transition-delay: 0s; }
.iv-tapa > .f-ar { left: 0; top: 0; transform: none; }
.iv-tapa > .f-ar-in { left: 0; top: 0; width: var(--w); height: var(--d); transform: rotateY(180deg); background: linear-gradient(180deg, #dbe5e2, #c9d6d2); }
.iv-in { position: absolute; backface-visibility: hidden; -webkit-backface-visibility: hidden; background: linear-gradient(180deg, #b9c8c4, #dfe8e5); }
.in-fr, .in-re { width: var(--w); height: var(--h); left: 0; top: 0; }
.in-fr { transform: translateZ(calc(var(--d) / 2 - 1px)) rotateY(180deg); }
.in-re { transform: translateZ(calc(var(--d) / -2 + 1px)); background: linear-gradient(180deg, #a9bab5, #d3dedb); }
.in-de, .in-iz { width: var(--d); height: var(--h); left: calc((var(--w) - var(--d)) / 2); top: 0; background: linear-gradient(180deg, #a3b5b0, #ccd8d5); }
.in-de { transform: rotateY(90deg) translateZ(calc(var(--w) / 2 - 1px)) rotateY(180deg); }
.in-iz { transform: rotateY(-90deg) translateZ(calc(var(--w) / 2 - 1px)) rotateY(180deg); }
.in-ab { width: var(--w); height: var(--d); left: 0; top: calc((var(--h) - var(--d)) / 2); transform: rotateX(90deg) translateZ(calc(var(--h) / -2 + 1px)); background: #c3d1cd; }
.iv-cont { position: absolute; left: 12px; top: 9px; width: calc(var(--w) - 24px); height: calc(var(--h) - 16px); transform-style: preserve-3d; transition: transform .45s var(--e); }
.abierta .iv-cont { transform: translateY(calc(var(--h) * -.62)) rotateX(-4deg); transition: transform .7s var(--e) .3s; }
.iv-cont > div { position: absolute; inset: 0; backface-visibility: hidden; -webkit-backface-visibility: hidden; border-radius: 7px; overflow: hidden; }
.iv-cont > .cb { transform: rotateY(180deg); }
.iv-bl { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); grid-template-rows: repeat(2, minmax(0, 1fr)); gap: 7px 9px; padding: 11px 13px;
    background: linear-gradient(128deg, #f6f9fa 0%, #c3ced2 26%, #f3f7f8 48%, #b2bfc4 72%, #eef3f4 100%); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .8); }
.iv-bl i { display: grid; place-items: center; border-radius: 999px;
    background: radial-gradient(circle at 34% 28%, rgba(255, 255, 255, .98), rgba(255, 255, 255, .3) 38%, rgba(170, 188, 194, .4) 72%);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .8), 0 2px 4px rgba(0, 0, 0, .18); }
.iv-bl.tab i { border-radius: 50%; aspect-ratio: 1; align-self: center; }
.iv-bl .cap { width: 46%; height: 80%; border-radius: 999px; background: linear-gradient(180deg, var(--k) 50%, #f7f7f2 50%); box-shadow: inset -2px 0 3px rgba(0, 0, 0, .18), inset 2px 0 2px rgba(255, 255, 255, .5); }
.iv-bl .tb { width: 74%; aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle at 40% 35%, #ffffff, #e9ebe4 70%); box-shadow: inset 0 -2px 3px rgba(0, 0, 0, .12); position: relative; }
.iv-bl .tb:after { content: ""; position: absolute; left: 18%; right: 18%; top: 50%; border-top: 1px solid rgba(0, 0, 0, .12); }
.iv-blb { display: grid; align-content: center; gap: 8px; padding: 10px; background: linear-gradient(128deg, #eef2f3 0%, #c5cfd3 30%, #f2f5f6 52%, #b9c5c9 78%, #e9eef0 100%); }
.iv-blb span { font: 700 11px var(--disp); color: var(--k); opacity: .7; white-space: nowrap; letter-spacing: .02em; }
.iv-blb span:nth-child(even) { margin-left: -30px; }
.iv-hoja { padding: 12px 14px; background: #fdfdfb; display: flex; flex-direction: column; gap: 5px; box-shadow: inset 0 0 0 1px #e3e8e6; }
.iv-hoja b { font: 700 12px var(--disp); color: var(--tinta); }
.iv-hoja i { height: 4px; border-radius: 2px; background: #dfe5e3; }
.iv-hoja i:nth-child(3n) { width: 70%; } .iv-hoja i:nth-child(4n) { width: 86%; }

/* ---------- Insumos: sobre, caja dispensadora y frasco ---------- */
.iv-caja.t-sobre { --w: 250px; --h: 112px; --d: 12px; }
.iv-caja.t-dispensador { --w: 206px; --h: 104px; --d: 112px; }
.iv-caja.t-frasco { --w: 100px; --h: 168px; --d: 64px; }
.t-sobre .iv-cara { background: #f6f8f7; }
.t-sobre .f-de .v, .t-sobre .f-iz .v, .t-sobre .f-ab span, .t-frasco .f-de .v, .t-frasco .f-iz .v, .t-frasco .f-ab span { display: none; }
.t-sobre .f-de, .t-sobre .f-iz { background: #e3eae7; }
.f-fr.s-fr { padding: 12px 14px; border: 7px solid #e3eae7; border-image: repeating-linear-gradient(45deg, #d8e1de 0 2px, #eef3f1 2px 4px) 7; }
.iv-cara.s-fr .cat { max-width: 44%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.iv-cara.s-fr .cat, .iv-cara.d-fr .cat, .iv-cara.fr-fr .cat { font: 600 7.5px var(--mono); letter-spacing: .12em; text-transform: uppercase; color: var(--k); }
.iv-cara.s-fr .nom { margin-top: 4px; max-width: 42%; font: 750 14.5px/1.05 var(--disp); overflow-wrap: break-word; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.iv-cara.s-fr .dosis { margin-top: 3px; font: 700 12px var(--ui); color: var(--k); }
.iv-cara.s-fr .pie { position: absolute; left: 14px; bottom: 7px; margin: 0; }
.iv-cara.s-fr .ventana { position: absolute; right: 8px; top: 8px; bottom: 8px; width: 50%; border-radius: 8px; display: grid; place-items: center; padding: 4px;
    background: linear-gradient(135deg, rgba(214, 233, 240, .75), rgba(240, 248, 250, .55) 50%, rgba(206, 226, 234, .7)); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .95), inset 0 0 12px rgba(120, 160, 175, .25); }
.iv-cara.s-fr .ventana svg { width: 100%; max-height: 100%; }
.iv-cara.d-fr { padding: 12px 14px 10px; display: flex; flex-direction: column; background: linear-gradient(180deg, #fbfdfc 62%, var(--k) 62%) !important; }
.iv-cara.d-fr .nom { margin-top: 5px; max-width: 112px; font: 750 19px/1 var(--disp); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.iv-cara.d-fr .dosis { margin-top: 4px; font: 700 12px var(--ui); color: var(--k); }
.iv-cara.d-fr .forma { font-size: 9px; color: #56625f; }
.iv-cara.d-fr .pie { position: absolute; left: 14px; bottom: 9px; color: rgba(255, 255, 255, .85); margin: 0; }
.iv-cara.d-fr .dibujo { position: absolute; right: 8px; bottom: 6px; width: 74px; height: 86px; display: grid; place-items: end center; }
.iv-cara.d-fr .dibujo svg { width: 100%; height: 100%; filter: drop-shadow(0 3px 4px rgba(0, 0, 0, .2)); }
.d-ar { background: #fbfdfc; }
.d-ar .ranura { position: absolute; left: 22%; right: 22%; top: 28%; bottom: 28%; border-radius: 50%; background: radial-gradient(closest-side, #12201e 60%, #33433f); box-shadow: 0 0 0 3px #e3eae7; }
.t-dispensador .f-de { background: var(--k); }
.t-dispensador .f-iz { background: #fbfdfc; }
.iv-cont.asoma { left: 24%; top: 0; width: 52%; height: var(--h); transform: translateY(-30%); }
.abierta .iv-cont.asoma { transform: translateY(-64%); }
.iv-cont > .iv-it { display: grid; place-items: center; background: none; border-radius: 0; overflow: visible; }
.iv-it svg { width: 100%; max-height: 100%; filter: drop-shadow(0 6px 8px rgba(0, 0, 0, .25)); }
.iv-cont.asoma > .iv-it { place-items: start center; }
.iv-cara.fr-fr { padding: 30px 7px 10px; display: flex; flex-direction: column; align-items: center; text-align: center;
    background: linear-gradient(180deg, #f4f6f5 0 18%, #fff 18% 82%, #f4f6f5 82%) !important; }
.iv-cara.fr-fr .nom { margin-top: 4px; font: 750 13px/1.05 var(--disp); overflow-wrap: break-word; }
.iv-cara.fr-fr .dosis { margin-top: 3px; font: 700 11px var(--ui); color: var(--k); }
.iv-cara.fr-fr .gota { margin-top: 8px; width: 22px; height: 22px; border-radius: 50% 0 50% 50%; transform: rotate(-45deg); background: var(--k); opacity: .85; }
.iv-cara.fr-fr .pie { margin-top: auto; font: 500 6.5px var(--mono); color: #56625f; }
.iv-cara.fr-fr .sello { left: 50%; translate: -50% 0; }
.t-frasco .f-de, .t-frasco .f-iz, .fr-ar { background: #eef2f1; }
.t-frasco .f-re { grid-template-columns: minmax(0, 1fr); grid-template-rows: minmax(0, 1fr) auto; padding: 10px 8px; }
.t-frasco .f-re .bar svg { width: 76px; height: 30px; }
.iv-tapon { position: absolute; left: calc((var(--w) - 40px) / 2); top: -24px; width: 40px; height: 24px; transform-style: preserve-3d; transition: transform .6s var(--e); }
.abierta .iv-tapon { transform: translateY(-46px) rotateY(150deg); }
.iv-tapon .tp { position: absolute; left: 0; top: 0; width: 40px; height: 24px; backface-visibility: hidden; -webkit-backface-visibility: hidden;
    background: repeating-linear-gradient(90deg, var(--k) 0 3px, color-mix(in srgb, var(--k), #000 18%) 3px 5px); }
.tp-fr { transform: translateZ(20px); } .tp-re { transform: rotateY(180deg) translateZ(20px); }
.tp-de { transform: rotateY(90deg) translateZ(20px); } .tp-iz { transform: rotateY(-90deg) translateZ(20px); }
.iv-tapon .tp-ar { height: 40px; top: -8px; background: var(--k); transform: rotateX(90deg) translateZ(20px); border-radius: 4px; }
.iv-pk.ins { place-items: center; padding: 0; }
.iv-pk.ins:before { opacity: .25; }
.iv-pk.ins span { position: relative; width: 34px; display: grid; place-items: center; }
.iv-pk.ins svg { width: 100%; max-height: 30px; }
.iv-ficha-cat em { margin-left: auto; font-style: normal; padding: 2px 8px; border-radius: 999px; background: var(--niebla); color: var(--texto); letter-spacing: .06em; }

/* Frente */
.f-fr { padding: 12px 14px 10px; display: flex; flex-direction: column; }
.f-fr .ola { position: absolute; right: -40px; bottom: -52px; width: 150px; height: 112px; border-radius: 50%; background: var(--k); }
.f-fr .ola2 { position: absolute; right: 30px; bottom: -74px; width: 150px; height: 100px; border-radius: 50%; background: var(--k); opacity: .18; }
.f-fr .cat { font: 600 8px var(--mono); letter-spacing: .14em; text-transform: uppercase; color: var(--k); }
.f-fr .nom { margin-top: 6px; max-width: 168px; font: 750 21px/1 var(--disp); letter-spacing: -.01em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.f-fr .nom.largo { font-size: 16px; }
.f-fr .dosis { margin-top: 5px; font: 750 15px/1 var(--ui); color: var(--k); }
.f-fr .forma { margin-top: 3px; font-size: 10px; font-weight: 500; color: #56625f; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.f-fr .pie { margin-top: auto; position: relative; display: flex; align-items: center; gap: 6px; font: 500 7.5px var(--mono); letter-spacing: .08em; color: #56625f; }
.f-fr .cruz { position: absolute; right: 14px; bottom: 12px; width: 26px; height: 26px; color: #fff; z-index: 1; }
.f-fr .sello { position: absolute; z-index: 2; left: 11px; bottom: 8px; padding: 2px 7px; border: 2px solid #c8102e; border-radius: 5px; color: #c8102e; font: 800 10.5px var(--disp); font-style: normal; letter-spacing: .08em; text-transform: uppercase; transform: rotate(-5deg); background: rgba(255, 255, 255, .9); }
.f-fr.con-sello .pie { visibility: hidden; }
.iv-caja.mini .sello { display: none; }
.iv-caja.mini .f-fr.con-sello .pie { visibility: visible; }
.f-fr .sello.bajo { border-color: #9a5800; color: #9a5800; }

/* Reverso */
.f-re { padding: 11px 13px; display: grid; grid-template-columns: minmax(0, 1fr) 104px; gap: 10px; }
.f-re .txt { min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.f-re .txt b { font: 700 11px/1.15 var(--disp); }
.f-re .txt span { font-size: 8px; color: #56625f; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
.f-re dl { margin-top: auto; display: grid; grid-template-columns: auto 1fr; gap: 1px 8px; font-size: 8px; }
.f-re dt { color: #56625f; }
.f-re dd { font: 600 8.5px var(--mono); text-align: right; }
.f-re .bar { align-self: end; display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 5px 4px 3px; background: #fff; border-radius: 3px; }
.f-re .bar svg { display: block; width: 96px; height: 38px; }
.f-re .bar span { font: 500 7.5px var(--mono); letter-spacing: .06em; color: #0b1e1c; }
.f-re .franja { position: absolute; left: 0; right: 0; top: 0; height: 4px; background: var(--k); }

/* Lados */
.f-de { background: var(--k); color: #fff; display: grid; place-items: center; }
.f-de .v, .f-iz .v { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-height: 116px; }
.f-de .v { font: 750 15px var(--disp); }
.f-de .v small { font: 600 9px var(--ui); opacity: .8; margin-top: 6px; }
.f-iz { display: grid; place-items: center; }
.f-iz .v { font: 700 13px var(--disp); color: var(--tinta); }
.f-iz .v i { font-style: normal; color: var(--k); }
.f-ar { display: flex; align-items: center; gap: 10px; padding: 0 14px; }
.f-ar .tapa { position: absolute; left: 10px; right: 10px; top: 50%; border-top: 1px dashed #c3d2ce; }
.f-ar b { position: relative; background: #fbfdfc; padding-right: 8px; font: 700 12px var(--disp); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
.f-ar span { position: relative; margin-left: auto; background: #fbfdfc; padding-left: 8px; font: 500 8px var(--mono); color: #56625f; }
.f-ab { display: flex; align-items: center; justify-content: space-between; padding: 0 14px; font: 500 8.5px var(--mono); color: #56625f; background: #f1f6f4; }

/* Caja pequeña del anaquel */
.iv-hueco-caja { position: relative; display: block; height: 104px; perspective: 700px; }
.iv-caja.mini { position: absolute; left: 50%; top: 50%; margin: calc(var(--h) / -2) 0 0 calc(var(--w) / -2);
    transform: scale(.5) rotateX(-14deg) rotateY(-30deg); transition: transform .35s var(--e); }
.iv-caja.mini .iv-cara:after { opacity: 0; }
.iv-caja.mini .f-de:after { opacity: .28; }
.iv-caja.mini .f-ar:after { opacity: .04; }
.iv-hueco:hover .iv-caja.mini, .iv-hueco.sel .iv-caja.mini { transform: scale(.54) rotateX(-12deg) rotateY(-14deg); }

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
