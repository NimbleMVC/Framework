<?php

use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Validation\ValidationResult;
use NimblePHP\Framework\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{

    public function testFieldErrorsDefaultToEmpty()
    {
        $exception = new ValidationException('msg');

        $this->assertSame([], $exception->getFieldErrors());
    }

    public function testFieldErrorsArePreserved()
    {
        $errors = ['email' => 'Invalid e-mail address.', 'age' => 'Must be int.'];
        $exception = new ValidationException('msg', 422, null, $errors);

        $this->assertSame($errors, $exception->getFieldErrors());
        $this->assertSame(422, $exception->getCode());
    }

    public function testValidationResultThrowIfFailedCarriesFieldMap()
    {
        $result = new ValidationResult(['email' => 'Invalid', 'age' => 'Too young']);

        try {
            $result->throwIfFailed();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame(422, $exception->getCode());
            $this->assertSame(
                ['email' => 'Invalid', 'age' => 'Too young'],
                $exception->getFieldErrors()
            );
        }
    }

    public function testValidationResultThrowIfFailedSkipsOnSuccess()
    {
        $result = new ValidationResult([]);
        $result->throwIfFailed();

        $this->assertTrue(true);
    }

    public function testValidatorValidateOrFailPropagatesFieldErrors()
    {
        try {
            Validator::validateOrFail(['email' => 'x'], [
                'email' => ['required', 'isEmail'],
                'name'  => ['required'],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $errors = $exception->getFieldErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('name', $errors);
        }
    }

}
