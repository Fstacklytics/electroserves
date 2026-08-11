@props(['settings'])

@php
    /** @var \App\Services\ContentService $content */
    $content = app(\App\Services\ContentService::class);
    $footerServices = $content->services()->take(5);

    $quickLinks = [
        ['route' => 'services.index', 'label' => __('common.nav.services')],
        ['route' => 'projects.index', 'label' => __('common.nav.projects')],
        ['route' => 'about', 'label' => __('common.nav.about')],
        ['route' => 'blog.index', 'label' => __('common.nav.blog')],
        ['route' => 'testimonials', 'label' => __('common.nav.testimonials')],
        ['route' => 'faq', 'label' => __('common.nav.faq')],
        ['route' => 'contact', 'label' => __('common.nav.contact')],
    ];

    $socialLabels = [
        'facebook' => __('common.social.facebook'),
        'instagram' => __('common.social.instagram'),
        'twitter' => __('common.social.twitter'),
        'linkedin' => __('common.social.linkedin'),
        'youtube' => __('common.social.youtube'),
        'whatsapp' => __('common.social.whatsapp'),
    ];
@endphp

<footer class="mt-auto bg-neutral-900 text-neutral-300">
    <div class="container-page py-12 lg:py-16">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Company --}}
            <div>
                <p class="text-lg font-bold text-white">{{ $settings->siteName }}</p>
                @if ($settings->tagline !== '')
                    <p class="mt-2 max-w-xs text-sm leading-relaxed text-neutral-400">{{ $settings->tagline }}</p>
                @endif

                @if ($settings->hasSocialLinks())
                    <div class="mt-5">
                        <p class="text-sm font-semibold text-white">{{ __('common.footer.follow_us') }}</p>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($settings->socialLinks as $network => $url)
                                <li>
                                    <a
                                        href="{{ $url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="touch-target rounded-md bg-neutral-800 text-neutral-300 transition-colors hover:bg-primary-700 hover:text-white focus-visible:ring-offset-neutral-900"
                                    >
                                        <span class="sr-only">{{ $socialLabels[$network] ?? $network }}</span>
                                        <x-icons.social :network="$network" class="h-5 w-5" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Quick links --}}
            <nav aria-labelledby="footer-quick-links">
                <p id="footer-quick-links" class="text-sm font-semibold text-white">{{ __('common.footer.quick_links') }}</p>
                <ul class="mt-4 space-y-1">
                    @foreach ($quickLinks as $link)
                        <li>
                            <a
                                href="{{ route($link['route']) }}"
                                class="flex min-h-touch items-center text-sm text-neutral-400 transition-colors hover:text-white focus-visible:ring-offset-neutral-900"
                            >{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Services --}}
            <nav aria-labelledby="footer-services">
                <p id="footer-services" class="text-sm font-semibold text-white">{{ __('common.footer.our_services') }}</p>
                @if ($footerServices->isEmpty())
                    <p class="mt-4 text-sm text-neutral-500">{{ __('home.services.empty') }}</p>
                @else
                    <ul class="mt-4 space-y-1">
                        @foreach ($footerServices as $service)
                            <li>
                                <a
                                    href="{{ route('services.show', $service->slug) }}"
                                    class="flex min-h-touch items-center text-sm text-neutral-400 transition-colors hover:text-white focus-visible:ring-offset-neutral-900"
                                >{{ $service->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </nav>

            {{-- Contact --}}
            <div>
                <p class="text-sm font-semibold text-white">{{ __('common.footer.get_in_touch') }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @if ($settings->phoneHref())
                        <li class="flex items-start gap-2.5">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                <path d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.716 3.223a1.5 1.5 0 0 1-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.5 11.5 0 0 0 6.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 0 1 1.767-1.052l3.223.716A1.5 1.5 0 0 1 18 15.352V16.5a1.5 1.5 0 0 1-1.5 1.5H15c-7.18 0-13-5.82-13-13V3.5Z" />
                            </svg>
                            <span>
                                <span class="sr-only">{{ __('common.meta.phone') }}:</span>
                                <a href="{{ $settings->phoneHref() }}" class="text-neutral-300 hover:text-white focus-visible:ring-offset-neutral-900">{{ $settings->phone }}</a>
                            </span>
                        </li>
                    @endif

                    @if ($settings->emergencyPhoneHref())
                        <li class="flex items-start gap-2.5">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-secondary-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                <path d="M11.983 1.907a.75.75 0 0 0-1.292-.657l-6.5 8.5A.75.75 0 0 0 4.75 11h3.925l-.658 5.093a.75.75 0 0 0 1.292.657l6.5-8.5A.75.75 0 0 0 15.25 7h-3.925l.658-5.093Z" />
                            </svg>
                            <span>
                                <span class="sr-only">{{ __('common.footer.emergency_line') }}:</span>
                                <a href="{{ $settings->emergencyPhoneHref() }}" class="text-neutral-300 hover:text-white focus-visible:ring-offset-neutral-900">{{ $settings->emergencyPhone }}</a>
                            </span>
                        </li>
                    @endif

                    @if ($settings->email !== '')
                        <li class="flex items-start gap-2.5">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                <path d="M3 4a2 2 0 0 0-2 2v.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 6.161V6a2 2 0 0 0-2-2H3Z" />
                                <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                            </svg>
                            <span>
                                <span class="sr-only">{{ __('common.meta.email') }}:</span>
                                <a href="mailto:{{ $settings->email }}" class="break-all text-neutral-300 hover:text-white focus-visible:ring-offset-neutral-900">{{ $settings->email }}</a>
                            </span>
                        </li>
                    @endif

                    @if ($settings->fullAddress() !== '')
                        <li class="flex items-start gap-2.5">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                <path fill-rule="evenodd" d="M9.69 18.933A7.5 7.5 0 0 0 10 19a7.5 7.5 0 0 0 .31-.067C13.02 17.79 16 14.2 16 10a6 6 0 1 0-12 0c0 4.2 2.98 7.79 5.69 8.933ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
                            </svg>
                            <address class="not-italic text-neutral-400">
                                <span class="sr-only">{{ __('common.meta.address') }}:</span>
                                {{ $settings->fullAddress() }}
                            </address>
                        </li>
                    @endif
                </ul>

                @if ($settings->hasBusinessHours())
                    <div class="mt-5">
                        <p class="text-sm font-semibold text-white">{{ __('common.footer.business_hours') }}</p>
                        <dl class="mt-2 space-y-1 text-sm text-neutral-400">
                            @foreach ($settings->businessHours as $row)
                                <div class="flex justify-between gap-3">
                                    <dt>{{ $row['day'] }}</dt>
                                    <dd class="text-right">{{ $row['hours'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-neutral-800 pt-6 sm:flex-row">
            <p class="text-center text-xs text-neutral-500 sm:text-left">
                &copy; {{ now()->year }}
                {{ $settings->copyright !== '' ? $settings->copyright : $settings->siteName }}.
                {{ __('common.footer.built_with') }}
            </p>

            <div class="flex items-center gap-4">
                <a href="{{ route('privacy') }}" class="text-xs text-neutral-500 hover:text-white focus-visible:ring-offset-neutral-900">{{ __('legal.privacy_title') }}</a>
                <a href="{{ route('terms') }}" class="text-xs text-neutral-500 hover:text-white focus-visible:ring-offset-neutral-900">{{ __('legal.terms_title') }}</a>
                <a
                    href="#main-content"
                    class="flex min-h-touch items-center gap-1 text-xs text-neutral-500 hover:text-white focus-visible:ring-offset-neutral-900"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                        <path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" />
                    </svg>
                    {{ __('common.cta.back_to_top') }}
                </a>
            </div>
        </div>
    </div>
</footer>
