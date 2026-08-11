<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ParsesDates;
use App\DataObjects\Concerns\ValidatesContent;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

/**
 * A completed project / portfolio case study.
 *
 * Source: content/projects/{slug}.md (Markdown body + YAML frontmatter).
 */
final readonly class Project
{
    use ParsesDates;
    use ValidatesContent;

    /**
     * @param  list<array{image: string, caption: ?string}>  $gallery
     * @param  list<string>  $servicesUsed
     */
    private function __construct(
        public string $title,
        public string $slug,
        public string $category,
        public string $shortDescription,
        public string $body,
        public ?string $featuredImage,
        public array $gallery,
        public ?string $clientName,
        public ?string $location,
        public ?DateTimeImmutable $completionDate,
        public array $servicesUsed,
        public bool $featured,
        public bool $published,
        public ?string $metaDescription,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'project'): ?self
    {
        if (! self::hasRequired($data, ['title', 'slug', 'short_description'], $context)) {
            return null;
        }

        $categories = array_keys((array) config('electroserves.project_categories', []));

        return new self(
            title: self::str($data, 'title'),
            slug: self::str($data, 'slug'),
            category: self::enum(
                $data,
                'category',
                $categories,
                $categories[0] ?? 'residential',
                $context,
            ),
            shortDescription: self::str($data, 'short_description'),
            body: self::str($data, 'body'),
            featuredImage: self::nullableStr($data, 'featured_image'),
            gallery: self::parseGallery($data, $context),
            clientName: self::nullableStr($data, 'client_name'),
            location: self::nullableStr($data, 'location'),
            completionDate: self::date($data, 'completion_date', $context),
            servicesUsed: self::stringList($data, 'services_used'),
            featured: self::bool($data, 'featured', false),
            published: self::bool($data, 'published', true),
            metaDescription: self::nullableStr($data, 'meta_description'),
        );
    }

    /**
     * Normalise gallery rows, dropping entries with no usable image path.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{image: string, caption: ?string}>
     */
    private static function parseGallery(array $data, string $context): array
    {
        $out = [];

        foreach (self::mapList($data, 'gallery') as $index => $row) {
            $image = self::str($row, 'image');

            if ($image === '') {
                Log::warning('Project gallery entry skipped: no image path.', [
                    'context' => $context,
                    'index' => $index,
                ]);

                continue;
            }

            $out[] = [
                'image' => $image,
                'caption' => self::nullableStr($row, 'caption'),
            ];
        }

        return $out;
    }


    public function seoDescription(): string
    {
        return $this->metaDescription ?? $this->shortDescription;
    }

    public function formattedCompletionDate(string $format = 'F Y'): ?string
    {
        return $this->completionDate?->format($format);
    }

    /**
     * ISO-8601 date for <time datetime="..."> and structured data.
     */
    public function completionDateIso(): ?string
    {
        return $this->completionDate?->format('Y-m-d');
    }

    public function hasGallery(): bool
    {
        return $this->gallery !== [];
    }

    /**
     * Sort key used to order projects newest-first; undated entries sink last.
     */
    public function sortTimestamp(): int
    {
        return $this->completionDate?->getTimestamp() ?? 0;
    }
}
