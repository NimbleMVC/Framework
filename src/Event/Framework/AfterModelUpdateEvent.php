<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after a model update operation finishes.
 */
class AfterModelUpdateEvent extends AbstractEvent
{

    /**
     * @param object $model
     * @param array $data
     * @param bool $result
     * @param string $type
     */
    public function __construct(
        public object $model,
        public array $data,
        public bool $result,
        public string $type = 'update'
    ) {
    }

}
