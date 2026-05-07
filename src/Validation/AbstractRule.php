<?php

namespace NimblePHP\Framework\Validation;

use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Kernel;
use Throwable;

/**
 * Base class for custom validation rules
 */
abstract class AbstractRule implements RuleInterface
{

    /**
     * Validate the given value.
     * Call $this->fail() or throw a ValidationException to indicate failure.
     * @param mixed $value
     * @return void
     * @throws ValidationException
     */
    abstract public function validate(mixed $value): void;

    /**
     * Default error message
     * @return string
     */
    public function message(): string
    {
        return 'Validation failed.';
    }

    /**
     * Build the rule from raw options as passed in the array syntax
     * (e.g. `['length' => ['min' => 3]]` → `Length::fromOptions(['min' => 3])`).
     *
     * Default implementation forwards $options to the constructor as a single
     * argument (or no argument when null). Rules whose constructor needs
     * structured arguments should override this.
     */
    public static function fromOptions(mixed $options): static
    {
        return $options === null ? new static() : new static($options);
    }

    /**
     * Resolve a translated validation message by key. Returns the key itself
     * when the translation provider is unavailable.
     */
    protected function msg(string $key): string
    {
        try {
            $translationKey = 'framework.validation.' . $key;
            $translation = Kernel::$serviceContainer->get('translation');
            $translated = $translation->translate($translationKey);

            return $translated !== $translationKey ? $translated : $key;
        } catch (Throwable) {
            return $key;
        }
    }

    /**
     * Throw a ValidationException with the rule's default message
     * @param string|null $message
     * @return never
     * @throws ValidationException
     */
    protected function fail(?string $message = null): never
    {
        throw new ValidationException($message ?? $this->message());
    }

}
