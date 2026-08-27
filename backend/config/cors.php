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

    // FRONTEND_URL couvre le dev local ; CORS_ALLOWED_ORIGINS_PATTERNS couvre
    // les sous-domaines pays (fr.topsocietes.com, be.topsocietes.com, ...)
    // sans les enumerer un a un — cf. countries en base, CLAUDE.md §6.
    'allowed_origins' => array_values(array_filter(explode(
        ',',
        (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', ''))
    ))),

    'allowed_origins_patterns' => array_values(array_filter(explode(
        ',',
        (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', '')
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Sanctum SPA (cookies de session) exige les credentials cross-origin —
    // voir docs/adr/0004-billing-auth.md.
    'supports_credentials' => true,

];
