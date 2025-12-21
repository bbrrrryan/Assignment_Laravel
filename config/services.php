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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Facility Management Module Web Service
    |--------------------------------------------------------------------------
    |
    | Configuration for the Facility Management Module Web Service API.
    | This allows you to specify a different URL/port for testing purposes.
    |
    | If FACILITY_SERVICE_URL is not set, it will use APP_URL.
    | If FACILITY_SERVICE_PORT is set, it will override the port in the URL.
    |
    */

    'facility_service' => [
        'url' => env('FACILITY_SERVICE_URL', null), 
        'port' => env('FACILITY_SERVICE_PORT', null), 
        'timeout' => env('FACILITY_SERVICE_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Management Module Web Service
    |--------------------------------------------------------------------------
    |
    | Configuration for the User Management Module Web Service API.
    | This allows you to specify a different URL/port for testing purposes.
    |
    | If USER_SERVICE_URL is not set, it will use APP_URL.
    | If USER_SERVICE_PORT is set, it will override the port in the URL.
    |
    */

    'user_service' => [
        'url' => env('USER_SERVICE_URL', null), 
        'port' => env('USER_SERVICE_PORT', null), 
        'timeout' => env('USER_SERVICE_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Announcement Management Module Web Service
    |--------------------------------------------------------------------------
    |
    | Configuration for the Announcement Management Module Web Service API.
    | This allows you to specify a different URL/port for testing purposes.
    |
    | If ANNOUNCEMENT_SERVICE_URL is not set, it will use USER_SERVICE_URL or APP_URL.
    | If ANNOUNCEMENT_SERVICE_PORT is set, it will override the port in the URL.
    |
    */

    'announcement_service' => [
        'url' => env('ANNOUNCEMENT_SERVICE_URL', null), 
        'port' => env('ANNOUNCEMENT_SERVICE_PORT', null), 
        'timeout' => env('ANNOUNCEMENT_SERVICE_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Management Module Web Service
    |--------------------------------------------------------------------------
    |
    | Configuration for the Booking Management Module Web Service API.
    | This allows you to specify a different URL/port for testing purposes.
    |
    | If BOOKING_SERVICE_URL is not set, it will use APP_URL.
    | If BOOKING_SERVICE_PORT is set, it will override the port in the URL.
    |
    */

    'booking_service' => [
        'url' => env('BOOKING_SERVICE_URL', null), 
        'port' => env('BOOKING_SERVICE_PORT', null), 
        'timeout' => env('BOOKING_SERVICE_TIMEOUT', 10),
    ],

];
