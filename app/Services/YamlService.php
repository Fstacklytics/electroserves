<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses YAML documents and Markdown frontmatter.
 *
 * Every method is total: malformed input is logged and reported as null rather
 * than thrown, so a single bad content file can never take down a page.
 */
class YamlService
{
    /**
     * Parse a YAML string into an array.
     *
     * @return array<string, mixed>|null null when the document is invalid or
     *                                   does not decode to a mapping
     */
    public function parse(string $yaml, string $context = 'inline YAML'): ?array
    {
        if (trim($yaml) === '') {
            return [];
        }

        try {
            $parsed = Yaml::parse($yaml);
        } catch (ParseException $e) {
            Log::error('Failed to parse YAML.', [
                'context' => $context,
                'error' => $e->getMessage(),
                'line' => $e->getParsedLine(),
            ]);

            return null;
        }

        if ($parsed === null) {
            return [];
        }

        if (! is_array($parsed)) {
            Log::error('YAML did not decode to a mapping.', [
                'context' => $context,
                'type' => get_debug_type($parsed),
            ]);

            return null;
        }

        return $parsed;
    }

    /**
     * Read and parse a YAML file from disk.
     *
     * @return array<string, mixed>|null null when the file is unreadable or invalid
     */
    public function parseFile(string $path): ?array
    {
        if (! is_file($path)) {
            Log::warning('Content file not found.', ['path' => $path]);

            return null;
        }

        if (! is_readable($path)) {
            Log::error('Content file is not readable.', ['path' => $path]);

            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            Log::error('Failed to read content file.', ['path' => $path]);

            return null;
        }

        return $this->parse($contents, $path);
    }

    /**
     * Split a Markdown document into its frontmatter and body.
     *
     * Supports the `---` delimited frontmatter Decap CMS writes. A document
     * with no frontmatter is valid and yields an empty attribute array.
     *
     * @return array{attributes: array<string, mixed>, body: string}|null
     *         null when frontmatter is present but unparseable
     */
    public function parseFrontMatter(string $contents, string $context = 'markdown'): ?array
    {
        // Normalise line endings so the delimiter regex behaves on CRLF files.
        $normalised = str_replace(["\r\n", "\r"], "\n", $contents);

        // Strip a UTF-8 BOM, which would otherwise prevent the `---` match.
        $normalised = preg_replace('/^\xEF\xBB\xBF/', '', $normalised) ?? $normalised;

        $pattern = '/^---\n(.*?)\n---\s*(?:\n(.*))?$/s';

        if (preg_match($pattern, $normalised, $matches) !== 1) {
            return [
                'attributes' => [],
                'body' => trim($normalised),
            ];
        }

        $attributes = $this->parse($matches[1], $context.' (frontmatter)');

        if ($attributes === null) {
            return null;
        }

        return [
            'attributes' => $attributes,
            'body' => trim($matches[2] ?? ''),
        ];
    }

    /**
     * Read a Markdown file and split it into frontmatter and body.
     *
     * @return array{attributes: array<string, mixed>, body: string}|null
     */
    public function parseMarkdownFile(string $path): ?array
    {
        if (! is_file($path)) {
            Log::warning('Content file not found.', ['path' => $path]);

            return null;
        }

        if (! is_readable($path)) {
            Log::error('Content file is not readable.', ['path' => $path]);

            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            Log::error('Failed to read content file.', ['path' => $path]);

            return null;
        }

        return $this->parseFrontMatter($contents, $path);
    }
}
