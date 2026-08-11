@props([
    'code',
    'title',
    'body',
    'showServices' => true,
])

@php
    $settings = app(\App\Services\ContentService::class)->siteSettings();
@endphp

<div class="container-page py-16 sm:py-24">
    <div class="mx-auto max-w-xl text-center">
        {{-- Decorative: the meaning is carried by the heading below. --}}
        <p class="text-6xl font-bold tracking-tight text-primary-200 sm:text-7xl" aria-hidden="true">{{ $code }}</p>

        <h1 class="mt-4 text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">{{ $title }}</h1>
        <p class="mt-4 text-base leading-relaxed text-neutral-600">{{ $body }}</p>

        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <x-ui.button :href="route('home')" variant="primary" size="md">{{ __('errors.actions.home') }}</x-ui.button>

            @if ($showServices)
                <x-ui.button :href="route('services.index')" variant="outline" size="md">{{ __('errors.actions.services') }}</x-ui.button>
            @endif

            <x-ui.button :href="route('contact')" variant="ghost" size="md">{{ __('errors.actions.contact') }}</x-ui.button>
        </div>

        @if ($settings->phone !== '')
            <p class="mt-8 text-sm text-neutral-500">
                {{ __('errors.help', ['phone' => '']) }}
                <a href="{{ $settings->phoneHref() }}" class="font-semibold text-primary-800 hover:underline">{{ $settings->phone }}</a>
            </p>
        @endif

        {{ $slot }}
    </div>
</div>
