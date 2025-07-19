<?php

use NimblePHP\Framework\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
    }

    public function testSetAndGetContent()
    {
        $content = 'Test content';
        $this->response->setContent($content);
        $this->assertEquals($content, $this->response->getContent());
    }

    public function testSetJsonContent()
    {
        $data = ['name' => 'John', 'age' => 30];
        $this->response->setJsonContent($data);

        $this->assertEquals(json_encode($data), $this->response->getContent());

        // Verify JSON Content-Type header was added
        $reflectionClass = new ReflectionClass($this->response);
        $headersProperty = $reflectionClass->getProperty('headers');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($this->response);

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);

        // Test without adding Content-Type header
        $this->response->setJsonContent($data, false);

        $this->assertEquals(json_encode($data), $this->response->getContent());
    }

    public function testSetAndGetStatusCode()
    {
        $this->assertEquals(200, $this->response->getStatusCode()); // Default status code

        $this->response->setStatusCode(404, 'Not Found');
        $this->assertEquals(404, $this->response->getStatusCode());

        // Test status text
        $reflectionClass = new ReflectionClass($this->response);
        $statusTextProperty = $reflectionClass->getProperty('statusText');
        $statusTextProperty->setAccessible(true);
        $statusText = $statusTextProperty->getValue($this->response);

        $this->assertEquals('Not Found', $statusText);

        // Test without status text
        $this->response->setStatusCode(403);
        $this->assertEquals(403, $this->response->getStatusCode());
    }

    public function testAddHeader()
    {
        $this->response->addHeader('X-Custom-Header', 'TestValue');

        $reflectionClass = new ReflectionClass($this->response);
        $headersProperty = $reflectionClass->getProperty('headers');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($this->response);

        $this->assertArrayHasKey('X-Custom-Header', $headers);
        $this->assertEquals('TestValue', $headers['X-Custom-Header']);
    }

    public function testSend()
    {
        // Test output buffering
        ob_start();
        $this->response->setContent('Test output');
        $this->response->send(false);
        $output = ob_get_clean();

        $this->assertEquals('Test output', $output);
    }

//    public function testRedirectWithAjax()
//    {
//        // Przekierowanie wywoła exit(), więc nie możemy tego faktycznie testować
//        // Zamiast tego przetestujmy tylko, czy odpowiednie metody są wywoływane
//
//        // Mock Request do symulowania żądania AJAX
//        $mockRequest = $this->createMock(Request::class);
//        $mockRequest->method('isAjax')->willReturn(true);
//
//        // Mock dla samego Response, żeby przechwycić wywołania metod
//        $responseMock = $this->getMockBuilder(Response::class)
//            ->onlyMethods(['setStatusCode', 'setJsonContent', 'send'])
//            ->getMock();
//
//        // Ustawiamy oczekiwania dla mocka
//        $responseMock->expects($this->once())
//            ->method('setStatusCode')
//            ->with(200);
//
//        $responseMock->expects($this->once())
//            ->method('setJsonContent')
//            ->with([
//                'type' => 'redirect',
//                'url' => '/dashboard',
//            ]);
//
//        $responseMock->expects($this->once())
//            ->method('send')
//            ->with(true);
//
//        // Ustawiamy mockowany obiekt Request wewnątrz mocka Response
//        $reflectionClass = new ReflectionClass($responseMock);
//        $requestProperty = $reflectionClass->getProperty('request');
//        $requestProperty->setAccessible(true);
//        $requestProperty->setValue($responseMock, $mockRequest);
//
//        // Musimy owinąć wywołanie w try-catch, aby przechwycić moment, gdy kod próbuje zakończyć wykonanie
//        try {
//            // Korzystamy z refleksji do wywołania metody redirect bez exit()
//            $redirectMethod = $reflectionClass->getMethod('redirect');
//            $redirectMethod->setAccessible(true);
//
//            // Wywołujemy metodę redirect bezpośrednio, zamiast przez $responseMock->redirect()
//            // ponieważ chcemy przetestować wszystko do momentu exit()
//            $redirectMethod->invokeArgs($responseMock, ['/dashboard']);
//
//            // Jeśli dojdziemy tutaj, oznacza to, że exit() nie został wywołany,
//            // co jest nieprawidłowe w prawdziwej implementacji
//            $this->fail('The redirect method should call exit()');
//        } catch (\Exception $e) {
//            // Oczekujemy, że metoda zawiera exit(), więc test powinien zakończyć się powodzeniem
//            $this->assertTrue(true);
//        }
//    }
}