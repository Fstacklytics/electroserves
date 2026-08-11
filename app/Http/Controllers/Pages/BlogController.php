<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
    ) {}

    public function index(Request $request): View
    {
        $allPosts = $this->content->blogPosts();
        $categories = $this->content->activeBlogCategories();
        $perPage = max(1, (int) config('electroserves.pagination.blog', 6));
        $page = max(1, $request->integer('page', 1));
        $lastPage = max(1, (int) ceil($allPosts->count() / $perPage));

        abort_if($page > $lastPage, 404);

        $requestedCategory = $request->query('category');
        $activeCategory = is_string($requestedCategory) && in_array($requestedCategory, $categories, true)
            ? $requestedCategory
            : 'all';
        $query = $request->query();

        if ($activeCategory === 'all') {
            unset($query['category']);
        }

        $posts = new LengthAwarePaginator(
            $allPosts->forPage($page, $perPage)->values(),
            $allPosts->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $query,
            ],
        );

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('blog.index.title'), 'url' => null],
        ];

        return view('pages.blog.index', [
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => __('blog.index.title'),
                'description' => __('blog.index.meta_description'),
                'canonical' => route('blog.index', $page > 1 ? ['page' => $page] : []),
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
