<?php
/**
 * Created by PhpStorm.
 * User: stavarengo
 * Date: 19/03/19
 * Time: 09:36
 */

namespace Sta\FullPageCache\FrameworkAdaptor\Zend;

use Sta\FullPageCache\FullPageCache;
use Sta\FullPageCache\Exception\MissingComposerDependency;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Psr7Bridge\Psr7Response;
use Zend\Psr7Bridge\Psr7ServerRequest;

class EventListener extends AbstractListenerAggregate
{
    /**
     * @var FullPageCache
     */
    protected $cacheProvider;

    /**
     * ZendEventListener constructor.
     * @param FullPageCache $cacheProvider
     */
    public function __construct(FullPageCache $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     * @param int $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], PHP_INT_MAX - 1);

        $priority = PHP_INT_MAX - 1;
        if (class_exists('\ZF\HttpCache\Module')) {
            // If this projects is using 'zfcampus/zf-http-cache', then we must ensure that our listener is going to
            // be executed after 'zfcampus/zf-http-cache' callback, since it add some headers to the response.
            $priority = -1001;
        }
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], $priority);
    }

    public function onRoute(MvcEvent $e)
    {
        if (!class_exists('\Zend\Psr7Bridge\Psr7ServerRequest')) {
            throw new MissingComposerDependency(
                sprintf(
                    'In order to use the listener "%s", you must install "zendframework/zend-psr7bridge". ' .
                    'This library will be used for converting PSR-7 messages to zend-http messages, and vice versa. ' .
                    'Please run `composer require zendframework/zend-psr7bridge` to install it. For more information, ' .
                    'check here: https://docs.zendframework.com/zend-psr7bridge/',
                    self::class
                )
            );
        }

        $request = $e->getRequest();
        if (!($request instanceof Request)) {
            return;
        }

        $response = $this->cacheProvider->getCachedResponse(Psr7ServerRequest::fromZend($request));
        if ($response) {
            $e->stopPropagation(true);
            return Psr7Response::toZend($response);
        }
    }

    public function onFinish(MvcEvent $e)
    {
        $response = $e->getResponse();
        $request = $e->getRequest();

        if (!($response instanceof Response) || !($request instanceof Request)) {
            return;
        }

        if ($response->getHeaders()->has(FullPageCache::HEADER_FULL_PAGE_CACHE)
            && $response->getHeaders()->get(FullPageCache::HEADER_FULL_PAGE_CACHE)->getFieldValue() == 'hit'
        ) {
            $e->stopPropagation(true);
            return;
        }

        $this->cacheProvider->cacheResponse(Psr7Response::fromZend($response), Psr7ServerRequest::fromZend($request));
    }
}