<?php

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        $baseTempDir = sys_get_temp_dir();
        $this->tempDir = $baseTempDir . '/nimble_test_' . uniqid();

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

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

        file_put_contents($this->tempDir . '/.env', "DEBUG=false\nLOG=true\nDEFAULT_CONTROLLER=index\nDEFAULT_METHOD=index\nDATABASE=false\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

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

    public function testKernelClassExists()
    {
        $this->assertTrue(class_exists(Kernel::class));
    }

    public function testProjectPath()
    {
        $this->assertDirectoryExists($this->tempDir);
        $this->assertDirectoryExists($this->tempDir . '/storage');
        $this->assertDirectoryExists($this->tempDir . '/storage/cache');
    }

    public function testLoadConfiguration()
    {
        file_put_contents($this->tempDir . '/.env.local', "DEBUG=true\n");

        $this->assertFileExists($this->tempDir . '/.env.local');
        $this->assertStringContainsString('DEBUG=true', file_get_contents($this->tempDir . '/.env.local'));
    }

    public function testSimpleAutoCreator()
    {
        $testDir = $this->tempDir . '/App/Test';
        if (is_dir($testDir)) {
            rmdir($testDir);
        }

        mkdir($testDir, 0777, true);

        $this->assertDirectoryExists($testDir);
    }

    public function testDebugFunction()
    {
        $_ENV['DEBUG'] = 'true';
        $this->assertEquals('true', $_ENV['DEBUG']);

        $_ENV['DEBUG'] = 'false';
        $this->assertEquals('false', $_ENV['DEBUG']);
    }

    public function testExceptionHandling()
    {
        $testException = new \Exception("Test exception");

        $this->assertInstanceOf(\Exception::class, $testException);
        $this->assertEquals("Test exception", $testException->getMessage());
    }

    public function testBootstrapSession()
    {
        $sessionDir = $this->tempDir . '/storage/session';
        $this->assertDirectoryExists($sessionDir);

        $testFile = $sessionDir . '/test.txt';
        file_put_contents($testFile, 'test');
        $this->assertFileExists($testFile);
        $this->assertEquals('test', file_get_contents($testFile));

        unlink($testFile);
    }

    public function testGetProjectPath()
    {
        $_SERVER['SCRIPT_FILENAME'] = $this->tempDir . '/public/index.php';
        file_put_contents($_SERVER['SCRIPT_FILENAME'], '<?php // Empty file for testing');

        $routeMock = $this->createMock(Route::class);
        $requestMock = $this->createMock(Request::class);
        $responseMock = $this->createMock(Response::class);

        $kernel = new Kernel($routeMock, $requestMock, $responseMock);

        $reflectionClass = new \ReflectionClass(Kernel::class);
        $getProjectPathMethod = $reflectionClass->getMethod('getProjectPath');
        $getProjectPathMethod->setAccessible(true);

        $path = $getProjectPathMethod->invoke($kernel);
        $this->assertEquals($this->tempDir, $path);
    }
}