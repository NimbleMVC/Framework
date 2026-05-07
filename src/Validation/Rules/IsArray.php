<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Strict array type check.
 */
class IsArray extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_array($value)) {
            $this->fail($this->msg('isArray'));
        }
    }

}
