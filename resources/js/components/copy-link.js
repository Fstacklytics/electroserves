/**
 * "Copy link" button for blog post sharing.
 *
 * The Clipboard API is unavailable on insecure origins and in some browsers,
 * so the failure path is handled explicitly: the button reports that copying
 * failed rather than appearing to succeed.
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
                if (!navigator.clipboard?.writeText) {
                    throw new Error('Clipboard API unavailable');
                }

                await navigator.clipboard.writeText(this.url);

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
    };
}
