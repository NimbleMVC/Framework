<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Value is a parseable date string (any format strtotime understands).
 */
class Date extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_string($value) || $value === '') {
            $this->fail($this->msg('date'));
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            $this->fail($this->msg('date'));
        }
    }

}
