<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ValidatesContent;

/**
 * A team member profile shown on the About page.
 *
 * Source: content/team/{slug}.yml
 *
 * Note (see docs/phase-0/05-pii-classification.md): only publish contact
 * details the team member has consented to make public. `email` is optional
 * and omitted from the sample content unless it is a role address.
 */
final readonly class TeamMember
{
    use ValidatesContent;

    /**
     * @param  list<array{name: string, issuer: ?string, year: ?int}>  $certifications
     */
    private function __construct(
        public string $name,
        public string $role,
        public string $bio,
        public ?string $photo,
        public array $certifications,
        public ?string $email,
        public int $order,
        public bool $published,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'team member'): ?self
    {
        if (! self::hasRequired($data, ['name', 'role'], $context)) {
            return null;
        }

        return new self(
            name: self::str($data, 'name'),
            role: self::str($data, 'role'),
            bio: self::str($data, 'bio'),
            photo: self::nullableStr($data, 'photo'),
            certifications: self::parseCertifications($data),
            email: self::nullableStr($data, 'email'),
            order: self::int($data, 'order', 99, 0),
            published: self::bool($data, 'published', true),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{name: string, issuer: ?string, year: ?int}>
     */
    private static function parseCertifications(array $data): array
    {
        $out = [];

        foreach (self::mapList($data, 'certifications') as $row) {
            $name = self::str($row, 'name');

            if ($name === '') {
                continue;
            }

            $year = self::int($row, 'year', 0);

            $out[] = [
                'name' => $name,
                'issuer' => self::nullableStr($row, 'issuer'),
                'year' => $year > 0 ? $year : null,
            ];
        }

        return $out;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = '';

        foreach ($parts as $part) {
            $first = mb_substr($part, 0, 1);

            if ($first !== '') {
                $letters .= mb_strtoupper($first);
            }

            if (mb_strlen($letters) === 2) {
                break;
            }
        }

        return $letters === '' ? '?' : $letters;
    }

    public function hasPhoto(): bool
    {
        return $this->photo !== null;
    }

    public function hasCertifications(): bool
    {
        return $this->certifications !== [];
    }
}
