<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'timeout' => 120,
        // Free tier quota is ~20 req/min, so a 429 typically clears in
        // ~60s. We retry up to this many times, respecting the Retry-After
        // hint Gemini returns in the 429 body.
        'max_retries' => env('GEMINI_MAX_RETRIES', 5),
        // Upper bound (seconds) for how long a single retry may sleep before
        // the next attempt. Keeps the queue worker responsive.
        'rate_limit_max_wait_sec' => env('GEMINI_RATE_LIMIT_MAX_WAIT_SEC', 90),
    ],

];
