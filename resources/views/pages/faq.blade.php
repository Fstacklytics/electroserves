<x-layouts.app :seo="$seo" :schema="$schema">
    <x-sections.page-header
        :title="__('faq.heading')"
        :subtitle="__('faq.subheading')"
        :breadcrumbs="$breadcrumbs"
    />

    <section class="section-spacing">
        <div class="container-page">
            @if ($groupedFaqs->isEmpty())
                <x-ui.empty-state
                    :message="__('faq.empty')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            @else
                <div class="mx-auto max-w-3xl space-y-10">
                    @foreach ($groupedFaqs as $category => $faqs)
                        <section aria-labelledby="faq-group-{{ \Illuminate\Support\Str::slug($category) }}">
                            <h2
                                id="faq-group-{{ \Illuminate\Support\Str::slug($category) }}"
                                class="text-xl font-bold text-neutral-900"
                            >{{ $category }}</h2>

                            {{--
                                Accordion following the WAI-ARIA pattern: each header is a
                                button with aria-expanded and aria-controls, and Arrow keys
                                move between headers.
                            --}}
                            <div x-data="accordion({ multiple: true })" class="mt-4 divide-y divide-neutral-200 rounded-lg border border-neutral-200 bg-white">
                                @foreach ($faqs as $faq)
                                    @php $id = $faq->anchorId(); @endphp
                                    <div>
                                        <h3>
                                            <button
                                                type="button"
                                                data-accordion-header
                                                id="{{ $id }}-header"
                                                aria-controls="{{ $id }}-panel"
                                                :aria-expanded="isOpen(@js($id)) ? 'true' : 'false'"
                                                x-on:click="toggle(@js($id))"
                                                x-on:keydown.arrow-down.prevent="moveFocus(1, $event.target)"
                                                x-on:keydown.arrow-up.prevent="moveFocus(-1, $event.target)"
                                                x-on:keydown.home.prevent="focusFirst()"
                                                x-on:keydown.end.prevent="focusLast()"
                                                class="flex w-full min-h-touch items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-neutral-50"
                                            >
                                                <span class="text-base font-medium text-neutral-900">{{ $faq->question }}</span>
                                                <svg
                                                    class="h-5 w-5 shrink-0 text-neutral-500 transition-transform duration-200"
                                                    :class="isOpen(@js($id)) ? 'rotate-180' : ''"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                    focusable="false"
                                                >
                                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </h3>

                                        <div
                                            id="{{ $id }}-panel"
                                            role="region"
                                            aria-labelledby="{{ $id }}-header"
                                            x-show="isOpen(@js($id))"
                                            x-cloak
                                            x-collapse
                                        >
                                            <div class="prose prose-sm prose-neutral max-w-none px-5 pb-5">
                                                {!! $answers[$id] ?? '' !!}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-sections.cta-banner
        :heading="__('faq.cta.heading')"
        :body="__('faq.cta.body')"
        :button-label="__('faq.cta.button')"
        :button-href="route('contact')"
    />
</x-layouts.app>
