/**
 * Client entry — Path B (Astro).
 *
 * The Alpine bootstrap lives in resources/js/app.js (kept from the Laravel
 * build). Alpine's module touches `MutationObserver` at import time, which
 * does not exist in the Node SSR/build process, so we only load it in the
 * browser. The dynamic import is still bundled by Vite; it just never
 * executes during static generation.
 */
if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    // Vanilla safety net: releases scroll locks and keeps the JS-only mobile
    // menu shut even if Alpine never initialises (see mobile-nav-guard.ts).
    void import('./mobile-nav-guard.ts').then(({ installMobileNavGuard }) => {
        installMobileNavGuard();
    });

    void import('../../resources/js/app.js').catch((error) => {
        console.error('[client] Alpine.js failed to initialise; JS-enhanced UI (mobile menu, tabs, carousels) stays hidden.', error);
    });
}
