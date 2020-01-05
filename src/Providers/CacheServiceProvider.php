<?php

namespace Inspirum\Cache\Providers;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\CacheServiceProvider as LaravelCacheServiceProvider;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Contracts\Foundation\Application;
use Inspirum\Cache\Services\MemcachedStore;

class CacheServiceProvider extends LaravelCacheServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // register cache config
        $this->mergeConfigFrom(__DIR__ . '/../../config/cache.php', 'cache');

        // register cache repository
        parent::register();

        // register custom memcached driver
        $this->registerCustomMemcachedDriver();
    }

    /**
     * Register custom memcached driver
     *
     * @return void
     */
    private function registerCustomMemcachedDriver(): void
    {
        // extend to custom memcached store
        $this->getCacheManager($this->app)->extend('memcached', function (Application $app, array $config) {
            // get cache prefix
            $cacheManager = $this->getCacheManager($app);
            $prefix       = $config['prefix'] ?? $app['config']['cache.prefix'];

            // connect memcached
            $connector = $this->getMemcachedConnector($app);
            $memcached = $connector->connect(
                $config['servers'],
                $config['persistent_id'] ?? null,
                $config['options'] ?? [],
                array_filter($config['sasl'] ?? [])
            );

            // register memcached store
            return $cacheManager->repository(new MemcachedStore($memcached, $prefix));
        });
    }

    /**
     * Get cache manager
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return \Illuminate\Cache\CacheManager
     */
    private function getCacheManager(Application &$app): CacheManager
    {
        return $app->get('cache');
    }

    /**
     * Get memcached connector
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return \Illuminate\Cache\MemcachedConnector
     */
    private function getMemcachedConnector(Application &$app): MemcachedConnector
    {
        return $app->get('memcached.connector');
    }
}
