/**
 * Tailwind 3.4 (misma versión que daba cdn.tailwindcss.com) compilado a public/css/tailwind.css,
 * para que MediTrack funcione sin internet.
 *
 * IMPORTANTE: Tailwind solo incluye las clases que encuentra escritas en estos archivos.
 * Si agregas clases nuevas en una vista, vuelve a compilar:   npm run css
 * (o déjalo vigilando mientras trabajas:   npm run css:vigilar)
 */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
        // Paginación de Laravel (p. ej. la Bitácora usa ->links())
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};
