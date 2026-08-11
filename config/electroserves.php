<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ElectroServes Application Configuration
|--------------------------------------------------------------------------
|
| Application-specific settings. Content is file-based (no database), so the
| paths below define where the CMS-managed Markdown/YAML lives, and the cache
| block controls how long parsed content is memoised.
|
| Nothing user-facing should be hardcoded in views or controllers — strings
| belong in lang files, and structural values belong here.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Content Root
    |--------------------------------------------------------------------------
    |
    | Absolute path to the directory holding all CMS-managed content. Decap CMS
    | commits into this directory; Laravel only ever reads from it.
    |
    */

    'content_path' => env('CONTENT_PATH', base_path('content')),

    /*
    |--------------------------------------------------------------------------
    | Collection Paths
    |--------------------------------------------------------------------------
    |
    | Relative to `content_path`. These mirror the collections defined in
    | docs/phase-0/10-decap-cms-config.yml and the data model document.
    |
    */

    'collections' => [
        'settings' => 'settings',
        'hero' => 'hero',
        'services' => 'services',
        'projects' => 'projects',
        'blog' => 'blog',
        'testimonials' => 'testimonials',
        'team' => 'team',
        'faqs' => 'faqs',
        'pages' => 'pages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Cache
    |--------------------------------------------------------------------------
    |
    | Parsed content is cached to avoid re-reading and re-parsing files on every
    | request. TTL is in seconds. Set `enabled` to false during content authoring
    | so changes appear immediately.
    |
    */

    'cache' => [
        'enabled' => env('CONTENT_CACHE_ENABLED', true),
        'ttl' => (int) env('CONTENT_CACHE_TTL', 300),
        'prefix' => 'electroserves.content',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Cache
    |--------------------------------------------------------------------------
    |
    | Full-page HTML caching for anonymous visitors, implemented by
    | App\Services\ResponseCacheService and applied by
    | App\Http\Middleware\ResponseCacheMiddleware.
    |
    | This sits on top of the content cache above: that one memoises *parsed*
    | Markdown and YAML, this one memoises the *rendered page*. Both are keyed
    | so that publishing content rolls them over.
    |
    | Disabled by default. It is a production optimisation, and leaving it off
    | locally means authors see their edits immediately. Enable it in
    | production with RESPONSE_CACHE_ENABLED=true.
    |
    | Safety rules (enforced in the service, not merely documented here):
    |   - GET and HEAD only;
    |   - only plain 200 responses — never redirects, never error pages;
    |   - never when the session holds validation errors, flashed data or old
    |     input;
    |   - never when the response sets a cookie or is marked no-store/private;
    |   - never when the body contains a CSRF token;
    |   - never for the routes listed in `excluded_routes`.
    |
    */

    'response_cache' => [

        'enabled' => env('RESPONSE_CACHE_ENABLED', false),

        /*
         * Cache store for rendered pages. Null uses the application default.
         * Naming a dedicated store (for example a separate `file` store, or a
         * separate Redis database) lets `responsecache:clear` empty it
         * wholesale without touching the content cache; see deploy/README.md.
         */
        'store' => env('RESPONSE_CACHE_STORE'),

        'prefix' => 'electroserves.response',

        /*
         * Fallback TTL in seconds for any cacheable route not listed below.
         * Zero disables caching for those routes.
         */
        'default_ttl' => (int) env('RESPONSE_CACHE_TTL', 600),

        /*
         * Per-route TTLs, keyed by route name. Pages that change rarely are
         * held longer; pages that reflect newly published content are held
         * briefly so an editor sees their work quickly even before the content
         * fingerprint rolls over.
         */
        'routes' => [
            'home' => (int) env('RESPONSE_CACHE_TTL_HOME', 600),
            'services.index' => (int) env('RESPONSE_CACHE_TTL_SERVICES', 1800),
            'services.show' => (int) env('RESPONSE_CACHE_TTL_SERVICES', 1800),
            'projects.index' => (int) env('RESPONSE_CACHE_TTL_PROJECTS', 1800),
            'projects.show' => (int) env('RESPONSE_CACHE_TTL_PROJECTS', 1800),
            'blog.index' => (int) env('RESPONSE_CACHE_TTL_BLOG', 600),
            'blog.show' => (int) env('RESPONSE_CACHE_TTL_BLOG', 600),
            'about' => (int) env('RESPONSE_CACHE_TTL_STATIC', 3600),
            'testimonials' => (int) env('RESPONSE_CACHE_TTL_STATIC', 3600),
            'faq' => (int) env('RESPONSE_CACHE_TTL_STATIC', 3600),
            'privacy' => (int) env('RESPONSE_CACHE_TTL_LEGAL', 86400),
            'terms' => (int) env('RESPONSE_CACHE_TTL_LEGAL', 86400),
            'sitemap' => (int) env('RESPONSE_CACHE_TTL_SITEMAP', 3600),
            'robots' => (int) env('RESPONSE_CACHE_TTL_SITEMAP', 3600),
        ],

        /*
         * Routes that must never be cached.
         *
         * `contact` renders a CSRF token and stores `contact_form_rendered_at`
         * in the session for the minimum-submit-time spam check; a shared copy
         * would both leak a token and defeat that check. `contact.store` is a
         * POST and is excluded by method as well — it is listed here so the
         * intent survives any future change to the route.
         *
         * `styleguide` is a development aid and is not registered in
         * production at all.
         */
        'excluded_routes' => [
            'contact',
            'contact.store',
            'styleguide',
        ],

        /*
         * Path patterns (Request::is syntax) that must never be cached, for
         * anything not reached through a named route.
         */
        'excluded_paths' => [
            'admin',
            'admin/*',
            'up',
        ],

        /*
         * Query parameters stripped before building the cache key, so that
         * inbound campaign links do not each create their own entry. Anything
         * that genuinely changes the page — `page`, `category`, `service` —
         * is deliberately absent from this list.
         */
        'ignored_query_parameters' => [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'gclid',
            'fbclid',
            'ref',
        ],

        /*
         * Emit X-Response-Cache: HIT|MISS|BYPASS. Useful when validating a
         * deployment; harmless to leave on, as it describes only the cache
         * decision.
         */
        'send_header' => env('RESPONSE_CACHE_HEADER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact Form
    |--------------------------------------------------------------------------
    |
    | The notification address receives contact form submissions. Rate limiting
    | and the honeypot field name implement the mitigations for threat T3
    | (contact form abuse) in docs/phase-0/08-threat-model.md.
    |
    */

    'contact' => [
        'notification_email' => env('CONTACT_NOTIFICATION_EMAIL'),
        'rate_limit' => [
            'max_attempts' => (int) env('CONTACT_RATE_LIMIT_MAX', 5),
            'decay_seconds' => (int) env('CONTACT_RATE_LIMIT_DECAY', 3600),
        ],
        'honeypot_field' => 'website_url',
        'min_submit_seconds' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'projects' => 12,
        'blog' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Service & Project Categories
    |--------------------------------------------------------------------------
    |
    | Kept in sync with the `select` widget options in the Decap CMS config.
    | Used to validate content frontmatter and to build filter controls.
    |
    */

    'service_categories' => [
        'residential' => 'Residential',
        'commercial' => 'Commercial',
        'electronics' => 'Electronics',
        'emergency' => 'Emergency',
        'installation' => 'Installation',
    ],

    'project_categories' => [
        'residential' => 'Residential',
        'commercial' => 'Commercial',
        'industrial' => 'Industrial',
        'electronics' => 'Electronics',
        'installation' => 'Installation',
    ],

    'blog_categories' => [
        'Tips & Advice',
        'Industry News',
        'Company Updates',
        'Project Spotlights',
        'Safety',
    ],

    'faq_categories' => [
        'General',
        'Services',
        'Pricing',
        'Emergency',
        'Warranty',
    ],

    /*
    |--------------------------------------------------------------------------
    | Required Environment Variables
    |--------------------------------------------------------------------------
    |
    | Validated at boot by App\Providers\EnvironmentValidationServiceProvider.
    | The application refuses to serve traffic if any of these are missing, so
    | misconfiguration surfaces immediately instead of as a runtime failure.
    |
    */

    'required_env' => [
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_URL',
        'MAIL_MAILER',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
        'CONTACT_NOTIFICATION_EMAIL',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMTP-Only Environment Variables
    |--------------------------------------------------------------------------
    |
    | Only required when MAIL_MAILER=smtp. Drivers such as `log` or `array`
    | (used in tests and local development) do not need a mail host.
    |
    */

    'required_env_smtp' => [
        'MAIL_HOST',
        'MAIL_PORT',
    ],

];
