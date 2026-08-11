/**
 * Accessible tab list, also used for the category filters.
 *
 * Implements the WAI-ARIA tabs pattern with manual activation:
 *   ArrowRight/Left  move focus between tabs (wrapping)
 *   Home / End       first / last tab
 *   Enter / Space    activate the focused tab
 *
 * When `queryParam` is provided, filter changes are reflected in the current
 * URL and in paginator links. Filtering remains deliberately scoped to the
 * items rendered on the current server-paginated page.
 *
 * @param {{ initial?: string, tabs?: string[], queryParam?: string }} options
 */
export default function tabs(options = {}) {
    return {
        active: options.initial ?? 'all',
        tabList: options.tabs ?? [],
        queryParam: options.queryParam ?? null,

        isActive(id) {
            return this.active === id;
        },

        select(id) {
            this.active = id;
            this.syncFilterQuery();
        },

        /**
         * Persist the active category without a navigation, then apply it to
         * every available page link so the filter survives pagination.
         */
        syncFilterQuery() {
            if (!this.queryParam || typeof window === 'undefined') {
                return;
            }

            const pageUrl = new URL(window.location.href);
            this.setFilterQuery(pageUrl);
            window.history.replaceState({}, '', pageUrl.toString());

            this.$root.querySelectorAll('[data-pagination-link]').forEach((link) => {
                const linkUrl = new URL(link.href, window.location.origin);
                this.setFilterQuery(linkUrl);
                link.href = linkUrl.toString();
            });
        },

        setFilterQuery(url) {
            if (this.active === 'all') {
                url.searchParams.delete(this.queryParam);

                return;
            }

            url.searchParams.set(this.queryParam, this.active);
        },

        get tabElements() {
            return Array.from(this.$root.querySelectorAll('[role="tab"]'));
        },

        moveFocus(direction, current) {
            const elements = this.tabElements;

            if (elements.length === 0) {
                return;
            }

            const index = elements.indexOf(current);

            if (index === -1) {
                return;
            }

            const next = (index + direction + elements.length) % elements.length;
            elements[next].focus();
        },

        focusFirst() {
            this.tabElements[0]?.focus();
        },

        focusLast() {
            const elements = this.tabElements;
            elements[elements.length - 1]?.focus();
        },

        /**
         * Whether an item tagged with `category` should be visible.
         * "all" is the reset state and always matches.
         */
        matches(category) {
            return this.active === 'all' || this.active === category;
        },

        /** Number of currently visible items, for the results count and empty state. */
        visibleCount(items) {
            if (this.active === 'all') {
                return items.length;
            }

            return items.filter((category) => category === this.active).length;
        },
    };
}
