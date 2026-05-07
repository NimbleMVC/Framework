<?php

namespace NimblePHP\Framework\Middleware;

use NimblePHP\Framework\Config;
use NimblePHP\Framework\Kernel;

/**
 * Adds CORS headers and short-circuits OPTIONS preflight requests.
 *
 * Configured entirely via env variables:
 *  - API_CORS_ORIGINS:     comma-separated list of allowed origins, or `*` (default empty = disabled)
 *  - API_CORS_METHODS:     comma-separated allowed HTTP methods (default GET,POST,PUT,PATCH,DELETE,OPTIONS)
 *  - API_CORS_HEADERS:     comma-separated allowed request headers (default Content-Type,Authorization,X-Requested-With)
 *  - API_CORS_CREDENTIALS: 1/true to send Access-Control-Allow-Credentials (default false)
 *  - API_CORS_MAX_AGE:     seconds to cache preflight (default 600)
 */
class CorsMiddleware
{

    protected static bool $registered = false;

    /**
     * Register the middleware if API_CORS_ORIGINS is configured (idempotent).
     */
    public static function registerFromEnv(): void
    {
        if (self::$registered) {
            return;
        }

        if (empty(Config::get('API_CORS_ORIGINS'))) {
            return;
        }

        if (!isset(Kernel::$middlewareManager)) {
            return;
        }

        Kernel::$middlewareManager->add(new self(), 200);
        self::$registered = true;
    }

    /**
     * Reset registration flag (useful for tests).
     */
    public static function reset(): void
    {
        self::$registered = false;
    }

    /**
     * Hook called by Kernel after bootstrap, before any controller dispatch.
     */
    public function afterBootstrap(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        if ($origin === null) {
            return;
        }

        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        if ($allowedOrigin === null) {
            return;
        }

        $this->sendHeader('Access-Control-Allow-Origin', $allowedOrigin);
        $this->sendHeader('Vary', 'Origin');

        if (filter_var(Config::get('API_CORS_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->sendHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($this->isPreflight()) {
            $this->sendHeader(
                'Access-Control-Allow-Methods',
                Config::get('API_CORS_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')
            );
            $this->sendHeader(
                'Access-Control-Allow-Headers',
                Config::get('API_CORS_HEADERS', 'Content-Type,Authorization,X-Requested-With')
            );
            $this->sendHeader(
                'Access-Control-Max-Age',
                (string)Config::get('API_CORS_MAX_AGE', 600)
            );

            $this->terminate(204);
        }
    }

    /**
     * End the request with the given status code (overridable for tests).
     */
    protected function terminate(int $statusCode): void
    {
        http_response_code($statusCode);
        exit;
    }

    /**
     * Resolve the value to put in Access-Control-Allow-Origin, or null if origin is not allowed.
     */
    protected function resolveAllowedOrigin(string $origin): ?string
    {
        $configured = trim((string)Config::get('API_CORS_ORIGINS', ''));

        if ($configured === '') {
            return null;
        }

        if ($configured === '*') {
            $allowCredentials = filter_var(Config::get('API_CORS_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN);

            return $allowCredentials ? $origin : '*';
        }

        $allowList = array_map('trim', explode(',', $configured));

        return in_array($origin, $allowList, true) ? $origin : null;
    }

    /**
     * Detect a CORS preflight request (OPTIONS + Access-Control-Request-Method header).
     */
    protected function isPreflight(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS'
            && isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
    }

    /**
     * Wrapper around header() to keep the class testable.
     */
    protected function sendHeader(string $name, string $value): void
    {
        if (headers_sent()) {
            return;
        }

        header($name . ': ' . $value, $name !== 'Vary');
    }

}
