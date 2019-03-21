<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 16/03/19
 * Time: 19:05
 */

namespace Sta\FullPageCache;

use Psr\Container\ContainerInterface;

class FullPageCacheFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new FullPageCache($container->get(CachePoolFactory::class));
    }
}