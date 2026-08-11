<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ValidatesContent;

/**
 * Global site settings, read on every page render.
 *
 * Source: content/settings/site.yml
 *
 * Unlike collection entries, settings must never make a page fail: if the file
 * is missing or malformed, `fallback()` supplies safe defaults so navigation
 * and the footer still render.
 */
final readonly class SiteSettings
{
    use ValidatesContent;

    /**
     * @param  list<array{day: string, hours: string}>  $businessHours
     * @param  array<string, string>  $socialLinks
     */
    private function __construct(
        public string $siteName,
        public string $tagline,
        public string $phone,
        public ?string $emergencyPhone,
        public string $email,
        public string $address,
        public string $city,
        public string $country,
        public ?string $mapEmbedUrl,
        public array $businessHours,
        public array $socialLinks,
        public string $copyright,
        public ?string $logo,
        public ?string $favicon,
        public int $yearsInBusiness,
        public int $projectsCompleted,
        public int $happyClients,
        public int $teamSize,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'site settings'): ?self
    {
        if (! self::hasRequired($data, ['site_name'], $context)) {
            return null;
        }

        return new self(
            siteName: self::str($data, 'site_name'),
            tagline: self::str($data, 'tagline'),
            phone: self::str($data, 'phone'),
            emergencyPhone: self::nullableStr($data, 'emergency_phone'),
            email: self::str($data, 'email'),
            address: self::str($data, 'address'),
            city: self::str($data, 'city', 'Dar es Salaam'),
            country: self::str($data, 'country', 'Tanzania'),
            mapEmbedUrl: self::nullableStr($data, 'map_embed_url'),
            businessHours: self::parseBusinessHours($data),
            socialLinks: self::parseSocialLinks($data),
            copyright: self::str($data, 'copyright'),
            logo: self::nullableStr($data, 'logo'),
            favicon: self::nullableStr($data, 'favicon'),
            yearsInBusiness: self::int($data, 'years_in_business', 0, 0),
            projectsCompleted: self::int($data, 'projects_completed', 0, 0),
            happyClients: self::int($data, 'happy_clients', 0, 0),
            teamSize: self::int($data, 'team_size', 0, 0),
        );
    }

    /**
     * Minimal settings used when content/settings/site.yml cannot be read.
     *
     * The site stays navigable and no page 500s; the missing file is logged by
     * the ContentService that requested it.
     */
    public static function fallback(): self
    {
        return new self(
            siteName: (string) config('app.name', 'ElectroServes'),
            tagline: '',
            phone: '',
            emergencyPhone: null,
            email: '',
            address: '',
            city: 'Dar es Salaam',
            country: 'Tanzania',
            mapEmbedUrl: null,
            businessHours: [],
            socialLinks: [],
            copyright: '',
            logo: null,
            favicon: null,
            yearsInBusiness: 0,
            projectsCompleted: 0,
            happyClients: 0,
            teamSize: 0,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{day: string, hours: string}>
     */
    private static function parseBusinessHours(array $data): array
    {
        $out = [];

        foreach (self::mapList($data, 'business_hours') as $row) {
            $day = self::str($row, 'day');
            $hours = self::str($row, 'hours');

            if ($day === '' || $hours === '') {
                continue;
            }

            $out[] = ['day' => $day, 'hours' => $hours];
        }

        return $out;
    }

    /**
     * Keep only social links that are present and look like usable URLs.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private static function parseSocialLinks(array $data): array
    {
        $raw = $data['social_links'] ?? null;

        if (! is_array($raw)) {
            return [];
        }

        $allowed = ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'whatsapp'];
        $out = [];

        foreach ($allowed as $network) {
            $value = $raw[$network] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value === '') {
                continue;
            }

            // Only absolute http(s) links are emitted, so a malformed entry can
            // never turn into a protocol-relative or javascript: URL.
            if (! preg_match('#^https?://#i', $value)) {
                continue;
            }

            $out[$network] = $value;
        }

        return $out;
    }

    public function fullAddress(): string
    {
        return trim(implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->country,
        ], static fn (string $part): bool => $part !== '')));
    }

    /**
     * Phone number reduced to a tel: href-safe form.
     */
    public function phoneHref(): ?string
    {
        return self::telHref($this->phone);
    }

    public function emergencyPhoneHref(): ?string
    {
        return $this->emergencyPhone === null ? null : self::telHref($this->emergencyPhone);
    }

    private static function telHref(string $number): ?string
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $number) ?? '';

        return $cleaned === '' ? null : 'tel:'.$cleaned;
    }

    public function hasSocialLinks(): bool
    {
        return $this->socialLinks !== [];
    }

    public function hasBusinessHours(): bool
    {
        return $this->businessHours !== [];
    }

    /**
     * Whether the stats band has anything meaningful to display.
     */
    public function hasStats(): bool
    {
        return $this->yearsInBusiness > 0
            || $this->projectsCompleted > 0
            || $this->happyClients > 0
            || $this->teamSize > 0;
    }
}
