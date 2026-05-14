<?php

use NimblePHP\Framework\Cache;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;
use PHPUnit\Framework\TestCase;

class RoutePerformanceTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $_ENV['DEFAULT_CONTROLLER'] = 'index';
        $_ENV['DEFAULT_METHOD'] = 'index';
        $_ENV['CACHE_ROUTE'] = false;

        $this->projectPath = sys_get_temp_dir() . '/nimble_route_perf_' . uniqid('', true);
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

    public function testStaticRouteLookupStaysFastWithLargeStaticTable(): void
    {
        for ($i = 0; $i < 2000; $i++) {
            Route::addRoute('/static/' . $i, 'StaticController', 'show', ['GET']);
        }

        $this->assertLessThan(
            2.0,
            $this->averageReloadMilliseconds('/static/1999', 200),
            'Expected static route reload average to stay below 2ms.'
        );
    }

    public function testDynamicRouteLookupStaysReasonableWithManyTypedPatterns(): void
    {
        for ($i = 0; $i < 200; $i++) {
            Route::addRoute('/articles/' . $i . '/{id:int}/{slug}', 'ArticleController', 'show', ['GET']);
        }

        $this->assertLessThan(
            25.0,
            $this->averageReloadMilliseconds('/articles/199/123/example-slug', 20),
            'Expected dynamic route reload average to stay below 25ms.'
        );
    }

    public function testCachedRouteRegistrationIsFasterThanColdScan(): void
    {
        $_ENV['CACHE_ROUTE'] = true;

        for ($i = 0; $i < 250; $i++) {
            $class = 'PerfController' . $i;
            $route = '/perf-' . $i;

            file_put_contents($this->projectPath . '/App/Controller/' . $class . '.php', <<<PHP
<?php

namespace App\Controller;

use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Attributes\Http\Route;

class {$class} extends AbstractController
{
    #[Route('{$route}', 'GET')]
    public function index(): void
    {
    }
}
PHP);
        }

        $coldMilliseconds = $this->measureMilliseconds(function (): void {
            Route::registerRoutes($this->projectPath . '/App/Controller', 'App\\Controller');
        });

        $this->resetRoutes();

        $warmMilliseconds = $this->measureMilliseconds(function (): void {
            Route::registerRoutes($this->projectPath . '/App/Controller', 'App\\Controller');
        });

        $this->assertGreaterThan(0.0, $coldMilliseconds);
        $this->assertLessThan($coldMilliseconds, $warmMilliseconds, 'Expected cached route registration to be faster than the cold scan.');
        $this->assertLessThan(10.0, $warmMilliseconds, 'Expected cached route registration to stay below 10ms.');
    }

    private function averageReloadMilliseconds(string $uri, int $iterations): float
    {
        $route = new Route($this->request($uri, 'GET'));
        $route->reload();

        $elapsed = $this->measureMilliseconds(function () use ($uri, $iterations): void {
            for ($i = 0; $i < $iterations; $i++) {
                $route = new Route($this->request($uri, 'GET'));
                $route->reload();
            }
        });

        return $elapsed / $iterations;
    }

    private function measureMilliseconds(callable $callback): float
    {
        $start = hrtime(true);
        $callback();

        return (hrtime(true) - $start) / 1_000_000;
    }

    private function request(string $uri, string $method): RequestInterface
    {
        return new class($uri, $method) implements RequestInterface {
            public function __construct(
                private readonly string $uri,
                private readonly string $method
            ) {
            }

            public function getQuery(string $key, mixed $default = null, bool $protect = true): mixed
            {
                return $default;
            }

            public function getPost(string $key, mixed $default = null, bool $protect = true): mixed
            {
                return $default;
            }

            public function getCookie(string $key, mixed $default = null, bool $protect = true): mixed
            {
                return $default;
            }

            public function getFile(string $key): mixed
            {
                return null;
            }

            public function getHeader(string $key): mixed
            {
                return null;
            }

            public function getMethod(): string
            {
                return $this->method;
            }

            public function getUri(): string
            {
                return $this->uri;
            }

            public function getServer(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function getBody(): string
            {
                return '';
            }

            public function getAllQuery(bool $protect = true): array
            {
                return [];
            }

            public function getAllPost(bool $protect = true): array
            {
                return [];
            }

            public function isAjax(): bool
            {
                return false;
            }

            public function issetQuery(string $key): bool
            {
                return false;
            }

            public function issetPost(string $key): bool
            {
                return false;
            }

            public function issetCookie(string $key): bool
            {
                return false;
            }

            public function hasPost(): bool
            {
                return false;
            }

            public function getBrowserLanguages(): array
            {
                return [];
            }
        };
    }

    private function resetRoutes(): void
    {
        $reflection = new ReflectionClass(Route::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(null, []);
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
