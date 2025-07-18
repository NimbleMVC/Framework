<?php

namespace NimblePHP\Framework\Middleware\Abstracts;

use NimblePHP\Framework\Middleware\Interfaces\ControllerMiddlewareInterface;

abstract class AbstractControllerMiddleware implements ControllerMiddlewareInterface
{

    public function beforeController(array &$controllerContext): void
    {
    }

    public function afterController(string $controllerName, string $methodName, array $params): void
    {
    }

}