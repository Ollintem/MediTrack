// Copia a public/vendor las librerías de la interfaz para que MediTrack funcione SIN internet.
// Antes se cargaban de CDNs (jsdelivr, unpkg, Google Fonts); sin conexión la pantalla quedaba a medias.
//
// Uso (solo cuando se actualiza una librería con npm):   npm run librerias
// Los archivos copiados SÍ van en git, así que las demás computadoras no necesitan correr nada.
import { cpSync, mkdirSync, readFileSync, rmSync, writeFileSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';

const NM = 'node_modules';
const DEST = 'public/vendor';

const copiar = (origen, destino) => {
    const de = join(NM, origen), a = join(DEST, destino);
    if (!existsSync(de)) throw new Error(`No existe ${de}. Corre "npm install" primero.`);
    mkdirSync(dirname(a), { recursive: true });
    cpSync(de, a, { recursive: true });
    console.log(`  ${origen} → ${a}`);
};

rmSync(DEST, { recursive: true, force: true });
console.log('Copiando librerías a public/vendor:');

// Alpine.js (misma build que daba el CDN: se carga con defer y arranca sola)
copiar('alpinejs/dist/cdn.min.js', 'alpinejs/alpine.min.js');
// SweetAlert2 (el CDN "sweetalert2@11" servía la build "all", que incluye sus estilos)
copiar('sweetalert2/dist/sweetalert2.all.min.js', 'sweetalert2/sweetalert2.all.min.js');
copiar('sweetalert2/dist/sweetalert2.min.css', 'sweetalert2/sweetalert2.min.css');
// Flatpickr (selector de fechas) y su traducción al español
copiar('flatpickr/dist/flatpickr.min.js', 'flatpickr/flatpickr.min.js');
copiar('flatpickr/dist/flatpickr.min.css', 'flatpickr/flatpickr.min.css');
copiar('flatpickr/dist/l10n/es.js', 'flatpickr/l10n/es.js');
// Bootstrap Icons (el CSS busca sus fuentes en ./fonts/)
copiar('bootstrap-icons/font/bootstrap-icons.min.css', 'bootstrap-icons/bootstrap-icons.min.css');
copiar('bootstrap-icons/font/fonts', 'bootstrap-icons/fonts');
// Lector de códigos QR / de barras (Caja)
copiar('html5-qrcode/html5-qrcode.min.js', 'html5-qrcode/html5-qrcode.min.js');

// Tipografías: se generan con los nombres que usa el sistema ('Geist', 'Geist Mono', 'Bricolage Grotesque')
// y solo los alfabetos latino y latino extendido (español).
const fuentes = [
    { paquete: '@fontsource-variable/geist', css: 'wght.css', familia: 'Geist' },
    { paquete: '@fontsource-variable/geist-mono', css: 'wght.css', familia: 'Geist Mono' },
    { paquete: '@fontsource-variable/bricolage-grotesque', css: 'standard.css', familia: 'Bricolage Grotesque' },
];
let css = '/* Generado por scripts/copiar-librerias.mjs — no editar a mano */\n';
mkdirSync(join(DEST, 'fonts'), { recursive: true });
for (const f of fuentes) {
    const original = readFileSync(join(NM, f.paquete, f.css), 'utf8');
    // Cada bloque va precedido por el nombre de su archivo, p. ej. /* geist-latin-ext-wght-normal */
    for (const [, nombre, bloque] of original.matchAll(/\/\*\s*([\w-]+)\s*\*\/\s*(@font-face\s*\{[^}]*\})/g)) {
        const alfabeto = nombre.includes('-latin-ext-') ? 'latin-ext' : (nombre.includes('-latin-') ? 'latin' : null);
        if (!alfabeto) continue;
        const archivo = bloque.match(/url\(\.\/files\/([^)]+\.woff2)\)/)[1];
        copiar(`${f.paquete}/files/${archivo}`, `fonts/${archivo}`);
        css += `/* ${f.familia} · ${alfabeto} */\n` + bloque
            .replace(/font-family:\s*'[^']+'/, `font-family: '${f.familia}'`)
            .replace(/url\(\.\/files\/[^)]+\.woff2\)/, `url(./${archivo})`)
            .replace(/url\([^)]+\.woff\)\s*format\('woff'\),?\s*/g, '') + '\n';
    }
}
writeFileSync(join(DEST, 'fonts', 'fuentes.css'), css);
console.log(`  fuentes → ${DEST}/fonts/fuentes.css`);
console.log('Listo.');
