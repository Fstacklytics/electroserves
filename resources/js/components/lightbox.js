/**
 * Accessible image lightbox for project galleries.
 *
 * Focus is trapped while open and returned to the thumbnail that opened it.
 * Arrow keys move between images, Escape closes.
 *
 * @param {{ total?: number }} options
 */
export default function lightbox(options = {}) {
    return {
        open: false,
        current: 0,
        total: options.total ?? 0,
        previouslyFocused: null,

        init() {
            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                    this.restoreFocus();
                }
            });
        },

        show(index) {
            if (this.total === 0) {
                return;
            }

            this.previouslyFocused = document.activeElement;
            this.current = Math.min(Math.max(index, 0), this.total - 1);
            this.open = true;
        },

        close() {
            this.open = false;
        },

        next() {
            if (this.total === 0) {
                return;
            }

            this.current = (this.current + 1) % this.total;
        },

        previous() {
            if (this.total === 0) {
                return;
            }

            this.current = (this.current - 1 + this.total) % this.total;
        },

        isActive(index) {
            return this.current === index;
        },

        restoreFocus() {
            const target = this.previouslyFocused;
            this.previouslyFocused = null;

            if (target && typeof target.focus === 'function' && document.contains(target)) {
                target.focus();
            }
        },
    };
}
