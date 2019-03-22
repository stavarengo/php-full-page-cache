<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 16/03/19
 * Time: 19:05
 */

namespace Sta\FullPageCache;

use Cache\Hierarchy\HierarchicalPoolInterface;
use Cache\Prefixed\PrefixedCachePool;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\ContentNormalizerOfHeadersThatVaryInterface;
use Zend\Http\Header\CacheControl;

class FullPageCache
{
    public const CACHE_NAMESPACE = 'sta-full-page-cache';
    public const HEADER_FULL_PAGE_CACHE = 'X-Sta-Fpc';

    /**
     * @var CacheItemPoolInterface
     */
    protected $store;
    /**
     * @var ContentNormalizerOfHeadersThatVaryInterface[]
     */
    private $contentNormalizerOfHeadersThatVary;

    /**
     * CacheProvider constructor.
     * @param CacheItemPoolInterface $store
     * @param ContentNormalizerOfHeadersThatVaryInterface[] $contentNormalizerOfHeadersThatVary
     */
    public function __construct(CacheItemPoolInterface $store, array $contentNormalizerOfHeadersThatVary)
    {
        if (!($store instanceof PrefixedCachePool)) {
            $store = new PrefixedCachePool($store, self::CACHE_NAMESPACE);
        }
        $this->store = $store;
        $this->contentNormalizerOfHeadersThatVary = $contentNormalizerOfHeadersThatVary;
    }

    public function getCachedResponse(RequestInterface $request): ?ResponseInterface
    {
        $metadataCacheId = $this->getMetadataCacheKey($request);
        $cacheItem = $this->store->getItem($metadataCacheId);

        if (!$cacheItem->isHit()) {
            return null;
        }

        /** @var RequestMetadata $cacheMetadata */
        $cacheMetadata = $cacheItem->get();

        $responseCacheId = $this->getResponseCacheKey($request, $cacheMetadata->getVary());
        $cacheItem = $this->store->getItem($responseCacheId);

        if (!$cacheItem->isHit()) {
            return null;
        }

        /** @var ResponseInterface $response */
        $response = $this->stringToPsrResponse($cacheItem->get());

        $response = $response->withHeader(self::HEADER_FULL_PAGE_CACHE, 'hit');

        return $response;
    }

    public function cacheResponse(ResponseInterface $response, RequestInterface $request): void
    {
        if ($response->getStatusCode() == 304) {
            // If another library has already set this response to 304
            return;
        }

        $response = $this->setNotModified($request, $response);
        if ($response->getStatusCode() == 304) {
            return;
        }

        $cacheControlLine = $response->getHeaderLine('Cache-Control');
        if (!$cacheControlLine) {
            return;
        }

        $cacheControl = CacheControl::fromString("Cache-Control: $cacheControlLine");
        if ($cacheControl->hasDirective('private') || $cacheControl->hasDirective('no-cache')
            || $cacheControl->hasDirective('no-store')
        ) {
            return;
        }

        $maxAge = (int)$cacheControl->getDirective('max-age');
        if ($maxAge < 1) {
            return;
        }

        $vary = $response->getHeaderLine('Vary');
        if ($vary === '*') {
            return;
        }
        $vary = explode(',', $vary);

        $expiresAfter = new \DateInterval('PT' . $maxAge . 'S');

        $requestCacheId = $this->getResponseCacheKey($request, $vary);
        $cacheItem = $this->store->getItem($requestCacheId);
        $cacheItem->set($this->psrResponseToString($response));
        $cacheItem->expiresAfter($expiresAfter);
        $this->store->save($cacheItem);

        $metadataCacheId = $this->getMetadataCacheKey($request);
        $cacheItem = $this->store->getItem($metadataCacheId);
        $cacheItem->set(new RequestMetadata($vary));
        $cacheItem->expiresAfter($expiresAfter);
        $this->store->save($cacheItem);
    }

    private function getResponseCacheKey(RequestInterface $request, array $vary): string
    {
        $vary = array_unique(array_filter(array_map('trim', $vary)));
        $varyHeaders = [];
        foreach ($vary as $varyHeaderName) {
            $varyHeaders[$varyHeaderName] = $request->getHeaderLine($varyHeaderName);
        }

        $normalizedVaryHeaders = [];
        foreach ($varyHeaders as $header => $value) {
            $lowerCaseHeaderName = strtolower($header);
            $value = strtolower($value);
            foreach ($this->contentNormalizerOfHeadersThatVary as $normalizer) {
                if ($normalizer->canNormalizeContentsFrom($lowerCaseHeaderName)) {
                    $value = $normalizer->normalize($value, $lowerCaseHeaderName);
                }
            }

            $normalizedVaryHeaders[] = $lowerCaseHeaderName . '=' . $value;
        }

        ksort($normalizedVaryHeaders);

        $cacheKey = trim(implode(',', $normalizedVaryHeaders));

        $cacheKey = $this->getMetadataCacheKey($request) . md5($cacheKey);

        return $cacheKey;
    }

    private function getMetadataCacheKey(RequestInterface $request): string
    {
        $cacheKey = trim(
            sprintf(
                '%s %s %s %s %s',
                strtolower($request->getMethod()),
                $request->getUri()->getUserInfo(),
                $request->getUri()->getPath(),
                $request->getUri()->getQuery(),
                $request->getUri()->getFragment()
            )
        );

        $cacheKey = md5($cacheKey);

        return $cacheKey;
    }

    /**
     * Returns the string representation of an HTTP message.
     *
     * @param ResponseInterface $response Message to convert to a string.
     *
     * @return string
     */
    private function psrResponseToString(ResponseInterface $response): string
    {
        $responseAsStr = \GuzzleHttp\Psr7\str($response);

        return $responseAsStr;
    }

    private function stringToPsrResponse(string $responseAsString): ResponseInterface
    {
        $response = \GuzzleHttp\Psr7\parse_response($responseAsString);

        return $response;
    }

    private function setNotModified(RequestInterface $request, ResponseInterface $response)
    {
        $requestETags = $request->getHeaderLine('If-None-Match');
        $responseETag = $response->getHeaderLine('Etag');
        if (!$requestETags || !$responseETag) {
            return $response;
        }

        $requestETags = array_filter(array_map('trim', explode(',', $requestETags)));

        if (in_array($responseETag, $requestETags) || in_array('*', $requestETags)) {
            $response = $response->withStatus(304)
                                 ->withBody(\GuzzleHttp\Psr7\stream_for(null));
        }

        return $response;
    }
}