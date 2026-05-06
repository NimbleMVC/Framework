<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class Regex extends AbstractRule
{

    public function __construct(private readonly string $pattern)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((string)$options);
    }

    public function validate(mixed $value): void
    {
        if (!preg_match($this->pattern, (string)($value ?? ''))) {
            $this->fail($this->msg('regex'));
        }
    }

}
