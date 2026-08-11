<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
    ) {}

    /**
     * The About page.
     *
     * The Markdown body is optional: if content/pages/about.md is missing the
     * page still renders the team grid and an explanatory empty state.
     */
    public function index(): View
    {
        $page = $this->content->page('about');

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('about.title'), 'url' => null],
        ];

        return view('pages.about', [
            'page' => $page,
            'bodyHtml' => $page === null ? '' : $this->markdown->toHtml($page->body, 'page:about'),
            'team' => $this->content->teamMembers(),
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => $page?->title ?? __('about.title'),
                'description' => $page?->metaDescription ?? __('about.meta_description'),
                'image' => $page?->featuredImage,
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }
}
