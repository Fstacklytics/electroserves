/**
 * Accessible dropdown / disclosure menu.
 *
 * Keyboard contract:
 *   Enter / Space  toggle
 *   Escape         close and return focus to the trigger
 *   ArrowDown/Up   move between items (wrapping)
 *   Home / End     first / last item
 *   Tab away       close
 *
 * @param {{ closeOnSelect?: boolean }} options
 */
export default function dropdown(options = {}) {
    return {
        open: false,
        closeOnSelect: options.closeOnSelect ?? true,
        activeIndex: -1,

        init() {
            // Close when focus leaves the whole component (mouse or keyboard).
            this.$root.addEventListener('focusout', (event) => {
                if (!this.open) {
                    return;
                }

                if (!this.$root.contains(event.relatedTarget)) {
                    this.open = false;
                    this.activeIndex = -1;
                }
            });
        },

        get items() {
            return Array.from(
                this.$refs.menu?.querySelectorAll('[role="menuitem"], a, button') ?? []
            ).filter((element) => !element.hasAttribute('disabled'));
        },

        toggle() {
            this.open ? this.close() : this.show();
        },

        show() {
            this.open = true;
            this.activeIndex = -1;
        },

        close(returnFocus = false) {
            if (!this.open) {
                return;
            }

            this.open = false;
            this.activeIndex = -1;

            if (returnFocus) {
                this.$refs.trigger?.focus();
            }
        },

        onSelect() {
            if (this.closeOnSelect) {
                this.close();
            }
        },

        focusNext() {
            if (!this.open) {
                this.show();
            }

            const items = this.items;

            if (items.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + 1) % items.length;
            items[this.activeIndex]?.focus();
        },

        focusPrevious() {
            if (!this.open) {
                this.show();
            }

            const items = this.items;

            if (items.length === 0) {
                return;
            }

            this.activeIndex = this.activeIndex <= 0 ? items.length - 1 : this.activeIndex - 1;
            items[this.activeIndex]?.focus();
        },

        focusFirst() {
            const items = this.items;
            this.activeIndex = 0;
            items[0]?.focus();
        },

        focusLast() {
            const items = this.items;
            this.activeIndex = items.length - 1;
            items[this.activeIndex]?.focus();
        },

        /** Bindings for the trigger button. */
        triggerAttrs() {
            return {
                'x-ref': 'trigger',
                '@click': 'toggle()',
                '@keydown.arrow-down.prevent': 'focusNext()',
                '@keydown.arrow-up.prevent': 'focusPrevious()',
                '@keydown.escape.prevent': 'close(true)',
                ':aria-expanded': 'open ? "true" : "false"',
                'aria-haspopup': 'true',
            };
        },
    };
}
