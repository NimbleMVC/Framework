<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class IsNumeric extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_numeric($value)) {
            $this->fail($this->msg('isNumeric'));
        }
    }

    public function message(): string
    {
        return $this->msg('isNumeric');
    }

}
