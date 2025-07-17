<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Interfaces\MiddlewareInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\ResponseInterface;

/**
 * Middleware manager
 */
class MiddlewareManager
{

    /**
     * Middlewares
     * @var array
     */
    private array $middlewares = [];

    /**
     * Route middlewares
     * @var array
     */
    private array $globalMiddlewares = [];

    /**
     * Route middlewares
     * @var array
     */
    private array $routeMiddlewares = [];

    /**
     * Add middleware
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Add global middleware
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addGlobal(MiddlewareInterface $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Add route middleware
     * @param string $route
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addRoute(string $route, MiddlewareInterface $middleware): self
    {
        if (!isset($this->routeMiddlewares[$route])) {
            $this->routeMiddlewares[$route] = [];
        }

        $this->routeMiddlewares[$route][] = $middleware;

        return $this;
    }

    /**
     * Run middleware
     * @param RequestInterface $request
     * @param callable $finalHandler
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, callable $finalHandler): ResponseInterface
    {
        $middlewares = array_merge($this->globalMiddlewares, $this->middlewares);

        $next = $finalHandler;

        foreach (array_reverse($middlewares) as $middleware) {
            $next = function (RequestInterface $request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }

        return $next($request);
    }

    /**
     * Run middleware for route
     * @param string $route
     * @param RequestInterface $request
     * @param callable $finalHandler
     * @return ResponseInterface
     */
    public function runForRoute(string $route, RequestInterface $request, callable $finalHandler): ResponseInterface
    {
        $routeMiddlewares = $this->routeMiddlewares[$route] ?? [];
        $allMiddlewares = array_merge($this->globalMiddlewares, $routeMiddlewares, $this->middlewares);

        $next = $finalHandler;

        foreach (array_reverse($allMiddlewares) as $middleware) {
            $next = function (RequestInterface $request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }

        return $next($request);
    }

    /**
     * Get middlewares
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Get global middlewares
     * @return array
     */
    public function getGlobalMiddlewares(): array
    {
        return $this->globalMiddlewares;
    }

    /**
     * Get route middlewares
     * @return array
     */
    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }

    /**
     * Clear middlewares
     * @return void
     */
    public function clear(): void
    {
        $this->middlewares = [];
        $this->routeMiddlewares = [];
        $this->globalMiddlewares = [];
    }

}