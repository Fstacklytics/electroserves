@props(['post'])

<x-ui.card
    as="article"
    hoverable
    padding="none"
    class="flex h-full flex-col"
    data-category="{{ $post->category }}"
>
    <x-ui.media
        :src="$post->featuredImage"
        :alt="$post->title"
        :width="640"
        :height="360"
        class="h-44 w-full object-cover"
    />

    <div class="flex flex-1 flex-col p-5">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge variant="secondary">{{ $post->category }}</x-ui.badge>
            @if ($post->date)
                <time datetime="{{ $post->dateIso() }}" class="text-xs text-neutral-500">{{ $post->formattedDate() }}</time>
            @endif
        </div>

        <h3 class="mt-2 text-lg font-semibold text-neutral-900">
            <a href="{{ route('blog.show', $post->slug) }}" class="after:absolute after:inset-0 focus-visible:outline-none">
                {{ $post->title }}
            </a>
        </h3>

        <p class="mt-2 flex-1 text-sm leading-relaxed text-neutral-600">{{ $post->excerpt }}</p>

        <p class="mt-4 text-xs text-neutral-500">
            {{ __('blog.show.by_author', ['author' => $post->author]) }}
            <span aria-hidden="true">·</span>
            {{ __('blog.show.reading_time', ['minutes' => $post->readingTimeMinutes()]) }}
        </p>
    </div>
</x-ui.card>
