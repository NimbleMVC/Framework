<?php

namespace NimblePHP\Framework\Routes;

use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;

/**
 * Route
 */
class SimpleRoute implements RouteInterface
{

    /**
     * Predefined routes
     * @var array
     */
    protected static array $routes = [];

    /**
     * Controller name
     * @var ?string
     */
    protected ?string $controller;

    /**
     * Method name
     * @var ?string
     */
    protected ?string $method;

    /**
     * Parameters list
     * @var array
     */
    protected array $params = [];

    /**
     * Add route
     * @param string $name
     * @param string|null $controller
     * @param string|null $method
     * @return void
     */
    public static function addRoute(string $name, ?string $controller = null, ?string $method = null): void
    {
        self::$routes[$name] = [
            'controller' => $controller ?? $_ENV['DEFAULT_CONTROLLER'],
            'method' => $method ?? $_ENV['DEFAULT_METHOD']
        ];
    }

    /**
     * Get routes
     * @return array
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Constructor
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $uri = strtok($request->getUri(), '?');

        if (str_starts_with($uri, '/')) {
            $uri = substr($uri, 1);
        }

        $uri = explode('/', htmlspecialchars($uri), 3);

        if (count($uri) === 1 && $uri[0] === '') {
            $uri = [];
        }

        $this->setController($uri[0] ?? null);
        $this->setMethod($uri[1] ?? null);
        $this->setParams(isset($uri[2]) ? explode('/', $uri[2]) : []);
    }

    /**
     * Reload routing
     * @return void
     */
    public function reload(): void
    {
        foreach (self::$routes as $route => $parameters) {
            if (in_array($route, [$this->getController(), $this->getController() . '/' . $this->getMethod()])) {
                $this->setController($parameters['controller']);
                $this->setMethod($parameters['method']);
            }
        }
    }

    /**
     * Get controller
     * @return string
     */
    public function getController(): string
    {
        return $this->controller ?? $_ENV['DEFAULT_CONTROLLER'];
    }

    /**
     * Set controller
     * @param ?string $controller
     * @return void
     */
    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * Get method
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method ?? $_ENV['DEFAULT_METHOD'];
    }

    /**
     * Set method
     * @param ?string $method
     * @return void
     */
    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    /**
     * Get params
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set params
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Route validate
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * Register routes
     * @param string $controllerPath
     * @param string $namespace
     * @return void
     */
    public static function registerRoutes(string $controllerPath, string $namespace): void
    {
    }

}