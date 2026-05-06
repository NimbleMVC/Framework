<?php

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\CorsMiddleware;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use PHPUnit\Framework\TestCase;

class CorsMiddlewareTest extends TestCase
{

    private array $envBackup;

    private array $serverBackup;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->serverBackup = $_SERVER;
        Kernel::$middlewareManager = new MiddlewareManager();
        CorsMiddleware::reset();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $_SERVER = $this->serverBackup;
        CorsMiddleware::reset();
    }

    public function testRegisterFromEnvSkippedWhenOriginsEmpty()
    {
        $_ENV['API_CORS_ORIGINS'] = '';
        CorsMiddleware::registerFromEnv();

        $namespaces = array_column(Kernel::$middlewareManager->getList(), 'namespace');
        $this->assertNotContains(CorsMiddleware::class, $namespaces);
    }

    public function testRegisterFromEnvAddsMiddlewareWhenOriginsConfigured()
    {
        $_ENV['API_CORS_ORIGINS'] = 'https://app.example.com';
        CorsMiddleware::registerFromEnv();
        CorsMiddleware::registerFromEnv();

        $namespaces = array_column(Kernel::$middlewareManager->getList(), 'namespace');
        $registered = array_filter($namespaces, fn ($ns) => $ns === CorsMiddleware::class);

        $this->assertCount(1, $registered);
    }

    public function testNoOriginHeaderEmitsNothing()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        unset($_SERVER['HTTP_ORIGIN']);

        $middleware = $this->buildSpy();
        $middleware->afterBootstrap();

        $this->assertSame([], $middleware->headers);
        $this->assertFalse($middleware->exited);
    }

    public function testWildcardOriginEmitsStarWhenCredentialsDisabled()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        $_ENV['API_CORS_CREDENTIALS'] = 'false';
        $_SERVER['HTTP_ORIGIN'] = 'https://anything.example';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $middleware = $this->buildSpy();
        $middleware->afterBootstrap();

        $this->assertSame('*', $middleware->headers['Access-Control-Allow-Origin'] ?? null);
        $this->assertSame('Origin', $middleware->headers['Vary'] ?? null);
        $this->assertArrayNotHasKey('Access-Control-Allow-Credentials', $middleware->headers);
    }

    public function testWildcardOriginEchoesOriginWhenCredentialsEnabled()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        $_ENV['API_CORS_CREDENTIALS'] = 'true';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $middleware = $this->buildSpy();
        $middleware->afterBootstrap();

        $this->assertSame('https://app.example.com', $middleware->headers['Access-Control-Allow-Origin'] ?? null);
        $this->assertSame('true', $middleware->headers['Access-Control-Allow-Credentials'] ?? null);
    }

    public function testAllowListedOriginIsEchoed()
    {
        $_ENV['API_CORS_ORIGINS'] = 'https://other.example, https://app.example.com';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $middleware = $this->buildSpy();
        $middleware->afterBootstrap();

        $this->assertSame('https://app.example.com', $middleware->headers['Access-Control-Allow-Origin'] ?? null);
    }

    public function testOriginNotInAllowListEmitsNothing()
    {
        $_ENV['API_CORS_ORIGINS'] = 'https://app.example.com';
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.example';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $middleware = $this->buildSpy();
        $middleware->afterBootstrap();

        $this->assertSame([], $middleware->headers);
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

        $middleware = $this->buildSpy();
        $middleware->afterBootstrap();

        $this->assertTrue($middleware->exited);
        $this->assertSame('GET,POST,DELETE', $middleware->headers['Access-Control-Allow-Methods'] ?? null);
        $this->assertSame('Content-Type,Authorization', $middleware->headers['Access-Control-Allow-Headers'] ?? null);
        $this->assertSame('120', $middleware->headers['Access-Control-Max-Age'] ?? null);
    }

    public function testOptionsWithoutPreflightHeaderDoesNotShortCircuit()
    {
        $_ENV['API_CORS_ORIGINS'] = '*';
        $_SERVER['HTTP_ORIGIN'] = 'https://app.example.com';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);

        $middleware = $this->buildSpy();
        $middleware->afterBootstrap();

        $this->assertFalse($middleware->exited);
        $this->assertArrayNotHasKey('Access-Control-Allow-Methods', $middleware->headers);
    }

    private function buildSpy(): CorsMiddleware
    {
        return new class extends CorsMiddleware {

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
