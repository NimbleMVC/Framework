<?php

namespace NimblePHP\Framework\Exception;

use Throwable;

/**
 * Hidden exception
 */
class HiddenException extends NimbleException
{

    protected string $hiddenMessage;

    public function __construct(string $message = 'System error', int $code = 500, ?Throwable $previous = null)
    {
        $showMessage = 'System error';
        $this->hiddenMessage = $message;

        if (isset($_ENV['DEBUG']) && $_ENV['DEBUG']) {
            $showMessage = $message;
        }

        parent::__construct($showMessage, $code, $previous);
    }

    public function getHiddenMessage(): string
    {
        return $this->hiddenMessage;
    }

}