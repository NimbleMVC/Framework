<?php

namespace Nimblephp\framework\Interfaces;

/**
 * Response interface
 */
interface ResponseInterface
{

    /**
     * Set content
     * @param $content
     * @return void
     */
    public function setContent($content): void;

    /**
     * Get content
     * @return string
     */
    public function getContent(): string;

    /**
     * Set status code
     * @param int $code
     * @param string $text
     * @return void
     */
    public function setStatusCode(int $code, string $text = ''): void;

    /**
     * Get status code
     * @return int
     */
    public function getStatusCode(): int;


    /**
     * Add header
     * @param string $name
     * @param string $value
     * @return void
     */
    public function addHeader(string $name, string $value): void;

    /**
     * Send response
     * @return void
     */
    public function send(): void;

    /**
     * Redirect
     * @param string $url
     * @param int $statusCode
     * @return never
     */
    public function redirect(string $url, int $statusCode = 302): never;

}