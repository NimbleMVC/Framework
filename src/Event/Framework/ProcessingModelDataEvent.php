<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired before model create/update payloads are sent to the table layer.
 */
class ProcessingModelDataEvent extends AbstractEvent
{

    /**
     * @param object $model
     * @param array $data
     * @param string $type
     */
    public function __construct(
        public object $model,
        public array $data,
        public string $type
    ) {
    }

}
