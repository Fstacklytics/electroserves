<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the security headers required by docs/phase-0/08-threat-model.md.
 *
 * These are also set in deploy/nginx.conf for defence in depth; setting them
 * here means they are present in every environment, including local dev and
 * the test suite, so regressions are caught by tests rather than in production.
 */
class SecurityHeadersMiddleware
{
    /**
     * Headers applied to every response.
     *
     * @return array<string, string>
     */
    public static function headers(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        foreach (self::headers() as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request), false);

        // HSTS is only meaningful over TLS, and sending it on plain HTTP during
        // local development would pin developers to https://localhost.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        // Never let a proxy or browser cache a page that carries a CSRF token
        // alongside validation errors for a specific visitor.
        //
        // This middleware is global, so it unwinds *outside* the `web` group:
        // by the time we get here StartSession has already saved the session,
        // and saving ages the flash data, which forgets the `errors` key. Asking
        // the session directly therefore always answered "no" and this header
        // was never sent. ShareErrorsFromSession copies the bag into the view
        // factory during the request and nothing ages that copy, so it is the
        // signal that is still readable at this point.
        if ($response->isRedirection() === false && $request->hasSession() && $this->hasValidationErrors()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /**
     * Whether this request rendered validation errors.
     *
     * Reads the bag shared with the view layer rather than the session, for the
     * flash-ageing reason described above. Requests that never reached the
     * `web` group have no shared bag and no validation errors, so returning
     * false for them is correct.
     */
    private function hasValidationErrors(): bool
    {
        $shared = ViewFacade::shared('errors');

        if ($shared instanceof ViewErrorBag) {
            return $shared->any();
        }

        return false;

        return $response;
    }

    /**
     * Build the Content-Security-Policy for the current request.
     *
     * The CMS admin route needs a broader policy than the public site: Decap
     * loads from a CDN, talks to the GitHub API, and renders previews in a
     * blob: worker. Widening the policy only for /admin keeps the public pages
     * — the part exposed to anonymous visitors — locked down.
     */
    private function contentSecurityPolicy(Request $request): string
    {
        if ($this->isAdminRequest($request)) {
            return $this->policy([
                'default-src' => "'self'",
                // Decap CMS is delivered from unpkg and evaluates its own bundle.
                'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net",
                'style-src' => "'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net https://fonts.googleapis.com",
                'font-src' => "'self' data: https://fonts.gstatic.com",
                'img-src' => "'self' data: blob: https:",
                'connect-src' => "'self' https://api.github.com https://unpkg.com https://cdn.jsdelivr.net",
                'frame-src' => "'self' blob:",
                'worker-src' => "'self' blob:",
                'form-action' => "'self' https://github.com",
                'base-uri' => "'self'",
                'object-src' => "'none'",
            ]);
        }

        $directives = [
            'default-src' => "'self'",
            // Alpine.js initialises from inline attributes (x-data, x-on), which
            // requires 'unsafe-inline'. No user-controlled content is ever
            // interpolated into a script context, and Markdown HTML is stripped
            // of inline HTML entirely, so this does not create an XSS sink.
            'script-src' => "'self' 'unsafe-inline'",
            'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
            'font-src' => "'self' data: https://fonts.gstatic.com",
            'img-src' => "'self' data:",
            'connect-src' => "'self'",
            // Google Maps is embedded on the contact page when configured.
            'frame-src' => "'self' https://www.google.com https://maps.google.com",
            'frame-ancestors' => "'self'",
            'form-action' => "'self'",
            'base-uri' => "'self'",
            'object-src' => "'none'",
        ];

        if (! $this->isLocalDevelopment()) {
            $directives['upgrade-insecure-requests'] = '';
        }

        return $this->policy($directives);
    }

    /**
     * @param  array<string, string>  $directives
     */
    private function policy(array $directives): string
    {
        $parts = [];

        foreach ($directives as $name => $value) {
            $parts[] = $value === '' ? $name : $name.' '.$value;
        }

        return implode('; ', $parts);
    }

    private function isAdminRequest(Request $request): bool
    {
        return $request->is('admin', 'admin/*');
    }

    /**
     * Vite's dev server is served over plain HTTP, so upgrade-insecure-requests
     * would break asset loading during local development.
     */
    private function isLocalDevelopment(): bool
    {
        return app()->environment('local', 'testing');
    }
}
