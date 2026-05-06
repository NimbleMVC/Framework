<?php

use NimblePHP\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorMoreRulesTest extends TestCase
{

    public function testSlug()
    {
        $this->assertTrue(Validator::validate(['s' => 'my-blog-post-2024'], ['s' => ['slug']])->passes());
        $this->assertTrue(Validator::validate(['s' => 'a'], ['s' => ['slug']])->passes());
        $this->assertTrue(Validator::validate(['s' => '12-34'], ['s' => ['slug']])->passes());
        $this->assertTrue(Validator::validate(['s' => 'My-Post'], ['s' => ['slug']])->fails(), 'uppercase should fail');
        $this->assertTrue(Validator::validate(['s' => 'my post'], ['s' => ['slug']])->fails(), 'space should fail');
        $this->assertTrue(Validator::validate(['s' => '-leading'], ['s' => ['slug']])->fails(), 'leading dash should fail');
        $this->assertTrue(Validator::validate(['s' => 'trailing-'], ['s' => ['slug']])->fails(), 'trailing dash should fail');
        $this->assertTrue(Validator::validate(['s' => 'double--dash'], ['s' => ['slug']])->fails(), 'double dash should fail');
        $this->assertTrue(Validator::validate(['s' => 'mój-post'], ['s' => ['slug']])->fails(), 'unicode should fail');
    }

    public function testJson()
    {
        $this->assertTrue(Validator::validate(['j' => '{"a":1}'], ['j' => ['json']])->passes());
        $this->assertTrue(Validator::validate(['j' => '[1,2,3]'], ['j' => ['json']])->passes());
        $this->assertTrue(Validator::validate(['j' => '"string"'], ['j' => ['json']])->passes());
        $this->assertTrue(Validator::validate(['j' => 'not-json'], ['j' => ['json']])->fails());
        $this->assertTrue(Validator::validate(['j' => '{bad}'], ['j' => ['json']])->fails());
        $this->assertTrue(Validator::validate(['j' => 123], ['j' => ['json']])->fails(), 'non-string should fail');
    }

    public function testDigits()
    {
        $this->assertTrue(Validator::validate(['n' => '1234'], ['n' => ['digits' => 4]])->passes());
        $this->assertTrue(Validator::validate(['n' => 1234], ['n' => ['digits' => 4]])->passes());
        $this->assertTrue(Validator::validate(['n' => '123'], ['n' => ['digits' => 4]])->fails(), 'too short');
        $this->assertTrue(Validator::validate(['n' => '12345'], ['n' => ['digits' => 4]])->fails(), 'too long');
        $this->assertTrue(Validator::validate(['n' => '12a4'], ['n' => ['digits' => 4]])->fails(), 'non-digit');
    }

    public function testDigitsBetween()
    {
        $rules = ['n' => ['digitsBetween' => [3, 5]]];
        $this->assertTrue(Validator::validate(['n' => '123'], $rules)->passes());
        $this->assertTrue(Validator::validate(['n' => '1234'], $rules)->passes());
        $this->assertTrue(Validator::validate(['n' => '12345'], $rules)->passes());
        $this->assertTrue(Validator::validate(['n' => '12'], $rules)->fails());
        $this->assertTrue(Validator::validate(['n' => '123456'], $rules)->fails());
        $this->assertTrue(Validator::validate(['n' => '12a'], $rules)->fails());
    }

    public function testIsString()
    {
        $this->assertTrue(Validator::validate(['s' => 'hello'], ['s' => ['isString']])->passes());
        $this->assertTrue(Validator::validate(['s' => ''], ['s' => ['isString']])->passes());
        $this->assertTrue(Validator::validate(['s' => 123], ['s' => ['isString']])->fails());
        $this->assertTrue(Validator::validate(['s' => true], ['s' => ['isString']])->fails());
        $this->assertTrue(Validator::validate(['s' => []], ['s' => ['isString']])->fails());
        $this->assertTrue(Validator::validate(['s' => null], ['s' => ['isString']])->fails());
    }

    public function testIsArray()
    {
        $this->assertTrue(Validator::validate(['a' => [1, 2]], ['a' => ['isArray']])->passes());
        $this->assertTrue(Validator::validate(['a' => []], ['a' => ['isArray']])->passes());
        $this->assertTrue(Validator::validate(['a' => 'list'], ['a' => ['isArray']])->fails());
        $this->assertTrue(Validator::validate(['a' => 123], ['a' => ['isArray']])->fails());
    }

    public function testAlphaNum()
    {
        $this->assertTrue(Validator::validate(['v' => 'Abc123'], ['v' => ['alphaNum']])->passes());
        $this->assertTrue(Validator::validate(['v' => 'krzysztof123'], ['v' => ['alphaNum']])->passes());
        $this->assertTrue(Validator::validate(['v' => 'żółty'], ['v' => ['alphaNum']])->passes(), 'unicode letters allowed');
        $this->assertTrue(Validator::validate(['v' => 'a-b'], ['v' => ['alphaNum']])->fails(), 'dash not allowed');
        $this->assertTrue(Validator::validate(['v' => 'a b'], ['v' => ['alphaNum']])->fails(), 'space not allowed');
        $this->assertTrue(Validator::validate(['v' => ''], ['v' => ['alphaNum']])->fails(), 'empty fails');
    }

    public function testAlphaDash()
    {
        $this->assertTrue(Validator::validate(['v' => 'user_name-1'], ['v' => ['alphaDash']])->passes());
        $this->assertTrue(Validator::validate(['v' => 'żółty_kot'], ['v' => ['alphaDash']])->passes(), 'unicode letters allowed');
        $this->assertTrue(Validator::validate(['v' => 'with space'], ['v' => ['alphaDash']])->fails());
        $this->assertTrue(Validator::validate(['v' => 'with.dot'], ['v' => ['alphaDash']])->fails());
    }

    public function testFluentApi()
    {
        $result = Validator::make([
            'username' => 'krzysiek_99',
            'pin' => '1234',
            'meta' => '{"a":1}',
            'tags' => ['php'],
        ])
            ->field('username')->required()->alphaDash()
            ->field('pin')->required()->digits(4)
            ->field('meta')->json()
            ->field('tags')->isArray()
            ->run();

        $this->assertTrue($result->passes(), implode(';', $result->errors()));
    }

}
