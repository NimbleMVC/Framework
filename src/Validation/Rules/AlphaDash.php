<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Letters (Unicode), digits, dashes and underscores only (typical for usernames, identifiers).
 */
class AlphaDash extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_string($value) || !preg_match('/^[\p{L}\p{N}_-]+$/u', $value)) {
            $this->fail($this->msg('alphaDash'));
        }
    }

}
