<?php

use NimblePHP\framework\Route;
use PHPUnit\Framework\TestCase;
use NimblePHP\framework\Interfaces\RequestInterface;

class RouteTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['DEFAULT_CONTROLLER'] = 'DefaultController';
        $_ENV['DEFAULT_METHOD'] = 'defaultMethod';
    }

    public function testAddRoute()
    {
        Route::addRoute('test/route', 'TestController', 'testMethod');

        $expected = [
            'controller' => 'TestController',
            'method' => 'testMethod'
        ];

        $this->assertArrayHasKey('test/route', Route::getRoutes());
        $this->assertEquals($expected, Route::getRoutes()['test/route']);
    }

    public function testAddRouteWithDefaults()
    {
        Route::addRoute('default/route');
        $expected = [
            'controller' => $_ENV['DEFAULT_CONTROLLER'],
            'method' => $_ENV['DEFAULT_METHOD']
        ];
        $this->assertEquals($expected, Route::getRoutes()['default/route']);
    }

    public function testConstructorSetsCorrectValues()
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/controller/method/param1/param2');

        $route = new Route($requestMock);

        $this->assertEquals('controller', $route->getController());
        $this->assertEquals('method', $route->getMethod());
        $this->assertEquals(['param1', 'param2'], $route->getParams());
    }

    public function testConstructorHandlesRootUri()
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/');

        $route = new Route($requestMock);

        $this->assertEquals($_ENV['DEFAULT_CONTROLLER'], $route->getController());
        $this->assertEquals($_ENV['DEFAULT_METHOD'], $route->getMethod());
        $this->assertEquals([], $route->getParams());
    }

    public function testReloadUpdatesControllerAndMethod()
    {
        Route::addRoute('test/method', 'AnotherController', 'anotherMethod');

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/test/method');

        $route = new Route($requestMock);

        $route->reload();

        $this->assertEquals('AnotherController', $route->getController());
        $this->assertEquals('anotherMethod', $route->getMethod());
    }


    public function testSetAndGetMethods()
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $route = new Route($requestMock);

        $route->setController('CustomController');
        $this->assertEquals('CustomController', $route->getController());

        $route->setMethod('customMethod');
        $this->assertEquals('customMethod', $route->getMethod());

        $route->setParams(['param1', 'param2']);
        $this->assertEquals(['param1', 'param2'], $route->getParams());
    }
}
