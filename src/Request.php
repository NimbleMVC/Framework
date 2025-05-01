<?php

namespace NimblePHP\Framework;

use Krzysztofzylka\Arrays\Arrays;
use NimblePHP\Framework\Interfaces\RequestInterface;

/**
 * Request
 */
readonly class Request implements RequestInterface
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
     * Get all query
     * @param bool $protect htmlspecialhars
     * @return array
     */
    public function getAllQuery(bool $protect = true): array
    {
        return $protect
            ? Arrays::htmlSpecialChars($this->query)
            : $this->query;
    }

    /**
     * Get query
     * @param string $key
     * @param mixed $default
     * @param bool $protect htmlspecialchars
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null, bool $protect = true): mixed
    {
        return $this->getValue($this->query, $key, $default, $protect);
    }

    /**
     * Isset query
     * @param string $key
     * @return bool
     */
    public function issetQuery(string $key): bool
    {
        return isset($this->query[$key]);
    }

    /**
     * Get all post
     * @param bool $protect htmlspecialhars
     * @return array
     */
    public function getAllPost(bool $protect = true): array
    {
        return $protect
            ? Arrays::htmlSpecialChars($this->post)
            : $this->post;
    }

    /**
     * Get post
     * @param string $key
     * @param mixed $default
     * @param bool $protect htmlspecialchars
     * @return mixed
     */
    public function getPost(string $key, mixed $default = null, bool $protect = true): mixed
    {
        return $this->getValue($this->post, $key, $default, $protect);
    }

    /**
     * Isset post
     * @param string $key
     * @return bool
     */
    public function issetPost(string $key): bool
    {
        return isset($this->post[$key]);
    }

    /**
     * Get cookie
     * @param string $key
     * @param mixed $default
     * @param bool $protect htmlspecialchars
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null, bool $protect = true): mixed
    {
        return $this->getValue($this->cookies, $key, $default, $protect);
    }

    /**
     * Isset cookie
     * @param string $key
     * @return bool
     */
    public function issetCookie(string $key): bool
    {
        return isset($this->cookies[$key]);
    }

    /**
     * Get file
     * @param string $key
     * @return mixed|null
     */
    public function getFile(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get header
     * @param string $key
     * @return mixed
     */
    public function getHeader(string $key): mixed
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Get method
     * @return string
     */
    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
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
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getServer(string $key, mixed $default = null): mixed
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

    /**
     * Check if the request is an AJAX request
     * @return bool
     */
    public function isAjax(): bool
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH'])
            && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

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
     * Get data from array
     * @param array $source
     * @param string $key
     * @param mixed|null $default
     * @param bool $protect
     * @return mixed
     */
    private function getValue(array $source, string $key, mixed $default = null, bool $protect = true): mixed
    {
        if (!isset($source[$key])) {
            return $default;
        }

        $data = $source[$key];

        return $protect ? (is_array($data) ? Arrays::htmlSpecialChars($data) : htmlspecialchars($data)) : $data;
    }

}