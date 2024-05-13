<?php

namespace Nimblephp\framework\Interfaces;

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
    public function beforeController(string $controllerName, string $action, array $params);

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

}