<?php

use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Routes\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['DEFAULT_CONTROLLER'] = 'DefaultController';
        $_ENV['DEFAULT_METHOD'] = 'defaultMethod';
        $reflection = new ReflectionClass(Route::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(null, []);
    }

    public function testAddRoute(): void
    {
        Route::addRoute('/test/route', 'TestController', 'testMethod');

        $expectedGet = [
            'path' => '/test/route',
            'controller' => 'TestController',
            'method' => 'testMethod',
            'httpMethod' => 'GET'
        ];

        $expectedPost = [
            'path' => '/test/route',
            'controller' => 'TestController',
            'method' => 'testMethod',
            'httpMethod' => 'POST'
        ];

        $routes = Route::getRoutes();

        $this->assertArrayHasKey('/test/route [GET]', $routes);
        $this->assertArrayHasKey('/test/route [POST]', $routes);
        $this->assertEquals($expectedGet, $routes['/test/route [GET]']);
        $this->assertEquals($expectedPost, $routes['/test/route [POST]']);
    }

    public function testAddRouteWithCustomHttpMethods(): void
    {
        Route::addRoute('/api/data', 'ApiController', 'getData', ['GET']);

        $expected = [
            'path' => '/api/data',
            'controller' => 'ApiController',
            'method' => 'getData',
            'httpMethod' => 'GET'
        ];

        $routes = Route::getRoutes();

        $this->assertArrayHasKey('/api/data [GET]', $routes);
        $this->assertEquals($expected, $routes['/api/data [GET]']);
    }

    public function testAddRouteWithDefaults(): void
    {
        Route::addRoute('/default/route');

        $expectedGet = [
            'path' => '/default/route',
            'controller' => $_ENV['DEFAULT_CONTROLLER'],
            'method' => $_ENV['DEFAULT_METHOD'],
            'httpMethod' => 'GET'
        ];

        $expectedPost = [
            'path' => '/default/route',
            'controller' => $_ENV['DEFAULT_CONTROLLER'],
            'method' => $_ENV['DEFAULT_METHOD'],
            'httpMethod' => 'POST'
        ];

        $routes = Route::getRoutes();

        $this->assertEquals($expectedGet, $routes['/default/route [GET]']);
        $this->assertEquals($expectedPost, $routes['/default/route [POST]']);
    }

    public function testReloadWithDynamicRoute(): void
    {
        Route::addRoute('/users/{id}', 'UserController', 'viewUser');

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/users/123');
        $requestMock->method('getMethod')->willReturn('GET');

        $route = new Route($requestMock);
        $route->reload();

        $this->assertEquals('UserController', $route->getController());
        $this->assertEquals('viewUser', $route->getMethod());
        $this->assertContains('123', $route->getParams());
    }

    public function testReloadThrowsExceptionForNonExistentRoute(): void
    {
        $this->expectException(NotFoundException::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/non/existent/route');
        $requestMock->method('getMethod')->willReturn('GET');

        $route = new Route($requestMock);
        $route->reload();
    }

    public function testValidateWithAllowedMethod(): void
    {
        Route::addRoute('/check/method', 'CheckController', 'checkMethod', ['GET']);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/check/method');
        $requestMock->method('getMethod')->willReturn('GET');

        $route = new Route($requestMock);
        $route->reload();

        $this->assertTrue($route->validate());
    }
}
