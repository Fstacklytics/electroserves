<?php

declare(strict_types=1);

use App\Services\ContentService;
use Illuminate\Support\Facades\Artisan;

/*
| Console entry points.
|
| The site has no scheduled jobs. `content:flush` exists so a deployment or a
| CMS webhook can drop the parsed-content cache without clearing every cache.
*/

Artisan::command('content:flush', function (ContentService $content): int {
    $content->flush();

    $this->info('Content cache cleared.');

    return 0;
})->purpose('Clear the parsed Markdown/YAML content cache');
