<?php

namespace NimblePHP\Framework\Validation\Rules;

use NimblePHP\Framework\Validation\AbstractRule;

/**
 * Value is a string containing valid JSON.
 */
class Json extends AbstractRule
{

    public function validate(mixed $value): void
    {
        if (!is_string($value)) {
            $this->fail($this->msg('json'));
        }

        if (!json_validate($value)) {
            $this->fail($this->msg('json'));
        }
    }

}
