<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired for service container lifecycle operations like set/get/remove.
 */
class ServiceContainerEvent extends AbstractEvent
{

    /**
     * @param string $operation
     * @param string $id
     * @param mixed $service
     */
    public function __construct(
        public string $operation,
        public string $id,
        public mixed $service = null
    ) {
    }

}
