<?php

use NimblePHP\Framework\Log;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Storage;
use PHPUnit\Framework\TestCase;

class LogTestExtended extends TestCase
{
    protected function setUp(): void
    {
        // Tworzymy unikalny tymczasowy katalog dla testów
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

        if (!is_dir($tempDir . '/storage/logs')) {
            mkdir($tempDir . '/storage/logs', 0777, true);
        }

        // Włączamy logowanie w środowisku
        $_ENV['LOG'] = true;

        // Resetujemy statyczne właściwości klasy Log
        $reflectionClass = new ReflectionClass(Log::class);

        if ($reflectionClass->hasProperty('session')) {
            $sessionProperty = $reflectionClass->getProperty('session');
            $sessionProperty->setAccessible(true);
            $sessionProperty->setValue(null, null);
        }

        if ($reflectionClass->hasProperty('storage')) {
            $storageProperty = $reflectionClass->getProperty('storage');
            $storageProperty->setAccessible(true);
            $storageProperty->setValue(null, null);
        }
    }

    protected function tearDown(): void
    {
        // Czyścimy pliki logów
        $logFiles = glob(Kernel::$projectPath . '/storage/logs/*.log.json');
        if (is_array($logFiles)) {
            foreach ($logFiles as $file) {
                @unlink($file);
            }
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
        if (is_array($objects)) {
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
        }

        @rmdir($dir);
    }

    public function testInit()
    {
        // Before initialization
        $this->checkClassNotHasStaticAttribute('session', Log::class);
        $this->checkClassNotHasStaticAttribute('storage', Log::class);

        // Run init
        Log::init();

        // After initialization
        $this->checkClassHasStaticAttribute('session', Log::class);
        $this->checkClassHasStaticAttribute('storage', Log::class);

        // Verify session format
        $reflectionClass = new ReflectionClass(Log::class);
        $sessionProperty = $reflectionClass->getProperty('session');
        $sessionProperty->setAccessible(true);
        $session = $sessionProperty->getValue();

        $pattern = '/^[A-F0-9]{4}[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}[A-F0-9]{4}[A-F0-9]{4}$/';
        $this->assertMatchesRegularExpression($pattern, $session);

        // Verify storage is initialized
        $storageProperty = $reflectionClass->getProperty('storage');
        $storageProperty->setAccessible(true);
        $storage = $storageProperty->getValue();

        $this->assertInstanceOf(Storage::class, $storage);
    }

    public function testLog()
    {
        // Test basic logging
        $result = Log::log('Test message', 'INFO', ['key' => 'value']);
        $this->assertTrue($result);

        // Verify log file was created with correct content
        $logFiles = glob(Kernel::$projectPath . '/storage/logs/*.log.json');
        $this->assertCount(1, $logFiles);

        $logContent = file_get_contents($logFiles[0]);
        $this->assertNotEmpty($logContent);

        // Decode log entry
        $logEntry = json_decode($logContent, true);
        $this->assertIsArray($logEntry);
        $this->assertEquals('Test message', $logEntry['message']);
        $this->assertEquals('INFO', $logEntry['level']);
        $this->assertEquals(['key' => 'value'], $logEntry['content']);

        // Verify session is consistent
        $reflectionClass = new ReflectionClass(Log::class);
        $sessionProperty = $reflectionClass->getProperty('session');
        $sessionProperty->setAccessible(true);
        $session = $sessionProperty->getValue();

        $this->assertEquals($session, $logEntry['session']);
    }

    public function testLogWithLogDisabled()
    {
        // Disable logging
        $_ENV['LOG'] = false;

        // Attempt to log
        $result = Log::log('Test message', 'INFO');

        // Should return false when logging is disabled
        $this->assertFalse($result);

        // Verify no log file was created
        $logFiles = glob(Kernel::$projectPath . '/storage/logs/*.log.json');
        $this->assertCount(0, $logFiles);
    }

    public function testGetDatetime()
    {
        $reflectionMethod = new ReflectionMethod(Log::class, 'getDatetime');
        $reflectionMethod->setAccessible(true);

        $datetime = $reflectionMethod->invoke(null);

        // Verify format is correct (YYYY-MM-DD HH:MM:SS.microseconds)
        $pattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/';
        $this->assertMatchesRegularExpression($pattern, $datetime);

        // Verify it's a valid datetime string
        $date = \DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime);
        $this->assertInstanceOf(\DateTime::class, $date);
    }

    public function testGenerateSession()
    {
        $reflectionMethod = new ReflectionMethod(Log::class, 'generateSession');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke(null);

        // Verify session was generated
        $reflectionClass = new ReflectionClass(Log::class);
        $sessionProperty = $reflectionClass->getProperty('session');
        $sessionProperty->setAccessible(true);
        $session = $sessionProperty->getValue();

        // Check format (8-4-4-4-12 hexadecimal)
        $pattern = '/^[A-F0-9]{4}[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}[A-F0-9]{4}[A-F0-9]{4}$/';
        $this->assertMatchesRegularExpression($pattern, $session);
    }

    public function testGetBacktrace()
    {
        // Define a function that will call log() and then getBacktrace()
        $testFunction = function() {
            $reflectionMethod = new ReflectionMethod(Log::class, 'getBacktrace');
            $reflectionMethod->setAccessible(true);

            return $reflectionMethod->invoke(null);
        };

        // Call the function to get backtrace
        $backtrace = $testFunction();

        // Verify backtrace contains expected information
        $this->assertIsArray($backtrace);
        $this->assertArrayHasKey('file', $backtrace);
        $this->assertArrayHasKey('line', $backtrace);
        $this->assertArrayHasKey('function', $backtrace);

        // In a real logging call, function would be the caller of log()
        $this->assertEquals(__FILE__, $backtrace['file']);
    }

    /**
     * Helper method to check if a class has a static attribute
     */
    private function checkClassHasStaticAttribute(string $attributeName, string $className): void
    {
        $reflectionClass = new ReflectionClass($className);
        $this->assertTrue($reflectionClass->hasProperty($attributeName));

        $property = $reflectionClass->getProperty($attributeName);
        $property->setAccessible(true);
        $this->assertNotNull($property->getValue());
    }

    /**
     * Helper method to check if a class does not have a static attribute or it's null
     */
    private function checkClassNotHasStaticAttribute(string $attributeName, string $className): void
    {
        $reflectionClass = new ReflectionClass($className);
        if (!$reflectionClass->hasProperty($attributeName)) {
            $this->assertTrue(true);
            return;
        }

        $property = $reflectionClass->getProperty($attributeName);
        $property->setAccessible(true);
        $this->assertNull($property->getValue());
    }
}