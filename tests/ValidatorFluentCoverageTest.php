<?php

use NimblePHP\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorFluentCoverageTest extends TestCase
{
    public function testFluentApiCoversStringNumericCollectionAndMembershipRules(): void
    {
        $result = Validator::make([
            'price' => '10.50',
            'username' => 'nimble_user-1',
            'code' => 'ABC123',
            'pin' => '12345',
            'role' => 'admin',
            'nickname' => 'maintainer',
            'tags' => ['php', 'mvc'],
        ])
            ->field('price')->required()->isDecimal(2)->min(10)->max(20)
            ->field('username')->isString()->length(3, 20)->alphaDash()
            ->field('code')->regex('/^[A-Z]{3}\d{3}$/')
            ->field('pin')->digitsBetween(4, 6)
            ->field('role')->in(['admin', 'editor'])
            ->field('nickname')->notIn(['banned', 'guest'])
            ->field('tags')->isArray()
            ->run();

        $this->assertTrue($result->passes(), implode(';', $result->errors()));
    }

    public function testFluentApiCoversConfirmationComparisonAndConditionalRules(): void
    {
        $result = Validator::make([
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'old_password' => 'old-secret',
            'contact_type' => 'email',
            'email' => 'john@example.com',
            'name' => 'John',
            'surname' => 'Doe',
            'phone' => '',
        ])
            ->field('password')->confirmed()->different('old_password')
            ->field('email')->requiredIf('contact_type', 'email')->isEmail()
            ->field('surname')->requiredWith('name')
            ->field('email')->requiredWithout('phone')
            ->run();

        $this->assertTrue($result->passes(), implode(';', $result->errors()));
    }

    public function testFluentApiCoversDateBooleanUrlUuidSlugAndEnumRules(): void
    {
        $result = Validator::make([
            'start' => '2026-01-01',
            'end' => '2026-06-01',
            'published_at' => '2026-05-14',
            'enabled' => 'true',
            'site' => 'https://nimblephp.com',
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'slug' => 'nimble-framework',
            'status' => 'Published',
            'kind' => 'Framework',
        ])
            ->field('start')->date()
            ->field('end')->dateFormat('Y-m-d')->after('start')
            ->field('published_at')->before('2026-12-31')
            ->field('enabled')->boolean()
            ->field('site')->url()
            ->field('id')->uuid()
            ->field('slug')->slug()
            ->field('status')->enum(ValidatorFluentCoverageStatus::class)
            ->field('kind')->same('kind')
            ->run();

        $this->assertTrue($result->passes(), implode(';', $result->errors()));
    }

    public function testFluentApiFailsOnMultipleInvalidValues(): void
    {
        $result = Validator::make([
            'username' => 'bad name',
            'enabled' => 'sometimes',
            'site' => 'invalid-url',
            'id' => 'bad-uuid',
        ])
            ->field('username')->alphaDash()
            ->field('enabled')->boolean()
            ->field('site')->url()
            ->field('id')->uuid()
            ->run();

        $this->assertTrue($result->fails());
        $this->assertArrayHasKey('username', $result->errors());
        $this->assertArrayHasKey('enabled', $result->errors());
        $this->assertArrayHasKey('site', $result->errors());
        $this->assertArrayHasKey('id', $result->errors());
    }
}

enum ValidatorFluentCoverageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
