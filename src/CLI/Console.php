<?php

namespace NimblePHP\Framework\CLI;

use Krzysztofzylka\Console\Args;
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
use RuntimeException;
use Throwable;

class Console
{

    /**
     * Commands
     * @var array
     */
    private static array $commands = [];

    /**
     * @param array $argv
     * @return int
     * @throws ReflectionException
     */
    public static function run(array $argv): int
    {
        ConsoleHelper::initProjectPath();
        self::scanCommands();

        if (!isset($argv[1])) {
            self::showOverview();

            return 0;
        }

        $command = $argv[1];

        if (in_array($command, ['--help', '-h', 'help'], true)) {
            self::showOverview();

            return 0;
        }

        $rawArguments = array_slice($argv, 2);
        $parsedArguments = self::parseArguments($rawArguments);

        if (!array_key_exists($command, self::$commands)) {
            Prints::warning('Unknown command: ' . $command);
            self::showSuggestions($command);
            self::showOverview();

            return 1;
        }

        if (($parsedArguments['help'] ?? false) === true || ($parsedArguments['h'] ?? false) === true) {
            self::showCommandHelp($command);

            return 0;
        }

        $commandDefinition = self::$commands[$command];
        $input = new Input(
            command: $command,
            rawArguments: $rawArguments,
            parsedArguments: $parsedArguments,
            argumentMetadata: self::resolveArgumentsMetadata($commandDefinition)
        );
        $output = new Output();

        return self::executeCommand($commandDefinition, $input, $output);
    }

    /**
     * @return array
     */
    public static function getCommandNames(): array
    {
        ConsoleHelper::initProjectPath();
        self::scanCommands();

        return array_keys(self::sortCommands());
    }

    /**
     * @param array $args
     * @return array
     */
    private static function parseArguments(array $args): array
    {
        $normalized = Args::getArgs(array_merge(['nimble'], $args));
        $parsed = $normalized['args'];

        foreach ($normalized['params'] as $key => $value) {
            $parsed[$key] = $value;
        }

        return $parsed;
    }

    /**
     * @return void
     */
    private static function showOverview(): void
    {
        $sections = [];

        $header = new Help();
        $header->addHeader('Commands');
        $sections[] = $header->renderOverview();

        foreach (self::groupCommandsForOverview() as $group => $commands) {
            $help = new Help();
            $help->addHeader($group);

            foreach ($commands as $command => $data) {
                $help->addHelp($command, $data['description']);
            }

            $sections[] = $help->renderOverview();
        }

        Prints::line(implode(PHP_EOL . PHP_EOL, $sections));
    }

    /**
     * @param string $command
     * @return void
     * @throws ReflectionException
     */
    private static function showCommandHelp(string $command): void
    {
        if (!isset(self::$commands[$command])) {
            self::showOverview();

            return;
        }

        $commandData = self::$commands[$command];
        $help = new Help();
        $help->setDescription($commandData['help'] ?? $commandData['description']);
        $help->setUsage($commandData['usage'] ?? self::generateUsage($command, $commandData));

        foreach (self::resolveArgumentsMetadata($commandData) as $argument) {
            $help->addArgument(
                $argument['name'],
                $argument['description'],
                $argument['required'] ?? false,
                $argument['default'] ?? null,
                $argument['multiple'] ?? false,
                $argument['accepted_values'] ?? []
            );
        }

        foreach (self::resolveOptionsMetadata($commandData) as $option) {
            $help->addOption(
                $option['name'],
                $option['description'],
                $option['required'] ?? false,
                $option['default'] ?? null,
                $option['multiple'] ?? false,
                $option['accepted_values'] ?? []
            );
        }

        foreach (self::resolveExamplesMetadata($command, $commandData) as $example) {
            $help->addExample($example['command'], $example['description'] ?? null);
        }

        Prints::line($help->renderCommandHelp());
    }

    /**
     * @return array
     */
    private static function sortCommands(): array
    {
        $serve = [];
        $withoutColon = [];
        $withColon = [];

        foreach (self::$commands as $command => $data) {
            if ($command === 'serve') {
                $serve[$command] = $data;
            } elseif (strpos($command, ':') === false) {
                $withoutColon[$command] = $data;
            } else {
                $withColon[$command] = $data;
            }
        }

        ksort($withoutColon);
        ksort($withColon);

        return $serve + $withoutColon + $withColon;
    }

    /**
     * @return array
     */
    private static function groupCommandsForOverview(): array
    {
        $grouped = [];

        foreach (self::sortCommands() as $command => $data) {
            $group = self::resolveOverviewGroup($command);

            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][$command] = $data;
        }

        $ordered = [];
        $preferredOrder = [
            'Core',
            'Cache',
            'Cron',
            'Logs',
            'Make',
            'Migration',
            'Module',
            'Project',
            'Routes',
            'Other',
        ];

        foreach ($preferredOrder as $group) {
            if (isset($grouped[$group])) {
                $ordered[$group] = $grouped[$group];
                unset($grouped[$group]);
            }
        }

        if ($grouped !== []) {
            ksort($grouped);
        }

        return $ordered + $grouped;
    }

    /**
     * @param string $command
     * @return string
     */
    private static function resolveOverviewGroup(string $command): string
    {
        if (strpos($command, ':') === false) {
            return 'Core';
        }

        $prefix = explode(':', $command, 2)[0];

        return match ($prefix) {
            'config', 'completion' => 'Core',
            'cache' => 'Cache',
            'cron' => 'Cron',
            'logs' => 'Logs',
            'make' => 'Make',
            'migration' => 'Migration',
            'module' => 'Module',
            'project' => 'Project',
            'routes' => 'Routes',
            default => ucfirst(str_replace(['-', '_'], ' ', $prefix)),
        };
    }

    /**
     * @return void
     */
    private static function scanCommands(): void
    {
        self::$commands = [];

        foreach (self::getAllCommandFiles(__DIR__ . '/Commands', 'NimblePHP\Framework\CLI\Commands') as $className) {
            self::registerCommandClass($className);
        }

        self::scanModuleCommands();
    }

    /**
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
                $moduleInstance = $classes->get('module');

                if (is_object($moduleInstance) && $moduleInstance instanceof CliCommandProviderInterface) {
                    foreach ($moduleInstance->getCliCommands() as $commandCandidate) {
                        self::registerCommandClass($commandCandidate);
                    }
                }
            }
        } catch (Throwable $e) {
            Prints::error('Failed load console for modules: ' . $e->getMessage());
        }
    }

    /**
     * @param object|string $commandCandidate
     * @return void
     */
    private static function registerCommandClass(object|string $commandCandidate): void
    {
        $instance = is_object($commandCandidate) ? $commandCandidate : null;
        $className = is_object($commandCandidate) ? $commandCandidate::class : $commandCandidate;

        if (!is_string($className) || !class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);

        foreach ($reflection->getAttributes(ConsoleCommand::class) as $attribute) {
            $metadata = $attribute->newInstance();

            self::registerCommandDefinition($metadata->command, [
                'class' => $className,
                'instance' => $instance,
                'description' => $metadata->description,
                'help' => $metadata->help,
                'usage' => $metadata->usage,
                'arguments' => $metadata->arguments,
                'options' => $metadata->options,
                'examples' => $metadata->examples,
                'mode' => 'class',
                'method' => 'handle',
            ]);
        }

        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(ConsoleCommand::class) as $attribute) {
                $metadata = $attribute->newInstance();

                self::registerCommandDefinition($metadata->command, [
                    'class' => $method->class,
                    'instance' => $instance,
                    'description' => $metadata->description,
                    'help' => $metadata->help,
                    'usage' => $metadata->usage,
                    'arguments' => $metadata->arguments,
                    'options' => $metadata->options,
                    'examples' => $metadata->examples,
                    'mode' => 'method',
                    'method' => $method->name,
                ]);
            }
        }
    }

    /**
     * @param string $command
     * @param array $definition
     * @return void
     */
    private static function registerCommandDefinition(string $command, array $definition): void
    {
        self::$commands[$command] = $definition;
    }

    /**
     * @param array $commandData
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws ReflectionException
     */
    private static function executeCommand(array $commandData, Input $input, Output $output): int
    {
        $instance = self::resolveCommandInstance($commandData);

        if (($commandData['mode'] ?? 'method') === 'class') {
            if ($instance instanceof AbstractCommand) {
                return self::normalizeExitCode($instance->run($input, $output));
            }

            if (method_exists($instance, 'handle')) {
                return self::normalizeExitCode($instance->handle($input, $output));
            }

            throw new RuntimeException('Command class ' . $commandData['class'] . ' must define a handle() method');
        }

        $reflection = new ReflectionMethod($instance, $commandData['method']);
        $arguments = self::resolveMethodParameters($reflection, $input, $output);

        return self::normalizeExitCode($reflection->invokeArgs($instance, $arguments));
    }

    /**
     * @param array $commandData
     * @return object
     */
    private static function resolveCommandInstance(array $commandData): object
    {
        if (isset($commandData['instance']) && is_object($commandData['instance'])) {
            return $commandData['instance'];
        }

        $className = $commandData['class'];

        return new $className();
    }

    /**
     * @param ReflectionMethod $reflection
     * @param Input $input
     * @param Output $output
     * @return array
     */
    private static function resolveMethodParameters(ReflectionMethod $reflection, Input $input, Output $output): array
    {
        $arguments = [];
        $positionals = $input->positionals();
        $position = 0;

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType) {
                if ($type->getName() === Input::class) {
                    $arguments[] = $input;
                    continue;
                }

                if ($type->getName() === Output::class) {
                    $arguments[] = $output;
                    continue;
                }

                if ($type->getName() === 'array') {
                    $arguments[] = $input->all();
                    continue;
                }
            }

            if (array_key_exists($position, $positionals)) {
                $arguments[] = $positionals[$position];
                $position++;
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                continue;
            }
        }

        return $arguments;
    }

    /**
     * @param int|mixed $exitCode
     * @return int
     */
    private static function normalizeExitCode(mixed $exitCode): int
    {
        return is_int($exitCode) ? $exitCode : 0;
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
                $list[] = str_replace('/', '\\', $namespace . '\\' . $file->getBasename('.php'));
            }
        }

        return $list;
    }

    /**
     * @param string $command
     * @param array $commandData
     * @return string
     * @throws ReflectionException
     */
    private static function generateUsage(string $command, array $commandData): string
    {
        $script = $_SERVER['argv'][0] ?? 'vendor/bin/nimble';
        $usage = 'php ' . $script . ' ' . $command;

        foreach (self::getCommandReflectionMethod($commandData)->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType) {
                if (in_array($type->getName(), [Input::class, Output::class], true)) {
                    continue;
                }

                if ($type->getName() === 'array') {
                    $usage .= ' [--option=value]';
                    continue;
                }
            }

            $segment = '<' . $parameter->getName() . '>';

            if ($parameter->isOptional()) {
                $segment = '[' . $parameter->getName() . ']';
            }

            $usage .= ' ' . $segment;
        }

        return $usage;
    }

    /**
     * @param array $commandData
     * @return array
     * @throws ReflectionException
     */
    private static function resolveArgumentsMetadata(array $commandData): array
    {
        if (!empty($commandData['arguments'])) {
            return $commandData['arguments'];
        }

        $arguments = [];

        foreach (self::getCommandReflectionMethod($commandData)->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && in_array($type->getName(), ['array', Input::class, Output::class], true)) {
                continue;
            }

            $arguments[] = [
                'name' => $parameter->getName(),
                'description' => 'The ' . $parameter->getName() . ' argument.',
                'required' => !$parameter->isOptional(),
                'default' => $parameter->isOptional() ? self::stringifyDefaultValue($parameter->getDefaultValue()) : null,
            ];
        }

        return $arguments;
    }

    /**
     * @param array $commandData
     * @return array
     */
    private static function resolveOptionsMetadata(array $commandData): array
    {
        $options = $commandData['options'] ?? [];

        $options[] = [
            'name' => '--help',
            'description' => 'Show help for this command.',
        ];
        $options[] = [
            'name' => '-h',
            'description' => 'Show help for this command.',
        ];

        return $options;
    }

    /**
     * @param string $command
     * @param array $commandData
     * @return array
     * @throws ReflectionException
     */
    private static function resolveExamplesMetadata(string $command, array $commandData): array
    {
        if (!empty($commandData['examples'])) {
            return $commandData['examples'];
        }

        return [[
            'command' => self::generateUsage($command, $commandData),
        ]];
    }

    /**
     * @param array $commandData
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    private static function getCommandReflectionMethod(array $commandData): ReflectionMethod
    {
        return new ReflectionMethod($commandData['class'], $commandData['method']);
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function stringifyDefaultValue(mixed $value): ?string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string)$value,
            default => null,
        };
    }

    /**
     * @param string $command
     * @return void
     */
    private static function showSuggestions(string $command): void
    {
        $matches = [];

        foreach (array_keys(self::$commands) as $registeredCommand) {
            if (str_contains($registeredCommand, $command)) {
                $matches[$registeredCommand] = 0;
                continue;
            }

            $distance = levenshtein($command, $registeredCommand);

            if ($distance <= 4) {
                $matches[$registeredCommand] = $distance;
            }
        }

        if ($matches === []) {
            return;
        }

        asort($matches);
        $suggestions = array_slice(array_keys($matches), 0, 5);

        Prints::line('Did you mean:');
        Prints::bulletList($suggestions);
    }

}
