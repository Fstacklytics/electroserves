<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ParsesDates;
use App\DataObjects\Concerns\ValidatesContent;
use DateTimeImmutable;

/**
 * A blog article.
 *
 * Source: content/blog/{YYYY-MM-DD-slug}.md (Markdown body + YAML frontmatter).
 */
final readonly class BlogPost
{
    use ParsesDates;
    use ValidatesContent;

    /**
     * @param  list<string>  $tags
     */
    private function __construct(
        public string $title,
        public string $slug,
        public string $author,
        public ?DateTimeImmutable $date,
        public string $category,
        public array $tags,
        public string $excerpt,
        public string $body,
        public ?string $featuredImage,
        public bool $published,
        public ?string $metaDescription,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'blog post'): ?self
    {
        if (! self::hasRequired($data, ['title', 'slug', 'excerpt'], $context)) {
            return null;
        }

        /** @var list<string> $categories */
        $categories = (array) config('electroserves.blog_categories', []);

        return new self(
            title: self::str($data, 'title'),
            slug: self::str($data, 'slug'),
            author: self::str($data, 'author', 'ElectroServes Team'),
            date: self::date($data, 'date', $context, warnWhenMissing: true),
            category: self::enum(
                $data,
                'category',
                $categories,
                $categories[0] ?? 'Company Updates',
                $context,
            ),
            tags: self::stringList($data, 'tags'),
            excerpt: self::str($data, 'excerpt'),
            body: self::str($data, 'body'),
            featuredImage: self::nullableStr($data, 'featured_image'),
            published: self::bool($data, 'published', true),
            metaDescription: self::nullableStr($data, 'meta_description'),
        );
    }


    public function seoDescription(): string
    {
        return $this->metaDescription ?? $this->excerpt;
    }

    public function formattedDate(string $format = 'j F Y'): ?string
    {
        return $this->date?->format($format);
    }

    /**
     * ISO-8601 date for <time datetime="..."> and Article structured data.
     */
    public function dateIso(): ?string
    {
        return $this->date?->format('Y-m-d');
    }

    public function sortTimestamp(): int
    {
        return $this->date?->getTimestamp() ?? 0;
    }

    /**
     * Rough reading time in minutes, based on ~200 words per minute.
     */
    public function readingTimeMinutes(): int
    {
        $words = str_word_count(strip_tags($this->body));

        return max(1, (int) ceil($words / 200));
    }

    public function hasTags(): bool
    {
        return $this->tags !== [];
    }
}
