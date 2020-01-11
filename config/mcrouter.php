<?php

use Inspirum\Mcrouter\Model\Values\Mcrouter;

return [
    /*
    |--------------------------------------------------------------------------
    | Mcrouter shared prefix
    |--------------------------------------------------------------------------
    |
    | Shared prefix is automatically used in all cache tag keys.
    |
    */

    'shared_prefix' => env('CACHE_MCROUTER_SHARED_PREFIX', Mcrouter::SHARED_PREFIX),

    /*
    |--------------------------------------------------------------------------
    | Mcrouter prefixes
    |--------------------------------------------------------------------------
    |
    | Additional prefixes that can be used in Mcrouter prefix routing.
    |
    */

    'prefixes' => array_filter(explode(',', env('CACHE_MCROUTER_PREFIXES', ''))),
];
