<?php

namespace NimblePHP\Framework\Exception;

use Exception;
use Throwable;

/**
 * Main exception
 */
class NimbleException extends Exception
{

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "System error", int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}