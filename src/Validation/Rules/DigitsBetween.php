<?php

namespace NimblePHP\Framework\Validation\Rules;

use LogicException;
use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Value consists of digits only, with length between min and max (inclusive).
 */
class DigitsBetween extends AbstractRule
{

    public function __construct(
        private readonly int $min,
        private readonly int $max
    )
    {
    }

    public static function fromOptions(mixed $options): static
    {
        if (is_array($options)) {
            if (array_is_list($options) && count($options) >= 2) {
                return new static((int)$options[0], (int)$options[1]);
            }

            return new static((int)($options['min'] ?? 0), (int)($options['max'] ?? 0));
        }

        throw new LogicException('digitsBetween rule expects [min, max] options');
    }

    public function validate(mixed $value): void
    {
        $string = (string)($value ?? '');
        $length = strlen($string);

        if (!preg_match('/^\d+$/', $string) || $length < $this->min || $length > $this->max) {
            $this->fail(str_replace(
                [':min', ':max'],
                [(string)$this->min, (string)$this->max],
                $this->msg('digitsBetween')
            ));
        }
    }

}
