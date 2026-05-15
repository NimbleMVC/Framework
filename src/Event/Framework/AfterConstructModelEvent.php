<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after a classic model instance finishes its construction hook.
 */
class AfterConstructModelEvent extends AbstractEvent
{

    /**
     * @param object $model
     */
    public function __construct(public object $model)
    {
    }

}
