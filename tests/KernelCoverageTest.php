<?php

namespace App\Controller {

    use NimblePHP\Framework\Abstracts\AbstractController;
    use NimblePHP\Framework\Attributes\Http\Action;
    use NimblePHP\Framework\Request;

    class KernelDispatchController extends AbstractController
    {
        public static array $calls = [];

        public function afterConstruct(): void
        {
            parent::afterConstruct();
            self::$calls[] = 'afterConstruct';
        }

        public function index(string $id): void
        {
            self::$calls[] = [
                'method' => 'index',
                'id' => $id,
                'name' => $this->name,
                'action' => $this->action,
                'request' => $this->request instanceof Request,
            ];
        }

        #[Action('disabled')]
        public function disabled(): void
        {
            self::$calls[] = 'disabled';
        }

        #[Action('ajax')]
        public function ajaxOnly(): void
        {
            self::$calls[] = 'ajaxOnly';
        }
    }
}

namespace {

use App\Controller\KernelDispatchController;
use NimblePHP\Framework\Container\ServiceContainer;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\ResponseInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestRuntimeStubs.php';

class KernelCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_SERVER = [
            'SCRIPT_FILENAME' => getcwd() . '/public/index.php',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/kernel',
        ];

        KernelDispatchController::$calls = [];
        Kernel::$middlewareManager = new MiddlewareManager();
        Kernel::$serviceContainer = new ServiceContainer();
        Kernel::$serviceContainer->clear();
        Kernel::$projectPath = getcwd();
    }

    public function testRegisterServicesRegistersCoreServicesAndSingletons(): void
    {
        $route = new FakeKernelRoute();
        $request = new Request();
        $response = new Response();
        $kernel = $this->createKernel($route, $request, $response);

        $this->invokeKernelMethod($kernel, 'registerServices');

        $this->assertSame($route, Kernel::$serviceContainer->get('kernel.router'));
        $this->assertSame($request, Kernel::$serviceContainer->get('kernel.request'));
        $this->assertSame($response, Kernel::$serviceContainer->get('kernel.response'));
        $this->assertInstanceOf(\NimblePHP\Framework\Session::class, Kernel::$serviceContainer->get('kernel.session'));
        $this->assertInstanceOf(\NimblePHP\Framework\Cache::class, Kernel::$serviceContainer->get('kernel.cache'));
        $this->assertInstanceOf(\NimblePHP\Framework\Cookie::class, Kernel::$serviceContainer->get('kernel.cookie'));
        $this->assertInstanceOf(\NimblePHP\Framework\View::class, Kernel::$serviceContainer->get('view'));
        $this->assertInstanceOf(\NimblePHP\Framework\Translation\Translation::class, Kernel::$serviceContainer->get('translation'));
    }

    public function testLoadControllerDispatchesAndRunsMiddlewareHooks(): void
    {
        $middleware = new KernelCoverageMiddleware();
        Kernel::$middlewareManager->add($middleware);

        $route = new FakeKernelRoute(
            controller: 'KernelDispatchController',
            method: 'index',
            params: ['42'],
            valid: true
        );
        $kernel = $this->createKernel($route);

        $this->invokeKernelMethod($kernel, 'loadController');

        $this->assertTrue($route->reloaded);
        $this->assertSame(['beforeController', 'afterAttributesController', 'afterControllerDispatch', 'afterController'], $middleware->events);
        $this->assertSame('afterConstruct', KernelDispatchController::$calls[0]);
        $this->assertSame([
            'method' => 'index',
            'id' => '42',
            'name' => 'KernelDispatchController',
            'action' => 'index',
            'request' => true,
        ], KernelDispatchController::$calls[1]);
    }

    public function testLoadControllerThrowsWhenRouteValidationFails(): void
    {
        $kernel = $this->createKernel(new FakeKernelRoute(valid: false));

        $this->expectException(\NimblePHP\Framework\Exception\NotFoundException::class);
        $this->invokeKernelMethod($kernel, 'loadController');
    }

    public function testLoadControllerThrowsWhenControllerDoesNotExist(): void
    {
        $kernel = $this->createKernel(new FakeKernelRoute(controller: 'MissingController', valid: true));

        $this->expectException(\NimblePHP\Framework\Exception\NotFoundException::class);
        $this->expectExceptionMessage('Controller MissingController not found');
        $this->invokeKernelMethod($kernel, 'loadController');
    }

    public function testLoadControllerThrowsWhenMethodDoesNotExist(): void
    {
        $kernel = $this->createKernel(new FakeKernelRoute(
            controller: 'KernelDispatchController',
            method: 'missingMethod',
            valid: true
        ));

        $this->expectException(\NimblePHP\Framework\Exception\NotFoundException::class);
        $this->expectExceptionMessage('Method missingMethod does not exist');
        $this->invokeKernelMethod($kernel, 'loadController');
    }

    public function testLoadControllerHonorsDisabledActionAttribute(): void
    {
        $kernel = $this->createKernel(new FakeKernelRoute(
            controller: 'KernelDispatchController',
            method: 'disabled',
            valid: true
        ));

        $this->expectException(\NimblePHP\Framework\Exception\NotFoundException::class);
        $this->expectExceptionMessage('Method disabled is disabled');
        $this->invokeKernelMethod($kernel, 'loadController');
    }

    public function testLoadControllerRejectsAjaxActionForNonAjaxRequest(): void
    {
        $kernel = $this->createKernel(new FakeKernelRoute(
            controller: 'KernelDispatchController',
            method: 'ajaxOnly',
            valid: true
        ));

        $this->expectException(\NimblePHP\Framework\Exception\NotFoundException::class);
        $this->expectExceptionMessage('Method ajaxOnly is allowed only for AJAX requests');
        $this->invokeKernelMethod($kernel, 'loadController');
    }

    public function testLoadControllerAllowsAjaxActionForAjaxRequest(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $kernel = $this->createKernel(new FakeKernelRoute(
            controller: 'KernelDispatchController',
            method: 'ajaxOnly',
            valid: true
        ));

        $this->invokeKernelMethod($kernel, 'loadController');

        $this->assertContains('ajaxOnly', KernelDispatchController::$calls);
    }

    private function createKernel(
        ?RouteInterface $route = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null
    ): Kernel {
        $reflection = new ReflectionClass(Kernel::class);
        /** @var Kernel $kernel */
        $kernel = $reflection->newInstanceWithoutConstructor();

        foreach ([
            'router' => $route ?? new FakeKernelRoute(),
            'request' => $request ?? new Request(),
            'response' => $response ?? new Response(),
        ] as $property => $value) {
            $reflectionProperty = $reflection->getProperty($property);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($kernel, $value);
        }

        return $kernel;
    }

    private function invokeKernelMethod(Kernel $kernel, string $method): mixed
    {
        $reflectionMethod = new ReflectionMethod(Kernel::class, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke($kernel);
    }
}

class FakeKernelRoute implements RouteInterface
{
    public bool $reloaded = false;

    public function __construct(
        private string $controller = 'KernelDispatchController',
        private string $method = 'index',
        private array $params = ['42'],
        private bool $valid = true
    ) {
    }

    public static function addRoute(string $name, ?string $controller = null, ?string $method = null): void
    {
    }

    public function reload(): void
    {
        $this->reloaded = true;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setController(?string $controller): void
    {
        $this->controller = (string)$controller;
    }

    public function setMethod(?string $method): void
    {
        $this->method = (string)$method;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function validate(): bool
    {
        return $this->valid;
    }

    public static function registerRoutes(string $controllerPath, string $namespace): void
    {
    }
}

class KernelCoverageMiddleware
{
    public array $events = [];

    public function beforeController(array &$context): void
    {
        $this->events[] = 'beforeController';
    }

    public function afterAttributesController(ReflectionMethod $reflection, object $controller): void
    {
        $this->events[] = 'afterAttributesController';
    }

    public function afterControllerDispatch(object $controller, string $controllerName, string $methodName, array $params): void
    {
        $this->events[] = 'afterControllerDispatch';
    }

    public function afterController(string $controllerName, string $methodName, array $params): void
    {
        $this->events[] = 'afterController';
    }

    public function afterConstructModel(object &$model): void
    {
        $this->events[] = 'afterConstructModelHook';
    }
}

}
