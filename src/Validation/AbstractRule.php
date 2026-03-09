<?php

namespace NimblePHP\Framework\Validation;

use NimblePHP\Framework\Exception\ValidationException;

/**
 * Base class for custom validation rules
 */
abstract class AbstractRule implements RuleInterface
{

    /**
     * Validate the given value.
     * Call $this->fail() or throw a ValidationException to indicate failure.
     * @param mixed $value
     * @return void
     * @throws ValidationException
     */
    abstract public function validate(mixed $value): void;

    /**
     * Default error message
     * @return string
     */
    public function message(): string
    {
        return 'Validation failed.';
    }

    /**
     * Throw a ValidationException with the rule's default message
     * @param string|null $message
     * @return never
     * @throws ValidationException
     */
    protected function fail(?string $message = null): never
    {
        throw new ValidationException($message ?? $this->message());
    }

}
