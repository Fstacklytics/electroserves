<?php

declare(strict_types=1);

namespace Tests\Unit\DataObjects;

use App\DataObjects\Service;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validAttributes(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Residential Electrical',
            'slug' => 'residential-electrical',
            'short_description' => 'Wiring, rewiring and fault finding for homes.',
            'icon' => 'home',
            'body' => 'Full description.',
            'category' => 'residential',
            'order' => 1,
            'published' => true,
        ], $overrides);
    }

    public function test_it_builds_from_valid_attributes(): void
    {
        $service = Service::fromArray($this->validAttributes());

        $this->assertInstanceOf(Service::class, $service);
        $this->assertSame('Residential Electrical', $service->title);
        $this->assertSame('residential-electrical', $service->slug);
        $this->assertTrue($service->published);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function requiredFields(): array
    {
        return [['title'], ['slug'], ['short_description']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('requiredFields')]
    public function test_it_returns_null_when_a_required_field_is_absent(string $field): void
    {
        $attributes = $this->validAttributes();
        unset($attributes[$field]);

        $this->assertNull(Service::fromArray($attributes));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('requiredFields')]
    public function test_it_returns_null_when_a_required_field_is_blank(string $field): void
    {
        $this->assertNull(Service::fromArray($this->validAttributes([$field => '   '])));
    }

    public function test_it_never_throws_on_malformed_input(): void
    {
        $this->assertNull(Service::fromArray([]));
        $this->assertNull(Service::fromArray(['title' => null, 'slug' => null, 'short_description' => null]));
    }

    public function test_features_accept_both_scalar_and_keyed_list_shapes(): void
    {
        $keyed = Service::fromArray($this->validAttributes([
            'features' => [['feature' => 'Rewiring'], ['feature' => 'Testing']],
        ]));

        $scalar = Service::fromArray($this->validAttributes([
            'features' => ['Rewiring', 'Testing'],
        ]));

        $this->assertSame(['Rewiring', 'Testing'], $keyed?->features);
        $this->assertSame(['Rewiring', 'Testing'], $scalar?->features);
    }

    public function test_blank_feature_entries_are_discarded(): void
    {
        $service = Service::fromArray($this->validAttributes([
            'features' => [['feature' => 'Real'], ['feature' => '  '], ['nope' => 'x']],
        ]));

        $this->assertSame(['Real'], $service?->features);
    }

    public function test_published_defaults_to_true_and_accepts_yaml_spellings(): void
    {
        $this->assertTrue(Service::fromArray($this->validAttributes(['published' => null]))?->published);
        $this->assertTrue(Service::fromArray($this->validAttributes(['published' => 'yes']))?->published);
        $this->assertFalse(Service::fromArray($this->validAttributes(['published' => false]))?->published);
    }

    public function test_seo_description_falls_back_to_the_short_description(): void
    {
        $withMeta = Service::fromArray($this->validAttributes(['meta_description' => 'Custom meta.']));
        $withoutMeta = Service::fromArray($this->validAttributes());

        $this->assertSame('Custom meta.', $withMeta?->seoDescription());
        $this->assertSame('Wiring, rewiring and fault finding for homes.', $withoutMeta?->seoDescription());
    }

    public function test_optional_fields_default_to_null_rather_than_empty_strings(): void
    {
        $service = Service::fromArray($this->validAttributes(['price_range' => '', 'image' => '']));

        $this->assertNull($service?->priceRange);
        $this->assertNull($service?->image);
        $this->assertFalse($service?->hasImage());
    }
}
