<?php

namespace NimblePHP\Framework;

class Config
{

    /**
     * Maximum depth of "env:" indirection before it is treated as a cycle.
     */
    private const MAX_ENV_INDIRECTION_DEPTH = 5;

    /**
     * Get config value.
     *
     * Values prefixed with one of the following are automatically decoded:
     * - base64:VALUE  -> base64_decode(VALUE)
     * - hex:VALUE     -> hex2bin(VALUE)
     * - file:PATH     -> contents of the file at PATH
     * - json:VALUE    -> json_decode(VALUE, true)
     * - env:NAME      -> value of another config key (resolved recursively)
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $_ENV)) {
            return $default;
        }

        return self::decode($_ENV[$key]);
    }

    /**
     * Set config value
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_ENV[$key] = $value;
    }

    /**
     * Decode a raw config value based on its prefix.
     * @param mixed $value
     * @param int $depth
     * @return mixed
     */
    private static function decode(mixed $value, int $depth = 0): mixed
    {
        if (!is_string($value) || $depth >= self::MAX_ENV_INDIRECTION_DEPTH) {
            return $value;
        }

        return match (true) {
            str_starts_with($value, 'base64:') => base64_decode(substr($value, 7)),
            str_starts_with($value, 'hex:') => self::decodeHex(substr($value, 4)),
            str_starts_with($value, 'file:') => self::readFile(substr($value, 5)),
            str_starts_with($value, 'json:') => json_decode(substr($value, 5), true),
            str_starts_with($value, 'env:') => self::decode($_ENV[substr($value, 4)] ?? null, $depth + 1),
            default => $value
        };
    }

    /**
     * @param string $hex
     * @return string
     */
    private static function decodeHex(string $hex): string
    {
        if ($hex === '' || !ctype_xdigit($hex) || strlen($hex) % 2 !== 0) {
            return '';
        }

        return hex2bin($hex);
    }

    /**
     * @param string $path
     * @return string
     */
    private static function readFile(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '';
        }

        return (string)file_get_contents($path);
    }

}