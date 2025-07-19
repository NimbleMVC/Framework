<?php

namespace NimblePHP\Framework\Routes;

use NimblePHP\Framework\Cache;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;
use NimblePHP\Framework\Libs\Classes;
use ReflectionClass;

/**
 * Route management class for handling URI routing
 */
class Route implements RouteInterface
{

    /**
     * Registry of defined routes
     * @var array
     */
    protected static array $routes = [];

    /**
     * Route cache key
     * @var string
     */
    public static string $cacheKey = 'framework_routes';

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
     * Register a new route in the system.
     * @param string $path URI path pattern
     * @param string|null $controller Controller name
     * @param string|null $method Method name
     * @param array|string $httpMethod Allowed HTTP methods
     * @return void
     */
    public static function addRoute(string $path, ?string $controller = null, ?string $method = null, array|string $httpMethod = ['GET', 'POST']): void
    {
        if (is_string($httpMethod)) {
            $httpMethod = explode(',', $httpMethod);
        }

        if (strpos($path, '[') !== false && strpos($path, ']') !== false) {
            $pathVariants = self::generatePathVariants($path);

            foreach ($pathVariants as $variant) {
                self::$routes[$variant] = [
                    'path' => $variant,
                    'controller' => $controller ?? $_ENV['DEFAULT_CONTROLLER'],
                    'method' => $method ?? $_ENV['DEFAULT_METHOD'],
                    'httpMethod' => implode(',', $httpMethod)
                ];
            }
        } else {
            self::$routes[$path] = [
                'path' => $path,
                'controller' => $controller ?? $_ENV['DEFAULT_CONTROLLER'],
                'method' => $method ?? $_ENV['DEFAULT_METHOD'],
                'httpMethod' => implode(',', $httpMethod)
            ];
        }
    }

    /**
     * Get all registered routes sorted by path
     * @return array
     */
    public static function getRoutes(): array
    {
        $routes = self::$routes;
        ksort($routes);

        return $routes;
    }

    /**
     * Initialize route from request
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
     * Match URI against dynamic routes with parameters including typed parameters with default values
     * @param string $uri
     * @return array|null
     */
    private function matchDynamicRoute(string $uri): ?array
    {
        foreach (self::$routes as $pattern => $route) {
            if (strpos($pattern, '{') === false) {
                continue;
            }

            $paramRegex = '/{([^}:]+)(?::([^=}]+)(?:=([^}]+))?)?}/';

            if (!preg_match_all($paramRegex, $pattern, $paramMatches, PREG_SET_ORDER)) {
                continue;
            }

            $hasDefaultParams = false;
            foreach ($paramMatches as $match) {
                if (isset($match[3])) {
                    $hasDefaultParams = true;
                    break;
                }
            }

            if ($hasDefaultParams) {
                $partialMatch = $this->tryPartialMatch($uri, $pattern, $route, $paramMatches);
                if ($partialMatch !== null) {
                    return $partialMatch;
                }
            }

            $uriPattern = $pattern;
            $paramNames = [];
            $paramTypes = [];
            $paramDefaults = [];

            foreach ($paramMatches as $match) {
                $paramName = $match[1];
                $paramType = $match[2] ?? null;
                $paramDefault = $match[3] ?? null;

                $paramNames[] = $paramName;
                if ($paramType !== null) {
                    $paramTypes[$paramName] = $paramType;
                }

                if ($paramDefault !== null) {
                    $paramDefaults[$paramName] = $paramDefault;
                }

                $typePattern = $this->getTypePattern($paramType);
                $uriPattern = str_replace($match[0], '(' . $typePattern . ')', $uriPattern);
            }

            $uriRegex = str_replace('/', '\/', $uriPattern);

            if (preg_match('/^' . $uriRegex . '$/', $uri, $matches)) {
                array_shift($matches);
                $params = [];

                foreach ($matches as $i => $value) {
                    if (isset($paramNames[$i])) {
                        $paramName = $paramNames[$i];
                        $type = $paramTypes[$paramName] ?? null;

                        $params[] = $this->convertValueToType($value, $type);
                    } else {
                        $params[] = $value;
                    }
                }

                return [
                    'route' => $route,
                    'params' => $params
                ];
            }
        }

        return null;
    }

    /**
     * Try to match URI with a partial pattern, filling in default values
     * @param string $uri
     * @param string $pattern
     * @param array $route
     * @param array $paramMatches
     * @return array|null
     */
    private function tryPartialMatch(string $uri, string $pattern, array $route, array $paramMatches): ?array
    {
        $paramInfo = [];

        foreach ($paramMatches as $match) {
            $paramName = $match[1];
            $paramType = $match[2] ?? null;
            $paramDefault = $match[3] ?? null;

            $paramInfo[] = [
                'name' => $paramName,
                'type' => $paramType,
                'default' => $paramDefault,
                'pattern' => $match[0]
            ];
        }

        $patternParts = explode('/', trim($pattern, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($uriParts) < count($patternParts)) {
            $allOptionalHaveDefaults = true;
            $missingParams = [];

            for ($i = count($uriParts); $i < count($patternParts); $i++) {
                $segment = $patternParts[$i];
                $isOptional = false;
                $defaultValue = null;
                $paramType = null;

                foreach ($paramInfo as $param) {
                    if (strpos($segment, $param['pattern']) !== false && $param['default'] !== null) {
                        $isOptional = true;
                        $defaultValue = $param['default'];
                        $paramType = $param['type'];
                        $missingParams[] = $this->convertValueToType($defaultValue, $paramType);
                        break;
                    }
                }

                if (!$isOptional) {
                    $allOptionalHaveDefaults = false;
                    break;
                }
            }

            if ($allOptionalHaveDefaults) {
                $partialPattern = '/' . implode('/', array_slice($patternParts, 0, count($uriParts)));
                $uriPattern = $partialPattern;

                foreach ($paramInfo as $param) {
                    if (strpos($uriPattern, $param['pattern']) !== false) {
                        $typePattern = $this->getTypePattern($param['type']);
                        $uriPattern = str_replace($param['pattern'], '(' . $typePattern . ')', $uriPattern);
                    }
                }

                $uriRegex = str_replace('/', '\/', $uriPattern);

                if (preg_match('/^' . $uriRegex . '$/', $uri, $matches)) {
                    array_shift($matches);
                    $matchedParams = [];

                    foreach ($matches as $i => $value) {
                        $type = null;

                        if (isset($paramInfo[$i])) {
                            $type = $paramInfo[$i]['type'];
                        }

                        $matchedParams[] = $this->convertValueToType($value, $type);
                    }

                    return [
                        'route' => $route,
                        'params' => array_merge($matchedParams, $missingParams)
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Convert string value to appropriate type
     * @param string $value
     * @param string|null $type
     * @return mixed
     */
    private function convertValueToType(string $value, ?string $type): mixed
    {
        if ($type === null) {
            return $value;
        }

        if (strtolower($value) === 'null') {
            return null;
        }

        return match ($type) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    /**
     * Get regex pattern for parameter type
     * @param string|null $type
     * @return string
     */
    private function getTypePattern(?string $type): string
    {
        return match ($type) {
            'int' => '[0-9]+',
            'float' => '[0-9]+(?:\\.[0-9]+)?',
            'bool' => '(?:true|false|1|0)',
            default => '[^/]+',
        };
    }

    /**
     * Generate all possible path variants for optional segments
     * @param string $path Path with optional segments in square brackets
     * @return array Array of path variants
     */
    private static function generatePathVariants(string $path): array
    {
        if (strpos($path, '[') === false) {
            return [$path];
        }

        $openPos = strpos($path, '[');
        $level = 0;
        $closePos = null;

        for ($i = $openPos; $i < strlen($path); $i++) {
            if ($path[$i] === '[') {
                $level++;
            } elseif ($path[$i] === ']') {
                $level--;
                if ($level === 0) {
                    $closePos = $i;
                    break;
                }
            }
        }

        if ($closePos === null) {
            return [$path];
        }

        $prefix = substr($path, 0, $openPos);
        $optional = substr($path, $openPos + 1, $closePos - $openPos - 1);
        $suffix = substr($path, $closePos + 1);
        $suffixVariants = self::generatePathVariants($suffix);
        $optionalVariants = self::generatePathVariants($optional);
        $result = [];

        foreach ($suffixVariants as $s) {
            $result[] = $prefix . $s;
        }

        foreach ($optionalVariants as $o) {
            foreach ($suffixVariants as $s) {
                $result[] = $prefix . $o . $s;
            }
        }

        return $result;
    }

    /**
     * Get controller name
     * @return string
     */
    public function getController(): string
    {
        return $this->controller ?? ($_ENV['DEFAULT_CONTROLLER']);
    }

    /**
     * Set controller name
     * @param ?string $controller
     * @return void
     */
    public function setController(?string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * Get method name
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method ?? $_ENV['DEFAULT_METHOD'];
    }

    /**
     * Set method name
     * @param ?string $method
     * @return void
     */
    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    /**
     * Get URI parameters
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set URI parameters
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Validate HTTP method against route configuration
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
     * @param string $controllerPath
     * @param string $namespace
     * @return void
     */
    public static function registerRoutes(string $controllerPath, string $namespace): void
    {
        $cacheEnabled = $_ENV['CACHE_ROUTE'] ?? false;
        $cache = new Cache();

        if ($cacheEnabled) {
            if ($cache->has(self::$cacheKey)) {
                $cachedData = $cache->get(self::$cacheKey);
                $controllersDirTimestamp = filemtime($controllerPath);

                if (isset($cachedData['timestamp']) && $cachedData['timestamp'] >= $controllersDirTimestamp) {
                    self::$routes = $cachedData['routes'];
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

        if ($cacheEnabled) {
            $cache->set(self::$cacheKey, [
                'routes' => self::$routes,
                'timestamp' => time()
            ]);
        }
    }
}