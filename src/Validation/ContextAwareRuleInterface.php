<?php

namespace NimblePHP\Framework\Validation;

use Exception;

/**
 * Interface for validation rules that need access to the validation
 * context (other field values, current field name) – e.g. `same`,
 * `confirmed`, `requiredIf`, `gt`, `unique`.
 */
interface ContextAwareRuleInterface
{

    /**
     * Validate the value with full context.
     * Throw a ValidationException with a message if validation fails.
     *
     * @param mixed $value Value of the current field
     * @param string $field Name of the current field
     * @param array $data Full validated data set (for cross-field comparisons)
     * @return void
     * @throws Exception
     */
    public function validateInContext(mixed $value, string $field, array $data): void;

    /**
     * Default error message when validation fails
     * @return string
     */
    public function message(): string;

}
