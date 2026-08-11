<?php

declare(strict_types=1);

namespace Tests\Unit\DataObjects;

use App\DataObjects\BlogPost;
use Tests\TestCase;

class BlogPostTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validAttributes(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Why Your Breaker Keeps Tripping',
            'slug' => 'why-your-breaker-keeps-tripping',
            'excerpt' => 'A tripping breaker is doing its job.',
            'author' => 'Amina Mwakalinga',
            'date' => '2026-01-12',
            'category' => 'Tips & Advice',
            'body' => str_repeat('word ', 400),
        ], $overrides);
    }

    public function test_it_builds_from_valid_attributes(): void
    {
        $post = BlogPost::fromArray($this->validAttributes());

        $this->assertInstanceOf(BlogPost::class, $post);
        $this->assertSame('2026-01-12', $post->dateIso());
        $this->assertSame('12 January 2026', $post->formattedDate());
    }

    public function test_it_returns_null_without_required_fields(): void
    {
        $this->assertNull(BlogPost::fromArray([]));
        $this->assertNull(BlogPost::fromArray(['title' => 'Only a title']));
    }

    public function test_a_string_date_is_parsed(): void
    {
        $post = BlogPost::fromArray($this->validAttributes(['date' => '2025-06-01']));

        $this->assertSame('2025-06-01', $post?->dateIso());
    }

    public function test_a_yaml_timestamp_date_is_parsed(): void
    {
        // Symfony's YAML parser resolves an unquoted date to a Unix timestamp.
        $post = BlogPost::fromArray($this->validAttributes(['date' => 1704067200]));

        $this->assertNotNull($post?->date);
        $this->assertSame('2024-01-01', $post->dateIso());
    }

    public function test_an_unparseable_date_is_ignored_rather_than_fatal(): void
    {
        $post = BlogPost::fromArray($this->validAttributes(['date' => 'sometime last spring']));

        $this->assertInstanceOf(BlogPost::class, $post);
        $this->assertNull($post->date);
        $this->assertNull($post->formattedDate());
        $this->assertSame(0, $post->sortTimestamp());
    }

    public function test_an_unknown_category_falls_back_to_a_configured_one(): void
    {
        $post = BlogPost::fromArray($this->validAttributes(['category' => 'Cryptozoology']));

        $this->assertContains($post?->category, (array) config('electroserves.blog_categories'));
    }

    public function test_reading_time_is_at_least_one_minute(): void
    {
        $short = BlogPost::fromArray($this->validAttributes(['body' => 'Three short words.']));
        $long = BlogPost::fromArray($this->validAttributes(['body' => str_repeat('word ', 1000)]));

        $this->assertSame(1, $short?->readingTimeMinutes());
        $this->assertSame(5, $long?->readingTimeMinutes());
    }

    public function test_author_defaults_when_omitted(): void
    {
        $post = BlogPost::fromArray($this->validAttributes(['author' => null]));

        $this->assertNotSame('', $post?->author);
    }

    public function test_tags_are_normalised_and_blank_entries_removed(): void
    {
        $post = BlogPost::fromArray($this->validAttributes(['tags' => ['safety', '  ', 'wiring']]));

        $this->assertSame(['safety', 'wiring'], $post?->tags);
        $this->assertTrue($post?->hasTags());
    }
}
