<?php

namespace NimblePHP\framework\CLI;

use NimblePHP\framework\CLI\Commands\ClearCache;
use NimblePHP\framework\CLI\Commands\MakeController;
use NimblePHP\framework\CLI\Commands\Serve;

class Console
{

    private static array $commands = [
        'cache:clear' => ClearCache::class,
        'serve' => Serve::class,
        'make:controller' => MakeController::class
    ];

    /**
     * Run command
     * @param array $argv
     * @return void
     */
    public static function run(array $argv): void
    {
        if (!isset($argv[1])) {
            self::showHelp();
            return;
        }

        $command = $argv[1];
        $args = array_slice($argv, 2);

        if (!array_key_exists($command, self::$commands)) {
            echo "Unknown command: $command\n";
            self::showHelp();
            return;
        }

        ConsoleHelper::initProjectPath();
        $commandClass = self::$commands[$command];
        (new $commandClass())->handle(...$args);
    }

    /**
     * Show help
     * @return void
     */
    private static function showHelp(): void
    {
        echo "Commands:\n";

        foreach (self::$commands as $cmd => $class) {
            echo "  $cmd\n";
        }

        echo "\n";
    }

}
