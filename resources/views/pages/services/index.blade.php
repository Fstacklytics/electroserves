<x-layouts.app :seo="$seo" :schema="$schema">
    <x-sections.page-header
        :title="__('services.index.heading')"
        :subtitle="__('services.index.subheading')"
        :breadcrumbs="$breadcrumbs"
    />

    <section class="section-spacing">
        <div class="container-page">
            @if ($services->isEmpty())
                <x-ui.empty-state
                    :message="__('services.index.empty_global')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            @else
                <div x-data="tabs({ initial: 'all' })">
                    {{-- Category filter. Works as a tab list; without JS every card stays visible. --}}
                    @if (count($categories) > 1)
                        <div
                            role="tablist"
                            aria-label="{{ __('services.index.filter_label') }}"
                            class="flex flex-wrap gap-2"
                        >
                            <button
                                type="button"
                                role="tab"
                                x-on:click="select('all')"
                                x-on:keydown.arrow-right.prevent="moveFocus(1, $event.target)"
                                x-on:keydown.arrow-left.prevent="moveFocus(-1, $event.target)"
                                x-on:keydown.home.prevent="focusFirst()"
                                x-on:keydown.end.prevent="focusLast()"
                                :aria-selected="isActive('all') ? 'true' : 'false'"
                                :tabindex="isActive('all') ? 0 : -1"
                                :class="isActive('all')
                                    ? 'bg-primary-800 text-white border-primary-800'
                                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100'"
                                class="min-h-touch rounded-full border px-4 text-sm font-medium transition-colors"
                            >{{ __('services.index.filter_all') }}</button>

                            @foreach ($categories as $key => $label)
                                <button
                                    type="button"
                                    role="tab"
                                    x-on:click="select(@js($key))"
                                    x-on:keydown.arrow-right.prevent="moveFocus(1, $event.target)"
                                    x-on:keydown.arrow-left.prevent="moveFocus(-1, $event.target)"
                                    x-on:keydown.home.prevent="focusFirst()"
                                    x-on:keydown.end.prevent="focusLast()"
                                    :aria-selected="isActive(@js($key)) ? 'true' : 'false'"
                                    :tabindex="isActive(@js($key)) ? 0 : -1"
                                    :class="isActive(@js($key))
                                        ? 'bg-primary-800 text-white border-primary-800'
                                        : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100'"
                                    class="min-h-touch rounded-full border px-4 text-sm font-medium transition-colors"
                                >{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Result count, announced when the filter changes. --}}
                    <p class="mt-5 text-sm text-neutral-500" aria-live="polite">
                        <span x-text="visibleCount(@js($services->pluck('category')->all()))">{{ $services->count() }}</span>
                        / {{ $services->count() }}
                    </p>

                    <ul role="list" class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($services as $service)
                            <li class="h-full" x-show="matches(@js($service->category))">
                                <x-sections.service-card :service="$service" />
                            </li>
                        @endforeach
                    </ul>

                    {{-- Empty state for a filter that matches nothing. --}}
                    <div
                        x-show="visibleCount(@js($services->pluck('category')->all())) === 0"
                        x-cloak
                        class="mt-6"
                    >
                        <x-ui.empty-state :message="__('services.index.empty_filtered')">
                            <div class="mt-6">
                                <x-ui.button variant="outline" size="md" x-on:click="select('all')">
                                    {{ __('services.index.clear_filter') }}
                                </x-ui.button>
                            </div>
                        </x-ui.empty-state>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <x-sections.cta-banner
        :heading="__('home.cta.heading')"
        :body="__('home.cta.body')"
        :button-label="__('home.cta.button')"
        :button-href="route('contact')"
    />
</x-layouts.app>
