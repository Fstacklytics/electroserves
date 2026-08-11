@php
    /**
     * Error page layout.
     *
     * Errors keep the navbar and footer so the visitor always has a way out,
     * and are explicitly marked noindex.
     */
    $seo = $seo ?? app(\App\Services\SeoService::class)->forPage([
        'title' => $title ?? __('errors.500.title'),
        'robots' => 'noindex, nofollow',
    ]);
    $siteSettings = $siteSettings ?? app(\App\Services\ContentService::class)->siteSettings();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-common.seo-head :seo="$seo" />
    <x-common.favicon :settings="$siteSettings" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-base text-neutral-700">
    <a href="#main-content" class="skip-link">{{ __('common.skip_to_content') }}</a>

    <x-common.navbar :settings="$siteSettings" />

    <main id="main-content" tabindex="-1" class="flex flex-1 items-center focus:outline-none">
        {{ $slot }}
    </main>

    <x-common.footer :settings="$siteSettings" />
</body>
</html>
