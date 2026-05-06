<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class Enum extends AbstractRule
{

    /**
     * @param string $enumClass FQCN of a backed/unit enum
     */
    public function __construct(private readonly string $enumClass)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((string)$options);
    }

    public function validate(mixed $value): void
    {
        $names = array_column($this->enumClass::cases(), 'name');

        if (!in_array($value, $names, true)) {
            $this->fail(str_replace(':values', implode(', ', $names), $this->msg('in')));
        }
    }

}
