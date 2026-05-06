<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class Required extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if ($value === null) {
            $this->fail($this->msg('required'));
        }

        if (is_string($value) && trim($value) === '') {
            $this->fail($this->msg('required'));
        }

        if (is_array($value) && empty($value)) {
            $this->fail($this->msg('required'));
        }
    }

    public function message(): string
    {
        return $this->msg('required');
    }

}
