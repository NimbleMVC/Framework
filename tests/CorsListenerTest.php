<?php

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Event\Framework\AfterBootstrapEvent;
use NimblePHP\Framework\Event\Listener\CorsListener;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use PHPUnit\Framework\TestCase;

class CorsListenerTest extends TestCase
{

    private array $envBackup;

    private array $serverBackup;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->serverBackup = $_SERVER;
        Kernel::$middlewareManager = new MiddlewareManager();
        Kernel::$eventDispatcher = null;
        CorsListener::reset();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $_SERVER = $this->serverBackup;
        Kernel::$eventDispatcher = null;
        CorsListener::reset();
    }

    public function testRegisterFromEnvSkippedWhenOriginsEmpty()
    {
        $_ENV['API_CORS_ORIGINS'] = '';
        CorsListener::registerFromEnv();

        $this->assertSame([], Kernel::getEventDispatcher()->getListeners());
    }

    public function testRegisterFromEnvAddsCorsEventListenerWhenOriginsConfigured()
    {
        $_ENV['API_CORS_ORIGINS'] = 'https://app.example.com';
        CorsListener::registerFromEnv();
        CorsListener::registerFromEnv();

        $registered = array_values(array_filter(
            Kernel::getEventDispatcher()->getListeners(),
            fn (array $listener): bool =>
                $listener['event'] === AfterBootstrapEvent::class
                && $listener['listener'] instanceof CorsListener
        ));

        $this->assertCount(1, $registered);
    }

    public function testNoOriginHeaderEmitsNothing()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        unset($_SERVER['HTTP_ORIGIN']);

        $listener = $this->buildSpy();
        $listener->handle(new AfterBootstrapEvent());

        $this->assertSame([], $listener->headers);
        $this->assertFalse($listener->exited);
    }

    public function testWildcardOriginEmitsStarWhenCredentialsDisabled()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        $_ENV['API_CORS_CREDENTIALS'] = 'false';
        $_SERVER['HTTP_ORIGIN'] = 'https://anything.example';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $listener = $this->buildSpy();
        $listener->handle(new AfterBootstrapEvent());

        $this->assertSame('*', $listener->headers['Access-Control-Allow-Origin'] ?? null);
        $this->assertSame('Origin', $listener->headers['Vary'] ?? null);
        $this->assertArrayNotHasKey('Access-Control-Allow-Credentials', $listener->headers);
    }

    public function testWildcardOriginEchoesOriginWhenCredentialsEnabled()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        $_ENV['API_CORS_CREDENTIALS'] = 'true';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $listener = $this->buildSpy();
        $listener->handle(new AfterBootstrapEvent());

        $this->assertSame('https://app.example.com', $listener->headers['Access-Control-Allow-Origin'] ?? null);
        $this->assertSame('true', $listener->headers['Access-Control-Allow-Credentials'] ?? null);
    }

    public function testAllowListedOriginIsEchoed()
    {
        $_ENV['API_CORS_ORIGINS'] = 'https://other.example, https://app.example.com';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $listener = $this->buildSpy();
        $listener->handle(new AfterBootstrapEvent());

        $this->assertSame('https://app.example.com', $listener->headers['Access-Control-Allow-Origin'] ?? null);
    }

    public function testOriginNotInAllowListEmitsNothing()
    {
        $_ENV['API_CORS_ORIGINS'] = 'https://app.example.com';
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.example';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $listener = $this->buildSpy();
        $listener->handle(new AfterBootstrapEvent());

        $this->assertSame([], $listener->headers);
    }

    public function testPreflightShortCircuitsAndSendsAllowHeaders()
    {
        $_ENV['API_CORS_ORIGINS'] = 'https://app.example.com';
        $_ENV['API_CORS_METHODS'] = 'GET,POST,DELETE';
        $_ENV['API_CORS_HEADERS'] = 'Content-Type,Authorization';
        $_ENV['API_CORS_MAX_AGE'] = '120';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'POST';

        $listener = $this->buildSpy();
        $listener->handle(new AfterBootstrapEvent());

        $this->assertTrue($listener->exited);
        $this->assertSame('GET,POST,DELETE', $listener->headers['Access-Control-Allow-Methods'] ?? null);
        $this->assertSame('Content-Type,Authorization', $listener->headers['Access-Control-Allow-Headers'] ?? null);
        $this->assertSame('120', $listener->headers['Access-Control-Max-Age'] ?? null);
    }

    public function testOptionsWithoutPreflightHeaderDoesNotShortCircuit()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);

        $listener = $this->buildSpy();
        $listener->handle(new AfterBootstrapEvent());

        $this->assertFalse($listener->exited);
        $this->assertArrayNotHasKey('Access-Control-Allow-Methods', $listener->headers);
    }

    private function buildSpy(): CorsListener
    {
        return new class extends CorsListener {

            public array $headers = [];

            public bool $exited = false;

            public ?int $exitStatus = null;

            protected function sendHeader(string $name, string $value): void
            {
                $this->headers[$name] = $value;
            }

            protected function terminate(int $statusCode): void
            {
                $this->exited = true;
                $this->exitStatus = $statusCode;
            }

        };
    }

}
