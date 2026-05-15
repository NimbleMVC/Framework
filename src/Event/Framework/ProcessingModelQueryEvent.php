<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired before a raw model query is executed.
 */
class ProcessingModelQueryEvent extends AbstractEvent
{

    /**
     * @param object $model
     * @param string $query
     * @param string $type
     */
    public function __construct(
        public object $model,
        public string $query,
        public string $type
    ) {
    }

}
