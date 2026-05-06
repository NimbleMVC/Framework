<?php

use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Middleware\ApiExceptionHandler;
use PHPUnit\Framework\TestCase;

class ApiExceptionHandlerTest extends TestCase
{

    public function testValidationExceptionIsMappedTo422()
    {
        $handler = new class extends ApiExceptionHandler {

            public function resolveStatusCodePublic(\Throwable $e): int
            {
                return $this->resolveStatusCode($e);
            }

        };

        $exception = new ValidationException('Validation failed', 400, null, [
            'email' => 'Invalid',
        ]);

        $this->assertSame(422, $handler->resolveStatusCodePublic($exception));
    }

    public function testValidationExceptionWithoutFieldErrorsKeepsOriginalCode()
    {
        $handler = new class extends ApiExceptionHandler {

            public function resolveStatusCodePublic(\Throwable $e): int
            {
                return $this->resolveStatusCode($e);
            }

        };

        $exception = new ValidationException('Validation failed', 400);

        $this->assertSame(400, $handler->resolveStatusCodePublic($exception));
    }

    public function testNotFoundExceptionIsMappedTo404()
    {
        $handler = new class extends ApiExceptionHandler {

            public function resolveStatusCodePublic(\Throwable $e): int
            {
                return $this->resolveStatusCode($e);
            }

        };

        $this->assertSame(404, $handler->resolveStatusCodePublic(new NotFoundException()));
    }

    public function testGenericExceptionDefaultsTo500()
    {
        $handler = new class extends ApiExceptionHandler {

            public function resolveStatusCodePublic(\Throwable $e): int
            {
                return $this->resolveStatusCode($e);
            }

        };

        $this->assertSame(500, $handler->resolveStatusCodePublic(new \RuntimeException('boom')));
    }

    public function testExceptionWithHttpCodeIsRespected()
    {
        $handler = new class extends ApiExceptionHandler {

            public function resolveStatusCodePublic(\Throwable $e): int
            {
                return $this->resolveStatusCode($e);
            }

        };

        $this->assertSame(401, $handler->resolveStatusCodePublic(new \RuntimeException('unauthorized', 401)));
    }

}
