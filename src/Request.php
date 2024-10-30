<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\RequestInterface;

/**
 * Request
 */
class Request implements RequestInterface
{

    /**
     * GET
     * @var array
     */
    private array $query;

    /**
     * POST
     * @var array
     */
    private array $post;

    /**
     * Cookies
     * @var array
     */
    private array $cookies;

    /**
     * Files
     * @var array
     */
    private array $files;

    /**
     * Headers
     * @var array
     */
    private array $headers;

    /**
     * Server
     * @var array
     */
    private array $server;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->headers = $this->getAllHeaders();
    }

    /**
     * Get all headers
     * @return array
     */
    private function getAllHeaders(): array
    {
        if (!function_exists('getallheaders')) {
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }

            return $headers;
        } else {
            return getallheaders();
        }
    }

    /**
     * Get query
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getQuery($key, $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get post
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getPost($key, $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get cookie
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getCookie($key, $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get file
     * @param $key
     * @return mixed|null
     */
    public function getFile($key): mixed
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get header
     * @param $key
     * @return mixed
     */
    public function getHeader($key): mixed
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Get method
     * @return string
     */
    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'];
    }

    /**
     * Get URI
     * @return string
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '';
    }

    /**
     * Get server
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getServer($key, $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get body
     * @return string
     */
    public function getBody(): string
    {
        return file_get_contents('php://input');
    }

}