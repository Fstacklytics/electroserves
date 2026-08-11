/**
 * Client-side enhancement for the contact form.
 *
 * This is UX only: every rule here is also enforced server-side by
 * ContactFormRequest. If JavaScript is unavailable the form still submits and
 * validates normally.
 *
 * @param {{ messages?: Record<string, string>, maxMessage?: number }} options
 */
export default function contactForm(options = {}) {
    return {
        submitting: false,
        touched: {},
        errors: {},
        maxMessage: options.maxMessage ?? 5000,
        messageLength: 0,
        messages: options.messages ?? {},

        init() {
            this.messageLength = this.$refs.message?.value.length ?? 0;
        },

        get remaining() {
            return Math.max(0, this.maxMessage - this.messageLength);
        },

        onMessageInput(event) {
            this.messageLength = event.target.value.length;
            this.validateField('message', event.target.value);
        },

        markTouched(field) {
            this.touched[field] = true;
        },

        hasError(field) {
            return Boolean(this.touched[field] && this.errors[field]);
        },

        errorFor(field) {
            return this.errors[field] ?? '';
        },

        validateField(field, value) {
            const trimmed = typeof value === 'string' ? value.trim() : '';
            let error = '';

            if (field === 'name') {
                if (trimmed === '') {
                    error = this.messages.nameRequired ?? '';
                } else if (trimmed.length < 2) {
                    error = this.messages.nameMin ?? '';
                }
            }

            if (field === 'email') {
                if (trimmed === '') {
                    error = this.messages.emailRequired ?? '';
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(trimmed)) {
                    error = this.messages.emailInvalid ?? '';
                }
            }

            if (field === 'phone' && trimmed !== '' && !/^[0-9+][0-9+\-\s()]{5,31}$/.test(trimmed)) {
                error = this.messages.phoneInvalid ?? '';
            }

            if (field === 'service_type' && trimmed === '') {
                error = this.messages.serviceRequired ?? '';
            }

            if (field === 'message') {
                if (trimmed === '') {
                    error = this.messages.messageRequired ?? '';
                } else if (trimmed.length < 10) {
                    error = this.messages.messageMin ?? '';
                }
            }

            if (error === '') {
                delete this.errors[field];
            } else {
                this.errors[field] = error;
            }

            return error === '';
        },

        /**
         * Validate everything on submit. Returning false prevents submission
         * and moves focus to the first field with a problem.
         */
        onSubmit(event) {
            const form = event.target;
            const fields = ['name', 'email', 'phone', 'service_type', 'message'];
            let firstInvalid = null;

            fields.forEach((field) => {
                this.touched[field] = true;
                const element = form.elements[field];
                const valid = this.validateField(field, element ? element.value : '');

                if (!valid && firstInvalid === null) {
                    firstInvalid = element;
                }
            });

            const consent = form.elements.consent;

            if (consent && !consent.checked) {
                this.touched.consent = true;
                this.errors.consent = this.messages.consentRequired ?? '';

                if (firstInvalid === null) {
                    firstInvalid = consent;
                }
            } else {
                delete this.errors.consent;
            }

            if (firstInvalid !== null) {
                event.preventDefault();
                firstInvalid.focus();

                return false;
            }

            // Let the browser submit, but reflect progress in the button.
            this.submitting = true;

            return true;
        },
    };
}
