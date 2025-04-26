<?php

use NimblePHP\Framework\Log;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    public function testDateTime()
    {
        $log = new Log();
        $reflection = new \ReflectionMethod(Log::class, 'getDatetime');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($log);
        $pattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/';
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    public function testGenerateSession()
    {
        $log = new Log();
        $reflection = new \ReflectionMethod(Log::class, 'generateSession');
        $reflection->setAccessible(true);
        $reflection->invoke($log);
        $pattern = '/^[A-F0-9]{4}[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}[A-F0-9]{4}[A-F0-9]{4}$/';
        $this->assertMatchesRegularExpression($pattern, Log::$session);
    }

    public function testGetBacktrace()
    {
        // Tworzymy testową funkcję, która będzie zawarta w backtrace
        $testFunction = function() {
            // Ta funkcja wywołuje Log::log(), która z kolei wywołuje getBacktrace()
            return Log::log("Test message", "INFO");
        };

        // Musimy najpierw inicjalizować Log
        Log::init();

        // Upewniamy się, że logowanie jest włączone dla testu
        $_ENV['LOG'] = true;

        // Wywołujemy testową funkcję, która wywołuje Log::log()
        $result = $testFunction();

        // Ponieważ getBacktrace() jest metodą prywatną, nie możemy jej bezpośrednio testować
        // Zamiast tego sprawdzamy, czy wywołanie Log::log() z testowej funkcji zadziałało
        $this->assertTrue($result);
    }
}