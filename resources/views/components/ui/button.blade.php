@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'disabled' => false,
    'loadingText' => null,
    'icon' => null,
])

@php
    /**
     * Button / link button.
     *
     * States: default, hover, focus, active, disabled, loading.
     *
     * Renders an <a> when `href` is given and the control is enabled; a
     * disabled link is rendered as a <button> instead, because an <a> without
     * href is not focusable and `pointer-events: none` alone still leaves it in
     * the tab order for some assistive technologies.
     */
    $isInteractiveLink = $href !== null && ! $disabled && ! $loading;
    $tag = $isInteractiveLink ? 'a' : 'button';
    $isBlocked = $disabled || $loading;

    $base = 'relative inline-flex items-center justify-center gap-2 rounded-md font-semibold '
        .'transition-colors duration-150 select-none '
        .'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';

    $variants = [
        'primary' => 'bg-primary-800 text-white shadow-sm hover:bg-primary-900 active:bg-primary-950 focus-visible:ring-primary-600',
        'secondary' => 'bg-secondary-500 text-neutral-900 shadow-sm hover:bg-secondary-400 active:bg-secondary-600 focus-visible:ring-secondary-600',
        'outline' => 'border-2 border-primary-800 bg-transparent text-primary-800 hover:bg-primary-50 active:bg-primary-100 focus-visible:ring-primary-600',
        'ghost' => 'bg-transparent text-primary-800 hover:bg-primary-50 active:bg-primary-100 focus-visible:ring-primary-600',
        'danger' => 'bg-danger-600 text-white shadow-sm hover:bg-danger-700 active:bg-danger-800 focus-visible:ring-danger-600',
        'white' => 'bg-white text-primary-900 shadow-sm hover:bg-neutral-100 active:bg-neutral-200 focus-visible:ring-white',
    ];

    // Every size clears the 44px minimum touch target.
    $sizes = [
        'sm' => 'min-h-touch px-3.5 py-2 text-sm',
        'md' => 'min-h-touch px-5 py-2.5 text-sm',
        'lg' => 'min-h-[3rem] px-6 py-3 text-base',
    ];

    $classes = implode(' ', [
        $base,
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $isBlocked ? 'cursor-not-allowed opacity-60' : '',
    ]);
@endphp

<{{ $tag }}
    @if ($isInteractiveLink)
        href="{{ $href }}"
    @else
        type="{{ $type }}"
        @if ($disabled) disabled aria-disabled="true" @endif
    @endif
    @if ($loading)
        aria-busy="true"
        aria-disabled="true"
    @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($loading)
        {{-- Spinner replaces the icon; the label switches to the loading text. --}}
        <x-ui.spinner size="sm" class="shrink-0" :label="false" />
        <span>{{ $loadingText ?? __('common.states.loading') }}</span>
    @else
        @if ($icon)
            <span class="shrink-0" aria-hidden="true">{{ $icon }}</span>
        @endif
        <span>{{ $slot }}</span>
    @endif
</{{ $tag }}>
