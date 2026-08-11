@props(['services', 'showViewAll' => true, 'total' => null])

@php
    $services = collect($services);
    $total = $total ?? $services->count();
@endphp

<section class="section-spacing" aria-labelledby="services-heading">
    <div class="container-page">
        <x-sections.section-heading
            id="services-heading"
            :title="__('home.services.heading')"
            :subtitle="__('home.services.subheading')"
        />

        @if ($services->isEmpty())
            <div class="mt-10">
                <x-ui.empty-state
                    :message="__('home.services.empty')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            </div>
        @else
            <ul role="list" class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($services as $service)
                    <li class="h-full"><x-sections.service-card :service="$service" /></li>
                @endforeach
            </ul>

            @if ($showViewAll && $total > $services->count())
                <div class="mt-10 text-center">
                    <x-ui.button :href="route('services.index')" variant="outline" size="md">
                        {{ __('common.cta.view_all') }} ({{ $total }})
                    </x-ui.button>
                </div>
            @endif
        @endif
    </div>
</section>
