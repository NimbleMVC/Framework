<?php

namespace Nimblephp\framework\Exception;

use Throwable;

/**
 * Not found exception
 */
class NotFoundException extends NimbleException
{

    public function __construct(string $message = "Not found", int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}