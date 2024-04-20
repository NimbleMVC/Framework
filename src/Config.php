<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\ConfigInterface;

/**
 * Config
 */
class Config implements ConfigInterface
{

    /**
     * Config list
     * @var array
     */
    protected static array $config = [];

    /**
     * Get config
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        if (!isset(self::$config[$name])) {
            return $default;
        }

        return self::$config[$name];
    }

    /**
     * Set config
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function set(string $name, string $value): void
    {
        self::$config[$name] = $value;
    }

    /**
     * Load from ENV
     * @param string $filePath
     * @return bool
     */
    public static function loadFromEnv(string $filePath): bool
    {
        $fileContents = file_get_contents($filePath);

        if ($fileContents === false) {
            return false;
        }

        $lines = explode(PHP_EOL, $fileContents);

        foreach ($lines as $line) {
            self::processEnvContent($line);
        }

        return true;
    }

    /**
     * Process ENV content of the given string
     * @param string $content The content to be processed
     * @return void
     */
    protected static function processEnvContent(string $content): void
    {
        $content = ltrim($content);

        if (str_starts_with($content, '#') || empty($content)) {
            return;
        }

        $contentParts = explode('=', $content, 2);

        if (count($contentParts) < 2) {
            return;
        }

        $name = $contentParts[0];
        $value = $contentParts[1];
        $value = self::parseEnvValue($value);

        self::$config[$name] = $value;
    }

    /**
     * Parse a given value and return the appropriate data type
     * @param mixed $value The value to be parsed
     * @return mixed The parsed value
     */
    protected static function parseEnvValue(mixed $value): mixed
    {
        if (str_starts_with($value, '"') && str_ends_with($value, '"') || str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return substr($value, 1, -1);
        } elseif (preg_match("/^\d+$/", $value)) {
            return (int)$value;
        } elseif (preg_match("/^\d+\.\d+$/", $value)) {
            return (float)$value;
        }

        return match (strtolower($value)) {
            'false' => false,
            'true' => true,
            'null' => null,
            default => $value,
        };
    }

}