<?php

namespace NimblePHP\Framework\Middleware\Abstracts;

use NimblePHP\Framework\Middleware\Interfaces\LogMiddlewareInterface;

/**
 * @deprecated Use typed framework events instead.
 */
abstract class AbstractLogMiddleware implements LogMiddlewareInterface
{

    public function beforeLog(string &$message): void
    {
    }

    public function afterLog(array &$logContent): void
    {
    }

}
