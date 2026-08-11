<x-layouts.app :seo="$seo" :schema="$schema">
    <x-sections.hero :slides="$slides" />

    <x-sections.services-grid :services="$services" :total="$totalServices" />

    <x-sections.featured-projects :projects="$featuredProjects" />

    <x-sections.stats :settings="$settings" />

    <x-sections.testimonials-carousel :testimonials="$testimonials" />

    <x-sections.cta-banner
        :heading="__('home.cta.heading')"
        :body="__('home.cta.body')"
        :button-label="__('home.cta.button')"
        :button-href="route('contact')"
        :secondary-label="__('home.cta.secondary')"
        :secondary-href="route('services.index')"
    />
</x-layouts.app>
