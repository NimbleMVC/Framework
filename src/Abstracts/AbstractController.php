<?php

namespace NimblePHP\Framework\Abstracts;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Log;
use NimblePHP\Framework\Traits\LoadModelTrait;
use ReflectionException;
use ReflectionFunction;

/**
 * Abstract controller
 */
abstract class AbstractController implements ControllerInterface
{

    use LoadModelTrait;

    /**
     * Dynamic methods registry for controllers
     * @var array<string, array<string, Closure>>
     */
    protected static array $dynamicMethods = [];

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
    }

    /**
     * Register dynamic method for controllers
     * @param string $methodName
     * @param callable $callable
     * @param string|null $controllerClass
     * @return void
     */
    #[Action("disabled")]
    public static function registerDynamicMethod(string $methodName, callable $callable, ?string $controllerClass = null): void
    {
        $methodName = strtolower(trim($methodName));

        if ($methodName === '') {
            throw new InvalidArgumentException('Method name cannot be empty');
        }

        $controllerClass = self::normalizeControllerClass($controllerClass);

        if ($controllerClass !== '*' && $controllerClass !== self::class && !is_subclass_of($controllerClass, self::class)) {
            throw new InvalidArgumentException('Controller class must extend ' . self::class);
        }

        self::$dynamicMethods[$controllerClass][$methodName] = Closure::fromCallable($callable);
    }

    /**
     * Check if dynamic method exists
     * @param string $methodName
     * @param string|null $controllerClass
     * @return bool
     */
    #[Action("disabled")]
    public static function hasDynamicMethod(string $methodName, ?string $controllerClass = null): bool
    {
        $controllerClass = self::normalizeControllerClass($controllerClass ?? static::class);
        return self::resolveDynamicMethod($controllerClass, $methodName) !== null;
    }

    /**
     * Get dynamic method param count
     * @param string $methodName
     * @param string|null $controllerClass
     * @return int|null
     * @throws ReflectionException
     */
    #[Action("disabled")]
    public static function getDynamicMethodParameterCount(string $methodName, ?string $controllerClass = null): ?int
    {
        $controllerClass = self::normalizeControllerClass($controllerClass ?? static::class);
        $method = self::resolveDynamicMethod($controllerClass, $methodName);

        if ($method === null) {
            return null;
        }

        return (new ReflectionFunction($method))->getNumberOfParameters();
    }

    /**
     * Handle dynamic method calls
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    #[Action("disabled")]
    public function __call(string $name, array $arguments): mixed
    {
        $method = self::resolveDynamicMethod(static::class, $name);

        if ($method === null) {
            throw new BadMethodCallException('Method ' . static::class . '::' . $name . ' does not exist');
        }

        return $method->bindTo($this, static::class)(...$arguments);
    }

    /**
     * Resolve dynamic method for controller class
     * @param string $controllerClass
     * @param string $methodName
     * @return Closure|null
     */
    protected static function resolveDynamicMethod(string $controllerClass, string $methodName): ?Closure
    {
        $methodName = strtolower($methodName);
        $controllerClass = self::normalizeControllerClass($controllerClass);

        if (isset(self::$dynamicMethods[$controllerClass][$methodName])) {
            return self::$dynamicMethods[$controllerClass][$methodName];
        }

        foreach (class_parents($controllerClass) ?: [] as $parentClass) {
            if (isset(self::$dynamicMethods[$parentClass][$methodName])) {
                return self::$dynamicMethods[$parentClass][$methodName];
            }
        }

        if (isset(self::$dynamicMethods['*'][$methodName])) {
            return self::$dynamicMethods['*'][$methodName];
        }

        return null;
    }

    /**
     * Normalize controller class name
     * @param string|null $controllerClass
     * @return string
     */
    protected static function normalizeControllerClass(?string $controllerClass): string
    {
        if ($controllerClass === null) {
            return '*';
        }

        return ltrim($controllerClass, '\\');
    }

}