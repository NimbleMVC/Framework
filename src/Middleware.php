<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\MiddlewareInterface;
use Throwable;

/**
 * Middleware class
 */
class Middleware implements MiddlewareInterface
{

    public function afterBootstrap()
    {
    }

    public function beforeController(string $controllerName, string $action, array $params)
    {
    }

    public function afterController(string $controllerName, string $action, array $params)
    {
    }

    public function handleException(Throwable $exception)
    {
    }

    public function afterLog(array $logContent)
    {
    }

}