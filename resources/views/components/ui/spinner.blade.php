@props([
    'size' => 'md',
    'label' => true,
    'text' => null,
])

@php
    /**
     * Loading spinner.
     *
     * When `label` is false the spinner is purely decorative — used inside a
     * button that already announces its own loading state via aria-busy, so the
     * status is not announced twice.
     */
    $sizes = [
        'sm' => 'h-4 w-4 border-2',
        'md' => 'h-6 w-6 border-2',
        'lg' => 'h-10 w-10 border-[3px]',
    ];

    $classes = ($sizes[$size] ?? $sizes['md'])
        .' inline-block animate-spin rounded-full border-current border-r-transparent align-[-0.125em]';
@endphp

@if ($label)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }} role="status">
        <span class="{{ $classes }}" aria-hidden="true"></span>
        <span class="sr-only">{{ $text ?? __('common.states.loading') }}</span>
        @if ($text)
            <span aria-hidden="true">{{ $text }}</span>
        @endif
    </span>
@else
    <span {{ $attributes->merge(['class' => $classes]) }} aria-hidden="true"></span>
@endif
