<?php

namespace Inspirum\Cache\Model\Values;

use Illuminate\Cache\TagSet as LaravelTagSet;
use Illuminate\Contracts\Cache\Store;

class TagSet extends LaravelTagSet
{
    /**
     * Static cache for tags
     *
     * @var array
     */
    private static $tags = [];

    /**
     * Mcrouter config
     *
     * @var \Inspirum\Cache\Model\Values\Mcrouter
     */
    private $mcrouter;

    /**
     * Create a new TagSet instance.
     *
     * @param \Illuminate\Contracts\Cache\Store     $store
     * @param array                                 $names
     * @param \Inspirum\Cache\Model\Values\Mcrouter $mcrouter
     */
    public function __construct(Store $store, array $names, Mcrouter $mcrouter)
    {
        parent::__construct($store, $names);

        $this->mcrouter = $mcrouter;
    }

    /**
     * Get an array of tag identifiers for all of the tags in the set.
     *
     * @return array
     */
    protected function tagIds()
    {
        // tag hashed ids
        $ids = [];

        foreach ($this->names as $name) {
            // get tag hash from static cache or from memcache
            $tag = static::$tags[$name] ?? $this->tagId($name);

            // store tag id to static cache
            $ids[] = static::$tags[$name] = $tag;
        }

        // return tag hashed ids
        return $ids;
    }

    /**
     * Reset the tag and return the new tag identifier.
     *
     * @param string $name
     *
     * @return string
     */
    public function resetTag($name)
    {
        // unset tag value from static cache
        unset(static::$tags[$name]);

        // reset tag value in memcache
        return parent::resetTag($name);
    }

    /**
     * Get the tag identifier key for a given tag.
     *
     * @param string $name
     *
     * @return string
     */
    public function tagKey($name)
    {
        return $this->mcrouter->getSharedKey(parent::tagKey($name));
    }

    /**
     * Reset cached tags from static memory
     *
     * @return void
     */
    public static function resetCachedTags(): void
    {
        static::$tags = [];
    }

    /**
     * Get cached tags from static memory
     *
     * @return array
     */
    public static function getCachedTags(): array
    {
        return static::$tags;
    }
}
