<?php

use NimblePHP\Framework\Attributes\Http\Route as HttpRoute;
use NimblePHP\Framework\Cache;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;
use PHPUnit\Framework\TestCase;

class RouteCoverageTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $_ENV['DEFAULT_CONTROLLER'] = 'index';
        $_ENV['DEFAULT_METHOD'] = 'index';
        $_ENV['CACHE_ROUTE'] = false;

        $this->projectPath = sys_get_temp_dir() . '/nimble_route_' . uniqid('', true);
        mkdir($this->projectPath . '/App/Controller', 0777, true);
        mkdir($this->projectPath . '/storage/cache', 0777, true);
        Kernel::$projectPath = $this->projectPath;

        $this->resetRoutes();
        (new Cache())->clear();
    }

    protected function tearDown(): void
    {
        $this->resetRoutes();
        $this->removeDirectory($this->projectPath);
    }

    public function testConstructorParsesUriAndGettersUseDefaults(): void
    {
        $emptyRoute = new Route($this->request('', 'GET'));
        $this->assertSame('index', $emptyRoute->getController());
        $this->assertSame('index', $emptyRoute->getMethod());
        $this->assertSame([], $emptyRoute->getParams());

        $route = new Route($this->request('/users/list/active/admin?tab=all', 'GET'));

        $this->assertSame('users', $route->getController());
        $this->assertSame('list', $route->getMethod());
        $this->assertSame(['active', 'admin'], $route->getParams());

        $route->setController('CustomController');
        $route->setMethod('customMethod');
        $route->setParams(['one', 'two']);

        $this->assertSame('CustomController', $route->getController());
        $this->assertSame('customMethod', $route->getMethod());
        $this->assertSame(['one', 'two'], $route->getParams());
    }

    public function testNormalizeHttpMethodsAndGetRoutesSorting(): void
    {
        $normalize = new ReflectionMethod(Route::class, 'normalizeHttpMethods');
        $normalize->setAccessible(true);

        $this->assertSame(['GET', 'POST'], $normalize->invoke(null, ' get , POST , get '));
        $this->assertSame(['GET', 'POST'], $normalize->invoke(null, 'GET,POST'));
        $this->assertSame(['GET', 'POST'], $normalize->invoke(null, 'GET, POST'));
        $this->assertSame(['GET', 'POST'], $normalize->invoke(null, ['GET, POST']));
        $this->assertSame(['GET', 'POST'], $normalize->invoke(null, ['GET, POST', 'get']));

        Route::addRoute('/zeta', 'ZetaController', 'index', ['post', 'get', 'GET']);
        Route::addRoute('/alpha', 'AlphaController', 'index', 'PATCH');

        $routes = Route::getRoutes();

        $this->assertSame(['/alpha [PATCH]', '/zeta [GET]', '/zeta [POST]'], array_keys($routes));
    }

    public function testGeneratePathVariantsSupportsNestedOptionalSegments(): void
    {
        $method = new ReflectionMethod(Route::class, 'generatePathVariants');
        $method->setAccessible(true);

        $variants = $method->invoke(null, '/blog[/[category]/posts]');
        sort($variants);

        $this->assertSame([
            '/blog',
            '/blog/category/posts',
            '/blog/posts',
        ], $variants);
    }

    public function testReloadMatchesExactStaticRoutesAndSingleParamFallback(): void
    {
        Route::addRoute('/news/archive', 'ArchiveController', 'index', ['GET']);
        Route::addRoute('/users/show/123', 'StaticController', 'details', ['GET']);

        $exactRoute = new Route($this->request('/news/archive', 'GET'));
        $exactRoute->reload();

        $this->assertSame('ArchiveController', $exactRoute->getController());
        $this->assertSame('index', $exactRoute->getMethod());
        $this->assertSame([], $exactRoute->getParams());

        $fallbackRoute = new Route($this->request('/users/show/123', 'GET'));
        $fallbackRoute->reload();

        $this->assertSame('StaticController', $fallbackRoute->getController());
        $this->assertSame('details', $fallbackRoute->getMethod());
        $this->assertSame(['123'], $fallbackRoute->getParams());
    }

    public function testReloadMatchesDynamicTypedParameters(): void
    {
        Route::addRoute('/products/{id:int}/price/{price:float}/available/{enabled:bool}', 'ProductController', 'show', ['GET']);

        $route = new Route($this->request('/products/42/price/99.95/available/true', 'GET'));
        $route->reload();

        $this->assertSame('ProductController', $route->getController());
        $this->assertSame('show', $route->getMethod());
        $this->assertSame([42, 99.95, true], $route->getParams());
    }

    public function testReloadMatchesDynamicRoutesWithDefaultParameters(): void
    {
        Route::addRoute('/reports/monthly/{page:int=1}/{published:bool=false}', 'ReportController', 'monthly', ['GET']);

        $defaultRoute = new Route($this->request('/reports/monthly', 'GET'));
        $defaultRoute->reload();
        $this->assertSame([1, false], $defaultRoute->getParams());

        $explicitRoute = new Route($this->request('/reports/monthly/5/true', 'GET'));
        $explicitRoute->reload();
        $this->assertSame([5, true], $explicitRoute->getParams());
    }

    public function testDynamicRouteOrderingPrefersMoreSpecificPattern(): void
    {
        Route::addRoute('/items/{type}/{id:int}', 'GenericController', 'generic', ['GET']);
        Route::addRoute('/items/static/{id:int}', 'SpecificController', 'specific', ['GET']);

        $route = new Route($this->request('/items/static/8', 'GET'));
        $route->reload();

        $this->assertSame('SpecificController', $route->getController());
        $this->assertSame('specific', $route->getMethod());
        $this->assertSame([8], $route->getParams());
    }

    public function testDynamicRouteCanInjectNumericLiteralSegmentsWhenControllerExpectsThem(): void
    {
        $this->registerAliasedController(RouteCoverageReportsControllerStub::class, 'App\\Controller\\ReportsController');
        Route::addRoute('/reports/2024/{month:int}/{day:int}', 'ReportsController', 'show', ['GET']);

        $route = new Route($this->request('/reports/2024/5/20', 'GET'));
        $route->reload();

        $this->assertSame([2024, 5, 20], $route->getParams());
    }

    public function testConvertValueToTypeAndTypePatterns(): void
    {
        $convert = new ReflectionMethod(Route::class, 'convertValueToType');
        $convert->setAccessible(true);
        $pattern = new ReflectionMethod(Route::class, 'getTypePattern');
        $pattern->setAccessible(true);

        $route = new Route($this->request('/', 'GET'));

        $this->assertSame('value', $convert->invoke($route, 'value', null));
        $this->assertSame(123, $convert->invoke($route, '123', 'int'));
        $this->assertSame(12.5, $convert->invoke($route, '12.5', 'float'));
        $this->assertTrue($convert->invoke($route, 'true', 'bool'));
        $this->assertFalse($convert->invoke($route, '0', 'bool'));
        $this->assertSame('[0-9]+', $pattern->invoke($route, 'int'));
        $this->assertSame('[0-9]+(?:\\.[0-9]+)?', $pattern->invoke($route, 'float'));
        $this->assertSame('(?:true|false|1|0)', $pattern->invoke($route, 'bool'));
        $this->assertSame('[^/]+', $pattern->invoke($route, null));
    }

    public function testValidateUsesMatchedRouteOrReturnsTrueWhenNoRouteExists(): void
    {
        Route::addRoute('/api/ping', 'ApiController', 'ping', ['GET']);

        $matched = new Route($this->request('/api/ping', 'GET'));
        $matched->reload();
        $this->assertTrue($matched->validate());

        $notAllowed = new Route($this->request('/api/ping', 'POST'));
        $notAllowed->reload();
        $this->assertFalse($notAllowed->validate());

        $unknown = new Route($this->request('/nowhere', 'DELETE'));
        $this->assertTrue($unknown->validate());
    }

    public function testFilterRoutesByMethodFallsBackWhenNothingMatches(): void
    {
        $method = new ReflectionMethod(Route::class, 'filterRoutesByMethod');
        $method->setAccessible(true);
        $routes = [
            ['httpMethod' => 'GET', 'controller' => 'First'],
            ['httpMethod' => 'POST', 'controller' => 'Second'],
        ];

        $matched = $method->invoke(null, $routes, 'POST');
        $fallback = $method->invoke(null, $routes, 'DELETE');

        $this->assertSame([['httpMethod' => 'POST', 'controller' => 'Second']], $matched);
        $this->assertSame($routes, $fallback);
    }

    public function testReloadThrowsNotFoundWhenNothingMatches(): void
    {
        $route = new Route($this->request('/missing/path', 'GET'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Route /missing/path not found');
        $route->reload();
    }

    public function testRegisterRoutesScansControllerAttributes(): void
    {
        $this->registerAliasedController(RouteCoverageAttributeControllerStub::class, 'App\\Controller\\AttributeController');
        $this->registerAliasedController(RouteCoverageNestedControllerStub::class, 'App\\Controller\\Admin\\DashboardController');

        $this->touchControllerFile('AttributeController.php');
        $this->touchControllerFile('DashboardController.php', 'Admin');

        Route::registerRoutes($this->projectPath . '/App/Controller', 'App\\Controller');
        $routes = Route::getRoutes();

        $this->assertArrayHasKey('/articles [GET]', $routes);
        $this->assertArrayHasKey('/articles [POST]', $routes);
        $this->assertArrayHasKey('/dashboard [PATCH]', $routes);
        $this->assertSame('AttributeController', $routes['/articles [GET]']['controller']);
        $this->assertSame('store', $routes['/articles [POST]']['method']);
        $this->assertSame('Admin\\DashboardController', $routes['/dashboard [PATCH]']['controller']);
    }

    public function testRegisterRoutesScansControllerAttributesWithCommaSeparatedMethods(): void
    {
        $this->registerAliasedController(RouteCoverageMultiMethodControllerStub::class, 'App\\Controller\\MultiController');
        $this->touchControllerFile('MultiController.php');

        Route::registerRoutes($this->projectPath . '/App/Controller', 'App\\Controller');
        $routes = Route::getRoutes();

        $this->assertArrayHasKey('/multi [GET]', $routes);
        $this->assertArrayHasKey('/multi [POST]', $routes);
        $this->assertSame('MultiController', $routes['/multi [GET]']['controller']);
        $this->assertSame('handle', $routes['/multi [POST]']['method']);
    }

    public function testRegisterRoutesUsesFreshCacheWhenAvailable(): void
    {
        $_ENV['CACHE_ROUTE'] = true;

        (new Cache())->set(Route::$cacheKey, [
            'routes' => [
                '/cached' => [
                    'GET' => [
                        'path' => '/cached',
                        'controller' => 'CachedController',
                        'method' => 'index',
                        'httpMethod' => 'GET',
                    ],
                ],
            ],
            'timestamp' => time() + 3600,
        ], 3600);

        Route::registerRoutes($this->projectPath . '/App/Controller', 'App\\Controller');

        $this->assertSame([
            '/cached [GET]' => [
                'path' => '/cached',
                'controller' => 'CachedController',
                'method' => 'index',
                'httpMethod' => 'GET',
            ],
        ], Route::getRoutes());
    }

    public function testRegisterRoutesIgnoresStaleCacheAndScansRealControllers(): void
    {
        $_ENV['CACHE_ROUTE'] = true;
        $this->registerAliasedController(RouteCoverageCachedControllerStub::class, 'App\\Controller\\CachedController');
        $this->touchControllerFile('CachedController.php');

        (new Cache())->set(Route::$cacheKey, [
            'routes' => [
                '/stale' => [
                    'GET' => [
                        'path' => '/stale',
                        'controller' => 'StaleController',
                        'method' => 'old',
                        'httpMethod' => 'GET',
                    ],
                ],
            ],
            'timestamp' => 1,
        ], 3600);

        clearstatcache();
        Route::registerRoutes($this->projectPath . '/App/Controller', 'App\\Controller');
        $routes = Route::getRoutes();

        $this->assertArrayNotHasKey('/stale [GET]', $routes);
        $this->assertArrayHasKey('/cached/fresh [GET]', $routes);
        $this->assertSame('CachedController', $routes['/cached/fresh [GET]']['controller']);
    }

    private function request(string $uri, string $method): RequestInterface
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn($method);

        return $request;
    }

    private function resetRoutes(): void
    {
        $reflection = new ReflectionClass(Route::class);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    private function registerAliasedController(string $sourceClass, string $alias): void
    {
        if (!class_exists($alias, false)) {
            class_alias($sourceClass, $alias);
        }
    }

    private function touchControllerFile(string $fileName, string $subDirectory = ''): void
    {
        $directory = $this->projectPath . '/App/Controller';

        if ($subDirectory !== '') {
            $directory .= '/' . $subDirectory;
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }

        file_put_contents($directory . '/' . $fileName, "<?php\n");
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}

class RouteCoverageReportsControllerStub
{
    public function show(int $year, int $month, int $day): void
    {
    }
}

class RouteCoverageAttributeControllerStub
{
    #[HttpRoute('/articles', 'GET')]
    public function index(): void
    {
    }

    #[HttpRoute('/articles', 'POST')]
    public function store(): void
    {
    }
}

class RouteCoverageNestedControllerStub
{
    #[HttpRoute('/dashboard', 'PATCH')]
    public function update(): void
    {
    }
}

class RouteCoverageMultiMethodControllerStub
{
    #[HttpRoute('/multi', 'GET, POST')]
    public function handle(): void
    {
    }
}

class RouteCoverageCachedControllerStub
{
    #[HttpRoute('/cached/fresh', 'GET')]
    public function index(): void
    {
    }
}
