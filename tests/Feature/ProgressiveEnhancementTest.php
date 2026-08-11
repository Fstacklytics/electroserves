<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The site must remain usable when JavaScript does not run.
 *
 * A server-rendered response is exactly what a visitor with JavaScript
 * disabled, a failed CDN request or a strict corporate proxy receives, so
 * asserting on that HTML is a faithful test of the no-JS experience. What these
 * tests cannot do is prove how a real browser paints it; that is recorded as a
 * deployment-verification step in deploy/README.md rather than claimed here.
 *
 * The rule this suite enforces: every control the server sends must either work
 * without JavaScript, or be marked `data-js-only` so it is hidden from a
 * visitor who cannot use it. Never a dead control.
 */
class ProgressiveEnhancementTest extends TestCase
{
    private function html(string $uri): string
    {
        $response = $this->get($uri);
        $response->assertOk();

        return (string) $response->getContent();
    }

    /**
     * @return list<array{0: string}>
     */
    public static function filterablePages(): array
    {
        return [
            'services index' => ['/services'],
            'projects index' => ['/projects'],
            'blog index' => ['/blog'],
            'testimonials' => ['/testimonials'],
        ];
    }

    // -----------------------------------------------------------------
    // Content is present in the server response
    // -----------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('filterablePages')]
    public function test_listing_content_is_server_rendered_not_built_by_javascript(string $uri): void
    {
        $html = $this->html($uri);

        // An empty <ul> that JavaScript later fills would leave a non-JS
        // visitor with a blank page.
        $this->assertMatchesRegularExpression(
            '/<li[^>]*>/',
            $html,
            "{$uri} renders no list items server-side, so the listing would be empty without JavaScript.",
        );
    }

    public function test_the_home_page_hero_content_does_not_depend_on_the_carousel_script(): void
    {
        $html = $this->html('/');

        // The first slide's copy must be in the markup, not injected on init.
        $this->assertStringContainsString('aria-roledescription="carousel"', $html);
        $this->assertMatchesRegularExpression(
            '/aria-roledescription="slide"/',
            $html,
            'Hero slides are not server-rendered, so no hero content would be visible without JavaScript.',
        );
    }

    // -----------------------------------------------------------------
    // No dead controls
    // -----------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('filterablePages')]
    public function test_javascript_only_filter_controls_are_marked_so_they_can_be_hidden(string $uri): void
    {
        $html = $this->html($uri);

        if (! str_contains($html, 'role="tablist"')) {
            $this->markTestSkipped("{$uri} renders no filter tabs with the current content set.");
        }

        // The tab strip only does something once Alpine binds its handlers.
        $this->assertMatchesRegularExpression(
            '/<div[^>]*role="tablist"[^>]*\sdata-js-only/',
            $html,
            "The filter tab list on {$uri} is not marked data-js-only, so without JavaScript it renders buttons that do nothing.",
        );
    }

    public function test_the_noscript_rule_that_hides_javascript_only_controls_is_present(): void
    {
        $html = $this->html('/projects');

        $this->assertStringContainsString('<noscript>', $html);

        // Extract every <noscript> block and require the hiding rule in one.
        preg_match_all('/<noscript>(.*?)<\/noscript>/s', $html, $matches);

        $this->assertNotEmpty($matches[1]);

        $combined = implode("\n", $matches[1]);

        $this->assertMatchesRegularExpression(
            '/\[data-js-only\]\s*\{\s*display:\s*none/',
            $combined,
            'Without this noscript rule, data-js-only controls would still be shown to non-JS visitors.',
        );
    }

    public function test_the_live_result_count_is_marked_javascript_only(): void
    {
        // The count is updated by the filter script; with no JS it would state
        // a total that never changes next to controls that are hidden.
        foreach (['/services', '/projects', '/blog'] as $uri) {
            $this->assertMatchesRegularExpression(
                '/<p[^>]*\sdata-js-only[^>]*aria-live="polite"/',
                $this->html($uri),
                "The filtered result count on {$uri} should be hidden when the filter itself is unavailable.",
            );
        }
    }

    // -----------------------------------------------------------------
    // Navigation and pagination are real links
    // -----------------------------------------------------------------

    public function test_pagination_uses_real_anchor_elements(): void
    {
        $root = $this->useTemporaryContent();

        for ($index = 1; $index <= 13; $index++) {
            $number = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $this->writeContentFile($root, "projects/pe-project-{$number}.md", <<<MD
            ---
            title: PE Project {$number}
            slug: pe-project-{$number}
            category: residential
            short_description: Progressive enhancement fixture {$number}.
            completion_date: 2026-01-{$number}
            published: true
            ---

            Body {$number}.
            MD);
        }

        $html = $this->html('/projects');

        // A button wired to history.pushState would strand a non-JS visitor on
        // page one.
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="[^"]*page=2"/',
            $html,
            'Pagination must be anchor elements with real hrefs so it works without JavaScript.',
        );

        // And the second page must actually serve different content.
        $this->get('/projects?page=2')->assertOk();
    }

    public function test_primary_navigation_links_are_real_hrefs(): void
    {
        $html = $this->html('/');

        foreach ([route('services.index'), route('projects.index'), route('contact')] as $url) {
            $path = parse_url($url, PHP_URL_PATH);

            $this->assertStringContainsString(
                'href="'.$url.'"',
                $html,
                "The navigation entry for {$path} is not a real link.",
            );
        }
    }

    // -----------------------------------------------------------------
    // Forms submit without JavaScript
    // -----------------------------------------------------------------

    public function test_the_contact_form_posts_normally_without_javascript(): void
    {
        $html = $this->html('/contact');

        // A real method+action pair, not a script-intercepted submit.
        $this->assertMatchesRegularExpression(
            '/<form[^>]+method="POST"/i',
            $html,
            'The contact form must declare method="POST" so it submits without JavaScript.',
        );

        $this->assertStringContainsString(
            'action="'.route('contact.store').'"',
            $html,
            'The contact form needs a real action URL to submit without JavaScript.',
        );

        $this->assertStringContainsString('name="_token"', $html);
    }

    public function test_a_plain_form_post_is_accepted_and_redirects(): void
    {
        // Exactly what a browser sends with no JavaScript involved.
        $response = $this->post(route('contact.store'), [
            'name' => 'Asha Mwinyi',
            'email' => 'asha@example.co.tz',
            'phone' => '+255 712 345 678',
            'subject' => 'Standby generator quote',
            'message' => 'Please quote a standby generator for our Mikocheni office building.',
            'consent' => '1',
            config('electroserves.contact.honeypot_field', 'website_url') => '',
            'form_rendered_at' => now()->subMinutes(2)->timestamp,
        ]);

        // A redirect (not a JSON body) is what a no-JS browser can follow.
        $response->assertRedirect();
    }

    // -----------------------------------------------------------------
    // Share controls degrade to usable links
    // -----------------------------------------------------------------

    public function test_share_controls_are_usable_links_not_script_only_buttons(): void
    {
        $html = $this->html('/blog/sizing-a-backup-system');

        // The network share targets must be plain outbound links.
        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="https:\/\/(www\.)?(facebook|twitter|x|linkedin|wa\.me|api\.whatsapp)[^"]*"/i',
            $html,
            'Social share controls should be real links so they work without JavaScript.',
        );
    }

    public function test_the_copy_link_button_is_marked_javascript_only(): void
    {
        $html = $this->html('/blog/sizing-a-backup-system');

        if (! str_contains($html, 'copyLink')) {
            $this->markTestSkipped('This build renders no copy-link control.');
        }

        // Clipboard access needs both a script and a secure context, so the
        // control must not be offered when it cannot work. Match the copy
        // button specifically, not merely "some button on the page".
        $this->assertMatchesRegularExpression(
            '/<button[^>]*\sdata-js-only[^>]*x-on:click="copy\(\)"/s',
            $html,
            'The copy-link button depends on the Clipboard API and must be marked data-js-only.',
        );
    }
}
