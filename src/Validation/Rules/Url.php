<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class Url extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->fail($this->msg('url'));
        }
    }

}
