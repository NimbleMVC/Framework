<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * URL-friendly slug: lowercase letters, digits and single dashes (no leading/trailing dash).
 */
class Slug extends AbstractRule
{

    private const string PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function validate(mixed $value): void
    {
        if (!is_string($value) || !preg_match(self::PATTERN, $value)) {
            $this->fail($this->msg('slug'));
        }
    }

}
