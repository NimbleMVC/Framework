<?php

namespace NimblePHP\Framework\Interfaces;

use Throwable;

/**
 * Loader interface
 */
interface MiddlewareInterface
{

    /**
     * Init before controller
     * @param string $controllerName
     * @param string $action
     * @param array $params
     */
    public function beforeController(string &$controllerName, string &$action, array &$params);

    /**
     * Init after controller
     * @param string $controllerName
     * @param string $action
     * @param array $params
     */
    public function afterController(string $controllerName, string $action, array $params);

    /**
     * Init after bootstrap
     */
    public function afterBootstrap();

    /**
     * After exception handler
     * @param Throwable $exception
     */
    public function handleException(Throwable $exception);

    /**
     * After log
     * @param array $logContent
     * @return mixed
     */
    public function afterLog(array $logContent);

}