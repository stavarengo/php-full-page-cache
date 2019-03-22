<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 18/03/19
 * Time: 16:20
 */

namespace Sta\FullPageCache;


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
        // Memory Cache
        return new \Cache\Adapter\PHPArray\ArrayCachePool();

        // Example of a filesystem cache
        //return new \Cache\Adapter\Filesystem\FilesystemCachePool(
        //    new \League\Flysystem\Filesystem(
        //        new \League\Flysystem\Adapter\Local(
        //            sys_get_temp_dir()
        //        )
        //    )
        //);

        // Example of a Redis cache - See https://github.com/phpredis/phpredis
        //$redisClient = new \Redis();
        //$redisClient->connect('redis-host', 6379);
        //return new \Cache\Adapter\Redis\RedisCachePool($redisClient);
    }
}