<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired before view data is extracted for template rendering.
 */
class ProcessingViewDataEvent extends AbstractEvent
{

    /**
     * @param array $data
     */
    public function __construct(public array $data)
    {
    }

}
