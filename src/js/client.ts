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
    void import('../../resources/js/app.js');
}
