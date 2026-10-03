import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Runs inside the `vite` container: listen on all interfaces, but tell
        // the browser (and the Laravel @vite directive) to reach it via localhost.
        host: true,
        port: 5173,
        strictPort: true,
        hmr: {
            host: process.env.VITE_DEV_SERVER_HOST ?? 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
        },
    },
});
