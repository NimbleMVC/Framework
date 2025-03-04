<?php

namespace NimblePHP\Framework\Exception;

use Throwable;

/**
 * Hidden exception
 */
class DatabaseException extends HiddenException
{

    public function __construct(string $message = 'Database error', int $code = 500, ?Throwable $previous = null)
    {
        $showMessage = 'Database error';
        $this->hiddenMessage = $message;

        if (isset($_ENV['DEBUG']) && $_ENV['DEBUG']) {
            $showMessage = $message;
        }

        parent::__construct($showMessage, $code, $previous);
    }

}