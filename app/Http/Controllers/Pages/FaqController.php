<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\DataObjects\Faq;
use App\Http\Controllers\Controller;
use App\Services\ContentService;
use App\Services\MarkdownService;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly MarkdownService $markdown,
        private readonly SeoService $seo,
    ) {}

    public function index(): View
    {
        $grouped = $this->content->faqsByCategory();
        $all = $this->content->faqs();

        // Answers are Markdown; render them once here rather than in the view.
        $answers = $all->mapWithKeys(fn (Faq $faq): array => [
            $faq->anchorId() => $this->markdown->toHtml($faq->answer, 'faq:'.$faq->anchorId()),
        ])->all();

        $crumbs = [
            ['label' => __('common.nav.home'), 'url' => route('home')],
            ['label' => __('faq.title'), 'url' => null],
        ];

        return view('pages.faq', [
            'groupedFaqs' => $grouped,
            'answers' => $answers,
            'breadcrumbs' => $crumbs,
            'seo' => $this->seo->forPage([
                'title' => __('faq.title'),
                'description' => __('faq.meta_description'),
            ]),
            'schema' => $this->seo->toJsonLd($this->faqSchema($all->all())),
        ]);
    }

    /**
     * FAQPage structured data.
     *
     * @param  list<Faq>  $faqs
     * @return array<string, mixed>
     */
    private function faqSchema(array $faqs): array
    {
        if ($faqs === []) {
            return $this->seo->breadcrumbSchema([
                ['label' => __('common.nav.home'), 'url' => route('home')],
                ['label' => __('faq.title'), 'url' => null],
            ]);
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (Faq $faq): array => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ], $faqs),
        ];
    }
}
