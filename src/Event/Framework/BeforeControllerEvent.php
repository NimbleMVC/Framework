<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired before route controller and method are validated and dispatched.
 */
class BeforeControllerEvent extends AbstractEvent
{

    /**
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     */
    public function __construct(
        public string $controllerName,
        public string $methodName,
        public array $params
    ) {
    }

}
