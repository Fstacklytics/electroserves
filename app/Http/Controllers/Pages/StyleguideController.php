<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Illuminate\Contracts\View\View;

/**
 * Living reference page for the design system.
 *
 * The route is only registered outside production (see routes/web.php), so this
 * is unreachable on the live site.
 */
class StyleguideController extends Controller
{
    public function __construct(
        private readonly SeoService $seo,
    ) {}

    public function index(): View
    {
        return view('pages.styleguide', [
            'seo' => $this->seo->forPage([
                'title' => __('styleguide.title'),
                'description' => __('styleguide.description'),
                // Never index the component gallery, even if it were exposed.
                'robots' => 'noindex, nofollow',
            ]),
        ]);
    }
}
