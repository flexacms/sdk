<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connection
    |--------------------------------------------------------------------------
    |
    | Your Flexa URL and a workspace API key. Use a key with only the Read
    | ability unless your site also writes content.
    |
    */

    'url' => env('FLEXA_URL'),

    'api_key' => env('FLEXA_API_KEY'),

    'api_path' => env('FLEXA_API_PATH', '/api/v1'),

    'timeout' => (int) env('FLEXA_TIMEOUT', 30),

    /*
    | Reads are retried after a rate limit (429), a gateway error or a
    | connection failure. Writes are never retried.
    */

    'retries' => (int) env('FLEXA_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Reads are cached for "ttl" seconds in the given store (null = default
    | store). Set the ttl to 0 to disable caching. Clear everything with
    | Flexa::flushCache(), e.g. from a webhook or a deploy script.
    |
    */

    'cache' => [
        'store' => env('FLEXA_CACHE_STORE'),
        'ttl' => (int) env('FLEXA_CACHE_TTL', 60),
    ],

];
