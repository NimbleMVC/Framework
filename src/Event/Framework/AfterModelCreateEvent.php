<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after a model create operation finishes.
 */
class AfterModelCreateEvent extends AbstractEvent
{

    /**
     * @param object $model
     * @param array $data
     * @param bool $result
     */
    public function __construct(
        public object $model,
        public array $data,
        public bool $result
    ) {
    }

}
