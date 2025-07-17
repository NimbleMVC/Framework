<?php

namespace NimblePHP\Framework\Interfaces;

interface SessionMiddlewareInterface
{

    /**
     * Before set
     * @param string $key
     * @param mixed &$value
     * @return void
     */
    public function beforeSet(string $key, &$value): void;

    /**
     * After set
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function afterSet(string $key, $value): void;

    /**
     * Before get
     * @param string $key
     * @param mixed &$value
     * @return void
     */
    public function beforeGet(string $key, &$value): void;

    /**
     * After get
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function afterGet(string $key, $value): void;

    /**
     * Before destroy
     * @return void
     */
    public function beforeDestroy(): void;

    /**
     * After destroy
     * @return void
     */
    public function afterDestroy(): void;

    /**
     * Before regenerate
     * @return void
     */
    public function beforeRegenerate(): void;

    /**
     * After regenerate
     * @return void
     */
    public function afterRegenerate(): void;

}