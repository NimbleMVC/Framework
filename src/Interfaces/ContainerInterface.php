<?php

namespace NimblePHP\Framework\Interfaces;

interface ContainerInterface
{
    /**
     * Set a service in the container
     * @param string $id
     * @param mixed $service
     * @return void
     */
    public function set(string $id, mixed $service): void;

    /**
     * Set a factory in the container
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function setFactory(string $id, callable $factory): void;

    /**
     * Get a service from the container
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed;

    /**
     * Check if a service exists in the container
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Remove a service from the container
     * @param string $id
     * @return void
     */
    public function remove(string $id): void;

    /**
     * Set an alias for a service
     * @param string $alias
     * @param string $id
     * @return void
     */
    public function setAlias(string $alias, string $id): void;

}