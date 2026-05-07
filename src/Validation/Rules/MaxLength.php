<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class MaxLength extends AbstractRule
{

    public function __construct(private int $max)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((int)$options);
    }

    public function validate(mixed $value): void
    {
        if (mb_strlen((string)($value ?? '')) > $this->max) {
            $this->fail(str_replace(':length', (string)$this->max, $this->msg('maxLength')));
        }
    }

}
