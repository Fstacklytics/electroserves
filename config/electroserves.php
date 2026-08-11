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
