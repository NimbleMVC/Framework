<?php

use NimblePHP\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorRulesTest extends TestCase
{

    public function testRequiredFailsOnEmpty()
    {
        $result = Validator::validate(['name' => ''], ['name' => ['required']]);
        $this->assertTrue($result->fails());
    }

    public function testRequiredPassesOnValue()
    {
        $result = Validator::validate(['name' => 'John'], ['name' => ['required']]);
        $this->assertTrue($result->passes());
    }

    public function testNullableSkipsFollowingRules()
    {
        $result = Validator::validate(['age' => null], ['age' => ['nullable', 'isInteger']]);
        $this->assertTrue($result->passes());
    }

    public function testCheckedFailsOnFalsy()
    {
        $result = Validator::validate(['accept' => '0'], ['accept' => ['checked']]);
        $this->assertTrue($result->fails());
    }

    public function testIsEmail()
    {
        $this->assertTrue(Validator::validate(['e' => 'a@b.c'], ['e' => ['isEmail']])->passes());
        $this->assertTrue(Validator::validate(['e' => 'foo'], ['e' => ['isEmail']])->fails());
    }

    public function testIsInteger()
    {
        $this->assertTrue(Validator::validate(['n' => '42'], ['n' => ['isInteger']])->passes());
        $this->assertTrue(Validator::validate(['n' => '4.2'], ['n' => ['isInteger']])->fails());
    }

    public function testIsNumeric()
    {
        $this->assertTrue(Validator::validate(['n' => '4.2'], ['n' => ['isNumeric']])->passes());
        $this->assertTrue(Validator::validate(['n' => 'abc'], ['n' => ['isNumeric']])->fails());
    }

    public function testIsDecimalRespectsMaxPlaces()
    {
        $this->assertTrue(Validator::validate(['p' => '12.34'], ['p' => ['isDecimal' => ['maxPlaces' => 2]]])->passes());
        $this->assertTrue(Validator::validate(['p' => '12.345'], ['p' => ['isDecimal' => ['maxPlaces' => 2]]])->fails());
        $this->assertTrue(Validator::validate(['p' => '12'], ['p' => ['isDecimal']])->passes());
    }

    public function testLengthBounds()
    {
        $rules = ['s' => ['length' => ['min' => 3, 'max' => 5]]];
        $this->assertTrue(Validator::validate(['s' => 'abcd'], $rules)->passes());
        $this->assertTrue(Validator::validate(['s' => 'ab'], $rules)->fails());
        $this->assertTrue(Validator::validate(['s' => 'abcdef'], $rules)->fails());
    }

    public function testMinLengthAndMaxLength()
    {
        $this->assertTrue(Validator::validate(['s' => 'abc'], ['s' => ['minLength' => 3]])->passes());
        $this->assertTrue(Validator::validate(['s' => 'ab'], ['s' => ['minLength' => 3]])->fails());
        $this->assertTrue(Validator::validate(['s' => 'abc'], ['s' => ['maxLength' => 3]])->passes());
        $this->assertTrue(Validator::validate(['s' => 'abcd'], ['s' => ['maxLength' => 3]])->fails());
    }

    public function testMinAndMax()
    {
        $this->assertTrue(Validator::validate(['n' => 5], ['n' => ['min' => 3]])->passes());
        $this->assertTrue(Validator::validate(['n' => 1], ['n' => ['min' => 3]])->fails());
        $this->assertTrue(Validator::validate(['n' => 3], ['n' => ['max' => 5]])->passes());
        $this->assertTrue(Validator::validate(['n' => 9], ['n' => ['max' => 5]])->fails());
    }

    public function testIn()
    {
        $rules = ['role' => ['in' => ['admin', 'user']]];
        $this->assertTrue(Validator::validate(['role' => 'admin'], $rules)->passes());
        $this->assertTrue(Validator::validate(['role' => 'guest'], $rules)->fails());
    }

    public function testNotIn()
    {
        $rules = ['role' => ['notIn' => ['banned', 'guest']]];
        $this->assertTrue(Validator::validate(['role' => 'admin'], $rules)->passes());
        $this->assertTrue(Validator::validate(['role' => 'banned'], $rules)->fails());
    }

    public function testRegex()
    {
        $rules = ['code' => ['regex' => '/^[A-Z]{3}\d{3}$/']];
        $this->assertTrue(Validator::validate(['code' => 'ABC123'], $rules)->passes());
        $this->assertTrue(Validator::validate(['code' => 'abc123'], $rules)->fails());
    }

    public function testSameWithContextField()
    {
        $rules = ['confirm' => ['same' => 'password']];
        $this->assertTrue(Validator::validate(['password' => 'secret', 'confirm' => 'secret'], $rules)->passes());
        $this->assertTrue(Validator::validate(['password' => 'secret', 'confirm' => 'other'], $rules)->fails());
    }

    public function testEnumByCaseName()
    {
        $rules = ['status' => ['enum' => Status::class]];
        $this->assertTrue(Validator::validate(['status' => 'Active'], $rules)->passes());
        $this->assertTrue(Validator::validate(['status' => 'Bogus'], $rules)->fails());
    }

    public function testCustomClosureRule()
    {
        $rules = [
            'nick' => [function (mixed $value) {
                if (str_contains((string)$value, ' ')) {
                    throw new \NimblePHP\Framework\Exception\ValidationException('No spaces');
                }
            }],
        ];
        $this->assertTrue(Validator::validate(['nick' => 'john'], $rules)->passes());
        $this->assertTrue(Validator::validate(['nick' => 'john doe'], $rules)->fails());
    }

    public function testCustomRuleObject()
    {
        $rule = new class extends \NimblePHP\Framework\Validation\AbstractRule {
            public function validate(mixed $value): void
            {
                if ($value !== 'magic') {
                    $this->fail('Must be magic.');
                }
            }
        };

        $this->assertTrue(Validator::validate(['x' => 'magic'], ['x' => [$rule]])->passes());
        $this->assertTrue(Validator::validate(['x' => 'banal'], ['x' => [$rule]])->fails());
    }

    public function testFluentApi()
    {
        $result = Validator::make(['email' => 'a@b.c', 'age' => 25])
            ->field('email')->required()->isEmail()
            ->field('age')->required()->isInteger()->min(18)
            ->run();
        $this->assertTrue($result->passes());
    }

    public function testRegisterCustomRule()
    {
        Validator::registerRule('isMagic', class_exists('TestMagicRule') ? 'TestMagicRule' : MagicRule::class);
        $result = Validator::validate(['x' => 'magic'], ['x' => ['isMagic']]);
        $this->assertTrue($result->passes());
        $result = Validator::validate(['x' => 'wrong'], ['x' => ['isMagic']]);
        $this->assertTrue($result->fails());
    }

    public function testFieldErrorsArePreservedInResult()
    {
        $result = Validator::validate(
            ['email' => 'x', 'age' => 'abc'],
            ['email' => ['isEmail'], 'age' => ['isInteger']]
        );
        $errors = $result->errors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
    }

}

enum Status: string
{
    case Active = 'a';
    case Inactive = 'i';
}

class MagicRule extends \NimblePHP\Framework\Validation\AbstractRule
{

    public function validate(mixed $value): void
    {
        if ($value !== 'magic') {
            $this->fail('Not magic.');
        }
    }

}
