<?php

namespace Sta\FullPageCache;

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
                CacheProvider::class => CacheProviderFactory::class,
                ZendEventListener::class => ZendEventListenerFactory::class,
            ],
        ];
    }
}
