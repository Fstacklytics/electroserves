import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                'resources/views/**',
                'routes/**',
                'lang/**',
                'content/**',
            ],
        }),
    ],

    server: {
        host: '0.0.0.0',
        port: 5173,
        // The dev server is reached through a proxied preview host, so requests
        // arrive with a hostname the default allowlist would reject.
        cors: true,
        strictPort: false,
        hmr: {
            host: process.env.VITE_HMR_HOST || undefined,
        },
    },

    build: {
        // Fail the build if a bundle grows unexpectedly large; the performance
        // budget is 500KB total for HTML + CSS + JS.
        chunkSizeWarningLimit: 300,
        cssCodeSplit: true,
        sourcemap: false,
    },
});
