<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MarkdownService;
use Tests\TestCase;

/**
 * Markdown rendered with {!! !!} must be safe.
 *
 * Covers threat T2 (content injection via a compromised CMS account) from
 * docs/phase-0/08-threat-model.md.
 */
class MarkdownServiceTest extends TestCase
{
    private MarkdownService $markdown;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markdown = app(MarkdownService::class);
    }

    public function test_it_renders_basic_markdown(): void
    {
        $html = $this->markdown->toHtml("# Heading\n\nSome **bold** text.");

        $this->assertStringContainsString('<h1>Heading</h1>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_empty_input_produces_empty_output(): void
    {
        $this->assertSame('', $this->markdown->toHtml(''));
        $this->assertSame('', $this->markdown->toHtml("   \n  "));
    }

    // -----------------------------------------------------------------
    // Sanitisation
    // -----------------------------------------------------------------

    public function test_script_tags_are_stripped(): void
    {
        $html = $this->markdown->toHtml("Before\n\n<script>alert('xss')</script>\n\nAfter");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(', $html);
    }

    public function test_iframes_are_stripped(): void
    {
        $html = $this->markdown->toHtml('<iframe src="https://evil.example.com"></iframe>');

        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_inline_event_handlers_are_stripped(): void
    {
        $html = $this->markdown->toHtml('<img src="x" onerror="alert(1)">');

        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_javascript_urls_are_not_emitted_as_links(): void
    {
        $html = $this->markdown->toHtml('[Click me](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_safe_links_survive(): void
    {
        $html = $this->markdown->toHtml('[Our services](https://electroserves.co.tz/services)');

        $this->assertStringContainsString('href="https://electroserves.co.tz/services"', $html);
    }

    public function test_external_links_are_given_noopener_noreferrer(): void
    {
        $html = $this->markdown->toHtml('[External](https://example.com/page)');

        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    // -----------------------------------------------------------------
    // Headings and anchors
    // -----------------------------------------------------------------

    public function test_it_extracts_h2_and_h3_headings(): void
    {
        $headings = $this->markdown->extractHeadings(<<<'MD'
        # Title

        ## First Section

        Text.

        ### A Subsection

        ## Second Section
        MD);

        $this->assertCount(3, $headings);
        $this->assertSame('First Section', $headings[0]['text']);
        $this->assertSame(2, $headings[0]['level']);
        $this->assertSame(3, $headings[1]['level']);
        $this->assertSame('first-section', $headings[0]['id']);
    }

    public function test_headings_inside_code_fences_are_ignored(): void
    {
        $headings = $this->markdown->extractHeadings(<<<'MD'
        ## Real Heading

        ```bash
        ## This is a shell comment, not a heading
        ```
        MD);

        $this->assertCount(1, $headings);
        $this->assertSame('Real Heading', $headings[0]['text']);
    }

    public function test_duplicate_headings_receive_unique_ids(): void
    {
        $headings = $this->markdown->extractHeadings("## Overview\n\n## Overview");

        $this->assertNotSame($headings[0]['id'], $headings[1]['id']);
    }

    public function test_rendered_headings_carry_matching_anchor_ids(): void
    {
        $markdown = "## First Section\n\nText.\n\n### Nested Heading";
        $html = $this->markdown->toHtmlWithAnchors($markdown);

        $this->assertStringContainsString('<h2 id="first-section">', $html);
        $this->assertStringContainsString('<h3 id="nested-heading">', $html);
    }

    public function test_slugify_handles_punctuation_and_case(): void
    {
        $this->assertSame('whats-included', $this->markdown->slugify("What's Included?"));
        $this->assertSame('section', $this->markdown->slugify('!!!'));
    }

    public function test_tables_are_supported(): void
    {
        $html = $this->markdown->toHtml("| A | B |\n|---|---|\n| 1 | 2 |");

        $this->assertStringContainsString('<table>', $html);
    }
}
