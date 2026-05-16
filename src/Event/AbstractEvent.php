<?php

namespace NimblePHP\Framework\Event;

/**
 * Base event implementation with propagation control.
 */
abstract class AbstractEvent implements StoppableEventInterface
{

    protected bool $propagationStopped = false;

    /**
     * Stop further listener execution for this event instance.
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Determine whether propagation was stopped by a listener.
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

}
