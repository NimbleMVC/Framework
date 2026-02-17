<?php

namespace NimblePHP\Framework\Module;

use Composer\InstalledVersions;
use NimblePHP\Framework\DataStore;
use NimblePHP\Framework\Enums\ModuleVersionEnum;
use NimblePHP\Framework\Module\Interfaces\ModuleModelInterface;

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
     * @param DataStore $config
     * @param string|null $namespace
     * @param DataStore|null $classes
     * @return void
     */
    public static function register(
        string     $name,
        DataStore  $config,
        ?string    $namespace = null,
        ?DataStore $classes = null
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

            $namespace = '\\' . str_replace(
                    ['Nimblephp'],
                    ['NimblePHP'],
                    implode('\\', array_map('ucfirst', explode('/', $package)))
                );

            if ($namespace === '\\NimblePHP\\Framework') {
                continue;
            }

            self::autoRegisterFromNamespace(
                namespace: $namespace,
                package: $package
            );
        }
    }

    /**
     * Register single module from namespace
     * @param string $namespace
     * @param string|null $path
     * @param string|null $package
     * @return void
     */
    public static function autoRegisterFromNamespace(string $namespace, ?string $path = null, ?string $package = null): void
    {
        $pkgVersion = null;

        if ($package) {
            $path = InstalledVersions::getInstallPath($package);
            $pkgVersion = InstalledVersions::getPrettyVersion($package) ?? '-';
        }

        $moduleClass = $namespace . '\\Module';
        $classes = new DataStore();
        $config = new DataStore();
        $config->set('path', realpath($path));
        $config->set('version', ModuleVersionEnum::V2);
        $config->set('pkg_version', $pkgVersion);
        $config->set('register', false);
        $config->set('models', []);

        if (class_exists($moduleClass)) {
            $moduleClass = new $moduleClass();
            $classes->set('module', $moduleClass);

            if ($moduleClass instanceof ModuleModelInterface) {
                $config->set('models', $moduleClass->getModels());
            }
        }

        self::register(
            name: $package ?? '',
            config: $config,
            namespace: $namespace,
            classes: $classes
        );
    }

    /**
     * Module exists in vendor
     * @param string $name
     * @return bool
     */
    public static function moduleExistsInVendor(string $name): bool
    {
        $packages = InstalledVersions::getInstalledPackages();

        if (in_array($name, $packages, true)) {
            return true;
        }

        return false;
    }

}