<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Glo Gifting API Transport & Proxy Configuration
    |--------------------------------------------------------------------------
    |
    | 'transport' determines whether requests to Glo's Gifting API are sent
    | directly ('direct') or routed through a secondary proxy server ('proxy').
    |
    */

    'transport' => env('GIFTING_TRANSPORT', 'direct'),

    'direct_url' => env('GIFTING_DIRECT_URL', 'https://gifting-api.gloworld.com/v1/distribution'),

    'proxy_url' => env('GIFTING_PROXY_URL'),

    'proxy_secret' => env('PROXY_SHARED_SECRET'),

    'timeout' => (int) env('GIFTING_TIMEOUT', 30),

    'connect_timeout' => (int) env('GIFTING_CONNECT_TIMEOUT', 10),
];
