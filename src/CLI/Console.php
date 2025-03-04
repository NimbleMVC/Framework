<?php

namespace NimblePHP\Framework\CLI;

use Krzysztofzylka\Console\Generator\Help;
use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;

class Console
{

    private static array $commands = [];

    /**
     * Run command
     * @param array $argv
     * @return void
     */
    public static function run(array $argv): void
    {
        self::scanCommands();
        
        if (!isset($argv[1])) {
            self::showHelp();
            return;
        }

        $command = $argv[1];
        $args = array_slice($argv, 2);

        if (!array_key_exists($command, self::$commands)) {
            Prints::print('Unknown command: ' . $command, color: 'yellow');
            self::showHelp();
            return;
        }

        ConsoleHelper::initProjectPath();
        $commandClass = self::$commands[$command];
        $method = $commandClass['method'];
        (new $commandClass['class']())->$method(...$args);
    }

    /**
     * Show help
     * @return void
     */
    private static function showHelp(): void
    {
        $help = new Help();
        $help->addHeader('Commands');

        foreach (self::$commands as $cmd => $data) {
            $help->addHelp($cmd, $data['description']);
        }

        $help->render();
    }

    /**
     * Scan commands
     * @return void
     */
    private static function scanCommands(): void
    {
        foreach (self::getAllCommandFiles(__DIR__ . '/Commands', 'NimblePHP\Framework\CLI\Commands') as $file) {
            if (!class_exists($file)) {
                continue;
            }

            $reflection = new \ReflectionClass($file);

            foreach ($reflection->getMethods() as $method) {
                foreach ($method->getAttributes(ConsoleCommand::class) as $attribute) {
                    $class = $attribute->newInstance();
                    
                    self::$commands[$class->command] = [
                        'class' => $method->class,
                        'description' => $class->description,
                        'method' => $method->name
                    ];}
            }
        }
    }

    /**
     * @param string $directory
     * @param string $namespace
     * @return array
     */
    private static function getAllCommandFiles(string $directory, string $namespace): array {
        $list = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $namespace . '\\' . $file->getBasename('.php');
                $list[] = str_replace('/', '\\', $className);
            }
        }

        return $list;
    }

}
