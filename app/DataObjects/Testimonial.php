<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ValidatesContent;

/**
 * A customer testimonial.
 *
 * Source: content/testimonials/{slug}.yml
 */
final readonly class Testimonial
{
    use ValidatesContent;

    private function __construct(
        public string $clientName,
        public ?string $company,
        public int $rating,
        public string $quote,
        public ?string $photo,
        public ?string $serviceUsed,
        public int $order,
        public bool $published,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'testimonial'): ?self
    {
        if (! self::hasRequired($data, ['client_name', 'quote'], $context)) {
            return null;
        }

        return new self(
            clientName: self::str($data, 'client_name'),
            company: self::nullableStr($data, 'company'),
            rating: self::int($data, 'rating', 5, 1, 5),
            quote: self::str($data, 'quote'),
            photo: self::nullableStr($data, 'photo'),
            serviceUsed: self::nullableStr($data, 'service_used'),
            order: self::int($data, 'order', 99, 0),
            published: self::bool($data, 'published', true),
        );
    }

    /**
     * Initials used for the avatar placeholder when no photo is supplied.
     *
     * Avoids external placeholder image services, per the project rules.
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->clientName)) ?: [];
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

    /**
     * Number of unfilled stars, for rendering the rating control.
     */
    public function emptyStars(): int
    {
        return 5 - $this->rating;
    }

    /**
     * Accessible label describing the rating, e.g. "Rated 5 out of 5".
     */
    public function ratingLabel(): string
    {
        return __('testimonials.rating_label', ['rating' => $this->rating]);
    }
}
