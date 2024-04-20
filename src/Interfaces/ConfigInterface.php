<?php

namespace Nimblephp\framework\Interfaces;

/**
 * Config interface
 */
interface ConfigInterface
{

    /**
     * Get config
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $name, mixed $default): mixed;

    /**
     * Set config
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function set(string $name, string $value): void;

    /**
     * Load from ENV
     * @param string $filePath
     * @return bool
     */
    public static function loadFromEnv(string $filePath): bool;

}