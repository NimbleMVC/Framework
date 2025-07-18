<?php

use NimblePHP\Framework\Middleware\MiddlewareManager;
use PHPUnit\Framework\TestCase;

class MiddlewareManagerTest extends TestCase
{
    private MiddlewareManager $middlewareManager;

    protected function setUp(): void
    {
        $this->middlewareManager = new MiddlewareManager();
    }

    public function testAddMiddleware()
    {
        $middleware = $this->createMock(\stdClass::class);
        $this->middlewareManager->add($middleware);

        $reflection = new ReflectionClass($this->middlewareManager);
        $property = $reflection->getProperty('stack');
        $property->setAccessible(true);

        $stack = $property->getValue($this->middlewareManager);
        $this->assertContains($middleware, $stack[0]);
    }

    public function testAddMiddlewareWithPriority()
    {
        $middleware1 = $this->createMock(\stdClass::class);
        $middleware2 = $this->createMock(\stdClass::class);

        $this->middlewareManager->add($middleware1, 1);
        $this->middlewareManager->add($middleware2, 2);

        $reflection = new ReflectionClass($this->middlewareManager);
        $property = $reflection->getProperty('stack');
        $property->setAccessible(true);

        $stack = $property->getValue($this->middlewareManager);
        $this->assertContains($middleware1, $stack[1]);
        $this->assertContains($middleware2, $stack[2]);
    }

    public function testHandleWithObjectMiddleware()
    {
        $middleware = new TestMiddleware();
        $this->middlewareManager->add($middleware);

        $finalHandler = function($request) {
            return 'final';
        };

        $result = $this->middlewareManager->handle('request', $finalHandler);
        $this->assertEquals('response', $result);
    }

    public function testHandleWithCallableMiddleware()
    {
        $middleware = function($request, $next) {
            return 'response';
        };

        $this->middlewareManager->add($middleware);

        $finalHandler = function($request) {
            return 'final';
        };

        $result = $this->middlewareManager->handle('request', $finalHandler);
        $this->assertEquals('response', $result);
    }

    public function testHandleWithStringMiddleware()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid middleware');

        $this->middlewareManager->add('NonExistentClass');

        $finalHandler = function($request) {
            return 'final';
        };

        $this->middlewareManager->handle('request', $finalHandler);
    }

    public function testRunHook()
    {
        $middleware = new TestHookMiddleware();
        $this->middlewareManager->add($middleware);

        $this->middlewareManager->runHook('testHook', ['arg1', 'arg2']);
        $this->assertTrue($middleware->hookCalled);
    }

    public function testRunHookWithReference()
    {
        $data = ['original' => 'value'];
        $middleware = new TestHookMiddleware();
        $this->middlewareManager->add($middleware);

        $this->middlewareManager->runHookWithReference('testHook', $data);
        $this->assertTrue($data['modified']);
    }

    public function testGetSortedStack()
    {
        $middleware1 = $this->createMock(\stdClass::class);
        $middleware2 = $this->createMock(\stdClass::class);

        $this->middlewareManager->add($middleware1, 1);
        $this->middlewareManager->add($middleware2, 2);

        $reflection = new ReflectionClass($this->middlewareManager);
        $method = $reflection->getMethod('getSortedStack');
        $method->setAccessible(true);

        $sortedStack = $method->invoke($this->middlewareManager);
        $this->assertCount(2, $sortedStack);
    }

    public function testEmptyStack()
    {
        $reflection = new ReflectionClass($this->middlewareManager);
        $method = $reflection->getMethod('getSortedStack');
        $method->setAccessible(true);

        $sortedStack = $method->invoke($this->middlewareManager);
        $this->assertEmpty($sortedStack);
    }

    public function testAfterBootstrapHook()
    {
        $middleware = new TestBootstrapMiddleware();
        $this->middlewareManager->add($middleware);

        $finalHandler = function($request) {
            return 'response';
        };

        $this->middlewareManager->handle('request', $finalHandler);
        $this->assertTrue($middleware->bootstrapCalled);
    }

    public function testMiddlewarePriority()
    {
        $executionOrder = [];

        $middleware1 = function($request, $next) use (&$executionOrder) {
            $executionOrder[] = 1;
            return $next($request);
        };

        $middleware2 = function($request, $next) use (&$executionOrder) {
            $executionOrder[] = 2;
            return $next($request);
        };

        $this->middlewareManager->add($middleware1, 1);
        $this->middlewareManager->add($middleware2, 2);

        $finalHandler = function($request) use (&$executionOrder) {
            $executionOrder[] = 'final';
            return 'response';
        };

        $this->middlewareManager->handle('request', $finalHandler);

        $this->assertEquals([2, 1, 'final'], $executionOrder);
    }

    public function testMiddlewareChain()
    {
        $middleware1 = function($request, $next) {
            return 'middleware1: ' . $next($request);
        };

        $middleware2 = function($request, $next) {
            return 'middleware2: ' . $next($request);
        };

        $this->middlewareManager->add($middleware1);
        $this->middlewareManager->add($middleware2);

        $finalHandler = function($request) {
            return 'final';
        };

        $result = $this->middlewareManager->handle('request', $finalHandler);
        $this->assertEquals('middleware1: middleware2: final', $result);
    }

    public function testHookWithNonExistentMethod()
    {
        $middleware = $this->createMock(\stdClass::class);
        $this->middlewareManager->add($middleware);

        $this->middlewareManager->runHook('nonExistentMethod', []);
        $this->assertTrue(true);
    }

    public function testHookWithReferenceNonExistentMethod()
    {
        $middleware = $this->createMock(\stdClass::class);
        $this->middlewareManager->add($middleware);

        $data = ['test' => 'value'];
        $this->middlewareManager->runHookWithReference('nonExistentMethod', $data);
        $this->assertEquals(['test' => 'value'], $data);
    }

    public function testMultipleMiddlewaresSamePriority()
    {
        $middleware1 = $this->createMock(\stdClass::class);
        $middleware2 = $this->createMock(\stdClass::class);

        $this->middlewareManager->add($middleware1, 1);
        $this->middlewareManager->add($middleware2, 1);

        $reflection = new ReflectionClass($this->middlewareManager);
        $property = $reflection->getProperty('stack');
        $property->setAccessible(true);

        $stack = $property->getValue($this->middlewareManager);
        $this->assertCount(2, $stack[1]);
        $this->assertContains($middleware1, $stack[1]);
        $this->assertContains($middleware2, $stack[1]);
    }
}

class TestMiddleware
{
    public function handle($request, $next)
    {
        return 'response';
    }
}

class TestHookMiddleware
{
    public bool $hookCalled = false;

    public function testHook(&$context)
    {
        $this->hookCalled = true;
        if (is_array($context)) {
            $context['modified'] = true;
        }
    }
}

class TestBootstrapMiddleware
{
    public bool $bootstrapCalled = false;

    public function handle($request, $next)
    {
        return $next($request);
    }

    public function afterBootstrap()
    {
        $this->bootstrapCalled = true;
    }
}