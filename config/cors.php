<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Credentialed requests forbid a wildcard origin, so the Next.js dev server
    // (and any deployed frontend) must be listed explicitly. Extra origins such
    // as preview deployments go in CORS_ALLOWED_ORIGINS, comma separated.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        array_merge(
            [(string) env('FRONTEND_URL', 'http://localhost:3000')],
            explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
        ),
    ))),

    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGIN_PATTERNS', '')),
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 3600),

    'supports_credentials' => true,

];
