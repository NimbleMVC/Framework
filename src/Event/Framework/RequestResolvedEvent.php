<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;

/**
 * Fired after the router resolves controller, method, and params from the request.
 */
class RequestResolvedEvent extends AbstractEvent
{

    /**
     * @param RouteInterface $router
     * @param RequestInterface $request
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     */
    public function __construct(
        public RouteInterface $router,
        public RequestInterface $request,
        public string $controllerName,
        public string $methodName,
        public array $params
    ) {
    }

}
