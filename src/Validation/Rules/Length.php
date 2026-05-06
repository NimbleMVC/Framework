<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

class Length extends AbstractRule
{

    public function __construct(
        private ?int $min = null,
        private ?int $max = null
    )
    {
    }

    public static function fromOptions(mixed $options): static
    {
        if (is_array($options)) {
            return new static(
                isset($options['min']) ? (int)$options['min'] : null,
                isset($options['max']) ? (int)$options['max'] : null
            );
        }

        return new static();
    }

    public function validate(mixed $value): void
    {
        $len = mb_strlen((string)($value ?? ''));

        if ($this->min !== null && $len < $this->min) {
            $this->fail(str_replace(':length', (string)$this->min, $this->msg('minLength')));
        }

        if ($this->max !== null && $len > $this->max) {
            $this->fail(str_replace(':length', (string)$this->max, $this->msg('maxLength')));
        }
    }

}
