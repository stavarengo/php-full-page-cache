<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 19/03/19
 * Time: 13:11
 */

namespace Sta\FullPageCache\ContentNormalizerOfHeadersThatVary;


use Psr\Container\ContainerInterface;
use Sta\FullPageCache\ConfigProvider;

class AcceptLanguageFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $fullPageCacheConfig = $container->get('config')[ConfigProvider::class];
        $normalizersConfig = $fullPageCacheConfig[ContentNormalizerOfHeadersThatVaryInterface::class];

        $listOfEncodesYouSupport = $normalizersConfig[AcceptLanguage::class]['supportedLanguages'];

        return new AcceptLanguage($listOfEncodesYouSupport);
    }
}