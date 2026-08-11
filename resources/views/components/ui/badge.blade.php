@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variants = [
        'neutral' => 'bg-neutral-100 text-neutral-700',
        'primary' => 'bg-primary-100 text-primary-800',
        'secondary' => 'bg-secondary-100 text-secondary-800',
        'success' => 'bg-success-100 text-success-800',
        'warning' => 'bg-warning-100 text-warning-800',
        'danger' => 'bg-danger-100 text-danger-800',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full font-medium '
        .($variants[$variant] ?? $variants['neutral']).' '
        .($sizes[$size] ?? $sizes['md']),
]) }}>{{ $slot }}</span>
