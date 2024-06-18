<?php

use Nimblephp\framework\Middleware;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{

    protected function setUp(): void
    {
        \Nimblephp\framework\Kernel::$projectPath = __DIR__ . '/framework';

        spl_autoload_register(function ($className) {
            $className = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $className);
            $file = \Nimblephp\framework\Kernel::$projectPath . '/' . $className . '.php';

            if (file_exists($file)) {
                require($file);
            }
        });

        if (class_exists('Middleware')) {
            $this->middleware = new \Middleware();
        } else {
            $this->middleware = new Middleware();
        }

        $request = new \Nimblephp\framework\Request();
        $route = new \Nimblephp\framework\Route($request);
        $kernel = new \Nimblephp\Framework\Kernel($route);
        $kernel->loadConfiguration();
        $kernel->handle();
    }

    public function testtest()
    {

    }

}