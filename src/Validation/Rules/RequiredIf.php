<?php

namespace NimblePHP\Framework\Validation\Rules;

use LogicException;
use NimblePHP\Framework\Validation\AbstractRule;
use NimblePHP\Framework\Validation\ContextAwareRuleInterface;

/**
 * Required when another field equals the given value.
 *
 * Options: `[other_field, expected_value]` or `['field' => 'name', 'value' => ...]`.
 */
class RequiredIf extends AbstractRule implements ContextAwareRuleInterface
{

    public function __construct(
        private readonly string $otherField,
        private readonly mixed $expectedValue
    )
    {
    }

    public static function fromOptions(mixed $options): static
    {
        if (is_array($options)) {
            if (array_is_list($options) && count($options) >= 2) {
                return new static((string)$options[0], $options[1]);
            }

            return new static((string)($options['field'] ?? ''), $options['value'] ?? null);
        }

        throw new LogicException('requiredIf rule expects [field, value] options');
    }

    public function validate(mixed $value): void
    {
        throw new LogicException(self::class . ' requires a validation context (use it inside Validator::validate)');
    }

    public function validateInContext(mixed $value, string $field, array $data): void
    {
        $actual = $data[$this->otherField] ?? null;

        if ($actual !== $this->expectedValue) {
            return;
        }

        if ($this->isEmpty($value)) {
            $this->fail(str_replace(
                [':field', ':value'],
                [$this->otherField, (string)$this->expectedValue],
                $this->msg('requiredIf')
            ));
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
