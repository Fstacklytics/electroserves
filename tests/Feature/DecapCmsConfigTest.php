<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * The CMS config is a static file served to the browser, so a syntax error in
 * it produces a blank admin screen with no server-side warning. These tests
 * make that failure visible in CI instead.
 */
class DecapCmsConfigTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $path = public_path('admin/config.yml');

        $this->assertFileExists($path, 'The Decap CMS configuration is missing.');

        return Yaml::parseFile($path);
    }

    public function test_the_admin_entry_point_exists(): void
    {
        $this->assertFileExists(public_path('admin/index.html'));
    }

    public function test_the_configuration_is_valid_yaml(): void
    {
        $this->assertIsArray($this->config());
    }

    public function test_it_targets_the_github_backend(): void
    {
        $config = $this->config();

        $this->assertSame('github', $config['backend']['name']);
        $this->assertNotEmpty($config['backend']['repo']);
        // A leftover placeholder would silently break authentication.
        $this->assertStringNotContainsString('Replace with', (string) $config['backend']['repo']);
    }

    public function test_media_is_stored_inside_the_content_directory(): void
    {
        $config = $this->config();

        $this->assertSame('content/uploads', $config['media_folder']);
        $this->assertSame('/uploads', $config['public_folder']);
    }

    public function test_every_collection_the_application_reads_is_configured(): void
    {
        $names = array_column($this->config()['collections'], 'name');

        foreach (['settings', 'hero_slides', 'services', 'projects', 'blog', 'testimonials', 'team', 'faqs', 'pages'] as $expected) {
            $this->assertContains($expected, $names, "Collection [{$expected}] is missing from the CMS config.");
        }
    }

    public function test_collection_folders_match_the_configured_content_paths(): void
    {
        $folders = [];

        foreach ($this->config()['collections'] as $collection) {
            if (isset($collection['folder'])) {
                $folders[$collection['name']] = $collection['folder'];
            }
        }

        $this->assertSame('content/services', $folders['services']);
        $this->assertSame('content/projects', $folders['projects']);
        $this->assertSame('content/blog', $folders['blog']);
        $this->assertSame('content/testimonials', $folders['testimonials']);
        $this->assertSame('content/team', $folders['team']);
        $this->assertSame('content/faqs', $folders['faqs']);
        $this->assertSame('content/pages', $folders['pages']);
        $this->assertSame('content/hero', $folders['hero_slides']);
    }

    public function test_every_configured_collection_folder_exists_on_disk(): void
    {
        foreach ($this->config()['collections'] as $collection) {
            if (! isset($collection['folder'])) {
                continue;
            }

            $this->assertDirectoryExists(
                base_path($collection['folder']),
                "Collection folder [{$collection['folder']}] does not exist.",
            );
        }
    }

    public function test_service_and_project_category_options_match_application_config(): void
    {
        $collections = collect($this->config()['collections'])->keyBy('name');

        $categoryOptions = function (array $collection): array {
            foreach ($collection['fields'] as $field) {
                if (($field['name'] ?? null) === 'category') {
                    return $field['options'] ?? [];
                }
            }

            return [];
        };

        $this->assertSame(
            array_keys((array) config('electroserves.service_categories')),
            $categoryOptions($collections['services']),
        );

        $this->assertSame(
            array_keys((array) config('electroserves.project_categories')),
            $categoryOptions($collections['projects']),
        );
    }
}
