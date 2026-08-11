@props([
    'title' => null,
    'message',
    'actionLabel' => null,
    'actionHref' => null,
    'icon' => true,
])

{{--
    Empty state.

    Used wherever a collection can legitimately be empty, so the visitor sees an
    explanation and a way forward instead of blank space.
--}}
<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-neutral-300 bg-neutral-50 px-6 py-12 text-center']) }}>
    @if ($icon)
        <svg class="mx-auto h-10 w-10 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" focusable="false">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 12m0 0-8.25-4.5M12 12v9.75m8.25-14.25v9l-8.25 4.5-8.25-4.5v-9L12 3l8.25 4.5Z" />
        </svg>
    @endif

    @if ($title)
        <p class="mt-4 text-base font-semibold text-neutral-900">{{ $title }}</p>
    @endif

    <p @class(['mx-auto max-w-md text-sm text-neutral-600', 'mt-2' => $title, 'mt-4' => ! $title && $icon])>{{ $message }}</p>

    @if ($actionLabel && $actionHref)
        <div class="mt-6">
            <x-ui.button :href="$actionHref" variant="outline" size="md">{{ $actionLabel }}</x-ui.button>
        </div>
    @endif

    {{ $slot }}
</div>
