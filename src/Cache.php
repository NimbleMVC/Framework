<?php

namespace NimblePHP\Framework;

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
     * @return void
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
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expiry = time() + $ttl;
        $data = [
            'value' => $value,
            'expiry' => $expiry
        ];
        $filename = $this->getCacheFilename($key);

        return $this->storage->put($filename, serialize($data));
    }

    /**
     * Get cache
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $filename = $this->getCacheFilename($key);
        $content = $this->storage->get($filename);

        if ($content === null) {
            return $default;
        }

        $data = @unserialize($content);

        if (!is_array($data) || !isset($data['expiry']) || !isset($data['value'])) {
            return $default;
        }

        if (time() > $data['expiry']) {
            $this->storage->delete($filename);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Check if cache exists
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Delete cache
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $filename = $this->getCacheFilename($key);

        return $this->storage->delete($filename);
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

}