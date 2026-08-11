<?php

declare(strict_types=1);

use App\Http\Middleware\ResponseCacheMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Security headers are applied to every response, including error
        // pages and redirects.
        $middleware->append(SecurityHeadersMiddleware::class);

        // Full-page cache for anonymous visitors. Appended to the `web` group
        // rather than globally so it runs *inside* the security headers
        // middleware and *after* the session has started: a cached page can
        // therefore never replay a stale CSP or Cache-Control, and the cache
        // can see whether the session holds visitor-specific state.
        $middleware->appendToGroup('web', ResponseCacheMiddleware::class);

        // Respect the reverse proxy so isSecure(), the canonical URL, and the
        // per-IP rate limiter all see the real visitor scheme and address.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Contact form PII must never be written to the log context of an
        // unrelated exception report.
        $exceptions->dontFlash(['name', 'email', 'phone', 'message']);

        /*
         * Render friendly, branded error pages for HTTP errors.
         *
         * Laravel already resolves resources/views/errors/{code}.blade.php, but
         * doing this explicitly lets us log 404s with the path that produced
         * them, which is how we detect broken CMS links.
         */
        $exceptions->render(function (HttpExceptionInterface $e, Request $request): ?Response {
            $status = $e->getStatusCode();

            if ($status === 404) {
                Log::info('Page not found.', [
                    'path' => $request->path(),
                    'referer' => $request->headers->get('referer'),
                ]);
            }

            if ($request->expectsJson()) {
                return null;
            }

            if (! view()->exists("errors.{$status}")) {
                return null;
            }

            return response()->view("errors.{$status}", [], $status);
        });
    })
    ->create();
