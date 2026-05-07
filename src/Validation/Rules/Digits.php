<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Value consists of exactly N digits (e.g. PIN, postal code, phone segment).
 */
class Digits extends AbstractRule
{

    public function __construct(private readonly int $length)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((int)$options);
    }

    public function validate(mixed $value): void
    {
        $string = (string)($value ?? '');

        if (!preg_match('/^\d+$/', $string) || strlen($string) !== $this->length) {
            $this->fail(str_replace(':length', (string)$this->length, $this->msg('digits')));
        }
    }

}
