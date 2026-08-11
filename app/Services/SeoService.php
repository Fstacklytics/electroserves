<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\SiteSettings;

/**
 * Builds the meta tag payload and JSON-LD structured data for a page.
 *
 * Controllers describe a page in terms of title/description/image; this service
 * resolves those against the site-wide SEO defaults so every page always has a
 * complete, non-empty set of tags.
 */
class SeoService
{
    public function __construct(
        private readonly ContentService $content,
        private readonly ImageService $images,
    ) {}

    /**
     * Resolve the meta tag values for a page.
     *
     * @param  array{title?: ?string, description?: ?string, image?: ?string, type?: string, canonical?: ?string, robots?: ?string}  $options
     * @return array{title: string, description: string, image: ?string, type: string, canonical: string, robots: string, site_name: string, twitter_handle: ?string, google_verification: ?string, locale: string}
     */
    public function forPage(array $options = []): array
    {
        $defaults = $this->content->seoDefaults();
        $settings = $this->content->siteSettings();

        $siteName = $settings->siteName !== '' ? $settings->siteName : $defaults->defaultTitle;

        $pageTitle = $this->clean($options['title'] ?? null);
        $title = $pageTitle === null
            ? $defaults->defaultTitle
            : $pageTitle.' | '.$siteName;

        $description = $this->truncate(
            $this->clean($options['description'] ?? null) ?? $defaults->defaultDescription,
            160,
        );

        $image = $this->clean($options['image'] ?? null) ?? $defaults->defaultOgImage;

        return [
            'title' => $title,
            'description' => $description,
            'image' => $image === null ? null : $this->images->absoluteUrl($image),
            'type' => $options['type'] ?? 'website',
            'canonical' => $this->clean($options['canonical'] ?? null) ?? url()->current(),
            'robots' => $options['robots'] ?? 'index, follow',
            'site_name' => $siteName,
            'twitter_handle' => $defaults->twitterHandle,
            'google_verification' => $defaults->googleVerification,
            'locale' => str_replace('-', '_', (string) config('app.locale', 'en')),
        ];
    }

    /**
     * Organization + LocalBusiness structured data for the site.
     *
     * @return array<string, mixed>
     */
    public function organizationSchema(?SiteSettings $settings = null): array
    {
        $settings ??= $this->content->siteSettings();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ElectricalContractor',
            'name' => $settings->siteName,
            'url' => (string) config('app.url'),
        ];

        if ($settings->tagline !== '') {
            $schema['description'] = $settings->tagline;
        }

        if ($settings->phone !== '') {
            $schema['telephone'] = $settings->phone;
        }

        if ($settings->email !== '') {
            $schema['email'] = $settings->email;
        }

        if ($settings->logo !== null) {
            $schema['logo'] = $this->images->absoluteUrl($settings->logo);
        }

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $settings->address !== '' ? $settings->address : null,
            'addressLocality' => $settings->city !== '' ? $settings->city : null,
            'addressCountry' => $settings->country !== '' ? $settings->country : null,
        ]);

        if (count($address) > 1) {
            $schema['address'] = $address;
        }

        if ($settings->hasSocialLinks()) {
            $schema['sameAs'] = array_values($settings->socialLinks);
        }

        if ($settings->hasBusinessHours()) {
            $schema['openingHours'] = array_map(
                static fn (array $row): string => $row['day'].' '.$row['hours'],
                $settings->businessHours,
            );
        }

        return $schema;
    }

    /**
     * BreadcrumbList structured data.
     *
     * @param  list<array{label: string, url: ?string}>  $crumbs
     * @return array<string, mixed>
     */
    public function breadcrumbSchema(array $crumbs): array
    {
        $items = [];
        $position = 1;

        foreach ($crumbs as $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $crumb['label'],
            ];

            if (($crumb['url'] ?? null) !== null) {
                $item['item'] = $crumb['url'];
            }

            $items[] = $item;
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Encode a schema array as JSON-LD.
     *
     * JSON_HEX_TAG prevents a `</script>` sequence in content from breaking out
     * of the script element.
     *
     * @param  array<string, mixed>  $schema
     */
    public function toJsonLd(array $schema): string
    {
        $json = json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );

        return $json === false ? '{}' : $json;
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');

        return $value === '' ? null : $value;
    }

    private function truncate(string $value, int $limit): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit - 1)).'…';
    }
}
