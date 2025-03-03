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
        if (session_status() === PHP_SESSION_NONE) {
            $sessionPath = Kernel::$projectPath . "/storage/session";

            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0777, true);
            }

            session_save_path($sessionPath);

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
        session_unset();
        session_destroy();
    }

    /**
     * Regenerate session id
     * @param bool|null $removeOldSession
     * @return void
     */
    public function regenerate(?bool $removeOldSession = false): void
    {
        session_regenerate_id($removeOldSession);
    }

}