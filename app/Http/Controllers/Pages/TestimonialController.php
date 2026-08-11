<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly SeoService $seo,
    ) {}

    public function index(): View
    {
        $testimonials = $this->content->testimonials();

        // Only offer the service filter for services that are actually
        // referenced, so the control never shows an option with no results.
        $services = $this->content->services();
        $usedSlugs = $testimonials->pluck('serviceUsed')->filter()->unique()->all();
        $filters = $services
            ->filter(fn ($service): bool => in_array($service->slug, $usedSlugs, true))
            ->mapWithKeys(fn ($service): array => [$service->slug => $service->title])
            ->all();

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('testimonials.title'), 'url' => null],
        ];

        return view('pages.testimonials', [
            'testimonials' => $testimonials,
            'filters' => $filters,
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => __('testimonials.title'),
                'description' => __('testimonials.meta_description'),
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }
}
