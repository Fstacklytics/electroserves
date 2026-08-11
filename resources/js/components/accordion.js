/**
 * Accessible accordion.
 *
 * Follows the WAI-ARIA accordion pattern:
 *   Enter / Space  toggle the focused header
 *   ArrowDown/Up   move between headers (wrapping)
 *   Home / End     first / last header
 *
 * @param {{ multiple?: boolean, initial?: string|null }} options
 */
export default function accordion(options = {}) {
    return {
        multiple: options.multiple ?? true,
        openPanels: options.initial ? [options.initial] : [],

        isOpen(id) {
            return this.openPanels.includes(id);
        },

        toggle(id) {
            if (this.isOpen(id)) {
                this.openPanels = this.openPanels.filter((panel) => panel !== id);

                return;
            }

            this.openPanels = this.multiple ? [...this.openPanels, id] : [id];
        },

        open(id) {
            if (!this.isOpen(id)) {
                this.toggle(id);
            }
        },

        closeAll() {
            this.openPanels = [];
        },

        get headers() {
            return Array.from(this.$root.querySelectorAll('[data-accordion-header]'));
        },

        moveFocus(direction, current) {
            const headers = this.headers;

            if (headers.length === 0) {
                return;
            }

            const index = headers.indexOf(current);

            if (index === -1) {
                return;
            }

            const next = (index + direction + headers.length) % headers.length;
            headers[next].focus();
        },

        focusFirst() {
            this.headers[0]?.focus();
        },

        focusLast() {
            const headers = this.headers;
            headers[headers.length - 1]?.focus();
        },
    };
}
