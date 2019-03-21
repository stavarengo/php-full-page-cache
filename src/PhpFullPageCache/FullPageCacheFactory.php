<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 16/03/19
 * Time: 19:05
 */

namespace Sta\FullPageCache;

use Psr\Container\ContainerInterface;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\ContentNormalizerOfHeadersThatVaryInterface;

class FullPageCacheFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $normalizers = $config[ConfigProvider::class][ContentNormalizerOfHeadersThatVaryInterface::class]['enabled'];

        foreach ($normalizers as $key => $normalizer) {
            if (is_string($normalizer)) {
                $normalizers[$key] = $container->get($normalizer);
            }
        }

        return new FullPageCache($container->get(CachePoolFactory::class), $normalizers);
    }
}