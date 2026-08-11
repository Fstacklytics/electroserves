<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
    ) {}

    public function privacyPolicy(): View
    {
        return $this->renderLegalPage('privacy-policy', __('legal.privacy_title'), 'privacy');
    }

    public function terms(): View
    {
        return $this->renderLegalPage('terms', __('legal.terms_title'), 'terms');
    }

    /**
     * Render a legal page from Markdown.
     *
     * A missing file must not 404: these pages are linked from the footer of
     * every page and are a legal requirement, so the view renders a clear
     * "being updated" state with a contact route instead.
     */
    private function renderLegalPage(string $slug, string $fallbackTitle, string $routeName): View
    {
        $page = $this->content->page($slug);

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => $page?->title ?? $fallbackTitle, 'url' => null],
        ];

        return view('pages.legal', [
            'page' => $page,
            'fallbackTitle' => $fallbackTitle,
            'bodyHtml' => $page === null ? '' : $this->markdown->toHtmlWithAnchors($page->body, 'page:'.$slug),
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => $page?->title ?? $fallbackTitle,
                'description' => $page?->metaDescription ?? __('legal.meta_description', ['title' => $fallbackTitle]),
                'canonical' => route($routeName),
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }
}
