@props([
    'width' => 'w-full',
    'height' => 'h-4',
    'rounded' => 'rounded',
    'lines' => 1,
])

@php
    /**
     * Loading placeholder.
     *
     * The wrapper carries aria-busy and a visually hidden "Loading" label so a
     * screen reader is told content is on its way rather than reading nothing.
     */
    $lines = max(1, (int) $lines);
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }} role="status" aria-busy="true">
    @for ($i = 0; $i < $lines; $i++)
        <div
            @class([
                'animate-pulse bg-neutral-200',
                $height,
                $rounded,
                // A multi-line skeleton reads more naturally with a short last line.
                $lines > 1 && $i === $lines - 1 ? 'w-2/3' : $width,
            ])
            aria-hidden="true"
        ></div>
    @endfor
    <span class="sr-only">{{ __('common.states.loading') }}</span>
</div>
