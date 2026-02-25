<?php

namespace NimblePHP\Framework\Exception;

use NimblePHP\Framework\Config;
use Throwable;

/**
 * Database exception
 */
class DatabaseException extends HiddenException
{

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = 'Database error', int $code = 500, ?Throwable $previous = null)
    {
        $showMessage = 'Database error';
        $this->hiddenMessage = $message;

        if (Config::get('DEBUG', false)) {
            $showMessage = $message;
        }

        parent::__construct($showMessage, $code, $previous);
    }

}