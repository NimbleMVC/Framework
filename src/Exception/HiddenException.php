<?php

namespace NimblePHP\Framework\Exception;

use Throwable;

/**
 * Hidden exception
 */
class HiddenException extends NimbleException
{

    protected string $hiddenMessage;

    public function __construct(string $message = "System error", int $code = 500, ?Throwable $previous = null)
    {
        $this->hiddenMessage = $message;

        parent::__construct('System error', $code, $previous);
    }

    public function getHiddenMessage(): string
    {
        return $this->hiddenMessage;
    }

}