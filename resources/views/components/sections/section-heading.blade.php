@props([
    'title',
    'subtitle' => null,
    'id' => null,
    'align' => 'center',
])

<div @class([
    'max-w-2xl',
    'mx-auto text-center' => $align === 'center',
])>
    <h2 @if ($id) id="{{ $id }}" @endif class="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ $title }}</h2>

    @if ($subtitle)
        <p class="mt-3 text-base leading-relaxed text-neutral-600">{{ $subtitle }}</p>
    @endif
</div>
