<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

use Throwable;

interface ExceptionMiddlewareInterface
{

    /**
     *
     * @param Throwable $exception
     * @return void
     */
    public function exceptionHook(Throwable $exception): void;


}