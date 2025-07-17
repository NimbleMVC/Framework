<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Interfaces\SessionInterface;
use NimblePHP\Framework\Interfaces\SessionMiddlewareInterface;

class Session implements SessionInterface
{
    /**
     * Session middleware
     * @var array
     */
    protected array $middleware = [];

    /**
     * Initialize session
     * @return void
     */
    public static function init(): void
    {
        if ($_ENV['SESSION_DRIVER'] === 'none') {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            switch ($_ENV['SESSION_DRIVER']) {
                case 'file':
                    $sessionPath = Kernel::$projectPath . "/storage/session";

                    if (!is_dir($sessionPath)) {
                        mkdir($sessionPath, 0777, true);
                    }

                    session_save_path($sessionPath);
                    break;
                case 'redis':
                    $redisHost = $_ENV['SESSION_REDIS_HOST'] ?? '127.0.0.1';
                    $redisPort = 6379;
                    $redisPassword = $_ENV['SESSION_REDIS_PASSWORD'] ?? null;
                    ini_set('session.save_handler', 'redis');
                    $redisConnection = "tcp://{$redisHost}:{$redisPort}";

                    if ($redisPassword) {
                        $redisConnection .= "?auth={$redisPassword}";
                    }

                    ini_set('session.save_path', $redisConnection);
                    break;
            }

            session_start();
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
        $this->runMiddleware('beforeSet', $key, $value);
        $_SESSION[$key] = $value;
        $this->runMiddleware('afterSet', $key, $value);

        return $this;
    }

    /**
     * Get session
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $value = $_SESSION[$key] ?? null;
        $this->runMiddleware('beforeGet', $key, $value);
        $this->runMiddleware('afterGet', $key, $value);
        return $value;
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
        $this->runMiddleware('beforeDestroy');
        session_unset();
        session_destroy();
        $this->runMiddleware('afterDestroy');
    }

    /**
     * Regenerate session id
     * @param bool|null $removeOldSession
     * @return void
     */
    public function regenerate(?bool $removeOldSession = false): void
    {
        $this->runMiddleware('beforeRegenerate');
        session_regenerate_id($removeOldSession);
        $this->runMiddleware('afterRegenerate');
    }

    /**
     * Run session middleware
     * @param string $method
     * @param mixed ...$args
     * @return void
     */
    protected function runMiddleware(string $method, ...$args): void
    {
        foreach ($this->middleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();
                
                if ($middleware instanceof SessionMiddlewareInterface && method_exists($middleware, $method)) {
                    $middleware->$method(...$args);
                }
            }
        }
    }
}