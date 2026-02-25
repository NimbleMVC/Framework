<?php

namespace NimblePHP\Framework\Exception;

use Throwable;

/**
 * Not found exception
 */
class NotFoundException extends NimbleException
{

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "Not found", int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}