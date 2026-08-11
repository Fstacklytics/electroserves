<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ValidatesContent;

/**
 * A slide in the homepage hero carousel.
 *
 * Source: content/hero/{slug}.yml
 */
final readonly class HeroSlide
{
    use ValidatesContent;

    private function __construct(
        public string $heading,
        public string $subheading,
        public ?string $image,
        public string $ctaText,
        public string $ctaLink,
        public ?string $ctaSecondaryText,
        public ?string $ctaSecondaryLink,
        public int $order,
        public bool $published,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'hero slide'): ?self
    {
        if (! self::hasRequired($data, ['heading'], $context)) {
            return null;
        }

        $secondaryText = self::nullableStr($data, 'cta_secondary_text');
        $secondaryLink = self::nullableStr($data, 'cta_secondary_link');

        // A secondary CTA is only rendered when both halves are present.
        if ($secondaryText === null || $secondaryLink === null) {
            $secondaryText = null;
            $secondaryLink = null;
        }

        return new self(
            heading: self::str($data, 'heading'),
            subheading: self::str($data, 'subheading'),
            image: self::nullableStr($data, 'image'),
            ctaText: self::str($data, 'cta_text', __('common.cta.get_quote')),
            ctaLink: self::str($data, 'cta_link', '/contact'),
            ctaSecondaryText: $secondaryText,
            ctaSecondaryLink: $secondaryLink,
            order: self::int($data, 'order', 99, 0),
            published: self::bool($data, 'published', true),
        );
    }

    public function hasSecondaryCta(): bool
    {
        return $this->ctaSecondaryText !== null && $this->ctaSecondaryLink !== null;
    }

    public function hasImage(): bool
    {
        return $this->image !== null;
    }
}
