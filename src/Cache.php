<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\CacheInterface;

class Cache implements CacheInterface
{

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
     * Constructor
     * @param ?string $cachePath
     * @throws NimbleException
     */
    public function __construct(?string $cachePath = null)
    {
        $cacheDir = $cachePath ?? 'cache';
        $this->storage = new Storage($cacheDir);
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

        $data = @unserialize($content, ['allowed_classes' => true]);

        if (!is_array($data)
            || !array_key_exists('expiry', $data)
            || !array_key_exists('value', $data)
            || !is_int($data['expiry'])
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

}
