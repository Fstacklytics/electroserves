@props(['settings'])

@php
    /**
     * Primary navigation.
     *
     * Desktop: horizontal links with an aria-current indicator.
     * Mobile:  a hamburger that opens a focus-trapped panel, closed by Escape.
     */
    $links = [
        ['route' => 'home', 'label' => __('common.nav.home'), 'pattern' => '/'],
        ['route' => 'services.index', 'label' => __('common.nav.services'), 'pattern' => 'services*'],
        ['route' => 'projects.index', 'label' => __('common.nav.projects'), 'pattern' => 'projects*'],
        ['route' => 'about', 'label' => __('common.nav.about'), 'pattern' => 'about'],
        ['route' => 'blog.index', 'label' => __('common.nav.blog'), 'pattern' => 'blog*'],
        ['route' => 'faq', 'label' => __('common.nav.faq'), 'pattern' => 'faq'],
        ['route' => 'contact', 'label' => __('common.nav.contact'), 'pattern' => 'contact'],
    ];

    /** @var \App\Services\ImageService $images */
    $images = app(\App\Services\ImageService::class);
    $logo = $settings->logo !== null && $images->exists($settings->logo) ? $images->url($settings->logo) : null;
    $emergencyHref = $settings->emergencyPhoneHref();
@endphp

<header
    x-data="{ scrolled: false, mobileOpen: false }"
    x-on:scroll.window="scrolled = window.scrollY > 8"
    x-on:keydown.escape.window="mobileOpen = false"
    class="sticky top-0 z-50 bg-white transition-shadow duration-200"
    :class="scrolled ? 'shadow-md' : 'shadow-sm'"
>
    @if ($emergencyHref)
        {{-- Emergency contact stays reachable from every page (user flow 4). --}}
        <div class="bg-primary-900 text-white">
            <div class="container-page flex items-center justify-center gap-2 py-1.5 text-center text-xs sm:text-sm">
                <svg class="h-4 w-4 shrink-0 text-secondary-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                    <path d="M11.983 1.907a.75.75 0 0 0-1.292-.657l-6.5 8.5A.75.75 0 0 0 4.75 11h3.925l-.658 5.093a.75.75 0 0 0 1.292.657l6.5-8.5A.75.75 0 0 0 15.25 7h-3.925l.658-5.093Z" />
                </svg>
                <span>{{ __('home.emergency.body') }}</span>
                <a
                    href="{{ $emergencyHref }}"
                    class="font-semibold underline underline-offset-2 hover:text-secondary-200 focus-visible:ring-offset-primary-900"
                >{{ $settings->emergencyPhone }}</a>
            </div>
        </div>
    @endif

    <nav class="container-page" aria-label="{{ __('common.nav.label') }}">
        <div class="flex h-18 items-center justify-between gap-4 py-3">
            {{-- Logo / wordmark --}}
            <a
                href="{{ route('home') }}"
                class="flex shrink-0 items-center gap-2.5 rounded-md py-1 text-lg font-bold text-neutral-900 hover:text-primary-800"
                @if (request()->routeIs('home')) aria-current="page" @endif
            >
                @if ($logo)
                    <img
                        src="{{ $logo }}"
                        alt="{{ $settings->siteName }}"
                        width="40"
                        height="40"
                        class="h-10 w-auto"
                        loading="eager"
                        decoding="async"
                    >
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-800" aria-hidden="true">
                        <svg class="h-6 w-6 text-secondary-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M13.2 2 6 12h4.5l-1.2 10L18 10h-5.2z" />
                        </svg>
                    </span>
                @endif
                <span class="whitespace-nowrap">{{ $settings->siteName }}</span>
            </a>

            {{-- Desktop navigation --}}
            <ul class="hidden items-center gap-1 lg:flex">
                @foreach ($links as $link)
                    @php $isActive = request()->is($link['pattern']) || ($link['pattern'] === '/' && request()->is('/')); @endphp
                    <li>
                        <a
                            href="{{ route($link['route']) }}"
                            @class([
                                'relative flex min-h-touch items-center rounded-md px-3 text-sm font-medium transition-colors',
                                'text-primary-800 bg-primary-50' => $isActive,
                                'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="flex items-center gap-2">
                <x-ui.button
                    :href="route('contact')"
                    variant="primary"
                    size="sm"
                    class="hidden sm:inline-flex"
                >
                    {{ __('common.cta.get_quote') }}
                </x-ui.button>

                {{-- Mobile menu trigger --}}
                <button
                    type="button"
                    x-on:click="mobileOpen = ! mobileOpen"
                    :aria-expanded="mobileOpen ? 'true' : 'false'"
                    aria-controls="mobile-menu"
                    class="touch-target rounded-md text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 lg:hidden"
                >
                    <span class="sr-only" x-text="mobileOpen ? @js(__('common.nav.close_menu')) : @js(__('common.nav.open_menu'))">{{ __('common.nav.open_menu') }}</span>
                    <svg x-show="! mobileOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile panel. Shares this Alpine scope so the hamburger owns the state. --}}
        <x-common.mobile-menu :links="$links" :settings="$settings" />

    </nav>
</header>
