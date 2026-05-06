<?php

namespace NimblePHP\Framework\Validation\Rules;

use LogicException;
use NimblePHP\Framework\Validation\AbstractRule;
use NimblePHP\Framework\Validation\ContextAwareRuleInterface;

/**
 * Required when another field is present (non-empty).
 */
class RequiredWith extends AbstractRule implements ContextAwareRuleInterface
{

    public function __construct(private readonly string $otherField)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((string)$options);
    }

    public function validate(mixed $value): void
    {
        throw new LogicException(self::class . ' requires a validation context (use it inside Validator::validate)');
    }

    public function validateInContext(mixed $value, string $field, array $data): void
    {
        if ($this->isEmpty($data[$this->otherField] ?? null)) {
            return;
        }

        if ($this->isEmpty($value)) {
            $this->fail(str_replace(':field', $this->otherField, $this->msg('requiredWith')));
        }
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

}
