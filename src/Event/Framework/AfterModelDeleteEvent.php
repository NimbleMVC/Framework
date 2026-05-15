<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after a model delete operation finishes.
 */
class AfterModelDeleteEvent extends AbstractEvent
{

    /**
     * @param object $model
     * @param bool $result
     * @param int|null $id
     * @param array|null $conditions
     * @param string $type
     */
    public function __construct(
        public object $model,
        public bool $result,
        public ?int $id = null,
        public ?array $conditions = null,
        public string $type = 'delete'
    ) {
    }

}
