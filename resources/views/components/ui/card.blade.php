@props([
    'hoverable' => false,
    'bordered' => true,
    'padding' => 'md',
    'as' => 'div',
])

@php
    /**
     * Surface container.
     *
     * `hoverable` adds elevation and a small lift; the lift is removed for
     * visitors who prefer reduced motion via the global CSS rule.
     */
    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    $classes = implode(' ', array_filter([
        'relative overflow-hidden rounded-lg bg-white',
        $bordered ? 'border border-neutral-200' : '',
        $paddings[$padding] ?? $paddings['md'],
        $hoverable
            ? 'shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg focus-within:-translate-y-0.5 focus-within:shadow-lg'
            : 'shadow-sm',
    ]));
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $classes]) }}>
    @isset($header)
        <div @class(['mb-4 border-b border-neutral-200 pb-4' => $padding !== 'none'])>
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}

    @isset($footer)
        <div @class(['mt-4 border-t border-neutral-200 pt-4' => $padding !== 'none'])>
            {{ $footer }}
        </div>
    @endisset
</{{ $as }}>
