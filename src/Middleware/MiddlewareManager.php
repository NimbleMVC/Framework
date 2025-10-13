<?php

namespace NimblePHP\Framework\Middleware;

/**
 * Middleware manager that supports priorities and special hooks (e.g. afterBootstrap).
 */
class MiddlewareManager
{

    /**
     * Registered middleware stack (priority => [middlewares]).
     * @var array<int, array<int, callable|object|string>>
     */
    protected array $stack = [];

    /**
     * Add middleware with optional priority.
     * @param callable|object|string $middleware
     * @param int $priority
     * @return void
     */
    public function add(mixed $middleware, int $priority = 0): void
    {
        $this->stack[$priority][] = $middleware;
    }

    /**
     * Get sorted middleware stack.
     * @return array<int, callable|object|string>
     */
    protected function getSortedStack(): array
    {
        if (empty($this->stack)) {
            return [];
        }

        krsort($this->stack);

        return array_merge(...array_values($this->stack));
    }

    /**
     * Handle the middleware stack.
     * @param mixed $request
     * @param callable $finalHandler
     * @return mixed
     */
    public function handle(mixed $request, callable $finalHandler): mixed
    {
        $stack = $this->getSortedStack();
        $core = array_reduce(
            array_reverse($stack),
            function ($next, $middleware) {
                return function ($req) use ($middleware, $next) {
                    if (is_string($middleware) && class_exists($middleware)) {
                        $middlewareInstance = $middleware::getInstance();

                        return $middlewareInstance->handle($req, $next);
                    } elseif (is_object($middleware) && method_exists($middleware, 'handle')) {
                        return $middleware->handle($req, $next);
                    } elseif (is_callable($middleware)) {
                        return $middleware($req, $next);
                    }

                    throw new \RuntimeException('Invalid middleware');
                };
            },
            $finalHandler
        );
        $response = $core($request);

        foreach ($stack as $middleware) {
            $instance = $middleware;

            if (is_string($middleware) && class_exists($middleware)) {
                $instance = $middleware::getInstance();
            }

            if (is_object($instance) && method_exists($instance, 'afterBootstrap')) {
                $instance->afterBootstrap();
            }
        }

        return $response;
    }

    /**
     * @param string $methodName
     * @param array $args
     * @return void
     */
    public function runHook(string $methodName, array $args = []): void
    {
        foreach ($this->getSortedStack() as $middleware) {
            $instance = $middleware;

            if (is_string($middleware) && class_exists($middleware)) {
                $instance = $middleware::getInstance();
            }

            if (is_object($instance) && method_exists($instance, $methodName)) {
                $instance->$methodName(...$args);
            }
        }
    }

    /**
     * @param string $methodName
     * @param mixed $context
     * @return void
     */
    public function runHookWithReference(string $methodName, mixed &$context): void
    {
        foreach ($this->getSortedStack() as $middleware) {
            $instance = $middleware;

            if (is_string($middleware) && class_exists($middleware)) {
                $instance = $middleware::getInstance();
            }

            if (is_object($instance) && method_exists($instance, $methodName)) {
                $instance->{$methodName}($context);
            }
        }
    }

}