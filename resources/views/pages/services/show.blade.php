<x-layouts.app :seo="$seo" :schema="$schema">
    @php $categories = (array) config('electroserves.service_categories', []); @endphp

    <div class="border-b border-neutral-200 bg-neutral-50">
        <div class="container-page">
            <x-common.breadcrumb :crumbs="$breadcrumbs" />
        </div>
    </div>

    <article>
        <header class="bg-neutral-50 pb-12">
            <div class="container-page">
                <div class="grid items-center gap-10 lg:grid-cols-2">
                    <div>
                        @if (isset($categories[$service->category]))
                            <x-ui.badge variant="primary">{{ $categories[$service->category] }}</x-ui.badge>
                        @endif

                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">{{ $service->title }}</h1>
                        <p class="mt-4 text-lg leading-relaxed text-neutral-600">{{ $service->shortDescription }}</p>

                        @if ($service->priceRange)
                            <p class="mt-5 text-sm">
                                <span class="font-semibold text-neutral-900">{{ __('services.show.price_from') }}:</span>
                                <span class="text-neutral-700">{{ $service->priceRange }}</span>
                            </p>
                        @endif

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <x-ui.button
                                :href="route('contact', ['service' => $service->slug])"
                                variant="primary"
                                size="lg"
                            >{{ __('services.show.request_quote', ['service' => $service->title]) }}</x-ui.button>
                        </div>
                    </div>

                    <div>
                        <x-ui.media
                            :src="$service->image"
                            :alt="$service->title"
                            :width="720"
                            :height="540"
                            eager
                            class="w-full rounded-lg object-cover shadow-md"
                        />
                    </div>
                </div>
            </div>
        </header>

        <div class="section-spacing">
            <div class="container-page">
                <div class="grid gap-12 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        @if ($bodyHtml !== '')
                            {{-- Markdown is rendered with raw HTML stripped by MarkdownService. --}}
                            <div class="prose prose-neutral max-w-none">{!! $bodyHtml !!}</div>
                        @else
                            <x-ui.empty-state
                                :message="__('services.show.no_description')"
                                :action-label="__('common.cta.contact_us')"
                                :action-href="route('contact')"
                                :icon="false"
                            />
                        @endif
                    </div>

                    {{-- Features --}}
                    <aside class="lg:col-span-1">
                        @if ($service->hasFeatures())
                            <x-ui.card padding="md" class="lg:sticky lg:top-28">
                                <h2 class="text-lg font-semibold text-neutral-900">{{ __('services.show.whats_included') }}</h2>
                                <ul role="list" class="mt-4 space-y-3">
                                    @foreach ($service->features as $feature)
                                        <li class="flex items-start gap-2.5 text-sm text-neutral-700">
                                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-success-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-6">
                                    <x-ui.button
                                        :href="route('contact', ['service' => $service->slug])"
                                        variant="primary"
                                        size="md"
                                        class="w-full"
                                    >{{ __('common.cta.get_quote') }}</x-ui.button>
                                </div>
                            </x-ui.card>
                        @endif
                    </aside>
                </div>
            </div>
        </div>

        {{-- Related projects --}}
        <section class="section-spacing bg-neutral-50" aria-labelledby="related-projects-heading">
            <div class="container-page">
                <h2 id="related-projects-heading" class="text-2xl font-bold tracking-tight text-neutral-900">
                    {{ __('services.show.related_projects') }}
                </h2>

                @if ($relatedProjects->isEmpty())
                    <div class="mt-6">
                        <x-ui.empty-state
                            :message="__('services.show.related_projects_empty')"
                            :action-label="__('projects.index.title')"
                            :action-href="route('projects.index')"
                            :icon="false"
                        />
                    </div>
                @else
                    <ul role="list" class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($relatedProjects as $project)
                            <li class="h-full"><x-sections.project-card :project="$project" /></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </article>

    <x-sections.cta-banner
        :heading="__('services.show.request_quote', ['service' => $service->title])"
        :body="__('services.show.cta_body')"
        :button-label="__('common.cta.get_quote')"
        :button-href="route('contact', ['service' => $service->slug])"
    />
</x-layouts.app>
