@props(['settings'])

@php
    /** @var \App\Services\ImageService $images */
    $images = app(\App\Services\ImageService::class);
    $favicon = $settings->favicon !== null && $images->exists($settings->favicon)
        ? $images->url($settings->favicon)
        : null;
@endphp

@if ($favicon)
    <link rel="icon" href="{{ $favicon }}">
@else
    {{--
        Inline SVG favicon: avoids a 404 on a fresh install and needs no
        external placeholder service.
    --}}
    <link
        rel="icon"
        href="data:image/svg+xml,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="6" fill="#1e40af"/><path d="M17.6 5 9 18h5.4L13 27l9.4-13.6H16.8z" fill="#fbbf24"/></svg>') }}"
    >
@endif
