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
use Zend\Http\Header\CacheControl;

class CacheProvider
{
    public const CACHE_NAMESPACE = 'cely-full-page-cache';
    public const HEADER_FULL_PAGE_CACHE = 'X-Full-Page-Cache';

    /**
     * @var CacheItemPoolInterface
     */
    protected $store;

    /**
     * CacheProvider constructor.
     * @param CacheItemPoolInterface $store
     */
    public function __construct(CacheItemPoolInterface $store)
    {
        $this->store = $store;
    }

    public function getCachedResponse(RequestInterface $request): ?ResponseInterface
    {
        $metadataCacheId = $this->getMetadataCacheId($request);
        $cacheItem = $this->store->getItem($metadataCacheId);

        if (!$cacheItem->isHit()) {
            return null;
        }

        /** @var CacheMetadata $cacheMetadata */
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
        $cacheItem->set(new CacheMetadata($vary));
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
            $header = strtolower($header);
            $value = strtolower($value);

            if ($header == 'accept-encoding') {
                if (preg_match('~\bgzip\b~', $value)) {
                    $value = 'gzip';
                } else {
                    $value = '';
                }
            } else if ($header == 'accept-language') {
                $value = [];
                if (preg_match('~en(-..){0,1}~', $value)) {
                    $value[] = 'en';
                }
                if (preg_match('~pt(-..){0,1}~', $value)) {
                    $value[] = 'pt';
                }
                $value = implode(',', $value);
            }

            $normalizedVaryHeaders[] = $header . '=' . $value;
        }

        ksort($normalizedVaryHeaders);

        $cacheId = trim(
        //https://$esRootUserName:$esRootUserPassword@es-main-temporary.stage.celebryts.com:9200
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