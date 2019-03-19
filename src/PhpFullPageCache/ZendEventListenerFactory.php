<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 19/03/19
 * Time: 09:36
 */

namespace Sta\FullPageCache;

use Psr\Container\ContainerInterface;

class ZendEventListenerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new ZendEventListener($container->get(CacheProvider::class));
    }
}