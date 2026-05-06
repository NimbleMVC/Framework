<?php

use NimblePHP\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorNewRulesTest extends TestCase
{

    public function testConfirmedPasses()
    {
        $result = Validator::validate(
            ['password' => 'secret', 'password_confirmation' => 'secret'],
            ['password' => ['confirmed']]
        );
        $this->assertTrue($result->passes());
    }

    public function testConfirmedFails()
    {
        $result = Validator::validate(
            ['password' => 'secret', 'password_confirmation' => 'other'],
            ['password' => ['confirmed']]
        );
        $this->assertTrue($result->fails());
    }

    public function testConfirmedFailsWhenCompanionMissing()
    {
        $result = Validator::validate(
            ['password' => 'secret'],
            ['password' => ['confirmed']]
        );
        $this->assertTrue($result->fails());
    }

    public function testDifferent()
    {
        $rules = ['new_pw' => ['different' => 'old_pw']];
        $this->assertTrue(Validator::validate(['old_pw' => 'a', 'new_pw' => 'b'], $rules)->passes());
        $this->assertTrue(Validator::validate(['old_pw' => 'a', 'new_pw' => 'a'], $rules)->fails());
    }

    public function testRequiredIfTriggers()
    {
        $rules = ['phone' => ['requiredIf' => ['contact', 'phone']]];
        $this->assertTrue(Validator::validate(['contact' => 'phone', 'phone' => ''], $rules)->fails());
        $this->assertTrue(Validator::validate(['contact' => 'phone', 'phone' => '+48'], $rules)->passes());
        $this->assertTrue(Validator::validate(['contact' => 'email', 'phone' => ''], $rules)->passes());
    }

    public function testRequiredWith()
    {
        $rules = ['surname' => ['requiredWith' => 'name']];
        $this->assertTrue(Validator::validate(['name' => 'John', 'surname' => ''], $rules)->fails());
        $this->assertTrue(Validator::validate(['name' => 'John', 'surname' => 'Doe'], $rules)->passes());
        $this->assertTrue(Validator::validate(['name' => '', 'surname' => ''], $rules)->passes());
    }

    public function testRequiredWithout()
    {
        $rules = ['email' => ['requiredWithout' => 'phone']];
        $this->assertTrue(Validator::validate(['phone' => '', 'email' => ''], $rules)->fails());
        $this->assertTrue(Validator::validate(['phone' => '+48', 'email' => ''], $rules)->passes());
        $this->assertTrue(Validator::validate(['phone' => '', 'email' => 'a@b.c'], $rules)->passes());
    }

    public function testDate()
    {
        $this->assertTrue(Validator::validate(['d' => '2026-01-15'], ['d' => ['date']])->passes());
        $this->assertTrue(Validator::validate(['d' => 'next monday'], ['d' => ['date']])->passes());
        $this->assertTrue(Validator::validate(['d' => 'not-a-date'], ['d' => ['date']])->fails());
    }

    public function testDateFormat()
    {
        $rules = ['d' => ['dateFormat' => 'Y-m-d']];
        $this->assertTrue(Validator::validate(['d' => '2026-01-15'], $rules)->passes());
        $this->assertTrue(Validator::validate(['d' => '15/01/2026'], $rules)->fails());
        $this->assertTrue(Validator::validate(['d' => '2026-13-15'], $rules)->fails());
    }

    public function testBeforeAndAfterLiteralReference()
    {
        $this->assertTrue(Validator::validate(['d' => '2025-01-01'], ['d' => ['before' => '2026-01-01']])->passes());
        $this->assertTrue(Validator::validate(['d' => '2027-01-01'], ['d' => ['before' => '2026-01-01']])->fails());
        $this->assertTrue(Validator::validate(['d' => '2027-01-01'], ['d' => ['after' => '2026-01-01']])->passes());
        $this->assertTrue(Validator::validate(['d' => '2025-01-01'], ['d' => ['after' => '2026-01-01']])->fails());
    }

    public function testBeforeAndAfterFieldReference()
    {
        $rules = ['end' => ['after' => 'start']];
        $this->assertTrue(Validator::validate(['start' => '2026-01-01', 'end' => '2026-12-31'], $rules)->passes());
        $this->assertTrue(Validator::validate(['start' => '2026-12-31', 'end' => '2026-01-01'], $rules)->fails());
    }

    public function testUrl()
    {
        $this->assertTrue(Validator::validate(['u' => 'https://example.com'], ['u' => ['url']])->passes());
        $this->assertTrue(Validator::validate(['u' => 'not a url'], ['u' => ['url']])->fails());
    }

    public function testUuid()
    {
        $this->assertTrue(Validator::validate(['id' => '550e8400-e29b-41d4-a716-446655440000'], ['id' => ['uuid']])->passes());
        $this->assertTrue(Validator::validate(['id' => 'not-a-uuid'], ['id' => ['uuid']])->fails());
        $this->assertTrue(Validator::validate(['id' => '550E8400-E29B-41D4-A716-446655440000'], ['id' => ['uuid']])->passes());
    }

    public function testBooleanAcceptsCommonValues()
    {
        foreach ([true, false, 1, 0, '1', '0', 'true', 'false', 'yes', 'no', 'on', 'off'] as $val) {
            $this->assertTrue(Validator::validate(['b' => $val], ['b' => ['boolean']])->passes(), 'Value: ' . var_export($val, true));
        }

        $this->assertTrue(Validator::validate(['b' => 'maybe'], ['b' => ['boolean']])->fails());
        $this->assertTrue(Validator::validate(['b' => 'asdf'], ['b' => ['boolean']])->fails());
    }

    public function testFluentApiNewRules()
    {
        $result = Validator::make([
            'password' => 'sec',
            'password_confirmation' => 'sec',
            'start' => '2026-01-01',
            'end' => '2026-12-31',
            'website' => 'https://example.com',
        ])
            ->field('password')->confirmed()
            ->field('end')->after('start')
            ->field('website')->url()
            ->run();

        $this->assertTrue($result->passes(), implode(';', $result->errors()));
    }

}
