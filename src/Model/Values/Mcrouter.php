<?php

namespace Inspirum\Mcrouter\Model\Values;

class Mcrouter
{
    /**
     * Prefix for shared cache (used in Mcrouter)
     *
     * @var string
     */
    public const SHARED_PREFIX = '/default/shr/';

    /**
     * Shared prefix for tags
     *
     * @var string
     */
    private $sharedPrefix;

    /**
     * Supported prefixes in Mcrouter
     *
     * @var array
     */
    private $prefixes = [];

    /**
     * Mcrouter constructor
     *
     * @param string $sharedPrefix
     * @param array  $supportedPrefixes
     */
    public function __construct(string $sharedPrefix, array $supportedPrefixes = [])
    {
        $this->sharedPrefix = $sharedPrefix;
        $this->prefixes     = array_filter(array_merge([$sharedPrefix], $supportedPrefixes));
    }

    /**
     * Get the cache prefixes.
     *
     * @return array
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * Get the shared-cache Mcrouter prefix.
     *
     * @param string $key
     *
     * @return string
     */
    public function getSharedKey(string $key): string
    {
        return $this->sharedPrefix . $key;
    }
}
