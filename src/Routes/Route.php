<?php

namespace NimblePHP\Framework\Routes;

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;
use NimblePHP\Framework\Libs\Classes;
use NimblePHP\Framework\Storage;
use ReflectionClass;
use ReflectionMethod;

/**
 * Route management class for handling URI routing
 */
class Route implements RouteInterface
{
    /**
     * Registry of defined routes
     * @var array
     */
    public static array $routes = [];

    /**
     * Route cache file path
     * @var string
     */
    public static string $cacheFile = 'framework/route.cache';

    /**
     * Current controller name
     * @var ?string
     */
    protected ?string $controller;

    /**
     * Current method name
     * @var ?string
     */
    protected ?string $method;

    /**
     * Current URI parameters
     * @var array
     */
    protected array $params = [];

    /**
     * Request instance
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * Register a new route
     *
     * @param string $path URI path pattern
     * @param string|null $controller Controller name
     * @param string|null $method Method name
     * @param array $httpMethod Allowed HTTP methods
     * @return void
     */
    public static function addRoute(string $path, ?string $controller = null, ?string $method = null, array $httpMethod = ['GET', 'POST']): void
    {
        self::$routes[$path] = [
            'path' => $path,
            'controller' => $controller ?? $_ENV['DEFAULT_CONTROLLER'],
            'method' => $method ?? $_ENV['DEFAULT_METHOD'],
            'httpMethod' => implode(',', $httpMethod)
        ];
    }

    /**
     * Get all registered routes
     *
     * @return array
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Initialize route from request
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
        $uri = strtok($request->getUri(), '?');

        if (str_starts_with($uri, '/')) {
            $uri = substr($uri, 1);
        }

        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        $uriParts = explode('/', $uri, 3);

        if (count($uriParts) === 1 && $uriParts[0] === '') {
            $uriParts = [];
        }

        $this->setController($uriParts[0] ?? null);
        $this->setMethod($uriParts[1] ?? null);
        $this->setParams(isset($uriParts[2]) ? explode('/', $uriParts[2]) : []);
    }

    /**
     * Match current URI with registered routes
     *
     * @return void
     * @throws NotFoundException
     */
    public function reload(): void
    {
        $uriPath = '/' . $this->controller . (!is_null($this->method) ? '/' . $this->method : '');

        if (array_key_exists($uriPath, self::$routes)) {
            $route = self::$routes[$uriPath];
            $this->setController($route['controller']);
            $this->setMethod($route['method']);
            return;
        }

        $fullPath = $uriPath;
        foreach ($this->params as $param) {
            $fullPath .= '/' . $param;
        }

        if (array_key_exists($fullPath, self::$routes)) {
            $route = self::$routes[$fullPath];
            $this->setController($route['controller']);
            $this->setMethod($route['method']);
            return;
        }

        foreach ($this->params as $key => $param) {
            $paramPath = $uriPath . '/' . $param;
            if (array_key_exists($paramPath, self::$routes)) {
                $route = self::$routes[$paramPath];
                $this->setController($route['controller']);
                $this->setMethod($route['method']);
                unset($this->params[$key]);
                return;
            }
        }

        $dynamicMatch = $this->matchDynamicRoute($fullPath);
        if ($dynamicMatch !== null) {
            $route = $dynamicMatch['route'];
            $this->setController($route['controller']);
            $this->setMethod($route['method']);
            $this->setParams($dynamicMatch['params']);
            return;
        }

        throw new NotFoundException('Route ' . $uriPath . ' not found');
    }

    /**
     * Match URI against dynamic routes with parameters
     *
     * @param string $uri
     * @return array|null
     */
    private function matchDynamicRoute(string $uri): ?array
    {
        foreach (self::$routes as $pattern => $route) {
            if (strpos($pattern, '{') === false) {
                continue;
            }

            $paramNames = [];
            preg_match_all('/{([^}]+)}/', $pattern, $matches);
            if (isset($matches[1])) {
                $paramNames = $matches[1];
            }

            $regex = preg_replace('/{([^}]+)}/', '([^/]+)', $pattern);
            $regex = str_replace('/', '\/', $regex);

            if (preg_match('/^' . $regex . '$/', $uri, $matches)) {
                array_shift($matches);
                return [
                    'route' => $route,
                    'params' => $matches
                ];
            }
        }
        return null;
    }

    /**
     * Get controller name
     *
     * @return string
     */
    public function getController(): string
    {
        return $this->controller ?? ($_ENV['DEFAULT_CONTROLLER']);
    }

    /**
     * Set controller name
     *
     * @param ?string $controller
     * @return void
     */
    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * Get method name
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method ?? $_ENV['DEFAULT_METHOD'];
    }

    /**
     * Set method name
     *
     * @param ?string $method
     * @return void
     */
    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    /**
     * Get URI parameters
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set URI parameters
     *
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Validate HTTP method against route configuration
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (isset(self::$routes['/' . $this->controller . '/' . $this->method])) {
            $route = self::$routes['/' . $this->controller . '/' . $this->method];
            $allowedMethods = explode(',', $route['httpMethod']);
            return in_array($this->request->getMethod(), $allowedMethods);
        }
        return true;
    }

    /**
     * Auto-register routes from controller classes
     *
     * @param string $controllerPath
     * @param string $namespace
     * @return void
     * @throws NimbleException
     */
    public static function registerRoutes(string $controllerPath, string $namespace): void {
        $cacheEnabled = $_ENV['CACHE_ROUTE'] ?? false;

        if ($cacheEnabled) {
            $storage = new Storage('cache');

            if ($storage->exists(self::$cacheFile)) {
                $cacheTimestamp = filemtime($storage->getFullPath(self::$cacheFile));
                $controllersDirTimestamp = filemtime($controllerPath);

                if ($cacheTimestamp > $controllersDirTimestamp) {
                    self::$routes = unserialize($storage->get(self::$cacheFile));
                    return;
                }
            }
        }

        foreach (Classes::getAllClasses($controllerPath, $namespace) as $controller) {
            if (!class_exists($controller)) {
                continue;
            }

            $reflection = new ReflectionClass($controller);

            foreach ($reflection->getMethods() as $method) {
                foreach ($method->getAttributes(\NimblePHP\Framework\Attributes\Http\Route::class) as $attribute) {
                    $route = $attribute->newInstance();
                    self::addRoute(
                        $route->path,
                        str_replace('App\Controller\\', '', $controller),
                        $method->name,
                        [$route->method]
                    );
                }
            }
        }

        if ($cacheEnabled && isset($storage)) {
            $storage->put(self::$cacheFile, serialize(self::$routes));
        }
    }
}