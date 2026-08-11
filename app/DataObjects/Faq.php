<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\DataObjects\Concerns\ValidatesContent;

/**
 * A frequently asked question.
 *
 * Source: content/faqs/{slug}.yml
 */
final readonly class Faq
{
    use ValidatesContent;

    private function __construct(
        public string $question,
        public string $answer,
        public string $category,
        public int $order,
        public bool $published,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $context = 'faq'): ?self
    {
        if (! self::hasRequired($data, ['question', 'answer'], $context)) {
            return null;
        }

        /** @var list<string> $categories */
        $categories = (array) config('electroserves.faq_categories', []);

        return new self(
            question: self::str($data, 'question'),
            answer: self::str($data, 'answer'),
            category: self::enum(
                $data,
                'category',
                $categories,
                $categories[0] ?? 'General',
                $context,
            ),
            order: self::int($data, 'order', 99, 0),
            published: self::bool($data, 'published', true),
        );
    }

    /**
     * Stable DOM id for accordion aria-controls / aria-labelledby wiring.
     */
    public function anchorId(): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($this->question)) ?? '';

        return 'faq-'.trim($slug, '-');
    }
}
