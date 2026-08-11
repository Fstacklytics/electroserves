<x-layouts.app :seo="$seo" :schema="$schema">
    <x-sections.page-header
        :title="__('faq.heading')"
        :subtitle="__('faq.subheading')"
        :breadcrumbs="$breadcrumbs"
    />

    <section class="section-spacing">
        <div class="container-page">
            @if ($groupedFaqs->isEmpty())
                <x-ui.empty-state
                    :message="__('faq.empty')"
                    :action-label="__('common.cta.contact_us')"
                    :action-href="route('contact')"
                />
            @else
                <div class="mx-auto max-w-3xl space-y-10">
                    @foreach ($groupedFaqs as $category => $faqs)
                        <section aria-labelledby="faq-group-{{ \Illuminate\Support\Str::slug($category) }}">
                            <h2
                                id="faq-group-{{ \Illuminate\Support\Str::slug($category) }}"
                                class="text-xl font-bold text-neutral-900"
                            >{{ $category }}</h2>

                            <x-ui.accordion
                                class="mt-4"
                                :items="$faqs->map(fn ($faq) => [
                                    'id' => $faq->anchorId(),
                                    'heading' => $faq->question,
                                    // Answers are Markdown rendered and sanitised by the
                                    // controller, so they are safe to emit as HTML.
                                    'content' => new \Illuminate\Support\HtmlString($answers[$faq->anchorId()] ?? ''),
                                ])->all()"
                            />
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-sections.cta-banner
        :heading="__('faq.cta.heading')"
        :body="__('faq.cta.body')"
        :button-label="__('faq.cta.button')"
        :button-href="route('contact')"
    />
</x-layouts.app>
