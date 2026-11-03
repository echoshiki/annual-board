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

    'tmdb' => [
        'token' => env('TMDB_API_TOKEN'),
        'key' => env('TMDB_API_KEY'),
        'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
        'image_base_url' => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/w500'),
        'timeout' => (int) env('TMDB_TIMEOUT', 10),
    ],

    'musicbrainz' => [
        'client_id' => env('MUSICBRAINZ_CLIENT_ID'),
        'client_secret' => env('MUSICBRAINZ_CLIENT_SECRET'),
        'base_url' => env('MUSICBRAINZ_BASE_URL', 'https://musicbrainz.org/ws/2'),
        'cover_base_url' => env('MUSICBRAINZ_COVER_BASE_URL', 'https://coverartarchive.org'),
        'user_agent' => env('MUSICBRAINZ_USER_AGENT', 'AnnualBoard/1.0 (contact@example.com)'),
        'timeout' => (int) env('MUSICBRAINZ_TIMEOUT', 10),
    ],

    'bangumi' => [
        'token' => env('BANGUMI_ACCESS_TOKEN'),
        'base_url' => env('BANGUMI_BASE_URL', 'https://api.bgm.tv'),
        'user_agent' => env('BANGUMI_USER_AGENT', 'shiki/annual-board (https://github.com/annual-board)'),
        'timeout' => (int) env('BANGUMI_TIMEOUT', 10),
    ],

    'steam' => [
        'base_url' => env('STEAM_BASE_URL', 'https://store.steampowered.com'),
        'timeout' => (int) env('STEAM_TIMEOUT', 10),
    ],

    'douban' => [
        'base_url' => env('DOUBAN_BASE_URL', 'https://www.douban.com'),
        'user_agent' => env('DOUBAN_USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'),
        'timeout' => (int) env('DOUBAN_TIMEOUT', 10),
    ],

    'third_party_proxy' => env('THIRD_PARTY_PROXY'),

    'media_providers' => [
        'game' => env('GAME_PROVIDER', 'steam'), // steam | bangumi | douban
    ],

];
