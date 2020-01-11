<?php

namespace Inspirum\Mcrouter\Services;

use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Session\CacheBasedSessionHandler as LaravelCacheBasedSessionHandler;
use Inspirum\Mcrouter\Model\Values\Mcrouter;

class MemcachedSessionHandler extends LaravelCacheBasedSessionHandler
{
    /**
     * Mcrouter config
     *
     * @var \Inspirum\Mcrouter\Model\Values\Mcrouter
     */
    private $mcrouter;

    /**
     * Create a new cache driven handler instance.
     *
     * @param \Illuminate\Contracts\Cache\Repository        $cache
     * @param int                                           $minutes
     * @param \Inspirum\Mcrouter\Model\Values\Mcrouter|null $mcrouter
     */
    public function __construct(CacheContract $cache, $minutes, Mcrouter $mcrouter = null)
    {
        parent::__construct($cache, $minutes);

        $this->mcrouter = $mcrouter ?: new Mcrouter('');
    }

    /**
     * Read session data
     *
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId)
    {
        return parent::read($this->getSharedKey($sessionId));
    }

    /**
     * Write session data
     *
     * @param string $sessionId
     * @param string $data
     *
     * @return bool
     */
    public function write($sessionId, $data)
    {
        return parent::write($this->getSharedKey($sessionId), $data);
    }

    /**
     * Destroy a session
     *
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        return parent::destroy($this->getSharedKey($sessionId));
    }

    /**
     * Get the cache key with Mcrouter shared prefix.
     *
     * @param string $key
     *
     * @return string
     */
    private function getSharedKey(string $key): string
    {
        return $this->mcrouter->getSharedKey($key);
    }
}
