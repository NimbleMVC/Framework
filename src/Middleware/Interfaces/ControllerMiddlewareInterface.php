<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

interface ControllerMiddlewareInterface
{

    public function beforeController(array &$controllerContext): void;

    public function afterController(string $controllerName, string $methodName, array $params): void;

}