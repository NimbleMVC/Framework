<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Interfaces\MiddlewareInterface;
use Throwable;

/**
 * Middleware class
 */
class Middleware implements MiddlewareInterface
{

    public function afterBootstrap()
    {
    }

    public function beforeController(string &$controllerName, string &$action, array &$params)
    {
    }

    public function afterController(string $controllerName, string $action, array $params)
    {
    }

    public function handleException(Throwable $exception)
    {
    }

    public function log(array &$logContent)
    {
    }

    public function afterLog(array $logContent)
    {
    }

}