<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Strict string type check (rejects numbers, booleans, arrays even if cast-compatible).
 */
class IsString extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_string($value)) {
            $this->fail($this->msg('isString'));
        }
    }

}
