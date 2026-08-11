<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Structural accessibility guarantees that must hold on every public page.
 *
 * These are the checks that can be made from the rendered HTML. They do not
 * replace a manual keyboard and screen reader pass, but they stop the common
 * regressions — an unlabelled input, a missing landmark, a duplicate id — from
 * reaching a review.
 */
class AccessibilityTest extends TestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'services index' => ['/services'],
            'service detail' => ['/services/residential-electrical'],
            'projects index' => ['/projects'],
            'project detail' => ['/projects/kigamboni-solar-backup'],
            'blog index' => ['/blog'],
            'blog post' => ['/blog/sizing-a-backup-system'],
            'about' => ['/about'],
            'contact' => ['/contact'],
            'faq' => ['/faq'],
            'testimonials' => ['/testimonials'],
            'privacy policy' => ['/privacy-policy'],
            'terms' => ['/terms'],
        ];
    }

    private function html(string $uri): string
    {
        $response = $this->get($uri);
        $response->assertOk();

        return $response->getContent();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_page_declares_a_language(string $uri): void
    {
        $this->assertMatchesRegularExpression('/<html[^>]+lang="[a-z]{2}(-[A-Za-z]+)?"/', $this->html($uri));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_page_has_exactly_one_h1(string $uri): void
    {
        $count = substr_count($this->html($uri), '<h1');

        $this->assertSame(1, $count, "Expected exactly one <h1> on {$uri}, found {$count}.");
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_page_has_the_required_landmarks(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('<main id="main-content"', $html);
        $this->assertStringContainsString('<footer', $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_page_has_a_working_skip_link(string $uri): void
    {
        $html = $this->html($uri);

        $this->assertStringContainsString('href="#main-content"', $html);
        $this->assertStringContainsString('id="main-content"', $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_no_page_contains_duplicate_element_ids(string $uri): void
    {
        preg_match_all('/\sid="([^"]+)"/', $this->html($uri), $matches);

        $ids = $matches[1];
        $duplicates = array_keys(array_filter(array_count_values($ids), static fn (int $n): bool => $n > 1));

        $this->assertSame(
            [],
            $duplicates,
            "Duplicate id(s) on {$uri}: ".implode(', ', $duplicates),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_image_has_an_alt_attribute(string $uri): void
    {
        preg_match_all('/<img\b[^>]*>/i', $this->html($uri), $matches);

        foreach ($matches[0] as $tag) {
            $this->assertStringContainsString(
                'alt=',
                $tag,
                "An <img> without an alt attribute on {$uri}: {$tag}",
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_image_declares_intrinsic_dimensions(string $uri): void
    {
        // Explicit width/height reserve space and keep CLS at zero.
        preg_match_all('/<img\b[^>]*>/i', $this->html($uri), $matches);

        foreach ($matches[0] as $tag) {
            $this->assertMatchesRegularExpression('/\bwidth="\d+"/', $tag, "Missing width: {$tag}");
            $this->assertMatchesRegularExpression('/\bheight="\d+"/', $tag, "Missing height: {$tag}");
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_inline_svg_is_hidden_from_assistive_technology_or_labelled(string $uri): void
    {
        preg_match_all('/<svg\b[^>]*>/i', $this->html($uri), $matches);

        foreach ($matches[0] as $tag) {
            $this->assertTrue(
                str_contains($tag, 'aria-hidden="true"')
                    || str_contains($tag, 'aria-label')
                    || str_contains($tag, 'role="img"'),
                "An <svg> that is neither hidden nor labelled on {$uri}: {$tag}",
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_every_external_link_is_safe(string $uri): void
    {
        preg_match_all('/<a\b[^>]*target="_blank"[^>]*>/i', $this->html($uri), $matches);

        foreach ($matches[0] as $tag) {
            $this->assertStringContainsString(
                'rel="noopener noreferrer"',
                $tag,
                "A target=\"_blank\" link without rel=\"noopener noreferrer\" on {$uri}: {$tag}",
            );
        }
    }

    public function test_every_form_control_on_the_contact_page_has_a_label(): void
    {
        $html = $this->html('/contact');

        // Collect the ids of every labelled control.
        preg_match_all('/<label[^>]*\sfor="([^"]+)"/s', $html, $labelMatches);
        $labelled = $labelMatches[1];

        preg_match_all('/<(input|select|textarea)\b[^>]*>/i', $html, $controlMatches);

        foreach ($controlMatches[0] as $control) {
            // Hidden inputs (CSRF) carry no user-facing label.
            if (preg_match('/type="hidden"/i', $control)) {
                continue;
            }

            preg_match('/\sid="([^"]+)"/', $control, $idMatch);

            $this->assertNotEmpty($idMatch, "A form control without an id, so it cannot be labelled: {$control}");
            $this->assertContains(
                $idMatch[1],
                $labelled,
                "No <label for> matches the control with id [{$idMatch[1]}].",
            );
        }
    }

    public function test_the_contact_form_marks_required_fields_for_screen_readers(): void
    {
        $html = $this->html('/contact');

        // The red asterisk is decorative; the word is what gets announced.
        $this->assertStringContainsString('aria-hidden="true">*', $html);
        $this->assertStringContainsString('(required)', $html);
    }

    public function test_interactive_controls_meet_the_minimum_touch_target(): void
    {
        $html = $this->html('/');

        // Every button and nav link uses one of the sanctioned sizing classes.
        preg_match_all('/<(?:button|a)\b[^>]*class="([^"]*)"[^>]*>/i', $html, $matches);

        $sized = 0;

        foreach ($matches[1] as $classes) {
            if (str_contains($classes, 'min-h-touch')
                || str_contains($classes, 'touch-target')
                || str_contains($classes, 'min-h-[3rem]')
                || str_contains($classes, 'h-touch')) {
                $sized++;
            }
        }

        // The homepage has many such controls; assert the convention is in use
        // rather than asserting on an exact count that would be brittle.
        $this->assertGreaterThan(10, $sized, 'Interactive controls are not using the touch-target sizing classes.');
    }

    public function test_the_carousel_exposes_its_role_and_controls(): void
    {
        $html = $this->html('/');

        $this->assertStringContainsString('aria-roledescription="carousel"', $html);
        $this->assertStringContainsString('aria-roledescription="slide"', $html);
        // Playback can be paused, which WCAG 2.2.2 requires for moving content.
        $this->assertStringContainsString(__('home.hero.pause'), $html);
        $this->assertStringContainsString(__('home.hero.previous_slide'), $html);
        $this->assertStringContainsString(__('home.hero.next_slide'), $html);
    }

    public function test_filter_tabs_expose_the_wai_aria_pattern(): void
    {
        $html = $this->html('/services');

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('aria-selected', $html);
    }

    public function test_dynamic_result_counts_are_announced(): void
    {
        foreach (['/services', '/projects', '/blog'] as $uri) {
            $this->assertStringContainsString(
                'aria-live="polite"',
                $this->html($uri),
                "The filtered result count on {$uri} is not announced.",
            );
        }
    }

    public function test_error_pages_keep_navigation_available(): void
    {
        $html = $this->get('/no-such-page')->getContent();

        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('<footer', $html);
        $this->assertStringContainsString('href="#main-content"', $html);
        $this->assertStringContainsString(__('errors.actions.home'), $html);
    }
}
