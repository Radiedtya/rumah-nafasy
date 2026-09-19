<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    | Login Google (Socialite). Dapatkan kredensial di
    | https://console.cloud.google.com/apis/credentials → OAuth client ID
    | → Application type: Web application.
    |
    | allowed_spa_origins: origin frontend yang dipercaya sebagai tujuan
    | redirect kembali (anti open-redirect). Pisahkan dengan koma bila > 1.
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'allowed_spa_origins' => env('GOOGLE_ALLOWED_SPA_ORIGINS', 'http://localhost:5173'),
        'default_spa_origin' => env('GOOGLE_DEFAULT_SPA_ORIGIN', 'http://localhost:5173'),
    ],

];
