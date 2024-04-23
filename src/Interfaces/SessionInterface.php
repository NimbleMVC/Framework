<?php

namespace Nimblephp\framework\Interfaces;

interface SessionInterface
{

    /**
     * Init session
     */
    public function __construct();

    /**
     * Set session
     * @param $key
     * @param $value
     * @return void
     */
    public function set($key, $value);

    /**
     * Get session
     * @param $key
     * @return mixed
     */
    public function get($key): mixed;

    /**
     * Exists session
     * @param $key
     * @return bool
     */
    public function exists($key): bool;

    /**
     * Remove session
     * @param $key
     * @return void
     */
    public function remove($key): void;

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