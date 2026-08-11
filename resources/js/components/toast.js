/**
 * Toast notification region.
 *
 * The container is an aria-live region so screen readers announce messages as
 * they arrive. Toasts are dismissible and auto-expire, but the timer pauses
 * while the toast has focus or the pointer is over it, so a keyboard user is
 * never racing a countdown.
 *
 * Trigger from anywhere with:
 *   window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
 */
export default function toast() {
    return {
        messages: [],
        nextId: 1,

        init() {
            window.addEventListener('toast', (event) => {
                const detail = event.detail ?? {};

                this.push(detail.message ?? '', detail.type ?? 'info', detail.timeout ?? 5000);
            });
        },

        push(message, type = 'info', timeout = 5000) {
            if (!message) {
                return;
            }

            const id = this.nextId++;

            this.messages.push({ id, message, type, timeout, paused: false });

            if (timeout > 0) {
                this.scheduleRemoval(id, timeout);
            }
        },

        scheduleRemoval(id, timeout) {
            window.setTimeout(() => {
                const entry = this.messages.find((item) => item.id === id);

                if (!entry) {
                    return;
                }

                // If the visitor is interacting with it, check again shortly
                // rather than pulling it out from under them.
                if (entry.paused) {
                    this.scheduleRemoval(id, 1000);

                    return;
                }

                this.dismiss(id);
            }, timeout);
        },

        pause(id) {
            const entry = this.messages.find((item) => item.id === id);

            if (entry) {
                entry.paused = true;
            }
        },

        resume(id) {
            const entry = this.messages.find((item) => item.id === id);

            if (entry) {
                entry.paused = false;
            }
        },

        dismiss(id) {
            this.messages = this.messages.filter((item) => item.id !== id);
        },

        /** ARIA role: assertive for errors, polite for everything else. */
        roleFor(type) {
            return type === 'error' || type === 'warning' ? 'alert' : 'status';
        },
    };
}
