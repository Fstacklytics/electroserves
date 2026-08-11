@props(['project'])

@php
    $categories = (array) config('electroserves.project_categories', []);
@endphp

<x-ui.card
    as="article"
    hoverable
    padding="none"
    class="flex h-full flex-col"
    data-category="{{ $project->category }}"
>
    <x-ui.media
        :src="$project->featuredImage"
        :alt="$project->title"
        :width="640"
        :height="420"
        class="h-48 w-full object-cover"
    />

    <div class="flex flex-1 flex-col p-5">
        <div class="flex flex-wrap items-center gap-2">
            @if (isset($categories[$project->category]))
                <x-ui.badge variant="primary">{{ $categories[$project->category] }}</x-ui.badge>
            @endif

            @if ($project->completionDate)
                <time datetime="{{ $project->completionDateIso() }}" class="text-xs text-neutral-500">
                    {{ $project->formattedCompletionDate() }}
                </time>
            @endif
        </div>

        <h3 class="mt-2 text-lg font-semibold text-neutral-900">
            <a href="{{ route('projects.show', $project->slug) }}" class="after:absolute after:inset-0 focus-visible:outline-none">
                {{ $project->title }}
            </a>
        </h3>

        @if ($project->location)
            <p class="mt-1 inline-flex items-center gap-1 text-sm text-neutral-500">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                    <path fill-rule="evenodd" d="M9.69 18.933A7.5 7.5 0 0 0 10 19a7.5 7.5 0 0 0 .31-.067C13.02 17.79 16 14.2 16 10a6 6 0 1 0-12 0c0 4.2 2.98 7.79 5.69 8.933ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
                </svg>
                {{ $project->location }}
            </p>
        @endif

        <p class="mt-2 flex-1 text-sm leading-relaxed text-neutral-600">{{ $project->shortDescription }}</p>
    </div>
</x-ui.card>
