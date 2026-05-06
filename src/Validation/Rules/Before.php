<?php

namespace NimblePHP\Framework\Validation\Rules;

use LogicException;
use NimblePHP\Framework\Validation\AbstractRule;
use NimblePHP\Framework\Validation\ContextAwareRuleInterface;

/**
 * Value (date) is strictly before the given reference. The reference is either
 * a literal date string (e.g. "2026-01-01", "now") or another field name in
 * the validated data.
 */
class Before extends AbstractRule implements ContextAwareRuleInterface
{

    public function __construct(private readonly string $reference)
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
        $valueTs = $this->toTimestamp($value);

        if ($valueTs === null) {
            $this->fail($this->msg('date'));
        }

        $referenceValue = array_key_exists($this->reference, $data) ? $data[$this->reference] : $this->reference;
        $referenceTs = $this->toTimestamp($referenceValue);

        if ($referenceTs === null || $valueTs >= $referenceTs) {
            $this->fail(str_replace(':date', (string)$this->reference, $this->msg('before')));
        }
    }

    private function toTimestamp(mixed $value): ?int
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $ts = strtotime($value);

        return $ts === false ? null : $ts;
    }

}
