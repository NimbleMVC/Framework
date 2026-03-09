<?php

namespace NimblePHP\Framework\Validation;

/**
 * Interface for custom validation rules
 */
interface RuleInterface
{

    /**
     * Validate the given value.
     * Throw an exception with a message if validation fails.
     * @param mixed $value
     * @return void
     * @throws \Exception
     */
    public function validate(mixed $value): void;

    /**
     * Default error message when validation fails
     * @return string
     */
    public function message(): string;

}
