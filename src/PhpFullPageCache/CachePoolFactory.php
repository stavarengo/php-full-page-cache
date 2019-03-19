<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 18/03/19
 * Time: 16:20
 */

namespace Sta\FullPageCache;


use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

class CachePoolFactory
{
    /**
     * Must return the cache pool that will be used for caching requests.
     *
     * @param ContainerInterface $container
     *
     * @return CacheItemPoolInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        return new FilesystemCachePool(
            new Filesystem(new Local(sys_get_temp_dir())),
            CacheProvider::CACHE_NAMESPACE
        );
    }
}