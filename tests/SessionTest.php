<?php

use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{

    public function testSet()
    {
        $session = new \Nimblephp\framework\Session();
        $session->set('foo', 'bar');
        $this->assertEquals('bar', $_SESSION['foo']);
    }

    public function testGet()
    {
        $session = new \Nimblephp\framework\Session();
        $session->set('foo', 'bar');
        $this->assertEquals('bar', $session->get('foo'));
    }

    public function testExists()
    {
        $session = new \Nimblephp\framework\Session();
        $session->set('foo', 'bar');
        $this->assertTrue($session->exists('foo'));
    }

    public function testRemove()
    {
        $session = new \Nimblephp\framework\Session();
        $session->set('foo', 'bar');
        $session->remove('foo');
        $this->assertNull($session->get('foo'));
    }

    public function testDestroy()
    {
        $session = new \Nimblephp\framework\Session();
        $session->set('foo', 'bar');
        $session->destroy();
        $this->assertEquals(
            PHP_SESSION_NONE,
            session_status()
        );
    }

}