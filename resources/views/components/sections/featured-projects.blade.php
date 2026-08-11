@props(['projects'])

@php $projects = collect($projects); @endphp

<section class="section-spacing bg-neutral-50" aria-labelledby="featured-projects-heading">
    <div class="container-page">
        <x-sections.section-heading
            id="featured-projects-heading"
            :title="__('home.projects.heading')"
            :subtitle="__('home.projects.subheading')"
        />

        @if ($projects->isEmpty())
            <div class="mt-10">
                <x-ui.empty-state
                    :message="__('home.projects.empty')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            </div>
        @else
            <ul role="list" class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    <li class="h-full"><x-sections.project-card :project="$project" /></li>
                @endforeach
            </ul>

            <div class="mt-10 text-center">
                <x-ui.button :href="route('projects.index')" variant="outline" size="md">
                    {{ __('common.cta.view_all') }}
                </x-ui.button>
            </div>
        @endif
    </div>
</section>
