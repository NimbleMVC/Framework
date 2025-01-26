<?php

namespace Nimblephp\framework;

use Composer\InstalledVersions;
use Nimblephp\debugbar\Debugbar;

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
        if (class_exists('Debugbar', false)) {
            Debugbar::startTime('module_auto_register', 'Module auto register');
        }

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

            $name = str_replace('\\Nimblephp\\', '', $namespace);

            if (class_exists('Debugbar', false)) {
                Debugbar::startTime('register_module_' . $namespace, 'Register module ' . $name);
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

            if (class_exists('Debugbar', false)) {
                Debugbar::stopTime('register_module_' . $namespace);
            }
        }

        if (class_exists('Debugbar', false)) {
            Debugbar::stopTime('module_auto_register');
        }
    }

    /**
     * Module exists in vendor
     * @param string $name
     * @return bool
     */
    public static function moduleExistsInVendor(string $name): bool
    {
        $packages = InstalledVersions::getInstalledPackages();

        foreach ($packages as $package) {
            if ($package === $name) {
                return true;
            }
        }

        return false;
    }

}