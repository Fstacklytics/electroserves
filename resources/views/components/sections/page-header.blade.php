@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<section class="border-b border-neutral-200 bg-neutral-50">
    <div class="container-page">
        <x-common.breadcrumb :crumbs="$breadcrumbs" />

        <div class="max-w-3xl pb-10 pt-2 sm:pb-14">
            <h1 class="text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">{{ $title }}</h1>

            @if ($subtitle)
                <p class="mt-4 text-lg leading-relaxed text-neutral-600">{{ $subtitle }}</p>
            @endif

            {{ $slot }}
        </div>
    </div>
</section>
