@props(['testimonials'])

@php
    $testimonials = collect($testimonials);
    $total = $testimonials->count();
@endphp

<section class="section-spacing bg-neutral-50" aria-labelledby="testimonials-heading">
    <div class="container-page">
        <x-sections.section-heading
            id="testimonials-heading"
            :title="__('home.testimonials.heading')"
            :subtitle="__('home.testimonials.subheading')"
        />

        @if ($total === 0)
            <div class="mt-10">
                <x-ui.empty-state
                    :message="__('home.testimonials.empty')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            </div>
        @else
            <div
                x-data="carousel({ total: {{ $total }}, interval: 8000, autoplay: {{ $total > 1 ? 'true' : 'false' }} })"
                x-on:mouseenter="onEnter()"
                x-on:mouseleave="onLeave()"
                x-on:focusin="onEnter()"
                x-on:focusout="onLeave()"
                x-on:keydown.arrow-right.prevent="manualNext()"
                x-on:keydown.arrow-left.prevent="manualPrevious()"
                class="relative mt-10"
                role="region"
                aria-roledescription="carousel"
                aria-label="{{ __('home.testimonials.heading') }}"
                tabindex="0"
            >
                <div :aria-live="liveSetting">
                    @foreach ($testimonials as $index => $testimonial)
                        <div
                            x-show="isActive({{ $index }})"
                            @if ($index > 0) x-cloak @endif
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            role="group"
                            aria-roledescription="slide"
                            aria-label="{{ __('home.hero.slide_position', ['current' => $index + 1, 'total' => $total]) }}"
                        >
                            <div class="mx-auto max-w-3xl">
                                <x-sections.testimonial-card :testimonial="$testimonial" />
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($total > 1)
                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button
                            type="button"
                            x-on:click="manualPrevious()"
                            class="touch-target rounded-full border border-neutral-300 bg-white text-neutral-700 transition-colors hover:bg-neutral-100"
                        >
                            <span class="sr-only">{{ __('home.hero.previous_slide') }}</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-2">
                            @for ($i = 0; $i < $total; $i++)
                                <button
                                    type="button"
                                    x-on:click="manualGo({{ $i }})"
                                    class="flex h-touch w-5 items-center justify-center"
                                    :aria-current="isActive({{ $i }}) ? 'true' : 'false'"
                                >
                                    <span class="sr-only">{{ __('home.hero.go_to_slide', ['number' => $i + 1]) }}</span>
                                    <span
                                        class="block h-2 rounded-full transition-all duration-200"
                                        :class="isActive({{ $i }}) ? 'w-5 bg-primary-700' : 'w-2 bg-neutral-300'"
                                        aria-hidden="true"
                                    ></span>
                                </button>
                            @endfor
                        </div>

                        <button
                            type="button"
                            x-on:click="manualNext()"
                            class="touch-target rounded-full border border-neutral-300 bg-white text-neutral-700 transition-colors hover:bg-neutral-100"
                        >
                            <span class="sr-only">{{ __('home.hero.next_slide') }}</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endif

                <div class="mt-6 text-center">
                    <a href="{{ route('testimonials') }}" class="inline-flex min-h-touch items-center text-sm font-semibold text-primary-800 hover:underline">
                        {{ __('common.cta.view_all') }} &rarr;
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>
