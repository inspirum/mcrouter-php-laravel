<?php

namespace Inspirum\Mcrouter\Services;

use Illuminate\Cache\MemcachedStore as LaravelMemcachedStore;
use Illuminate\Cache\TaggedCache;
use Inspirum\Mcrouter\Model\Values\Mcrouter;
use Inspirum\Mcrouter\Model\Values\TagSet;
use Memcached;

class MemcachedStore extends LaravelMemcachedStore
{
    /**
     * Mcrouter config
     *
     * @var \Inspirum\Mcrouter\Model\Values\Mcrouter
     */
    private $mcrouter;

    /**
     * Create a new Memcached store.
     *
     * @param \Memcached                                    $memcached
     * @param string                                        $prefix
     * @param \Inspirum\Mcrouter\Model\Values\Mcrouter|null $mcrouter
     */
    public function __construct(Memcached $memcached, string $prefix = '', Mcrouter $mcrouter = null)
    {
        parent::__construct($memcached, $prefix);

        $this->mcrouter = $mcrouter ?: new Mcrouter('');
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param string|string[] $names
     *
     * @return \Illuminate\Cache\TaggedCache
     */
    public function tags($names)
    {
        return new TaggedCache($this, new TagSet($this, is_array($names) ? $names : func_get_args(), $this->mcrouter));
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->memcached->get($this->getPrefixedKey($key));

        if ($this->memcached->getResultCode() !== 0) {
            return null;
        }

        return $value;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param string[] $keys
     *
     * @return array
     */
    public function many(array $keys)
    {
        $prefixedKeys = array_map(function ($key) {
            return $this->getPrefixedKey($key);
        }, $keys);

        /** @var array $values */
        $values = $this->memcached->getMulti($prefixedKeys, Memcached::GET_PRESERVE_ORDER);

        if ($this->memcached->getResultCode() !== 0) {
            return array_fill_keys($keys, null);
        }

        /** @var array $values */
        $values = array_combine($keys, $values);

        return $values;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $seconds
     *
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return $this->memcached->set($this->getPrefixedKey($key), $value, $this->toTimestamp($seconds));
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param array $values
     * @param int   $seconds
     *
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->getPrefixedKey($key)] = $value;
        }

        return $this->memcached->setMulti($prefixedValues, $this->toTimestamp($seconds));
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $seconds
     *
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        return $this->memcached->add($this->getPrefixedKey($key), $value, $this->toTimestamp($seconds));
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param int    $value
     *
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->memcached->increment($this->getPrefixedKey($key), $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param string $key
     * @param int    $value
     *
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->memcached->decrement($this->getPrefixedKey($key), $value);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function forget($key)
    {
        return $this->memcached->delete($this->getPrefixedKey($key));
    }

    /**
     * Get the cache key prefix.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getPrefixedKey(string $key): string
    {
        $prefixes = $this->mcrouter->getPrefixes();

        foreach ($prefixes as $prefix) {
            $position = strpos($key, $prefix);

            if ($position !== false) {
                return substr_replace($key, $this->prefix, $position + strlen($prefix), 0);
            }
        }

        return $this->prefix . $key;
    }
}
