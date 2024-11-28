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
     * Isset module
     * @param string $name
     * @return bool
     */
    public static function isset(string $name): bool
    {
        return array_key_exists($name, self::$modules);
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

            if ($namespace === '\\Nimblephp\\framework') {
                continue;
            }

            $path = InstalledVersions::getInstallPath($package);
            $serviceProviderClass = $namespace . '\\ServiceProvider';
            $classes = [];

            if (class_exists($serviceProviderClass)) {
                $serviceProvider = new $serviceProviderClass();
                $classes['service_providers'][] = $serviceProvider;

                if (method_exists($serviceProvider, 'register')) {
                    $serviceProvider->register();
                }
            }

            $config = ['path' => realpath($path)];

            self::register(
                name: $package,
                config: $config,
                namespace: $namespace,
                classes: $classes
            );
        }
    }

}