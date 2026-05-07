<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Loose boolean check: accepts true/false, 1/0, "1"/"0", "true"/"false".
 */
class Boolean extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $this->fail($this->msg('boolean'));
        }
    }

}
