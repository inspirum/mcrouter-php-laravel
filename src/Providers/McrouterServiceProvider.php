<?php

namespace Inspirum\Mcrouter\Providers;

use Illuminate\Cache\CacheManager;
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
        $this->mergeConfigFrom(__DIR__ . '/../../config/mcrouter.php', 'mcrouter');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
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
            // connect memcached
            /** @var \Illuminate\Cache\MemcachedConnector $connector */
            $connector = $app['memcached.connector'];
            $memcached = $connector->connect(
                $config['servers'],
                $config['persistent_id'] ?? null,
                $config['options'] ?? [],
                array_filter($config['sasl'] ?? [])
            );

            // get cache prefix
            /** @var \Illuminate\Contracts\Config\Repository $configRepository */
            $configRepository = $app['config'];
            $prefix           = $config['prefix'] ?? $configRepository->get('cache.prefix');

            // get mcrouter config
            $mcrouter = new Mcrouter(
                $configRepository->get('mcrouter.shared_prefix') ?? Mcrouter::SHARED_PREFIX,
                $configRepository->get('mcrouter.prefixes') ?? []
            );

            // register memcached store
            /** @var \Illuminate\Cache\CacheManager $cacheManager */
            $cacheManager = $app['cache'];

            return $cacheManager->repository(new MemcachedStore($memcached, $prefix, $mcrouter));
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
        return $app['cache'];
    }
}
