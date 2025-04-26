<?php

use NimblePHP\Framework\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        // Symulujemy globalne tablice
        $_GET = ['page' => '1', 'sort' => 'name'];
        $_POST = ['username' => 'testuser', 'password' => 'secret'];
        $_COOKIE = ['session_id' => 'abc123'];
        $_FILES = [
            'avatar' => [
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'tmp_name' => '/tmp/phpXXX',
                'error' => 0
            ]
        ];
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/users/profile',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ];

        $this->request = new Request();
    }

    public function testGetQuery()
    {
        $this->assertEquals('1', $this->request->getQuery('page'));
        $this->assertEquals('name', $this->request->getQuery('sort'));
        $this->assertEquals('default', $this->request->getQuery('limit', 'default'));
        $this->assertNull($this->request->getQuery('nonexistent'));
    }

    public function testGetPost()
    {
        $this->assertEquals('testuser', $this->request->getPost('username'));
        $this->assertEquals('secret', $this->request->getPost('password'));
        $this->assertEquals('default', $this->request->getPost('email', 'default'));
        $this->assertNull($this->request->getPost('nonexistent'));
    }

    public function testGetCookie()
    {
        $this->assertEquals('abc123', $this->request->getCookie('session_id'));
        $this->assertEquals('default', $this->request->getCookie('theme', 'default'));
        $this->assertNull($this->request->getCookie('nonexistent'));
    }

    public function testGetFile()
    {
        $file = $this->request->getFile('avatar');
        $this->assertIsArray($file);
        $this->assertEquals('test.jpg', $file['name']);
        $this->assertNull($this->request->getFile('nonexistent'));
    }

    public function testGetMethod()
    {
        $this->assertEquals('POST', $this->request->getMethod());
    }

    public function testGetUri()
    {
        $this->assertEquals('/users/profile', $this->request->getUri());
    }

    public function testIsAjax()
    {
        $this->assertTrue($this->request->isAjax());

        // Test when it's not an AJAX request
        $_SERVER['HTTP_X_REQUESTED_WITH'] = '';
        $request = new Request();
        $this->assertFalse($request->isAjax());
    }

    public function testIssetMethods()
    {
        $this->assertTrue($this->request->issetQuery('page'));
        $this->assertFalse($this->request->issetQuery('nonexistent'));

        $this->assertTrue($this->request->issetPost('username'));
        $this->assertFalse($this->request->issetPost('nonexistent'));

        $this->assertTrue($this->request->issetCookie('session_id'));
        $this->assertFalse($this->request->issetCookie('nonexistent'));
    }

    public function testGetAllQuery()
    {
        $allQuery = $this->request->getAllQuery();
        $this->assertIsArray($allQuery);
        $this->assertArrayHasKey('page', $allQuery);
        $this->assertArrayHasKey('sort', $allQuery);
        $this->assertEquals('1', $allQuery['page']);
        $this->assertEquals('name', $allQuery['sort']);
    }

    public function testGetAllPost()
    {
        $allPost = $this->request->getAllPost();
        $this->assertIsArray($allPost);
        $this->assertArrayHasKey('username', $allPost);
        $this->assertArrayHasKey('password', $allPost);
        $this->assertEquals('testuser', $allPost['username']);
        $this->assertEquals('secret', $allPost['password']);
    }

    public function testGetServer()
    {
        $this->assertEquals('POST', $this->request->getServer('REQUEST_METHOD'));
        $this->assertEquals('default', $this->request->getServer('NONEXISTENT', 'default'));
    }
}