<?php

namespace NimblePHP\Framework\Validation;

use NimblePHP\Framework\Exception\ValidationException;

/**
 * Holds the result of a validation run
 */
class ValidationResult
{

    /**
     * @param array<string, string> $errors Field name => error message
     */
    public function __construct(private readonly array $errors = [])
    {
    }

    /**
     * Returns true when at least one field failed validation
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns true when all fields passed validation
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * All validation errors keyed by field name
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * First error message for a specific field, or null if none
     * @param string $field
     * @return string|null
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Returns true if the given field has a validation error
     * @param string $field
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Throw a ValidationException when validation failed
     * The exception message lists all errors as JSON.
     * @return void
     * @throws ValidationException
     */
    public function throwIfFailed(): void
    {
        if ($this->fails()) {
            $first = array_values($this->errors)[0];

            throw new ValidationException($first);
        }
    }

}
