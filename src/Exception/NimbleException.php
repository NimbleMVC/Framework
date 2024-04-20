<?php

namespace Nimblephp\framework\Exception;

use Exception;
use Throwable;

/**
 * Main exception
 */
class NimbleException extends Exception
{

    public function __construct(string $message = "System error", int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}