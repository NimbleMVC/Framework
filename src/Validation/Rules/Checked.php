<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class Checked extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!(bool)trim((string)($value ?? ''))) {
            $this->fail($this->msg('checked'));
        }
    }

    public function message(): string
    {
        return $this->msg('checked');
    }

}
