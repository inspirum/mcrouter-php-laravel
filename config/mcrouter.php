<?php

use Inspirum\Mcrouter\Model\Values\Mcrouter;

return [
    /*
    |--------------------------------------------------------------------------
    | Mcrouter prefixes
    |--------------------------------------------------------------------------
    |
    */

    'mcrouter' => [
        'shared_prefix' => env('CACHE_MCROUTER_SHARED_PREFIX', Mcrouter::SHARED_PREFIX),
        'prefixes'      => explode(',', env('CACHE_MCROUTER_PREFIXES', '')),
    ],
];
