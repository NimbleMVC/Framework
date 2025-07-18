<?php

use NimblePHP\Framework\Routes\SimpleRoute;
use NimblePHP\Framework\Interfaces\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * Testy dla klasy SimpleRoute
 */
class SimpleRouteTest extends TestCase
{
    protected function setUp(): void
    {
        // Ustawiamy domyślne wartości środowiskowe
        $_ENV['DEFAULT_CONTROLLER'] = 'index';
        $_ENV['DEFAULT_METHOD'] = 'index';

        // Resetujemy statyczne tablice tras
        $reflectionClass = new ReflectionClass(SimpleRoute::class);
        $routesProperty = $reflectionClass->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(null, []);
    }

    public function testAddAndGetRoutes()
    {
        // Dodajemy trasę
        SimpleRoute::addRoute('test/route', 'TestController', 'testMethod');

        // Pobieramy zarejestrowane trasy
        $routes = SimpleRoute::getRoutes();

        // Sprawdzamy, czy trasa została dodana
        $this->assertArrayHasKey('test/route', $routes);
        $this->assertEquals('TestController', $routes['test/route']['controller']);
        $this->assertEquals('testMethod', $routes['test/route']['method']);
    }

    public function testAddRouteWithDefaultValues()
    {
        // Dodajemy trasę bez podania kontrolera i metody
        SimpleRoute::addRoute('default/route');

        // Pobieramy zarejestrowane trasy
        $routes = SimpleRoute::getRoutes();

        // Sprawdzamy, czy domyślne wartości zostały użyte
        $this->assertArrayHasKey('default/route', $routes);
        $this->assertEquals($_ENV['DEFAULT_CONTROLLER'], $routes['default/route']['controller']);
        $this->assertEquals($_ENV['DEFAULT_METHOD'], $routes['default/route']['method']);
    }

    public function testConstructorWithEmptyUri()
    {
        // Tworzymy mock dla RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('');

        // Tworzymy obiekt SimpleRoute
        $route = new SimpleRoute($requestMock);

        // Sprawdzamy, czy domyślne wartości zostały użyte
        $this->assertEquals($_ENV['DEFAULT_CONTROLLER'], $route->getController());
        $this->assertEquals($_ENV['DEFAULT_METHOD'], $route->getMethod());
        $this->assertEmpty($route->getParams());
    }

    public function testConstructorWithBasicUri()
    {
        // Tworzymy mock dla RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/test/method');

        // Tworzymy obiekt SimpleRoute
        $route = new SimpleRoute($requestMock);

        // Sprawdzamy, czy wartości zostały poprawnie ustawione
        $this->assertEquals('test', $route->getController());
        $this->assertEquals('method', $route->getMethod());
        $this->assertEmpty($route->getParams());
    }

    public function testConstructorWithParams()
    {
        // Tworzymy mock dla RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/test/method/param1/param2');

        // Tworzymy obiekt SimpleRoute
        $route = new SimpleRoute($requestMock);

        // Sprawdzamy, czy wartości zostały poprawnie ustawione
        $this->assertEquals('test', $route->getController());
        $this->assertEquals('method', $route->getMethod());
        $this->assertEquals(['param1', 'param2'], $route->getParams());
    }

    public function testReload()
    {
        // Dodajemy trasę
        SimpleRoute::addRoute('custom/route', 'CustomController', 'customMethod');

        // Tworzymy mock dla RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('/custom/route');

        // Tworzymy obiekt SimpleRoute
        $route = new SimpleRoute($requestMock);

        // Przed reload, kontroler i metoda są ustawione na podstawie URI
        $this->assertEquals('custom', $route->getController());
        $this->assertEquals('route', $route->getMethod());

        // Wykonujemy reload
        $route->reload();

        // Po reload, kontroler i metoda powinny być ustawione na podstawie zarejestrowanej trasy
        $this->assertEquals('CustomController', $route->getController());
        $this->assertEquals('customMethod', $route->getMethod());
    }

    public function testSettersAndGetters()
    {
        // Tworzymy mock dla RequestInterface
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('');

        // Tworzymy obiekt SimpleRoute
        $route = new SimpleRoute($requestMock);

        // Testujemy settery i gettery
        $route->setController('TestController');
        $route->setMethod('testMethod');
        $route->setParams(['param1', 'param2']);

        $this->assertEquals('TestController', $route->getController());
        $this->assertEquals('testMethod', $route->getMethod());
        $this->assertEquals(['param1', 'param2'], $route->getParams());
    }

    public function testValidate()
    {
        // Metoda validate w SimpleRoute zawsze zwraca true
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getUri')->willReturn('');

        $route = new SimpleRoute($requestMock);

        $this->assertTrue($route->validate());
    }
}