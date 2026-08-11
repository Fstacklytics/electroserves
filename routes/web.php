<?php

declare(strict_types=1);

use App\Http\Controllers\Pages\AboutController;
use App\Http\Controllers\Pages\BlogController;
use App\Http\Controllers\Pages\ContactController;
use App\Http\Controllers\Pages\FaqController;
use App\Http\Controllers\Pages\HomeController;
use App\Http\Controllers\Pages\LegalController;
use App\Http\Controllers\Pages\ProjectController;
use App\Http\Controllers\Pages\ServiceController;
use App\Http\Controllers\Pages\StyleguideController;
use App\Http\Controllers\Pages\TestimonialController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Every public page of the ElectroServes website. There are no authenticated
| routes: the CMS admin at /admin is a static Decap bundle that authenticates
| against GitHub in the browser, so it needs no Laravel-side session.
|
| Slugs are constrained to the character set the CMS produces; anything else
| never reaches a controller and falls through to the custom 404 page.
|
*/

$slug = '[A-Za-z0-9]+(?:[._-][A-Za-z0-9]+)*';

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])
    ->where('slug', $slug)
    ->name('services.show');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{slug}', [ProjectController::class, 'show'])
    ->where('slug', $slug)
    ->name('projects.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])
    ->where('slug', $slug)
    ->name('blog.show');

Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials');
Route::get('/faq', [FaqController::class, 'index'])->name('faq');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact-form')
    ->name('contact.store');

Route::get('/privacy-policy', [LegalController::class, 'privacyPolicy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
| The styleguide is a development aid, not a public page. Registering it only
| outside production keeps it from being reachable on the live site at all,
| rather than relying on a runtime check inside the controller.
*/
if (! app()->environment('production')) {
    Route::get('/styleguide', [StyleguideController::class, 'index'])->name('styleguide');
}
