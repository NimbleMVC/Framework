<?php

namespace Nimblephp\framework\Interfaces;

/**
 * Config interface
 * @deprecated see $_ENV
 */
interface ConfigInterface
{

    /**
     * Get config
     * @param string $name
     * @param mixed $default
     * @return mixed
     * @deprecated see $_ENV
     */
    public static function get(string $name, mixed $default): mixed;

    /**
     * Get all config
     * @return array
     * @deprecated see $_ENV
     */
    public static function getAll(): array;

}