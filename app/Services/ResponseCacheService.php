<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Full-page response cache for the public, file-backed pages.
 *
 * Why this exists
 * ---------------
 * Every public page is assembled from Markdown and YAML on disk. Parsing is
 * already memoised by ContentService, but rendering — Blade compilation,
 * Markdown to HTML, SEO tag construction, schema.org JSON — still runs on every
 * request. Caching the finished HTML removes that work for anonymous visitors,
 * which is the entire audience of this site.
 *
 * What it deliberately does NOT do
 * --------------------------------
 * It never caches anything that varies per visitor. The contact page carries a
 * CSRF token and a session timestamp; validation errors and flash messages are
 * session state; redirects and error pages are transient. All of these are
 * excluded, and a body-level CSRF guard backs the route-level exclusions up so
 * that a future page that happens to embed a token cannot be cached by
 * accident.
 *
 * Relationship to SecurityHeadersMiddleware
 * -----------------------------------------
 * This service stores the body, status and a short allowlist of headers. It
 * never stores Cache-Control, CSP, HSTS or any other security header: those are
 * recomputed per request by SecurityHeadersMiddleware, which sits *outside*
 * this middleware in the stack. A cached page therefore cannot pin a stale
 * policy, and the middleware's `no-store, private` rule for error-bearing
 * sessions continues to win.
 *
 * Relationship to Nginx
 * ---------------------
 * deploy/nginx.conf does not proxy-cache HTML. This is the single HTML cache,
 * so there is exactly one place to reason about and one place to purge.
 */
class ResponseCacheService
{
    /**
     * Headers worth replaying. Everything else is either recomputed per request
     * (security headers), connection-specific (Date, Connection), or visitor
     * state (Set-Cookie).
     *
     * @var list<string>
     */
    private const REPLAYED_HEADERS = ['content-type', 'content-language', 'link', 'vary'];

    /**
     * The value Symfony synthesises when the application sets no Cache-Control.
     *
     * It looks like a privacy directive but carries no intent, so it must not
     * be mistaken for one. See shouldCacheResponse().
     */
    private const SYMFONY_DEFAULT_CACHE_CONTROL = 'no-cache, private';

    /**
     * Memoised content fingerprint for the current request.
     */
    private ?string $fingerprint = null;

    public function enabled(): bool
    {
        return (bool) config('electroserves.response_cache.enabled', false);
    }

    /**
     * Whether this request is eligible to be served from, or written to, cache.
     *
     * Only idempotent, unauthenticated, query-normalised page requests qualify.
     */
    public function shouldCacheRequest(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return false;
        }

        // A visitor mid-conversation with the server (validation errors, a
        // flashed status message, a pending form timestamp) must always get a
        // freshly rendered page.
        if ($this->hasVisitorState($request)) {
            return false;
        }

        return ! $this->isExcludedRoute($request);
    }

    /**
     * Whether a rendered response may be stored.
     *
     * Anything that is not a plain, complete, self-contained 200 is refused.
     */
    public function shouldCacheResponse(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if ($response->isRedirection()) {
            return false;
        }

        // Streamed and binary responses have no body to capture.
        if (! $response instanceof \Illuminate\Http\Response) {
            return false;
        }

        // An upstream decision to keep this response private always wins.
        //
        // Symfony's ResponseHeaderBag *computes* a Cache-Control value when the
        // application has not set one, and that computed default is literally
        // "no-cache, private". Treating it as an explicit privacy directive
        // would disable the cache for every page on the site, so the default is
        // recognised and skipped; anything else is honoured.
        $cacheControl = strtolower(trim((string) $response->headers->get('Cache-Control', '')));

        if ($cacheControl !== self::SYMFONY_DEFAULT_CACHE_CONTROL
            && preg_match('/\b(no-store|private)\b/', $cacheControl) === 1
        ) {
            return false;
        }

        if (count($response->headers->getCookies()) > 0) {
            return false;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return false;
        }

        // Backstop for the route exclusions: a CSRF token is per-session, so a
        // page containing one can never be shared between visitors.
        if ($this->containsCsrfToken($content)) {
            Log::warning('Refused to cache a response containing a CSRF token.', [
                'path' => $request->path(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Retrieve a stored response payload, or null on a miss.
     *
     * @return array{status: int, headers: array<string, string>, content: string}|null
     */
    public function get(Request $request): ?array
    {
        try {
            $entry = Cache::store($this->store())->get($this->keyFor($request));
        } catch (Throwable $e) {
            // A cache backend outage must slow the site down, not break it.
            Log::error('Response cache unavailable on read.', ['error' => $e->getMessage()]);

            return null;
        }

        if (! is_array($entry)
            || ! isset($entry['status'], $entry['headers'], $entry['content'])
            || ! is_int($entry['status'])
            || ! is_array($entry['headers'])
            || ! is_string($entry['content'])
        ) {
            return null;
        }

        /** @var array{status: int, headers: array<string, string>, content: string} $entry */
        return $entry;
    }

    /**
     * Store a rendered response for the configured TTL of its route.
     */
    public function put(Request $request, Response $response): void
    {
        $ttl = $this->ttlFor($request);

        if ($ttl <= 0) {
            return;
        }

        $headers = [];

        foreach (self::REPLAYED_HEADERS as $name) {
            $value = $response->headers->get($name);

            if (is_string($value) && $value !== '') {
                $headers[$name] = $value;
            }
        }

        try {
            Cache::store($this->store())->put(
                $this->keyFor($request),
                [
                    'status' => $response->getStatusCode(),
                    'headers' => $headers,
                    'content' => (string) $response->getContent(),
                ],
                $ttl,
            );
        } catch (Throwable $e) {
            Log::error('Response cache unavailable on write.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cache key for a request.
     *
     * The key folds in the content fingerprint, so publishing through the CMS
     * changes every key at once and stale HTML can never be served. Query
     * strings are normalised (sorted, tracking parameters dropped) so that
     * `?page=2&category=commercial` and `?category=commercial&page=2` are one
     * entry while `?page=2` and `?page=3` remain separate.
     */
    public function keyFor(Request $request): string
    {
        $prefix = (string) config('electroserves.response_cache.prefix', 'electroserves.response');

        $parts = [
            $request->getScheme(),
            $request->getHttpHost(),
            // HEAD is answered from the GET entry; Symfony strips the body.
            'GET',
            '/'.ltrim($request->path(), '/'),
            $this->normalisedQuery($request),
            $this->contentFingerprint(),
            (string) app()->getLocale(),
        ];

        return $prefix.'.'.hash('xxh128', implode('|', $parts));
    }

    /**
     * TTL in seconds for the current request's route.
     */
    public function ttlFor(Request $request): int
    {
        /** @var array<string, int> $perRoute */
        $perRoute = (array) config('electroserves.response_cache.routes', []);

        $name = $request->route()?->getName();

        if (is_string($name) && array_key_exists($name, $perRoute)) {
            return (int) $perRoute[$name];
        }

        return (int) config('electroserves.response_cache.default_ttl', 0);
    }

    /**
     * Drop every cached page.
     *
     * Called by `php artisan responsecache:clear` and by `content:flush`, so a
     * deploy or a CMS publish never leaves stale HTML behind.
     */
    public function flush(): bool
    {
        $this->fingerprint = null;

        try {
            $store = Cache::store($this->store());

            // A dedicated store can be emptied wholesale. Sharing the default
            // store with the content cache means we must not wipe it, so we
            // rely on the fingerprint rolling the keys over instead; entries
            // then expire on their own TTL.
            if ($this->hasDedicatedStore()) {
                return $store->clear();
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Response cache could not be flushed.', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * A cheap fingerprint of the content directory.
     *
     * Decap CMS commits files; a deploy or webhook then changes mtimes. Hashing
     * the relative paths and mtimes of the content tree means any add, edit,
     * rename or delete produces a different fingerprint and therefore a
     * different key namespace — invalidation without a purge list.
     */
    public function contentFingerprint(): string
    {
        if ($this->fingerprint !== null) {
            return $this->fingerprint;
        }

        $root = (string) config('electroserves.content_path', base_path('content'));

        if (! is_dir($root)) {
            return $this->fingerprint = 'no-content';
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            );

            $parts = [];

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $parts[] = str_replace($root, '', $file->getPathname()).':'.$file->getMTime();
            }

            sort($parts);

            return $this->fingerprint = hash('xxh128', implode('|', $parts));
        } catch (Throwable $e) {
            // If the tree cannot be walked, refuse to reuse any previous
            // fingerprint: a unique value disables cache reuse for this request
            // rather than risking a stale page.
            Log::error('Could not fingerprint the content directory.', ['error' => $e->getMessage()]);

            return $this->fingerprint = 'unknown-'.bin2hex(random_bytes(8));
        }
    }

    /**
     * Whether the HIT/MISS diagnostic header should be emitted.
     */
    public function shouldSendHeader(): bool
    {
        return (bool) config('electroserves.response_cache.send_header', true);
    }

    /**
     * Route names that must never be cached.
     */
    private function isExcludedRoute(Request $request): bool
    {
        /** @var list<string> $excluded */
        $excluded = (array) config('electroserves.response_cache.excluded_routes', []);

        $name = $request->route()?->getName();

        if (is_string($name) && in_array($name, $excluded, true)) {
            return true;
        }

        /** @var list<string> $paths */
        $paths = (array) config('electroserves.response_cache.excluded_paths', []);

        return $paths !== [] && $request->is(...$paths);
    }

    /**
     * Whether the session holds anything that makes this response personal.
     */
    private function hasVisitorState(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        if ($session->has('errors')) {
            return true;
        }

        // Any flashed key (contact_status, old input) makes the render one-off.
        $flashed = $session->get('_flash.new', []);
        $kept = $session->get('_flash.old', []);

        if ((is_array($flashed) && $flashed !== []) || (is_array($kept) && $kept !== [])) {
            return true;
        }

        return (bool) $session->hasOldInput();
    }

    /**
     * Sorted query string with tracking parameters removed.
     */
    private function normalisedQuery(Request $request): string
    {
        /** @var list<string> $ignored */
        $ignored = (array) config('electroserves.response_cache.ignored_query_parameters', []);

        $query = $request->query();

        foreach ($ignored as $parameter) {
            unset($query[$parameter]);
        }

        ksort($query);

        return http_build_query($query);
    }

    private function containsCsrfToken(string $content): bool
    {
        return str_contains($content, 'name="_token"')
            || str_contains($content, 'name="csrf-token"');
    }

    private function store(): ?string
    {
        $store = config('electroserves.response_cache.store');

        return is_string($store) && $store !== '' ? $store : null;
    }

    private function hasDedicatedStore(): bool
    {
        return $this->store() !== null;
    }
}
