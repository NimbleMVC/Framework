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
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class Console
{

    /**
     * Commands
     * @var array
     */
    private static array $commands = [];

    /**
     * Run command
     * @param array $argv
     * @return void
     * @throws ReflectionException
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

        $instance = new $commandClass['class']();
        $reflection = new ReflectionMethod($instance, $method);
        $params = $reflection->getParameters();

        if (count($params) === 1 && ($params[0]->getType() instanceof ReflectionNamedType)
            && $params[0]->getType()->getName() === 'array'
        ) {
            $instance->$method($parsedArgs);
            return;
        }

        $positional = array_values(array_filter(
            $parsedArgs,
            static fn ($k) => is_int($k),
            ARRAY_FILTER_USE_KEY
        ));

        $instance->$method(...$positional);
    }

    /**
     * Parse command arguments into array
     * Supports:
     *   --key=value
     *   --key value
     *   -f (boolean flag)
     * @param array $args
     * @return array
     */
    private static function parseArguments(array $args): array
    {
        $parsed = [];

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            if (str_starts_with($arg, '--')) {
                $raw = substr($arg, 2);

                if (str_contains($raw, '=')) {
                    [$key, $value] = explode('=', $raw, 2);
                    $parsed[$key] = $value;

                    continue;
                }

                $key = $raw;
                $next = $args[$i + 1] ?? null;

                if ($next !== null && !str_starts_with($next, '-')) {
                    $parsed[$key] = $next;
                    $i++;
                } else {
                    $parsed[$key] = true;
                }

                continue;
            }

            if (str_starts_with($arg, '-')) {
                $parsed[substr($arg, 1)] = true;
                continue;
            }

            $parsed[] = $arg;
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

        return $serve + $withoutColon + $withColon;
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
        } catch (Throwable $e) {
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
