@props(['settings'])

@php
    $stats = array_values(array_filter([
        $settings->yearsInBusiness > 0 ? ['value' => $settings->yearsInBusiness, 'label' => __('home.stats.years'), 'suffix' => '+'] : null,
        $settings->projectsCompleted > 0 ? ['value' => $settings->projectsCompleted, 'label' => __('home.stats.projects'), 'suffix' => '+'] : null,
        $settings->happyClients > 0 ? ['value' => $settings->happyClients, 'label' => __('home.stats.clients'), 'suffix' => '+'] : null,
        $settings->teamSize > 0 ? ['value' => $settings->teamSize, 'label' => __('home.stats.team'), 'suffix' => ''] : null,
    ]));
@endphp

@if ($stats !== [])
    <section class="bg-neutral-900" aria-labelledby="stats-heading">
        <div class="container-page py-12 sm:py-16">
            <h2 id="stats-heading" class="sr-only">{{ __('home.stats.heading') }}</h2>

            <dl class="grid grid-cols-2 gap-8 lg:grid-cols-{{ min(4, count($stats)) }}">
                @foreach ($stats as $stat)
                    <div class="text-center">
                        <dt class="order-2 mt-1 text-sm text-neutral-400">{{ $stat['label'] }}</dt>
                        <dd class="order-1 text-3xl font-bold text-white sm:text-4xl">
                            {{ number_format($stat['value']) }}{{ $stat['suffix'] }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
@endif
