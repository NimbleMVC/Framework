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

    public function testGetDecodesBase64Prefix(): void
    {
        $_ENV['SECRET'] = 'base64:' . base64_encode('raw-value');

        $this->assertSame('raw-value', Config::get('SECRET'));
    }

    public function testGetDecodesHexPrefix(): void
    {
        $_ENV['SECRET'] = 'hex:' . bin2hex('raw-value');

        $this->assertSame('raw-value', Config::get('SECRET'));
    }

    public function testGetDecodesHexPrefixReturnsEmptyStringForInvalidHex(): void
    {
        $_ENV['SECRET'] = 'hex:not-hex';

        $this->assertSame('', Config::get('SECRET'));
    }

    public function testGetDecodesFilePrefix(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'nimble-config-test-');
        file_put_contents($path, 'file-contents');

        $_ENV['SECRET'] = 'file:' . $path;

        $this->assertSame('file-contents', Config::get('SECRET'));

        unlink($path);
    }

    public function testGetDecodesFilePrefixReturnsEmptyStringWhenMissing(): void
    {
        $_ENV['SECRET'] = 'file:/path/does/not/exist';

        $this->assertSame('', Config::get('SECRET'));
    }

    public function testGetDecodesJsonPrefix(): void
    {
        $_ENV['SECRET'] = 'json:' . json_encode(['a' => 1, 'b' => [2, 3]]);

        $this->assertSame(['a' => 1, 'b' => [2, 3]], Config::get('SECRET'));
    }

    public function testGetDecodesEnvPrefix(): void
    {
        $_ENV['REAL_KEY'] = 'the-value';
        $_ENV['ALIAS_KEY'] = 'env:REAL_KEY';

        $this->assertSame('the-value', Config::get('ALIAS_KEY'));
    }

    public function testGetDecodesEnvPrefixRecursively(): void
    {
        $_ENV['REAL_KEY'] = 'base64:' . base64_encode('nested-value');
        $_ENV['ALIAS_KEY'] = 'env:REAL_KEY';

        $this->assertSame('nested-value', Config::get('ALIAS_KEY'));
    }

    public function testGetDecodesEnvPrefixDoesNotInfiniteLoopOnCycle(): void
    {
        $_ENV['A_KEY'] = 'env:B_KEY';
        $_ENV['B_KEY'] = 'env:A_KEY';

        // A circular "env:" reference must terminate (bounded depth) instead of looping forever.
        // Once the depth limit is hit, the last unresolved "env:" token is returned as-is.
        $result = Config::get('A_KEY');

        $this->assertIsString($result);
        $this->assertStringStartsWith('env:', $result);
    }

    public function testGetLeavesUnprefixedValueUntouched(): void
    {
        $_ENV['PLAIN'] = 'just-a-value';

        $this->assertSame('just-a-value', Config::get('PLAIN'));
    }
}
