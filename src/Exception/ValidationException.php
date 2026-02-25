<?php

namespace NimblePHP\Framework\Exception;

use Throwable;

/**
 * Validation exception
 */
class ValidationException extends NimbleException
{

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}