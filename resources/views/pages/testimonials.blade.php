<x-layouts.app :seo="$seo" :schema="$schema">
    <x-sections.page-header
        :title="__('testimonials.heading')"
        :subtitle="__('testimonials.subheading')"
        :breadcrumbs="$breadcrumbs"
    />

    <section class="section-spacing">
        <div class="container-page">
            @if ($testimonials->isEmpty())
                <x-ui.empty-state
                    :message="__('testimonials.empty')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            @else
                <div x-data="tabs({ initial: 'all' })">
                    @if (count($filters) > 1)
                        <div role="tablist" aria-label="{{ __('testimonials.filter_label') }}" class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                role="tab"
                                x-on:click="select('all')"
                                x-on:keydown.arrow-right.prevent="moveFocus(1, $event.target)"
                                x-on:keydown.arrow-left.prevent="moveFocus(-1, $event.target)"
                                :aria-selected="isActive('all') ? 'true' : 'false'"
                                :tabindex="isActive('all') ? 0 : -1"
                                :class="isActive('all') ? 'bg-primary-800 text-white border-primary-800' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100'"
                                class="min-h-touch rounded-full border px-4 text-sm font-medium transition-colors"
                            >{{ __('testimonials.filter_all') }}</button>

                            @foreach ($filters as $slug => $label)
                                <button
                                    type="button"
                                    role="tab"
                                    x-on:click="select(@js($slug))"
                                    x-on:keydown.arrow-right.prevent="moveFocus(1, $event.target)"
                                    x-on:keydown.arrow-left.prevent="moveFocus(-1, $event.target)"
                                    :aria-selected="isActive(@js($slug)) ? 'true' : 'false'"
                                    :tabindex="isActive(@js($slug)) ? 0 : -1"
                                    :class="isActive(@js($slug)) ? 'bg-primary-800 text-white border-primary-800' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100'"
                                    class="min-h-touch rounded-full border px-4 text-sm font-medium transition-colors"
                                >{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Masonry-style columns; each card stays whole. --}}
                    <div class="mt-8 gap-6 sm:columns-2 lg:columns-3">
                        @foreach ($testimonials as $testimonial)
                            <div
                                class="mb-6 break-inside-avoid"
                                x-show="matches(@js($testimonial->serviceUsed ?? 'none'))"
                            >
                                <x-sections.testimonial-card :testimonial="$testimonial" />
                            </div>
                        @endforeach
                    </div>

                    <div
                        x-show="visibleCount(@js($testimonials->map(fn ($t) => $t->serviceUsed ?? 'none')->all())) === 0"
                        x-cloak
                    >
                        <x-ui.empty-state :message="__('testimonials.empty_filtered')">
                            <div class="mt-6">
                                <x-ui.button variant="outline" size="md" x-on:click="select('all')">
                                    {{ __('testimonials.clear_filter') }}
                                </x-ui.button>
                            </div>
                        </x-ui.empty-state>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <x-sections.cta-banner
        :heading="__('testimonials.cta.heading')"
        :body="__('testimonials.cta.body')"
        :button-label="__('testimonials.cta.button')"
        :button-href="route('contact')"
    />
</x-layouts.app>
