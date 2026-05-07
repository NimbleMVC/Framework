<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class In extends AbstractRule
{

    public function __construct(private array $allowed)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((array)$options);
    }

    public function validate(mixed $value): void
    {
        if (!in_array($value, $this->allowed, true)) {
            $this->fail(str_replace(':values', implode(', ', $this->allowed), $this->msg('in')));
        }
    }

}
