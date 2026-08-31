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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    | Connecteurs de collecte de sources publiques (Phase 09). Wikimedia
    | (Wikipedia/Wikidata) et Nominatim exigent un User-Agent descriptif
    | identifiant l'application et un contact — pas de clé requise.
    */
    'sources' => [
        'user_agent' => env('SOURCES_USER_AGENT', 'TOPsocietesBot/1.0 (+https://topsocietes.com; contact@topsocietes.com)'),
        'wikipedia' => [
            'base_url' => env('WIKIPEDIA_BASE_URL', 'https://fr.wikipedia.org'),
        ],
        'wikidata' => [
            'base_url' => env('WIKIDATA_BASE_URL', 'https://www.wikidata.org'),
        ],
        'nominatim' => [
            'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
        ],
    ],

];
