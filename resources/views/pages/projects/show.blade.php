<x-layouts.app :seo="$seo" :schema="$schema">
    @php
        $categories = (array) config('electroserves.project_categories', []);
        $gallery = $project->gallery;
    @endphp

    <div class="border-b border-neutral-200 bg-neutral-50">
        <div class="container-page">
            <x-common.breadcrumb :crumbs="$breadcrumbs" />
        </div>
    </div>

    <article>
        <header class="bg-neutral-50 pb-10">
            <div class="container-page">
                @if (isset($categories[$project->category]))
                    <x-ui.badge variant="primary">{{ $categories[$project->category] }}</x-ui.badge>
                @endif

                <h1 class="mt-3 max-w-3xl text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">{{ $project->title }}</h1>
                <p class="mt-4 max-w-3xl text-lg leading-relaxed text-neutral-600">{{ $project->shortDescription }}</p>
            </div>
        </header>

        <div class="container-page -mt-2">
            <x-ui.media
                :src="$project->featuredImage"
                :alt="$project->title"
                :width="1280"
                :height="720"
                eager
                class="aspect-[16/9] w-full rounded-lg object-cover shadow-md"
            />
        </div>

        <div class="section-spacing">
            <div class="container-page">
                <div class="grid gap-12 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        @if ($bodyHtml !== '')
                            <div class="prose prose-neutral max-w-none">{!! $bodyHtml !!}</div>
                        @else
                            <x-ui.empty-state :message="__('projects.show.no_description')" :icon="false" />
                        @endif

                        {{-- Gallery with lightbox --}}
                        <section class="mt-12" aria-labelledby="gallery-heading">
                            <h2 id="gallery-heading" class="text-xl font-bold text-neutral-900">{{ __('projects.show.gallery') }}</h2>

                            @if ($gallery === [])
                                <div class="mt-4">
                                    <x-ui.empty-state :message="__('projects.show.gallery_empty')" :icon="false" />
                                </div>
                            @else
                                <div x-data="lightbox({ total: {{ count($gallery) }} })" class="mt-4">
                                    <ul role="list" class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                        @foreach ($gallery as $index => $item)
                                            <li>
                                                <button
                                                    type="button"
                                                    x-on:click="show({{ $index }})"
                                                    class="group block w-full overflow-hidden rounded-lg border border-neutral-200"
                                                >
                                                    <span class="sr-only">
                                                        {{ __('projects.show.view_image', ['caption' => $item['caption'] ?? $project->title]) }}
                                                    </span>
                                                    <x-ui.media
                                                        :src="$item['image']"
                                                        alt=""
                                                        :width="480"
                                                        :height="360"
                                                        class="aspect-[4/3] w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                                    />
                                                </button>
                                                @if ($item['caption'])
                                                    <p class="mt-1.5 text-xs text-neutral-500">{{ $item['caption'] }}</p>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>

                                    {{-- Lightbox dialog: focus trapped, Escape closes, arrows navigate. --}}
                                    <div
                                        x-show="open"
                                        x-cloak
                                        x-trap.noscroll="open"
                                        x-on:keydown.escape.window="close()"
                                        x-on:keydown.arrow-right.prevent="next()"
                                        x-on:keydown.arrow-left.prevent="previous()"
                                        class="fixed inset-0 z-[70] flex items-center justify-center bg-neutral-950/90 p-4"
                                        role="dialog"
                                        aria-modal="true"
                                        aria-label="{{ __('projects.show.lightbox_label') }}"
                                    >
                                        <button
                                            type="button"
                                            x-on:click="close()"
                                            class="absolute right-4 top-4 touch-target rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:ring-white focus-visible:ring-offset-neutral-950"
                                        >
                                            <span class="sr-only">{{ __('projects.show.close_lightbox') }}</span>
                                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                            </svg>
                                        </button>

                                        @if (count($gallery) > 1)
                                            <button
                                                type="button"
                                                x-on:click="previous()"
                                                class="absolute left-2 touch-target rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:ring-white focus-visible:ring-offset-neutral-950 sm:left-6"
                                            >
                                                <span class="sr-only">{{ __('projects.show.previous_image') }}</span>
                                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                x-on:click="next()"
                                                class="absolute right-2 touch-target rounded-full bg-white/10 text-white hover:bg-white/20 focus-visible:ring-white focus-visible:ring-offset-neutral-950 sm:right-6"
                                            >
                                                <span class="sr-only">{{ __('projects.show.next_image') }}</span>
                                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        @endif

                                        <figure class="max-h-full w-full max-w-4xl">
                                            @foreach ($gallery as $index => $item)
                                                <div x-show="isActive({{ $index }})" @if ($index > 0) x-cloak @endif>
                                                    <x-ui.media
                                                        :src="$item['image']"
                                                        :alt="$item['caption'] ?? $project->title"
                                                        :width="1280"
                                                        :height="960"
                                                        class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain"
                                                    />
                                                    @if ($item['caption'])
                                                        <figcaption class="mt-3 text-center text-sm text-neutral-300">{{ $item['caption'] }}</figcaption>
                                                    @endif
                                                </div>
                                            @endforeach

                                            <p class="mt-2 text-center text-xs text-neutral-400" aria-live="polite">
                                                <span x-text="current + 1"></span> / {{ count($gallery) }}
                                            </p>
                                        </figure>
                                    </div>
                                </div>
                            @endif
                        </section>
                    </div>

                    {{-- Project facts --}}
                    <aside class="lg:col-span-1">
                        <x-ui.card padding="md" class="lg:sticky lg:top-28">
                            <dl class="space-y-4 text-sm">
                                @if ($project->clientName)
                                    <div>
                                        <dt class="font-semibold text-neutral-900">{{ __('projects.show.client') }}</dt>
                                        <dd class="mt-0.5 text-neutral-600">{{ $project->clientName }}</dd>
                                    </div>
                                @endif

                                @if ($project->location)
                                    <div>
                                        <dt class="font-semibold text-neutral-900">{{ __('projects.show.location') }}</dt>
                                        <dd class="mt-0.5 text-neutral-600">{{ $project->location }}</dd>
                                    </div>
                                @endif

                                @if ($project->completionDate)
                                    <div>
                                        <dt class="font-semibold text-neutral-900">{{ __('projects.show.completed') }}</dt>
                                        <dd class="mt-0.5 text-neutral-600">
                                            <time datetime="{{ $project->completionDateIso() }}">{{ $project->formattedCompletionDate() }}</time>
                                        </dd>
                                    </div>
                                @endif

                                @if ($linkedServices->isNotEmpty())
                                    <div>
                                        <dt class="font-semibold text-neutral-900">{{ __('projects.show.services_used') }}</dt>
                                        <dd class="mt-2">
                                            <ul role="list" class="flex flex-wrap gap-2">
                                                @foreach ($linkedServices as $service)
                                                    <li>
                                                        <a
                                                            href="{{ route('services.show', $service->slug) }}"
                                                            class="inline-flex min-h-touch items-center rounded-full bg-primary-50 px-3 text-xs font-medium text-primary-800 hover:bg-primary-100"
                                                        >{{ $service->title }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            <div class="mt-6">
                                <x-ui.button :href="route('contact')" variant="primary" size="md" class="w-full">
                                    {{ __('common.cta.get_quote') }}
                                </x-ui.button>
                            </div>
                        </x-ui.card>
                    </aside>
                </div>
            </div>
        </div>

        @if ($relatedProjects->isNotEmpty())
            <section class="section-spacing bg-neutral-50" aria-labelledby="related-heading">
                <div class="container-page">
                    <h2 id="related-heading" class="text-2xl font-bold tracking-tight text-neutral-900">{{ __('projects.show.related') }}</h2>
                    <ul role="list" class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($relatedProjects as $related)
                            <li class="h-full"><x-sections.project-card :project="$related" /></li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </article>

    <x-sections.cta-banner
        :heading="__('projects.show.cta_heading')"
        :body="__('projects.show.cta_body')"
        :button-label="__('common.cta.get_quote')"
        :button-href="route('contact')"
    />
</x-layouts.app>
