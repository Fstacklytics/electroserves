@props(['slides'])

@php
    /**
     * Homepage hero carousel.
     *
     * Falls back to a single static panel when no slides are published, so the
     * top of the page is never empty.
     */
    $slides = collect($slides);
    $hasSlides = $slides->isNotEmpty();
    $total = $hasSlides ? $slides->count() : 1;
@endphp

<section
    x-data="carousel({ total: {{ $total }}, interval: 7000, autoplay: {{ $total > 1 ? 'true' : 'false' }} })"
    x-on:mouseenter="onEnter()"
    x-on:mouseleave="onLeave()"
    x-on:focusin="onEnter()"
    x-on:focusout="onLeave()"
    x-on:keydown.arrow-right.prevent="manualNext()"
    x-on:keydown.arrow-left.prevent="manualPrevious()"
    class="relative isolate overflow-hidden bg-primary-900"
    role="region"
    aria-roledescription="carousel"
    aria-label="{{ __('home.hero.label') }}"
>
    @if (! $hasSlides)
        {{-- Empty state: still a complete, on-brand hero. --}}
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-br from-primary-900 via-primary-800 to-primary-950" aria-hidden="true"></div>
            <div class="container-page relative py-20 sm:py-28">
                <div class="max-w-2xl">
                    <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                        {{ __('home.hero.empty_heading') }}
                    </h1>
                    <p class="mt-5 text-lg leading-relaxed text-primary-100">{{ __('home.hero.empty_subheading') }}</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <x-ui.button :href="route('contact')" variant="secondary" size="lg">{{ __('common.cta.get_quote') }}</x-ui.button>
                        <x-ui.button :href="route('services.index')" variant="white" size="lg">{{ __('home.cta.secondary') }}</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div :aria-live="liveSetting">
            @foreach ($slides as $index => $slide)
                <div
                    x-show="isActive({{ $index }})"
                    @if ($index > 0) x-cloak @endif
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    @class(['relative', 'absolute inset-0' => $index > 0])
                    role="group"
                    aria-roledescription="slide"
                    aria-label="{{ __('home.hero.slide_position', ['current' => $index + 1, 'total' => $total]) }}"
                >
                    {{-- Background image with an overlay that guarantees text contrast. --}}
                    @if ($slide->hasImage())
                        <x-ui.media
                            :src="$slide->image"
                            alt=""
                            :width="1920"
                            :height="900"
                            :eager="$index === 0"
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-r from-primary-950/90 via-primary-900/80 to-primary-900/50" aria-hidden="true"></div>
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br from-primary-900 via-primary-800 to-primary-950" aria-hidden="true"></div>
                    @endif

                    <div class="container-page relative py-20 sm:py-28">
                        <div class="max-w-2xl">
                            @if ($index === 0)
                                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $slide->heading }}</h1>
                            @else
                                <p class="text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ $slide->heading }}</p>
                            @endif

                            @if ($slide->subheading !== '')
                                <p class="mt-5 text-lg leading-relaxed text-primary-100">{{ $slide->subheading }}</p>
                            @endif

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <x-ui.button :href="$slide->ctaLink" variant="secondary" size="lg">{{ $slide->ctaText }}</x-ui.button>

                                @if ($slide->hasSecondaryCta())
                                    <x-ui.button :href="$slide->ctaSecondaryLink" variant="white" size="lg">{{ $slide->ctaSecondaryText }}</x-ui.button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($total > 1)
            {{-- Carousel controls --}}
            <div class="container-page relative pb-6">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        x-on:click="manualPrevious()"
                        class="touch-target rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20 focus-visible:ring-white focus-visible:ring-offset-primary-900"
                    >
                        <span class="sr-only">{{ __('home.hero.previous_slide') }}</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        x-on:click="manualNext()"
                        class="touch-target rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20 focus-visible:ring-white focus-visible:ring-offset-primary-900"
                    >
                        <span class="sr-only">{{ __('home.hero.next_slide') }}</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        x-on:click="toggle()"
                        class="touch-target rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/20 focus-visible:ring-white focus-visible:ring-offset-primary-900"
                    >
                        <span class="sr-only" x-text="playing ? @js(__('home.hero.pause')) : @js(__('home.hero.play'))">{{ __('home.hero.pause') }}</span>
                        <svg x-show="playing" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M5.75 3a.75.75 0 0 0-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 0 0 .75-.75V3.75A.75.75 0 0 0 7.25 3h-1.5Zm7 0a.75.75 0 0 0-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 0 0 .75-.75V3.75a.75.75 0 0 0-.75-.75h-1.5Z" />
                        </svg>
                        <svg x-show="! playing" x-cloak class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M6.3 2.84A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.27l9.344-5.891a1.5 1.5 0 0 0 0-2.538L6.3 2.841Z" />
                        </svg>
                    </button>

                    {{-- Slide dots --}}
                    <div class="ml-auto flex items-center gap-2">
                        @for ($i = 0; $i < $total; $i++)
                            <button
                                type="button"
                                x-on:click="manualGo({{ $i }})"
                                class="flex h-touch w-6 items-center justify-center focus-visible:ring-white focus-visible:ring-offset-primary-900"
                                :aria-current="isActive({{ $i }}) ? 'true' : 'false'"
                            >
                                <span class="sr-only">{{ __('home.hero.go_to_slide', ['number' => $i + 1]) }}</span>
                                <span
                                    class="block h-2 rounded-full transition-all duration-200"
                                    :class="isActive({{ $i }}) ? 'w-6 bg-secondary-400' : 'w-2 bg-white/40'"
                                    aria-hidden="true"
                                ></span>
                            </button>
                        @endfor
                    </div>
                </div>
            </div>
        @endif
    @endif
</section>
