/**
 * "Copy link" button for blog post sharing.
 *
 * navigator.clipboard is only exposed in a secure context (HTTPS or localhost),
 * so it is missing on a plain-HTTP staging host and in a few older targets in
 * our support matrix. Three layers are tried in order:
 *
 *   1. navigator.clipboard.writeText  — the modern path.
 *   2. document.execCommand('copy')   — deprecated but still implemented
 *                                       everywhere we support, and it works on
 *                                       insecure origins.
 *   3. An explicit failure message    — never a silent no-op, and never a
 *                                       success state that did not happen.
 *
 * @param {{ url?: string, copiedLabel?: string, failedLabel?: string }} options
 */
export default function copyLink(options = {}) {
    return {
        url: options.url ?? window.location.href,
        status: 'idle',
        message: '',
        copiedLabel: options.copiedLabel ?? 'Link copied',
        failedLabel: options.failedLabel ?? 'Could not copy the link',

        async copy() {
            this.status = 'loading';

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(this.url);
                } else if (!this.copyUsingFallback()) {
                    throw new Error('No clipboard mechanism available');
                }

                this.status = 'success';
                this.message = this.copiedLabel;
            } catch (error) {
                this.status = 'error';
                this.message = this.failedLabel;
            }

            window.setTimeout(() => {
                this.status = 'idle';
                this.message = '';
            }, 4000);
        },

        /**
         * Insecure-context fallback using a temporary off-screen textarea.
         *
         * The element is positioned off-screen rather than hidden with
         * `display: none`, because a hidden element cannot be selected. It is
         * removed again in a `finally` block so a thrown exception cannot leave
         * stray nodes in the document.
         *
         * @returns {boolean} whether the copy actually succeeded
         */
        copyUsingFallback() {
            if (typeof document.execCommand !== 'function') {
                return false;
            }

            const field = document.createElement('textarea');

            field.value = this.url;
            field.setAttribute('readonly', '');
            field.setAttribute('aria-hidden', 'true');
            field.style.position = 'fixed';
            field.style.top = '-9999px';
            field.style.opacity = '0';

            document.body.appendChild(field);

            // Restore focus afterwards so the keyboard user is not dumped at
            // the top of the document.
            const previouslyFocused = document.activeElement;

            try {
                field.select();
                field.setSelectionRange(0, field.value.length);

                return document.execCommand('copy');
            } catch (error) {
                return false;
            } finally {
                document.body.removeChild(field);

                if (previouslyFocused instanceof HTMLElement) {
                    previouslyFocused.focus();
                }
            }
        },
    };
}
