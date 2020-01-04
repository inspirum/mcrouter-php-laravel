<?php

namespace Inspirum\Cache\Definitions;

final class Mcrouter
{
    /**
     * Prefix for shared cache (used in Mcrouter)
     *
     * @var string
     */
    private const SHARED_PREFIX = '/default/shr/';

    /**
     * Get the cache prefixes.
     *
     * @return array
     */
    public static function getPrefixes(): array
    {
        return [
            self::SHARED_PREFIX,
        ];
    }

    /**
     * Get the shared-cache Mcrouter prefix.
     *
     * @param string $key
     *
     * @return string
     */
    public static function getSharedKey(string $key): string
    {
        return self::SHARED_PREFIX . $key;
    }
}
