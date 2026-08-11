@props([
    'src' => null,
    'alt' => '',
    'width' => 800,
    'height' => 600,
    'eager' => false,
    'label' => null,
    'rounded' => '',
    'class' => '',
])

@php
    /**
     * Image with a guaranteed placeholder.
     *
     * Every image carries explicit width/height (so the browser reserves space
     * and CLS stays at zero), lazy loading below the fold, and async decoding.
     * A WebP sibling is served through <picture> when one exists on disk.
     *
     * When the reference is missing or the file is absent, a solid gradient
     * placeholder with the site mark is rendered instead of a broken image —
     * no external placeholder service is used.
     */
    /** @var \App\Services\ImageService $images */
    $images = app(\App\Services\ImageService::class);

    $hasImage = $src !== null && $images->exists($src);
    $url = $hasImage ? $images->url($src) : null;
    $webp = $hasImage ? $images->webpUrl($src) : null;
    $boxClasses = trim($class.' '.$rounded);
@endphp

@if ($hasImage && $url)
    @if ($webp)
        <picture>
            <source srcset="{{ $webp }}" type="image/webp">
            <img
                src="{{ $url }}"
                alt="{{ $alt }}"
                width="{{ $width }}"
                height="{{ $height }}"
                loading="{{ $eager ? 'eager' : 'lazy' }}"
                @if ($eager) fetchpriority="high" @endif
                decoding="async"
                {{ $attributes->merge(['class' => $boxClasses]) }}
            >
        </picture>
    @else
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            @if ($eager) fetchpriority="high" @endif
            decoding="async"
            {{ $attributes->merge(['class' => $boxClasses]) }}
        >
    @endif
@else
    {{-- Decorative placeholder. Any meaning is carried by `label`, not the mark. --}}
    <div
        {{ $attributes->merge(['class' => 'image-placeholder '.$boxClasses]) }}
        style="aspect-ratio: {{ $width }} / {{ $height }};"
        @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    >
        <svg class="h-1/4 max-h-16 w-auto opacity-40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
            <path d="M13.2 2 6 12h4.5l-1.2 10L18 10h-5.2z" />
        </svg>
    </div>
@endif
