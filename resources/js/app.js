import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

import dropdown from './components/dropdown.js';
import modal from './components/modal.js';
import accordion from './components/accordion.js';
import tabs from './components/tabs.js';
import toast from './components/toast.js';
import carousel from './components/carousel.js';
import lightbox from './components/lightbox.js';
import contactForm from './components/contact-form.js';
import copyLink from './components/copy-link.js';

/*
 * The focus plugin provides x-trap, used by the modal and mobile menu to keep
 * keyboard focus inside an open overlay and restore it to the trigger on close.
 */
Alpine.plugin(focus);

/* x-collapse animates the FAQ accordion panels open and closed. */
Alpine.plugin(collapse);

Alpine.data('dropdown', dropdown);
Alpine.data('modal', modal);
Alpine.data('accordion', accordion);
Alpine.data('tabs', tabs);
Alpine.data('toast', toast);
Alpine.data('carousel', carousel);
Alpine.data('lightbox', lightbox);
Alpine.data('contactForm', contactForm);
Alpine.data('copyLink', copyLink);

/**
 * Whether the visitor has asked for reduced motion.
 *
 * Exposed as a global Alpine store so any component can honour it without
 * re-querying matchMedia.
 */
Alpine.store('motion', {
    reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,

    init() {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = (event) => {
            this.reduced = event.matches;
        };

        // addEventListener on MediaQueryList is unsupported in Safari < 14.
        if (typeof query.addEventListener === 'function') {
            query.addEventListener('change', update);
        } else if (typeof query.addListener === 'function') {
            query.addListener(update);
        }
    },
});

window.Alpine = Alpine;

Alpine.start();
