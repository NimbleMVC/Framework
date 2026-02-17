<?php

namespace NimblePHP\Framework\CLI;

use Krzysztofzylka\Console\Generator\Help;
use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\DataStore;
use NimblePHP\Framework\Interfaces\CliCommandProviderInterface;
use NimblePHP\Framework\Module\ModuleRegister;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

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
        ConsoleHelper::initProjectPath();
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

        $commandClass = self::$commands[$command];
        $method = $commandClass['method'];
        $parsedArgs = self::parseArguments($args);
        (new $commandClass['class']())->$method(...$parsedArgs);
    }

    /**
     * Parse command arguments into array
     * @param array $args
     * @return array
     */
    private static function parseArguments(array $args): array
    {
        $parsed = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                $value = $parts[1] ?? true;
                $parsed[$key] = $value;
            } elseif (str_starts_with($arg, '-')) {
                $parsed[substr($arg, 1)] = true;
            } else {
                $parsed[] = $arg;
            }
        }

        return $parsed;
    }

    /**
     * Show help
     * @return void
     */
    private static function showHelp(): void
    {
        $help = new Help();
        $help->addHeader('Commands');

        $sortedCommands = self::sortCommands();

        foreach ($sortedCommands as $cmd => $data) {
            $help->addHelp($cmd, $data['description']);
        }

        $help->render();
    }

    /**
     * Sort commands: serve first, then without :, then with :
     * @return array
     */
    private static function sortCommands(): array
    {
        $serve = [];
        $withoutColon = [];
        $withColon = [];

        foreach (self::$commands as $cmd => $data) {
            if ($cmd === 'serve') {
                $serve[$cmd] = $data;
            } elseif (strpos($cmd, ':') === false) {
                $withoutColon[$cmd] = $data;
            } else {
                $withColon[$cmd] = $data;
            }
        }

        ksort($withoutColon);
        ksort($withColon);

        // Połącz w odpowiedniej kolejności
        return $serve + $withoutColon + $withColon;
    }

    /**
     * Scan commands
     * @return void
     */
    private static function scanCommands(): void
    {
        // Scan framework commands
        foreach (self::getAllCommandFiles(__DIR__ . '/Commands', 'NimblePHP\Framework\CLI\Commands') as $file) {
            if (!class_exists($file)) {
                continue;
            }

            $reflection = new ReflectionClass($file);

            foreach ($reflection->getMethods() as $method) {
                foreach ($method->getAttributes(ConsoleCommand::class) as $attribute) {
                    $class = $attribute->newInstance();

                    self::$commands[$class->command] = [
                        'class' => $method->class,
                        'description' => $class->description,
                        'method' => $method->name
                    ];
                }
            }
        }

        // Scan commands from modules
        self::scanModuleCommands();
    }

    /**
     * Scan commands from modules via service providers
     * @return void
     */
    private static function scanModuleCommands(): void
    {
        try {
            ConsoleHelper::loadConfig();
            $modules = new ModuleRegister();
            $modules->autoRegister();

            foreach ($modules->getAll() as $module) {
                /** @var DataStore $classes */
                $classes = $module['classes'];
                $module = $classes->get('module');

                if (is_object($module) && $module instanceof CliCommandProviderInterface) {
                    foreach ($module->getCliCommands() as $commandInstance) {
                        $reflection = new ReflectionClass($commandInstance);

                        foreach ($reflection->getMethods() as $method) {
                            foreach ($method->getAttributes(ConsoleCommand::class) as $attribute) {
                                $commandAttr = $attribute->newInstance();

                                self::$commands[$commandAttr->command] = [
                                    'class' => $method->class,
                                    'description' => $commandAttr->description,
                                    'method' => $method->name
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Prints::print('Failed load console for modules: ' . $e->getMessage(), false, true, 'red');
        }
    }

    /**
     * @param string $directory
     * @param string $namespace
     * @return array
     */
    private static function getAllCommandFiles(string $directory, string $namespace): array
    {
        $list = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $namespace . '\\' . $file->getBasename('.php');
                $list[] = str_replace('/', '\\', $className);
            }
        }

        return $list;
    }

}
