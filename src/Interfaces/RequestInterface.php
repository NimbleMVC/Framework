<?php

namespace Nimblephp\framework\Interfaces;

/**
 * Request interface
 */
interface RequestInterface
{

    /**
     * Get query
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getQuery($key, $default = null): mixed;

    /**
     * Get post
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getPost($key, $default = null): mixed;

    /**
     * Get cookie
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getCookie($key, $default = null): mixed;

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
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getServer($key, $default = null): mixed;

    /**
     * Get body
     * @return string
     */
    public function getBody(): string;

}