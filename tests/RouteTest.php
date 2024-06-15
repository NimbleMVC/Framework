<?php

use Nimblephp\framework\Config;
use Nimblephp\framework\Request;
use Nimblephp\framework\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{

    public function testRoute()
    {
        Config::set('DEFAULT_CONTROLLER', 'index');
        Config::set('DEFAULT_METHOD', 'index');
        $request = new Request();
        $route = new Route($request);
        $this->assertEquals(
            'index',
            $route->getController()
        );
        $this->assertEquals(
            'index',
            $route->getMethod()
        );
        $this->assertEmpty($route->getParams());
    }

    public function testRoute2()
    {
        $_SERVER['REQUEST_URI'] = '/controller/method/param1/param2';
        $request = new Request();
        $route = new Route($request);
        $this->assertEquals(
            'controller',
            $route->getController()
        );
        $this->assertEquals(
            'method',
            $route->getMethod()
        );
        $this->assertEquals(
            [
                'param1',
                'param2'
            ],
            $route->getParams()
        );
        unset($_SERVER['REQUEST_URI']);
    }

}