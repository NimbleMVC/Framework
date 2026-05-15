<?php

use NimblePHP\Framework\Cache;
use NimblePHP\Framework\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private Cache $cache;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cache_test_' . uniqid('', true);
        mkdir($this->tempDir . '/storage', 0777, true);

        Kernel::$projectPath = $this->tempDir;
        $this->cache = new Cache();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public static function cacheValueProvider(): array
    {
        $object = new stdClass();
        $object->property = 'value';

        $nestedObject = new stdClass();
        $nestedObject->inner = (object) ['value' => 'nested'];

        return [
            'string' => ['test_key_string', 'test_value'],
            'integer' => ['test_key_int', 42],
            'boolean true' => ['test_key_bool_true', true],
            'boolean false' => ['test_key_bool_false', false],
            'zero' => ['test_key_zero', 0],
            'empty string' => ['test_key_empty_string', ''],
            'empty array' => ['test_key_empty_array', []],
            'array' => ['test_key_array', ['key1' => 'value1', 'key2' => 'value2']],
            'object' => ['test_key_object', $object],
            'nested object' => ['test_key_nested_object', $nestedObject],
            'unicode' => ['test_key_unicode', 'Unicode: ąćęłńóśźż ĄĆĘŁŃÓŚŹŻ'],
            'special chars' => ['test_key_special_chars_!@#$%^&*()', 'Value with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?'],
            'complex structure' => [
                'test_key_complex',
                [
                    'string' => 'test',
                    'int' => 42,
                    'bool' => true,
                    'null' => null,
                    'array' => [1, 2, 3],
                    'object' => (object) ['property' => 'value'],
                    'nested' => [
                        'level1' => [
                            'level2' => 'deep_value'
                        ]
                    ]
                ]
            ],
            'large payload' => ['test_key_large', str_repeat('Large data content. ', 1000)],
            'null' => ['test_key_null', null],
        ];
    }

    public function testConstructorUsesDefaultCacheDirectory(): void
    {
        $this->assertDirectoryExists($this->tempDir . '/storage/cache');
    }

    public function testConstructorSupportsCustomCacheDirectory(): void
    {
        $cache = new Cache('custom-cache');

        $cache->set('custom-key', 'custom-value', 3600);

        $this->assertDirectoryExists($this->tempDir . '/storage/custom-cache');
        $this->assertSame('custom-value', $cache->get('custom-key'));
    }

    #[DataProvider('cacheValueProvider')]
    public function testSetAndGetSupportsDifferentValueTypes(string $key, mixed $value): void
    {
        $this->assertTrue($this->cache->set($key, $value, 3600));

        $this->assertEquals($value, $this->cache->get($key));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->cache->get('missing-key'));
    }

    public function testGetReturnsDefaultValueForMissingKey(): void
    {
        $this->assertSame('default-value', $this->cache->get('missing-key', 'default-value'));
    }

    public function testSetWithoutTtlUsesDefaultLifetime(): void
    {
        $before = time();
        $this->cache->set('default-ttl-key', 'value');
        $after = time();

        $cacheFile = $this->getCacheFilePath('default-ttl-key');
        $content = file_get_contents($cacheFile);
        $payload = unserialize($content);

        $this->assertIsArray($payload);
        $this->assertSame('value', $payload['value']);
        $this->assertGreaterThanOrEqual($before + 3600, $payload['expiry']);
        $this->assertLessThanOrEqual($after + 3600, $payload['expiry']);
    }

    public function testHasReturnsTrueForStoredNonNullValues(): void
    {
        $this->cache->set('existing-string', 'value', 3600);
        $this->cache->set('existing-false', false, 3600);
        $this->cache->set('existing-zero', 0, 3600);
        $this->cache->set('existing-empty', '', 3600);
        $this->cache->set('existing-null', null, 3600);

        $this->assertTrue($this->cache->has('existing-string'));
        $this->assertTrue($this->cache->has('existing-false'));
        $this->assertTrue($this->cache->has('existing-zero'));
        $this->assertTrue($this->cache->has('existing-empty'));
        $this->assertTrue($this->cache->has('existing-null'));
        $this->assertFalse($this->cache->has('missing-key'));
    }

    public function testDeleteRemovesExistingCacheEntry(): void
    {
        $this->cache->set('delete-key', 'delete-value', 3600);

        $this->assertTrue($this->cache->delete('delete-key'));
        $this->assertNull($this->cache->get('delete-key'));
        $this->assertFileDoesNotExist($this->getCacheFilePath('delete-key'));
    }

    public function testDeleteReturnsFalseForMissingEntry(): void
    {
        $this->assertFalse($this->cache->delete('missing-key'));
    }

    public function testClearRemovesAllCachedEntries(): void
    {
        $this->cache->set('key1', 'value1', 3600);
        $this->cache->set('key2', 'value2', 3600);
        $this->cache->set('key3', ['value3'], 3600);

        $this->assertTrue($this->cache->clear());
        $this->assertSame([], array_values(array_diff(scandir($this->tempDir . '/storage/cache'), ['.', '..'])));
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertFalse($this->cache->has('key3'));
    }

    public function testExpiredEntryReturnsDefaultAndDeletesFile(): void
    {
        $this->cache->set('expired-key', 'expired-value', -1);

        $this->assertSame('fallback', $this->cache->get('expired-key', 'fallback'));
        $this->assertFileDoesNotExist($this->getCacheFilePath('expired-key'));
    }

    public function testOverwriteExistingKeyStoresLatestValue(): void
    {
        $this->cache->set('overwrite-key', 'old-value', 3600);
        $this->cache->set('overwrite-key', 'new-value', 3600);

        $this->assertSame('new-value', $this->cache->get('overwrite-key'));
    }

    public function testInvalidSerializedPayloadReturnsDefaultValue(): void
    {
        file_put_contents($this->getCacheFilePath('invalid-payload'), 'not-serialized');

        $this->assertSame('fallback', $this->cache->get('invalid-payload', 'fallback'));
        $this->assertFalse($this->cache->has('invalid-payload'));
        $this->assertFileDoesNotExist($this->getCacheFilePath('invalid-payload'));
    }

    public function testInvalidPayloadMissingRequiredKeysReturnsDefaultValue(): void
    {
        file_put_contents($this->getCacheFilePath('missing-keys'), serialize(['value' => 'test']));

        $this->assertSame('fallback', $this->cache->get('missing-keys', 'fallback'));
        $this->assertFileDoesNotExist($this->getCacheFilePath('missing-keys'));
    }

    private function getCacheFilePath(string $key, string $directory = 'cache'): string
    {
        return $this->tempDir . '/storage/' . $directory . '/' . md5($key) . '.cache';
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
