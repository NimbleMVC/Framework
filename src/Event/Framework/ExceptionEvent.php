<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;
use Throwable;

/**
 * Fired when an exception reaches the kernel exception flow.
 */
class ExceptionEvent extends AbstractEvent
{

    /**
     * @param Throwable $exception
     */
    public function __construct(public Throwable $exception)
    {
    }

}
