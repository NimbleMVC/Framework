<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;

/**
 * Fired immediately before the kernel reloads and resolves the route.
 */
class BeforeRouteDispatchEvent extends AbstractEvent
{

    /**
     * @param RouteInterface $router
     * @param RequestInterface $request
     */
    public function __construct(
        public RouteInterface $router,
        public RequestInterface $request
    ) {
    }

}
