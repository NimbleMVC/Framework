<?php

namespace NimblePHP\Framework\Interfaces;

interface CacheInterface
{

    /**
     * Set cache
     * @param string $key
     * @param mixed $value
     * @param ?int $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Get cache
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if cache exists
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Delete cache
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Clear cache
     * @return bool
     */
    public function clear(): bool;

}