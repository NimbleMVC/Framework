<?php

namespace Nimblephp\framework\Interfaces;

interface CookieInterface
{

    /**
     * Set samesite
     * @param string $sameSite
     * @return void
     */
    public static function setSameSite(string $sameSite): void;

    /**
     * Set default secure
     * @param bool $defaultSecure
     * @return void
     */
    public static function setDefaultSecure(bool $defaultSecure): void;

    /**
     * Set default http only
     * @param bool $defaultHttpOnly
     * @return void
     */
    public static function setDefaultHttpOnly(bool $defaultHttpOnly): void;

    /**
     * Set cookie
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param ?bool $secure
     * @param ?bool $httponlya
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
    ): void;

    /**
     * Get cookie
     * @param $name
     * @return mixed
     */
    public function get($name): mixed;

    /**
     * Exists cookie
     * @param $name
     * @return bool
     */
    public function exists($name): bool;

    /**
     * Remove cookie
     * @param $name
     * @return void
     */
    public function remove($name): void;

}