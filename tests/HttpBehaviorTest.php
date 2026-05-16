<?php

namespace {

use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\Event\Framework\AfterResponseSendEvent;
use NimblePHP\Framework\Event\Framework\BeforeResponseSendEvent;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use PHPUnit\Framework\TestCase;
use TestSupport\RuntimeFunctionState;

require_once __DIR__ . '/TestRuntimeStubs.php';

class HttpBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        RuntimeFunctionState::reset();
        Kernel::$middlewareManager = new MiddlewareManager();
        Kernel::$eventDispatcher = null;
        $_GET = [
            'escaped' => '<b>query</b>',
            'email' => 'john@example.com',
            'age' => '42',
            'price' => '19.95',
            'website' => 'https://nimblephp.com',
            'ip' => '127.0.0.1',
        ];
        $_POST = [
            'raw' => '<script>alert(1)</script>',
        ];
        $_COOKIE = [];
        $_FILES = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/http/test',
            'HTTP_AUTHORIZATION' => 'Bearer token',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US;q=0.9,pl;q=1.0,de;q=0.8',
        ];
    }

    protected function tearDown(): void
    {
        Kernel::$eventDispatcher = null;
    }

    public function testRequestCanReadHeadersBodyAndProtectedValues(): void
    {
        RuntimeFunctionState::$phpInput = '{"message":"hello"}';
        $request = new Request();

        $this->assertSame('&lt;b&gt;query&lt;/b&gt;', $request->getQuery('escaped'));
        $this->assertSame('<b>query</b>', $request->getQuery('escaped', null, false));
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $request->getPost('raw'));
        $this->assertSame('<script>alert(1)</script>', $request->getPost('raw', null, false));
        $this->assertSame('Bearer token', $request->getHeader('Authorization'));
        $this->assertSame('{"message":"hello"}', $request->getBody());
    }

    public function testRequestValidateInputSupportsSupportedTypes(): void
    {
        $request = new Request();

        $this->assertSame('john@example.com', $request->validateInput('email', 'email'));
        $this->assertSame(42, $request->validateInput('age', 'int'));
        $this->assertSame(19.95, $request->validateInput('price', 'float'));
        $this->assertSame('https://nimblephp.com', $request->validateInput('website', 'url'));
        $this->assertSame('127.0.0.1', $request->validateInput('ip', 'ip'));
        $this->assertSame('john@example.com', $request->validateInput('email', 'string'));
        $this->assertNull($request->validateInput('missing', 'string'));
        $this->assertNull($request->validateInput('escaped', 'email'));
    }

    public function testRequestBrowserLanguageParsingKeepsPriorityAndShortCodes(): void
    {
        $request = new Request();

        $this->assertSame(['pl', 'en-US', 'en', 'de'], $request->getBrowserLanguages());
    }

    public function testRequestHasPostReflectsGlobalState(): void
    {
        $request = new Request();
        $this->assertTrue($request->hasPost());

        $_POST = [];
        $this->assertFalse((new Request())->hasPost());
    }

    public function testResponseSendWritesHeadersAndContent(): void
    {
        $response = new Response();
        $events = [];
        Kernel::getEventDispatcher()->addListener(BeforeResponseSendEvent::class, function (BeforeResponseSendEvent $event) use (&$events): void {
            $events[] = ['before', $event->statusCode, $event->content, $event->die];
            $event->statusCode = 203;
            $event->statusText = 'Non-Authoritative Information';
            $event->headers['X-Event'] = 'before-send';
            $event->content = 'event-payload';
        }, 100);
        Kernel::getEventDispatcher()->addListener(AfterResponseSendEvent::class, function (AfterResponseSendEvent $event) use (&$events): void {
            $events[] = ['after', $event->statusCode, $event->content, $event->die];
        }, 100);
        $response->setStatusCode(202, 'Accepted');
        $response->addHeader('X-Test', 'yes');
        $response->setContent('payload');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('event-payload', $output);
        $this->assertSame('HTTP/1.1 203 Non-Authoritative Information', RuntimeFunctionState::$headers[0]['header']);
        $this->assertSame('X-Test: yes', RuntimeFunctionState::$headers[1]['header']);
        $this->assertSame('X-Event: before-send', RuntimeFunctionState::$headers[2]['header']);
        $this->assertSame([
            ['before', 202, 'payload', false],
            ['after', 203, 'event-payload', false],
        ], $events);
    }

    public function testResponseJsonHelpersProduceExpectedPayloads(): void
    {
        $response = new Response();

        ob_start();
        $response->success(['id' => 5], 201, 'Created');
        $successOutput = ob_get_clean();
        $successPayload = json_decode($successOutput, true);

        $this->assertTrue($successPayload['success']);
        $this->assertSame(201, $successPayload['code']);
        $this->assertSame(['id' => 5], $successPayload['data']);

        RuntimeFunctionState::reset();
        ob_start();
        $response->error('Broken', 422, ['field' => 'email']);
        $errorOutput = ob_get_clean();
        $errorPayload = json_decode($errorOutput, true);

        $this->assertFalse($errorPayload['success']);
        $this->assertSame(422, $errorPayload['code']);
        $this->assertSame(['field' => 'email'], $errorPayload['data']);

        RuntimeFunctionState::reset();
        ob_start();
        $response->paginated([['id' => 1]], 13, 2, 5);
        $paginatedOutput = ob_get_clean();
        $paginatedPayload = json_decode($paginatedOutput, true);

        $this->assertSame(3, $paginatedPayload['pagination']['pages']);
        $this->assertSame(2, $paginatedPayload['pagination']['page']);

        RuntimeFunctionState::reset();
        ob_start();
        $response->created(['id' => 10], 'Saved');
        $createdPayload = json_decode((string)ob_get_clean(), true);

        $this->assertSame(201, $createdPayload['code']);
        $this->assertSame('Saved', $createdPayload['message']);

        RuntimeFunctionState::reset();
        ob_start();
        $response->noContent();
        $this->assertSame('', ob_get_clean());
        $this->assertSame('HTTP/1.1 204 ', RuntimeFunctionState::$headers[0]['header']);
    }

    public function testResponseSetJsonContentThrowsForInvalidUtf8(): void
    {
        $response = new Response();

        $this->expectException(NimbleException::class);
        $this->expectExceptionMessage('JSON encoding failed');
        $response->setJsonContent(['broken' => "\xB1\x31"]);
    }

    public function testActionDisabledAndAjaxModesAreEnforced(): void
    {
        $controller = new class extends \NimblePHP\Framework\Abstracts\AbstractController {
            public function demo(): void
            {
            }
        };

        $disabledAction = new Action('disabled');
        $ajaxAction = new Action('ajax');

        try {
            $disabledAction->handle($controller, 'demo');
            $this->fail('Disabled action should throw');
        } catch (NotFoundException $exception) {
            $this->assertSame('Method demo is disabled', $exception->getMessage());
        }

        try {
            $ajaxAction->handle($controller, 'demo');
            $this->fail('Non-AJAX action should throw');
        } catch (NotFoundException $exception) {
            $this->assertSame('Method demo is allowed only for AJAX requests', $exception->getMessage());
        }

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $ajaxAction->handle($controller, 'demo');
        $this->addToAssertionCount(1);
    }
}

}
