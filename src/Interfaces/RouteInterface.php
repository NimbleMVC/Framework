<?php

namespace NimblePHP\Framework\Interfaces;

/**
 * Route interface
 */
interface RouteInterface
{

    /**
     * Add route
     * @param string $name
     * @param string|null $controller
     * @param string|null $method
     * @return void
     */
    public static function addRoute(string $name, ?string $controller = null, ?string $method = null): void;

    /**
     * Reload routing
     * @return void
     */
    public function reload(): void;

    /**
     * Get controller
     * @return string
     */
    public function getController(): string;

    /**
     * Get method
     * @return string
     */
    public function getMethod(): string;

    /**
     * Get params
     * @return array
     */
    public function getParams(): array;

    /**
     * Set controller
     * @param ?string $controller
     * @return void
     */
    public function setController(?string $controller): void;

    /**
     * Set method
     * @param ?string $method
     * @return void
     */
    public function setMethod(?string $method): void;

    /**
     * Set params
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void;

    /**
     * Validate route
     * @return bool
     */
    public function validate(): bool;

}