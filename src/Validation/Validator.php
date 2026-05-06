<?php

namespace NimblePHP\Framework\Validation;

use Closure;
use LogicException;
use NimblePHP\Framework\Exception\ValidationException;

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
 * Each built-in rule lives in its own class under `src/Validation/Rules/`.
 * Add a custom rule by implementing `RuleInterface` (or extending
 * `AbstractRule`); use `ContextAwareRuleInterface` if your rule needs the
 * other field values (e.g. cross-field comparisons).
 */
class Validator
{

    /**
     * Built-in rule registry: short name => FQCN of rule class.
     * @var array<string, class-string<RuleInterface|ContextAwareRuleInterface>>
     */
    private static array $builtInRules = [
        'required' => Rules\Required::class,
        'checked' => Rules\Checked::class,
        'isEmail' => Rules\IsEmail::class,
        'isInteger' => Rules\IsInteger::class,
        'isNumeric' => Rules\IsNumeric::class,
        'isDecimal' => Rules\IsDecimal::class,
        'length' => Rules\Length::class,
        'minLength' => Rules\MinLength::class,
        'maxLength' => Rules\MaxLength::class,
        'min' => Rules\Min::class,
        'max' => Rules\Max::class,
        'in' => Rules\In::class,
        'notIn' => Rules\NotIn::class,
        'regex' => Rules\Regex::class,
        'same' => Rules\Same::class,
        'enum' => Rules\Enum::class,
        'confirmed' => Rules\Confirmed::class,
        'different' => Rules\Different::class,
        'requiredIf' => Rules\RequiredIf::class,
        'requiredWith' => Rules\RequiredWith::class,
        'requiredWithout' => Rules\RequiredWithout::class,
        'date' => Rules\Date::class,
        'dateFormat' => Rules\DateFormat::class,
        'before' => Rules\Before::class,
        'after' => Rules\After::class,
        'url' => Rules\Url::class,
        'uuid' => Rules\Uuid::class,
        'boolean' => Rules\Boolean::class,
    ];

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
     * Create a new Validator instance for the given data
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Validate data against rules and return a ValidationResult.
     *
     * @param array $data Associative array of input values (e.g. $_POST)
     * @param array $rules Field => rule list map
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

    /**
     * Register a new built-in rule under a short name. Allows extension
     * libraries to add rules usable in array syntax.
     *
     * @param string $name
     * @param class-string<RuleInterface|ContextAwareRuleInterface> $ruleClass
     */
    public static function registerRule(string $name, string $ruleClass): void
    {
        self::$builtInRules[$name] = $ruleClass;
    }

    public function __construct(array $data)
    {
        $this->data = $data;
    }

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
     */
    public function in(array $values): self
    {
        return $this->addFluentRule('in', $values);
    }

    /** Value must not be in the given list (fluent) */
    public function notIn(array $values): self
    {
        return $this->addFluentRule('notIn', $values);
    }

    /** Value must match the regex pattern (fluent) */
    public function regex(string $pattern): self
    {
        return $this->addFluentRule('regex', $pattern);
    }

    /** Value must match the value of another field (fluent) */
    public function same(string $otherField): self
    {
        return $this->addFluentRule('same', $otherField);
    }

    /**
     * Value must be a valid case name of the given PHP enum (fluent)
     */
    public function enum(string $enumClass): self
    {
        return $this->addFluentRule('enum', $enumClass);
    }

    /** Companion field `<field>_confirmation` must match (fluent) */
    public function confirmed(): self
    {
        return $this->addFluentRule('confirmed');
    }

    /** Value must differ from another field (fluent) */
    public function different(string $otherField): self
    {
        return $this->addFluentRule('different', $otherField);
    }

    /** Required when another field equals a given value (fluent) */
    public function requiredIf(string $otherField, mixed $expectedValue): self
    {
        return $this->addFluentRule('requiredIf', [$otherField, $expectedValue]);
    }

    /** Required when another field is non-empty (fluent) */
    public function requiredWith(string $otherField): self
    {
        return $this->addFluentRule('requiredWith', $otherField);
    }

    /** Required when another field is empty/missing (fluent) */
    public function requiredWithout(string $otherField): self
    {
        return $this->addFluentRule('requiredWithout', $otherField);
    }

    /** Must parse as a date string (fluent) */
    public function date(): self
    {
        return $this->addFluentRule('date');
    }

    /** Must match the given strict date format (fluent) */
    public function dateFormat(string $format): self
    {
        return $this->addFluentRule('dateFormat', $format);
    }

    /** Date must be strictly before reference (literal date or another field) (fluent) */
    public function before(string $reference): self
    {
        return $this->addFluentRule('before', $reference);
    }

    /** Date must be strictly after reference (literal date or another field) (fluent) */
    public function after(string $reference): self
    {
        return $this->addFluentRule('after', $reference);
    }

    /** Must be a valid URL (fluent) */
    public function url(): self
    {
        return $this->addFluentRule('url');
    }

    /** Must be a valid RFC 4122 UUID (fluent) */
    public function uuid(): self
    {
        return $this->addFluentRule('uuid');
    }

    /** Must be a (loose) boolean: true/false/1/0/"true"/"false" (fluent) */
    public function boolean(): self
    {
        return $this->addFluentRule('boolean');
    }

    /**
     * Add a custom closure or rule object (fluent)
     */
    public function addRule(Closure|RuleInterface|ContextAwareRuleInterface $rule): self
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

            foreach ($ruleList as $ruleType => $rule) {
                try {
                    if (is_string($ruleType)) {
                        if ($ruleType === 'nullable') {
                            if ($this->isEmpty($value)) {
                                break;
                            }

                            continue;
                        }

                        $this->dispatchBuiltIn($ruleType, $rule, $value, $fieldKey);

                        continue;
                    }

                    if (is_string($rule)) {
                        if ($rule === 'nullable') {
                            if ($this->isEmpty($value)) {
                                break;
                            }

                            continue;
                        }

                        $this->dispatchBuiltIn($rule, null, $value, $fieldKey);

                        continue;
                    }

                    if ($rule instanceof Closure) {
                        $rule($value);

                        continue;
                    }

                    if ($rule instanceof ContextAwareRuleInterface) {
                        $rule->validateInContext($value, $fieldKey, $this->data);

                        continue;
                    }

                    if ($rule instanceof RuleInterface) {
                        $rule->validate($value);
                    }
                } catch (ValidationException $e) {
                    $this->errors[$fieldKey] = $e->getMessage();
                    break;
                }
            }
        }

        return new ValidationResult($this->errors);
    }

    /**
     * Build a built-in rule from its short name and options, then dispatch it.
     */
    private function dispatchBuiltIn(string $name, mixed $options, mixed $value, string $fieldKey): void
    {
        if (!isset(self::$builtInRules[$name])) {
            throw new LogicException('Unknown validation rule: ' . $name);
        }

        $ruleClass = self::$builtInRules[$name];
        $rule = $ruleClass::fromOptions($options);

        if ($rule instanceof ContextAwareRuleInterface) {
            $rule->validateInContext($value, $fieldKey, $this->data);

            return;
        }

        $rule->validate($value);
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
            throw new LogicException('Call field() before adding rules');
        }
    }

}
