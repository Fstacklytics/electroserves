<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The headers required by docs/phase-0/08-threat-model.md must be present on
 * every response, including error responses.
 */
class SecurityHeadersTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function expectedHeaders(): array
    {
        return [
            ['X-Content-Type-Options', 'nosniff'],
            ['X-Frame-Options', 'SAMEORIGIN'],
            ['Referrer-Policy', 'strict-origin-when-cross-origin'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('expectedHeaders')]
    public function test_security_headers_are_present_on_page_responses(string $header, string $value): void
    {
        $this->get('/')->assertHeader($header, $value);
    }

    public function test_permissions_policy_disables_sensitive_features(): void
    {
        $response = $this->get('/');

        $policy = $response->headers->get('Permissions-Policy');

        $this->assertIsString($policy);
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }

    public function test_a_content_security_policy_is_set(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertIsString($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function test_security_headers_are_present_on_error_responses(): void
    {
        $response = $this->get('/a-page-that-does-not-exist');

        $response->assertNotFound();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_security_headers_are_present_on_redirects(): void
    {
        $response = $this->post(route('contact.store'), []);

        $response->assertRedirect();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        // Sending HSTS on an insecure local request would pin developers to
        // https://localhost.
        $this->assertNull($this->get('/')->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_is_sent_over_https(): void
    {
        $response = $this->get('https://localhost/');

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security'),
        );
    }
}
