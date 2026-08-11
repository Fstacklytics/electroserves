<x-layouts.app :seo="$seo" :schema="$schema">
    <x-sections.page-header
        :title="__('projects.index.heading')"
        :subtitle="__('projects.index.subheading')"
        :breadcrumbs="$breadcrumbs"
    />

    <section class="section-spacing">
        <div class="container-page">
            @if ($projects->isEmpty())
                <x-ui.empty-state
                    :message="__('projects.index.empty_global')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            @else
                <div x-data="tabs({ initial: 'all' })">
                    @if (count($categories) > 1)
                        <div role="tablist" aria-label="{{ __('projects.index.filter_label') }}" class="flex flex-wrap gap-2">
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
                                :class="isActive('all') ? 'bg-primary-800 text-white border-primary-800' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100'"
                                class="min-h-touch rounded-full border px-4 text-sm font-medium transition-colors"
                            >{{ __('projects.index.filter_all') }}</button>

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
                                    :class="isActive(@js($key)) ? 'bg-primary-800 text-white border-primary-800' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100'"
                                    class="min-h-touch rounded-full border px-4 text-sm font-medium transition-colors"
                                >{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif

                    <p class="mt-5 text-sm text-neutral-500" aria-live="polite">
                        <span x-text="visibleCount(@js($projects->pluck('category')->all()))">{{ $projects->count() }}</span>
                        / {{ $projects->count() }}
                    </p>

                    <ul role="list" class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($projects as $project)
                            <li class="h-full" x-show="matches(@js($project->category))">
                                <x-sections.project-card :project="$project" />
                            </li>
                        @endforeach
                    </ul>

                    <div x-show="visibleCount(@js($projects->pluck('category')->all())) === 0" x-cloak class="mt-6">
                        <x-ui.empty-state :message="__('projects.index.empty_filtered')">
                            <div class="mt-6">
                                <x-ui.button variant="outline" size="md" x-on:click="select('all')">
                                    {{ __('projects.index.clear_filter') }}
                                </x-ui.button>
                            </div>
                        </x-ui.empty-state>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <x-sections.cta-banner
        :heading="__('projects.show.cta_heading')"
        :body="__('projects.show.cta_body')"
        :button-label="__('common.cta.get_quote')"
        :button-href="route('contact')"
    />
</x-layouts.app>
