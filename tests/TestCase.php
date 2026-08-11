<?php

declare(strict_types=1);

namespace Tests;

use App\Services\ContentService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    /**
     * Temporary content directories created by a test, removed on teardown.
     *
     * @var list<string>
     */
    private array $temporaryContentPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Content is memoised; a stale entry would leak between tests.
        $this->flushContentCache();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryContentPaths as $path) {
            if (is_dir($path)) {
                File::deleteDirectory($path);
            }
        }

        $this->temporaryContentPaths = [];

        parent::tearDown();
    }

    /**
     * Point the application at an isolated, empty content directory.
     *
     * Returns the absolute path so a test can write exactly the fixtures it
     * needs — including deliberately broken ones.
     */
    protected function useTemporaryContent(): string
    {
        $path = storage_path('framework/testing/content-'.bin2hex(random_bytes(6)));

        File::ensureDirectoryExists($path);

        foreach (array_values((array) config('electroserves.collections')) as $collection) {
            File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.$collection);
        }

        $this->temporaryContentPaths[] = $path;

        config(['electroserves.content_path' => $path]);
        $this->flushContentCache();

        return $path;
    }

    /**
     * Write a content file, creating its directory if needed.
     */
    protected function writeContentFile(string $root, string $relativePath, string $contents): string
    {
        $full = $root.DIRECTORY_SEPARATOR.ltrim($relativePath, '/\\');

        File::ensureDirectoryExists(dirname($full));
        File::put($full, $contents);

        $this->flushContentCache();

        return $full;
    }

    /**
     * Drop every memoised content entry and rebuild the service.
     */
    protected function flushContentCache(): void
    {
        app(ContentService::class)->flush();
        app()->forgetInstance(ContentService::class);
    }
}
