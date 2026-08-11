@props(['rating' => 5, 'label' => null])

@php
    $rating = max(0, min(5, (int) $rating));
    $label = $label ?? __('testimonials.rating_label', ['rating' => $rating]);
@endphp

{{-- The rating is conveyed by text for assistive tech, not by colour alone. --}}
<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} role="img" aria-label="{{ $label }}">
    @for ($i = 1; $i <= 5; $i++)
        <svg
            class="h-4 w-4 {{ $i <= $rating ? 'text-secondary-500' : 'text-neutral-300' }}"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
            focusable="false"
        >
            <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z" clip-rule="evenodd" />
        </svg>
    @endfor
</div>
