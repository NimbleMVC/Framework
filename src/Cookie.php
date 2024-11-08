<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\CookieInterface;

class Cookie implements CookieInterface
{

    /**
     * Default secure
     * @var bool
     */
    protected static bool $defaultSecure = false;

    /**
     * Default HTTP only
     * @var bool
     */
    protected static bool $defaultHttpOnly = false;

    /**
     * Samesite
     * @var string
     */
    protected static string $sameSite = 'Lax';

    /**
     * Set samesite
     * @param string $sameSite
     * @return void
     */
    public static function setSameSite(string $sameSite): void
    {
        self::$sameSite = $sameSite;
    }

    /**
     * Set default secure
     * @param bool $defaultSecure
     * @return void
     */
    public static function setDefaultSecure(bool $defaultSecure): void
    {
        self::$defaultSecure = $defaultSecure;
    }

    /**
     * Set default http only
     * @param bool $defaultHttpOnly
     * @return void
     */
    public static function setDefaultHttpOnly(bool $defaultHttpOnly): void
    {
        self::$defaultHttpOnly = $defaultHttpOnly;
    }

    /**
     * Set cookie
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param ?bool $secure
     * @param ?bool $httponly
     * @return void
     */
    public function set(
        string $name,
        mixed $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        ?bool $secure = null,
        ?bool $httponly = false
    ): void
    {
        $expireTime = $expire > 0 ? time() + $expire : 0;

        setcookie($name, $value, [
            'expires' => $expireTime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure ?? self::$defaultSecure,
            'httponly' => $httponly ?? self::$defaultHttpOnly,
            'samesite' => self::$sameSite
        ]);
    }

    /**
     * Get cookie
     * @param $name
     * @return mixed
     */
    public function get($name): mixed
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Exists cookie
     * @param $name
     * @return bool
     */
    public function exists($name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Remove cookie
     * @param $name
     * @return void
     */
    public function remove($name): void
    {
        setcookie($name, '', time() - 3600, '/');
        unset($_COOKIE[$name]);
    }

}
