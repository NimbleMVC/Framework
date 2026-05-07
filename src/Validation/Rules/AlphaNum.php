<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Letters (Unicode) and digits only.
 */
class AlphaNum extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_string($value) || !preg_match('/^[\p{L}\p{N}]+$/u', $value)) {
            $this->fail($this->msg('alphaNum'));
        }
    }

}
