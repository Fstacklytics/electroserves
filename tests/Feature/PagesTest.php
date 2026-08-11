<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
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

    public function test_project_index_renders_the_second_page_at_twelve_items_per_page(): void
    {
        $root = $this->useTemporaryContent();
        $this->writeProjectFixtures($root, 13);

        $this->get('/projects?page=2')
            ->assertOk()
            ->assertSee('Paginated Project 01')
            ->assertDontSee('Paginated Project 13')
            ->assertSee('<link rel="canonical" href="'.route('projects.index', ['page' => 2]).'">', false)
            ->assertViewHas('projects', static fn (mixed $projects): bool => $projects instanceof LengthAwarePaginator
                && $projects->perPage() === 12
                && $projects->currentPage() === 2
                && $projects->total() === 13
                && $projects->count() === 1);
    }

    public function test_blog_index_renders_the_second_page_at_six_items_per_page(): void
    {
        $root = $this->useTemporaryContent();
        $this->writeBlogFixtures($root, 7);

        $this->get('/blog?page=2')
            ->assertOk()
            ->assertSee('Paginated Article 01')
            ->assertDontSee('Paginated Article 07')
            ->assertSee('<link rel="canonical" href="'.route('blog.index', ['page' => 2]).'">', false)
            ->assertViewHas('posts', static fn (mixed $posts): bool => $posts instanceof LengthAwarePaginator
                && $posts->perPage() === 6
                && $posts->currentPage() === 2
                && $posts->total() === 7
                && $posts->count() === 1);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function paginatedIndexes(): array
    {
        return [
            'projects' => ['/projects'],
            'blog' => ['/blog'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paginatedIndexes')]
    public function test_an_out_of_range_index_page_returns_the_custom_404(string $uri): void
    {
        $this->useTemporaryContent();

        $this->get($uri.'?page=2')
            ->assertNotFound()
            ->assertSee(__('errors.404.title'));
    }

    public function test_category_filters_persist_in_project_and_blog_pagination_links(): void
    {
        $root = $this->useTemporaryContent();
        $this->writeProjectFixtures($root, 13);
        $this->writeBlogFixtures($root, 7);

        $this->get('/projects?category=residential')
            ->assertOk()
            ->assertViewHas('activeCategory', 'residential')
            ->assertSee('category=residential&amp;page=2', false)
            ->assertSee('data-pagination-link', false)
            ->assertSee("queryParam: 'category'", false);

        $this->get('/blog?category=Safety')
            ->assertOk()
            ->assertViewHas('activeCategory', 'Safety')
            ->assertSee('category=Safety&amp;page=2', false)
            ->assertSee('data-pagination-link', false)
            ->assertSee("queryParam: 'category'", false);
    }

    public function test_blog_post_has_generated_toc_and_complete_share_controls(): void
    {
        $this->get('/blog/why-your-breaker-keeps-tripping')
            ->assertOk()
            ->assertSee('id="overload"', false)
            ->assertSee('href="#overload"', false)
            ->assertSee('twitter.com/intent/tweet', false)
            ->assertSee('facebook.com/sharer/sharer.php', false)
            ->assertSee('linkedin.com/sharing/share-offsite', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('x-data="copyLink(', false)
            ->assertSee('role="status"', false);
    }

    public function test_project_lightbox_exposes_escape_and_arrow_key_controls(): void
    {
        $root = $this->useTemporaryContent();
        $this->writeContentFile($root, 'projects/keyboard-gallery.md', <<<'MD'
---
title: Keyboard Gallery
slug: keyboard-gallery
category: residential
short_description: A gallery fixture for keyboard interaction coverage.
gallery:
  - image: /images/gallery-one.jpg
    caption: First gallery image
  - image: /images/gallery-two.jpg
    caption: Second gallery image
completion_date: 2026-01-01
published: true
---

Project description.
MD);

        $this->get('/projects/keyboard-gallery')
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('x-trap.noscroll="open"', false)
            ->assertSee('x-on:keydown.escape.window="close()"', false)
            ->assertSee('x-on:keydown.arrow-right.prevent="next()"', false)
            ->assertSee('x-on:keydown.arrow-left.prevent="previous()"', false);
    }

    public function test_service_quote_cta_links_to_contact_with_the_service_slug(): void
    {
        $this->get('/services/residential-electrical')
            ->assertOk()
            ->assertSee(route('contact', ['service' => 'residential-electrical']), false);
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

    private function writeProjectFixtures(string $root, int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $number = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $category = $index % 2 === 0 ? 'commercial' : 'residential';
            $content = <<<MD
---
title: Paginated Project {$number}
slug: paginated-project-{$number}
category: {$category}
short_description: Pagination fixture project {$number}.
completion_date: 2026-01-{$number}
published: true
---

Project {$number} description.
MD;

            $this->writeContentFile($root, "projects/paginated-project-{$number}.md", $content);
        }
    }

    private function writeBlogFixtures(string $root, int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $number = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $category = $index % 2 === 0 ? 'Tips & Advice' : 'Safety';
            $content = <<<MD
---
title: Paginated Article {$number}
slug: paginated-article-{$number}
author: Test Author
date: 2026-02-{$number}
category: {$category}
excerpt: Pagination fixture article {$number}.
published: true
---

Article {$number} body.
MD;

            $this->writeContentFile($root, "blog/2026-02-{$number}-paginated-article-{$number}.md", $content);
        }
    }
}
