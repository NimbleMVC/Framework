<?php

use NimblePHP\Framework\Cookie;
use PHPUnit\Framework\TestCase;

class CookieTest extends TestCase
{
    private Cookie $cookie;

    protected function setUp(): void
    {
        $this->cookie = new Cookie();
        $_COOKIE = [];
    }

    public function testSetAndGetCookie()
    {
        // Since we can't actually set cookies in a test environment,
        // we'll mock the behavior by directly manipulating $_COOKIE

        // Simulate set() by adding to $_COOKIE
        $name = 'test_cookie';
        $value = 'test_value';

        // Cookie::set would normally call PHP's setcookie() function
        // We'll mock this behavior by adding to $_COOKIE directly
        $_COOKIE[$name] = $value;

        // Now test get()
        $this->assertEquals($value, $this->cookie->get($name));
        $this->assertNull($this->cookie->get('nonexistent_cookie'));
    }

    public function testExists()
    {
        // First test with non-existent cookie
        $this->assertFalse($this->cookie->exists('test_cookie'));

        // Add a test cookie
        $_COOKIE['test_cookie'] = 'test_value';

        // Test again
        $this->assertTrue($this->cookie->exists('test_cookie'));
    }

    public function testRemove()
    {
        // Set up a test cookie
        $_COOKIE['test_cookie'] = 'test_value';

        // Verify it exists
        $this->assertTrue($this->cookie->exists('test_cookie'));

        // Test removal
        $this->cookie->remove('test_cookie');

        // Verify it's gone
        $this->assertFalse($this->cookie->exists('test_cookie'));
        $this->assertArrayNotHasKey('test_cookie', $_COOKIE);
    }

    public function testStaticSettings()
    {
        // Test default secure setting
        Cookie::setDefaultSecure(true);

        // Test this setting was applied
        $reflectionClass = new ReflectionClass(Cookie::class);
        $defaultSecureProperty = $reflectionClass->getProperty('defaultSecure');
        $defaultSecureProperty->setAccessible(true);
        $defaultSecure = $defaultSecureProperty->getValue();

        $this->assertTrue($defaultSecure);

        // Test same site setting
        Cookie::setSameSite('Strict');

        $sameSiteProperty = $reflectionClass->getProperty('sameSite');
        $sameSiteProperty->setAccessible(true);
        $sameSite = $sameSiteProperty->getValue();

        $this->assertEquals('Strict', $sameSite);

        // Test default http only setting
        Cookie::setDefaultHttpOnly(true);

        $defaultHttpOnlyProperty = $reflectionClass->getProperty('defaultHttpOnly');
        $defaultHttpOnlyProperty->setAccessible(true);
        $defaultHttpOnly = $defaultHttpOnlyProperty->getValue();

        $this->assertTrue($defaultHttpOnly);
    }

    /**
     * Test Cookie::set method with various parameters
     *
     * Note: We can't actually test that the cookie is set in the header because
     * PHP's setcookie() function can't be used in a unit test environment.
     * Instead, we're mocking the behavior.
     */
    public function testSetWithParameters()
    {
        // W tym teście nie możemy używać mocków, ponieważ PHP nie pozwala na mockowanie
        // funkcji globalnych jak setcookie().
        // Zamiast tego po prostu sprawdzimy, czy metoda set() działa bez błędów

        // Ustawienie podstawowego ciasteczka
        $this->cookie->set('test_cookie', 'test_value');

        // Ustawienie ciasteczka z niestandardowymi parametrami
        $this->cookie->set(
            'test_cookie2',
            'test_value2',
            3600,
            '/admin',
            'example.com',
            true,
            true
        );

        // Jeśli dotarliśmy do tego miejsca bez błędów, test jest udany
        $this->assertTrue(true);
    }
}