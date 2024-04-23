<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\RequestInterface;
use Nimblephp\framework\Interfaces\RouteInterface;

/**
 * Route
 */
class Route implements RouteInterface
{

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
     * Get controller
     * @return string
     */
    public function getController(): string
    {
        return '\src\Controller\\' . ($this->controller ?? Config::get('DEFAULT_CONTROLLER'));
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
        return $this->method ?? Config::get('DEFAULT_METHOD');
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

}