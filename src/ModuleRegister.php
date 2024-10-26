<?php

namespace Nimblephp\framework;

use Composer\InstalledVersions;

/**
 * Module register
 */
class ModuleRegister
{

    /**
     * Modules list
     * @var array
     */
    private static array $modules = [];

    /**
     * Register modules
     * @param string $name
     * @param array $config
     * @param string|null $namespace
     * @param array|null $classes
     * @return void
     */
    public static function register(
        string $name,
        array $config = [],
        ?string $namespace = null,
        ?array $classes = null
    ): void
    {
        self::$modules[$name] = [
            'name' => $name,
            'config' => $config,
            'namespace' => $namespace,
            'classes' => $classes
        ];
    }

    /**
     * Get module
     * @param string $name
     * @return array
     */
    public static function get(string $name): array
    {
        return self::$modules[$name] ?? [];
    }

    /**
     * Get all modules
     * @return array
     */
    public static function getAll(): array
    {
        return self::$modules;
    }

    /**
     * Auto init module
     * @return void
     */
    public function autoRegister(): void
    {
        $packages = InstalledVersions::getInstalledPackages();

        foreach ($packages as $package) {
            if (in_array($package, array_keys(self::$modules))) {
                continue;
            }

            if (!str_starts_with($package, 'nimblephp/')) {
                continue;
            }

            $namespace = '\\' . str_replace(['/', 'nimblephp'], ['\\', 'Nimblephp'], $package);
            $path = InstalledVersions::getInstallPath($package);
            $serviceProviderClass = $namespace . '\\ServiceProvider';
            $classes = [];

            if (class_exists($serviceProviderClass)) {
                $classes['service_providers'][] = new $serviceProviderClass();
            }

            $config = ['path' => $path];

            self::register(
                name: $package,
                config: $config,
                namespace: $namespace,
                classes: $classes
            );
        }
    }

}