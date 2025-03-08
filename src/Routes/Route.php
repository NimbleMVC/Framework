<?php

namespace NimblePHP\Framework\Routes;

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Storage;

/**
 * Route
 */
class Route implements RouteInterface
{

    /**
     * Predefined routes
     * @var array
     */
    protected static array $routes = [];

    /**
     * Cache file
     * @var string
     */
    public static string $cacheFile = 'framework/route.cache';

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
            'route' => $name,
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
        if (!array_key_exists('/' . $this->controller . (!is_null($this->method) ? '/' . $this->method : ''), self::$routes)) {
            return;
        }

        $route = self::$routes['/' . $this->controller . (!is_null($this->method) ? '/' . $this->method : '')];
        $this->setController($route['controller']);
        $this->setMethod($route['method']);
    }

    /**
     * Get controller
     * @return string
     */
    public function getController(): string
    {
        return $this->controller ?? ($_ENV['DEFAULT_CONTROLLER']);
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

    public function validate(): bool
    {
        $url = explode('/', ltrim((new Request())->getUri(), '/'), 3);
        $routeName = '/';

        if (isset($url[0])) {
            $routeName .= $url[0];
        }

        if (isset($url[1])) {
            $routeName .= '/' . strtok($url[1], '?');
        }

        if ($routeName === '/') {
            $routeName .= $_ENV['DEFAULT_CONTROLLER'] . '/' . $_ENV['DEFAULT_METHOD'];
        }

        return array_key_exists($routeName, self::$routes);
    }

    /**
     * Auto register routes
     * @param string $controllerPath
     * @param string $namespace
     * @return void
     * @throws NimbleException
     */
    public static function registerRoutes(string $controllerPath, string $namespace): void {
        if ($_ENV['CACHE_ROUTE']) {
            $storage = new Storage('cache');

            if ($storage->exists(self::$cacheFile)) {
                self::$routes = unserialize($storage->get(self::$cacheFile));
                return;
            }
        }

        $controllers = self::getAllControllers($controllerPath, $namespace);

        foreach ($controllers as $controller) {
            if (!class_exists($controller)) {
                continue;
            }

            $reflection = new \ReflectionClass($controller);

            foreach ($reflection->getMethods() as $method) {
                foreach ($method->getAttributes(\NimblePHP\Framework\Attributes\Http\Route::class) as $attribute) {
                    $route = $attribute->newInstance();
                    self::addRoute($route->path, str_replace('App\Controller\\', '', $controller), $method->name);
                }
            }
        }

        if ($_ENV['CACHE_ROUTE'] && isset($storage)) {
            $storage->put(self::$cacheFile, serialize(self::$routes));
        }
    }

    /**
     * Get all controllers
     * @param string $directory
     * @param string $namespace
     * @return array
     */
    private static function getAllControllers(string $directory, string $namespace): array {
        $controllers = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $namespace . '\\' . $file->getBasename('.php');
                $controllers[] = str_replace('/', '\\', $className);
            }
        }

        return $controllers;
    }

}