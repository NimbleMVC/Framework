<?php

namespace NimblePHP\Framework\Validation\Rules;

use DateTimeImmutable;
use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Value matches a strict date format (e.g. 'Y-m-d', 'Y-m-d H:i:s').
 */
class DateFormat extends AbstractRule
{

    public function __construct(private readonly string $format)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((string)$options);
    }

    public function validate(mixed $value): void
    {
        if (!is_string($value)) {
            $this->fail(str_replace(':format', $this->format, $this->msg('dateFormat')));
        }

        $parsed = DateTimeImmutable::createFromFormat($this->format, $value);

        if ($parsed === false || $parsed->format($this->format) !== $value) {
            $this->fail(str_replace(':format', $this->format, $this->msg('dateFormat')));
        }
    }

}
