@php
    /**
     * Base layout.
     *
     * @var array{title: string, description: string, image: ?string, type: string, canonical: string, robots: string, site_name: string, twitter_handle: ?string, google_verification: ?string, locale: string}|null $seo
     * @var \App\DataObjects\SiteSettings $siteSettings
     */
    $seo = $seo ?? app(\App\Services\SeoService::class)->forPage();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-common.seo-head :seo="$seo" />

    {{-- Preconnect to the font host so the swap happens as early as possible. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        media="print"
        onload="this.media='all'"
    >
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

        {{--
            Controls that only do something once Alpine has initialised are
            marked with `data-js-only` and removed for non-JS visitors, so the
            page never presents a button that cannot work. The filtered card
            lists themselves stay fully visible: filtering is a progressive
            enhancement layered on top of the complete, server-rendered page.

            An inline <style> is used rather than an inline <script> so the rule
            applies before first paint (no flash of dead controls) and so this
            keeps working if script-src is ever tightened to drop
            'unsafe-inline'.
        --}}
        <style>
            [data-js-only] {
                display: none !important;
            }
        </style>
    </noscript>

    <x-common.favicon :settings="$siteSettings" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @isset($schema)
        <script type="application/ld+json">{!! $schema !!}</script>
    @endisset

    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-base text-neutral-700">
    {{-- Must be the first focusable element on the page. --}}
    <x-common.skip-to-content />

    <x-common.navbar :settings="$siteSettings" />

    <main id="main-content" tabindex="-1" class="flex-1 focus:outline-none">
        {{ $slot }}
    </main>

    <x-common.footer :settings="$siteSettings" />

    {{-- Global live region for transient notifications. --}}
    <x-common.toast-container />

    <x-common.cookie-banner />

    @stack('scripts')
</body>
</html>
