<?php

use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Validation\AbstractRule;
use NimblePHP\Framework\Validation\ContextAwareRuleInterface;
use NimblePHP\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorBehaviorTest extends TestCase
{
    public function testValidateOrFailThrowsFirstValidationError(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('isEmail');

        Validator::validateOrFail(
            ['email' => 'invalid', 'age' => 'abc'],
            ['email' => ['isEmail'], 'age' => ['isInteger']]
        );
    }

    public function testUnknownRuleThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown validation rule: totallyMissingRule');

        Validator::validate(['name' => 'John'], ['name' => ['totallyMissingRule']]);
    }

    public function testFluentApiRequiresFieldBeforeAddingRules(): void
    {
        $validator = Validator::make([]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Call field() before adding rules');
        $validator->required();
    }

    public function testNestedDotNotationAndSlashNotationAreSupported(): void
    {
        $data = [
            'user' => [
                'email' => 'john@example.com',
                'profile' => [
                    'website' => 'https://example.com',
                ],
            ],
        ];

        $result = Validator::validate($data, [
            'user.email' => ['required', 'isEmail'],
            'user/profile/website' => ['required', 'url'],
        ]);

        $this->assertTrue($result->passes(), implode(';', $result->errors()));
    }

    public function testNullableSkipsFollowingRulesForWhitespaceStringsAndEmptyArrays(): void
    {
        $result = Validator::validate(
            ['email' => '   ', 'tags' => []],
            ['email' => ['nullable', 'isEmail'], 'tags' => ['nullable', 'isString']]
        );

        $this->assertTrue($result->passes(), implode(';', $result->errors()));
    }

    public function testFieldStopsAtFirstErrorInsteadOfOverwritingWithLaterRule(): void
    {
        $result = Validator::validate(
            ['email' => ''],
            ['email' => ['required', 'isEmail', 'maxLength' => 1]]
        );

        $this->assertTrue($result->fails());
        $this->assertSame('required', $result->firstError('email'));
    }

    public function testAddRuleSupportsContextAwareRuleObjects(): void
    {
        $rule = new class extends AbstractRule implements ContextAwareRuleInterface {
            public function validate(mixed $value): void
            {
            }

            public function validateInContext(mixed $value, string $field, array $data): void
            {
                if (($data['other'] ?? null) !== $value) {
                    $this->fail('Values differ.');
                }
            }
        };

        $passes = Validator::make(['other' => 'same', 'current' => 'same'])
            ->field('current')
            ->addRule($rule)
            ->run();

        $fails = Validator::make(['other' => 'same', 'current' => 'different'])
            ->field('current')
            ->addRule($rule)
            ->run();

        $this->assertTrue($passes->passes());
        $this->assertSame('Values differ.', $fails->firstError('current'));
    }

    public function testRegisterRuleSupportsAdditionalCustomRuleNames(): void
    {
        Validator::registerRule('startsWithNimble', ValidatorBehaviorStartsWithNimbleRule::class);

        $pass = Validator::validate(['name' => 'NimblePHP'], ['name' => ['startsWithNimble']]);
        $fail = Validator::validate(['name' => 'Framework'], ['name' => ['startsWithNimble']]);

        $this->assertTrue($pass->passes());
        $this->assertSame('Value must start with Nimble.', $fail->firstError('name'));
    }
}

class ValidatorBehaviorStartsWithNimbleRule extends AbstractRule
{
    public function validate(mixed $value): void
    {
        if (!str_starts_with((string) $value, 'Nimble')) {
            $this->fail('Value must start with Nimble.');
        }
    }
}
