<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class IsInteger extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->fail($this->msg('isInteger'));
        }
    }

    public function message(): string
    {
        return $this->msg('isInteger');
    }

}
