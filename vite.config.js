import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/auth.css' , 'resources/css/cards.css', 'resources/css/dashboard.css', 'resources/css/forms.css', 'resources/css/responsive.css', 'resources/css/tables.css'],
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
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
        // Allows the Vite dev server's HMR websocket + asset requests to be
        // accepted when the page itself is loaded from rental.test (Laragon)
        // instead of only localhost/127.0.0.1. Without this, @vite() assets
        // requested from the rental.test origin can be blocked as cross-origin
        // even though the dev server is running locally.
        hmr: {
            host: 'rental.test',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
