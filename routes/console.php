<?php

declare(strict_types=1);

use App\Services\ContentService;
use App\Services\ResponseCacheService;
use Illuminate\Support\Facades\Artisan;

/*
| Console entry points.
|
| The site has no scheduled jobs. These commands exist so a deployment or a CMS
| webhook can drop the caches without clearing every cache in the application.
*/

Artisan::command('content:flush', function (ContentService $content, ResponseCacheService $responses): int {
    $content->flush();

    // Rendered pages are built from parsed content, so dropping one without
    // the other would leave the site serving HTML built from content that has
    // already been discarded.
    $responses->flush();

    $this->info('Content cache cleared.');
    $this->info('Response cache cleared.');

    return 0;
})->purpose('Clear the parsed Markdown/YAML content cache and the rendered page cache');

Artisan::command('responsecache:clear', function (ResponseCacheService $responses): int {
    if (! $responses->flush()) {
        $this->error('Response cache could not be cleared; see the application log.');

        return 1;
    }

    $this->info('Response cache cleared.');

    return 0;
})->purpose('Clear the rendered page cache');
