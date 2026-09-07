import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', // Permite que otras PCs de la red se conecten
        port: 5173,
        hmr: {
            host: '192.168.1.249', // Reemplaza esto por la IP local de tu PC host (ej: 192.168.1.50)
        },
    },
});