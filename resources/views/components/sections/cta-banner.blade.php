@props([
    'heading',
    'body' => null,
    'buttonLabel' => null,
    'buttonHref' => null,
    'secondaryLabel' => null,
    'secondaryHref' => null,
    'variant' => 'default',
])

@php
    $isEmergency = $variant === 'emergency';
@endphp

<section {{ $attributes->merge(['class' => $isEmergency ? 'bg-danger-700' : 'bg-primary-800']) }}>
    <div class="container-page py-12 sm:py-16">
        <div class="flex flex-col items-center gap-6 text-center lg:flex-row lg:justify-between lg:text-left">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-bold text-white sm:text-3xl">{{ $heading }}</h2>
                @if ($body)
                    <p class="mt-3 text-base leading-relaxed {{ $isEmergency ? 'text-danger-100' : 'text-primary-100' }}">{{ $body }}</p>
                @endif
            </div>

            @if (($buttonLabel && $buttonHref) || ($secondaryLabel && $secondaryHref))
                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                    @if ($buttonLabel && $buttonHref)
                        <x-ui.button :href="$buttonHref" variant="{{ $isEmergency ? 'white' : 'secondary' }}" size="lg">
                            {{ $buttonLabel }}
                        </x-ui.button>
                    @endif

                    @if ($secondaryLabel && $secondaryHref)
                        <a
                            href="{{ $secondaryHref }}"
                            class="inline-flex min-h-touch items-center justify-center rounded-md border-2 border-white/70 px-6 py-3 text-base font-semibold text-white transition-colors hover:bg-white/10 focus-visible:ring-white focus-visible:ring-offset-{{ $isEmergency ? 'danger' : 'primary' }}-700"
                        >{{ $secondaryLabel }}</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
