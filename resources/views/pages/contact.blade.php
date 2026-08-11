<x-layouts.app :seo="$seo" :schema="$schema">
    @php
        $status = session('contact_status');
        // Pre-select the service when arriving from a service detail CTA.
        $preselectedService = request()->query('service');
        $preselectedService = is_string($preselectedService) && array_key_exists($preselectedService, $serviceTypes)
            ? $preselectedService
            : null;
    @endphp

    <x-sections.page-header
        :title="__('contact.heading')"
        :subtitle="__('contact.subheading')"
        :breadcrumbs="$breadcrumbs"
    />

    <section class="section-spacing">
        <div class="container-page">
            <div class="grid gap-10 lg:grid-cols-5">
                {{-- Form --}}
                <div class="lg:col-span-3">
                    @if ($status === 'success')
                        <x-ui.alert type="success" :title="__('contact.status.success_title')" class="mb-6">
                            <p>{{ __('contact.status.success_body') }}</p>
                            <p class="mt-3">
                                <a href="{{ route('contact') }}" class="font-semibold underline underline-offset-2">
                                    {{ __('contact.form.send_another') }}
                                </a>
                            </p>
                        </x-ui.alert>
                    @elseif ($status === 'error')
                        <x-ui.alert type="error" :title="__('contact.status.error_title')" class="mb-6">
                            {{ $siteSettings->phone !== ''
                                ? __('contact.status.error_body', ['phone' => $siteSettings->phone])
                                : __('contact.status.error_body_no_phone') }}
                        </x-ui.alert>
                    @elseif ($status === 'rate_limited')
                        <x-ui.alert type="warning" :title="__('contact.status.rate_limited_title')" class="mb-6">
                            {{ $siteSettings->phone !== ''
                                ? __('contact.status.rate_limited_body', ['phone' => $siteSettings->phone])
                                : __('contact.status.rate_limited_body_no_phone') }}
                        </x-ui.alert>
                    @endif

                    {{-- Server-side validation summary, focusable so it can be jumped to. --}}
                    @if ($errors->any())
                        <x-ui.alert type="error" :title="__('contact.validation.summary_heading')" class="mb-6" tabindex="-1" id="form-errors">
                            <ul role="list" class="list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif

                    <x-ui.card padding="lg">
                        <h2 class="text-xl font-bold text-neutral-900">{{ __('contact.form.heading') }}</h2>
                        <p class="mt-1 text-sm text-neutral-500">{{ __('contact.form.required_note') }}</p>

                        <form
                            method="POST"
                            action="{{ route('contact.store') }}"
                            class="mt-6 space-y-5"
                            x-data="contactForm({
                                maxMessage: 5000,
                                messages: {
                                    nameRequired: @js(__('contact.validation.name_required')),
                                    nameMin: @js(__('contact.validation.name_min')),
                                    emailRequired: @js(__('contact.validation.email_required')),
                                    emailInvalid: @js(__('contact.validation.email_invalid')),
                                    phoneInvalid: @js(__('contact.validation.phone_invalid')),
                                    serviceRequired: @js(__('contact.validation.service_required')),
                                    messageRequired: @js(__('contact.validation.message_required')),
                                    messageMin: @js(__('contact.validation.message_min')),
                                    consentRequired: @js(__('contact.validation.consent_required'))
                                }
                            })"
                            x-on:submit="onSubmit($event)"
                            novalidate
                        >
                            @csrf

                            {{--
                                Honeypot. Hidden from sighted users and from assistive
                                technology, and removed from the tab order — only an
                                automated client will fill it in.
                            --}}
                            <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                                <label for="{{ $honeypotField }}">Leave this field empty</label>
                                <input
                                    type="text"
                                    id="{{ $honeypotField }}"
                                    name="{{ $honeypotField }}"
                                    tabindex="-1"
                                    autocomplete="off"
                                    value=""
                                >
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-ui.input
                                    name="name"
                                    :label="__('contact.fields.name')"
                                    :placeholder="__('contact.fields.name_placeholder')"
                                    autocomplete="name"
                                    maxlength="120"
                                    required
                                    x-on:blur="markTouched('name'); validateField('name', $event.target.value)"
                                    x-on:input="touched.name && validateField('name', $event.target.value)"
                                />

                                <x-ui.input
                                    name="email"
                                    type="email"
                                    :label="__('contact.fields.email')"
                                    :placeholder="__('contact.fields.email_placeholder')"
                                    autocomplete="email"
                                    inputmode="email"
                                    maxlength="180"
                                    required
                                    x-on:blur="markTouched('email'); validateField('email', $event.target.value)"
                                    x-on:input="touched.email && validateField('email', $event.target.value)"
                                />
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-ui.input
                                    name="phone"
                                    type="tel"
                                    :label="__('contact.fields.phone')"
                                    :placeholder="__('contact.fields.phone_placeholder')"
                                    :help-text="__('contact.fields.phone_help')"
                                    autocomplete="tel"
                                    inputmode="tel"
                                    maxlength="32"
                                    x-on:blur="markTouched('phone'); validateField('phone', $event.target.value)"
                                />

                                <x-ui.select
                                    name="service_type"
                                    :label="__('contact.fields.service_type')"
                                    :options="$serviceTypes"
                                    :value="$preselectedService"
                                    :placeholder="__('contact.fields.service_placeholder')"
                                    required
                                    x-on:change="markTouched('service_type'); validateField('service_type', $event.target.value)"
                                />
                            </div>

                            <div>
                                <x-ui.textarea
                                    name="message"
                                    :label="__('contact.fields.message')"
                                    :placeholder="__('contact.fields.message_placeholder')"
                                    :help-text="__('contact.fields.message_help')"
                                    :rows="6"
                                    maxlength="5000"
                                    required
                                    x-ref="message"
                                    x-on:input="onMessageInput($event)"
                                    x-on:blur="markTouched('message'); validateField('message', $event.target.value)"
                                />
                                {{-- Character budget, announced politely as it changes. --}}
                                <p class="mt-1 text-right text-xs text-neutral-500" aria-live="polite">
                                    <span x-text="remaining"></span> / {{ number_format(5000) }}
                                </p>
                            </div>

                            {{-- Consent (Tanzania PDPA / GDPR: purpose limitation) --}}
                            <x-ui.checkbox
                                name="consent"
                                :label="__('contact.fields.consent')"
                                id="consent"
                                required
                            >
                            </x-ui.checkbox>
                            <p class="-mt-3 pl-8 text-sm text-neutral-500">
                                <a href="{{ route('privacy') }}" class="text-primary-800 underline underline-offset-2">
                                    {{ __('contact.fields.consent_link') }}
                                </a>
                            </p>

                            <div class="pt-2">
                                {{--
                                    The button reflects the in-flight state: it is disabled
                                    and announces aria-busy while the browser submits, so a
                                    double submission is not possible.
                                --}}
                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    :aria-busy="submitting ? 'true' : 'false'"
                                    class="relative inline-flex min-h-touch w-full items-center justify-center gap-2 rounded-md bg-primary-800 px-6 py-3 text-base font-semibold text-white shadow-sm transition-colors hover:bg-primary-900 active:bg-primary-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                >
                                    <span
                                        x-show="submitting"
                                        x-cloak
                                        class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent"
                                        aria-hidden="true"
                                    ></span>
                                    <span x-text="submitting ? @js(__('contact.form.submitting')) : @js(__('contact.form.submit'))">{{ __('contact.form.submit') }}</span>
                                </button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>

                {{-- Contact details sidebar --}}
                <aside class="lg:col-span-2">
                    <x-ui.card padding="lg">
                        <h2 class="text-lg font-bold text-neutral-900">{{ __('contact.sidebar.heading') }}</h2>

                        <dl class="mt-5 space-y-5 text-sm">
                            @if ($settings->phoneHref())
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.5 11.5 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-7.18 0-13-5.82-13-13V3.5Z" />
                                    </svg>
                                    <div>
                                        <dt class="font-semibold text-neutral-900">{{ __('contact.sidebar.phone_label') }}</dt>
                                        <dd><a href="{{ $settings->phoneHref() }}" class="text-primary-800 hover:underline">{{ $settings->phone }}</a></dd>
                                    </div>
                                </div>
                            @endif

                            @if ($settings->emergencyPhoneHref())
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-danger-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path d="M11.983 1.907a.75.75 0 0 0-1.292-.657l-6.5 8.5A.75.75 0 0 0 4.75 11h3.925l-.658 5.093a.75.75 0 0 0 1.292.657l6.5-8.5A.75.75 0 0 0 15.25 7h-3.925l.658-5.093Z" />
                                    </svg>
                                    <div>
                                        <dt class="font-semibold text-neutral-900">{{ __('contact.sidebar.emergency_label') }}</dt>
                                        <dd><a href="{{ $settings->emergencyPhoneHref() }}" class="text-danger-700 hover:underline">{{ $settings->emergencyPhone }}</a></dd>
                                    </div>
                                </div>
                            @endif

                            @if ($settings->email !== '')
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path d="M3 4a2 2 0 0 0-2 2v.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 6.161V6a2 2 0 0 0-2-2H3Z" />
                                        <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                                    </svg>
                                    <div class="min-w-0">
                                        <dt class="font-semibold text-neutral-900">{{ __('contact.sidebar.email_label') }}</dt>
                                        <dd><a href="mailto:{{ $settings->email }}" class="break-all text-primary-800 hover:underline">{{ $settings->email }}</a></dd>
                                    </div>
                                </div>
                            @endif

                            @if ($settings->fullAddress() !== '')
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path fill-rule="evenodd" d="M9.69 18.933A7.5 7.5 0 0 0 10 19a7.5 7.5 0 0 0 .31-.067C13.02 17.79 16 14.2 16 10a6 6 0 1 0-12 0c0 4.2 2.98 7.79 5.69 8.933ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <dt class="font-semibold text-neutral-900">{{ __('contact.sidebar.address_label') }}</dt>
                                        <dd><address class="not-italic text-neutral-600">{{ $settings->fullAddress() }}</address></dd>
                                    </div>
                                </div>
                            @endif

                            @if ($settings->hasBusinessHours())
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="min-w-0 flex-1">
                                        <dt class="font-semibold text-neutral-900">{{ __('contact.sidebar.hours_label') }}</dt>
                                        <dd class="mt-1 space-y-0.5 text-neutral-600">
                                            @foreach ($settings->businessHours as $row)
                                                <div class="flex justify-between gap-3">
                                                    <span>{{ $row['day'] }}</span>
                                                    <span class="text-right">{{ $row['hours'] }}</span>
                                                </div>
                                            @endforeach
                                        </dd>
                                    </div>
                                </div>
                            @endif
                        </dl>
                    </x-ui.card>

                    {{-- Map --}}
                    @if ($settings->mapEmbedUrl)
                        <div class="mt-6 overflow-hidden rounded-lg border border-neutral-200">
                            <iframe
                                src="{{ $settings->mapEmbedUrl }}"
                                title="{{ __('contact.sidebar.map_title') }}"
                                width="100%"
                                height="320"
                                style="border:0"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
