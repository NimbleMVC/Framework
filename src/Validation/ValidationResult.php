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
    public function __construct(private readonly array $errors = []) {}

    /**
     * Returns true when at least one field failed validation
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns true when all fields passed validation
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
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Returns true if the given field has a validation error
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Throw a ValidationException when validation failed
     * The exception message lists all errors as JSON.
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
