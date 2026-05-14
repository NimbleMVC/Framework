<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Interfaces\SessionInterface;

class Session implements SessionInterface
{

    /**
     * Initialize session
     * @return void
     */
    public static function init(): void
    {
        if (php_sapi_name() === 'cli' || headers_sent()) {
            return;
        }

        if (Config::get('SESSION_DRIVER', 'none') === 'none') {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            switch (Config::get('SESSION_DRIVER', 'none')) {
                case 'file':
                    $sessionPath = Kernel::$projectPath . "/storage/session";

                    if (!is_dir($sessionPath)) {
                        mkdir($sessionPath, 0777, true);
                    }

                    session_save_path($sessionPath);
                    break;
                case 'redis':
                    $redisHost = Config::get('SESSION_REDIS_HOST', '127.0.0.1');
                    $redisPort = (int) Config::get('SESSION_REDIS_PORT', 6379);
                    $redisPassword = Config::get('SESSION_REDIS_PASSWORD', null);
                    $redisConnectTimeout = (float) Config::get('SESSION_REDIS_CONNECT_TIMEOUT', 0.5);
                    $redisReadTimeout = (float) Config::get('SESSION_REDIS_READ_TIMEOUT', 1);
                    ini_set('session.save_handler', 'redis');
                    $redisConnection = "tcp://{$redisHost}:{$redisPort}";
                    $redisConnectionParams = [
                        'timeout' => $redisConnectTimeout,
                        'read_timeout' => $redisReadTimeout,
                    ];

                    if ($redisPassword) {
                        $redisConnectionParams['auth'] = $redisPassword;
                    }

                    if (!empty($redisConnectionParams)) {
                        $redisConnection .= '?' . http_build_query($redisConnectionParams, '', '&', PHP_QUERY_RFC3986);
                    }

                    ini_set('session.save_path', $redisConnection);
                    break;
            }

            self::startSession();
        }
    }

    /**
     * Init session
     */
    public function __construct()
    {
        self::init();
    }

    /**
     * Set session
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $_SESSION[$key] = $value;

        return $this;
    }

    /**
     * Get session
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Exists session
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session
     * @return void
     */
    public function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Regenerate session id
     * @param bool|null $removeOldSession
     * @return void
     */
    public function regenerate(?bool $removeOldSession = false): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (!self::shouldRetryRedisSessionOperation()) {
            session_regenerate_id($removeOldSession);
            return;
        }

        self::runRedisSessionOperationWithRetry(
            static fn (): bool => session_regenerate_id($removeOldSession)
        );
    }

    /**
     * @return void
     */
    private static function startSession(): void
    {
        if (!self::shouldRetryRedisSessionOperation()) {
            session_start();
            return;
        }

        self::runRedisSessionOperationWithRetry(
            static fn (): bool => session_start()
        );
    }

    /**
     * @return bool
     */
    private static function shouldRetryRedisSessionOperation(): bool
    {
        return Config::get('SESSION_DRIVER', 'none') === 'redis';
    }

    /**
     * @param callable $operation
     * @return void
     */
    private static function runRedisSessionOperationWithRetry(callable $operation): void
    {
        $attempts = max(1, (int) Config::get('SESSION_REDIS_RETRY_ATTEMPTS', 3));
        $delayMilliseconds = max(0, (int) Config::get('SESSION_REDIS_RETRY_DELAY_MS', 150));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($attempt === $attempts) {
                $operation();
                return;
            }

            set_error_handler(static fn (): bool => true);

            try {
                $result = $operation();
            } finally {
                restore_error_handler();
            }

            if ($result !== false) {
                return;
            }

            if ($delayMilliseconds > 0) {
                usleep($delayMilliseconds * 1000);
            }
        }
    }

}
