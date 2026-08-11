<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
    ) {}

    public function index(): View
    {
        return view('pages.services.index', [
            'services' => $this->content->services(),
            'categories' => $this->content->activeServiceCategories(),
            'seo' => $this->seo->forPage([
                'title' => __('services.index.title'),
                'description' => __('services.index.meta_description'),
            ]),
            'breadcrumbs' => $crumbs = [
                ['label' => __('common.nav.home'), 'url' => route('home')],
                ['label' => __('services.index.title'), 'url' => null],
            ],
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }

    /**
     * A single service.
     *
     * An unknown slug raises a 404 so it renders the custom error page rather
     * than an empty service detail view.
     */
    public function show(string $slug): View
    {
        $service = $this->content->findService($slug);

        if ($service === null) {
            throw new NotFoundHttpException("No service exists for slug [{$slug}].");
        }

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('services.index.title'), 'url' => route('services.index')],
            ['label' => $service->title, 'url' => null],
        ];

        return view('pages.services.show', [
            'service' => $service,
            'bodyHtml' => $this->markdown->toHtml($service->body, 'service:'.$service->slug),
            'relatedProjects' => $this->content->projectsForService($service->slug),
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => $service->title,
                'description' => $service->seoDescription(),
                'image' => $service->image,
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }
}
