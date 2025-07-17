<?php

namespace NimblePHP\Framework\Abstracts\Middlewares;

use NimblePHP\Framework\Interfaces\MiddlewareInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\ResponseInterface;
use Throwable;

abstract class AbstractMiddleware implements MiddlewareInterface
{

    /**
     * After bootstrap
     * @return void
     */
    public function afterBootstrap(): void
    {
    }

    /**
     * Before controller
     * @param string &$controllerName
     * @param string &$action
     * @param array &$params
     * @return void
     */
    public function beforeController(string &$controllerName, string &$action, array &$params): void
    {
    }

    /**
     * After controller
     * @param string $controllerName
     * @param string $action
     * @param array $params
     * @return void
     */
    public function afterController(string $controllerName, string $action, array $params): void
    {
    }

    /**
     * Handle exception
     * @param \Throwable $exception
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
    }

    /**
     * Log the request
     * @param array &$logContent
     * @return void
     */
    public function log(array &$logContent): void
    {
    }

    /**
     * After log
     * @param array $logContent
     * @return void
     */
    public function afterLog(array $logContent): void
    {
    }

    /**
     * Handle the request
     * @param \NimblePHP\Framework\Interfaces\RequestInterface $request
     * @param callable $next
     * @return \NimblePHP\Framework\Interfaces\ResponseInterface
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        return $next($request);
    }
}