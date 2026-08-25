/**
 * Global safety net for the mobile navigation — deliberately vanilla JS with
 * no dependency on Alpine, so it still works when Alpine is slow, broken, or
 * never loads.
 *
 * Why it exists:
 * - `x-trap.noscroll` (@alpinejs/focus) locks <html> overflow and compensates
 *   the scrollbar with paddingRight. If the page is frozen into the
 *   back/forward cache while the menu is open (or an interrupted transition
 *   leaves the lock behind), the restored page can be unscrollable. We release
 *   the lock on pagehide and again on every pageshow.
 * - When Alpine never initialises, the mobile menu's native `hidden` attribute
 *   keeps it out of view. We also force the panel shut (and keep it shut) so
 *   raw menu content can never sit over the page if that attribute is changed.
 *
 * This runs in the browser only; the `typeof window` guard keeps it safe if
 * the module is ever evaluated in a non-DOM context (SSR/build tooling).
 */
export function installMobileNavGuard(): void {
    if (typeof window === 'undefined' || typeof document === 'undefined') return;

    const releaseScrollLock = () => {
        const html = document.documentElement;
        html.style.overflow = '';
        html.style.paddingRight = '';
        document.body.style.overflow = '';
    };

    const hideMobileMenu = () => {
        const menu = document.getElementById('mobile-menu');
        if (menu) menu.style.display = 'none';
    };

    const hideMobileMenuIfUnmanaged = () => {
        // Only force the panel shut when Alpine is not available to manage it
        // during normal interaction; otherwise this would fight Alpine's own
        // x-show/transition handling. `window.Alpine` is set by
        // resources/js/app.js right before Alpine.start().
        const alpineLoaded = typeof (window as { Alpine?: unknown }).Alpine !== 'undefined';
        if (!alpineLoaded) hideMobileMenu();
    };

    // The page may be frozen into the back/forward cache with the menu open.
    // Release the scroll lock on the way out and again on every (re)show, so a
    // restored page is never left unscrollable. We hide the panel unconditionally
    // on pagehide/pageshow — a restored page must never start with the menu
    // open. toggleMobileMenu() removes the inline display:none on the next open,
    // so this does not prevent future opens.
    window.addEventListener('pagehide', () => {
        releaseScrollLock();
        hideMobileMenu();
    });
    window.addEventListener('pageshow', () => {
        releaseScrollLock();
        hideMobileMenu();
    });

    // If Alpine never starts (e.g. a broken chunk), keep the JS-only mobile
    // menu off the page once the dust settles.
    window.setTimeout(hideMobileMenuIfUnmanaged, 1000);
}
