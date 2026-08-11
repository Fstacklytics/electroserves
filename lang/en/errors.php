<?php

declare(strict_types=1);

return [

    '404' => [
        'code' => '404',
        'title' => 'We could not find that page',
        'body' => 'The page you asked for does not exist, or it may have been moved. The links below should get you back on track.',
    ],

    '403' => [
        'code' => '403',
        'title' => 'That page is not available to you',
        'body' => 'You do not have permission to view this page. If you believe this is a mistake, please get in touch.',
    ],

    '419' => [
        'code' => '419',
        'title' => 'Your session expired',
        'body' => 'For your security, the form you were filling in timed out. Please go back and submit it again.',
    ],

    '429' => [
        'code' => '429',
        'title' => 'Too many requests',
        'body' => 'You have made a lot of requests in a short time. Please wait a moment and try again.',
    ],

    '500' => [
        'code' => '500',
        'title' => 'Something went wrong on our side',
        'body' => 'We hit an unexpected problem. Our team has been notified. Please try again in a few minutes.',
    ],

    '503' => [
        'code' => '503',
        'title' => 'We are down for maintenance',
        'body' => 'The site is briefly unavailable while we make an update. Please try again shortly.',
    ],

    'actions' => [
        'home' => 'Go to the homepage',
        'services' => 'Browse our services',
        'contact' => 'Contact support',
        'retry' => 'Try again',
        'back' => 'Go back',
    ],

    'help' => 'If you need help right now, call us on :phone.',

];
