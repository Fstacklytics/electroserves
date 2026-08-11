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

class ProjectController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
    ) {}

    public function index(Request $request): View
    {
        $allProjects = $this->content->projects();
        $categories = $this->content->activeProjectCategories();
        $perPage = max(1, (int) config('electroserves.pagination.projects', 12));
        $page = max(1, $request->integer('page', 1));
        $lastPage = max(1, (int) ceil($allProjects->count() / $perPage));

        abort_if($page > $lastPage, 404);

        $requestedCategory = $request->query('category');
        $activeCategory = is_string($requestedCategory) && array_key_exists($requestedCategory, $categories)
            ? $requestedCategory
            : 'all';
        $query = $request->query();

        if ($activeCategory === 'all') {
            unset($query['category']);
        }

        $projects = new LengthAwarePaginator(
            $allProjects->forPage($page, $perPage)->values(),
            $allProjects->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $query,
            ],
        );

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('projects.index.title'), 'url' => null],
        ];

        return view('pages.projects.index', [
            'projects' => $projects,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => __('projects.index.title'),
                'description' => __('projects.index.meta_description'),
                'canonical' => route('projects.index', $page > 1 ? ['page' => $page] : []),
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }

    public function show(string $slug): View
    {
        $project = $this->content->findProject($slug);

        if ($project === null) {
            throw new NotFoundHttpException("No project exists for slug [{$slug}].");
        }

        // Resolve linked service slugs to real services; a stale reference in
        // the frontmatter is dropped rather than rendered as a dead link.
        $services = $this->content->services()
            ->filter(fn ($service): bool => in_array($service->slug, $project->servicesUsed, true))
            ->values();

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('projects.index.title'), 'url' => route('projects.index')],
            ['label' => $project->title, 'url' => null],
        ];

        return view('pages.projects.show', [
            'project' => $project,
            'bodyHtml' => $this->markdown->toHtml($project->body, 'project:'.$project->slug),
            'linkedServices' => $services,
            'relatedProjects' => $this->content->relatedProjects($project),
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => $project->title,
                'description' => $project->seoDescription(),
                'image' => $project->featuredImage,
                'type' => 'article',
            ]),
            'schema' => $this->seo->toJsonLd($this->seo->breadcrumbSchema($crumbs)),
        ]);
    }
}
