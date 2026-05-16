<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;
use ReflectionMethod;

/**
 * Fired after controller action attributes are reflected.
 */
class AfterAttributesControllerEvent extends AbstractEvent
{

    /**
     * @param ReflectionMethod $reflection
     * @param object $controller
     */
    public function __construct(
        public ReflectionMethod $reflection,
        public object $controller
    ) {
    }

}
