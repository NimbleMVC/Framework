<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after controller dispatch bookkeeping is complete.
 */
class AfterControllerEvent extends AbstractEvent
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
