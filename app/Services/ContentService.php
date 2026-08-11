<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\BlogPost;
use App\DataObjects\Faq;
use App\DataObjects\HeroSlide;
use App\DataObjects\Page;
use App\DataObjects\Project;
use App\DataObjects\SeoDefaults;
use App\DataObjects\Service;
use App\DataObjects\SiteSettings;
use App\DataObjects\TeamMember;
use App\DataObjects\Testimonial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads CMS content from disk and returns typed DataObjects.
 *
 * Contract (see docs/phase-0 rules):
 *   - never throws to the controller layer;
 *   - a missing directory or file yields an empty collection / null, logged;
 *   - a malformed or incomplete entry is skipped and logged, and the remaining
 *     entries still render;
 *   - results are cached with a configurable TTL.
 */
class ContentService
{
    public function __construct(
        private readonly YamlService $yaml,
    ) {}

    // ---------------------------------------------------------------------
    // Settings
    // ---------------------------------------------------------------------

    /**
     * Global site settings, always returning a usable object.
     */
    public function siteSettings(): SiteSettings
    {
        return $this->remember('settings.site', function (): SiteSettings {
            $path = $this->pathFor('settings', 'site.yml');
            $data = $this->yaml->parseFile($path);

            if ($data === null) {
                Log::warning('Site settings unavailable; using fallback values.', ['path' => $path]);

                return SiteSettings::fallback();
            }

            return SiteSettings::fromArray($data, $path) ?? SiteSettings::fallback();
        });
    }

    /**
     * Site-wide SEO defaults, always returning a usable object.
     */
    public function seoDefaults(): SeoDefaults
    {
        return $this->remember('settings.seo', function (): SeoDefaults {
            $path = $this->pathFor('settings', 'seo.yml');
            $data = $this->yaml->parseFile($path);

            if ($data === null) {
                return SeoDefaults::fallback();
            }

            return SeoDefaults::fromArray($data, $path) ?? SeoDefaults::fallback();
        });
    }

    // ---------------------------------------------------------------------
    // Collections
    // ---------------------------------------------------------------------

    /**
     * All published services, ordered by their `order` field then title.
     *
     * @return Collection<int, Service>
     */
    public function services(): Collection
    {
        return $this->remember('services', function (): Collection {
            return $this->loadMarkdownCollection(
                'services',
                static fn (array $data, string $ctx): ?Service => Service::fromArray($data, $ctx),
            )
                ->filter(static fn (Service $s): bool => $s->published)
                ->sortBy([
                    static fn (Service $a, Service $b): int => $a->order <=> $b->order,
                    static fn (Service $a, Service $b): int => strcasecmp($a->title, $b->title),
                ])
                ->values();
        });
    }

    public function findService(string $slug): ?Service
    {
        $slug = $this->normaliseSlug($slug);

        if ($slug === null) {
            return null;
        }

        return $this->services()->firstWhere('slug', $slug);
    }

    /**
     * All published projects, newest completion date first.
     *
     * @return Collection<int, Project>
     */
    public function projects(): Collection
    {
        return $this->remember('projects', function (): Collection {
            return $this->loadMarkdownCollection(
                'projects',
                static fn (array $data, string $ctx): ?Project => Project::fromArray($data, $ctx),
            )
                ->filter(static fn (Project $p): bool => $p->published)
                ->sortByDesc(static fn (Project $p): int => $p->sortTimestamp())
                ->values();
        });
    }

    public function findProject(string $slug): ?Project
    {
        $slug = $this->normaliseSlug($slug);

        if ($slug === null) {
            return null;
        }

        return $this->projects()->firstWhere('slug', $slug);
    }

    /**
     * Projects flagged as featured, for the homepage.
     *
     * @return Collection<int, Project>
     */
    public function featuredProjects(int $limit = 6): Collection
    {
        return $this->projects()
            ->filter(static fn (Project $p): bool => $p->featured)
            ->take(max(0, $limit))
            ->values();
    }

    /**
     * All published blog posts, newest first.
     *
     * @return Collection<int, BlogPost>
     */
    public function blogPosts(): Collection
    {
        return $this->remember('blog', function (): Collection {
            return $this->loadMarkdownCollection(
                'blog',
                static fn (array $data, string $ctx): ?BlogPost => BlogPost::fromArray($data, $ctx),
            )
                ->filter(static fn (BlogPost $p): bool => $p->published)
                ->sortByDesc(static fn (BlogPost $p): int => $p->sortTimestamp())
                ->values();
        });
    }

    public function findBlogPost(string $slug): ?BlogPost
    {
        $slug = $this->normaliseSlug($slug);

        if ($slug === null) {
            return null;
        }

        return $this->blogPosts()->firstWhere('slug', $slug);
    }

    /**
     * All published testimonials, ordered by `order`.
     *
     * @return Collection<int, Testimonial>
     */
    public function testimonials(): Collection
    {
        return $this->remember('testimonials', function (): Collection {
            return $this->loadYamlCollection(
                'testimonials',
                static fn (array $data, string $ctx): ?Testimonial => Testimonial::fromArray($data, $ctx),
            )
                ->filter(static fn (Testimonial $t): bool => $t->published)
                ->sortBy(static fn (Testimonial $t): int => $t->order)
                ->values();
        });
    }

    /**
     * All published team members, ordered by `order`.
     *
     * @return Collection<int, TeamMember>
     */
    public function teamMembers(): Collection
    {
        return $this->remember('team', function (): Collection {
            return $this->loadYamlCollection(
                'team',
                static fn (array $data, string $ctx): ?TeamMember => TeamMember::fromArray($data, $ctx),
            )
                ->filter(static fn (TeamMember $m): bool => $m->published)
                ->sortBy(static fn (TeamMember $m): int => $m->order)
                ->values();
        });
    }

    /**
     * All published FAQs, ordered by `order`.
     *
     * @return Collection<int, Faq>
     */
    public function faqs(): Collection
    {
        return $this->remember('faqs', function (): Collection {
            return $this->loadYamlCollection(
                'faqs',
                static fn (array $data, string $ctx): ?Faq => Faq::fromArray($data, $ctx),
            )
                ->filter(static fn (Faq $f): bool => $f->published)
                ->sortBy(static fn (Faq $f): int => $f->order)
                ->values();
        });
    }

    /**
     * FAQs grouped by category, preserving the configured category order.
     *
     * @return Collection<string, Collection<int, Faq>>
     */
    public function faqsByCategory(): Collection
    {
        $grouped = $this->faqs()->groupBy(static fn (Faq $f): string => $f->category);

        /** @var list<string> $order */
        $order = (array) config('electroserves.faq_categories', []);

        return $grouped->sortBy(static function (Collection $_, string $category) use ($order): int {
            $position = array_search($category, $order, true);

            return $position === false ? PHP_INT_MAX : $position;
        });
    }

    /**
     * All published hero slides, ordered by `order`.
     *
     * @return Collection<int, HeroSlide>
     */
    public function heroSlides(): Collection
    {
        return $this->remember('hero', function (): Collection {
            return $this->loadYamlCollection(
                'hero',
                static fn (array $data, string $ctx): ?HeroSlide => HeroSlide::fromArray($data, $ctx),
            )
                ->filter(static fn (HeroSlide $s): bool => $s->published)
                ->sortBy(static fn (HeroSlide $s): int => $s->order)
                ->values();
        });
    }

    /**
     * A single static Markdown page by slug (e.g. "about", "terms").
     */
    public function page(string $slug): ?Page
    {
        $slug = $this->normaliseSlug($slug);

        if ($slug === null) {
            return null;
        }

        return $this->remember('pages.'.$slug, function () use ($slug): ?Page {
            $path = $this->pathFor('pages', $slug.'.md');
            $parsed = $this->yaml->parseMarkdownFile($path);

            if ($parsed === null) {
                return null;
            }

            $attributes = $parsed['attributes'];
            $attributes['body'] = $parsed['body'];
            $attributes['slug'] = $attributes['slug'] ?? $slug;

            $page = Page::fromArray($attributes, $path);

            if ($page === null) {
                return null;
            }

            return $page->published ? $page : null;
        });
    }

    // ---------------------------------------------------------------------
    // Cross-collection helpers
    // ---------------------------------------------------------------------

    /**
     * Projects that reference a given service slug.
     *
     * @return Collection<int, Project>
     */
    public function projectsForService(string $serviceSlug, int $limit = 3): Collection
    {
        $slug = $this->normaliseSlug($serviceSlug);

        if ($slug === null) {
            return collect();
        }

        return $this->projects()
            ->filter(static fn (Project $p): bool => in_array($slug, $p->servicesUsed, true))
            ->take(max(0, $limit))
            ->values();
    }

    /**
     * Other posts in the same category, excluding the current one.
     *
     * @return Collection<int, BlogPost>
     */
    public function relatedBlogPosts(BlogPost $post, int $limit = 3): Collection
    {
        return $this->blogPosts()
            ->filter(static fn (BlogPost $p): bool => $p->slug !== $post->slug && $p->category === $post->category)
            ->take(max(0, $limit))
            ->values();
    }

    /**
     * Other projects in the same category, excluding the current one.
     *
     * @return Collection<int, Project>
     */
    public function relatedProjects(Project $project, int $limit = 3): Collection
    {
        return $this->projects()
            ->filter(static fn (Project $p): bool => $p->slug !== $project->slug && $p->category === $project->category)
            ->take(max(0, $limit))
            ->values();
    }

    /**
     * Service categories that actually have published services behind them.
     *
     * @return array<string, string>
     */
    public function activeServiceCategories(): array
    {
        /** @var array<string, string> $all */
        $all = (array) config('electroserves.service_categories', []);
        $used = $this->services()->pluck('category')->unique()->all();

        return array_filter($all, static fn (string $key): bool => in_array($key, $used, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Project categories that actually have published projects behind them.
     *
     * @return array<string, string>
     */
    public function activeProjectCategories(): array
    {
        /** @var array<string, string> $all */
        $all = (array) config('electroserves.project_categories', []);
        $used = $this->projects()->pluck('category')->unique()->all();

        return array_filter($all, static fn (string $key): bool => in_array($key, $used, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Blog categories that actually have published posts behind them.
     *
     * @return list<string>
     */
    public function activeBlogCategories(): array
    {
        return $this->blogPosts()
            ->pluck('category')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Clear every cached content entry (used after content changes and in tests).
     */
    public function flush(): void
    {
        foreach ($this->cacheKeys() as $key) {
            Cache::forget($key);
        }
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /**
     * Load every Markdown file in a collection directory into DataObjects.
     *
     * @template T of object
     *
     * @param  callable(array<string, mixed>, string): ?T  $factory
     * @return Collection<int, T>
     */
    private function loadMarkdownCollection(string $collection, callable $factory): Collection
    {
        $items = collect();

        foreach ($this->filesIn($collection, 'md') as $path) {
            $parsed = $this->yaml->parseMarkdownFile($path);

            if ($parsed === null) {
                // Already logged by YamlService; skip this entry, keep the rest.
                continue;
            }

            $attributes = $parsed['attributes'];
            $attributes['body'] = $parsed['body'];

            // Fall back to the filename when the author omitted an explicit slug.
            if (! isset($attributes['slug']) || ! is_string($attributes['slug']) || trim($attributes['slug']) === '') {
                $attributes['slug'] = $this->slugFromFilename($path);
            }

            $item = $factory($attributes, $path);

            if ($item !== null) {
                $items->push($item);
            }
        }

        return $items;
    }

    /**
     * Load every YAML file in a collection directory into DataObjects.
     *
     * @template T of object
     *
     * @param  callable(array<string, mixed>, string): ?T  $factory
     * @return Collection<int, T>
     */
    private function loadYamlCollection(string $collection, callable $factory): Collection
    {
        $items = collect();

        foreach ($this->filesIn($collection, 'yml', 'yaml') as $path) {
            $data = $this->yaml->parseFile($path);

            if ($data === null) {
                continue;
            }

            $item = $factory($data, $path);

            if ($item !== null) {
                $items->push($item);
            }
        }

        return $items;
    }

    /**
     * List content files of the given extensions inside a collection directory.
     *
     * A missing directory is a normal state for a brand-new site: it is logged
     * once at debug level and treated as "no entries".
     *
     * @return list<string> absolute file paths, sorted for deterministic output
     */
    private function filesIn(string $collection, string ...$extensions): array
    {
        $directory = $this->directoryFor($collection);

        if ($directory === null || ! is_dir($directory)) {
            Log::debug('Content directory not present; treating collection as empty.', [
                'collection' => $collection,
                'directory' => $directory,
            ]);

            return [];
        }

        $entries = scandir($directory);

        if ($entries === false) {
            Log::error('Failed to list content directory.', [
                'collection' => $collection,
                'directory' => $directory,
            ]);

            return [];
        }

        $files = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            if (! is_file($path)) {
                continue;
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

            if (! in_array($extension, $extensions, true)) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }

    /**
     * Absolute path to a collection directory, or null if it is not configured.
     */
    private function directoryFor(string $collection): ?string
    {
        /** @var array<string, string> $map */
        $map = (array) config('electroserves.collections', []);
        $relative = $map[$collection] ?? null;

        if (! is_string($relative) || $relative === '') {
            Log::error('Unknown content collection requested.', ['collection' => $collection]);

            return null;
        }

        return rtrim((string) config('electroserves.content_path'), '/\\').DIRECTORY_SEPARATOR.$relative;
    }

    /**
     * Absolute path to a single file inside a collection directory.
     */
    private function pathFor(string $collection, string $filename): string
    {
        $directory = $this->directoryFor($collection);

        return ($directory ?? rtrim((string) config('electroserves.content_path'), '/\\'))
            .DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Derive a slug from a content filename, stripping any date prefix.
     *
     * Blog files are named `YYYY-MM-DD-my-post.md` per the Decap config, but
     * the public URL should be `/blog/my-post`.
     */
    private function slugFromFilename(string $path): string
    {
        $name = pathinfo($path, PATHINFO_FILENAME);

        return preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $name) ?? $name;
    }

    /**
     * Reject slugs that are empty or contain traversal / separator characters.
     *
     * Slugs reach this class straight from the URL, so this is the boundary
     * that prevents a crafted path from escaping the content directory.
     */
    private function normaliseSlug(string $slug): ?string
    {
        $slug = trim($slug);

        if ($slug === '' || strlen($slug) > 200) {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9._-]+$/', $slug) !== 1) {
            Log::info('Rejected content slug with unexpected characters.', ['slug' => $slug]);

            return null;
        }

        if (str_contains($slug, '..')) {
            Log::warning('Rejected content slug containing path traversal.', ['slug' => $slug]);

            return null;
        }

        return $slug;
    }

    /**
     * Memoise a content read, honouring the configured cache settings.
     *
     * A cache backend failure must not take the site down, so any throwable is
     * logged and the value is computed directly instead.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    private function remember(string $key, callable $callback): mixed
    {
        if (! (bool) config('electroserves.cache.enabled', true)) {
            return $callback();
        }

        $cacheKey = config('electroserves.cache.prefix', 'electroserves.content').'.'.$key;
        $ttl = (int) config('electroserves.cache.ttl', 300);

        try {
            return Cache::remember($cacheKey, $ttl, $callback);
        } catch (Throwable $e) {
            Log::error('Content cache unavailable; reading from disk.', [
                'key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * Every cache key this service may write, used by `flush()`.
     *
     * @return list<string>
     */
    private function cacheKeys(): array
    {
        $prefix = (string) config('electroserves.cache.prefix', 'electroserves.content');

        $keys = ['settings.site', 'settings.seo', 'services', 'projects', 'blog', 'testimonials', 'team', 'faqs', 'hero'];

        foreach (['about', 'privacy-policy', 'terms'] as $page) {
            $keys[] = 'pages.'.$page;
        }

        return array_map(static fn (string $key): string => $prefix.'.'.$key, $keys);
    }
}
