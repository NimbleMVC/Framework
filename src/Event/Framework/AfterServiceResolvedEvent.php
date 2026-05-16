<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Container\ServiceContainer;
use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after the container resolves a service instance.
 */
class AfterServiceResolvedEvent extends AbstractEvent
{

    /**
     * @param ServiceContainer $container
     * @param string $id
     * @param string $resolvedId
     * @param mixed $service
     * @param string $source
     */
    public function __construct(
        public ServiceContainer $container,
        public string $id,
        public string $resolvedId,
        public mixed $service,
        public string $source
    ) {
    }

}
