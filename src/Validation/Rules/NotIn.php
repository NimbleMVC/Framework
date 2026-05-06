<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class NotIn extends AbstractRule
{

    public function __construct(private readonly array $forbidden)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((array)$options);
    }

    public function validate(mixed $value): void
    {
        if (in_array($value, $this->forbidden, true)) {
            $this->fail(str_replace(':values', implode(', ', $this->forbidden), $this->msg('notIn')));
        }
    }

}
