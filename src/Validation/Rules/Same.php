<?php

namespace NimblePHP\Framework\Validation\Rules;

use LogicException;
use NimblePHP\Framework\Validation\AbstractRule;
use NimblePHP\Framework\Validation\ContextAwareRuleInterface;

class Same extends AbstractRule implements ContextAwareRuleInterface
{

    public function __construct(private readonly string $otherField)
    {
    }

    public static function fromOptions(mixed $options): static
    {
        return new static((string)$options);
    }

    public function validate(mixed $value): void
    {
        throw new LogicException(self::class . ' requires a validation context (use it inside Validator::validate)');
    }

    public function validateInContext(mixed $value, string $field, array $data): void
    {
        $other = $this->getDataByKey($data, $this->otherField);

        if ($value !== $other) {
            $this->fail(str_replace(':field', $this->otherField, $this->msg('same')));
        }
    }

    private function getDataByKey(array $data, string $key): mixed
    {
        $parts = preg_split('/[.\/]/', $key);
        $value = $data;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

}
