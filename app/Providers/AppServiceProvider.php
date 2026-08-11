<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ContentService;
use App\Services\ImageService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use App\Services\YamlService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     *
     * Content services are singletons: each holds a per-request parse cache, so
     * sharing one instance avoids re-reading the same files within a request.
     */
    public function register(): void
    {
        $this->app->singleton(YamlService::class);
        $this->app->singleton(MarkdownService::class);
        $this->app->singleton(ImageService::class);
        $this->app->singleton(ContentService::class);
        $this->app->singleton(SeoService::class);
    }

    public function boot(): void
    {
        // Enforce HTTPS everywhere except local development so no page can emit
        // a mixed-content URL (see A02 in the threat model).
        if (! $this->app->environment('local', 'testing')) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('components.common.pagination-links');
        Paginator::defaultSimpleView('components.common.pagination-links');

        // The documented project structure keeps layouts in resources/views/layouts
        // rather than resources/views/components/layouts. Registering the views
        // root as an anonymous component path lets <x-layouts.app> resolve to
        // resources/views/layouts/app.blade.php without duplicating the file.
        Blade::anonymousComponentPath(resource_path('views'));

        $this->shareGlobalViewData();
    }

    /**
     * Share the data every layout needs: site settings and navigation.
     *
     * Using a view composer keeps controllers from having to remember to pass
     * settings, and guarantees the navbar/footer always have a value — the
     * ContentService returns a fallback object rather than null.
     */
    private function shareGlobalViewData(): void
    {
        View::composer('*', function ($view): void {
            /** @var ContentService $content */
            $content = $this->app->make(ContentService::class);

            $view->with('siteSettings', $content->siteSettings());
        });
    }
}
