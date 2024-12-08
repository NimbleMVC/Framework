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
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed;

    /**
     * Get post
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost(string $key, mixed $default = null): mixed;

    /**
     * Get cookie
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null): mixed;

    /**
     * Get file
     * @param $key
     * @return mixed|null
     */
    public function getFile($key): mixed;

    /**
     * Get header
     * @param $key
     * @return mixed
     */
    public function getHeader($key): mixed;

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

}