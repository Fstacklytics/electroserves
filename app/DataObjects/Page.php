<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ParsesDates;
use App\DataObjects\Concerns\ValidatesContent;
use DateTimeImmutable;

/**
 * A static Markdown page (About, Privacy Policy, Terms).
 *
 * Source: content/pages/{slug}.md
 */
final readonly class Page
{
    use ParsesDates;
    use ValidatesContent;

    private function __construct(
        public string $title,
        public string $slug,
        public string $body,
        public ?string $featuredImage,
        public ?string $metaDescription,
        public ?DateTimeImmutable $updatedAt,
        public bool $published,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'page'): ?self
    {
        if (! self::hasRequired($data, ['title'], $context)) {
            return null;
        }

        return new self(
            title: self::str($data, 'title'),
            slug: self::str($data, 'slug'),
            body: self::str($data, 'body'),
            featuredImage: self::nullableStr($data, 'featured_image'),
            metaDescription: self::nullableStr($data, 'meta_description'),
            updatedAt: self::date($data, 'updated_at', $context),
            published: self::bool($data, 'published', true),
        );
    }


    public function formattedUpdatedAt(string $format = 'j F Y'): ?string
    {
        return $this->updatedAt?->format($format);
    }

    public function updatedAtIso(): ?string
    {
        return $this->updatedAt?->format('Y-m-d');
    }
}
