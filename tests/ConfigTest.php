<?php

use NimblePHP\Framework\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
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

    public function testGetReturnsConfiguredValue(): void
    {
        $_ENV['APP_ENV'] = 'test';

        $this->assertSame('test', Config::get('APP_ENV'));
    }

    public function testGetReturnsDefaultWhenValueMissing(): void
    {
        unset($_ENV['UNKNOWN_KEY']);

        $this->assertSame('fallback', Config::get('UNKNOWN_KEY', 'fallback'));
    }

    public function testSetStoresValueInEnvironment(): void
    {
        Config::set('FEATURE_ENABLED', true);

        $this->assertTrue($_ENV['FEATURE_ENABLED']);
        $this->assertTrue(Config::get('FEATURE_ENABLED'));
    }
}
