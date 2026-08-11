<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ValidatesContent;

/**
 * A service offered by ElectroServes.
 *
 * Source: content/services/{slug}.md (Markdown body + YAML frontmatter).
 */
final readonly class Service
{
    use ValidatesContent;

    /**
     * @param  list<string>  $features
     */
    private function __construct(
        public string $title,
        public string $slug,
        public string $icon,
        public string $shortDescription,
        public string $body,
        public ?string $image,
        public array $features,
        public ?string $priceRange,
        public string $category,
        public int $order,
        public bool $published,
        public ?string $metaDescription,
    ) {}

    /**
     * Build a Service from parsed frontmatter, or null when it is unusable.
     *
     * Required fields are title, slug and short_description — without them the
     * entry cannot be listed or linked, so it is skipped rather than rendered
     * half-empty.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'service'): ?self
    {
        if (! self::hasRequired($data, ['title', 'slug', 'short_description'], $context)) {
            return null;
        }

        $categories = array_keys((array) config('electroserves.service_categories', []));

        return new self(
            title: self::str($data, 'title'),
            slug: self::str($data, 'slug'),
            icon: self::str($data, 'icon', 'bolt'),
            shortDescription: self::str($data, 'short_description'),
            body: self::str($data, 'body'),
            image: self::nullableStr($data, 'image'),
            features: self::stringList($data, 'features', 'feature'),
            priceRange: self::nullableStr($data, 'price_range'),
            category: self::enum(
                $data,
                'category',
                $categories,
                $categories[0] ?? 'residential',
                $context,
            ),
            order: self::int($data, 'order', 99, 0),
            published: self::bool($data, 'published', true),
            metaDescription: self::nullableStr($data, 'meta_description'),
        );
    }

    /**
     * The description used for meta tags, falling back to the short description.
     */
    public function seoDescription(): string
    {
        return $this->metaDescription ?? $this->shortDescription;
    }

    public function hasFeatures(): bool
    {
        return $this->features !== [];
    }

    public function hasImage(): bool
    {
        return $this->image !== null;
    }
}
