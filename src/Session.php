<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\SessionInterface;

class Session implements SessionInterface
{

    /**
     * Init session
     */
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set session
     * @param $key
     * @param $value
     * @return void
     */
    public function set($key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session
     * @param $key
     * @return mixed
     */
    public function get($key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Exists session
     * @param $key
     * @return bool
     */
    public function exists($key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session
     * @param $key
     * @return void
     */
    public function remove($key): void
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