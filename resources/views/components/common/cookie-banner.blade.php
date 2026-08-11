{{--
    Cookie / privacy notice.

    Per docs/phase-0/05-pii-classification.md the site sets no tracking cookies
    at launch — only the session cookie required for CSRF protection, which is
    strictly necessary and needs no consent under the Tanzania PDPA or GDPR.

    This banner is therefore an *informational notice*, not a consent gate: it
    does not block content, and dismissing it does not enable anything. The
    dismissal is remembered in localStorage rather than a cookie, so
    acknowledging the notice does not itself create the thing it describes.

    If analytics are added later, this component becomes the consent gate and
    must gain explicit accept/reject controls before any script loads.
--}}
<div
    x-data="{
        shown: false,
        storageKey: 'electroserves.privacy-notice.acknowledged',

        init() {
            // localStorage throws in some privacy modes; treat any failure as
            // 'not yet acknowledged' rather than breaking the page.
            try {
                this.shown = window.localStorage.getItem(this.storageKey) !== 'true';
            } catch (error) {
                this.shown = true;
            }
        },

        dismiss() {
            this.shown = false;

            try {
                window.localStorage.setItem(this.storageKey, 'true');
            } catch (error) {
                // Storage unavailable — the notice simply reappears next visit,
                // which is acceptable and better than a broken control.
            }
        },
    }"
    x-show="shown"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="no-print fixed inset-x-0 bottom-0 z-40 border-t border-neutral-200 bg-white shadow-lg"
    role="region"
    aria-label="{{ __('common.cookies.label') }}"
>
    <div class="container-page flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm leading-relaxed text-neutral-700">
            {{ __('common.cookies.message') }}
            <a
                href="{{ route('privacy') }}"
                class="font-medium text-primary-800 underline underline-offset-2 hover:text-primary-900"
            >{{ __('common.cookies.learn_more') }}</a>
        </p>

        <div class="shrink-0">
            <x-ui.button variant="primary" size="sm" x-on:click="dismiss()">
                {{ __('common.cookies.dismiss') }}
            </x-ui.button>
        </div>
    </div>
</div>
