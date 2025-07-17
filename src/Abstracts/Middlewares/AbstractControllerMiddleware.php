<?php

namespace NimblePHP\Framework\Abstracts\Middlewares;

use NimblePHP\Framework\Interfaces\ControllerMiddlewareInterface;

abstract class AbstractControllerMiddleware implements ControllerMiddlewareInterface
{
    /**
     * Before controller execution
     * @param string &$controllerName
     * @param string &$action
     * @param array &$params
     * @return void
     */
    public function beforeController(string &$controllerName, string &$action, array &$params): void {}

    /**
     * After controller execution
     * @param string $controllerName
     * @param string $action
     * @param array $params
     * @return void
     */
    public function afterController(string $controllerName, string $action, array $params): void {}

    /**
     * Before action execution
     * @param string $controllerName
     * @param string $action
     * @param array &$params
     * @return void
     */
    public function beforeAction(string $controllerName, string $action, array &$params): void {}

    /**
     * After action execution
     * @param string $controllerName
     * @param string $action
     * @param array $params
     * @param mixed $result
     * @return void
     */
    public function afterAction(string $controllerName, string $action, array $params, $result): void {}

    /**
     * Before controller construction
     * @param string $controllerName
     * @return void
     */
    public function beforeConstruct(string $controllerName): void {}

    /**
     * After controller construction
     * @param string $controllerName
     * @param object $controller
     * @return void
     */
    public function afterConstruct(string $controllerName, object $controller): void {}

}