<?php

namespace NimblePHP\Framework\Exception;

use Throwable;

/**
 * Hidden exception
 */
class HiddenException extends NimbleException
{

    /**
     * Hidden message
     * @var string
     */
    protected string $hiddenMessage;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = 'System error', int $code = 500, ?Throwable $previous = null)
    {
        $showMessage = 'System error';
        $this->hiddenMessage = $message;

        if (isset($_ENV['DEBUG']) && $_ENV['DEBUG']) {
            $showMessage = $message;
        }

        parent::__construct($showMessage, $code, $previous);
    }

    /**
     * Get hidden message
     * @return string
     */
    public function getHiddenMessage(): string
    {
        return $this->hiddenMessage;
    }

}