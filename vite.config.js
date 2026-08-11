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
        /*
         * Supported browsers: Chrome 90+, Firefox 90+, Safari 14+, Edge 90+.
         *
         * These are the esbuild target strings for exactly that matrix. Edge
         * 90+ is Chromium-based and covered by chrome90. Keep this list in
         * sync with the `browserslist` key in package.json, which is what
         * Autoprefixer reads for the CSS side of the same matrix.
         *
         * Safari 14 is the constraint that matters: it predates top-level
         * await and a few 2021+ syntax features, so esbuild will down-level
         * or error rather than silently shipping code that white-screens on
         * an older iPhone.
         */
        target: ['chrome90', 'firefox90', 'safari14', 'edge90'],

        // Fail the build if a bundle grows unexpectedly large; the performance
        // budget is 500KB total for HTML + CSS + JS.
        chunkSizeWarningLimit: 300,
        cssCodeSplit: true,
        sourcemap: false,
    },
});
