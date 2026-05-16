<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

/**
 * @deprecated Use NimblePHP\Framework\Event\EventDispatcher instead.
 */
interface MiddlewareInterface
{

    /**
     * @param mixed $request
     * @param callable $next
     * @return mixed
     */
    public function handle(mixed $request, callable $next): mixed;

}
