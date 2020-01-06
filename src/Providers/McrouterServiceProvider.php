<?php

namespace Inspirum\Mcrouter\Providers;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Inspirum\Mcrouter\Model\Values\Mcrouter;
use Inspirum\Mcrouter\Services\MemcachedStore;

class McrouterServiceProvider extends LaravelServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // register cache config
        $this->mergeConfigFrom(__DIR__ . '/../../config/mcrouter.php', 'mcrouter');

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
            $cacheManager     = $this->getCacheManager($app);
            $configRepository = $this->getConfig($app);

            $prefix = $config['prefix'] ?? $configRepository->get('cache.prefix');

            // connect memcached
            $connector = $this->getMemcachedConnector($app);
            $memcached = $connector->connect(
                $config['servers'],
                $config['persistent_id'] ?? null,
                $config['options'] ?? [],
                array_filter($config['sasl'] ?? [])
            );

            $mcrouter = new Mcrouter(
                $configRepository->get('mcrouter.shared_prefix') ?? Mcrouter::SHARED_PREFIX,
                $configRepository->get('mcrouter.prefixes') ?? []
            );

            // register memcached store
            return $cacheManager->repository(new MemcachedStore($memcached, $prefix, $mcrouter));
        });
    }

    /**
     * Get config repository
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return \Illuminate\Contracts\Config\Repository
     */
    private function getConfig(Application &$app): ConfigRepository
    {
        return $app->get('config');
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
