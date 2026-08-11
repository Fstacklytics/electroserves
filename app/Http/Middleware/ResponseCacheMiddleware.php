<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ResponseCacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves and stores full-page responses via ResponseCacheService.
 *
 * Placement matters. This middleware is appended to the `web` group, so it runs
 * *inside* SecurityHeadersMiddleware (which is appended globally and therefore
 * wraps it). Consequences, both intentional:
 *
 *   1. Security headers are recomputed on every response, hit or miss. A cached
 *      page can never replay a stale CSP or a stale Cache-Control.
 *   2. The session has been started by the time this runs, so the service can
 *      inspect it for validation errors, flashed status and old input, and
 *      refuse to cache anything visitor-specific.
 *
 * The decision logic lives in the service; this class only wires it into the
 * pipeline and adds the diagnostic header.
 */
class ResponseCacheMiddleware
{
    public function __construct(
        private readonly ResponseCacheService $cache,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cache->shouldCacheRequest($request)) {
            return $this->tag($next($request), 'BYPASS');
        }

        $cached = $this->cache->get($request);

        if ($cached !== null) {
            return $this->tag(
                response($cached['content'], $cached['status'], $cached['headers']),
                'HIT',
            );
        }

        /** @var Response $response */
        $response = $next($request);

        if ($this->cache->shouldCacheResponse($request, $response)) {
            $this->cache->put($request, $response);

            return $this->tag($response, 'MISS');
        }

        return $this->tag($response, 'BYPASS');
    }

    /**
     * Record why a response was or was not cached.
     *
     * This is a diagnostic aid for operators verifying a deployment; it exposes
     * no visitor data and can be switched off in config.
     */
    private function tag(Response $response, string $state): Response
    {
        if ($this->cache->shouldSendHeader()) {
            $response->headers->set('X-Response-Cache', $state);
        }

        return $response;
    }
}
