@props(['seo'])

{{--
    All meta tags for the current page.

    Every value is resolved by SeoService against the site-wide defaults, so the
    title, description and canonical URL are never empty.
--}}

<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
@if ($seo['image'])
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta property="og:image:alt" content="{{ $seo['title'] }}">
@endif

{{-- Twitter / X --}}
<meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if ($seo['image'])
    <meta name="twitter:image" content="{{ $seo['image'] }}">
@endif
@if ($seo['twitter_handle'])
    <meta name="twitter:site" content="{{ $seo['twitter_handle'] }}">
@endif

@if ($seo['google_verification'])
    <meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
@endif

<meta name="theme-color" content="#1e40af">
