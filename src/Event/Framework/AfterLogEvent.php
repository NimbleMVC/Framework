<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after log payload assembly and before it is written to storage.
 */
class AfterLogEvent extends AbstractEvent
{

    /**
     * @param array $payload
     */
    public function __construct(public array $payload)
    {
    }

}
