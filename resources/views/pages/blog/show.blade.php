<x-layouts.app :seo="$seo" :schema="$schema">
    @php
        $shareUrl = route('blog.show', $post->slug);
        $shareTitle = $post->title;
    @endphp

    @push('head')
        {{-- Article structured data, in addition to the breadcrumb schema. --}}
        <script type="application/ld+json">
            {!! app(\App\Services\SeoService::class)->toJsonLd(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->title,
                'description' => $post->excerpt,
                'datePublished' => $post->dateIso(),
                'author' => ['@type' => 'Person', 'name' => $post->author],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => $siteSettings->siteName,
                ],
                'mainEntityOfPage' => $shareUrl,
                'image' => app(\App\Services\ImageService::class)->absoluteUrl($post->featuredImage),
            ])) !!}
        </script>
    @endpush

    <div class="border-b border-neutral-200 bg-neutral-50">
        <div class="container-page">
            <x-common.breadcrumb :crumbs="$breadcrumbs" />
        </div>
    </div>

    <article class="section-spacing">
        <div class="container-page">
            <header class="mx-auto max-w-3xl">
                <x-ui.badge variant="secondary">{{ $post->category }}</x-ui.badge>

                <h1 class="mt-3 text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">{{ $post->title }}</h1>

                <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-neutral-500">
                    <span>{{ __('blog.show.by_author', ['author' => $post->author]) }}</span>
                    @if ($post->date)
                        <span aria-hidden="true">·</span>
                        <time datetime="{{ $post->dateIso() }}">{{ $post->formattedDate() }}</time>
                    @endif
                    <span aria-hidden="true">·</span>
                    <span>{{ __('blog.show.reading_time', ['minutes' => $post->readingTimeMinutes()]) }}</span>
                </p>
            </header>

            <div class="mx-auto mt-8 max-w-4xl">
                <x-ui.media
                    :src="$post->featuredImage"
                    :alt="$post->title"
                    :width="1280"
                    :height="720"
                    eager
                    class="aspect-[16/9] w-full rounded-lg object-cover shadow-md"
                />
            </div>

            <div class="mt-10 grid gap-10 lg:grid-cols-4">
                {{-- Table of contents --}}
                @if ($headings !== [])
                    <aside class="lg:col-span-1 lg:order-2" aria-labelledby="toc-heading">
                        <nav class="lg:sticky lg:top-28">
                            <p id="toc-heading" class="text-sm font-semibold text-neutral-900">{{ __('blog.show.contents') }}</p>
                            <ol class="mt-3 space-y-1 border-l border-neutral-200 text-sm">
                                @foreach ($headings as $heading)
                                    <li>
                                        <a
                                            href="#{{ $heading['id'] }}"
                                            @class([
                                                'block border-l-2 border-transparent py-1.5 pl-3 -ml-px text-neutral-600 hover:border-primary-600 hover:text-primary-800',
                                                'pl-6' => $heading['level'] === 3,
                                            ])
                                        >{{ $heading['text'] }}</a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    </aside>
                @endif

                <div @class(['lg:order-1', 'lg:col-span-3' => $headings !== [], 'lg:col-span-4' => $headings === []])>
                    <div class="mx-auto max-w-prose">
                        @if ($bodyHtml !== '')
                            {{-- Sanitised by MarkdownService: inline HTML is stripped at parse time. --}}
                            <div class="prose prose-neutral max-w-none">{!! $bodyHtml !!}</div>
                        @else
                            <x-ui.empty-state :message="__('blog.show.no_body')" :icon="false" />
                        @endif

                        @if ($post->hasTags())
                            <div class="mt-8">
                                <h2 class="text-sm font-semibold text-neutral-900">{{ __('blog.show.tags') }}</h2>
                                <ul role="list" class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($post->tags as $tag)
                                        <li><x-ui.badge variant="neutral">#{{ $tag }}</x-ui.badge></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Share --}}
                        <div class="mt-8 border-t border-neutral-200 pt-6" x-data="copyLink({
                            url: @js($shareUrl),
                            copiedLabel: @js(__('blog.show.link_copied')),
                            failedLabel: @js(__('blog.show.copy_failed'))
                        })">
                            <h2 class="text-sm font-semibold text-neutral-900">{{ __('blog.show.share') }}</h2>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <a
                                    href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareTitle) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="touch-target rounded-md border border-neutral-300 px-3 text-sm text-neutral-700 hover:bg-neutral-100"
                                >
                                    <span class="sr-only">{{ __('blog.show.share_twitter') }}</span>
                                    <x-icons.social network="twitter" class="h-4 w-4" />
                                </a>

                                <a
                                    href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="touch-target rounded-md border border-neutral-300 px-3 text-sm text-neutral-700 hover:bg-neutral-100"
                                >
                                    <span class="sr-only">{{ __('blog.show.share_facebook') }}</span>
                                    <x-icons.social network="facebook" class="h-4 w-4" />
                                </a>

                                <a
                                    href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="touch-target rounded-md border border-neutral-300 px-3 text-sm text-neutral-700 hover:bg-neutral-100"
                                >
                                    <span class="sr-only">{{ __('blog.show.share_linkedin') }}</span>
                                    <x-icons.social network="linkedin" class="h-4 w-4" />
                                </a>

                                <button
                                    type="button"
                                    x-on:click="copy()"
                                    :aria-busy="status === 'loading' ? 'true' : 'false'"
                                    class="inline-flex min-h-touch items-center gap-2 rounded-md border border-neutral-300 px-3 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" focusable="false">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                    </svg>
                                    {{ __('blog.show.copy_link') }}
                                </button>

                                {{-- Result of the copy attempt, announced politely. --}}
                                <span
                                    x-show="message !== ''"
                                    x-cloak
                                    x-text="message"
                                    :class="status === 'error' ? 'text-danger-700' : 'text-success-700'"
                                    class="text-sm font-medium"
                                    role="status"
                                ></span>
                            </div>
                        </div>

                        <div class="mt-8">
                            <a href="{{ route('blog.index') }}" class="inline-flex min-h-touch items-center text-sm font-semibold text-primary-800 hover:underline">
                                &larr; {{ __('blog.show.back_to_blog') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>

    @if ($relatedPosts->isNotEmpty())
        <section class="section-spacing bg-neutral-50" aria-labelledby="related-posts-heading">
            <div class="container-page">
                <h2 id="related-posts-heading" class="text-2xl font-bold tracking-tight text-neutral-900">{{ __('blog.show.related') }}</h2>
                <ul role="list" class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedPosts as $related)
                        <li class="h-full"><x-sections.blog-card :post="$related" /></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layouts.app>
