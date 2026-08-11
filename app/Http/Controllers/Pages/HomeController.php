<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly SeoService $seo,
    ) {}

    /**
     * The homepage.
     *
     * Every section degrades independently: if a collection is empty the view
     * renders that section's empty state instead of failing the whole page.
     */
    public function index(): View
    {
        $settings = $this->content->siteSettings();

        return view('pages.home', [
            'slides' => $this->content->heroSlides(),
            'services' => $this->content->services()->take(8),
            'totalServices' => $this->content->services()->count(),
            'featuredProjects' => $this->content->featuredProjects(6),
            'testimonials' => $this->content->testimonials()->take(6),
            'settings' => $settings,
            'seo' => $this->seo->forPage([
                'description' => $settings->tagline !== '' ? $settings->tagline : null,
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->organizationSchema($settings)),
        ]);
    }
}
