<?php

namespace NimblePHP\Framework\Middleware\Abstracts;

use NimblePHP\Framework\Middleware\Interfaces\KernelMiddlewareInterface;

/**
 * @deprecated Use typed framework events instead.
 */
abstract class AbstractKernelMiddleware implements KernelMiddlewareInterface
{

    public function afterBootstrap(): void
    {
    }

}
