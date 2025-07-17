<?php

namespace NimblePHP\Framework\Interfaces;

use Throwable;

interface MiddlewareInterface
{

    /**
     * Handle the request
     * @param \NimblePHP\Framework\Interfaces\RequestInterface $request
     * @param callable $next
     * @return \NimblePHP\Framework\Interfaces\ResponseInterface
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;

    /**
     * After bootstrap
     * @return void
     */
    public function afterBootstrap(): void;

    /**
     * Handle exception
     * @param \Throwable $exception
     * @return void
     */
    public function handleException(Throwable $exception): void;

    /**
     * Log the request
     * @param array &$logContent
     * @return void
     */
    public function log(array &$logContent): void;

    /**
     * After log
     * @param array $logContent
     * @return void
     */
    public function afterLog(array $logContent): void;

}