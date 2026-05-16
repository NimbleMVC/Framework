<?php

namespace NimblePHP\Framework\Event;

/**
 * Contract for events that can stop listener propagation.
 */
interface StoppableEventInterface
{

    /**
     * Mark the event as handled and stop any remaining listeners.
     *
     * @return void
     */
    public function stopPropagation(): void;

    /**
     * Check whether further listeners should be skipped.
     *
     * @return bool
     */
    public function isPropagationStopped(): bool;

}
