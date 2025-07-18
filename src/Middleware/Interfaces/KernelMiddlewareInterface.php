<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

interface KernelMiddlewareInterface
{

    /**
     * @return void
     */
    public function afterBootstrap(): void;

}
