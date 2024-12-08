<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\ConfigInterface;

/**
 * Config
 * @deprecated use $_ENV
 */
class Config implements ConfigInterface
{

    /**
     * Get config
     * @param string $name
     * @param mixed $default
     * @return mixed
     * @deprecated see $_ENV
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return $_ENV[$name] ?? $default;
    }

    /**
     * Get all config
     * @return array
     * @deprecated see $_ENV
     */
    public static function getAll(): array
    {
        return $_ENV;
    }

}