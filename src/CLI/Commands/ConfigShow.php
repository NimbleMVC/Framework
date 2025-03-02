<?php

namespace NimblePHP\framework\CLI\Commands;

use Exception;
use Krzysztofzylka\Env\Env;
use NimblePHP\framework\Kernel;

class ConfigShow
{

    public static string $description = 'Show configuration';

    /**
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $env = new Env();

        if (file_exists(__DIR__ . '/../../Default/.env')) {
            $env->loadFromFile(__DIR__ . '/../../Default/.env');
        }

        if (file_exists(Kernel::$projectPath . '/.env')) {
            $env->loadFromFile(Kernel::$projectPath . '/.env');
        }

        if (file_exists(Kernel::$projectPath . '/.env.local')) {
            $env->loadFromFile(Kernel::$projectPath . '/.env.local');
        }

        foreach ($_ENV as $name => $value) {
            echo "$name: " . (is_bool($value) ? ($value ? 'True' : 'False') : $value) . PHP_EOL;
        }
    }

}