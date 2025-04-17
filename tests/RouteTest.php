<?php

use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Routes\Route;
use NimblePHP\Framework\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['DEFAULT_CONTROLLER'] = 'DefaultController';
        $_ENV['DEFAULT_METHOD'] = 'defaultMethod';
        Route::getRoutes() !== [] ? Route::$routes = [] : null;
    }

    public function testAddRoute(): void
    {
        Route::addRoute('/test/route', 'TestController', 'testMethod');

        $expected = [
            'path' => '/test/route',
            'controller' => 'TestController',
            'method' => 'testMethod',
            'httpMethod' => 'GET,POST'
        ];

        $this->assertArrayHasKey('/test/route', Route::getRoutes());
        $this->assertEquals($expected, Route::getRoutes()['/test/route']);
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

        $this->assertArrayHasKey('/api/data', Route::getRoutes());
        $this->assertEquals($expected, Route::getRoutes()['/api/data']);
    }

    public function testAddRouteWithDefaults(): void
    {
        Route::addRoute('/default/route');

        $expected = [
            'path' => '/default/route',
            'controller' => $_ENV['DEFAULT_CONTROLLER'],
            'method' => $_ENV['DEFAULT_METHOD'],
            'httpMethod' => 'GET,POST'
        ];

        $this->assertEquals($expected, Route::getRoutes()['/default/route']);
    }

    public function testReloadWithDynamicRoute(): void
    {
        Route::addRoute('/users/{id}', 'UserController', 'viewUser');

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/users/123');

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