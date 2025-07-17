<?php

namespace NimblePHP\Framework\Abstracts;

use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Interfaces\ControllerMiddlewareInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Log;
use NimblePHP\Framework\Traits\LoadModelTrait;

/**
 * Abstract controller
 */
abstract class AbstractController implements ControllerInterface
{

    use LoadModelTrait;

    /**
     * Controller name
     * @var string
     */
    public string $name;

    /**
     * Controller action
     * @var string
     */
    public string $action;

    /**
     * Request instance
     * @var RequestInterface
     */
    public RequestInterface $request;

    /**
     * Controller middleware
     * @var array
     */
    protected array $middleware = [];

    /**
     * Global controller middleware - applied to all controllers
     * @var array
     */
    protected static array $globalMiddleware = [];

    /**
     * Create logs
     * @param string $message
     * @param string $level
     * @param array $content
     * @return bool
     */
    #[Action("disabled")]
    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return Log::log($message, $level, $content);
    }

    /**
     * After construct method
     * @return void
     */
    #[Action("disabled")]
    public function afterConstruct(): void
    {
        // Merge global middleware with instance middleware
        $this->middleware = array_merge(static::$globalMiddleware, $this->middleware);

        // Run afterConstruct middleware
        $this->runMiddleware('afterConstruct', $this->name, $this);
    }

    /**
     * Run controller middleware
     * @param string $method
     * @param mixed ...$args
     * @return void
     */
    public function runMiddleware(string $method, ...$args): void
    {
        foreach ($this->middleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();

                if ($middleware instanceof ControllerMiddlewareInterface && method_exists($middleware, $method)) {
                    $middleware->$method(...$args);
                }
            }
        }
    }

    /**
     * Add global middleware that will be applied to all controllers
     * @param string $middlewareClass
     * @return void
     */
    public static function addGlobalMiddleware(string $middlewareClass): void
    {
        if (!in_array($middlewareClass, static::$globalMiddleware)) {
            static::$globalMiddleware[] = $middlewareClass;
        }
    }

    /**
     * Get global middleware
     * @return array
     */
    public static function getGlobalMiddleware(): array
    {
        return static::$globalMiddleware;
    }
}