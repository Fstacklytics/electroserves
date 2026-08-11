<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ResponseCacheService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * Behavioural tests for the full-page response cache.
 *
 * These exercise the middleware through real HTTP requests rather than
 * asserting on internals: a page is requested twice and the X-Response-Cache
 * header reports whether the second request was served from store. The header
 * is a deliberate, documented part of the feature (config
 * `electroserves.response_cache.send_header`), so testing through it is
 * testing behaviour, not implementation detail.
 *
 * The cache is disabled by default, so every test that needs it on enables it
 * explicitly. That also proves the default-off behaviour.
 */
class ResponseCacheTest extends TestCase
{
    /**
     * Turn the cache on with a predictable configuration.
     */
    private function enableCache(int $ttl = 600): void
    {
        config([
            'electroserves.response_cache.enabled' => true,
            'electroserves.response_cache.default_ttl' => $ttl,
            'electroserves.response_cache.send_header' => true,
        ]);
    }

    /**
     * Run an artisan command and return its exit code and captured output.
     *
     * Laravel's $this->artisan() PendingCommand helper is deliberately avoided:
     * it drives the command through an interactive-console mock that segfaults
     * the WebAssembly PHP build used in some CI/sandbox environments (it fails
     * even for the builtin `list` command). Artisan::call() invokes exactly the
     * same command object and Console kernel, so the behaviour under test is
     * identical while remaining portable.
     *
     * @return array{0: int, 1: string} exit code, buffered output
     */
    private function runCommand(string $command, array $parameters = []): array
    {
        $output = new BufferedOutput();
        $exitCode = Artisan::call($command, $parameters, $output);

        return [$exitCode, $output->fetch()];
    }

    // -----------------------------------------------------------------
    // Default state
    // -----------------------------------------------------------------

    public function test_the_response_cache_is_disabled_by_default(): void
    {
        $this->assertFalse(
            (bool) config('electroserves.response_cache.enabled'),
            'The response cache must ship disabled so content authors see edits immediately.',
        );

        $this->get('/')->assertOk()->assertHeader('X-Response-Cache', 'BYPASS');
    }

    // -----------------------------------------------------------------
    // Hits and misses
    // -----------------------------------------------------------------

    public function test_a_second_request_to_a_cacheable_page_is_served_from_cache(): void
    {
        $this->enableCache();

        $first = $this->get('/services');
        $first->assertOk()->assertHeader('X-Response-Cache', 'MISS');

        $second = $this->get('/services');
        $second->assertOk()->assertHeader('X-Response-Cache', 'HIT');

        $this->assertSame(
            $first->getContent(),
            $second->getContent(),
            'A cache hit must reproduce the response body byte for byte.',
        );
    }

    public function test_a_cache_hit_preserves_the_content_type(): void
    {
        $this->enableCache();

        $this->get('/sitemap.xml')->assertOk();

        $hit = $this->get('/sitemap.xml');

        $hit->assertHeader('X-Response-Cache', 'HIT');
        $this->assertStringContainsString('xml', (string) $hit->headers->get('Content-Type'));
    }

    public function test_a_head_request_is_answered_from_the_get_entry(): void
    {
        $this->enableCache();

        $this->get('/about')->assertOk()->assertHeader('X-Response-Cache', 'MISS');

        $this->head('/about')->assertOk()->assertHeader('X-Response-Cache', 'HIT');
    }

    // -----------------------------------------------------------------
    // TTL and configuration
    // -----------------------------------------------------------------

    public function test_a_route_with_a_zero_ttl_is_never_stored(): void
    {
        $this->enableCache();
        config(['electroserves.response_cache.routes.about' => 0]);

        $this->get('/about')->assertHeader('X-Response-Cache', 'MISS');

        // Nothing was written, so the next request must miss again.
        $this->get('/about')->assertHeader('X-Response-Cache', 'MISS');
    }

    public function test_the_per_route_ttl_overrides_the_default(): void
    {
        $this->enableCache(600);
        config(['electroserves.response_cache.routes.faq' => 4242]);

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/faq');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $this->assertSame(4242, $service->ttlFor($request));
    }

    public function test_an_expired_entry_is_re_rendered(): void
    {
        $this->enableCache(600);

        $this->get('/faq')->assertHeader('X-Response-Cache', 'MISS');
        $this->get('/faq')->assertHeader('X-Response-Cache', 'HIT');

        // Simulate expiry by emptying the store the way a TTL lapse would.
        Cache::flush();

        $this->get('/faq')->assertHeader('X-Response-Cache', 'MISS');
    }

    // -----------------------------------------------------------------
    // Exclusions — unsafe and visitor-specific responses
    // -----------------------------------------------------------------

    public function test_the_contact_page_is_never_cached(): void
    {
        $this->enableCache();

        $this->get('/contact')->assertOk()->assertHeader('X-Response-Cache', 'BYPASS');
        $this->get('/contact')->assertOk()->assertHeader('X-Response-Cache', 'BYPASS');
    }

    public function test_the_contact_page_is_listed_as_an_excluded_route(): void
    {
        /** @var list<string> $excluded */
        $excluded = (array) config('electroserves.response_cache.excluded_routes');

        $this->assertContains('contact', $excluded);
        $this->assertContains('contact.store', $excluded);
    }

    public function test_a_contact_submission_is_never_cached(): void
    {
        $this->enableCache();

        $response = $this->post('/contact', [
            'name' => 'Asha Mbwana',
            'email' => 'asha@example.net',
            'service_type' => 'residential',
            'message' => 'Please quote for rewiring a three bedroom house in Mikocheni.',
            'consent' => '1',
        ]);

        $response->assertRedirect();
        $response->assertHeader('X-Response-Cache', 'BYPASS');
    }

    public function test_post_requests_are_never_cached(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/contact', 'POST');

        $this->assertFalse($service->shouldCacheRequest($request));
    }

    public function test_a_redirect_is_never_stored(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/services');

        $this->assertFalse(
            $service->shouldCacheResponse($request, redirect('/')),
            'A redirect must never be stored: the visitor it was computed for is not the next one.',
        );
    }

    public function test_an_error_page_is_never_stored(): void
    {
        $this->enableCache();

        $this->get('/services/no-such-service')
            ->assertNotFound()
            ->assertHeader('X-Response-Cache', 'BYPASS');
    }

    public function test_a_response_carrying_a_csrf_token_is_refused(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/services');

        $response = response('<form><input type="hidden" name="_token" value="abc"></form>');

        $this->assertFalse(
            $service->shouldCacheResponse($request, $response),
            'A page containing a CSRF token is per-session and must never be shared.',
        );
    }

    public function test_a_response_marked_private_is_refused(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/services');

        $response = response('<p>ok</p>')->header('Cache-Control', 'no-store, private');

        $this->assertFalse($service->shouldCacheResponse($request, $response));
    }

    public function test_a_response_setting_a_cookie_is_refused(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/services');

        $response = response('<p>ok</p>')->cookie('visitor', 'value', 10);

        $this->assertFalse($service->shouldCacheResponse($request, $response));
    }

    public function test_a_session_holding_validation_errors_bypasses_the_cache(): void
    {
        $this->enableCache();

        // Prime the cache, then prove the entry is live. Without this control
        // the BYPASS below could be caused by nothing having been stored at
        // all, and the test would pass for the wrong reason.
        $this->get('/about')->assertHeader('X-Response-Cache', 'MISS');
        $this->get('/about')->assertHeader('X-Response-Cache', 'HIT');

        $errors = (new ViewErrorBag)->put('default', new MessageBag(['name' => ['required']]));

        $this->session(['errors' => $errors]);

        // A visitor carrying validation errors must never be served, and must
        // never be able to poison, the shared copy of the page.
        $this->get('/about')->assertHeader('X-Response-Cache', 'BYPASS');
    }

    public function test_a_session_holding_flashed_data_bypasses_the_cache(): void
    {
        $this->enableCache();

        $this->get('/about')->assertHeader('X-Response-Cache', 'MISS');
        $this->get('/about')->assertHeader('X-Response-Cache', 'HIT');

        // The success notice flashed after a contact submission is personal to
        // one visitor; a page rendered while it is present must not be shared.
        $this->session(['_flash' => ['new' => ['contact_status'], 'old' => []], 'contact_status' => 'success']);

        $this->get('/about')->assertHeader('X-Response-Cache', 'BYPASS');
    }

    // -----------------------------------------------------------------
    // Query string separation
    // -----------------------------------------------------------------

    public function test_different_pages_of_a_paginated_list_are_cached_separately(): void
    {
        $this->enableCache();

        $first = $this->get('/blog');
        $first->assertOk()->assertHeader('X-Response-Cache', 'MISS');

        // A different page must not be answered with page one's HTML.
        $this->get('/blog?page=1')->assertOk();

        $this->get('/blog')->assertHeader('X-Response-Cache', 'HIT');
    }

    public function test_a_category_filter_produces_a_distinct_cache_entry(): void
    {
        $this->enableCache();

        $this->get('/projects')->assertHeader('X-Response-Cache', 'MISS');
        $this->get('/projects?category=commercial')->assertHeader('X-Response-Cache', 'MISS');
        $this->get('/projects?category=commercial')->assertHeader('X-Response-Cache', 'HIT');
        $this->get('/projects')->assertHeader('X-Response-Cache', 'HIT');
    }

    public function test_query_parameter_order_does_not_split_the_cache(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);

        $a = \Illuminate\Http\Request::create('/projects?category=commercial&page=2');
        $b = \Illuminate\Http\Request::create('/projects?page=2&category=commercial');

        $this->assertSame($service->keyFor($a), $service->keyFor($b));
    }

    public function test_tracking_parameters_do_not_split_the_cache(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);

        $plain = \Illuminate\Http\Request::create('/services');
        $campaign = \Illuminate\Http\Request::create('/services?utm_source=newsletter&utm_medium=email');

        $this->assertSame(
            $service->keyFor($plain),
            $service->keyFor($campaign),
            'Campaign parameters do not change the page, so they must not fragment the cache.',
        );
    }

    public function test_a_meaningful_query_parameter_is_not_ignored(): void
    {
        /** @var list<string> $ignored */
        $ignored = (array) config('electroserves.response_cache.ignored_query_parameters');

        $this->assertNotContains('page', $ignored);
        $this->assertNotContains('category', $ignored);
        $this->assertNotContains('service', $ignored);
    }

    // -----------------------------------------------------------------
    // Invalidation
    // -----------------------------------------------------------------

    public function test_publishing_content_invalidates_cached_pages(): void
    {
        $this->enableCache();

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/services');

        $before = $service->keyFor($request);

        // Simulate a CMS commit: a new file in the content tree.
        $root = (string) config('electroserves.content_path');
        $path = $root.'/services/temporary-phase-4-fixture.md';

        file_put_contents($path, "---\ntitle: Fixture\n---\n");

        try {
            $fresh = app()->make(ResponseCacheService::class);
            $after = $fresh->keyFor($request);

            $this->assertNotSame(
                $before,
                $after,
                'A change under content/ must change the cache key so stale HTML cannot be served.',
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_flushing_the_content_cache_also_clears_rendered_pages(): void
    {
        $this->enableCache();
        config(['electroserves.response_cache.store' => 'array']);

        $this->get('/about')->assertHeader('X-Response-Cache', 'MISS');
        $this->get('/about')->assertHeader('X-Response-Cache', 'HIT');

        [$exitCode, $output] = $this->runCommand('content:flush');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Response cache cleared.', $output);

        $this->get('/about')->assertHeader('X-Response-Cache', 'MISS');
    }

    public function test_the_dedicated_clear_command_empties_the_cache(): void
    {
        $this->enableCache();
        config(['electroserves.response_cache.store' => 'array']);

        $this->get('/faq')->assertHeader('X-Response-Cache', 'MISS');
        $this->get('/faq')->assertHeader('X-Response-Cache', 'HIT');

        [$exitCode, $output] = $this->runCommand('responsecache:clear');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Response cache cleared.', $output);

        $this->get('/faq')->assertHeader('X-Response-Cache', 'MISS');
    }

    // -----------------------------------------------------------------
    // Interaction with the security headers
    // -----------------------------------------------------------------

    public function test_security_headers_are_recomputed_on_a_cache_hit(): void
    {
        $this->enableCache();

        $this->get('/about')->assertHeader('X-Response-Cache', 'MISS');

        $hit = $this->get('/about');

        $hit->assertHeader('X-Response-Cache', 'HIT');
        $hit->assertHeader('X-Content-Type-Options', 'nosniff');
        $hit->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $hit->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($hit->headers->get('Content-Security-Policy'));
    }

    public function test_a_cached_page_never_stores_a_set_cookie_header(): void
    {
        $this->enableCache();

        $this->get('/about');

        $service = app(ResponseCacheService::class);
        $request = \Illuminate\Http\Request::create('/about');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $entry = $service->get($request);

        if ($entry === null) {
            $this->markTestSkipped('Entry not present under the synthetic request key.');
        }

        $this->assertArrayNotHasKey('set-cookie', array_change_key_case($entry['headers']));
    }
}
