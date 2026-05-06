<?php

use NimblePHP\Framework\Abstracts\AbstractApiController;
use NimblePHP\Framework\Container\ServiceContainer;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\ApiExceptionHandler;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use PHPUnit\Framework\TestCase;

class AbstractApiControllerTest extends TestCase
{

    protected function setUp(): void
    {
        Kernel::$middlewareManager = new MiddlewareManager();
        Kernel::$serviceContainer = ServiceContainer::getInstance();
        Kernel::$serviceContainer->set('kernel.response', new Response());
        ApiExceptionHandler::reset();
    }

    public function testAfterConstructRegistersExceptionHandler()
    {
        $this->buildController();

        $namespaces = array_column(Kernel::$middlewareManager->getList(), 'namespace');
        $this->assertContains(ApiExceptionHandler::class, $namespaces);
    }

    public function testAfterConstructIsIdempotent()
    {
        $this->buildController();
        $this->buildController();
        $this->buildController();

        $namespaces = array_column(Kernel::$middlewareManager->getList(), 'namespace');
        $registered = array_filter($namespaces, fn ($ns) => $ns === ApiExceptionHandler::class);

        $this->assertCount(1, $registered);
    }

    public function testAfterConstructAddsJsonContentTypeHeader()
    {
        $controller = $this->buildController();

        $reflection = new ReflectionClass($controller->response);
        $headersProperty = $reflection->getProperty('headers');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($controller->response);

        $this->assertSame('application/json', $headers['Content-Type'] ?? null);
    }

    public function testJsonReturnsEmptyArrayForEmptyBody()
    {
        $controller = $this->buildController('');

        $this->assertSame([], $this->invoke($controller, 'json'));
    }

    public function testJsonThrowsOnInvalidPayload()
    {
        $controller = $this->buildController('not-json');

        $this->expectException(NimbleException::class);
        $this->invoke($controller, 'json');
    }

    public function testJsonRejectsScalarPayload()
    {
        $controller = $this->buildController('"just a string"');

        $this->expectException(NimbleException::class);
        $this->invoke($controller, 'json');
    }

    public function testJsonDecodesObjectBody()
    {
        $controller = $this->buildController('{"foo":"bar","n":1}');

        $this->assertSame(['foo' => 'bar', 'n' => 1], $this->invoke($controller, 'json'));
    }

    public function testJsonIsCached()
    {
        $controller = $this->buildController('{"a":1}');

        $first = $this->invoke($controller, 'json');
        $second = $this->invoke($controller, 'json');

        $this->assertSame($first, $second);
    }

    public function testInputReadsFromBodyFirst()
    {
        $_GET['x'] = 'from_query';
        $_POST['x'] = 'from_post';

        try {
            $controller = $this->buildController('{"x":"from_body"}');
            $this->assertSame('from_body', $this->invoke($controller, 'input', 'x'));
        } finally {
            unset($_GET['x'], $_POST['x']);
        }
    }

    public function testInputFallsBackToPostThenQuery()
    {
        $_GET['only_query'] = 'q';
        $_POST['only_post'] = 'p';

        try {
            $controller = $this->buildController('{}');

            $this->assertSame('p', $this->invoke($controller, 'input', 'only_post'));
            $this->assertSame('q', $this->invoke($controller, 'input', 'only_query'));
            $this->assertSame('default', $this->invoke($controller, 'input', 'missing', 'default'));
        } finally {
            unset($_GET['only_query'], $_POST['only_post']);
        }
    }

    private function buildController(?string $body = null): AbstractApiController
    {
        $controller = new class extends AbstractApiController {
        };
        $controller->name = 'Test';
        $controller->action = 'test';
        $controller->request = $body === null
            ? new Request()
            : new StubJsonRequest($body);
        $controller->afterConstruct();

        return $controller;
    }

    private function invoke(AbstractApiController $controller, string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, ...$args);
    }

}

readonly class StubJsonRequest extends Request
{

    public string $stubBody;

    public function __construct(string $body)
    {
        parent::__construct();
        $this->stubBody = $body;
    }

    public function getBody(): string
    {
        return $this->stubBody;
    }

}
