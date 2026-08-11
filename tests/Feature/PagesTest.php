<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every public route must respond, both with the shipped sample content and
 * with no content at all.
 */
class PagesTest extends TestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['/'],
            'services index' => ['/services'],
            'projects index' => ['/projects'],
            'blog index' => ['/blog'],
            'about' => ['/about'],
            'contact' => ['/contact'],
            'faq' => ['/faq'],
            'testimonials' => ['/testimonials'],
            'privacy policy' => ['/privacy-policy'],
            'terms' => ['/terms'],
            'sitemap' => ['/sitemap.xml'],
            'robots' => ['/robots.txt'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicRoutes')]
    public function test_every_public_route_returns_200(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    /**
     * The critical resilience case: a brand-new install with no content files.
     * Every page must still render rather than 500.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('publicRoutes')]
    public function test_every_public_route_renders_with_no_content(string $uri): void
    {
        $this->useTemporaryContent();

        $this->get($uri)->assertOk();
    }

    public function test_service_detail_renders_for_a_known_slug(): void
    {
        $this->get('/services/residential-electrical')
            ->assertOk()
            ->assertSee('Residential Electrical');
    }

    public function test_project_detail_renders_for_a_known_slug(): void
    {
        $this->get('/projects/masaki-apartment-rewire')->assertOk();
    }

    public function test_blog_post_renders_for_a_known_slug(): void
    {
        $this->get('/blog/why-your-breaker-keeps-tripping')->assertOk();
    }

    public function test_unknown_service_slug_returns_the_custom_404_page(): void
    {
        $response = $this->get('/services/no-such-service');

        $response->assertNotFound();
        // The custom error page, not Laravel's default whoops screen.
        $response->assertSee(__('errors.404.title'));
        $response->assertSee(__('errors.actions.home'));
    }

    public function test_unknown_project_slug_returns_404(): void
    {
        $this->get('/projects/no-such-project')
            ->assertNotFound()
            ->assertSee(__('errors.404.title'));
    }

    public function test_unknown_blog_slug_returns_404(): void
    {
        $this->get('/blog/no-such-post')
            ->assertNotFound()
            ->assertSee(__('errors.404.title'));
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound();
    }

    /**
     * A slug containing traversal characters must never reach the filesystem.
     */
    public function test_traversal_slug_is_rejected(): void
    {
        $this->get('/services/..%2F..%2Fconfig')->assertNotFound();
    }

    public function test_every_page_has_seo_meta_tags(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta name="twitter:card"', false);
        $response->assertSee('<link rel="canonical"', false);
    }

    public function test_every_page_has_a_skip_to_content_link(): void
    {
        foreach (['/', '/services', '/contact', '/faq'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee(__('common.skip_to_content'));
        }
    }

    public function test_pages_include_structured_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('ElectricalContractor', false);
    }

    public function test_faq_page_exposes_faq_structured_data(): void
    {
        $this->get('/faq')
            ->assertOk()
            ->assertSee('FAQPage', false);
    }

    public function test_sitemap_lists_content_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('services.show', 'residential-electrical'), false);
    }

    public function test_robots_disallows_indexing_outside_production(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /');
    }

    public function test_styleguide_is_available_outside_production(): void
    {
        $this->get('/styleguide')->assertOk();
    }
}
