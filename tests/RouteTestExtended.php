<?php

use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Routes\Route;
use PHPUnit\Framework\TestCase;

class RouteTestExtended extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['DEFAULT_CONTROLLER'] = 'index';
        $_ENV['DEFAULT_METHOD'] = 'index';

        // Reset the routes array before each test
        $reflectionClass = new ReflectionClass(Route::class);
        $routesProperty = $reflectionClass->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(null, []);
    }

    public function testAddRouteWithSpecificHttpMethods()
    {
        Route::addRoute('/test/route', 'TestController', 'testMethod', ['GET', 'POST']);

        $routes = Route::getRoutes();
        $this->assertArrayHasKey('/test/route', $routes);
        $this->assertEquals('GET,POST', $routes['/test/route']['httpMethod']);

        // Test with string parameter
        Route::addRoute('/api/data', 'ApiController', 'getData', 'PUT');

        $routes = Route::getRoutes();
        $this->assertArrayHasKey('/api/data', $routes);
        $this->assertEquals('PUT', $routes['/api/data']['httpMethod']);
    }

    public function testRouteWithOptionalSegments()
    {
        Route::addRoute('/users[/profile]', 'UserController', 'profile');

        $routes = Route::getRoutes();
        $this->assertArrayHasKey('/users', $routes);
        $this->assertArrayHasKey('/users/profile', $routes);

        $this->assertEquals('UserController', $routes['/users']['controller']);
        $this->assertEquals('profile', $routes['/users']['method']);

        $this->assertEquals('UserController', $routes['/users/profile']['controller']);
        $this->assertEquals('profile', $routes['/users/profile']['method']);
    }

    public function testRouteWithNestedOptionalSegments()
    {
        Route::addRoute('/blog[/[category]/posts]', 'BlogController', 'index');

        $routes = Route::getRoutes();

        // Should generate routes for:
        // /blog
        // /blog/posts
        // /blog/category/posts
        $this->assertArrayHasKey('/blog', $routes);
        $this->assertArrayHasKey('/blog/posts', $routes);
        $this->assertArrayHasKey('/blog/category/posts', $routes);

        $this->assertEquals('BlogController', $routes['/blog']['controller']);
        $this->assertEquals('index', $routes['/blog']['method']);
    }

    public function testRouteWithDynamicParameters()
    {
        // Create a request mock for testing
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/users/123');
        $requestMock->method('getMethod')->willReturn('GET');

        // Add a route with a parameter
        Route::addRoute('/users/{id}', 'UserController', 'view');

        // Create a route object with our request
        $route = new Route($requestMock);

        // Test route matching
        $route->reload();

        // Check that the correct controller and method were set
        $this->assertEquals('UserController', $route->getController());
        $this->assertEquals('view', $route->getMethod());

        // Check that the parameter was captured
        $params = $route->getParams();
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertEquals('123', $params[0]);
    }

    public function testRouteWithTypedParameters()
    {
        // Create a request mock for testing
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/products/456/reviews/10');
        $requestMock->method('getMethod')->willReturn('GET');

        // Add a route with typed parameters
        Route::addRoute('/products/{id:int}/reviews/{page:int}', 'ProductController', 'reviews');

        // Create a route object with our request
        $route = new Route($requestMock);

        // Test route matching
        $route->reload();

        // Check that the correct controller and method were set
        $this->assertEquals('ProductController', $route->getController());
        $this->assertEquals('reviews', $route->getMethod());

        // Check that the parameters were captured with correct types
        $params = $route->getParams();
        $this->assertIsArray($params);
        $this->assertCount(2, $params);
        $this->assertIsInt($params[0]);
        $this->assertEquals(456, $params[0]);
        $this->assertIsInt($params[1]);
        $this->assertEquals(10, $params[1]);
    }

    public function testRouteWithDefaultParameters()
    {
        // Create a request mock for testing a partial URL
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/blog/categories');
        $requestMock->method('getMethod')->willReturn('GET');

        // Add a route with default parameters
        Route::addRoute('/blog/categories/{page:int=1}', 'BlogController', 'categories');

        // Create a route object with our request
        $route = new Route($requestMock);

        // Test route matching
        $route->reload();

        // Check that the correct controller and method were set
        $this->assertEquals('BlogController', $route->getController());
        $this->assertEquals('categories', $route->getMethod());

        // Check that the default parameter was applied
        $params = $route->getParams();
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertIsInt($params[0]);
        $this->assertEquals(1, $params[0]);

        // Now test with an explicit parameter
        $requestMock2 = $this->createMock(RequestInterface::class);
        $requestMock2->method('getUri')->willReturn('/blog/categories/5');
        $requestMock2->method('getMethod')->willReturn('GET');

        $route2 = new Route($requestMock2);
        $route2->reload();

        $params2 = $route2->getParams();
        $this->assertCount(1, $params2);
        $this->assertEquals(5, $params2[0]);
    }

    public function testRouteWithMultipleParameterTypes()
    {
        // Create a request mock for testing
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/products/search/laptop/price/499.99/instock/true');
        $requestMock->method('getMethod')->willReturn('GET');

        // Add a route with multiple parameter types
        Route::addRoute('/products/search/{keyword}/price/{price:float}/instock/{available:bool}', 'ProductController', 'search');

        // Create a route object with our request
        $route = new Route($requestMock);

        // Test route matching
        $route->reload();

        // Check that the correct controller and method were set
        $this->assertEquals('ProductController', $route->getController());
        $this->assertEquals('search', $route->getMethod());

        // Check that parameters were captured with correct types
        $params = $route->getParams();
        $this->assertIsArray($params);
        $this->assertCount(3, $params);
        $this->assertEquals('laptop', $params[0]);
        $this->assertIsFloat($params[1]);
        $this->assertEquals(499.99, $params[1]);
        $this->assertIsBool($params[2]);
        $this->assertTrue($params[2]);
    }

    public function testRouteNotFound()
    {
        // Create a request mock for a non-existent route
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/non/existent/route');
        $requestMock->method('getMethod')->willReturn('GET');

        // Create a route object with our request
        $route = new Route($requestMock);

        // Test route matching should throw NotFoundException
        $this->expectException(NotFoundException::class);
        $route->reload();
    }

    public function testRouteValidation()
    {
        // Add a route with specific HTTP method
        Route::addRoute('/api/data', 'ApiController', 'getData', ['GET']);

        // Test with matching method
        $requestMock1 = $this->createMock(RequestInterface::class);
        $requestMock1->method('getUri')->willReturn('/api/data');
        $requestMock1->method('getMethod')->willReturn('GET');

        $route1 = new Route($requestMock1);
        $route1->reload();

        $this->assertTrue($route1->validate());

        // Test with non-matching method
        $requestMock2 = $this->createMock(RequestInterface::class);
        $requestMock2->method('getUri')->willReturn('/api/data');
        $requestMock2->method('getMethod')->willReturn('POST');

        $route2 = new Route($requestMock2);
        $route2->reload();

        $this->assertFalse($route2->validate());
    }

    public function testRouteWithCustomParameterRegex()
    {
        // Test with custom regex for UUID
        $uuidRegex = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

        $reflectionMethod = new ReflectionMethod(Route::class, 'getTypePattern');
        $reflectionMethod->setAccessible(true);

        // For a standard parameter type
        $this->assertEquals('[0-9]+', $reflectionMethod->invoke(null, 'int'));
        $this->assertEquals('[0-9]+(?:\\.[0-9]+)?', $reflectionMethod->invoke(null, 'float'));
        $this->assertEquals('(?:true|false|1|0)', $reflectionMethod->invoke(null, 'bool'));
        $this->assertEquals('[^/]+', $reflectionMethod->invoke(null, null));
    }
}