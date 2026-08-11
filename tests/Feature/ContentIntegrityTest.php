<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ContentService;
use Tests\TestCase;

/**
 * Guards the shipped content in content/ against silent degradation.
 *
 * ContentServiceTest already proves the *parser* survives malformed input.
 * These tests are about the opposite risk: content that parses perfectly but
 * should never have reached production — a collection quietly dropping below
 * the agreed minimum, an entry that renders as an empty card because an
 * optional-looking field was left blank, a stray "TODO" in published prose, or
 * a reference to an external placeholder-image service.
 *
 * They run against the REAL content directory rather than the temporary
 * fixtures the other suites use, because the point is to check what actually
 * ships.
 *
 * Scope note: these are structural and hygiene checks. Whether the copy is
 * accurate about Tanzanian electrical practice is an editorial judgement that
 * no test can make; that was verified by reading every file, and is recorded
 * in docs/PHASE-4-GAP-MATRIX.md §4.6.
 */
class ContentIntegrityTest extends TestCase
{
    /**
     * Minimum entries per collection, from the Phase 0 content plan.
     *
     * These are floors, not targets — adding content is always fine. The test
     * exists so that a deletion, a bad merge, or a `published: false` flipped
     * during editing cannot quietly shrink the site.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function collectionMinimums(): array
    {
        // Keyed by the ContentService accessor that exposes the collection, so
        // the test calls the same API the controllers do.
        return [
            'services' => ['services', 6],
            'projects' => ['projects', 6],
            'blog posts' => ['blogPosts', 4],
            'testimonials' => ['testimonials', 6],
            'team members' => ['teamMembers', 5],
            'FAQs' => ['faqs', 10],
            'hero slides' => ['heroSlides', 3],
        ];
    }

    private function content(): ContentService
    {
        return app(ContentService::class);
    }

    /**
     * Raw text of every shipped content file, keyed by path relative to content/.
     *
     * @return array<string, string>
     */
    private function contentFiles(): array
    {
        $root = (string) config('electroserves.content_path');

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! in_array($file->getExtension(), ['md', 'yml', 'yaml'], true)) {
                continue;
            }

            $relative = str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $files[$relative] = (string) file_get_contents($file->getPathname());
        }

        return $files;
    }

    public function test_the_content_directory_exists_where_the_application_expects_it(): void
    {
        $this->assertDirectoryExists(
            (string) config('electroserves.content_path'),
            'The configured content_path does not exist.'
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('collectionMinimums')]
    public function test_each_collection_meets_its_minimum(string $accessor, int $minimum): void
    {
        $entries = $this->content()->{$accessor}();

        $this->assertGreaterThanOrEqual(
            $minimum,
            $entries->count(),
            "ContentService::{$accessor}() returns {$entries->count()} published entries but the site is specified to ship at least {$minimum}."
        );
    }

    public function test_no_shipped_content_contains_filler_or_editorial_leftovers(): void
    {
        // Deliberately narrow: each of these means "someone was not finished".
        $forbidden = [
            'lorem ipsum' => 'placeholder Latin',
            'dolor sit amet' => 'placeholder Latin',
            'todo' => 'an unfinished note',
            'fixme' => 'an unfinished note',
            'tbd' => 'an unfinished note',
            'xxx' => 'an unfinished note',
            'john doe' => 'a dummy name',
            'jane doe' => 'a dummy name',
            'your text here' => 'template boilerplate',
            'sample text' => 'template boilerplate',
        ];

        foreach ($this->contentFiles() as $path => $body) {
            $haystack = strtolower($body);

            foreach ($forbidden as $needle => $why) {
                $this->assertStringNotContainsString(
                    $needle,
                    $haystack,
                    "content/{$path} contains \"{$needle}\" ({$why})."
                );
            }
        }
    }

    public function test_no_content_references_an_external_placeholder_image_service(): void
    {
        // An external placeholder would break the CSP (img-src 'self' data:),
        // leak visitor IPs to a third party, and disappear without warning.
        $services = [
            'placehold.co',
            'placeholder.com',
            'placekitten',
            'dummyimage',
            'loremflickr',
            'picsum.photos',
            'unsplash.com',
            'lorempixel',
        ];

        foreach ($this->contentFiles() as $path => $body) {
            foreach ($services as $service) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $service,
                    $body,
                    "content/{$path} references the external image service {$service}."
                );
            }
        }
    }

    public function test_no_content_hotlinks_a_remote_image(): void
    {
        // Images belong in content/uploads and are served from this origin.
        foreach ($this->contentFiles() as $path => $body) {
            $this->assertDoesNotMatchRegularExpression(
                '/https?:\/\/[^\s"\')]+\.(?:jpe?g|png|gif|webp|avif|svg)/i',
                $body,
                "content/{$path} points at a remote image; img-src is 'self' data: so it would not render."
            );
        }
    }

    public function test_every_service_carries_the_fields_the_cards_and_pages_render(): void
    {
        foreach ($this->content()->services() as $service) {
            $label = $service->slug ?: 'unknown';

            $this->assertNotSame('', trim($service->title), "Service {$label} has no title.");
            $this->assertNotSame('', trim($service->slug), 'A service has no slug.');
            $this->assertNotSame(
                '',
                trim($service->shortDescription),
                "Service {$label} has no short_description, so its card would render empty."
            );
            $this->assertNotEmpty(
                $service->features,
                "Service {$label} lists no features."
            );
        }
    }

    public function test_every_project_carries_the_fields_the_case_study_renders(): void
    {
        foreach ($this->content()->projects() as $project) {
            $label = $project->slug ?: 'unknown';

            $this->assertNotSame('', trim($project->title), "Project {$label} has no title.");
            $this->assertNotSame('', trim($project->slug), 'A project has no slug.');
            $this->assertNotSame(
                '',
                trim($project->shortDescription),
                "Project {$label} has no short_description."
            );
            $this->assertNotSame(
                '',
                trim((string) $project->category),
                "Project {$label} has no category, so it cannot be filtered."
            );
        }
    }

    public function test_every_blog_post_has_a_title_slug_and_date(): void
    {
        foreach ($this->content()->blogPosts() as $post) {
            $label = $post->slug ?: 'unknown';

            $this->assertNotSame('', trim($post->title), "Post {$label} has no title.");
            $this->assertNotSame('', trim($post->slug), 'A post has no slug.');
            $this->assertNotNull($post->date, "Post {$label} has no date, so ordering is undefined.");
        }
    }

    public function test_no_blog_post_is_dated_in_the_future(): void
    {
        // A future date would sort a post to the top of the index and read as
        // a mistake to any visitor who notices.
        foreach ($this->content()->blogPosts() as $post) {
            $this->assertLessThanOrEqual(
                now()->endOfDay()->getTimestamp(),
                $post->date->getTimestamp(),
                "Post {$post->slug} is dated {$post->date->format('Y-m-d')}, which is in the future."
            );
        }
    }

    public function test_every_testimonial_has_an_attributable_quote(): void
    {
        foreach ($this->content()->testimonials() as $testimonial) {
            $this->assertNotSame(
                '',
                trim($testimonial->quote),
                'A testimonial has no quote.'
            );

            // An unattributed testimonial is worth less than none at all.
            $this->assertNotSame(
                '',
                trim($testimonial->clientName),
                'A testimonial is not attributed to anyone.'
            );

            $this->assertGreaterThanOrEqual(1, $testimonial->rating);
            $this->assertLessThanOrEqual(5, $testimonial->rating);
        }
    }

    public function test_every_faq_has_both_a_question_and_an_answer(): void
    {
        foreach ($this->content()->faqs() as $faq) {
            $this->assertNotSame('', trim($faq->question), 'An FAQ has no question.');
            $this->assertNotSame(
                '',
                trim($faq->answer),
                "FAQ \"{$faq->question}\" has no answer, so the accordion would open onto nothing."
            );
        }
    }

    public function test_every_team_member_has_a_name_and_role(): void
    {
        foreach ($this->content()->teamMembers() as $member) {
            $this->assertNotSame('', trim($member->name), 'A team member has no name.');
            $this->assertNotSame(
                '',
                trim($member->role),
                "Team member {$member->name} has no role."
            );
        }
    }

    public function test_every_hero_slide_has_a_heading_and_a_working_call_to_action(): void
    {
        foreach ($this->content()->heroSlides() as $slide) {
            $this->assertNotSame('', trim($slide->heading), 'A hero slide has no heading.');

            // A CTA label with no link (or the reverse) renders a dead or
            // unlabelled button in the most prominent place on the site.
            $hasLabel = trim((string) $slide->ctaText) !== '';
            $hasUrl = trim((string) $slide->ctaLink) !== '';

            $this->assertSame(
                $hasLabel,
                $hasUrl,
                "Hero slide \"{$slide->heading}\" has a CTA label without a URL or a URL without a label."
            );
        }
    }

    public function test_slugs_are_url_safe_and_unique_within_each_collection(): void
    {
        foreach (['services', 'projects', 'blogPosts'] as $collection) {
            $slugs = [];

            foreach ($this->content()->{$collection}() as $entry) {
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    $entry->slug,
                    "Slug `{$entry->slug}` in {$collection} is not a clean URL segment."
                );

                $slugs[] = $entry->slug;
            }

            $duplicates = array_keys(array_filter(array_count_values($slugs), fn (int $n): bool => $n > 1));

            $this->assertSame(
                [],
                $duplicates,
                "Duplicate slug(s) in {$collection}: ".implode(', ', $duplicates).' — one entry would shadow the other.'
            );
        }
    }

    public function test_contact_details_are_consistent_across_the_site(): void
    {
        $settings = $this->content()->siteSettings();

        $this->assertNotSame('', trim($settings->email), 'Site settings define no email address.');
        $this->assertNotSame('', trim($settings->phone), 'Site settings define no phone number.');

        // The company is Tanzanian; a foreign dialling code would be a
        // copy-paste from a template.
        $this->assertStringStartsWith(
            '+255',
            preg_replace('/\s+/', '', $settings->phone) ?? '',
            'The primary phone number should use the Tanzanian country code.'
        );

        $this->assertStringNotContainsStringIgnoringCase(
            'example.com',
            $settings->email,
            'Site settings still use an example.com address.'
        );
    }
}
