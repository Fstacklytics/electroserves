<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
    ) {}

    public function index(): View
    {
        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('blog.index.title'), 'url' => null],
        ];

        return view('pages.blog.index', [
            'posts' => $this->content->blogPosts(),
            'categories' => $this->content->activeBlogCategories(),
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => __('blog.index.title'),
                'description' => __('blog.index.meta_description'),
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->content->findBlogPost($slug);

        if ($post === null) {
            throw new NotFoundHttpException("No blog post exists for slug [{$slug}].");
        }

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('blog.index.title'), 'url' => route('blog.index')],
            ['label' => $post->title, 'url' => null],
        ];

        return view('pages.blog.show', [
            'post' => $post,
            'bodyHtml' => $this->markdown->toHtmlWithAnchors($post->body, 'blog:'.$post->slug),
            'headings' => $this->markdown->extractHeadings($post->body),
            'relatedPosts' => $this->content->relatedBlogPosts($post),
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => $post->title,
                'description' => $post->seoDescription(),
                'image' => $post->featuredImage,
                'type' => 'article',
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }
}
