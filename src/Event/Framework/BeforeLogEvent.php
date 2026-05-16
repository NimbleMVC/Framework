<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired before a log message is normalized and persisted.
 */
class BeforeLogEvent extends AbstractEvent
{

    /**
     * @param string $message
     */
    public function __construct(public string $message)
    {
    }

}
