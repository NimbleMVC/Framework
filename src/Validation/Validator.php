<?php

namespace NimblePHP\Framework\Validation;

use Closure;
use Exception;
use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Kernel;

/**
 * Standalone data validator for use in controllers, models, forms and anywhere else.
 *
 * ## Array syntax
 * ```php
 * $result = Validator::validate($_POST, [
 *     'name'   => ['required', 'length' => ['min' => 3, 'max' => 50]],
 *     'email'  => ['required', 'isEmail'],
 *     'age'    => ['required', 'isInteger', 'min' => 18, 'max' => 120],
 *     'price'  => ['nullable', 'isDecimal' => ['maxPlaces' => 2]],
 *     'role'   => ['required', 'in' => ['admin', 'user', 'guest']],
 *     'code'   => ['required', 'regex' => '/^[A-Z]{3}\d{3}$/'],
 *     'accept' => ['checked'],
 *     'nick'   => [function(mixed $value) { if (str_contains($value, ' ')) throw new \Exception('No spaces allowed'); }],
 *     'token'  => ['required', new MyCustomRule()],
 * ]);
 *
 * if ($result->fails()) {
 *     $errors = $result->errors();
 * }
 *
 * // Throw ValidationException on first failure:
 * $result->throwIfFailed();
 * // or shorthand:
 * Validator::validateOrFail($_POST, $rules);
 * ```
 *
 * ## Supported built-in rules
 * | Rule key          | Options / value                                  |
 * |-------------------|--------------------------------------------------|
 * | `required`        | –                                                |
 * | `nullable`        | Skip remaining rules when value is empty/null    |
 * | `checked`         | Truthy value (checkbox)                          |
 * | `isEmail`         | –                                                |
 * | `isInteger`       | –                                                |
 * | `isDecimal`       | `['maxPlaces' => N]` (default 2)                 |
 * | `isNumeric`       | –                                                |
 * | `length`          | `['min' => N, 'max' => N]`                       |
 * | `minLength`       | integer                                          |
 * | `maxLength`       | integer                                          |
 * | `min`             | number                                           |
 * | `max`             | number                                           |
 * | `in`              | array of allowed values                          |
 * | `notIn`           | array of forbidden values                        |
 * | `regex`           | regex pattern string                             |
 * | `same`            | name of another field whose value must match     |
 * | `enum`            | FQCN of a PHP backed/unit enum                   |
 * | Closure           | `function(mixed $value): void` – throw on error  |
 * | RuleInterface     | Custom rule object                               |
 */
class Validator
{

    /**
     * Data being validated
     * @var array
     */
    private array $data;

    /**
     * Collected errors after run
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * Rules defined via fluent API
     * @var array
     */
    private array $fluentRules = [];

    /**
     * Current field for fluent API
     * @var string|null
     */
    private ?string $currentField = null;

    /**
     * Translation messages cache
     * @var array<string, string>
     */
    private static array $messages = [];

    // -------------------------------------------------------------------------
    // Factory / static helpers
    // -------------------------------------------------------------------------

    /**
     * Create a new Validator instance for the given data
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Validate data against rules and return a ValidationResult.
     * This is the primary entry point for the array syntax.
     *
     * @param array $data    Associative array of input values (e.g. $_POST)
     * @param array $rules   Field => rule list map
     * @return ValidationResult
     */
    public static function validate(array $data, array $rules): ValidationResult
    {
        return (new self($data))->run($rules);
    }

    /**
     * Same as validate() but throws a ValidationException when validation fails.
     *
     * @throws ValidationException
     */
    public static function validateOrFail(array $data, array $rules): ValidationResult
    {
        $result = self::validate($data, $rules);
        $result->throwIfFailed();

        return $result;
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // -------------------------------------------------------------------------
    // Fluent API
    // -------------------------------------------------------------------------

    /**
     * Begin rule definition for the given field (fluent API)
     */
    public function field(string $name): self
    {
        $this->currentField = $name;

        if (!isset($this->fluentRules[$name])) {
            $this->fluentRules[$name] = [];
        }

        return $this;
    }

    /** Field must not be empty (fluent) */
    public function required(): self
    {
        return $this->addFluentRule('required');
    }

    /** Allow null/empty – skip following rules when empty (fluent) */
    public function nullable(): self
    {
        return $this->addFluentRule('nullable');
    }

    /** Value must be truthy – e.g. checkbox (fluent) */
    public function checked(): self
    {
        return $this->addFluentRule('checked');
    }

    /** Must be a valid e-mail address (fluent) */
    public function isEmail(): self
    {
        return $this->addFluentRule('isEmail');
    }

    /** Must be an integer (fluent) */
    public function isInteger(): self
    {
        return $this->addFluentRule('isInteger');
    }

    /** Must be numeric (fluent) */
    public function isNumeric(): self
    {
        return $this->addFluentRule('isNumeric');
    }

    /**
     * Must be a decimal number (fluent)
     * @param int $maxPlaces Maximum decimal places (default 2)
     */
    public function isDecimal(int $maxPlaces = 2): self
    {
        return $this->addFluentRule('isDecimal', ['maxPlaces' => $maxPlaces]);
    }

    /**
     * String length constraints (fluent)
     * @param int|null $min Minimum length
     * @param int|null $max Maximum length
     */
    public function length(?int $min = null, ?int $max = null): self
    {
        return $this->addFluentRule('length', array_filter(['min' => $min, 'max' => $max], fn($v) => $v !== null));
    }

    /** Minimum string length (fluent) */
    public function minLength(int $min): self
    {
        return $this->addFluentRule('minLength', $min);
    }

    /** Maximum string length (fluent) */
    public function maxLength(int $max): self
    {
        return $this->addFluentRule('maxLength', $max);
    }

    /** Minimum numeric value (fluent) */
    public function min(int|float $min): self
    {
        return $this->addFluentRule('min', $min);
    }

    /** Maximum numeric value (fluent) */
    public function max(int|float $max): self
    {
        return $this->addFluentRule('max', $max);
    }

    /**
     * Value must be one of the given values (fluent)
     * @param array $values
     */
    public function in(array $values): self
    {
        return $this->addFluentRule('in', $values);
    }

    /**
     * Value must not be in the given list (fluent)
     */
    public function notIn(array $values): self
    {
        return $this->addFluentRule('notIn', $values);
    }

    /**
     * Value must match the regex pattern (fluent)
     */
    public function regex(string $pattern): self
    {
        return $this->addFluentRule('regex', $pattern);
    }

    /**
     * Value must match the value of another field (fluent)
     */
    public function same(string $otherField): self
    {
        return $this->addFluentRule('same', $otherField);
    }

    /**
     * Value must be a valid case name of the given PHP enum (fluent)
     * @param string $enumClass FQCN of the enum
     */
    public function enum(string $enumClass): self
    {
        return $this->addFluentRule('enum', $enumClass);
    }

    /**
     * Add a custom closure rule (fluent)
     * The closure receives the field value and should throw on failure.
     */
    public function addRule(Closure|RuleInterface $rule): self
    {
        $this->assertCurrentField();
        $this->fluentRules[$this->currentField][] = $rule;

        return $this;
    }

    /**
     * Execute fluent rules and return ValidationResult
     */
    public function run(?array $rules = null): ValidationResult
    {
        $rules = $rules ?? $this->fluentRules;
        $this->errors = [];

        foreach ($rules as $fieldKey => $ruleList) {
            $value = $this->getDataByKey($fieldKey);
            $nullable = false;

            foreach ($ruleList as $ruleType => $rule) {
                try {
                    if ($rule instanceof RuleInterface) {
                        $rule->validate($value);
                    } elseif ($rule instanceof Closure) {
                        $rule($value);
                    } elseif (is_string($ruleType)) {
                        // key => value pair, e.g. 'length' => ['min' => 3]
                        if ($ruleType === 'nullable') {
                            $nullable = true;

                            if ($this->isEmpty($value)) {
                                break;
                            }
                        } else {
                            $this->runBuiltIn($ruleType, $rule, $value, $fieldKey);
                        }
                    } elseif (is_int($ruleType) && is_string($rule)) {
                        // indexed, e.g. 0 => 'required'
                        if ($rule === 'nullable') {
                            $nullable = true;

                            if ($this->isEmpty($value)) {
                                break;
                            }
                        } else {
                            $this->runBuiltIn($rule, null, $value, $fieldKey);
                        }
                    }
                } catch (ValidationException $e) {
                    $this->errors[$fieldKey] = $e->getMessage();
                    break;
                }
            }
        }

        return new ValidationResult($this->errors);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Execute a single built-in rule
     * @throws Exception
     */
    private function runBuiltIn(string $name, mixed $options, mixed $value, string $fieldKey): void
    {
        switch ($name) {
            case 'required':
                if ($this->isEmpty($value)) {
                    throw new Exception($this->msg('required'));
                }
                break;

            case 'checked':
                if (!(bool)trim((string)($value ?? ''))) {
                    throw new Exception($this->msg('checked'));
                }
                break;

            case 'isEmail':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception($this->msg('isEmail'));
                }
                break;

            case 'isInteger':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    throw new Exception($this->msg('isInteger'));
                }
                break;

            case 'isNumeric':
                if (!is_numeric($value)) {
                    throw new Exception($this->msg('isNumeric'));
                }
                break;

            case 'isDecimal':
                $normalized = str_replace(',', '.', (string)$value);

                if (!is_numeric($normalized)) {
                    throw new Exception($this->msg('isNumeric'));
                }

                if (!str_contains($normalized, '.')) {
                    break;
                }

                $maxPlaces = is_array($options) ? ($options['maxPlaces'] ?? 2) : 2;
                $decimalPart = explode('.', $normalized)[1];

                if (strlen($decimalPart) > $maxPlaces) {
                    throw new Exception(str_replace(':decimal', (string)$maxPlaces, $this->msg('decimalMax')));
                }
                break;

            case 'length':
                $min = is_array($options) ? ($options['min'] ?? null) : null;
                $max = is_array($options) ? ($options['max'] ?? null) : null;
                $len = mb_strlen((string)($value ?? ''));

                if ($min !== null && $len < $min) {
                    throw new Exception(str_replace(':length', (string)$min, $this->msg('minLength')));
                }

                if ($max !== null && $len > $max) {
                    throw new Exception(str_replace(':length', (string)$max, $this->msg('maxLength')));
                }
                break;

            case 'minLength':
                $min = (int)$options;

                if (mb_strlen((string)($value ?? '')) < $min) {
                    throw new Exception(str_replace(':length', (string)$min, $this->msg('minLength')));
                }
                break;

            case 'maxLength':
                $max = (int)$options;

                if (mb_strlen((string)($value ?? '')) > $max) {
                    throw new Exception(str_replace(':length', (string)$max, $this->msg('maxLength')));
                }
                break;

            case 'min':
                if (!is_numeric($value) || (float)$value < (float)$options) {
                    throw new Exception(str_replace(':min', (string)$options, $this->msg('min')));
                }
                break;

            case 'max':
                if (!is_numeric($value) || (float)$value > (float)$options) {
                    throw new Exception(str_replace(':max', (string)$options, $this->msg('max')));
                }
                break;

            case 'in':
                $allowed = (array)$options;

                if (!in_array($value, $allowed, true)) {
                    throw new Exception(str_replace(':values', implode(', ', $allowed), $this->msg('in')));
                }
                break;

            case 'notIn':
                $forbidden = (array)$options;

                if (in_array($value, $forbidden, true)) {
                    throw new Exception(str_replace(':values', implode(', ', $forbidden), $this->msg('notIn')));
                }
                break;

            case 'regex':
                if (!preg_match((string)$options, (string)($value ?? ''))) {
                    throw new Exception($this->msg('regex'));
                }
                break;

            case 'same':
                $otherValue = $this->getDataByKey((string)$options);

                if ($value !== $otherValue) {
                    throw new Exception(str_replace(':field', (string)$options, $this->msg('same')));
                }
                break;

            case 'enum':
                /** @var string $enumClass */
                $enumClass = $options;
                $names = array_column($enumClass::cases(), 'name');

                if (!in_array($value, $names, true)) {
                    throw new Exception(str_replace(':values', implode(', ', $names), $this->msg('in')));
                }
                break;
        }
    }

    /**
     * Retrieve a nested value from the data array.
     * Keys may use dot-notation: "user.email" → $data['user']['email']
     */
    private function getDataByKey(string $key): mixed
    {
        $parts = preg_split('/[.\/]/', $key);
        $value = $this->data;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

    private function isEmpty(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    private function addFluentRule(string $ruleName, mixed $options = null): self
    {
        $this->assertCurrentField();

        if ($options === null) {
            $this->fluentRules[$this->currentField][] = $ruleName;
        } else {
            $this->fluentRules[$this->currentField][$ruleName] = $options;
        }

        return $this;
    }

    private function assertCurrentField(): void
    {
        if ($this->currentField === null) {
            throw new \LogicException('Call field() before adding rules');
        }
    }

    /**
     * Resolve a translated message, falling back to the built-in English string.
     */
    private function msg(string $key): string
    {
        static $fallback = [
            'required'   => 'This field is required.',
            'checked'    => 'This field must be checked.',
            'isEmail'    => 'Invalid e-mail address.',
            'isInteger'  => 'This field must be an integer.',
            'isNumeric'  => 'This field must be a number.',
            'decimalMax' => 'This field may have at most :decimal decimal places.',
            'minLength'  => 'This field must be at least :length characters.',
            'maxLength'  => 'This field may not exceed :length characters.',
            'min'        => 'This field must be at least :min.',
            'max'        => 'This field may not exceed :max.',
            'in'         => 'Invalid value. Allowed: :values.',
            'notIn'      => 'This value is not allowed.',
            'regex'      => 'This field has an invalid format.',
            'same'       => 'This field must match :field.',
        ];

        try {
            $translationKey = 'framework.validation.' . $key;
            $translation = Kernel::$serviceContainer->get('translation');
            $translated = $translation->translate($translationKey);

            // translate() returns the key itself when not found
            if ($translated !== $translationKey) {
                return $translated;
            }
        } catch (\Throwable) {
            // Translation not available (e.g. outside HTTP context)
        }

        return $fallback[$key] ?? 'Validation error.';
    }

}
