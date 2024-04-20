<?php

namespace Nimblephp\framework\Exception;

use Throwable;

/**
 * Hidden exception
 */
class DatabaseException extends HiddenException
{

    public function __construct(string $message = "Database error", int $code = 500, ?Throwable $previous = null)
    {
        $this->hiddenMessage = $message;

        parent::__construct('Database error', $code, $previous);
    }

}