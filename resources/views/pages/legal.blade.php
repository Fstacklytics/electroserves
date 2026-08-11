<x-layouts.app :seo="$seo" :schema="$schema">
    <x-sections.page-header
        :title="$page?->title ?? $fallbackTitle"
        :breadcrumbs="$breadcrumbs"
    >
        @if ($page?->updatedAt)
            <p class="mt-3 text-sm text-neutral-500">
                <time datetime="{{ $page->updatedAtIso() }}">
                    {{ __('legal.last_updated', ['date' => $page->formattedUpdatedAt()]) }}
                </time>
            </p>
        @endif
    </x-sections.page-header>

    <section class="section-spacing">
        <div class="container-page">
            @if ($bodyHtml !== '')
                <div class="prose prose-neutral mx-auto max-w-prose">{!! $bodyHtml !!}</div>
            @else
                {{-- A legal page must never 404; it explains itself and offers a route out. --}}
                <x-ui.empty-state
                    :message="__('legal.missing_body')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            @endif
        </div>
    </section>
</x-layouts.app>
