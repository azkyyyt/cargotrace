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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'geocoding' => [
        'url' => env('GEOCODING_API_URL', 'https://nominatim.openstreetmap.org'),
    ],

    'weather' => [
        'url' => env('WEATHER_API_URL', 'https://api.open-meteo.com/v1/forecast'),
        'key' => env('WEATHER_API_KEY'),
    ],

    'cargo' => [
        'delay_threshold_low' => env('DELAY_THRESHOLD_LOW_HOURS', 6),
        'delay_threshold_medium' => env('DELAY_THRESHOLD_MEDIUM_HOURS', 24),
        'delay_threshold_high' => env('DELAY_THRESHOLD_HIGH_HOURS', 48),
        'group_threshold' => env('GROUP_DELAY_THRESHOLD_HOURS', 18),
    ],

];
