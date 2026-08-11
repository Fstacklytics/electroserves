<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Defines the named rate limiters used by routes.
 *
 * Implements mitigation T3 in docs/phase-0/08-threat-model.md: the contact form
 * accepts at most 5 submissions per IP per hour.
 */
class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('contact-form', function (Request $request): Limit {
            $maxAttempts = (int) config('electroserves.contact.rate_limit.max_attempts', 5);
            $decaySeconds = (int) config('electroserves.contact.rate_limit.decay_seconds', 3600);

            // Limit is constructed directly so the decay window can be
            // expressed in seconds, which the named factories do not allow.
            return (new Limit(
                key: 'contact-form',
                maxAttempts: $maxAttempts,
                decaySeconds: $decaySeconds,
            ))
                ->by($request->ip() ?? 'unknown')
                ->response(function (Request $request, array $headers) {
                    Log::warning('Contact form rate limit exceeded.', [
                        'ip' => $request->ip(),
                    ]);

                    // Send the visitor back to the form with a message that
                    // explains the wait and offers the phone number instead.
                    return redirect()
                        ->route('contact')
                        ->withHeaders($headers)
                        ->with('contact_status', 'rate_limited');
                });
        });
    }
}
