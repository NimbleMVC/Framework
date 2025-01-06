<?php

namespace Nimblephp\framework\Interfaces;

/**
 * Request interface
 */
interface RequestInterface
{

    /**
     * Get query
     * @param string $key
     * @param mixed $default
     * @param bool $protect htmlspecialchars
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null, bool $protect = true): mixed;

    /**
     * Get post
     * @param string $key
     * @param mixed $default
     * @param bool $protect htmlspecialchars
     * @return mixed
     */
    public function getPost(string $key, mixed $default = null, bool $protect = true): mixed;

    /**
     * Get cookie
     * @param string $key
     * @param mixed $default
     * @param bool $protect htmlspecialchars
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null, bool $protect = true): mixed;

    /**
     * Get file
     * @param string $key
     * @return mixed|null
     */
    public function getFile(string $key): mixed;

    /**
     * Get header
     * @param string $key
     * @return mixed
     */
    public function getHeader(string $key): mixed;

    /**
     * Get method
     * @return string
     */
    public function getMethod(): string;

    /**
     * Get URI
     * @return string
     */
    public function getUri(): string;

    /**
     * Get server
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getServer(string $key, mixed $default = null): mixed;

    /**
     * Get body
     * @return string
     */
    public function getBody(): string;

    /**
     * Get all query
     * @param bool $protect htmlspecialhars
     * @return array
     */
    public function getAllQuery(bool $protect = true): array;

    /**
     * Get all post
     * @param bool $protect htmlspecialhars
     * @return array
     */
    public function getAllPost(bool $protect = true): array;

    /**
     * Check if the request is an AJAX request
     * @return bool
     */
    public function isAjax(): bool;

    /**
     * Isset query
     * @param string $key
     * @return bool
     */
    public function issetQuery(string $key): bool;

    /**
     * Isset post
     * @param string $key
     * @return bool
     */
    public function issetPost(string $key): bool;

    /**
     * Isset cookie
     * @param string $key
     * @return bool
     */
    public function issetCookie(string $key): bool;

}