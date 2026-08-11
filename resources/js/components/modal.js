/**
 * Accessible modal dialog.
 *
 * Behaviour:
 *   - focus is trapped inside the dialog while it is open (via x-trap);
 *   - focus returns to the element that opened it on close;
 *   - Escape closes;
 *   - background scrolling is locked while open;
 *   - the backdrop is click-to-close but the panel is not.
 *
 * @param {{ open?: boolean }} options
 */
export default function modal(options = {}) {
    return {
        open: options.open ?? false,
        previouslyFocused: null,

        init() {
            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    this.lockScroll();
                } else {
                    this.unlockScroll();
                    this.restoreFocus();
                }
            });

            // Ensure the scroll lock is not left behind if the element is
            // removed from the DOM while the dialog is open.
            this.$root.addEventListener('alpine:destroyed', () => this.unlockScroll());
        },

        show() {
            if (this.open) {
                return;
            }

            this.previouslyFocused = document.activeElement;
            this.open = true;
        },

        close() {
            this.open = false;
        },

        restoreFocus() {
            const target = this.previouslyFocused;
            this.previouslyFocused = null;

            if (target && typeof target.focus === 'function' && document.contains(target)) {
                target.focus();
            }
        },

        lockScroll() {
            // Compensate for the scrollbar so the page does not shift.
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.body.style.overflow = 'hidden';

            if (scrollbarWidth > 0) {
                document.body.style.paddingRight = `${scrollbarWidth}px`;
            }
        },

        unlockScroll() {
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        },
    };
}
