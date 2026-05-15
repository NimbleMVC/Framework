<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired immediately after the controller action method finishes.
 */
class AfterControllerDispatchEvent extends AbstractEvent
{

    /**
     * @param object $controller
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     */
    public function __construct(
        public object $controller,
        public string $controllerName,
        public string $methodName,
        public array $params
    ) {
    }

}
