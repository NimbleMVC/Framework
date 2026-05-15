<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

use Throwable;

/**
 * @deprecated Use NimblePHP\Framework\Event\Framework\ExceptionEvent listeners instead.
 */
interface ExceptionMiddlewareInterface
{

    /**
     *
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHook(Throwable $exception): void;


}
