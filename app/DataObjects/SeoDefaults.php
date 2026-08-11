<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ValidatesContent;

/**
 * Site-wide SEO fallbacks.
 *
 * Source: content/settings/seo.yml
 */
final readonly class SeoDefaults
{
    use ValidatesContent;

    private function __construct(
        public string $defaultTitle,
        public string $defaultDescription,
        public ?string $defaultOgImage,
        public ?string $twitterHandle,
        public ?string $googleVerification,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'seo defaults'): ?self
    {
        if (! self::hasRequired($data, ['default_title', 'default_description'], $context)) {
            return null;
        }

        return new self(
            defaultTitle: self::str($data, 'default_title'),
            defaultDescription: self::str($data, 'default_description'),
            defaultOgImage: self::nullableStr($data, 'default_og_image'),
            twitterHandle: self::nullableStr($data, 'twitter_handle'),
            googleVerification: self::nullableStr($data, 'google_verification'),
        );
    }

    /**
     * Safe defaults so meta tags are always populated, even with no seo.yml.
     */
    public static function fallback(): self
    {
        return new self(
            defaultTitle: (string) config('app.name', 'ElectroServes'),
            defaultDescription: __('seo.default_description'),
            defaultOgImage: null,
            twitterHandle: null,
            googleVerification: null,
        );
    }
}
