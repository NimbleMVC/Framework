<?php

namespace NimblePHP\Framework\Event\Listener;

use JetBrains\PhpStorm\NoReturn;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Event\Framework\AfterBootstrapEvent;
use NimblePHP\Framework\Kernel;

/**
 * Applies CORS headers during the post-bootstrap framework event.
 */
class CorsListener
{

    protected static bool $registered = false;

    /**
     * Register the listener when CORS configuration is enabled.
     *
     * @return void
     */
    public static function registerFromEnv(): void
    {
        if (self::$registered) {
            return;
        }

        if (empty(Config::get('API_CORS_ORIGINS'))) {
            return;
        }

        Kernel::getEventDispatcher()->addListener(AfterBootstrapEvent::class, new self(), 200);
        self::$registered = true;
    }

    /**
     * Reset the listener registration flag.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$registered = false;
    }

    /**
     * Handle the framework bootstrap completion event.
     *
     * @param AfterBootstrapEvent $event
     * @return void
     */
    public function handle(AfterBootstrapEvent $event): void
    {
        $this->applyCors();
    }

    /**
     * Apply the configured CORS policy to the current request.
     *
     * @return void
     */
    protected function applyCors(): void
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
     * Resolve the response origin value for the provided request origin.
     *
     * @param string $origin
     * @return string|null
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
     * Determine whether the request is a CORS preflight request.
     *
     * @return bool
     */
    protected function isPreflight(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS'
            && isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
    }

    /**
     * Send a response header unless headers were already emitted.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    protected function sendHeader(string $name, string $value): void
    {
        if (headers_sent()) {
            return;
        }

        header($name . ': ' . $value, $name !== 'Vary');
    }

    #[NoReturn]
    /**
     * Terminate the request after responding to a preflight request.
     *
     * @param int $statusCode
     * @return void
     */
    protected function terminate(int $statusCode): void
    {
        http_response_code($statusCode);

        exit;
    }

}
