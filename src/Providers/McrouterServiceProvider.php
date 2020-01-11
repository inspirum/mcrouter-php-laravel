<?php

namespace Inspirum\Mcrouter\Providers;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Inspirum\Mcrouter\Model\Values\Mcrouter;
use Inspirum\Mcrouter\Services\MemcachedSessionHandler;
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
        $configFile = __DIR__ . '/../../config/mcrouter.php';

        $this->mergeConfigFrom($configFile, 'mcrouter');
        $this->publishes([$configFile => config_path('mcrouter.php')], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCustomMemcachedStore();

        $this->registerCustomMemcachedSessionHandler();
    }

    /**
     * Register custom memcached cache repository
     *
     * @return void
     */
    private function registerCustomMemcachedStore(): void
    {
        // get Mcrouter configuration
        $mcrouter = $this->getMcrouterConfig();

        // extend to custom memcached store
        $this->getCacheManager()->extend('memcached', function (Application $app, array $config) use ($mcrouter) {
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

            // register memcached store
            /** @var \Illuminate\Cache\CacheManager $cacheManager */
            $cacheManager = $app['cache'];

            return $cacheManager->repository(new MemcachedStore($memcached, $prefix, $mcrouter));
        });
    }

    /**
     * Register custom memcached session driver
     *
     * @return void
     */
    private function registerCustomMemcachedSessionHandler(): void
    {
        // get Mcrouter configuration
        $mcrouter = $this->getMcrouterConfig();

        // extend to custom memcached session handler
        $this->getSessionManager()->extend('memcached', function (Application $app) use ($mcrouter) {
            /** @var \Illuminate\Cache\CacheManager $cacheManager */
            $cacheManager = $app['cache'];

            /** @var \Illuminate\Contracts\Config\Repository $configRepository */
            $configRepository = $app['config'];

            return new MemcachedSessionHandler(
                clone $cacheManager->store('memcached'),
                $configRepository->get('session.lifetime'),
                $mcrouter
            );
        });
    }

    /**
     * Get Mcrouter configuration
     *
     * @return \Inspirum\Mcrouter\Model\Values\Mcrouter
     */
    private function getMcrouterConfig(): Mcrouter
    {
        $configRepository = $this->getConfigRepository();

        return new Mcrouter(
            $configRepository->get('mcrouter.shared_prefix') ?? Mcrouter::SHARED_PREFIX,
            $configRepository->get('mcrouter.prefixes') ?? []
        );
    }

    /**
     * Get cache manager
     *
     * @return \Illuminate\Cache\CacheManager
     */
    private function getCacheManager(): CacheManager
    {
        return $this->app['cache'];
    }

    /**
     * Get config repository
     *
     * @return \Illuminate\Contracts\Config\Repository
     */
    private function getConfigRepository(): ConfigRepository
    {
        return $this->app['config'];
    }

    /**
     * Get Session Manager
     *
     * @return \Illuminate\Session\SessionManager
     */
    private function getSessionManager(): SessionManager
    {
        return $this->app['session'];
    }
}
