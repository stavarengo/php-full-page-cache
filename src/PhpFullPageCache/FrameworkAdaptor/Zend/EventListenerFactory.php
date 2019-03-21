<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 19/03/19
 * Time: 09:36
 */

namespace Sta\FullPageCache\FrameworkAdaptor\Zend;

use Psr\Container\ContainerInterface;
use Sta\FullPageCache\FullPageCache;

class EventListenerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new EventListener($container->get(FullPageCache::class));
    }
}