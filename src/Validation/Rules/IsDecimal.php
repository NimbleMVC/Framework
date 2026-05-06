<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class IsDecimal extends AbstractRule
{

    public function __construct(private readonly int $maxPlaces = 2)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        if (is_array($options)) {
            return new static((int)($options['maxPlaces'] ?? 2));
        }

        return new static();
    }

    public function validate(mixed $value): void
    {
        $normalized = str_replace(',', '.', (string)$value);

        if (!is_numeric($normalized)) {
            $this->fail($this->msg('isNumeric'));
        }

        if (!str_contains($normalized, '.')) {
            return;
        }

        $decimalPart = explode('.', $normalized)[1];

        if (strlen($decimalPart) > $this->maxPlaces) {
            $this->fail(str_replace(':decimal', (string)$this->maxPlaces, $this->msg('decimalMax')));
        }
    }

    public function message(): string
    {
        return $this->msg('isNumeric');
    }

}
