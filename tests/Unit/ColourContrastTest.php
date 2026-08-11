<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * WCAG 2.1 contrast arithmetic for the design tokens.
 *
 * The ratios in tailwind.config.js's header comment were written by hand. This
 * computes them from the actual hex values using the WCAG relative-luminance
 * formula, so a token can never be edited to a failing value while a stale
 * comment still claims it passes.
 *
 * Scope and honesty: this proves the *token pairs* meet the numeric threshold.
 * It cannot prove which pairs a browser actually composites on screen (an
 * overlay, opacity or gradient can change the effective background). Visual
 * verification against a real rendering remains a deployment-verification step.
 *
 * Thresholds (WCAG 2.1 AA):
 *   1.4.3 normal text          >= 4.5:1
 *   1.4.3 large text (>=24px,
 *         or >=18.66px bold)   >= 3.0:1
 *   1.4.11 UI components and
 *          graphical objects   >= 3.0:1
 */
class ColourContrastTest extends TestCase
{
    private const AA_NORMAL_TEXT = 4.5;

    private const AA_LARGE_TEXT_AND_UI = 3.0;

    /**
     * Design tokens, mirrored from tailwind.config.js.
     *
     * Kept as a literal map rather than parsed out of the JS config: a test
     * that reimplements the config parser can pass while the real build reads
     * something different. A mismatch here is caught by
     * test_tokens_match_the_tailwind_config below.
     */
    private const TOKENS = [
        'white' => '#ffffff',
        'primary-50' => '#eff6ff',
        'primary-100' => '#dbeafe',
        'primary-700' => '#1d4ed8',
        'primary-800' => '#1e40af',
        'primary-900' => '#1e3a8a',
        'primary-950' => '#172554',
        'secondary-700' => '#b45309',
        'secondary-800' => '#92400e',
        'neutral-50' => '#f8fafc',
        'neutral-100' => '#f1f5f9',
        'neutral-500' => '#64748b',
        'neutral-600' => '#475569',
        'neutral-700' => '#334155',
        'neutral-900' => '#0f172a',
        'danger-700' => '#b91c1c',
        'success-700' => '#15803d',
    ];

    /**
     * Foreground/background pairs the site actually renders.
     *
     * @return list<array{0: string, 1: string, 2: float}>
     */
    public static function textPairs(): array
    {
        return [
            // Body and heading text on the page background.
            'body text on white' => ['neutral-700', 'white', self::AA_NORMAL_TEXT],
            'headings on white' => ['neutral-900', 'white', self::AA_NORMAL_TEXT],
            'muted text on white' => ['neutral-600', 'white', self::AA_NORMAL_TEXT],
            'body text on neutral-50' => ['neutral-700', 'neutral-50', self::AA_NORMAL_TEXT],
            'body text on neutral-100' => ['neutral-700', 'neutral-100', self::AA_NORMAL_TEXT],
            'headings on neutral-100' => ['neutral-900', 'neutral-100', self::AA_NORMAL_TEXT],

            // Links and primary accents.
            'link text on white' => ['primary-800', 'white', self::AA_NORMAL_TEXT],
            'primary-700 link on white' => ['primary-700', 'white', self::AA_NORMAL_TEXT],
            'link on primary-50 panel' => ['primary-800', 'primary-50', self::AA_NORMAL_TEXT],
            'link on primary-100 panel' => ['primary-800', 'primary-100', self::AA_NORMAL_TEXT],

            // Reversed: white text on the solid brand buttons and footer.
            'button label on primary-800' => ['white', 'primary-800', self::AA_NORMAL_TEXT],
            'button label on primary-900' => ['white', 'primary-900', self::AA_NORMAL_TEXT],
            'footer text on primary-950' => ['white', 'primary-950', self::AA_NORMAL_TEXT],
            'button label on primary-700' => ['white', 'primary-700', self::AA_NORMAL_TEXT],

            // Status colours, which must not rely on hue alone.
            'error text on white' => ['danger-700', 'white', self::AA_NORMAL_TEXT],
            'success text on white' => ['success-700', 'white', self::AA_NORMAL_TEXT],
            'amber accent text on white' => ['secondary-700', 'white', self::AA_NORMAL_TEXT],
            'dark amber text on white' => ['secondary-800', 'white', self::AA_NORMAL_TEXT],
        ];
    }

    /**
     * Non-text contrast: focus rings and borders against their backdrop.
     *
     * @return list<array{0: string, 1: string, 2: float}>
     */
    public static function uiComponentPairs(): array
    {
        return [
            'focus ring on white' => ['primary-700', 'white', self::AA_LARGE_TEXT_AND_UI],
            'focus ring on neutral-50' => ['primary-700', 'neutral-50', self::AA_LARGE_TEXT_AND_UI],
            'secondary text on white' => ['neutral-500', 'white', self::AA_LARGE_TEXT_AND_UI],
        ];
    }

    #[DataProvider('textPairs')]
    public function test_text_pairs_meet_the_wcag_aa_threshold(
        string $foreground,
        string $background,
        float $threshold,
    ): void {
        $this->assertPairMeets($foreground, $background, $threshold);
    }

    #[DataProvider('uiComponentPairs')]
    public function test_ui_component_pairs_meet_the_wcag_non_text_threshold(
        string $foreground,
        string $background,
        float $threshold,
    ): void {
        $this->assertPairMeets($foreground, $background, $threshold);
    }

    /**
     * neutral-500 is the site's muted/meta text (dates, captions, counts) and
     * is used ~38 times. On white it measures 4.76:1, so it clears AA for
     * normal text — but only just. The margin is thin enough that placing it on
     * any tinted panel would drop it below 4.5:1, so both facts are pinned
     * here: it is safe on white, and it is *not* safe on neutral-100.
     */
    public function test_muted_text_passes_on_white_but_has_no_headroom_for_tinted_panels(): void
    {
        $onWhite = $this->contrastRatio(self::TOKENS['neutral-500'], self::TOKENS['white']);

        $this->assertGreaterThanOrEqual(
            self::AA_NORMAL_TEXT,
            $onWhite,
            'neutral-500 muted text no longer passes AA on white.',
        );

        $onTint = $this->contrastRatio(self::TOKENS['neutral-500'], self::TOKENS['neutral-100']);

        $this->assertLessThan(
            self::AA_NORMAL_TEXT,
            $onTint,
            'neutral-500 now clears AA on neutral-100; the restriction below can be relaxed.',
        );
    }

    public function test_muted_text_is_not_placed_on_tinted_panels_in_any_view(): void
    {
        // Enforces the conclusion above against the actual markup, since the
        // arithmetic alone cannot see how the classes are combined.
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $markup = (string) file_get_contents($file);

            foreach ($this->classAttributes($markup) as $classes) {
                $utilities = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                // Only unprefixed utilities describe the resting state. A
                // variant such as `hover:bg-neutral-100` pairs with
                // `hover:text-neutral-900`, so the two always change together
                // and the resting contrast is unaffected.
                $base = array_filter($utilities, static fn (string $u): bool => ! str_contains($u, ':'));

                $hasMutedText = in_array('text-neutral-500', $base, true);
                $hasTintedBackground = (bool) array_intersect(
                    $base,
                    ['bg-neutral-100', 'bg-neutral-200', 'bg-neutral-300'],
                );

                if ($hasMutedText && $hasTintedBackground) {
                    $offenders[] = basename($file);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'neutral-500 text on a neutral-100/200/300 background falls below WCAG AA (4.5:1). '
            .'Use neutral-600 or darker on tinted panels. Files: '.implode(', ', $offenders),
        );
    }

    /**
     * Every static class="..." attribute value in a Blade template.
     *
     * @return list<string>
     */
    private function classAttributes(string $markup): array
    {
        preg_match_all('/\bclass="([^"]*)"/', $markup, $matches);

        return $matches[1];
    }

    /**
     * @return list<string>
     */
    private function bladeFiles(): array
    {
        $directory = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/resources/views')
        );

        $files = [];

        foreach ($directory as $file) {
            if ($file instanceof \SplFileInfo && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    public function test_tokens_match_the_tailwind_config(): void
    {
        $config = file_get_contents(dirname(__DIR__, 2).'/tailwind.config.js');

        $this->assertIsString($config);

        foreach (self::TOKENS as $name => $hex) {
            if ($name === 'white') {
                continue;
            }

            [, $shade] = explode('-', $name);

            $this->assertMatchesRegularExpression(
                '/'.$shade.":\s*'".preg_quote($hex, '/')."'/i",
                $config,
                "Token {$name} is {$hex} in this test but differs in tailwind.config.js.",
            );
        }
    }

    private function assertPairMeets(string $foreground, string $background, float $threshold): void
    {
        $this->assertArrayHasKey($foreground, self::TOKENS);
        $this->assertArrayHasKey($background, self::TOKENS);

        $ratio = $this->contrastRatio(self::TOKENS[$foreground], self::TOKENS[$background]);

        $this->assertGreaterThanOrEqual(
            $threshold,
            $ratio,
            sprintf(
                '%s on %s is %.2f:1, below the required %.1f:1.',
                $foreground,
                $background,
                $ratio,
                $threshold,
            ),
        );
    }

    /**
     * WCAG 2.1 contrast ratio: (L1 + 0.05) / (L2 + 0.05).
     */
    private function contrastRatio(string $hexA, string $hexB): float
    {
        $a = $this->relativeLuminance($hexA);
        $b = $this->relativeLuminance($hexB);

        $lighter = max($a, $b);
        $darker = min($a, $b);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * WCAG 2.1 relative luminance of an sRGB colour.
     */
    private function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        $channels = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        $linear = array_map(static function (int $value): float {
            $channel = $value / 255;

            return $channel <= 0.03928
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4;
        }, $channels);

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }
}
