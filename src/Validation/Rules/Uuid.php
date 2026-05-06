<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Validates RFC 4122 UUID (any version, dashed canonical form).
 */
class Uuid extends AbstractRule
{

    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function validate(mixed $value): void
    {
        if (!is_string($value) || !preg_match(self::PATTERN, $value)) {
            $this->fail($this->msg('uuid'));
        }
    }

}
