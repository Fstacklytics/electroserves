import { defineConfig } from 'astro/config';

// Path B — Astro + Netlify static build.
//
// The site is fully static: content/ is parsed at build time into HTML, so
// there is no PHP runtime, no file-backed session, no request-time Markdown
// parsing and no app-level response cache. See docs/DEPLOY-NETLIFY-PATH-B.md
// for the trade-offs versus the old Nginx + PHP-FPM deployment.
export default defineConfig({
    output: 'static',
    // Canonical host. Override at build time with the SITE_URL env var if the
    // production domain differs (e.g. a Netlify preview URL must not be used
    // for canonical links).
    site: process.env.SITE_URL || 'https://electroserves.co.tz',
    trailingSlash: 'never',
    build: {
        assets: '_assets',
    },
    // Allow any Host header on the preview server so the live-preview host is
    // accepted during local/sandbox preview. Netlify serves production.
    vite: {
        preview: {
            allowedHosts: true,
        },
    },
});
