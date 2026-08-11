<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the deployment artefacts in deploy/.
 *
 * WHAT THESE TESTS CAN AND CANNOT PROVE
 * -------------------------------------
 * Nginx is not installed in CI or in this project's development container, so
 * `nginx -t` cannot be run here and these tests do NOT prove the file parses.
 * That check belongs to the deployment itself, where deploy/README.md requires
 * `sudo nginx -t` to pass before the config is activated.
 *
 * What these tests do prove is the part that silently rots: the config
 * agreeing with the application. A directive such as the PHP-FPM socket path
 * or the response-cache exclusion list is correct only relative to something
 * else in this repository, and when that other thing changes nobody thinks to
 * reopen the Nginx file. Each assertion below therefore ties a line of
 * deploy/nginx.conf to the application fact it depends on, and fails when the
 * two drift apart. The remainder assert the security properties that must hold
 * regardless of environment — TLS floor, no directory listing, no wildcard
 * CORS, hidden files denied — because a regression there is not visible from
 * the outside until it is exploited.
 */
class DeploymentConfigTest extends TestCase
{
    private function nginxConf(): string
    {
        $path = base_path('deploy/nginx.conf');

        $this->assertFileExists($path, 'deploy/nginx.conf is missing.');

        return (string) file_get_contents($path);
    }

    private function readme(): string
    {
        $path = base_path('deploy/README.md');

        $this->assertFileExists($path, 'deploy/README.md is missing.');

        return (string) file_get_contents($path);
    }

    // -----------------------------------------------------------------
    // TLS
    // -----------------------------------------------------------------

    public function test_only_tls_1_2_and_1_3_are_offered(): void
    {
        $conf = $this->nginxConf();

        $this->assertMatchesRegularExpression(
            '/^\s*ssl_protocols\s+TLSv1\.2\s+TLSv1\.3\s*;/m',
            $conf,
            'ssl_protocols must offer exactly TLS 1.2 and 1.3.'
        );

        foreach (['TLSv1;', 'TLSv1.1', 'SSLv3', 'SSLv2'] as $obsolete) {
            $this->assertStringNotContainsString(
                $obsolete,
                $conf,
                "Obsolete protocol {$obsolete} must not be enabled."
            );
        }
    }

    public function test_the_cipher_list_contains_no_known_broken_primitives(): void
    {
        $conf = $this->nginxConf();

        // Matched case-insensitively against the whole file: a weak suite is
        // just as dangerous if it is added in a comment-and-paste later.
        foreach (['RC4', 'DES-CBC3', '3DES', 'MD5', 'EXPORT', 'aNULL', 'eNULL'] as $weak) {
            $this->assertStringNotContainsStringIgnoringCase(
                $weak,
                $conf,
                "Cipher list must not include {$weak}."
            );
        }
    }

    public function test_plain_http_redirects_to_https(): void
    {
        $conf = $this->nginxConf();

        $this->assertMatchesRegularExpression(
            '/return\s+301\s+https:\/\/\$host\$request_uri\s*;/',
            $conf,
            'Port 80 must issue a permanent redirect to HTTPS.'
        );
    }

    public function test_the_acme_challenge_path_stays_reachable_over_plain_http(): void
    {
        // If this prefix is ever swallowed by the dotfile deny rule, certificate
        // renewal fails ~60 days later, long after the change is forgotten.
        $this->assertStringContainsString(
            'location ^~ /.well-known/acme-challenge/',
            $this->nginxConf(),
            'Certbot HTTP-01 challenges must not be blocked.'
        );
    }

    // -----------------------------------------------------------------
    // Front controller and PHP execution
    // -----------------------------------------------------------------

    public function test_the_document_root_is_the_public_directory(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\s*root\s+\S*\/public\s*;/m',
            $this->nginxConf(),
            'The document root must end in /public so .env, storage/ and vendor/ are unreachable.'
        );
    }

    public function test_requests_fall_through_to_the_laravel_front_controller(): void
    {
        $this->assertStringContainsString(
            'try_files $uri $uri/ /index.php?$query_string;',
            $this->nginxConf(),
            'Pretty URLs require the standard Laravel try_files fallback.'
        );
    }

    public function test_php_execution_is_restricted_to_the_front_controller(): void
    {
        $conf = $this->nginxConf();

        $this->assertMatchesRegularExpression(
            '/location\s+~\s+\^\/index\\\\\.php\(\/\|\$\)\s*\{/',
            $conf,
            'PHP should be handled by an anchored /index.php location, not a bare \.php$ match.'
        );

        // Any other .php file must be refused rather than executed.
        $this->assertMatchesRegularExpression(
            '/location\s+~\s+\\\\\.php\$\s*\{[^}]*deny all;/s',
            $conf,
            'Arbitrary .php files must be denied.'
        );
    }

    public function test_php_is_never_handed_a_path_that_is_not_a_real_file(): void
    {
        $conf = $this->nginxConf();

        // Guards the classic /uploads/evil.jpg/index.php path-info attack.
        $phpBlock = $this->blockAfter($conf, 'location ~ ^/index\.php(/|$) {');

        $this->assertStringContainsString(
            'try_files $uri =404;',
            $phpBlock,
            'The PHP location must verify the script exists before passing it to FPM.'
        );

        $this->assertStringContainsString(
            'fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;',
            $phpBlock,
            'SCRIPT_FILENAME must use $realpath_root so opcache follows release symlink flips.'
        );
    }

    public function test_the_fpm_socket_matches_the_php_version_the_project_requires(): void
    {
        $composer = json_decode(
            (string) file_get_contents(base_path('composer.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        // "^8.3" -> "8.3". The socket path in the config embeds the version,
        // so bumping the platform requirement without touching deploy/ leaves
        // a config that cannot connect to FPM at all.
        preg_match('/(\d+\.\d+)/', (string) $composer['require']['php'], $matches);
        $version = $matches[1];

        $this->assertStringContainsString(
            "php{$version}-fpm.sock",
            $this->nginxConf(),
            "The fastcgi_pass socket must reference PHP {$version}, matching composer.json."
        );
    }

    public function test_the_proxy_scheme_is_forwarded_so_the_app_can_detect_https(): void
    {
        $conf = $this->nginxConf();

        // SecurityHeadersMiddleware only emits HSTS when $request->isSecure().
        $this->assertStringContainsString('fastcgi_param HTTPS on;', $conf);
        $this->assertStringContainsString('fastcgi_param REQUEST_SCHEME https;', $conf);
    }

    // -----------------------------------------------------------------
    // Exposure
    // -----------------------------------------------------------------

    public function test_directory_listing_is_disabled(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\s*autoindex\s+off\s*;/m',
            $this->nginxConf()
        );
        $this->assertStringNotContainsString('autoindex on', $this->nginxConf());
    }

    public function test_hidden_files_are_denied(): void
    {
        $conf = $this->nginxConf();

        $this->assertMatchesRegularExpression(
            '/location\s+~\s+\/\\\\\.\s*\{[^}]*deny all;/s',
            $conf,
            'Dotfiles such as .env and .git must be denied.'
        );
    }

    public function test_sensitive_project_files_are_denied(): void
    {
        $conf = $this->nginxConf();

        // Plain substring checks: the deny rule is itself a regex, so the
        // filenames appear in the file with their dots escaped
        // (`composer\.json`), and searching for them with a regex would need
        // double escaping for no benefit.
        foreach (['composer', 'artisan', 'phpunit', 'package'] as $needle) {
            $this->assertStringContainsString(
                $needle,
                $conf,
                "The deny list should cover {$needle}."
            );
        }

        foreach (['vendor', 'storage', 'bootstrap', 'content'] as $directory) {
            $this->assertStringContainsString(
                $directory,
                $conf,
                "Directory {$directory} should be explicitly denied as defence in depth."
            );
        }
    }

    public function test_the_nginx_version_is_not_advertised(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\s*server_tokens\s+off\s*;/m',
            $this->nginxConf()
        );
    }

    public function test_a_request_body_limit_is_set(): void
    {
        $conf = $this->nginxConf();

        $this->assertMatchesRegularExpression(
            '/^\s*client_max_body_size\s+(\d+)([kKmM])\s*;/m',
            $conf,
            'An explicit client_max_body_size protects PHP-FPM from large uploads.'
        );

        preg_match('/client_max_body_size\s+(\d+)([kKmM])\s*;/', $conf, $matches);
        $bytes = (int) $matches[1] * (strtolower($matches[2]) === 'm' ? 1048576 : 1024);

        // The site accepts no file uploads; anything large is a mistake or abuse.
        $this->assertLessThanOrEqual(
            10 * 1048576,
            $bytes,
            'The body limit is far larger than a text-only contact form needs.'
        );
    }

    public function test_timeouts_are_bounded(): void
    {
        $conf = $this->nginxConf();

        foreach (['client_body_timeout', 'client_header_timeout', 'send_timeout', 'fastcgi_read_timeout'] as $directive) {
            $this->assertMatchesRegularExpression(
                '/^\s*'.$directive.'\s+\d+[a-z]*\s*;/m',
                $conf,
                "{$directive} must be set so a slow client cannot hold a worker open."
            );
        }
    }

    public function test_no_wildcard_cors_header_is_sent(): void
    {
        $conf = $this->nginxConf();

        // Match only real directives; the file explains in prose why '*' is
        // forbidden, and that explanation must not trip this test.
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*add_header\s+Access-Control-Allow-Origin/mi',
            $conf,
            'This site exposes no cross-origin API; it must not send CORS headers.'
        );
    }

    // -----------------------------------------------------------------
    // Caching — agreement with the application
    // -----------------------------------------------------------------

    public function test_hashed_build_assets_are_cached_immutably(): void
    {
        $conf = $this->nginxConf();

        $buildBlock = $this->blockAfter($conf, 'location ^~ /build/ {');

        $this->assertStringContainsString('immutable', $buildBlock);
        $this->assertStringContainsString('max-age=31536000', $buildBlock);
    }

    public function test_the_immutable_location_matches_where_vite_actually_writes(): void
    {
        $manifest = base_path('public/build/manifest.json');

        if (! file_exists($manifest)) {
            $this->markTestSkipped('Run `npm run build` first; the manifest is required to verify asset paths.');
        }

        $entries = json_decode((string) file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);

        $files = [];
        foreach ($entries as $entry) {
            if (isset($entry['file'])) {
                $files[] = $entry['file'];
            }
            foreach ($entry['css'] ?? [] as $css) {
                $files[] = $css;
            }
        }

        $this->assertNotEmpty($files, 'The Vite manifest lists no output files.');

        foreach ($files as $file) {
            // Vite's buildDirectory is `build`, so every emitted asset is
            // served from /build/... and is therefore covered by the
            // immutable location above.
            $this->assertStringStartsWith(
                'assets/',
                $file,
                "Asset {$file} is not under the hashed assets directory the Nginx rule assumes."
            );

            $this->assertMatchesRegularExpression(
                '/-[A-Za-z0-9_-]{8,}\.[a-z]+$/',
                $file,
                "Asset {$file} has no content hash, so caching it immutably would be unsafe."
            );
        }
    }

    public function test_the_contact_form_is_never_cached_by_the_proxy(): void
    {
        $conf = $this->nginxConf();

        $contactBlock = $this->blockAfter($conf, 'location = /contact {');

        $this->assertStringContainsString('fastcgi_no_cache 1;', $contactBlock);
        $this->assertStringContainsString('proxy_no_cache 1;', $contactBlock);
    }

    public function test_nginx_does_not_proxy_cache_php_responses(): void
    {
        $conf = $this->nginxConf();

        // Page caching is Laravel's job precisely because it knows which
        // routes carry a CSRF token or session state. A stray fastcgi_cache
        // here would bypass every one of those rules.
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*fastcgi_cache\s+\w/m',
            $conf,
            'Enabling fastcgi_cache would bypass ResponseCacheService and risk sharing CSRF tokens.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/^\s*fastcgi_cache_path\s/m',
            $conf
        );
    }

    public function test_every_route_laravel_refuses_to_cache_is_also_uncached_at_the_edge(): void
    {
        $excluded = config('electroserves.response_cache.excluded_routes');

        $this->assertContains('contact', $excluded, 'Sanity check on the application config.');

        // The contact page is the only excluded route that exists in
        // production (`styleguide` is not registered there, and contact.store
        // is a POST, which no cache layer stores). It must have an explicit
        // no-cache block in the server config.
        $this->assertStringContainsString(
            'location = /contact {',
            $this->nginxConf(),
            'The contact route is excluded from the application cache and needs the same treatment at the edge.'
        );
    }

    public function test_security_headers_are_not_duplicated_for_php_responses(): void
    {
        $conf = $this->nginxConf();

        // The middleware owns these. Re-adding one here inside a location
        // would drop the rest through add_header inheritance, so the config
        // must leave them alone on any path that reaches PHP.
        foreach (['Content-Security-Policy', 'Strict-Transport-Security', 'Referrer-Policy', 'Permissions-Policy'] as $header) {
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*add_header\s+'.preg_quote($header, '/').'/mi',
                $conf,
                "{$header} is set by SecurityHeadersMiddleware; duplicating it in Nginx risks dropping the others."
            );
        }
    }

    // -----------------------------------------------------------------
    // Rate limiting
    // -----------------------------------------------------------------

    public function test_the_edge_rate_limit_is_looser_than_the_application_limit(): void
    {
        $conf = $this->nginxConf();

        $this->assertMatchesRegularExpression(
            '/limit_req_zone .*zone=electroserves_contact/',
            $conf,
            'A contact-form rate limit zone should be documented.'
        );

        preg_match('/zone=electroserves_contact:\d+[a-z]\s+rate=(\d+)r\/([sm])/i', $conf, $matches);
        $this->assertNotEmpty($matches, 'Could not parse the contact rate limit.');

        $nginxPerHour = (int) $matches[1] * (strtolower($matches[2]) === 'm' ? 60 : 3600);

        $appMax = (int) config('electroserves.contact.rate_limit.max_attempts');
        $appDecay = (int) config('electroserves.contact.rate_limit.decay_seconds');
        $appPerHour = $appMax * (3600 / $appDecay);

        // Laravel must be the layer that rejects a normal over-eager visitor,
        // so it returns the translated message instead of a bare Nginx 429.
        $this->assertGreaterThan(
            $appPerHour,
            $nginxPerHour,
            'The Nginx limit must stay looser than the application limiter, or visitors get an untranslated 429.'
        );
    }

    // -----------------------------------------------------------------
    // Documentation
    // -----------------------------------------------------------------

    public function test_the_deployment_readme_covers_the_required_operations(): void
    {
        $readme = strtolower($this->readme());

        $topics = [
            'nginx -t' => 'validating the Nginx config',
            'certbot' => 'TLS certificate issuance',
            'php artisan config:cache' => 'framework caching',
            'responsecache:clear' => 'response cache invalidation',
            'content:flush' => 'content cache invalidation',
            'npm run build' => 'asset compilation',
            'rollback' => 'rollback procedure',
            'backup' => 'backup procedure',
            'app_key' => 'application key handling',
        ];

        foreach ($topics as $needle => $description) {
            $this->assertStringContainsString(
                $needle,
                $readme,
                "deploy/README.md should document {$description}."
            );
        }
    }

    public function test_the_deployment_docs_contain_no_real_credentials(): void
    {
        $readme = $this->readme();

        // A README is the most common place for a copied-in production secret.
        $this->assertDoesNotMatchRegularExpression(
            '/APP_KEY\s*=\s*base64:[A-Za-z0-9+\/]{40,}={0,2}/',
            $readme,
            'A real APP_KEY must never be committed; show the key:generate command instead.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/MAIL_PASSWORD\s*=\s*\S+/',
            $readme,
            'Mail credentials must not appear in documentation.'
        );
    }

    public function test_the_referenced_artisan_commands_exist(): void
    {
        $readme = $this->readme();

        preg_match_all('/php artisan ([a-z0-9:\-]+)/', $readme, $matches);

        $this->assertNotEmpty($matches[1], 'The README should show artisan commands.');

        $registered = array_keys(\Illuminate\Support\Facades\Artisan::all());

        foreach (array_unique($matches[1]) as $command) {
            $this->assertContains(
                $command,
                $registered,
                "deploy/README.md documents `php artisan {$command}`, which is not a registered command."
            );
        }
    }

    /**
     * Return the body of the first brace-delimited block starting at $marker.
     *
     * Written by hand rather than with a regex because Nginx blocks nest, and
     * a non-greedy `[^}]*` match would stop at the first inner closing brace
     * and silently pass tests that should fail.
     */
    private function blockAfter(string $conf, string $marker): string
    {
        $start = strpos($conf, $marker);

        $this->assertNotFalse($start, "Could not find `{$marker}` in deploy/nginx.conf.");

        $offset = $start + strlen($marker);
        $depth = 1;

        for ($i = $offset; $i < strlen($conf); $i++) {
            if ($conf[$i] === '{') {
                $depth++;
            } elseif ($conf[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($conf, $offset, $i - $offset);
                }
            }
        }

        $this->fail("Unbalanced braces after `{$marker}` in deploy/nginx.conf.");
    }
}
