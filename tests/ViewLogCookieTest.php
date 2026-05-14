<?php

namespace {

use NimblePHP\Framework\Cookie;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use NimblePHP\Framework\View;
use PHPUnit\Framework\TestCase;
use TestSupport\RuntimeFunctionState;

require_once __DIR__ . '/TestRuntimeStubs.php';

class ViewLogCookieTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        RuntimeFunctionState::reset();
        $_ENV['LOG'] = true;
        Kernel::$middlewareManager = new MiddlewareManager();
        $this->projectPath = sys_get_temp_dir() . '/nimble_runtime_' . uniqid('', true);
        mkdir($this->projectPath . '/App/View', 0777, true);
        mkdir($this->projectPath . '/storage/logs', 0777, true);
        Kernel::$projectPath = $this->projectPath;
        $this->primeLogState();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectPath);
    }

    public function testViewRenderRunsHooksAndSendsConfiguredStatusCode(): void
    {
        file_put_contents($this->projectPath . '/App/View/demo.phtml', 'Hello <?= $name ?>');
        $middleware = new class {
            public array $events = [];

            public function processingViewData(array &$data): void
            {
                $data['name'] = strtoupper($data['name']);
                $this->events[] = 'processing';
            }

            public function beforeViewRender(array $previewData, string $viewName, string $filePath): void
            {
                $this->events[] = ['before', $previewData['name'], $viewName, basename($filePath)];
            }

            public function afterViewRender(array $previewData, string $viewName, string $filePath): void
            {
                $this->events[] = ['after', $previewData['name'], $viewName, basename($filePath)];
            }
        };
        Kernel::$middlewareManager->add($middleware);

        $view = new View();
        $view->setResponseCode(202);

        ob_start();
        $view->render('demo', ['name' => 'john']);
        $output = ob_get_clean();

        $this->assertSame('Hello JOHN', $output);
        $this->assertSame('HTTP/1.1 202 ', RuntimeFunctionState::$headers[0]['header']);
        $this->assertSame([
            'processing',
            ['before', 'JOHN', 'demo', 'demo.phtml'],
            ['after', 'JOHN', 'demo', 'demo.phtml'],
        ], $middleware->events);
    }

    public function testViewRenderThrowsForMissingTemplate(): void
    {
        $this->expectException(\NimblePHP\Framework\Exception\NotFoundException::class);
        (new View())->render('missing');
    }

    public function testCookieSetAndRemoveRecordExpectedOptions(): void
    {
        $cookie = new Cookie();
        Cookie::setDefaultSecure(true);
        Cookie::setDefaultHttpOnly(true);
        Cookie::setSameSite('Strict');

        $before = time();
        $cookie->set('token', 'abc', 60, '/admin', 'example.com', null, null);
        $_COOKIE['token'] = 'abc';
        $cookie->remove('token');

        $this->assertCount(2, RuntimeFunctionState::$cookies);
        $setCookie = RuntimeFunctionState::$cookies[0];
        $this->assertSame('token', $setCookie['name']);
        $this->assertSame('abc', $setCookie['value']);
        $this->assertSame('/admin', $setCookie['options']['path']);
        $this->assertSame('example.com', $setCookie['options']['domain']);
        $this->assertTrue($setCookie['options']['secure']);
        $this->assertTrue($setCookie['options']['httponly']);
        $this->assertSame('Strict', $setCookie['options']['samesite']);
        $this->assertGreaterThanOrEqual($before + 59, $setCookie['options']['expires']);

        $removeCookie = RuntimeFunctionState::$cookies[1];
        $this->assertSame('', $removeCookie['value']);
        $this->assertLessThan(time(), $removeCookie['options']['expires']);
        $this->assertFalse($cookie->exists('token'));
    }

    public function testLogNormalizesLevelsRunsHooksAndRotatesFiles(): void
    {
        $_GET = ['scope' => 'tests'];
        $middleware = new class {
            public array $messages = [];
            public array $payloads = [];

            public function beforeLog(string &$message): void
            {
                $message .= ' [hooked]';
                $this->messages[] = $message;
            }

            public function afterLog(array &$payload): void
            {
                $payload['content']['after'] = true;
                $this->payloads[] = $payload;
            }
        };
        Kernel::$middlewareManager->add($middleware);

        $currentFile = $this->projectPath . '/storage/logs/' . date('Y_m_d') . '.log.json';
        file_put_contents($currentFile, str_repeat('x', 11 * 1024 * 1024));

        for ($i = 0; $i < 31; $i++) {
            $backup = $this->projectPath . '/storage/logs/old-' . $i . '.log.json.bak';
            file_put_contents($backup, 'backup');
            touch($backup, time() - 1000 + $i);
        }

        RuntimeFunctionState::$randomValues = [1];

        $this->assertTrue(Log::log('Something happened', 'fatal_err', ['from' => 'test']));

        $files = glob($this->projectPath . '/storage/logs/*') ?: [];
        $backups = glob($this->projectPath . '/storage/logs/*.log.json.*') ?: [];

        $this->assertContains('Something happened [hooked]', $middleware->messages);
        $this->assertSame('CRITICAL', $middleware->payloads[0]['level']);
        $this->assertTrue($middleware->payloads[0]['content']['after']);
        $this->assertLessThanOrEqual(31, count($backups));
        $this->assertFileDoesNotExist($this->projectPath . '/storage/logs/old-0.log.json.bak');
        $this->assertNotEmpty(array_filter($files, static fn(string $file): bool => str_contains($file, date('Y_m_d') . '.log.json.')));
    }

    private function primeLogState(): void
    {
        $session = new \ReflectionProperty(Log::class, 'session');
        $session->setAccessible(true);
        $session->setValue(null, 'TEST-SESSION');

        $storage = new \ReflectionProperty(Log::class, 'storage');
        $storage->setAccessible(true);
        $storage->setValue(null, new \NimblePHP\Framework\Storage('logs'));
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

}
