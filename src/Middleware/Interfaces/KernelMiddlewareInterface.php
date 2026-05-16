<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

/**
 * @deprecated Use typed framework events instead.
 */
interface KernelMiddlewareInterface
{

    /**
     * @return void
     */
    public function afterBootstrap(): void;

}
