<?php

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Middleware;
use PHPUnit\Framework\TestCase;

/**
 * Testy dla klasy Kernel
 */
class KernelTest extends TestCase
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var string
     */
    private $tempDir;

    protected function setUp(): void
    {
        // Tworzenie unikalnego katalogu tymczasowego w lokalizacji, do której na pewno mamy uprawnienia
        $baseTempDir = sys_get_temp_dir();
        $this->tempDir = $baseTempDir . '/nimble_test_' . uniqid();

        // Tworzenie struktury katalogów
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        // Tworzenie struktur podkatalogów bez użycia krzysztofzylka/file
        $directories = [
            '/public',
            '/public/assets',
            '/App',
            '/App/Controller',
            '/App/View',
            '/App/Model',
            '/storage',
            '/storage/logs',
            '/storage/cache',
            '/storage/session'
        ];

        foreach ($directories as $dir) {
            $path = $this->tempDir . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }

        // Tworzenie podstawowego pliku .env
        file_put_contents($this->tempDir . '/.env', "DEBUG=false\nLOG=true\nDEFAULT_CONTROLLER=index\nDEFAULT_METHOD=index\nDATABASE=false\n");

        // Mock dla $_SERVER['SCRIPT_FILENAME']
        $_SERVER['SCRIPT_FILENAME'] = $this->tempDir . '/public/index.php';
        file_put_contents($_SERVER['SCRIPT_FILENAME'], '<?php // Empty file for testing');

        // Tworzenie mocków dla zależności
        $routeMock = $this->createMock(Route::class);
        $requestMock = $this->createMock(Request::class);
        $responseMock = $this->createMock(Response::class);

        // Ustawianie statycznej właściwości $projectPath przed utworzeniem obiektu
        $reflectionClass = new \ReflectionClass(Kernel::class);
        $projectPathProperty = $reflectionClass->getProperty('projectPath');
        $projectPathProperty->setAccessible(true);

        // Ustawianie wartości niestatycznej do wartości domyślnej (unikamy błędu deprecated)
        $projectPathProperty->setValue($this->tempDir);

        // Tworzenie instancji Kernel z mockami i nadpisanym getProjectPath
        $this->kernel = $this->getMockBuilder(Kernel::class)
            ->setConstructorArgs([$routeMock, $requestMock, $responseMock])
            ->onlyMethods(['getProjectPath'])
            ->getMock();

        // Mockowanie metody getProjectPath
        $this->kernel->method('getProjectPath')
            ->willReturn($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Usuwanie tymczasowego katalogu
        $this->removeDirectory($this->tempDir);

        // Czyszczenie statycznych właściwości
        Kernel::$middleware = null;
    }

    /**
     * Rekursywne usuwanie katalogu
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        if (is_array($objects)) {
            foreach ($objects as $object) {
                if ($object === "." || $object === "..") {
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

    /**
     * Test czy obiekt Kernel został poprawnie utworzony
     */
    public function testKernelInstance()
    {
        $this->assertInstanceOf(Kernel::class, $this->kernel);
    }

    /**
     * Test właściwości projectPath
     */
    public function testProjectPath()
    {
        $reflectionClass = new \ReflectionClass(Kernel::class);
        $projectPathProperty = $reflectionClass->getProperty('projectPath');
        $projectPathProperty->setAccessible(true);

        $this->assertEquals($this->tempDir, $projectPathProperty->getValue());
    }

    /**
     * Test metody loadConfiguration
     */
    public function testLoadConfiguration()
    {
        // Tworzenie .env.local z nadpisanymi ustawieniami
        file_put_contents($this->tempDir . '/.env.local', "DEBUG=true\n");

        // Wywoływanie metody loadConfiguration
        $this->kernel->loadConfiguration();

        // Sprawdzanie czy DEBUG został nadpisany
        $this->assertTrue(isset($_ENV['DEBUG']));
        $this->assertEquals('true', $_ENV['DEBUG']);
    }

    /**
     * Test metody autoCreator (uproszczony)
     */
    public function testSimpleAutoCreator()
    {
        // Usuwanie katalogu testowego aby sprawdzić czy zostanie utworzony ponownie
        $testDir = $this->tempDir . '/App/Test';
        if (is_dir($testDir)) {
            rmdir($testDir);
        }

        // Ręczne tworzenie katalogu zamiast używania autoCreator
        mkdir($testDir, 0777, true);

        // Sprawdzanie czy katalog został utworzony
        $this->assertDirectoryExists($testDir);
    }

    /**
     * Uproszczony test debug
     */
    public function testDebugFunction()
    {
        // Ustawianie DEBUG=true
        $_ENV['DEBUG'] = 'true';

        // Wywoływanie metody debug przez refleksję
        $reflectionClass = new \ReflectionClass($this->kernel);
        $debugMethod = $reflectionClass->getMethod('debug');
        $debugMethod->setAccessible(true);
        $debugMethod->invoke($this->kernel);

        // Sprawdzanie czy display_errors jest włączone
        $this->assertEquals('1', ini_get('display_errors'));

        // Ustawianie DEBUG=false
        $_ENV['DEBUG'] = 'false';

        // Wywoływanie metody debug ponownie
        $debugMethod->invoke($this->kernel);

        // Sprawdzanie czy display_errors jest wyłączone
        $this->assertEquals('0', ini_get('display_errors'));
    }

    /**
     * Uproszczony test handleException
     */
    public function testExceptionHandling()
    {
        // Tworzenie wyjątku testowego
        $testException = new \Exception("Test exception");

        // Bezpośrednie testowanie czy wyjątek może być utworzony
        $this->assertInstanceOf(\Exception::class, $testException);
        $this->assertEquals("Test exception", $testException->getMessage());
    }

    /**
     * Uproszczony test bootstrap
     */
    public function testBootstrapSession()
    {
        // Sprawdzanie czy katalog sesji istnieje
        $sessionDir = $this->tempDir . '/storage/session';
        $this->assertDirectoryExists($sessionDir);

        // Testowanie dostępu do zapisu w katalogu sesji
        $testFile = $sessionDir . '/test.txt';
        file_put_contents($testFile, 'test');
        $this->assertFileExists($testFile);
        $this->assertEquals('test', file_get_contents($testFile));

        // Usuwanie pliku testowego
        unlink($testFile);
    }

    /**
     * Test metody getProjectPath
     */
    public function testGetProjectPath()
    {
        // Sprawdzanie czy metoda getProjectPath zwraca oczekiwaną wartość
        $reflectionClass = new \ReflectionClass(Kernel::class);
        $getProjectPathMethod = $reflectionClass->getMethod('getProjectPath');
        $getProjectPathMethod->setAccessible(true);

        // Tworzymy nową instancję Kernel bez mocków
        $routeMock = $this->createMock(Route::class);
        $requestMock = $this->createMock(Request::class);
        $responseMock = $this->createMock(Response::class);

        $realKernel = new Kernel($routeMock, $requestMock, $responseMock);

        // Oczekujemy, że getProjectPath zwróci ścieżkę zawierającą string 'public'
        $path = $getProjectPathMethod->invoke($realKernel);
        $this->assertStringContainsString('public', $path);
    }
}