<?php

namespace NimblePHP\Framework\Middleware\Abstracts;

use NimblePHP\Framework\Middleware\Interfaces\KernelMiddlewareInterface;

abstract class AbstractKernelMiddleware implements KernelMiddlewareInterface
{

    public function afterBootstrap(): void
    {
    }

}