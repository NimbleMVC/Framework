<?php

use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testPassingResultHasNoErrors(): void
    {
        $result = new ValidationResult();

        $this->assertTrue($result->passes());
        $this->assertFalse($result->fails());
        $this->assertSame([], $result->errors());
        $this->assertNull($result->firstError('name'));
        $this->assertFalse($result->hasError('name'));
    }

    public function testFailingResultExposesErrors(): void
    {
        $result = new ValidationResult([
            'name' => 'Name is required',
            'email' => 'Email is invalid',
        ]);

        $this->assertTrue($result->fails());
        $this->assertFalse($result->passes());
        $this->assertSame('Name is required', $result->firstError('name'));
        $this->assertTrue($result->hasError('email'));
    }

    public function testThrowIfFailedRaisesValidationExceptionWithFieldErrors(): void
    {
        $result = new ValidationResult([
            'name' => 'Name is required',
            'email' => 'Email is invalid',
        ]);

        try {
            $result->throwIfFailed();
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame('Name is required', $exception->getMessage());
            $this->assertSame(422, $exception->getCode());
            $this->assertSame([
                'name' => 'Name is required',
                'email' => 'Email is invalid',
            ], $exception->getFieldErrors());
        }
    }
}
