<?php

use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class FrameworkExceptionTest extends TestCase
{
    private array $envBackup;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
    }

    public function testNimbleExceptionUsesProvidedMessageAndCode(): void
    {
        $exception = new NimbleException('Broken', 501);

        $this->assertSame('Broken', $exception->getMessage());
        $this->assertSame(501, $exception->getCode());
    }

    public function testNotFoundExceptionDefaultsTo404(): void
    {
        $exception = new NotFoundException();

        $this->assertSame('Not found', $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
    }

    public function testHiddenExceptionHidesMessageWhenDebugDisabled(): void
    {
        $_ENV['DEBUG'] = false;

        $exception = new HiddenException('Sensitive details', 500);

        $this->assertSame('System error', $exception->getMessage());
        $this->assertSame('Sensitive details', $exception->getHiddenMessage());
    }

    public function testHiddenExceptionShowsMessageWhenDebugEnabled(): void
    {
        $_ENV['DEBUG'] = true;

        $exception = new HiddenException('Sensitive details', 500);

        $this->assertSame('Sensitive details', $exception->getMessage());
        $this->assertSame('Sensitive details', $exception->getHiddenMessage());
    }
}
