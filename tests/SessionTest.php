<?php

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        // Zapewniamy, że mamy ustawiony Kernel::$projectPath dla inicjalizacji Session
        Kernel::$projectPath = sys_get_temp_dir() . '/nimble_test_' . uniqid();

        if (!is_dir(Kernel::$projectPath)) {
            mkdir(Kernel::$projectPath, 0777, true);
        }

        if (!is_dir(Kernel::$projectPath . '/storage')) {
            mkdir(Kernel::$projectPath . '/storage', 0777, true);
        }

        if (!is_dir(Kernel::$projectPath . '/storage/session')) {
            mkdir(Kernel::$projectPath . '/storage/session', 0777, true);
        }

        // Jeżeli istnieje już sesja, zamykamy ją przed zmianą ini_set
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Ustawiamy driver sesji na 'file'
        $_ENV['SESSION_DRIVER'] = 'file';

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Czyścimy sesję
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        // Usuwamy pliki sesji
        $sessionFiles = glob(Kernel::$projectPath . '/storage/session/sess_*');
        if (is_array($sessionFiles)) {
            foreach ($sessionFiles as $file) {
                @unlink($file);
            }
        }

        // Rekursywnie usuwamy katalog testowy
        $this->removeDirectory(Kernel::$projectPath);
    }

    /**
     * Rekursywnie usuwa katalog
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        if (is_array($objects)) {
            foreach ($objects as $object) {
                if ($object == "." || $object == "..") {
                    continue;
                }

                $path = $dir . "/" . $object;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    @unlink($path);
                }
            }
        }

        @rmdir($dir);
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

        // Wywołujemy destroy() zamiast session_destroy()
        $session->destroy();

        // Tworzymy nową instancję Session, aby mieć pewność, że sesja została zniszczona
        $newSession = new Session();
        $this->assertNull($newSession->get('user'));
    }

    public function testRegenerateSessionId()
    {
        $session = new Session();
        $session->set('test_key', 'test_value');

        $session->regenerate();

        $this->assertEquals('test_value', $session->get('test_key'));
    }

    public function testRegenerateSessionIdWithRemoveOldSession()
    {
        $session = new Session();
        $session->set('test_key', 'test_value');

        $session->regenerate(true);

        $this->assertEquals('test_value', $session->get('test_key'));
    }
}
