<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 16/03/19
 * Time: 19:05
 */

namespace Sta\FullPageCache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sta\FullPageCache\ContentNormalizerOfHeadersThatVary\ContentNormalizerOfHeadersThatVaryInterface;
use Zend\Http\Header\CacheControl;

class FullPageCache
{
    public const CACHE_NAMESPACE = 'sta-full-page-cache';
    public const HEADER_FULL_PAGE_CACHE = 'X-Sta-Full-Page-Cache';

    /**
     * @var CacheItemPoolInterface
     */
    protected $store;
    /**
     * @var ContentNormalizerOfHeadersThatVaryInterface[]
     */
    private $headerNormalizers;

    /**
     * CacheProvider constructor.
     * @param CacheItemPoolInterface $store
     * @param ContentNormalizerOfHeadersThatVaryInterface[] $headerNormalizers
     */
    public function __construct(CacheItemPoolInterface $store, array $headerNormalizers)
    {
        $this->store = $store;
        $this->headerNormalizers = $headerNormalizers;
    }

    public function getCachedResponse(RequestInterface $request): ?ResponseInterface
    {
        $metadataCacheId = $this->getMetadataCacheId($request);
        $cacheItem = $this->store->getItem($metadataCacheId);

        if (!$cacheItem->isHit()) {
            return null;
        }

        /** @var RequestMetadata $cacheMetadata */
        $cacheMetadata = $cacheItem->get();

        $responseCacheId = $this->getResponseCacheId($request, $cacheMetadata->getVary());
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

        $requestCacheId = $this->getResponseCacheId($request, $vary);
        $cacheItem = $this->store->getItem($requestCacheId);
        $cacheItem->set($this->psrResponseToString($response));
        $cacheItem->expiresAfter($expiresAfter);
        $this->store->save($cacheItem);

        $metadataCacheId = $this->getMetadataCacheId($request);
        $cacheItem = $this->store->getItem($metadataCacheId);
        $cacheItem->set(new RequestMetadata($vary));
        $cacheItem->expiresAfter($expiresAfter);
        $this->store->save($cacheItem);
    }

    private function getResponseCacheId(RequestInterface $request, array $vary): string
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
            foreach ($this->headerNormalizers as $headerNormalizer) {
                if ($headerNormalizer->canNormalizeContentsFrom($lowerCaseHeaderName)) {
                    $value = $headerNormalizer->normalize($value, $lowerCaseHeaderName);
                }
            }

            $normalizedVaryHeaders[] = $lowerCaseHeaderName . '=' . $value;
        }

        ksort($normalizedVaryHeaders);

        $cacheId = trim(
            sprintf(
                '%s %s %s %s %s %s',
                strtolower($request->getMethod()),
                $request->getUri()->getUserInfo(),
                $request->getUri()->getPath(),
                $request->getUri()->getQuery(),
                $request->getUri()->getFragment(),
                implode(',', $normalizedVaryHeaders)
            )
        );

        return md5('RESPONSE:' . $cacheId);
    }

    private function getMetadataCacheId(RequestInterface $request): string
    {
        $cacheId = trim(
            sprintf(
                '%s %s %s %s %s',
                strtolower($request->getMethod()),
                $request->getUri()->getUserInfo(),
                $request->getUri()->getPath(),
                $request->getUri()->getQuery(),
                $request->getUri()->getFragment()
            )
        );

        return md5('METADATA:' . $cacheId);
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

}