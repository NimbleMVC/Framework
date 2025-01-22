<?php

use Nimblephp\framework\Route;
use PHPUnit\Framework\TestCase;
use Nimblephp\framework\Interfaces\RequestInterface;

class RouteTest extends TestCase
{
    protected function setUp(): void
    {
        // Przygotowanie domyślnych wartości środowiskowych
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
        // Dodanie trasy bez podawania kontrolera i metody
        Route::addRoute('default/route');

        // Oczekiwany rezultat
        $expected = [
            'controller' => $_ENV['DEFAULT_CONTROLLER'],
            'method' => $_ENV['DEFAULT_METHOD']
        ];

        // Sprawdzenie
        $this->assertEquals($expected, Route::getRoutes()['default/route']);
    }

    public function testConstructorSetsCorrectValues()
    {
        // Mockowanie RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/controller/method/param1/param2');

        // Tworzenie obiektu Route
        $route = new Route($requestMock);

        // Sprawdzenie wartości
        $this->assertEquals('controller', $route->getController());
        $this->assertEquals('method', $route->getMethod());
        $this->assertEquals(['param1', 'param2'], $route->getParams());
    }

    public function testConstructorHandlesRootUri()
    {
        // Mockowanie RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/');

        // Tworzenie obiektu Route
        $route = new Route($requestMock);

        // Sprawdzenie wartości
        $this->assertEquals($_ENV['DEFAULT_CONTROLLER'], $route->getController());
        $this->assertEquals($_ENV['DEFAULT_METHOD'], $route->getMethod());
        $this->assertEquals([], $route->getParams());
    }

    public function testReloadUpdatesControllerAndMethod()
    {
        // Dodanie tras
        Route::addRoute('test/method', 'AnotherController', 'anotherMethod');

        // Mockowanie RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/test/method');

        // Tworzenie obiektu Route
        $route = new Route($requestMock);

        // Wywołanie reload
        $route->reload();

        // Sprawdzenie po reload
        $this->assertEquals('AnotherController', $route->getController());
        $this->assertEquals('anotherMethod', $route->getMethod());
    }


    public function testSetAndGetMethods()
    {
        // Mockowanie RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $route = new Route($requestMock);

        // Ustawianie kontrolera
        $route->setController('CustomController');
        $this->assertEquals('CustomController', $route->getController());

        // Ustawianie metody
        $route->setMethod('customMethod');
        $this->assertEquals('customMethod', $route->getMethod());

        // Ustawianie parametrów
        $route->setParams(['param1', 'param2']);
        $this->assertEquals(['param1', 'param2'], $route->getParams());
    }
}
