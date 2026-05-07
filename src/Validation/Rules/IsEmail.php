<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class IsEmail extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->fail($this->msg('isEmail'));
        }
    }

    public function message(): string
    {
        return $this->msg('isEmail');
    }

}
