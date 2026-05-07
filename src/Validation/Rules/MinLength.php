<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class MinLength extends AbstractRule
{

    public function __construct(private int $min)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((int)$options);
    }

    public function validate(mixed $value): void
    {
        if (mb_strlen((string)($value ?? '')) < $this->min) {
            $this->fail(str_replace(':length', (string)$this->min, $this->msg('minLength')));
        }
    }

}
