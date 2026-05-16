<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after the kernel resolves the concrete controller object and method.
 */
class ControllerResolvedEvent extends AbstractEvent
{

    /**
     * @param object $controller
     * @param string $controllerClass
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     */
    public function __construct(
        public object $controller,
        public string $controllerClass,
        public string $controllerName,
        public string $methodName,
        public array $params
    ) {
    }

}
