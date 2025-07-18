<?php

use NimblePHP\Framework\Log;
use NimblePHP\Framework\Kernel;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/log_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        mkdir($this->tempDir . '/storage', 0777, true);
        mkdir($this->tempDir . '/storage/logs', 0777, true);
        Kernel::$projectPath = $this->tempDir;
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

    public function testLogWritesToFile()
    {
        $_ENV['LOG'] = true;
        Log::log('Test message', 'INFO', ['foo' => 'bar']);
        $logFiles = glob($this->tempDir . '/storage/logs/*.log.json');
        $this->assertNotEmpty($logFiles);
        $content = file_get_contents($logFiles[0]);
        $this->assertStringContainsString('Test message', $content);
    }

    public function testLogSessionIsGenerated()
    {
        Log::init();
        $this->assertNotEmpty(Log::$session);
    }

    public function testGetBacktrace()
    {
        $backtrace = $this->callGetBacktrace();
        $this->assertIsArray($backtrace);
    }

    private function callGetBacktrace()
    {
        $reflection = new ReflectionClass(Log::class);
        $method = $reflection->getMethod('getBacktrace');
        $method->setAccessible(true);
        return $method->invoke(null);
    }
}