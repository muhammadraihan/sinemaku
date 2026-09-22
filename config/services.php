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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cinepoint_ingest' => [
        'key_id' => env('CINEPOINT_INGEST_KEY_ID', 'cinepoint-vps-1'),
        'secret' => env('CINEPOINT_INGEST_SECRET'),
    ],

    'cinepoint' => [

        'mode' => env('CINEPOINT_MODE', 'remote'),
        'lease_seconds' => (int) env('CINEPOINT_LEASE_SECONDS', 600),
        'max_attempts' => (int) env('CINEPOINT_MAX_ATTEMPTS', 3),
        'node_binary' => env('CINEPOINT_NODE_BINARY', 'node'),
        'browser_script' => env('CINEPOINT_BROWSER_SCRIPT', base_path('scripts/cinepoint-daily-browser.cjs')),
        'browser_executable' => env('CINEPOINT_BROWSER_EXECUTABLE'),
        'playwright_path' => env('CINEPOINT_PLAYWRIGHT_PATH'),
    ],

];
