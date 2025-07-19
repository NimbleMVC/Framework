<?php

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Storage;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    private Storage $storage;
    private string $testDirectory = 'test_storage';
    private string $testFilePath = 'test_file.txt';
    private string $testContent = 'This is test content';

    protected function setUp(): void
    {
        // Tworzymy tymczasowy katalog z unikalną nazwą
        $tempDir = sys_get_temp_dir() . '/nimble_test_' . uniqid();

        // Ustawiamy ścieżkę projektu Kernel
        Kernel::$projectPath = $tempDir;

        // Tworzymy strukturę katalogów
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        if (!is_dir($tempDir . '/storage')) {
            mkdir($tempDir . '/storage', 0777, true);
        }

        // Inicjalizujemy klasę Storage
        $this->storage = new Storage($this->testDirectory);

        // Upewniamy się, że zaczynamy z czystego stanu
        if (file_exists($this->storage->getFullPath($this->testFilePath))) {
            unlink($this->storage->getFullPath($this->testFilePath));
        }
    }

    protected function tearDown(): void
    {
        // Czyścimy pliki testowe
        if (file_exists($this->storage->getFullPath($this->testFilePath))) {
            @unlink($this->storage->getFullPath($this->testFilePath));
        }

        // Rekursywnie usuwamy katalog testowy
        $this->removeDirectory(Kernel::$projectPath);
    }

    /**
     * Rekursywnie usuwa katalog
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object == "." || $object == "..") {
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

    public function testPutAndGet()
    {
        // Test putting content
        $result = $this->storage->put($this->testFilePath, $this->testContent);
        $this->assertTrue($result);
        $this->assertFileExists($this->storage->getFullPath($this->testFilePath));

        // Test getting content
        $content = $this->storage->get($this->testFilePath);
        $this->assertEquals($this->testContent, $content);

        // Test getting non-existent file
        $nonExistentContent = $this->storage->get('non_existent.txt');
        $this->assertNull($nonExistentContent);
    }

    public function testAppend()
    {
        // First put some content
        $this->storage->put($this->testFilePath, $this->testContent);

        // Then append more content
        $appendContent = ' - Appended text';
        $result = $this->storage->append($this->testFilePath, $appendContent, '');
        $this->assertTrue($result);

        // Verify content was appended
        $fullContent = $this->storage->get($this->testFilePath);
        $this->assertEquals($this->testContent . $appendContent, $fullContent);
    }

    public function testDelete()
    {
        // First create a file
        $this->storage->put($this->testFilePath, $this->testContent);
        $this->assertFileExists($this->storage->getFullPath($this->testFilePath));

        // Then delete it
        $result = $this->storage->delete($this->testFilePath);
        $this->assertTrue($result);
        $this->assertFileDoesNotExist($this->storage->getFullPath($this->testFilePath));

        // Test deleting non-existent file
        $nonExistentResult = $this->storage->delete('non_existent.txt');
        $this->assertFalse($nonExistentResult);
    }

    public function testExists()
    {
        // Check non-existent file
        $this->assertFalse($this->storage->exists($this->testFilePath));

        // Create file and check again
        $this->storage->put($this->testFilePath, $this->testContent);
        $this->assertTrue($this->storage->exists($this->testFilePath));
    }

    public function testListFiles()
    {
        // Create a test file
        $this->storage->put($this->testFilePath, $this->testContent);

        // Test simple list
        $files = $this->storage->listFiles();
        $this->assertIsArray($files);
        $this->assertContains($this->testFilePath, $files);

        // Test extended list
        $extendedFiles = $this->storage->listFiles(true);
        $this->assertIsArray($extendedFiles);
        $this->assertArrayHasKey($this->testFilePath, $extendedFiles);
        $this->assertIsArray($extendedFiles[$this->testFilePath]);
        $this->assertArrayHasKey('size', $extendedFiles[$this->testFilePath]);
        $this->assertArrayHasKey('modified', $extendedFiles[$this->testFilePath]);
        $this->assertArrayHasKey('type', $extendedFiles[$this->testFilePath]);
        $this->assertArrayHasKey('path', $extendedFiles[$this->testFilePath]);
    }

    public function testGetMetadata()
    {
        // First create a file
        $this->storage->put($this->testFilePath, $this->testContent);

        // Get metadata
        $metadata = $this->storage->getMetadata($this->testFilePath);
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('modified', $metadata);
        $this->assertArrayHasKey('type', $metadata);
        $this->assertArrayHasKey('path', $metadata);

        // Size should match our test content
        $this->assertEquals(strlen($this->testContent), $metadata['size']);

        // Test non-existent file
        $nonExistentMetadata = $this->storage->getMetadata('non_existent.txt');
        $this->assertNull($nonExistentMetadata);
    }

    public function testCopyAndMove()
    {
        // Create a source file
        $sourcePath = $this->storage->getFullPath($this->testFilePath);
        file_put_contents($sourcePath, $this->testContent);

        // Test copy
        $destPath = 'copied_file.txt';
        $copyResult = $this->storage->copy($sourcePath, $destPath);
        $this->assertTrue($copyResult);
        $this->assertTrue($this->storage->exists($destPath));
        $this->assertEquals($this->testContent, $this->storage->get($destPath));

        // Test move
        $movedPath = 'moved_file.txt';
        $moveResult = $this->storage->move($sourcePath, $movedPath);
        $this->assertTrue($moveResult);
        $this->assertTrue($this->storage->exists($movedPath));
        $this->assertEquals($this->testContent, $this->storage->get($movedPath));
        $this->assertFileDoesNotExist($sourcePath);

        // Clean up the extra test files
        $this->storage->delete($destPath);
        $this->storage->delete($movedPath);
    }
}