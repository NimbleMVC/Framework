<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

use ReflectionMethod;

/**
 * @deprecated Use typed framework events instead.
 */
interface ControllerMiddlewareInterface
{

    public function beforeController(array &$controllerContext): void;

    public function afterController(string $controllerName, string $methodName, array $params): void;

    public function afterAttributesController(ReflectionMethod $reflection, object $controller): void;

}
