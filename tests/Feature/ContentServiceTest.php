<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ContentService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ContentService must never throw to the controller layer.
 *
 * These tests cover the three failure modes named in the project rules:
 * missing files, malformed files, and files missing required fields.
 */
class ContentServiceTest extends TestCase
{
    private function service(): ContentService
    {
        return app(ContentService::class);
    }

    // -----------------------------------------------------------------
    // Missing content
    // -----------------------------------------------------------------

    public function test_missing_collection_directory_yields_an_empty_collection(): void
    {
        $root = $this->useTemporaryContent();
        File::deleteDirectory($root.'/services');

        $services = $this->service()->services();

        $this->assertTrue($services->isEmpty());
    }

    public function test_missing_settings_file_falls_back_to_safe_defaults(): void
    {
        $this->useTemporaryContent();

        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $settings = $this->service()->siteSettings();

        // The object is still usable, so the navbar and footer render.
        $this->assertNotSame('', $settings->siteName);
        $this->assertSame('Dar es Salaam', $settings->city);
        $this->assertSame([], $settings->socialLinks);
    }

    public function test_missing_page_returns_null_rather_than_throwing(): void
    {
        $this->useTemporaryContent();

        $this->assertNull($this->service()->page('about'));
    }

    public function test_unknown_slug_lookups_return_null(): void
    {
        $service = $this->service();

        $this->assertNull($service->findService('not-a-real-service'));
        $this->assertNull($service->findProject('not-a-real-project'));
        $this->assertNull($service->findBlogPost('not-a-real-post'));
    }

    // -----------------------------------------------------------------
    // Malformed content
    // -----------------------------------------------------------------

    public function test_malformed_yaml_is_logged_and_skipped(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'testimonials/broken.yml', <<<'YAML'
        client_name: "Unterminated quote
          rating: [1, 2
        quote: >-
        YAML);

        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        // Must not throw, and must not include the broken entry.
        $this->assertTrue($this->service()->testimonials()->isEmpty());
    }

    public function test_a_malformed_entry_does_not_prevent_valid_entries_loading(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'testimonials/broken.yml', "client_name: \"unterminated\n  bad: [1,\n");
        $this->writeContentFile($root, 'testimonials/good.yml', <<<'YAML'
        client_name: Valid Client
        quote: This entry is well formed and must still be rendered.
        rating: 5
        order: 1
        published: true
        YAML);

        $testimonials = $this->service()->testimonials();

        $this->assertCount(1, $testimonials);
        $this->assertSame('Valid Client', $testimonials->first()->clientName);
    }

    public function test_malformed_markdown_frontmatter_is_skipped(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'services/broken.md', <<<'MD'
        ---
        title: "Broken
        slug: [unclosed
        ---

        Body text.
        MD);

        $this->assertTrue($this->service()->services()->isEmpty());
    }

    public function test_yaml_that_is_not_a_mapping_is_rejected(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'faqs/scalar.yml', 'just a plain string');

        $this->assertTrue($this->service()->faqs()->isEmpty());
    }

    // -----------------------------------------------------------------
    // Incomplete content
    // -----------------------------------------------------------------

    public function test_entry_missing_required_fields_is_skipped(): void
    {
        $root = $this->useTemporaryContent();

        // No `quote`, which Testimonial requires.
        $this->writeContentFile($root, 'testimonials/incomplete.yml', <<<'YAML'
        client_name: Someone
        rating: 5
        YAML);

        $this->assertTrue($this->service()->testimonials()->isEmpty());
    }

    public function test_service_missing_short_description_is_skipped(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'services/incomplete.md', <<<'MD'
        ---
        title: Incomplete Service
        slug: incomplete-service
        ---

        Body without a short description.
        MD);

        $this->assertTrue($this->service()->services()->isEmpty());
    }

    public function test_missing_optional_fields_are_tolerated(): void
    {
        $root = $this->useTemporaryContent();

        // Only the required fields; everything else omitted.
        $this->writeContentFile($root, 'services/minimal.md', <<<'MD'
        ---
        title: Minimal Service
        slug: minimal-service
        short_description: The bare minimum set of fields.
        ---
        MD);

        $service = $this->service()->services()->first();

        $this->assertNotNull($service);
        $this->assertSame('Minimal Service', $service->title);
        $this->assertNull($service->priceRange);
        $this->assertNull($service->image);
        $this->assertSame([], $service->features);
        // Defaults are applied rather than left null.
        $this->assertTrue($service->published);
        $this->assertNotSame('', $service->icon);
    }

    public function test_unrecognised_category_falls_back_to_a_default(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'services/odd-category.md', <<<'MD'
        ---
        title: Odd Category
        slug: odd-category
        short_description: Has a category that is not in the allowed list.
        category: interdimensional
        ---
        MD);

        $service = $this->service()->services()->first();

        $this->assertNotNull($service);
        $this->assertContains(
            $service->category,
            array_keys((array) config('electroserves.service_categories')),
        );
    }

    public function test_unparseable_date_does_not_break_the_entry(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'projects/bad-date.md', <<<'MD'
        ---
        title: Bad Date Project
        slug: bad-date-project
        short_description: The completion date cannot be parsed.
        completion_date: "not a date at all"
        ---
        MD);

        $project = $this->service()->projects()->first();

        $this->assertNotNull($project);
        $this->assertNull($project->completionDate);
        $this->assertNull($project->formattedCompletionDate());
    }

    // -----------------------------------------------------------------
    // Filtering, ordering and relationships
    // -----------------------------------------------------------------

    public function test_unpublished_entries_are_excluded(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'services/draft.md', <<<'MD'
        ---
        title: Draft Service
        slug: draft-service
        short_description: Should never appear on the site.
        published: false
        ---
        MD);

        $this->assertTrue($this->service()->services()->isEmpty());
    }

    public function test_services_are_ordered_by_the_order_field(): void
    {
        $root = $this->useTemporaryContent();

        foreach ([['third', 3], ['first', 1], ['second', 2]] as [$slug, $order]) {
            $this->writeContentFile($root, "services/{$slug}.md", <<<MD
            ---
            title: {$slug}
            slug: {$slug}
            short_description: Ordering fixture.
            order: {$order}
            ---
            MD);
        }

        $this->assertSame(
            ['first', 'second', 'third'],
            $this->service()->services()->pluck('slug')->all(),
        );
    }

    public function test_blog_posts_are_ordered_newest_first(): void
    {
        $root = $this->useTemporaryContent();

        foreach ([['old', '2024-01-01'], ['new', '2026-01-01'], ['middle', '2025-01-01']] as [$slug, $date]) {
            $this->writeContentFile($root, "blog/{$date}-{$slug}.md", <<<MD
            ---
            title: {$slug}
            slug: {$slug}
            excerpt: Ordering fixture for blog posts.
            date: {$date}
            ---
            MD);
        }

        $this->assertSame(
            ['new', 'middle', 'old'],
            $this->service()->blogPosts()->pluck('slug')->all(),
        );
    }

    public function test_blog_slug_is_derived_from_the_filename_without_the_date_prefix(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'blog/2026-05-04-a-dated-post.md', <<<'MD'
        ---
        title: A Dated Post
        excerpt: The slug should not include the date prefix.
        date: 2026-05-04
        ---
        MD);

        $post = $this->service()->blogPosts()->first();

        $this->assertNotNull($post);
        $this->assertSame('a-dated-post', $post->slug);
    }

    public function test_projects_for_service_matches_on_the_service_slug(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'projects/linked.md', <<<'MD'
        ---
        title: Linked Project
        slug: linked-project
        short_description: References a service.
        services_used:
          - rewiring
        ---
        MD);

        $this->assertCount(1, $this->service()->projectsForService('rewiring'));
        $this->assertCount(0, $this->service()->projectsForService('something-else'));
    }

    public function test_faqs_are_grouped_by_category(): void
    {
        $grouped = $this->service()->faqsByCategory();

        $this->assertGreaterThan(0, $grouped->count());

        foreach ($grouped as $category => $faqs) {
            $this->assertIsString($category);
            $this->assertGreaterThan(0, $faqs->count());
        }
    }

    public function test_active_categories_only_include_categories_in_use(): void
    {
        $root = $this->useTemporaryContent();

        $this->writeContentFile($root, 'services/only-one.md', <<<'MD'
        ---
        title: Only Residential
        slug: only-residential
        short_description: The single published service.
        category: residential
        ---
        MD);

        $this->assertSame(['residential'], array_keys($this->service()->activeServiceCategories()));
    }

    // -----------------------------------------------------------------
    // Path safety
    // -----------------------------------------------------------------

    public function test_slugs_containing_traversal_are_rejected(): void
    {
        $service = $this->service();

        $this->assertNull($service->findService('../../../etc/passwd'));
        $this->assertNull($service->findProject('..'));
        $this->assertNull($service->page('../settings/site'));
        $this->assertNull($service->findBlogPost(''));
    }

    // -----------------------------------------------------------------
    // Caching
    // -----------------------------------------------------------------

    public function test_results_are_cached_when_caching_is_enabled(): void
    {
        $root = $this->useTemporaryContent();
        config(['electroserves.cache.enabled' => true]);

        $this->writeContentFile($root, 'services/cached.md', <<<'MD'
        ---
        title: Cached Service
        slug: cached-service
        short_description: Written before the first read.
        ---
        MD);

        $service = app(ContentService::class);
        $this->assertCount(1, $service->services());

        // Add a second file without flushing: the cached result must be reused.
        File::put($root.'/services/second.md', "---\ntitle: Second\nslug: second\nshort_description: Added after caching.\n---\n");

        $this->assertCount(1, $service->services());

        // After an explicit flush the new file is picked up.
        $service->flush();
        $this->assertCount(2, $service->services());
    }
}
