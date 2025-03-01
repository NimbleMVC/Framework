<?php

namespace NimblePHP\framework\Interfaces;

interface SessionInterface
{

    /**
     * Init session
     */
    public function __construct();

    /**
     * Set session
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self;

    /**
     * Get session
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Exists session
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Remove session
     * @param string $key
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Destroy session
     * @return void
     */
    public function destroy(): void;

    /**
     * Regenerate session id
     * @param bool|null $removeOldSession
     * @return void
     */
    public function regenerate(?bool $removeOldSession = false): void;

}