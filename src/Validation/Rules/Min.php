<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class Min extends AbstractRule
{

    public function __construct(private int|float $min)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static(is_numeric($options) ? $options + 0 : 0);
    }

    public function validate(mixed $value): void
    {
        if (!is_numeric($value) || (float)$value < (float)$this->min) {
            $this->fail(str_replace(':min', (string)$this->min, $this->msg('min')));
        }
    }

}
