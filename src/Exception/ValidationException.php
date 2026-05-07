<?php

namespace NimblePHP\Framework\Exception;

use Throwable;

/**
 * Validation exception
 */
class ValidationException extends NimbleException
{

    /**
     * Per-field error map (field name => message)
     * @var array<string, string>
     */
    private array $fieldErrors;

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array<string, string> $fieldErrors Optional field => message map (used by API responses)
     */
    public function __construct(string $message, int $code = 400, ?Throwable $previous = null, array $fieldErrors = [])
    {
        parent::__construct($message, $code, $previous);

        $this->fieldErrors = $fieldErrors;
    }

    /**
     * Get the per-field error map (empty when not set)
     * @return array<string, string>
     */
    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }

}
