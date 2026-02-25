<?php

namespace NimblePHP\Framework;

/**
 * Simple key-value data store.
 */
class DataStore
{
    
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Set a value by key.
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Append to value
     * @param string $key
     * @param mixed $value
     * @param ?string $arrayKey
     * @return bool
     */
    public function append(string $key, mixed $value, ?string $arrayKey = null): bool
    {
        if (!array_key_exists($key, $this->data)) {
            return false;
        }

        if (is_array($this->data[$key])) {
            if (!is_null($arrayKey)) {
                $this->data[$key][$arrayKey] = $value;
            } else {
                $this->data[$key][] = $value;
            }

            return true;
        } elseif (is_string($this->data[$key])) {
            $this->data[$key] .= $value;

            return true;
        } elseif (is_integer($this->data[$key])) {
            $this->data[$key] += $value;

            return true;
        }

        return true;
    }

    /**
     * Get a value by key.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get a value and remove the key.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            return $default;
        }

        $value = $this->data[$key];
        unset($this->data[$key]);

        return $value;
    }

    /**
     * Check if a key exists.
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove a key.
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Clear all data.
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Get all data.
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get all keys.
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Get all values.
     * @return array<int, mixed>
     */
    public function values(): array
    {
        return array_values($this->data);
    }

    /**
     * Get a value or throw if missing.
     * @param string $key
     * @return mixed
     */
    public function getRequired(string $key): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            throw new \RuntimeException("Missing data key: {$key}");
        }

        return $this->data[$key];
    }

    /**
     * Merge data into the store.
     * @param array<string, mixed>|self $data
     * @return void
     */
    public function merge(array|self $data): void
    {
        $payload = $data instanceof self ? $data->all() : $data;
        $this->data = array_merge($this->data, $payload);
    }

    /**
     * Replace all data.
     * @param array<string, mixed>|self $data
     * @return void
     */
    public function replace(array|self $data): void
    {
        $this->data = $data instanceof self ? $data->all() : $data;
    }

    /**
     * Count stored items.
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Check if store is empty.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->data === [];
    }

}
