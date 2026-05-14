<?php

use NimblePHP\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorEdgeCasesTest extends TestCase
{
    public function testRequiredIfDoesNotTriggerWhenConditionDoesNotMatch(): void
    {
        $result = Validator::validate(
            ['type' => 'user', 'email' => ''],
            ['email' => ['requiredIf' => ['type', 'admin']]]
        );

        $this->assertTrue($result->passes());
    }

    public function testRequiredWithTreatsNonEmptyArrayAsPresent(): void
    {
        $result = Validator::validate(
            ['phone' => ['123'], 'email' => ''],
            ['email' => ['requiredWith' => 'phone']]
        );

        $this->assertTrue($result->fails());
    }

    public function testRequiredWithoutTreatsEmptyArrayAsMissing(): void
    {
        $result = Validator::validate(
            ['phone' => [], 'email' => ''],
            ['email' => ['requiredWithout' => 'phone']]
        );

        $this->assertTrue($result->fails());
    }

    public function testBooleanRejectsUnknownString(): void
    {
        $result = Validator::validate(['flag' => 'maybe'], ['flag' => ['boolean']]);

        $this->assertTrue($result->fails());
    }

    public function testEnumRejectsBackedEnumValueAndRequiresCaseName(): void
    {
        $result = Validator::validate(
            ['status' => 'published'],
            ['status' => ['enum' => ValidatorEdgeCasesStatus::class]]
        );

        $this->assertTrue($result->fails());
    }
}

enum ValidatorEdgeCasesStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
