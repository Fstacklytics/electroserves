@props(['service'])

@php
    $categories = (array) config('electroserves.service_categories', []);
@endphp

<x-ui.card
    as="article"
    hoverable
    padding="none"
    class="flex h-full flex-col"
    data-category="{{ $service->category }}"
>
    <div class="flex h-full flex-col p-6">
        <div class="flex items-start justify-between gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-800" aria-hidden="true">
                <x-icons.service :name="$service->icon" class="h-6 w-6" />
            </span>

            @if (isset($categories[$service->category]))
                <x-ui.badge variant="neutral">{{ $categories[$service->category] }}</x-ui.badge>
            @endif
        </div>

        <h3 class="mt-4 text-lg font-semibold text-neutral-900">
            {{-- Stretched link: the whole card is clickable, but only one link is in the tab order. --}}
            <a href="{{ route('services.show', $service->slug) }}" class="after:absolute after:inset-0 focus-visible:outline-none">
                {{ $service->title }}
                <span class="sr-only">— {{ __('services.card.learn_more_about', ['service' => $service->title]) }}</span>
            </a>
        </h3>

        <p class="mt-2 flex-1 text-sm leading-relaxed text-neutral-600">{{ $service->shortDescription }}</p>

        @if ($service->priceRange)
            <p class="mt-3 text-sm font-medium text-neutral-900">{{ $service->priceRange }}</p>
        @endif

        <p class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-800" aria-hidden="true">
            {{ __('common.cta.learn_more') }}
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" focusable="false">
                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
            </svg>
        </p>
    </div>
</x-ui.card>
