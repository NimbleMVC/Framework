<?php

use NimblePHP\Framework\Cache;
use NimblePHP\Framework\Kernel;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private Cache $cache;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cache_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        mkdir($this->tempDir . '/storage', 0777, true);
        mkdir($this->tempDir . '/storage/cache', 0777, true);
        Kernel::$projectPath = $this->tempDir;
        $this->cache = new Cache();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === "." || $object === "..") {
                continue;
            }
            $path = $dir . "/" . $object;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testSetAndGetString()
    {
        $this->cache->set('test_key', 'test_value', 3600);
        $result = $this->cache->get('test_key');
        $this->assertEquals('test_value', $result);
    }

    public function testSetAndGetArray()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $this->cache->set('test_array', $data, 3600);
        $result = $this->cache->get('test_array');
        $this->assertEquals($data, $result);
    }

    public function testSetAndGetObject()
    {
        $object = new stdClass();
        $object->property = 'value';
        $this->cache->set('test_object', $object, 3600);
        $result = $this->cache->get('test_object');
        $this->assertEquals($object, $result);
    }

    public function testSetAndGetInteger()
    {
        $this->cache->set('test_int', 42, 3600);
        $result = $this->cache->get('test_int');
        $this->assertEquals(42, $result);
    }

    public function testSetAndGetBoolean()
    {
        $this->cache->set('test_bool', true, 3600);
        $result = $this->cache->get('test_bool');
        $this->assertTrue($result);
    }

    public function testSetAndGetNull()
    {
        $this->cache->set('test_null', null, 3600);
        $result = $this->cache->get('test_null');
        $this->assertNull($result);
    }

    public function testGetNonExistentKey()
    {
        $result = $this->cache->get('non_existent_key');
        $this->assertNull($result);
    }

    public function testGetWithDefaultValue()
    {
        $result = $this->cache->get('non_existent_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testHasKey()
    {
        $this->cache->set('test_key', 'test_value', 3600);
        $this->assertTrue($this->cache->has('test_key'));
        $this->assertFalse($this->cache->has('non_existent_key'));
    }

    public function testDeleteKey()
    {
        $this->cache->set('test_key', 'test_value', 3600);
        $this->assertTrue($this->cache->has('test_key'));
        $this->cache->delete('test_key');
        $this->assertFalse($this->cache->has('test_key'));
    }

    public function testDeleteNonExistentKey()
    {
        $this->cache->delete('non_existent_key');
        $this->assertTrue(true);
    }

    public function testClear()
    {
        $this->cache->set('key1', 'value1', 3600);
        $this->cache->set('key2', 'value2', 3600);
        $this->assertTrue($this->cache->has('key1'));
        $this->assertTrue($this->cache->has('key2'));
        $this->cache->clear();
        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
    }

    public function testExpiration()
    {
        $this->cache->set('test_key', 'test_value', 1);
        $this->assertEquals('test_value', $this->cache->get('test_key'));
        sleep(2);
        $result = $this->cache->get('test_key');
        $this->assertNull($result);
    }

    public function testMultipleKeys()
    {
        $this->cache->set('key1', 'value1', 3600);
        $this->cache->set('key2', 'value2', 3600);
        $this->cache->set('key3', 'value3', 3600);
        $this->assertEquals('value1', $this->cache->get('key1'));
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));
    }

    public function testOverwriteExistingKey()
    {
        $this->cache->set('test_key', 'old_value', 3600);
        $this->cache->set('test_key', 'new_value', 3600);
        $result = $this->cache->get('test_key');
        $this->assertEquals('new_value', $result);
    }

    public function testLargeData()
    {
        $largeData = str_repeat('Large data content. ', 1000);
        $this->cache->set('large_key', $largeData, 3600);
        $result = $this->cache->get('large_key');
        $this->assertEquals($largeData, $result);
    }

    public function testSpecialCharactersInKey()
    {
        $key = 'test_key_with_special_chars_!@#$%^&*()';
        $this->cache->set($key, 'test_value', 3600);
        $result = $this->cache->get($key);
        $this->assertEquals('test_value', $result);
    }

    public function testSpecialCharactersInValue()
    {
        $value = 'Value with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?';
        $this->cache->set('test_key', $value, 3600);
        $result = $this->cache->get('test_key');
        $this->assertEquals($value, $result);
    }

    public function testUnicodeCharacters()
    {
        $value = 'Unicode: ąćęłńóśźż ĄĆĘŁŃÓŚŹŻ';
        $this->cache->set('test_key', $value, 3600);
        $result = $this->cache->get('test_key');
        $this->assertEquals($value, $result);
    }

    public function testComplexDataStructure()
    {
        $data = [
            'string' => 'test',
            'int' => 42,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => (object)['property' => 'value'],
            'nested' => [
                'level1' => [
                    'level2' => 'deep_value'
                ]
            ]
        ];
        $this->cache->set('complex_key', $data, 3600);
        $result = $this->cache->get('complex_key');
        $this->assertEquals($data, $result);
    }

    public function testEmptyString()
    {
        $this->cache->set('empty_key', '', 3600);
        $result = $this->cache->get('empty_key');
        $this->assertEquals('', $result);
    }

    public function testZeroValue()
    {
        $this->cache->set('zero_key', 0, 3600);
        $result = $this->cache->get('zero_key');
        $this->assertEquals(0, $result);
    }

    public function testFalseValue()
    {
        $this->cache->set('false_key', false, 3600);
        $result = $this->cache->get('false_key');
        $this->assertFalse($result);
    }

    public function testEmptyArray()
    {
        $this->cache->set('empty_array_key', [], 3600);
        $result = $this->cache->get('empty_array_key');
        $this->assertEquals([], $result);
    }

    public function testNestedObjects()
    {
        $outer = new stdClass();
        $inner = new stdClass();
        $inner->value = 'nested_value';
        $outer->inner = $inner;
        $this->cache->set('nested_object_key', $outer, 3600);
        $result = $this->cache->get('nested_object_key');
        $this->assertEquals($outer, $result);
    }
}