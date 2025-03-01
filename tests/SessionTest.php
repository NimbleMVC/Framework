<?php

use NimblePHP\framework\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        session_destroy();
    }

    public function testSetAndGetSession()
    {
        $session = new Session();
        $session->set('user', 'JohnDoe');
        $this->assertEquals('JohnDoe', $session->get('user'));
    }

    public function testSessionExists()
    {
        $session = new Session();
        $session->set('user', 'JohnDoe');
        $this->assertTrue($session->exists('user'));
        $this->assertFalse($session->exists('non_existing_key'));
    }

    public function testRemoveSession()
    {
        $session = new Session();
        $session->set('user', 'JohnDoe');
        $session->remove('user');
        $this->assertNull($session->get('user'));
    }

    public function testDestroySession()
    {
        $session = new Session();
        $session->set('user', 'JohnDoe');
        $session->destroy();
        $this->assertNull($session->get('user'));
        $this->assertFalse($session->exists('user'));
    }

    public function testRegenerateSessionId()
    {
        $session = new Session();
        $oldSessionId = session_id();
        $session->regenerate();
        $this->assertNotEquals($oldSessionId, session_id());
    }

    public function testRegenerateSessionIdWithRemoveOldSession()
    {
        $session = new Session();
        $oldSessionId = session_id();
        $session->regenerate(true);
        $this->assertNotEquals($oldSessionId, session_id());
    }
}
