import { defineConfig } from 'astro/config';

// Path B — Astro + Netlify static build.
//
// The site is fully static: content/ is parsed at build time into HTML, so
// there is no PHP runtime, no file-backed session, no request-time Markdown
// parsing and no app-level response cache. See docs/DEPLOY-NETLIFY-PATH-B.md
// for the trade-offs versus the old Nginx + PHP-FPM deployment.
export default defineConfig({
    output: 'static',
    // Canonical host. Production builds set SITE_URL in netlify.toml (or the
    // Netlify UI). The fallback is the intended custom domain; Decap
    // site_url / display_url must match whichever host actually serves the
    // site — see docs/NEXT-SESSION-CONTENT-NOT-LIVE.md.
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
