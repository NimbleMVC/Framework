<?php

use NimblePHP\framework\Log;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{

    public function testGetBacktrace()
    {
        $log = new Log();
        $reflection = new \ReflectionMethod(Log::class, 'getBacktrace');
        $result = $reflection->invoke($log);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('line', $result);
        $this->assertArrayHasKey('function', $result);
        $this->assertArrayHasKey('class', $result);
        $this->assertArrayHasKey('object', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('args', $result);
    }

    public function testDateTime()
    {
        $log = new Log();
        $reflection = new \ReflectionMethod(Log::class, 'getDatetime');
        $result = $reflection->invoke($log);
        $pattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/';
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    public function testGenerateSession()
    {
        $log = new Log();
        $reflection = new \ReflectionMethod(Log::class, 'generateSession');
        $reflection->invoke($log);
        $pattern = '/^[A-F0-9]{4}[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}[A-F0-9]{4}[A-F0-9]{4}$/';
        $this->assertMatchesRegularExpression($pattern, Log::$session);
    }

}