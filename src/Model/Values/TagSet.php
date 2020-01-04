<?php

namespace Inspirum\Cache\Model\Values;

use Illuminate\Cache\TagSet as LaravelTagSet;
use Inspirum\Cache\Definitions\Mcrouter;

class TagSet extends LaravelTagSet
{
    /**
     * Static cache for tags
     *
     * @var array
     */
    private static $tags = [];

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
        return Mcrouter::getSharedKey('tag:' . $name . ':key');
    }
}
