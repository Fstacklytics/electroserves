<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Converts CMS Markdown into HTML that is safe to emit with `{!! !!}`.
 *
 * Content is authored through Decap CMS by trusted staff, but a compromised
 * CMS account is an explicit threat (T2 in docs/phase-0/08-threat-model.md).
 * Raw HTML is therefore disabled at the parser level rather than filtered
 * afterwards, which removes script/iframe injection entirely instead of
 * trying to blocklist it.
 */
class MarkdownService
{
    private ?MarkdownConverter $converter = null;

    /**
     * Convert Markdown to sanitised HTML.
     *
     * Returns an empty string (and logs) if conversion fails, so a malformed
     * document degrades to "no body" rather than a 500.
     */
    public function toHtml(string $markdown, string $context = 'markdown'): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        try {
            $html = $this->converter()->convert($markdown)->getContent();
        } catch (CommonMarkException $e) {
            Log::error('Failed to render Markdown.', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return '';
        }

        return $this->hardenLinks($html);
    }

    /**
     * Extract h2/h3 headings for an auto-generated table of contents.
     *
     * Anchors match the ids CommonMark's heading permalink-free output would
     * not provide, so ids are derived here and injected by `toHtmlWithAnchors`.
     *
     * @return list<array{level: int, text: string, id: string}>
     */
    public function extractHeadings(string $markdown): array
    {
        $headings = [];
        $seen = [];

        // Ignore fenced code blocks so `## comment` inside a snippet is skipped.
        $inFence = false;

        foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
            if (preg_match('/^\s*(```|~~~)/', $line) === 1) {
                $inFence = ! $inFence;

                continue;
            }

            if ($inFence) {
                continue;
            }

            if (preg_match('/^(#{2,3})\s+(.+?)\s*#*\s*$/', $line, $m) !== 1) {
                continue;
            }

            $text = trim(strip_tags($m[2]));

            if ($text === '') {
                continue;
            }

            $id = $this->slugify($text);

            // Guarantee unique ids; duplicate ids break aria-controls and anchors.
            if (isset($seen[$id])) {
                $seen[$id]++;
                $id .= '-'.$seen[$id];
            } else {
                $seen[$id] = 1;
            }

            $headings[] = [
                'level' => strlen($m[1]),
                'text' => $text,
                'id' => $id,
            ];
        }

        return $headings;
    }

    /**
     * Render Markdown and add matching ids to h2/h3 so the TOC can link to them.
     */
    public function toHtmlWithAnchors(string $markdown, string $context = 'markdown'): string
    {
        $html = $this->toHtml($markdown, $context);

        if ($html === '') {
            return '';
        }

        $headings = $this->extractHeadings($markdown);

        if ($headings === []) {
            return $html;
        }

        $index = 0;

        $result = preg_replace_callback(
            '/<h([23])>/',
            function (array $m) use ($headings, &$index): string {
                $heading = $headings[$index] ?? null;
                $index++;

                if ($heading === null) {
                    return $m[0];
                }

                return sprintf('<h%s id="%s">', $m[1], htmlspecialchars($heading['id'], ENT_QUOTES));
            },
            $html,
        );

        return $result ?? $html;
    }

    /**
     * Convert a heading to a URL-safe anchor id.
     */
    public function slugify(string $text): string
    {
        $slug = mb_strtolower(trim($text));
        // Drop apostrophes rather than turning them into separators, so
        // "What's Included" becomes "whats-included" not "what-s-included".
        $slug = str_replace(["'", '’', '`'], '', $slug);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug === '' ? 'section' : $slug;
    }

    /**
     * Build the converter once per request.
     *
     * `html_input: strip` removes any inline HTML (including <script> and
     * <iframe>) and `allow_unsafe_links: false` blocks javascript: and data:
     * URLs, satisfying the "Markdown sanitized" security requirement.
     */
    private function converter(): MarkdownConverter
    {
        if ($this->converter instanceof MarkdownConverter) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
            'renderer' => [
                'soft_break' => "\n",
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());

        return $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Make external links safe to click: no referrer leakage, no tab-nabbing.
     */
    private function hardenLinks(string $html): string
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        $result = preg_replace_callback(
            '/<a\s+href="(https?:\/\/[^"]+)"/i',
            function (array $m) use ($appHost): string {
                $host = parse_url($m[1], PHP_URL_HOST);

                if (is_string($host) && is_string($appHost) && strcasecmp($host, $appHost) === 0) {
                    return $m[0];
                }

                return sprintf('<a href="%s" target="_blank" rel="noopener noreferrer"', $m[1]);
            },
            $html,
        );

        return $result ?? $html;
    }
}
