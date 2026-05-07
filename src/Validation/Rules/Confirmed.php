<?php

namespace NimblePHP\Framework\Validation\Rules;

use LogicException;
use NimblePHP\Framework\Validation\AbstractRule;
use NimblePHP\Framework\Validation\ContextAwareRuleInterface;

/**
 * Field requires a `<field>_confirmation` companion with the same value.
 */
class Confirmed extends AbstractRule implements ContextAwareRuleInterface
{

    public function validate(mixed $value): void
    {
        throw new LogicException(self::class . ' requires a validation context (use it inside Validator::validate)');
    }

    public function validateInContext(mixed $value, string $field, array $data): void
    {
        $companion = $field . '_confirmation';
        $other = $data[$companion] ?? null;

        if ($value !== $other) {
            $this->fail($this->msg('confirmed'));
        }
    }

}
