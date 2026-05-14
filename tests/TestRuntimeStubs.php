<?php

namespace TestSupport;

final class RuntimeFunctionState
{
    public static array $headers = [];

    public static bool $headersSent = false;

    public static array $cookies = [];

    public static ?string $phpInput = null;

    public static array $passthruCalls = [];

    public static int $passthruStatus = 0;

    public static ?string $lastChdir = null;

    public static ?string $cwd = null;

    public static array $randomValues = [];

    public static function reset(): void
    {
        self::$headers = [];
        self::$headersSent = false;
        self::$cookies = [];
        self::$phpInput = null;
        self::$passthruCalls = [];
        self::$passthruStatus = 0;
        self::$lastChdir = null;
        self::$cwd = null;
        self::$randomValues = [];
    }

    public static function recordHeader(string $header, bool $replace, int $responseCode): void
    {
        self::$headers[] = [
            'header' => $header,
            'replace' => $replace,
            'response_code' => $responseCode,
        ];
    }

    public static function recordCookie(array $cookie): void
    {
        self::$cookies[] = $cookie;
    }

    public static function shiftRandom(int $min, int $max): int
    {
        if (self::$randomValues !== []) {
            return array_shift(self::$randomValues);
        }

        return \mt_rand($min, $max);
    }
}

namespace NimblePHP\Framework;

if (!function_exists(__NAMESPACE__ . '\header')) {
    function header(string $header, bool $replace = true, int $response_code = 0): void
    {
        \TestSupport\RuntimeFunctionState::recordHeader($header, $replace, $response_code);
    }
}

if (!function_exists(__NAMESPACE__ . '\headers_sent')) {
    function headers_sent(?string &$filename = null, ?int &$line = null): bool
    {
        return \TestSupport\RuntimeFunctionState::$headersSent;
    }
}

if (!function_exists(__NAMESPACE__ . '\setcookie')) {
    function setcookie(
        string $name,
        string $value = '',
        array|int $expires_or_options = 0,
        string $path = '',
        string $domain = '',
        ?bool $secure = null,
        ?bool $httponly = null
    ): bool {
        if (is_array($expires_or_options)) {
            $payload = [
                'name' => $name,
                'value' => $value,
                'options' => $expires_or_options,
            ];
        } else {
            $payload = [
                'name' => $name,
                'value' => $value,
                'options' => [
                    'expires' => $expires_or_options,
                    'path' => $path,
                    'domain' => $domain,
                    'secure' => $secure,
                    'httponly' => $httponly,
                ],
            ];
        }

        \TestSupport\RuntimeFunctionState::recordCookie($payload);

        return true;
    }
}

if (!function_exists(__NAMESPACE__ . '\file_get_contents')) {
    function file_get_contents(
        string $filename,
        bool $use_include_path = false,
        mixed $context = null,
        int $offset = 0,
        ?int $length = null
    ): string|false {
        if ($filename === 'php://input' && \TestSupport\RuntimeFunctionState::$phpInput !== null) {
            return \TestSupport\RuntimeFunctionState::$phpInput;
        }

        if ($length === null) {
            return \file_get_contents($filename, $use_include_path, $context, $offset);
        }

        return \file_get_contents($filename, $use_include_path, $context, $offset, $length);
    }
}

if (!function_exists(__NAMESPACE__ . '\mt_rand')) {
    function mt_rand(int $min, int $max): int
    {
        return \TestSupport\RuntimeFunctionState::shiftRandom($min, $max);
    }
}

namespace NimblePHP\Framework\CLI\Commands;

if (!function_exists(__NAMESPACE__ . '\passthru')) {
    function passthru(string $command, ?int &$status = null): ?false
    {
        \TestSupport\RuntimeFunctionState::$passthruCalls[] = $command;
        $status = \TestSupport\RuntimeFunctionState::$passthruStatus;

        return null;
    }
}

if (!function_exists(__NAMESPACE__ . '\chdir')) {
    function chdir(string $directory): bool
    {
        \TestSupport\RuntimeFunctionState::$lastChdir = $directory;

        return true;
    }
}

if (!function_exists(__NAMESPACE__ . '\getcwd')) {
    function getcwd(): string|false
    {
        return \TestSupport\RuntimeFunctionState::$cwd ?? \getcwd();
    }
}

namespace NimblePHP\Framework\CLI;

if (!function_exists(__NAMESPACE__ . '\getcwd')) {
    function getcwd(): string|false
    {
        return \TestSupport\RuntimeFunctionState::$cwd ?? \getcwd();
    }
}
