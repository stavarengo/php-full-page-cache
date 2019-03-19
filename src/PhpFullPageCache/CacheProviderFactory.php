<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 16/03/19
 * Time: 19:05
 */

namespace Sta\FullPageCache;

use Psr\Container\ContainerInterface;

class CacheProviderFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new CacheProvider($container->get(CachePoolFactory::class));
    }
}