<?php

namespace Sta\FullPageCache;

use Sta\FullPageCache\FrameworkAdaptor\Zend\EventListener;
use Sta\FullPageCache\FrameworkAdaptor\Zend\EventListenerFactory;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Provide default container dependency configuration.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories' => [
                CachePoolFactory::class => CachePoolFactory::class,
                FullPageCache::class => FullPageCacheFactory::class,
                EventListener::class => EventListenerFactory::class,
            ],
        ];
    }
}
