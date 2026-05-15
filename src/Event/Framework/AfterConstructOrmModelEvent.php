<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after an ORM model instance is created.
 */
class AfterConstructOrmModelEvent extends AbstractEvent
{

    /**
     * @param object $model
     */
    public function __construct(public object $model)
    {
    }

}
