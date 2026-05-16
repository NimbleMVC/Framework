<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

/**
 * @deprecated Use typed framework events instead.
 */
interface LogMiddlewareInterface
{

    /**
     *
     * @param string $message
     * @return void
     */
    public function beforeLog(string &$message): void;

    /**
     * @param array $logContent
     * @return void
     */
    public function afterLog(array &$logContent): void;

}
