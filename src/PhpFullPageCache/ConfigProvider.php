<?php

namespace Sta\FullPageCache;

use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\AcceptEncoding;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\AcceptEncodingFactory;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\AcceptLanguage;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\AcceptLanguageFactory;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\ContentNormalizerOfHeadersThatVaryInterface;
use Sta\FullPageCache\FrameworkAdaptor;
use Sta\FullPageCache\FrameworkAdaptor\Zend\EventListenerFactory;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            self::class => $this->getConfig(),
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    public function getConfig()
    {
        return [
            ContentNormalizerOfHeadersThatVaryInterface::class => [
                'enabled' => [],
                AcceptEncoding::class => [
                    'supportedEncodings' => [
                        'gzip',
                    ],
                ],
                AcceptLanguage::class => [
                    'supportedLanguages' => [],
                ]
            ],
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
                FullPageCache::class => FullPageCacheFactory::class,
                CachePoolFactory::class => CachePoolFactory::class,

                ContentNormalizerOfHeadersThatVary\AcceptEncoding::class => AcceptEncodingFactory::class,
                ContentNormalizerOfHeadersThatVary\AcceptLanguage::class => AcceptLanguageFactory::class,

                FrameworkAdaptor\Zend\EventListener::class => EventListenerFactory::class,
            ],
        ];
    }
}
