<?php

namespace NimblePHP\Framework\Container;

/**
 * Base container class for services and middleware.
 */
abstract class ContainerBase
{

    /**
     * Stored instances (singleton pattern)
     * @var array<class-string, static>
     */
    protected static array $instances = [];

    /**
     * Returns singleton instance of the called class.
     * @return static
     */
    public static function getInstance(): static
    {
        $calledClass = static::class;

        if (!isset(self::$instances[$calledClass])) {
            self::$instances[$calledClass] = new static();
        }

        return self::$instances[$calledClass];
    }

}