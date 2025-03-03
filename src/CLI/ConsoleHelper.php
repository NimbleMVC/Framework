<?php

namespace NimblePHP\Framework\CLI;

use Krzysztofzylka\Env\Env;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Route;

class ConsoleHelper
{


    /**
     * Init project path for methods
     * @return void
     */
    public static function initProjectPath(): void
    {
        Kernel::$projectPath = getcwd();
    }

    /**
     * Check projest is initialized
     * @param string $path
     * @return bool
     */
    public static function projectIsInitialized(string $path): bool
    {
        $paths = ['App', 'public', 'storage'];

        foreach ($paths as $value) {
            if (file_exists($path. '/' . $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load config
     * @return void
     */
    public static function loadConfig(): void
    {
        $env = new Env();

        if (file_exists(__DIR__ . '/../Default/.env')) {
            $env->loadFromFile(__DIR__ . '/../Default/.env');
        }

        if (file_exists(Kernel::$projectPath . '/.env')) {
            $env->loadFromFile(Kernel::$projectPath . '/.env');
        }

        if (file_exists(Kernel::$projectPath . '/.env.local')) {
            $env->loadFromFile(Kernel::$projectPath . '/.env.local');
        }
    }

    /**
     * Init kernel
     * @return Kernel
     * @throws \NimblePHP\Framework\Exception\DatabaseException
     * @throws \Throwable
     */
    public static function initKernel(): Kernel
    {
        $kernel = new Kernel(new Route(new Request()));
        self::initProjectPath();
        $kernel->bootstrap();

        return $kernel;
    }

}