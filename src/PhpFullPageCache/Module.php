<?php
namespace Sta\FullPageCache;

class Module
{
    public function getConfig()
    {
        $provider = new ConfigProvider();

        return [
            ConfigProvider::class => $provider->getConfig(),
            'service_manager' => $provider->getDependencyConfig(),
        ];
    }
}
