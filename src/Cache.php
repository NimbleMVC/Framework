<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\CacheInterface;

class Cache implements CacheInterface
{

    /**
     * Maximum nesting depth of a cached value checked for disallowed objects
     */
    private const MAX_VALUE_DEPTH = 512;

    /**
     * Storage
     * @var Storage
     */
    private Storage $storage;

    /**
     * Default TTL
     * @var int
     */
    private int $defaultTtl = 3600;

    /**
     * Classes that may be restored from cache files
     * @var string[]
     */
    private array $allowedClasses;

    /**
     * Constructor
     * @param ?string $cachePath
     * @param string[] $allowedClasses Classes that may be restored from cache (stdClass by default); entries with other objects are treated as a cache miss
     * @throws NimbleException
     */
    public function __construct(?string $cachePath = null, array $allowedClasses = [\stdClass::class])
    {
        $cacheDir = $cachePath ?? 'cache';
        $this->storage = new Storage($cacheDir);
        $this->allowedClasses = array_values($allowedClasses);
    }

    /**
     * Set cache
     * @param string $key
     * @param mixed $value
     * @param ?int $ttl
     * @return bool
     * @throws NimbleException
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $data = [
            'value' => $value,
            'expiry' => time() + ($ttl ?? $this->defaultTtl)
        ];

        return $this->storage->put($this->getCacheFilename($key), serialize($data));
    }

    /**
     * Get cache
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->getCacheEntry($this->getCacheFilename($key));

        if ($data === null) {
            return $default;
        }

        return $data['value'];
    }

    /**
     * Check if a cache exists
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->getCacheEntry($this->getCacheFilename($key)) !== null;
    }

    /**
     * Delete cache
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->storage->delete($this->getCacheFilename($key));
    }

    /**
     * Clear cache
     * @return bool
     */
    public function clear(): bool
    {
        $files = $this->storage->listFiles();

        foreach ($files as $file) {
            $this->storage->delete($file);
        }

        return true;
    }

    /**
     * Get cache filename
     * @param string $key
     * @return string
     */
    private function getCacheFilename(string $key): string
    {
        return md5($key) . '.cache';
    }

    /**
     * Get a valid cache entry
     * @param string $filename
     * @return array{value: mixed, expiry: int}|null
     */
    private function getCacheEntry(string $filename): ?array
    {
        $content = $this->storage->get($filename);

        if ($content === null) {
            return null;
        }

        $data = @unserialize($content, ['allowed_classes' => $this->allowedClasses]);

        if (!is_array($data)
            || !array_key_exists('expiry', $data)
            || !array_key_exists('value', $data)
            || !is_int($data['expiry'])
            || $this->containsIncompleteClass($data['value'])
        ) {
            $this->storage->delete($filename);

            return null;
        }

        if (time() > $data['expiry']) {
            $this->storage->delete($filename);

            return null;
        }

        return $data;
    }

    /**
     * Check whether an unserialized value holds an object of a class that was not allowed.
     * Structures nested deeper than MAX_VALUE_DEPTH are treated as invalid.
     * @param mixed $value
     * @param array<int, true> $visitedObjects
     * @param int $depth
     * @return bool
     */
    private function containsIncompleteClass(mixed $value, array &$visitedObjects = [], int $depth = 0): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class || $depth > self::MAX_VALUE_DEPTH) {
            return true;
        }

        if (is_object($value)) {
            if (isset($visitedObjects[spl_object_id($value)])) {
                return false;
            }

            $visitedObjects[spl_object_id($value)] = true;
        } elseif (!is_array($value)) {
            return false;
        }

        foreach ((array)$value as $item) {
            if ($this->containsIncompleteClass($item, $visitedObjects, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

}
