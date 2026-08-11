<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentService;
use Illuminate\Http\Response;

/**
 * Generates the XML sitemap and robots.txt from the live content.
 *
 * Both are produced at request time rather than committed as static files, so
 * a page published through the CMS is discoverable immediately.
 */
class SitemapController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
    ) {}

    public function index(): Response
    {
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('services.index'), 'changefreq' => 'monthly', 'priority' => '0.9'],
            ['loc' => route('projects.index'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('blog.index'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => route('about'), 'changefreq' => 'yearly', 'priority' => '0.7'],
            ['loc' => route('testimonials'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('faq'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('contact'), 'changefreq' => 'yearly', 'priority' => '0.9'],
            ['loc' => route('privacy'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => route('terms'), 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        foreach ($this->content->services() as $service) {
            $urls[] = [
                'loc' => route('services.show', $service->slug),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        foreach ($this->content->projects() as $project) {
            $urls[] = [
                'loc' => route('projects.show', $project->slug),
                'lastmod' => $project->completionDateIso(),
                'changefreq' => 'yearly',
                'priority' => '0.6',
            ];
        }

        foreach ($this->content->blogPosts() as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->dateIso(),
                'changefreq' => 'yearly',
                'priority' => '0.6',
            ];
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * robots.txt.
     *
     * Non-production environments are marked noindex so a staging deployment
     * can never compete with the live site in search results.
     */
    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->environment('production')) {
            $lines[] = 'Disallow: /admin';
            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
