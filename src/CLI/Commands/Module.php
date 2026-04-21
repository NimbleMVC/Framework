<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Generator\Table;
use BackedEnum;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\Output;
use NimblePHP\Framework\DataStore;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use NimblePHP\Framework\Module\ModuleRegister;
use UnitEnum;

class Module
{

    #[ConsoleCommand(
        'module:info',
        'Show module information',
        help: 'Display registered module information or inspect a single module by package name.',
        usage: 'php vendor/bin/nimble module:info [name]',
        arguments: [
            ['name' => 'name', 'description' => 'Module package name to inspect.', 'default' => 'all modules'],
        ],
        examples: [
            ['command' => 'php vendor/bin/nimble module:info', 'description' => 'List registered modules and their basic metadata.'],
            ['command' => 'php vendor/bin/nimble module:info nimblephp/example-module', 'description' => 'Show detailed information for a single module.'],
        ]
    )]
    public function info(Output $output, string $name = ''): int
    {
        $modules = new ModuleRegister();
        $modules->autoRegister();

        $allModules = ModuleRegister::getAll();

        if ($allModules === []) {
            $output->warning('No modules found.');

            return 0;
        }

        if ($name !== '') {
            return $this->showSingleModule($output, trim($name), $allModules);
        }

        $output->section('Modules', count($allModules) . ' discovered');
        $table = new Table();
        $table->addColumn('Module', 'name');
        $table->addColumn('Version', 'version');
        $table->addColumn('Registered', 'registered');
        $table->setData(array_map(
            fn(array $module) => $this->mapModuleSummary($module),
            array_values($allModules)
        ));
        $output->table($table);

        return 0;
    }

    /**
     * @param Output $output
     * @param string $name
     * @param array $modules
     * @return int
     */
    private function showSingleModule(Output $output, string $name, array $modules): int
    {
        if (!isset($modules[$name])) {
            $output->error('Module not found: ' . $name);

            return 1;
        }

        $module = $modules[$name];
        $output->section('Module info', $name);
        $output->kv($this->mapModuleDetails($module));

        return 0;
    }

    /**
     * @param array $module
     * @return array
     */
    private function mapModuleSummary(array $module): array
    {
        $config = $module['config'] instanceof DataStore ? $module['config'] : new DataStore();
        $moduleInstance = $this->resolveModuleInstance($module);

        return [
            'name' => $module['name'] ?? '-',
            'version' => (string)$config->get('pkg_version', '-'),
            'registered' => $config->get('register', false) ? 'yes' : 'no',
        ];
    }

    /**
     * @param array $module
     * @return array
     */
    private function mapModuleDetails(array $module): array
    {
        $config = $module['config'] instanceof DataStore ? $module['config'] : new DataStore();
        $moduleInstance = $this->resolveModuleInstance($module);

        return [
            'Package' => (string)($module['name'] ?? '-'),
            'Display name' => $moduleInstance instanceof ModuleInterface ? $moduleInstance->getName() : '-',
            'Namespace' => $this->stringifyValue($module['namespace'] ?? '-'),
            'Module class' => $moduleInstance?->class ?? '-',
            'Package version' => $this->stringifyValue($config->get('pkg_version', '-')),
            'Framework version' => $this->stringifyValue($config->get('version', '-')),
            'Registered' => $config->get('register', false) ? 'yes' : 'no',
            'Path' => $this->stringifyValue($config->get('path', '-')),
        ];
    }

    /**
     * @param array $module
     * @return ModuleInterface|null
     */
    private function resolveModuleInstance(array $module): ?ModuleInterface
    {
        if (!isset($module['classes']) || !$module['classes'] instanceof DataStore) {
            return null;
        }

        if (!$module['classes']->exists('module')) {
            return null;
        }

        $instance = $module['classes']->get('module');

        return $instance instanceof ModuleInterface ? $instance : null;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function stringifyValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string)$value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return (string)$value;
    }

}
